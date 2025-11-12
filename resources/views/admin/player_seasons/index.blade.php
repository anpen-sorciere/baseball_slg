@extends('layouts.app')

@section('title', 'Player Seasons 一覧')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Player Seasons 一覧</h1>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="year" class="form-label">年度</label>
                    <select name="year" id="year" class="form-select">
                        <option value="">すべて</option>
                        @foreach($years as $year)
                            <option value="{{ $year }}" @selected($filters['year'] == $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="team_id" class="form-label">チーム</label>
                    <select name="team_id" id="team_id" class="form-select">
                        <option value="">すべて</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" @selected($filters['team_id'] == $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="keyword" class="form-label">選手名（部分一致）</label>
                    <input type="text" name="keyword" id="keyword" class="form-control"
                           value="{{ $filters['keyword'] }}" placeholder="例: 大谷">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">絞り込み</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>年度</th>
                            <th>チーム</th>
                            <th>選手名</th>
                            <th>ポジション</th>
                            <th>役割</th>
                            <th>二刀流</th>
                            <th>総合</th>
                            <th>打撃(ミート)</th>
                            <th>打撃(パワー)</th>
                            <th>選球眼</th>
                            <th>走力</th>
                            <th>守備</th>
                            <th>スタミナ</th>
                            <th>制球</th>
                            <th>球速</th>
                            <th>変化</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($playerSeasons as $season)
                            <tr>
                                <td>{{ $season->year }}</td>
                                <td>{{ optional($season->team)->name ?? '---' }}</td>
                                <td>{{ optional($season->player)->name ?? '---' }}</td>
                                <td>{{ $season->position_main ?? '---' }}</td>
                                <td>{{ $season->role ?? '---' }}</td>
                                <td>{{ $season->is_two_way ? 'はい' : 'いいえ' }}</td>
                                <td>{{ $season->overall_rating ?? '-' }}</td>
                                <td>{{ $season->batting_contact ?? '-' }}</td>
                                <td>{{ $season->batting_power ?? '-' }}</td>
                                <td>{{ $season->batting_eye ?? '-' }}</td>
                                <td>{{ $season->running_speed ?? '-' }}</td>
                                <td>{{ $season->defense ?? '-' }}</td>
                                <td>{{ $season->pitcher_stamina ?? '-' }}</td>
                                <td>{{ $season->pitcher_control ?? '-' }}</td>
                                <td>{{ $season->pitcher_velocity ?? '-' }}</td>
                                <td>{{ $season->pitcher_movement ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="16" class="text-center py-4">該当するデータがありません。</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card-footer">
            {{ $playerSeasons->links() }}
        </div>
    </div>
@endsection


