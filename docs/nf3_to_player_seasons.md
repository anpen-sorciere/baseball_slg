# nf3 → player_seasons 変換仕様 v1

対象:

- nf3_batting_rows, nf3_pitching_rows に格納された nf3 シーズン成績
- それをゲーム用能力値として `player_seasons` に反映する

目的:

- 実際の成績をベースにした **0〜100 レーティング** を自動生成する
- 年度別・リーグ別の相対評価（同じ年・同じリーグ内での強さ）を反映する
- 「同じ選手の別年度版」も別オブジェクトとして扱えるようにする

---

## 1. 全体フロー

### 1.1 対象年・リーグの決定

- コマンド例: `php artisan nf3:build-player-seasons {year} {--league=セ}`
- 引数:
  - `year`: 必須。例: `2024`
  - `league`:
    - 任意。`セ`, `パ`, `CL`, `PL`, `NPB` など
    - 未指定なら、その year の全リーグを対象にする

### 1.2 処理の大まかな流れ

1. 対象年(+リーグ)の `nf3_batting_rows` / `nf3_pitching_rows` を取得
2. 各行ごとに
   - 選手 (`players`) & チーム (`teams`) と紐付け
   - 派生スタッツを計算（率・9回あたり成績など）
3. 年度＋リーグ内で、「能力値計算に使うベース指標」の平均・標準偏差を算出
4. 各選手ごとに「指標 → Zスコア → 0〜100 レーティング」に変換
5. `player_seasons` に `upsert`（`player_id + year` で1レコード）
   - 打者用能力値、投手用能力値、`overall_rating`, `role`, `is_two_way` などを更新
   - `nf3_batting_row_id`, `nf3_pitching_row_id` をセット

---

## 2. 共通ヘルパー定義

### 2.1 Zスコア

```
z = (value - mean) / stddev
```

stddev が 0 または非常に小さい場合（< 1e-6）は z = 0 とする

### 2.2 Zスコアから 0〜100 レーティングへ

```
rating = round( 50 + z * SCALE )
```

`SCALE = 15` を基本とする  
（z = +2 → 80, z = +1 → 65, z = 0 → 50, z = -1 → 35, z = -2 → 20）

最終的に 0〜100 に clamp:

```
rating = max(0, min(100, rating))
```

### 2.3 サンプル数による収縮（小さいサンプル対策）

打者:

```
min_pa_for_full_weight = 200
weight = min(1.0, pa / min_pa_for_full_weight)
final_rating = 50 + (raw_rating - 50) * weight
```

投手:

```
min_ip_for_full_weight = 50.0
weight = min(1.0, ip / min_ip_for_full_weight)
final_rating = 50 + (raw_rating - 50) * weight
```

---

## 3. 打者側の能力値仕様

### 3.1 ベースにする元スタッツ

`nf3` の打撃行 (`Nf3BattingRow`) から、少なくとも以下を取得できる前提:

- pa = plate_appearances（打席）
- ab = at_bats（打数）
- h = hits（安打）
- doubles = doubles
- triples = triples
- hr = home_runs
- bb = walks
- so = strikeouts
- sb = stolen_bases
- cs = caught_stealing
- errors = errors
- games = games

`nf3` 側に avg, slg, obp, ops があればそれを優先し、ない場合は下記で算出:

```
avg = h / ab                        （ab > 0 の場合）
tb = 1B + 2B*2 + 3B*3 + HR*4
slg = tb / ab                       （ab > 0）
obp = (H + BB + HBP) / (AB + BB + HBP + SF)
iso = slg - avg
bb_rate = bb / pa
k_rate = so / pa
sb_attempts = sb + cs
sb_success_rate = sb_attempts > 0 ? sb / sb_attempts : 0
```

### 3.2 打撃系能力値

#### 3.2.1 batting_contact（ミート）

指標:

- avg（+）
- k_rate（三振率, -）

```
z_avg    = z(avg)
z_krate  = z(k_rate)
contact_z = 0.7 * z_avg - 0.3 * z_krate
raw_rating = rating_from_z(contact_z)
```

サンプル補正後 → `batting_contact`

#### 3.2.2 batting_power（パワー）

指標:

- iso（長打力）
- hr_rate（本塁打率）

```
power_z = 0.7 * z_iso + 0.3 * z_hr_rate
raw_rating = rating_from_z(power_z)
```

サンプル補正後 → `batting_power`

#### 3.2.3 batting_eye（選球眼）

指標:

- bb_rate（+）
- obp_minus_avg（+）
- k_rate（-）

```
eye_z = 0.6 * z_bb + 0.4 * z_obp_diff - 0.2 * z_krate
raw_rating = rating_from_z(eye_z)
```

サンプル補正後 → `batting_eye`

#### 3.2.4 running_speed（走力）

指標:

- sb_attempt_rate
- sb_success_rate
- runs_per_game

```
speed_z = 0.5 * z_sb_attempt + 0.3 * z_sb_success + 0.2 * z_runs_game
raw_rating = rating_from_z(speed_z)
```

サンプル補正後 → `running_speed`

#### 3.2.5 defense（守備）

指標:

- errors_per_game = errors / games
- def_metric = - errors_per_game

```
z_def = z(def_metric)
raw_rating = rating_from_z(z_def)
```

→ `defense`

### 3.3 打者としての overall_rating

```
bat_overall_raw =
    0.35 * batting_contact +
    0.35 * batting_power +
    0.15 * batting_eye +
    0.15 * running_speed
bat_overall = round(bat_overall_raw)
```

---

## 4. 投手側の能力値仕様

### 4.1 ベースにする元スタッツ

`Nf3PitchingRow` から取得:

- ip = innings（投球回、2/3 を小数に変換）
- games, games_started, relief_games
- wins, losses, holds, saves
- era, whip
- hits_allowed, home_runs_allowed, strikeouts, walks
- runs_allowed, earned_runs

派生指標:

```
k9  = strikeouts * 9 / ip
bb9 = walks * 9 / ip
hr9 = home_runs_allowed * 9 / ip
k_bb_ratio = strikeouts / max(1, walks)
```

### 4.2 投手系能力値

#### 4.2.1 pitcher_velocity（球威・球速）

指標: k9（+）, hr9（-）

```
velocity_z = 0.8 * z_k9 - 0.2 * z_hr9
```

サンプル補正後 → `pitcher_velocity`

#### 4.2.2 pitcher_control（制球）

指標: bb9（-）, whip（-）, k_bb_ratio（+）

```
control_z = -0.5 * z_bb9 - 0.3 * z_whip + 0.2 * z_kbb
```

サンプル補正後 → `pitcher_control`

#### 4.2.3 pitcher_movement（キレ・変化）

指標: hr9（-）, era（-）

```
movement_z = -0.6 * z_hr9 - 0.4 * z_era
```

サンプル補正後 → `pitcher_movement`

#### 4.2.4 pitcher_stamina（スタミナ）

指標: ip, ip_per_game, games_started

```
stamina_z = 0.4 * z_ip + 0.4 * z_ip_per_game + 0.2 * z_gs
```

サンプル補正後 → `pitcher_stamina`

### 4.3 投手としての overall_rating

```
pit_overall_raw =
    0.3 * pitcher_velocity +
    0.3 * pitcher_control +
    0.2 * pitcher_movement +
    0.2 * pitcher_stamina
pit_overall = round(pit_overall_raw)
```

---

## 5. 二刀流・役割・overall_rating の決め方

### 5.1 二刀流判定

- 打者として有効: `pa >= 50`
- 投手として有効: `ip >= 30`

```
has_bat = (pa >= 50)
has_pit = (ip >= 30)
is_two_way = has_bat && has_pit ? 1 : 0
```

### 5.2 role カラム

優先順位:

```
if ip >= 30:
    if saves >= 10: role = 'closer'
    else if relief_games >= games * 0.5: role = 'reliever'
    else: role = 'starter'
else if pa >= 300: role = 'regular'
else if pa >= 50: role = 'part_time'
else: role = 'bench'
```

### 5.3 overall_rating の最終決定

- has_bat && !has_pit → overall = bat_overall
- has_pit && !has_bat → overall = pit_overall
- 両方ある場合:
  - role が `starter`, `reliever`, `closer` → `round(0.7 * pit_overall + 0.3 * bat_overall)`
  - それ以外 → `round(0.7 * bat_overall + 0.3 * pit_overall)`

`is_two_way` もセットする。

---

## 6. player_seasons への保存ルール

- upsert キー: `(player_id, year)`
- 反映カラム:
  - `team_id`, `league`, `uniform_number`, `position_main`
  - `overall_rating`, `role`, `is_two_way`
  - 打撃: `batting_contact`, `batting_power`, `batting_eye`, `running_speed`, `defense`
  - 投手: `pitcher_stamina`, `pitcher_control`, `pitcher_velocity`, `pitcher_movement`
  - 紐づけ: `nf3_batting_row_id`, `nf3_pitching_row_id`

---

## 7. 実装メモ

- サービス: `App\Services\PlayerSeasonBuilder`
- コマンド: `php artisan nf3:build-player-seasons {year} {--league=}`
- nf3 から name + team_id で `players` を検索、なければ自動作成
- 指標→Zスコア→レーティング→`player_seasons` upsert

---

## 8. 今後の拡張余地

- 守備能力の精緻化（ポジション補正など）
- クラッチ指標の導入
- 怪我しやすさ (durability) の追加
- 年度横断モード用の時代補正

#### 3.2.1 batting_contact（ミート）

使う指標:

- avg（打率） … プラス
- k_rate（三振率） … マイナス

年度＋リーグ内で:

- z_avg   = z(avg)
- z_krate = z(k_rate)

contact_z = 0.7 * z_avg - 0.3 * z_krate  
raw_rating = rating_from_z(contact_z)  
サンプル補正後 → `batting_contact`

#### 3.2.2 batting_power（パワー）

使う指標:

- iso = slg - avg
- hr_rate = hr / pa

年度＋リーグ内で:

- z_iso = z(iso)
- z_hr_rate = z(hr_rate)

power_z = 0.7 * z_iso + 0.3 * z_hr_rate  
raw_rating = rating_from_z(power_z)  
サンプル補正後 → `batting_power`

#### 3.2.3 batting_eye（選球眼）

使う指標:

- bb_rate（四球率）
- obp_minus_avg = obp - avg
- k_rate（三振率）

年度＋リーグ内で:

- z_bb = z(bb_rate)
- z_obp_diff = z(obp_minus_avg)
- z_krate = z(k_rate)

eye_z = 0.6 * z_bb + 0.4 * z_obp_diff - 0.2 * z_krate  
raw_rating = rating_from_z(eye_z)  
サンプル補正後 → `batting_eye`

#### 3.2.4 running_speed（走力）

使う指標:

- sb_attempt_rate = sb_attempts / pa
- sb_success_rate
- runs_per_game

年度＋リーグ内で:

- z_sb_attempt = z(sb_attempt_rate)
- z_sb_success = z(sb_success_rate)
- z_runs_game  = z(runs_per_game)

speed_z = 0.5 * z_sb_attempt + 0.3 * z_sb_success + 0.2 * z_runs_game  
raw_rating = rating_from_z(speed_z)  
サンプル補正後 → `running_speed`

#### 3.2.5 defense（守備）

簡易版として、まずはエラーの少なさを使用:

- errors_per_game = errors / games （games > 0）
- def_metric = - errors_per_game
- z_def = z(def_metric)

raw_rating = rating_from_z(z_def)  
→ `defense`

（後でポジション別の基礎値などを加える拡張余地あり）

### 3.3 打者としての overall_rating

打者専用の総合値:

bat_overall_raw =
- 0.35 * batting_contact
- + 0.35 * batting_power
- + 0.15 * batting_eye
- + 0.15 * running_speed

bat_overall = round(bat_overall_raw)

---

## 4. 投手側の能力値仕様

### 4.1 ベースにする元スタッツ

`Nf3PitchingRow` から取得:

- ip = innings（投球回。2/3 などは 0.2 or 0.1 で表現されていれば、内部で 0.666... 等に変換）
- games = games
- games_started = games_started
- relief_games = relief_games
- wins = wins
- losses = losses
- holds = holds
- saves = saves
- era = era
- whip = whip（なければ `(H + BB + HBP) / IP`）
- hits_allowed
- home_runs_allowed
- strikeouts
- walks
- runs_allowed
- earned_runs

派生指標:

- k9  = strikeouts * 9 / ip （ip > 0）
- bb9 = walks * 9 / ip
- hr9 = home_runs_allowed * 9 / ip
- k_bb_ratio = strikeouts / max(1, walks)

### 4.2 投手系能力値

#### 4.2.1 pitcher_velocity（球威・球速）

使う指標:

- k9（奪三振能力）
- hr9（被本塁打率、少ないほど良い）

年度＋リーグ内で:

- z_k9  = z(k9)
- z_hr9 = z(hr9)

velocity_z = 0.8 * z_k9 - 0.2 * z_hr9  
raw_rating = rating_from_z(velocity_z)  
サンプル補正後 → `pitcher_velocity`

#### 4.2.2 pitcher_control（制球）

使う指標:

- bb9（与四球率、少ないほど良い）
- whip
- k_bb_ratio

年度＋リーグ内で:

- z_bb9  = z(bb9)
- z_whip = z(whip)
- z_kbb  = z(k_bb_ratio)

control_z = -0.5 * z_bb9 - 0.3 * z_whip + 0.2 * z_kbb  
raw_rating = rating_from_z(control_z)  
サンプル補正後 → `pitcher_control`

#### 4.2.3 pitcher_movement（キレ・変化）

使う指標:

- hr9（少ないほど良い）
- era（小さいほど良い）

年度＋リーグ内で:

- z_hr9 = z(hr9)
- z_era = z(era)

movement_z = -0.6 * z_hr9 - 0.4 * z_era  
raw_rating = rating_from_z(movement_z)  
サンプル補正後 → `pitcher_movement`

#### 4.2.4 pitcher_stamina（スタミナ）

使う指標:

- ip（総投球回）
- ip_per_game = ip / games
- games_started

年度＋リーグ内で:

- ip_per_game = ip / games （games > 0）
- z_ip = z(ip)
- z_ip_per_game = z(ip_per_game)
- z_gs = z(games_started)

stamina_z = 0.4 * z_ip + 0.4 * z_ip_per_game + 0.2 * z_gs  
raw_rating = rating_from_z(stamina_z)  
サンプル補正後 → `pitcher_stamina`

### 4.3 投手としての overall_rating

pit_overall_raw =
- 0.3 * pitcher_velocity
- + 0.3 * pitcher_control
- + 0.2 * pitcher_movement
- + 0.2 * pitcher_stamina

pit_overall = round(pit_overall_raw)

---

## 5. 二刀流・役割・overall_rating の決め方

### 5.1 二刀流判定

閾値例:

- 打者として有効: `pa >= 50`
- 投手として有効: `ip >= 30`

has_bat = (pa >= 50)  
has_pit = (ip >= 30)  

is_two_way = (has_bat && has_pit) ? 1 : 0

### 5.2 role カラム

優先順位:

1. 投手成績があり、一定以上投げているなら投手ロール優先
2. そうでなければ打者ロール

投手側ロール例:

- ip >= 30 の場合:
  - saves >= 10 → `closer`
  - else if relief_games >= games * 0.5 → `reliever`
  - else → `starter`

打者側ロール例:

- pa >= 300 → `regular`
- pa >= 50  → `part_time`
- それ以外 → `bench`

### 5.3 overall_rating の最終決定

- has_bat かつ !has_pit → 打者専用:
  - overall_rating = bat_overall
- has_pit かつ !has_bat → 投手専用:
  - overall_rating = pit_overall
- 両方ある（二刀流）の場合:

  - role が `starter`, `reliever`, `closer` の場合:

    overall_rating = round(0.7 * pit_overall + 0.3 * bat_overall)

  - それ以外（野手寄り）の場合:

    overall_rating = round(0.7 * bat_overall + 0.3 * pit_overall)

`is_two_way` フラグも合わせて保存する。

---

## 6. player_seasons への保存ルール

### 6.1 upsert キー

- 一意キー: `(player_id, year)`

where 条件:

- player_id = {player_id}
- year = {year}

見つかれば update, なければ insert。

### 6.2 反映するカラム

共通:

- `player_id`
- `team_id`
- `year`
- `league`
- `uniform_number`
- `position_main`
- `role`
- `is_two_way`
- `overall_rating`
- `nf3_batting_row_id`
- `nf3_pitching_row_id`

打者能力:

- `batting_contact`
- `batting_power`
- `batting_eye`
- `running_speed`
- `defense`

投手能力:

- `pitcher_stamina`
- `pitcher_control`
- `pitcher_velocity`
- `pitcher_movement`

---

## 7. 実装メモ（構成案）

サービスクラス例:

- `App\Services\PlayerSeasonBuilder`
  - `buildForYear(int $year, ?string $league = null): void`
  - 役割:
    - nf3 行の読み取り
    - players の検索 or 自動作成
    - 指標計算・Zスコア・レーティング
    - player_seasons への upsert

コンソールコマンド例:

- `php artisan nf3:build-player-seasons {year} {--league=}`
  - 中で `PlayerSeasonBuilder` を呼び出す

正規化処理:

- 「年度＋リーグ」単位で、一度全選手分のベース指標を集めて平均・標準偏差を計算
- その後、各選手ごとに Zスコア → rating を計算して反映

---

## 8. 今後の拡張余地

- 守備能力の精緻化:
  - ポジション別の基礎守備値テーブルを用意して補正
- クラッチ補正:
  - 得点圏打率（`risp_avg`）や RISP OPS を用いて clutch パラメータ追加
- 怪我しやすさ（durability）:
  - 出場試合数や離脱情報を取り込めれば、別能力として追加可能
- 年度横断モード用補正:
  - 「打高・投高時代」を跨いだ比較を行うための、年代補正係数の導入
