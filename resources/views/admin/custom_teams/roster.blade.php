@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-4">{{ $customTeam->year }}年 {{ $customTeam->name }} ベンチ登録</h1>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
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

    <p class="text-muted">
        野手13人・投手13人まで登録できます。投手は保存時に「先発6・中継ぎ5・抑え2」に自動配分されます。チーム編成の前に、ベンチ入りメンバーをここで登録してください。
    </p>

    @php
        $oldBatters = collect(old('batters', $currentBatters))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $oldPitchers = collect(old('pitchers', $currentPitchers))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $selectedBatterCount = $oldBatters->count();
        $selectedPitcherCount = $oldPitchers->count();
        $pitcherRoles = ($pitcherRoles ?? collect())->toArray();
        $pitcherRoleLabels = [
            'starter' => '先発',
            'reliever' => '中継ぎ',
            'closer' => '抑え',
        ];
    @endphp

    <form action="{{ route('admin.custom-teams.roster.update', $customTeam) }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label fw-bold mb-0">野手</label>
                        <span class="badge bg-primary">
                            <span id="batter-count" class="{{ $selectedBatterCount > 13 ? 'text-danger' : '' }}">{{ $selectedBatterCount }}</span> / 13
                        </span>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <select class="form-select form-select-sm" data-filter="batters" data-filter-type="team" style="min-width: 160px;">
                            <option value="">全チーム</option>
                            @foreach ($batterCandidates->pluck('team.name')->unique()->sort()->filter() as $teamName)
                                <option value="{{ $teamName }}">{{ $teamName }}</option>
                            @endforeach
                        </select>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="position-filter-btn">
                                守備位置
                            </button>
                            <ul class="dropdown-menu" data-filter="batters" data-filter-type="position" style="max-height: 300px; overflow-y: auto;">
                                @php
                                    // 守備位置の定義（表示名 => DB値のマッピング）
                                    $positionMap = [
                                        '捕手' => '捕手',
                                        '一塁手' => '一塁手',
                                        '二塁手' => '二塁手',
                                        '三塁手' => '三塁手',
                                        '遊撃手' => '遊撃手',
                                        '左翼' => ['左翼', '左翼手'],
                                        '中堅' => ['中堅', '中堅手'],
                                        '右翼' => ['右翼', '右翼手'],
                                    ];
                                    
                                    // データベースに存在する守備位置を取得
                                    $dbPositions = $batterCandidates->pluck('position_1')->filter()->unique()->values()->toArray();
                                    
                                    // 表示順序でフィルタリング
                                    $availablePositions = [];
                                    foreach ($positionMap as $displayName => $dbValues) {
                                        $dbValuesArray = is_array($dbValues) ? $dbValues : [$dbValues];
                                        foreach ($dbValuesArray as $dbValue) {
                                            if (in_array($dbValue, $dbPositions)) {
                                                $availablePositions[$displayName] = $dbValue;
                                                break;
                                            }
                                        }
                                    }
                                @endphp
                                @foreach ($availablePositions as $displayName => $dbValue)
                                    <li>
                                        <label class="dropdown-item mb-0" style="cursor: pointer;">
                                            <input type="checkbox" class="form-check-input me-2" value="{{ $dbValue }}" data-position-filter data-display-name="{{ $displayName }}">
                                            {{ $displayName }}
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="border rounded overflow-auto" style="max-height: 500px;" data-roster-list="batters">
                    <ul class="list-group list-group-flush">
                        @foreach ($batterCandidates as $season)
                            @php
                                $player = $season->player;
                                $team = $season->team;
                                $seasonId = (int) $season->id;
                                $checked = $oldBatters->contains($seasonId);
                            @endphp
                            <li class="list-group-item py-2">
                                <label class="form-check d-flex align-items-start gap-2 mb-0">
                                    <input type="checkbox" class="form-check-input mt-1"
                                           name="batters[]" value="{{ $seasonId }}" @checked($checked)>
                                    @php
                                        // 守備位置の表示名に変換（左翼手→左翼、中堅手→中堅、右翼手→右翼）
                                        $positionDisplay = $season->position_1 ?? '';
                                        if ($positionDisplay === '左翼手') {
                                            $positionDisplay = '左翼';
                                        } elseif ($positionDisplay === '中堅手') {
                                            $positionDisplay = '中堅';
                                        } elseif ($positionDisplay === '右翼手') {
                                            $positionDisplay = '右翼';
                                        }
                                    @endphp
                                    <span data-team="{{ $team->name ?? '' }}" data-position="{{ $season->position_1 ?? '' }}">
                                        <strong>{{ $player->name ?? '不明選手' }}</strong>
                                        <small class="text-muted d-block">
                                            {{ $team->name ?? '所属不明' }}@if($positionDisplay) | {{ $positionDisplay }}@endif |
                                            ミート{{ $season->batting_contact ?? 0 }}・パワー{{ $season->batting_power ?? 0 }}・走力{{ $season->running_speed ?? 0 }}
                                        </small>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label fw-bold mb-0">投手</label>
                        <span class="badge bg-success">
                            <span id="pitcher-count" class="{{ $selectedPitcherCount > 13 ? 'text-danger' : '' }}">{{ $selectedPitcherCount }}</span> / 13
                        </span>
                    </div>
                    <select class="form-select form-select-sm" data-filter="pitchers" style="min-width: 160px;">
                        <option value="">全チーム</option>
                        @foreach ($pitcherCandidates->pluck('team.name')->unique()->sort()->filter() as $teamName)
                            <option value="{{ $teamName }}">{{ $teamName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="border rounded overflow-auto" style="max-height: 500px;" data-roster-list="pitchers">
                    <ul class="list-group list-group-flush">
                        @foreach ($pitcherCandidates as $season)
                            @php
                                $player = $season->player;
                                $team = $season->team;
                                $seasonId = (int) $season->id;
                                $checked = $oldPitchers->contains($seasonId);
                                $roleText = $pitcherRoleLabels[$pitcherRoles[$seasonId] ?? null] ?? null;
                            @endphp
                            <li class="list-group-item py-2">
                                <label class="form-check d-flex align-items-start gap-2 mb-0">
                                    <input type="checkbox" class="form-check-input mt-1"
                                           name="pitchers[]" value="{{ $seasonId }}" @checked($checked)>
                                    <span data-team="{{ $team->name ?? '' }}">
                                        <strong>{{ $player->name ?? '不明選手' }}</strong>
                                        <small class="text-muted d-block">
                                            {{ $team->name ?? '所属不明' }} |
                                            球速{{ $season->pitcher_velocity ?? 0 }}・制球{{ $season->pitcher_control ?? 0 }}・スタミナ{{ $season->pitcher_stamina ?? 0 }}
                                            @if($roleText)
                                                | 現在: {{ $roleText }}
                                            @endif
                                        </small>
                                    </span>
                                </label>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <a href="{{ route('admin.custom-teams.index') }}" class="btn btn-outline-secondary">一覧に戻る</a>
            <div>
                <button type="submit" class="btn btn-primary">ベンチ登録を保存</button>
                <a href="{{ route('admin.custom-teams.lineup.edit', $customTeam) }}" class="btn btn-link">チーム編成へ</a>
            </div>
        </div>
    </form>
</div>
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const limits = { batters: 13, pitchers: 13 };
        const counters = {
            batters: [document.getElementById('batter-count')],
            pitchers: [document.getElementById('pitcher-count')],
        };

        ['batters', 'pitchers'].forEach(type => {
            const container = document.querySelector(`[data-roster-list="${type}"]`);
            if (!container) return;

            const checkboxes = container.querySelectorAll('input[type="checkbox"]');
            const max = limits[type];

            const applyFilters = () => {
                const teamFilter = document.querySelector(`[data-filter="${type}"][data-filter-type="team"]`);
                const positionCheckboxes = document.querySelectorAll(`[data-filter="${type}"][data-filter-type="position"] [data-position-filter]`);
                
                const teamValue = (teamFilter?.value || '').trim();
                const selectedPositions = Array.from(positionCheckboxes)
                    .filter(cb => cb.checked)
                    .map(cb => cb.value.trim())
                    .filter(v => v);

                container.querySelectorAll('li').forEach(item => {
                    const span = item.querySelector('[data-team]');
                    if (!span) return;
                    
                    const teamName = (span.dataset.team || '').trim();
                    const position = (span.dataset.position || '').trim();
                    
                    const teamMatch = !teamValue || teamName === teamValue;
                    const positionMatch = selectedPositions.length === 0 || selectedPositions.includes(position);
                    
                    item.classList.toggle('d-none', !(teamMatch && positionMatch));
                });

                // 守備位置フィルターボタンのテキストを更新
                if (type === 'batters') {
                    const positionBtn = document.getElementById('position-filter-btn');
                    if (positionBtn) {
                        if (selectedPositions.length === 0) {
                            positionBtn.textContent = '守備位置';
                        } else if (selectedPositions.length === 1) {
                            // 表示名を取得
                            const checkbox = document.querySelector(`[data-position-filter][value="${selectedPositions[0]}"]`);
                            const displayName = checkbox ? checkbox.dataset.displayName : selectedPositions[0];
                            // 表示名に変換（左翼手→左翼、中堅手→中堅、右翼手→右翼）
                            let displayText = displayName || selectedPositions[0];
                            if (displayText === '左翼手') displayText = '左翼';
                            else if (displayText === '中堅手') displayText = '中堅';
                            else if (displayText === '右翼手') displayText = '右翼';
                            positionBtn.textContent = displayText;
                        } else {
                            positionBtn.textContent = `${selectedPositions.length}件選択`;
                        }
                    }
                }
            };

            const update = () => {
                const visibleCheckboxes = Array.from(container.querySelectorAll('li:not(.d-none) input[type="checkbox"]'));
                const selected = visibleCheckboxes.filter(cb => cb.checked).length;
                (counters[type] || []).forEach(counter => {
                    if (!counter) return;
                    counter.textContent = selected;
                    counter.classList.toggle('text-danger', selected > max);
                });
            };

            checkboxes.forEach(cb => cb.addEventListener('change', update));

            // チームフィルター
            const teamFilter = document.querySelector(`[data-filter="${type}"][data-filter-type="team"]`);
            if (teamFilter) {
                teamFilter.addEventListener('change', () => {
                    applyFilters();
                    update();
                });
            }

            // 守備位置フィルター（チェックボックス）
            const positionCheckboxes = document.querySelectorAll(`[data-filter="${type}"][data-filter-type="position"] [data-position-filter]`);
            const positionLabels = document.querySelectorAll(`[data-filter="${type}"][data-filter-type="position"] label.dropdown-item`);
            
            positionCheckboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    applyFilters();
                    update();
                });
                // チェックボックスをクリックしたときにドロップダウンが閉じないようにする
                cb.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            });

            // ラベルをクリックしたときもドロップダウンが閉じないようにする
            positionLabels.forEach(label => {
                label.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            });

            // ドロップダウンメニュー内のクリックで閉じないようにする
            if (type === 'batters') {
                const positionDropdown = document.querySelector(`[data-filter="${type}"][data-filter-type="position"]`);
                if (positionDropdown) {
                    positionDropdown.addEventListener('click', (e) => {
                        e.stopPropagation();
                    });
                }
            }

            applyFilters();
            update();
        });
    });
</script>
@endpush
@endsection

