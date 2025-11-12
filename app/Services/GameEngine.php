<?php

namespace App\Services;

use Illuminate\Support\Arr;

class GameEngine
{
    protected const STRATEGY_KEYS = [
        'offense_style',
        'pitching_style',
        'defense_style',
    ];

    protected const DEFAULT_STRATEGY = [
        'offense_style' => 'balanced',
        'pitching_style' => 'balanced',
        'defense_style' => 'balanced',
    ];

    /**
     * NPB同士の試合シミュレーション。従来の挙動を保ちつつ戦略パラメータを受け取れる。
     *
     * @param Team $teamA
     * @param Team $teamB
     * @param array{team_a?: array, team_b?: array} $strategyParams
     */
    public function simulateGame(array $teamAContext, array $teamBContext): array
    {
        return $this->runNineInningSimulation($teamAContext, $teamBContext);
    }

    /**
     * 実際の9回シミュレーションを実行。
     */
    public function runNineInningSimulation(array $teamAContext, array $teamBContext): array
    {
        $innings = [];
        $log = [];
        $playLog = [];

        $lineups = [
            'teamA' => $this->formatLineup($teamAContext['lineup'] ?? null),
            'teamB' => $this->formatLineup($teamBContext['lineup'] ?? null),
        ];

        $battingState = [
            'teamA' => [
                'index' => 0,
                'lineup' => array_values($lineups['teamA']['batters'] ?? []),
                'bench' => array_values($lineups['teamA']['bench_batters'] ?? []),
                'substituted' => [], // 交代された選手のIDを記録
                'rbi' => [], // 各打者の打点を記録 [player_season_id => rbi]
                'stats' => [], // 各打者の打撃成績を記録 [player_season_id => ['ab' => 0, 'h' => 0, 'hr' => 0]]
            ],
            'teamB' => [
                'index' => 0,
                'lineup' => array_values($lineups['teamB']['batters'] ?? []),
                'bench' => array_values($lineups['teamB']['bench_batters'] ?? []),
                'substituted' => [], // 交代された選手のIDを記録
                'rbi' => [], // 各打者の打点を記録 [player_season_id => rbi]
                'stats' => [], // 各打者の打撃成績を記録 [player_season_id => ['ab' => 0, 'h' => 0, 'hr' => 0]]
            ],
        ];

        $scoreA = 0;
        $scoreB = 0;

        $defenseOuts = [
            'teamA' => 0,
            'teamB' => 0,
        ];

        for ($i = 1; $i <= 9; $i++) {
            $scoreABefore = $scoreA;
            $topWalkoff = false;
            $this->simulateHalfInning(
                'teamA',
                'teamB',
                $teamAContext,
                $teamBContext,
                $lineups,
                $battingState,
                $i,
                true,
                $scoreA,
                $scoreB,
                $defenseOuts['teamB'],
                $log,
                $playLog,
                $topWalkoff
            );
            $runsTop = $scoreA - $scoreABefore;

            $runsBottom = null;
            $bottomPlayed = true;

            if ($i < 9 || $scoreA >= $scoreB) {
                $scoreBBefore = $scoreB;
                $walkoff = false;
                $this->simulateHalfInning(
                    'teamB',
                    'teamA',
                    $teamBContext,
                    $teamAContext,
                    $lineups,
                    $battingState,
                    $i,
                    false,
                    $scoreA,
                    $scoreB,
                $defenseOuts['teamA'],
                    $log,
                    $playLog,
                    $walkoff
                );
                $runsBottom = $scoreB - $scoreBBefore;
                if ($walkoff) {
                    $log[] = "ゲームセット！サヨナラで{$teamBContext['name']}の勝利！";
                    $innings[] = [
                        'inning' => $i,
                        'teamA' => $runsTop,
                        'teamB' => $runsBottom,
                    ];
                    break;
                }
                if ($i === 9 && $runsBottom !== null) {
                    if ($scoreB > $scoreA) {
                        $log[] = "ゲームセット！{$teamBContext['name']}の勝利！";
                    } elseif ($scoreB === $scoreA) {
                        $log[] = "ゲームセット！同点で試合終了。";
                    } else {
                        $log[] = "ゲームセット！{$teamAContext['name']}の勝利！";
                    }
                }
            } else {
                $bottomPlayed = false;
                $log[] = "ゲームセット！{$teamBContext['name']}の勝利！";
                $runsBottom = null;
                $innings[] = [
                    'inning' => $i,
                    'teamA' => $runsTop,
                    'teamB' => null,
                ];
                break;
            }

            $innings[] = [
                'inning' => $i,
                'teamA' => $runsTop,
                'teamB' => $bottomPlayed ? $runsBottom : null,
            ];
        }

        $battingStats = [
            'teamA' => $this->generateBattingStats($lineups['teamA']['batters'] ?? [], $scoreA, $battingState['teamA']['rbi'] ?? [], $battingState['teamA']['stats'] ?? []),
            'teamB' => $this->generateBattingStats($lineups['teamB']['batters'] ?? [], $scoreB, $battingState['teamB']['rbi'] ?? [], $battingState['teamB']['stats'] ?? []),
        ];

        $pitchingStats = [
            'teamA' => $this->generatePitchingStats($teamAContext['name'], $lineups['teamA'], $scoreB, $defenseOuts['teamA']),
            'teamB' => $this->generatePitchingStats($teamBContext['name'], $lineups['teamB'], $scoreA, $defenseOuts['teamB']),
        ];

        $mvp = $this->determineMvp($battingStats, $pitchingStats, $lineups);

        return [
            'score' => [
                'teamA' => $scoreA,
                'teamB' => $scoreB,
            ],
            'teamA_score' => $scoreA,
            'teamB_score' => $scoreB,
            'innings' => $innings,
            'lineups' => $lineups,
            'batting_stats' => $battingStats,
            'pitching_stats' => $pitchingStats,
            'log' => array_slice($log, 0, 20),
            'play_by_play' => $playLog,
            'mvp' => $mvp,
            'teamA_ratings' => $teamAContext['ratings'],
            'teamB_ratings' => $teamBContext['ratings'],
            'strategies' => [
                $teamAContext['key'] => $teamAContext['strategy'],
                $teamBContext['key'] => $teamBContext['strategy'],
            ],
        ];
    }

    /**
     * チーム全体の能力値をざっくり集計。
     */
    protected function calculateTeamRatings($seasons): array
    {
        if ($seasons->count() === 0) {
            return [
                'offense' => 50,
                'power' => 50,
                'eye' => 50,
                'speed' => 50,
                'defense' => 50,
                'pitch' => 50,
            ];
        }

        $count = $seasons->count();

        $sumContact = $seasons->sum('batting_contact');
        $sumPower = $seasons->sum('batting_power');
        $sumEye = $seasons->sum('batting_eye');
        $sumSpeed = $seasons->sum('running_speed');
        $sumDefense = $seasons->sum('defense');
        $sumPitchCtl = $seasons->sum('pitcher_control');
        $sumPitchMov = $seasons->sum('pitcher_movement');
        $sumPitchVel = $seasons->sum('pitcher_velocity');

        $offense = ($sumContact + $sumPower) / max(1, 2 * $count);
        $power = $sumPower / max(1, $count);
        $eye = $sumEye / max(1, $count);
        $speed = $sumSpeed / max(1, $count);
        $defense = $sumDefense / max(1, $count);
        $pitch = ($sumPitchCtl + $sumPitchMov + $sumPitchVel) / max(1, 3 * $count);

        return [
            'offense' => $offense,
            'power' => $power,
            'eye' => $eye,
            'speed' => $speed,
            'defense' => $defense,
            'pitch' => $pitch,
        ];
    }

    /**
     * 1イニング（攻撃側 vs 守備側）をシミュレーション。
     */
    protected function simulateHalfInning(
        string $offenseKey,
        string $defenseKey,
        array $offenseContext,
        array $defenseContext,
        array &$lineups,
        array &$battingState,
        int $inning,
        bool $isTop,
        int &$scoreA,
        int &$scoreB,
        int &$defenseOuts,
        array &$summaryLog,
        array &$playLog,
        bool &$walkoff = false
    ): int {
        $outs = 0;
        $bases = [false, false, false];
        $walkoff = false;

        $offenseRatings = $offenseContext['ratings'];
        $defenseRatings = $defenseContext['ratings'];

        $offenseName = $offenseContext['name'];
        $defenseName = $defenseContext['name'];
        $pitcher = $lineups[$defenseKey]['pitcher'] ?? ['name' => $defenseName];

        $halfLabel = $isTop ? '表' : '裏';

        $runsBefore = $offenseKey === 'teamA' ? $scoreA : $scoreB;

        $hitProb = $this->clamp(0.12 + ($offenseRatings['offense'] - $defenseRatings['pitch']) / 900, 0.03, 0.30);
        $hrProb = $this->clamp(0.02 + ($offenseRatings['power'] - $defenseRatings['pitch']) / 1100, 0.005, 0.12);
        $walkProb = $this->clamp(0.06 + ($offenseRatings['eye'] - $defenseRatings['pitch']) / 1000, 0.01, 0.18);

        while ($outs < 3) {
            $batter = $this->getNextBatter($offenseKey, $battingState);
            $batterName = $batter['name'] ?? '無名選手';
            $pitcherName = $pitcher['name'] ?? "{$defenseName}投手";

            $roll = mt_rand() / mt_getrandmax();
            $resultType = 'out';
            $runsScored = 0;
            $rbi = 0;

            $prevBases = $bases;

            if ($roll < $hrProb) {
                // 本塁打：打者自身も含めて得点した人数が打点
                $runsScored = 1;
                foreach ($prevBases as $occupied) {
                    if ($occupied) {
                        $runsScored++;
                    }
                }
                $rbi = $runsScored; // 本塁打の場合は全得点が打点
                $bases = [false, false, false];
                $resultType = 'homerun';
            } elseif ($roll < $hrProb + $hitProb) {
                // 安打：走者が得点した数が打点（打者自身が得点した場合は含まない）
                $hitRoll = mt_rand(1, 100);
                $rbi = 0; // 走者が得点した数
                if ($hitRoll <= 10) {
                    $resultType = 'triple';
                    $runsScored = 0;
                    if ($prevBases[2]) {
                        $runsScored++;
                        $rbi++;
                    }
                    if ($prevBases[1]) {
                        $runsScored++;
                        $rbi++;
                    }
                    if ($prevBases[0]) {
                        $runsScored++;
                        $rbi++;
                    }
                    // 三塁打では打者自身も得点できる可能性があるが、通常は含まない
                    $bases = [false, false, true];
                } elseif ($hitRoll <= 35) {
                    $resultType = 'double';
                    $runsScored = 0;
                    if ($prevBases[2]) {
                        $runsScored++;
                        $rbi++;
                    }
                    if ($prevBases[1]) {
                        $runsScored++;
                        $rbi++;
                    }
                    // 二塁打では打者自身は得点しない
                    $bases = [
                        false,
                        true,
                        $prevBases[0],
                    ];
                } else {
                    $resultType = 'single';
                    $runsScored = 0;
                    if ($prevBases[2]) {
                        $runsScored++;
                        $rbi++;
                    }
                    // 一塁打では打者自身は得点しない
                    $bases = [
                        true,
                        $prevBases[0],
                        $prevBases[1],
                    ];
                }
            } elseif ($roll < $hrProb + $hitProb + $walkProb) {
                // 四死球：満塁の場合は1打点、それ以外は0打点
                $resultType = 'walk';
                $runsScored = 0;
                $rbi = 0;
                if ($prevBases[0] && $prevBases[1] && $prevBases[2]) {
                    $runsScored++;
                    $rbi = 1; // 満塁の場合は1打点
                }
                $bases = $this->advanceOnWalk($prevBases);
            } else {
                // アウト：打点は0
                $strikeoutChance = $this->clamp(0.18 + ($defenseRatings['pitch'] - $offenseRatings['offense']) / 900, 0.08, 0.45);
                $resultType = (mt_rand() / mt_getrandmax() < $strikeoutChance) ? 'strikeout' : 'out';
                $runsScored = 0;
                $rbi = 0;
                $outs++;
            }

            // 得点を記録
            if ($runsScored > 0) {
                if ($offenseKey === 'teamA') {
                    $scoreA += $runsScored;
                } else {
                    $scoreB += $runsScored;
                }
            }

            // 打点を記録
            $batterId = $batter['player_season_id'] ?? null;
            if ($batterId && $rbi > 0) {
                if (!isset($battingState[$offenseKey]['rbi'][$batterId])) {
                    $battingState[$offenseKey]['rbi'][$batterId] = 0;
                }
                $battingState[$offenseKey]['rbi'][$batterId] += $rbi;
            }

            // 打撃成績を記録
            if ($batterId) {
                if (!isset($battingState[$offenseKey]['stats'][$batterId])) {
                    $battingState[$offenseKey]['stats'][$batterId] = [
                        'ab' => 0,
                        'h' => 0,
                        'hr' => 0,
                    ];
                }
                
                // 打数を記録（四死球と犠牲バント以外は打数）
                if ($resultType !== 'walk' && $resultType !== 'hit_by_pitch') {
                    $battingState[$offenseKey]['stats'][$batterId]['ab']++;
                }
                
                // 安打を記録
                if (in_array($resultType, ['single', 'double', 'triple', 'homerun'], true)) {
                    $battingState[$offenseKey]['stats'][$batterId]['h']++;
                }
                
                // 本塁打を記録
                if ($resultType === 'homerun') {
                    $battingState[$offenseKey]['stats'][$batterId]['hr']++;
                }
            }

            $playLog[] = [
                'batter_id' => $batterId, // 打者のIDを記録
                'inning' => $inning,
                'half' => $isTop ? 'top' : 'bottom',
                'batter_team' => $offenseKey === 'teamA' ? 'A' : 'B',
                'batter_name' => $batterName,
                'pitcher_name' => $pitcherName,
                'result_type' => $resultType,
                'rbi' => $rbi,
                'runs_scored' => $runsScored,
                'score_after' => [
                    'teamA' => $scoreA,
                    'teamB' => $scoreB,
                ],
                'description' => $this->buildDescription(
                    $inning,
                    $isTop,
                    $batterName,
                    $pitcherName,
                    $resultType,
                    $runsScored,
                    $rbi,
                    $scoreA,
                    $scoreB
                ),
            ];

            if (!$isTop && $inning >= 9 && $scoreB > $scoreA) {
                $walkoff = true;
                break;
            }

            if ($outs >= 3) {
                break;
            }
        }

        $defenseOuts += $outs;

        $runsScoredHalf = ($offenseKey === 'teamA' ? $scoreA : $scoreB) - $runsBefore;

        if ($walkoff) {
            $summaryLog[] = "{$offenseName}がサヨナラ勝ちを決めた！";
        } elseif ($runsScoredHalf > 0) {
            $summaryLog[] = "{$inning}回{$halfLabel}終了：{$offenseName}が{$runsScoredHalf}点を奪取。";
        } else {
            $summaryLog[] = "{$inning}回{$halfLabel}終了：{$offenseName}は無得点。";
        }

        return $runsScoredHalf;
    }

    protected function formatLineup(?array $lineup): array
    {
        if (!$lineup) {
            return [
                'batters' => [],
                'bench_batters' => [],
                'pitcher' => null,
                'relievers' => [],
                'closers' => [],
            ];
        }

        // スタメン打者（打順1～9番のみ）
        $batters = [];
        $battersCollection = $lineup['batters'] ?? collect();
        $battersCollection = $battersCollection instanceof \Illuminate\Support\Collection
            ? $battersCollection->values()
            : collect($battersCollection)->values();

        // 打順でソート（1～9番のみ）
        // 打順が設定されていない場合は、最初の9人に自動的に1～9番を割り当て
        $sortedBatters = $battersCollection->filter(function ($season) {
            return $season !== null;
        })->take(9)->values();

        // 打順が設定されている選手と設定されていない選手を分ける
        $battersWithOrder = $sortedBatters->filter(function ($season) {
            $order = $season->batting_order ?? null;
            return $order !== null && $order >= 1 && $order <= 9;
        });

        $battersWithoutOrder = $sortedBatters->filter(function ($season) {
            $order = $season->batting_order ?? null;
            return $order === null || $order < 1 || $order > 9;
        });

        // 打順が設定されている選手をソート
        $sortedWithOrder = $battersWithOrder->sortBy('batting_order')->values();
        
        // 打順が設定されていない選手に自動的に1～9番を割り当て（既存の打順を避ける）
        $usedOrders = $sortedWithOrder->pluck('batting_order')->toArray();
        $availableOrders = array_diff(range(1, 9), $usedOrders);
        $orderIndex = 0;
        
        $sortedWithoutOrder = $battersWithoutOrder->map(function ($season) use (&$orderIndex, $availableOrders) {
            if ($orderIndex < count($availableOrders)) {
                $season->batting_order = array_values($availableOrders)[$orderIndex];
                $orderIndex++;
            } else {
                // 9番まで埋まっている場合は、次の番号を割り当て（通常は発生しない）
                $season->batting_order = 9;
            }
            return $season;
        });

        // 両方を結合してソート
        $allSortedBatters = $sortedWithOrder->concat($sortedWithoutOrder)->sortBy('batting_order')->values();

        foreach ($allSortedBatters as $season) {
            if (!$season) {
                continue;
            }
            $player = $season->player ?? null;
            $order = $season->batting_order ?? 0;
            $batters[] = [
                'order' => $order,
                'name' => ($player && isset($player->name)) ? $player->name : '不明',
                'position' => $season->position_1 ?? (($player && isset($player->position_1)) ? $player->position_1 : '--'),
                'contact' => (int) ($season->batting_contact ?? 0),
                'power' => (int) ($season->batting_power ?? 0),
                'eye' => (int) ($season->batting_eye ?? 0),
                'speed' => (int) ($season->running_speed ?? 0),
                'defense' => (int) ($season->defense ?? 0),
                'player_season_id' => $season->player_season_id ?? $season->id ?? null,
            ];
        }

        // 控え選手（代打・守備交代用）
        $benchBatters = [];
        $benchBattersCollection = $lineup['bench_batters'] ?? collect();
        $benchBattersCollection = $benchBattersCollection instanceof \Illuminate\Support\Collection
            ? $benchBattersCollection->values()
            : collect($benchBattersCollection)->values();

        foreach ($benchBattersCollection as $season) {
            if (!$season) {
                continue;
            }
            $player = $season->player ?? null;
            $benchBatters[] = [
                'name' => ($player && isset($player->name)) ? $player->name : '不明',
                'position' => $season->position_1 ?? (($player && isset($player->position_1)) ? $player->position_1 : '--'),
                'contact' => (int) ($season->batting_contact ?? 0),
                'power' => (int) ($season->batting_power ?? 0),
                'eye' => (int) ($season->batting_eye ?? 0),
                'speed' => (int) ($season->running_speed ?? 0),
                'defense' => (int) ($season->defense ?? 0),
                'player_season_id' => $season->player_season_id ?? $season->id ?? null,
            ];
        }

        $pitcherSeason = $lineup['pitcher'] ?? null;
        $pitcherPlayer = $pitcherSeason?->player;
        $pitcher = $pitcherSeason ? [
            'name' => ($pitcherPlayer && isset($pitcherPlayer->name)) ? $pitcherPlayer->name : '不明',
            'position' => $pitcherSeason->position_1 ?? 'P',
            'stamina' => (int) ($pitcherSeason->pitcher_stamina ?? 0),
            'control' => (int) ($pitcherSeason->pitcher_control ?? 0),
            'velocity' => (int) ($pitcherSeason->pitcher_velocity ?? 0),
            'movement' => (int) ($pitcherSeason->pitcher_movement ?? 0),
        ] : null;

        $relievers = [];
        $relieversCollection = $lineup['relievers'] ?? collect();
        $relieversCollection = $relieversCollection instanceof \Illuminate\Support\Collection
            ? $relieversCollection->values()
            : collect($relieversCollection)->values();

        foreach ($relieversCollection as $season) {
            if (!$season) {
                continue;
            }
            $player = $season->player ?? null;
            $relievers[] = [
                'name' => ($player && isset($player->name)) ? $player->name : '不明',
                'stamina' => (int) ($season->pitcher_stamina ?? 0),
                'control' => (int) ($season->pitcher_control ?? 0),
                'velocity' => (int) ($season->pitcher_velocity ?? 0),
                'movement' => (int) ($season->pitcher_movement ?? 0),
            ];
        }

        $closers = [];
        $closersCollection = $lineup['closers'] ?? collect();
        $closersCollection = $closersCollection instanceof \Illuminate\Support\Collection
            ? $closersCollection->values()
            : collect($closersCollection)->values();

        foreach ($closersCollection as $season) {
            if (!$season) {
                continue;
            }
            $player = $season->player ?? null;
            $closers[] = [
                'name' => ($player && isset($player->name)) ? $player->name : '不明',
                'stamina' => (int) ($season->pitcher_stamina ?? 0),
                'control' => (int) ($season->pitcher_control ?? 0),
                'velocity' => (int) ($season->pitcher_velocity ?? 0),
                'movement' => (int) ($season->pitcher_movement ?? 0),
            ];
        }

        return [
            'batters' => $batters,
            'bench_batters' => $benchBatters,
            'pitcher' => $pitcher,
            'relievers' => $relievers,
            'closers' => $closers,
        ];
    }

    protected function generateBattingStats(array $batters, int $totalRuns, array $rbiByPlayerId = [], array $statsByPlayerId = []): array
    {
        $stats = [];
        if (empty($batters)) {
            return $stats;
        }

        foreach ($batters as $index => $batter) {
            $playerId = $batter['player_season_id'] ?? null;
            
            // 実際の試合経過から打撃成績を取得
            if ($playerId && isset($statsByPlayerId[$playerId])) {
                $atBats = $statsByPlayerId[$playerId]['ab'] ?? 0;
                $hits = $statsByPlayerId[$playerId]['h'] ?? 0;
                $hr = $statsByPlayerId[$playerId]['hr'] ?? 0;
            } else {
                // 試合に出場しなかった場合は0
                $atBats = 0;
                $hits = 0;
                $hr = 0;
            }

            // 実際の試合で記録された打点を使用
            $rbi = $playerId && isset($rbiByPlayerId[$playerId]) ? $rbiByPlayerId[$playerId] : 0;

            $stats[] = [
                'order' => $batter['order'],
                'name' => $batter['name'],
                'ab' => $atBats,
                'h' => $hits,
                'hr' => $hr,
                'rbi' => $rbi,
            ];
        }

        return $stats;
    }

    protected function generatePitchingStats(string $teamName, array $pitchingLineup, int $runsAllowed, int $outsRecorded): array
    {
        $starter = $pitchingLineup['pitcher'] ?? null;
        $relievers = $pitchingLineup['relievers'] ?? [];
        $closers = $pitchingLineup['closers'] ?? [];

        if (!$starter && empty($relievers) && empty($closers)) {
            return [];
        }

        $outsRecorded = max(0, $outsRecorded);

        if ($outsRecorded === 0) {
            $outsRecorded = 3;
        }

        $starterStamina = $starter['stamina'] ?? 60;
        $starterControl = $starter['control'] ?? 60;
        $starterVelocity = $starter['velocity'] ?? 60;
        $starterMovement = $starter['movement'] ?? 60;

        $remainingOuts = $outsRecorded;
        $remainingRuns = $runsAllowed;
        $remainingStrikeouts = max(0, (int) round(($starterVelocity / 14) + ($starterMovement / 16) + mt_rand(0, 3)));

        $arms = [];
        
        // 先発投手のアウト数を計算
        // スタミナに基づいて投球回数を決定
        // スタミナ100の場合：18～24アウト（6～8回）
        // スタミナ80の場合：15～21アウト（5～7回）
        // スタミナ60の場合：12～18アウト（4～6回）
        // スタミナ40の場合：9～15アウト（3～5回）
        $starterOutsPotential = $starter 
            ? max(9, (int) round(($starterStamina / 100) * 18 + mt_rand(-3, 6)))
            : 0;
        $starterOuts = min($remainingOuts, $starterOutsPotential);
        
        if ($starter && $starterOuts > 0) {
            $arms[] = [
                'name' => $starter['name'] ?? "{$teamName}投手",
                'stamina' => $starterStamina,
                'control' => $starterControl,
                'velocity' => $starterVelocity,
                'movement' => $starterMovement,
                'outs' => $starterOuts, // 先発投手のアウト数を保存
            ];
            $remainingOuts -= $starterOuts;
        }

        // 9回目（27アウト）以降のアウト数を計算
        $outsIn9th = max(0, $outsRecorded - 27);
        $outsBefore9thRemaining = max(0, min(27, $outsRecorded) - $starterOuts);

        // 2番手以降（9回未満）は中継ぎから選択
        $relieverIndex = 0;
        $remainingRelieverOuts = $outsBefore9thRemaining;
        foreach ($relievers as $reliever) {
            if ($remainingRelieverOuts <= 0) {
                break;
            }
            $relieverStamina = isset($reliever['stamina']) && $reliever['stamina'] > 0 
                ? (int) $reliever['stamina'] 
                : max(30, $starterStamina - 20 - $relieverIndex * 5);
            
            // 中継ぎ投手が投げられるアウト数を計算
            $relieverOutsPotential = max(1, (int) round(($relieverStamina / 100) * 9 + mt_rand(0, 4)));
            $relieverOuts = min($remainingRelieverOuts, $relieverOutsPotential);
            
            // リリーフ投手の実際の能力値を使用（設定されていない場合のみ先発から計算）
            $arms[] = [
                'name' => $reliever['name'] ?? "{$teamName}リリーフ" . ($relieverIndex + 1),
                'stamina' => $relieverStamina,
                'control' => isset($reliever['control']) && $reliever['control'] > 0 
                    ? (int) $reliever['control'] 
                    : max(38, $starterControl - 10 - $relieverIndex * 5),
                'velocity' => isset($reliever['velocity']) && $reliever['velocity'] > 0 
                    ? (int) $reliever['velocity'] 
                    : max(40, $starterVelocity - 8 - $relieverIndex * 4),
                'movement' => isset($reliever['movement']) && $reliever['movement'] > 0 
                    ? (int) $reliever['movement'] 
                    : max(40, $starterMovement - 8 - $relieverIndex * 4),
                'outs' => $relieverOuts, // 中継ぎ投手のアウト数を保存
            ];
            $remainingRelieverOuts -= $relieverOuts;
            $remainingOuts -= $relieverOuts;
            $relieverIndex++;
        }

        // 9回目（27アウト以降）の投手交代時は抑えから選択
        $remainingCloserOuts = $outsIn9th;
        if ($remainingCloserOuts > 0 && !empty($closers)) {
            foreach ($closers as $closer) {
                if ($remainingCloserOuts <= 0) {
                    break;
                }
                $closerStamina = isset($closer['stamina']) && $closer['stamina'] > 0 
                    ? (int) $closer['stamina'] 
                    : max(30, $starterStamina - 20);
                
                // 抑え投手が投げられるアウト数を計算
                $closerOutsPotential = max(1, (int) round(($closerStamina / 100) * 9 + mt_rand(0, 4)));
                $closerOuts = min($remainingCloserOuts, $closerOutsPotential);
                
                // 抑え投手の実際の能力値を使用
                $arms[] = [
                    'name' => $closer['name'] ?? "{$teamName}抑え",
                    'stamina' => $closerStamina,
                    'control' => isset($closer['control']) && $closer['control'] > 0 
                        ? (int) $closer['control'] 
                        : max(38, $starterControl - 10),
                    'velocity' => isset($closer['velocity']) && $closer['velocity'] > 0 
                        ? (int) $closer['velocity'] 
                        : max(40, $starterVelocity - 8),
                    'movement' => isset($closer['movement']) && $closer['movement'] > 0 
                        ? (int) $closer['movement'] 
                        : max(40, $starterMovement - 8),
                    'outs' => $closerOuts, // 抑え投手のアウト数を保存
                ];
                $remainingCloserOuts -= $closerOuts;
                $remainingOuts -= $closerOuts;
            }
        }

        // 残りのアウト数がある場合、最後の投手に追加
        if ($remainingOuts > 0 && !empty($arms)) {
            $arms[count($arms) - 1]['outs'] += $remainingOuts;
        }

        $statLines = [];

        foreach ($arms as $arm) {
            $outsThis = $arm['outs'] ?? 0;
            if ($outsThis <= 0) {
                continue;
            }

            $efficiency = max(0.25, 1.05 - ($arm['control'] / 140));
            $erThis = min($remainingRuns, max(0, (int) round($outsThis * $efficiency + mt_rand(-1, 1))));
            $remainingRuns -= $erThis;

            $strikeoutChance = max(0.3, ($arm['velocity'] + $arm['movement']) / 230);
            $soThis = max(0, min($remainingStrikeouts, (int) round($outsThis * $strikeoutChance + mt_rand(0, 1))));
            $remainingStrikeouts -= $soThis;

            $statLines[] = [
                'name' => $arm['name'],
                'outs' => $outsThis,
                'ip' => $this->formatOuts($outsThis),
                'er' => $erThis,
                'so' => $soThis,
            ];
        }

        if ($remainingRuns > 0 && !empty($statLines)) {
            $statLines[count($statLines) - 1]['er'] += $remainingRuns;
        }

        return $statLines;
    }

    protected function determineMvp(array $battingStats, array $pitchingStats, array $lineups): array
    {
        $candidates = [];

        foreach (['teamA', 'teamB'] as $teamKey) {
            foreach ($battingStats[$teamKey] ?? [] as $stat) {
                $score = ($stat['rbi'] * 3) + ($stat['hr'] * 4) + ($stat['h'] * 1.5);
                $candidates[] = [
                    'team' => $teamKey,
                    'name' => $stat['name'],
                    'type' => 'batter',
                    'score' => $score,
                    'detail' => sprintf('%d打数%d安打%d本塁打%d打点', $stat['ab'], $stat['h'], $stat['hr'], $stat['rbi']),
                ];
            }

            foreach ($pitchingStats[$teamKey] ?? [] as $stat) {
                $outsValue = $stat['outs'] ?? 0;
                $ipValue = $outsValue / 3;
                $score = ($ipValue * 1.5) + ($stat['so'] * 1.2) - ($stat['er'] * 2.5);
                $candidates[] = [
                    'team' => $teamKey,
                    'name' => $stat['name'],
                    'type' => 'pitcher',
                    'score' => $score,
                    'detail' => sprintf('投球回%s、失点%d、奪三振%d', $stat['ip'], $stat['er'], $stat['so']),
                ];
            }
        }

        if (empty($candidates)) {
            $fallbackTeam = 'A';
            $fallbackBatter = $lineups['teamA']['batters'][0] ?? null;
            if (!$fallbackBatter && !empty($lineups['teamB']['batters'][0])) {
                $fallbackBatter = $lineups['teamB']['batters'][0];
                $fallbackTeam = 'B';
            }

            return [
                'team' => $fallbackTeam,
                'name' => $fallbackBatter['name'] ?? '該当なし',
                'reason' => '活躍が光りました。',
            ];
        }

        usort($candidates, static function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $top = $candidates[0];

        return [
            'team' => $top['team'] === 'teamA' ? 'A' : 'B',
            'name' => $top['name'],
            'type' => $top['type'],
            'reason' => $top['detail'],
        ];
    }

    protected function getNextBatter(string $teamKey, array &$battingState): array
    {
        $lineup = $battingState[$teamKey]['lineup'] ?? [];
        $substituted = $battingState[$teamKey]['substituted'] ?? [];
        $count = count($lineup);

        if ($count === 0) {
            return [
                'name' => '名無しの打者',
                'order' => 0,
                'position' => '--',
                'player_season_id' => null,
            ];
        }

        // 打順は1～9番のみを循環（控えは含めない）
        $currentIndex = $battingState[$teamKey]['index'] ?? 0;
        $currentIndex = $currentIndex % $count;

        $batter = $lineup[$currentIndex];
        
        // 交代された選手が打順に回ってきた場合、控えから代打を選択（簡易実装）
        // 将来的には、より詳細な代打ロジックを実装可能
        $batterId = $batter['player_season_id'] ?? null;
        if ($batterId && in_array($batterId, $substituted, true)) {
            // 交代された選手の場合は、控えから代打を選択
            $bench = $battingState[$teamKey]['bench'] ?? [];
            $availableBench = array_filter($bench, function ($benchPlayer) use ($substituted) {
                $benchId = $benchPlayer['player_season_id'] ?? null;
                return $benchId && !in_array($benchId, $substituted, true);
            });
            
            if (!empty($availableBench)) {
                // 控えから最初の利用可能な選手を代打として選択
                $pinchHitter = reset($availableBench);
                $batter = [
                    'order' => $batter['order'] ?? 0,
                    'name' => $pinchHitter['name'] ?? '代打',
                    'position' => $pinchHitter['position'] ?? '--',
                    'contact' => $pinchHitter['contact'] ?? 0,
                    'power' => $pinchHitter['power'] ?? 0,
                    'eye' => $pinchHitter['eye'] ?? 0,
                    'speed' => $pinchHitter['speed'] ?? 0,
                    'defense' => $pinchHitter['defense'] ?? 0,
                    'player_season_id' => $pinchHitter['player_season_id'] ?? null,
                ];
                // 代打として使用した選手を交代済みリストに追加
                if ($batter['player_season_id']) {
                    $battingState[$teamKey]['substituted'][] = $batter['player_season_id'];
                }
            }
        }

        $battingState[$teamKey]['index'] = ($currentIndex + 1) % $count;

        return $batter;
    }

    protected function advanceOnWalk(array $bases): array
    {
        return [
            true,
            $bases[0] || $bases[1],
            $bases[2] || ($bases[1] && $bases[0]),
        ];
    }

    protected function buildDescription(
        int $inning,
        bool $isTop,
        string $batter,
        string $pitcher,
        string $resultType,
        int $runsScored,
        int $rbi,
        int $scoreA,
        int $scoreB
    ): string {
        $scoreLabel = "（{$scoreA}-{$scoreB}）";

        switch ($resultType) {
            case 'homerun':
                $title = $runsScored >= 2 ? "{$runsScored}ランホームラン" : 'ソロホームラン';
                return "{$batter}が{$title}！{$scoreLabel}";
            case 'triple':
                return "{$batter}の三塁打！" . ($runsScored > 0 ? $scoreLabel : '');
            case 'double':
                return "{$batter}の二塁打！" . ($runsScored > 0 ? $scoreLabel : '');
            case 'single':
                return "{$batter}のヒット！" . ($runsScored > 0 ? $scoreLabel : '');
            case 'walk':
                if ($runsScored > 0) {
                    return "{$batter}が押し出し四球！{$scoreLabel}";
                }
                return "{$batter}がフォアボールで出塁。";
            case 'strikeout':
                return "{$pitcher}が{$batter}を三振に斬る！";
            case 'out':
            default:
                return "{$batter}は凡退。";
        }
    }

    protected function formatOuts(int $outs): string
    {
        $innings = intdiv($outs, 3);
        $remainder = $outs % 3;

        if ($remainder === 0) {
            return (string) $innings;
        }

        $fraction = $remainder === 1 ? '1/3' : '2/3';

        if ($innings === 0) {
            return $fraction;
        }

        return "{$innings} {$fraction}";
    }

    protected function normalizeStrategy(array $strategy = []): array
    {
        $filtered = Arr::only($strategy, self::STRATEGY_KEYS);

        $nonEmpty = array_filter(
            $filtered,
            static fn($value) => $value !== null && $value !== ''
        );

        return array_merge(self::DEFAULT_STRATEGY, $nonEmpty);
    }

    protected function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
