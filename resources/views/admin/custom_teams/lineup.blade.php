@extends('layouts.app')

@section('title', 'チーム編成')

@section('content')
    <div class="mb-4">
        <h1 class="h3">チーム編成</h1>
        <p class="text-muted mb-1">
            {{ $customTeam->name }}（{{ $customTeam->year }}年）
        </p>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="{{ route('admin.custom-teams.index') }}" class="btn btn-sm btn-outline-secondary">一覧へ戻る</a>
            <a href="{{ route('admin.custom-teams.roster.edit', $customTeam) }}" class="btn btn-sm btn-outline-success">ベンチ登録へ</a>
            <span class="badge bg-primary">野手: {{ $batterCount }} / 13</span>
            <span class="badge bg-success">投手: {{ $pitcherCount }} / 13</span>
        </div>
    </div>

    @if ($rosterBatters->isEmpty() || $rosterPitchers->isEmpty())
        <div class="alert alert-warning">
            ベンチ登録された野手・投手が不足しています。先に「ベンチ登録」から登録してください。
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.custom-teams.lineup.update', $customTeam) }}" method="POST">
                @csrf

                <h2 class="h5 mb-3">スターティングメンバー（打順）</h2>
                @php
                    $positionOptions = [
                        '' => '--- 選択してください ---',
                        'C' => 'C（捕手）',
                        '1B' => '1B（一塁手）',
                        '2B' => '2B（二塁手）',
                        '3B' => '3B（三塁手）',
                        'SS' => 'SS（遊撃手）',
                        'LF' => 'LF（左翼手）',
                        'CF' => 'CF（中堅手）',
                        'RF' => 'RF（右翼手）',
                        'DH' => 'DH（指名打者）',
                    ];
                @endphp
                <div class="table-responsive">
                    <table class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>打順</th>
                                <th>選手</th>
                                <th>ポジション</th>
                                <th>所属</th>
                            </tr>
                        </thead>
                        <tbody>
                            @for ($order = 1; $order <= 9; $order++)
                                @php
                                    $existing = $startingBattersByOrder->get($order) ?? null;
                                @endphp
                                <tr>
                                    <td class="fw-bold">{{ $order }}</td>
                                    <td>
                                        <select name="batters[{{ $order }}][player_season_id]" class="form-select" data-order="{{ $order }}">
                                            <option value="">--- 選択してください ---</option>
                                            @foreach ($rosterBatters as $entry)
                                                @php
                                                    $season = optional($entry->playerSeason);
                                                    if (!$season || !$season->id) {
                                                        continue;
                                                    }
                                                    $label = sprintf(
                                                        '%s / %s (%s) [打:%d / パ:%d / 走:%d / 守:%d]',
                                                        optional($season->team)->name ?? '所属不明',
                                                        optional($season->player)->name ?? '選手不明',
                                                        $season->position_main ?? optional($season->player)->primary_position ?? '--',
                                                        $season->batting_contact ?? 0,
                                                        $season->batting_power ?? 0,
                                                        $season->running_speed ?? 0,
                                                        $season->defense ?? 0
                                                    );
                                                @endphp
                                                @php
                                                    $seasonId = $season->id;
                                                @endphp
                                                <option value="{{ $seasonId }}"
                                                    @selected(old("batters.$order.player_season_id", $existing->player_season_id ?? null) == $seasonId)>
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td style="max-width: 170px;">
                                        @php
                                            $defaultPosition = $existing->position
                                                ?? optional(optional($existing)->playerSeason)->position_main
                                                ?? '';
                                            $defaultPosition = strtoupper($defaultPosition);
                                            $selectedPosition = strtoupper(old("batters.$order.position", $defaultPosition));
                                            if (!array_key_exists($selectedPosition, $positionOptions)) {
                                                $selectedPosition = '';
                                            }
                                        @endphp
                                        <select name="batters[{{ $order }}][position]" class="form-select">
                                            @foreach ($positionOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($selectedPosition === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="text-muted">
                                        @if($existing && optional($existing->playerSeason)->team)
                                            {{ $existing->playerSeason->team->name }}
                                        @endif
                                    </td>
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>

                <h2 class="h5 mt-4 mb-2">控え野手</h2>
                <p class="text-muted small mb-3">
                    控えに表示されている選手をスタメンに入れる場合は、上の打順のプルダウンから選択するか「スタメンに入れる」ボタンを使用してください。
                </p>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>選手</th>
                                <th>所属</th>
                                <th>能力</th>
                                <th style="max-width: 140px;">打順</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rosterBatters as $bench)
                                @php
                                    $season = optional($bench->playerSeason);
                                    $teamName = optional($season->team)->name ?? '所属不明';
                                    $playerName = optional($season->player)->name ?? '選手不明';
                                    $seasonId = $season->id ?? null;
                                @endphp
                                <tr data-bench-player-id="{{ $seasonId }}" data-bench-row>
                                    <td>{{ $playerName }}</td>
                                    <td class="text-muted">{{ $teamName }}</td>
                                    <td class="text-muted small">
                                        打{{ $season->batting_contact ?? 0 }} /
                                        パ{{ $season->batting_power ?? 0 }} /
                                        走{{ $season->running_speed ?? 0 }} /
                                        守{{ $season->defense ?? 0 }}
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm" data-bench-assign="{{ $seasonId }}">
                                            <option value="">--- 指定 ---</option>
                                            @for ($order = 1; $order <= 9; $order++)
                                                <option value="{{ $order }}">打順 {{ $order }}</option>
                                            @endfor
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted text-center py-3">控え野手はいません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <h2 class="h5 mt-4 mb-3">先発投手</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <select name="pitcher[player_season_id]" class="form-select" required>
                            <option value="">--- 選択してください ---</option>
                            @foreach ($rosterPitchers as $entry)
                                @php
                                    $season = optional($entry->playerSeason);
                                    if (!$season || !$season->id) {
                                        continue;
                                    }
                                    $label = sprintf(
                                        '%s / %s [体:%d / 制:%d / 速:%d / 変:%d]',
                                        optional($season->team)->name ?? '所属不明',
                                        optional($season->player)->name ?? '選手不明',
                                        $season->pitcher_stamina ?? 0,
                                        $season->pitcher_control ?? 0,
                                        $season->pitcher_velocity ?? 0,
                                        $season->pitcher_movement ?? 0
                                    );
                                    $seasonId = $season->id;
                                    $selectedPitcherId = old('pitcher.player_season_id', optional($pitcher)->player_season_id);
                                @endphp
                                <option value="{{ $seasonId }}"
                                    @selected($selectedPitcherId == $seasonId)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <h2 class="h5 mt-4 mb-2">投手役割（先発6・中継ぎ5・抑え2）</h2>
                <p class="text-muted small mb-2">
                    各投手に役割を割り当ててください。先発投手に選択する選手は必ず「先発」に設定する必要があります。
                </p>
                <div class="alert alert-light border">
                    先発:
                    <span id="role-count-starter"
                          class="{{ ($pitcherRoleCounts['starter'] ?? 0) === 6 ? '' : 'text-danger' }}">
                        {{ $pitcherRoleCounts['starter'] ?? 0 }}
                    </span> / 6　
                    中継ぎ:
                    <span id="role-count-reliever"
                          class="{{ ($pitcherRoleCounts['reliever'] ?? 0) === 5 ? '' : 'text-danger' }}">
                        {{ $pitcherRoleCounts['reliever'] ?? 0 }}
                    </span> / 5　
                    抑え:
                    <span id="role-count-closer"
                          class="{{ ($pitcherRoleCounts['closer'] ?? 0) === 2 ? '' : 'text-danger' }}">
                        {{ $pitcherRoleCounts['closer'] ?? 0 }}
                    </span> / 2
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>選手</th>
                                <th>所属</th>
                                <th>役割</th>
                                <th>能力</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rosterPitchers as $entry)
                                @php
                                    $season = optional($entry->playerSeason);
                                    if (!$season || !$season->id) {
                                        continue;
                                    }
                                    $seasonId = $season->id;
                                    $currentRole = old("pitcher_roles.$seasonId", $entry->pitcher_role);
                                @endphp
                                <tr>
                                    <td>{{ optional($season->player)->name ?? '選手不明' }}</td>
                                    <td class="text-muted">{{ optional($season->team)->name ?? '所属不明' }}</td>
                                    <td style="max-width: 160px;">
                                        <select name="pitcher_roles[{{ $seasonId }}]" class="form-select form-select-sm"
                                                data-role-select>
                                            <option value="starter" @selected($currentRole === 'starter')>先発</option>
                                            <option value="reliever" @selected($currentRole === 'reliever')>中継ぎ</option>
                                            <option value="closer" @selected($currentRole === 'closer')>抑え</option>
                                        </select>
                                    </td>
                                    <td class="text-muted small">
                                        体{{ $season->pitcher_stamina ?? 0 }} /
                                        制{{ $season->pitcher_control ?? 0 }} /
                                        速{{ $season->pitcher_velocity ?? 0 }} /
                                        変{{ $season->pitcher_movement ?? 0 }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <button type="submit" class="btn btn-primary mt-4">編成を保存</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // 控えリストを更新する関数
        const updateBenchDisplay = () => {
            // すべての控え行を表示
            document.querySelectorAll('tr[data-bench-row]').forEach(row => {
                row.style.display = '';
            });
            
            // 打順に選択されている選手を控えリストから非表示
            document.querySelectorAll('select[data-order]').forEach(select => {
                const selectedPlayerId = select.value;
                if (selectedPlayerId) {
                    const benchRow = document.querySelector(`tr[data-bench-player-id="${selectedPlayerId}"]`);
                    if (benchRow) {
                        benchRow.style.display = 'none';
                    }
                }
            });
        };
        
        document.querySelectorAll('[data-bench-assign]').forEach(select => {
            select.addEventListener('change', () => {
                const order = parseInt(select.value, 10);
                if (!order) {
                    return;
                }
                const playerId = select.dataset.benchAssign;
                const target = document.querySelector(`select[data-order="${order}"]`);
                if (!target) {
                    alert('指定した打順の入力欄が見つかりませんでした。');
                    select.value = '';
                    return;
                }
                
                // 元々その打順にいた選手のIDを取得
                const previousPlayerId = target.value;
                
                // 控え野手を打順に移動
                target.value = playerId;
                target.classList.add('border-success');
                setTimeout(() => target.classList.remove('border-success'), 1200);
                
                // 元々その打順にいた選手が別の打順にいるかどうかを確認
                let previousPlayerInOtherOrder = false;
                if (previousPlayerId) {
                    document.querySelectorAll('select[data-order]').forEach(otherSelect => {
                        if (otherSelect !== target && otherSelect.value === previousPlayerId) {
                            previousPlayerInOtherOrder = true;
                        }
                    });
                }
                
                // 元々その打順にいた選手が別の打順にいない場合、控えリストに戻す
                if (previousPlayerId && !previousPlayerInOtherOrder) {
                    const benchRow = document.querySelector(`tr[data-bench-player-id="${previousPlayerId}"]`);
                    if (benchRow) {
                        benchRow.style.display = '';
                        const benchSelect = benchRow.querySelector('[data-bench-assign]');
                        if (benchSelect) {
                            benchSelect.value = '';
                        }
                    }
                }
                
                // 移動した控え野手の行を非表示にする
                const movedRow = select.closest('tr[data-bench-row]');
                if (movedRow) {
                    movedRow.style.display = 'none';
                }
                
                // 控えリストを更新（打順に選択されている選手を非表示にする）
                updateBenchDisplay();
                
                select.value = '';
            });
        });

        const roleLimits = { starter: 6, reliever: 5, closer: 2 };
        const roleTargets = {
            starter: document.getElementById('role-count-starter'),
            reliever: document.getElementById('role-count-reliever'),
            closer: document.getElementById('role-count-closer'),
        };

        const updateRoleCounts = () => {
            const counts = { starter: 0, reliever: 0, closer: 0 };
            document.querySelectorAll('[data-role-select]').forEach(select => {
                const value = select.value;
                if (counts[value] !== undefined) {
                    counts[value]++;
                }
            });

            Object.keys(roleTargets).forEach(role => {
                const el = roleTargets[role];
                if (!el) return;
                el.textContent = counts[role] ?? 0;
                const isValid = (counts[role] ?? 0) === roleLimits[role];
                el.classList.toggle('text-danger', !isValid);
            });
        };

        document.querySelectorAll('[data-role-select]').forEach(select => {
            select.addEventListener('change', updateRoleCounts);
        });
        updateRoleCounts();
        
        // 打順のプルダウンが変更されたときに、控えリストを更新
        document.querySelectorAll('select[data-order]').forEach(select => {
            select.addEventListener('change', function() {
                updateBenchDisplay();
            });
        });
        
        // 初期状態で打順に選択されている選手を控えリストから非表示
        updateBenchDisplay();
    });
</script>
@endpush

