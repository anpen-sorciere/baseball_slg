@extends('layouts.app')

@section('title', $team->name . ' - チーム詳細')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">{{ $team->name }}</h1>
        <div>
            <a href="{{ route('teams.index') }}" class="btn btn-outline-secondary btn-sm">チーム一覧へ戻る</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <span class="text-muted small d-block">略称</span>
                    <span class="fs-5">{{ $team->short_name }}</span>
                </div>
                <div class="col-md-4">
                    <span class="text-muted small d-block">リーグ</span>
                    <span class="fs-5">{{ $team->league }}</span>
                </div>
                <div class="col-md-4">
                    <span class="text-muted small d-block">創設年</span>
                    <span class="fs-5">{{ $team->founded_year }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">野手（player_seasons）</div>
        <div class="card-body p-0">
            @if($batters->isEmpty())
                <p class="text-muted text-center py-4 mb-0">登録された野手データがありません。</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>年度</th>
                                <th>選手名</th>
                                <th>背番号</th>
                                <th>守備位置</th>
                                <th>総合</th>
                                <th>ミート</th>
                                <th>パワー</th>
                                <th>選球眼</th>
                                <th>走力</th>
                                <th>守備</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($batters as $season)
                                <tr>
                                    <td>{{ $season->year }}</td>
                                    <td>{{ $season->player->name }}</td>
                                    <td>{{ $season->uniform_number }}</td>
                                    <td>{{ $season->position_main }}</td>
                                    <td>{{ $season->overall_rating }}</td>
                                    <td>{{ $season->batting_contact }}</td>
                                    <td>{{ $season->batting_power }}</td>
                                    <td>{{ $season->batting_eye }}</td>
                                    <td>{{ $season->running_speed }}</td>
                                    <td>{{ $season->defense }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header">投手（player_seasons）</div>
        <div class="card-body p-0">
            @if($pitchers->isEmpty())
                <p class="text-muted text-center py-4 mb-0">登録された投手データがありません。</p>
            @else
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>年度</th>
                                <th>選手名</th>
                                <th>背番号</th>
                                <th>役割</th>
                                <th>総合</th>
                                <th>スタミナ</th>
                                <th>コントロール</th>
                                <th>球速</th>
                                <th>変化</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pitchers as $season)
                                <tr>
                                    <td>{{ $season->year }}</td>
                                    <td>{{ $season->player->name }}</td>
                                    <td>{{ $season->uniform_number }}</td>
                                    <td>{{ $season->role }}</td>
                                    <td>{{ $season->overall_rating }}</td>
                                    <td>{{ $season->pitcher_stamina }}</td>
                                    <td>{{ $season->pitcher_control }}</td>
                                    <td>{{ $season->pitcher_velocity }}</td>
                                    <td>{{ $season->pitcher_movement }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
