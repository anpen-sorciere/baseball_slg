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
                    <select class="form-select form-select-sm" data-filter="batters" style="min-width: 160px;">
                        <option value="">全チーム</option>
                        @foreach ($batterCandidates->pluck('team.name')->unique()->sort()->filter() as $teamName)
                            <option value="{{ $teamName }}">{{ $teamName }}</option>
                        @endforeach
                    </select>
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
                                    <span data-team="{{ $team->name ?? '' }}">
                                        <strong>{{ $player->name ?? '不明選手' }}</strong>
                                        <small class="text-muted d-block">
                                            {{ $team->name ?? '所属不明' }} |
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

            const applyFilter = (value) => {
                const normalized = value?.trim() || '';
                container.querySelectorAll('li').forEach(item => {
                    const teamLabel = item.querySelector('[data-team]');
                    if (!teamLabel) return;
                    const teamName = (teamLabel.dataset.team || '').trim();
                    const match = !normalized || teamName === normalized;
                    item.classList.toggle('d-none', !match);
                });
            };

            const update = () => {
                const selected = Array.from(checkboxes).filter(cb => cb.checked).length;
                (counters[type] || []).forEach(counter => {
                    if (!counter) return;
                    counter.textContent = selected;
                    counter.classList.toggle('text-danger', selected > max);
                });
            };

            checkboxes.forEach(cb => cb.addEventListener('change', update));

            const filterSelect = document.querySelector(`[data-filter="${type}"]`);
            if (filterSelect) {
                filterSelect.addEventListener('change', () => {
                    applyFilter(filterSelect.value);
                    update();
                });
            }

            applyFilter(filterSelect ? filterSelect.value : '');
            update();
        });
    });
</script>
@endpush
@endsection

