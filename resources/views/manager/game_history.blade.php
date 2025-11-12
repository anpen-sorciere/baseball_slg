@extends('layouts.app')

@section('title', 'マネージャーモード：試合一覧')

@section('content')
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">マネージャーモード：試合一覧</h1>
        <a href="{{ route('manager.game.index') }}" class="btn btn-outline-secondary">試合設定に戻る</a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>日時</th>
                        <th>年度</th>
                        <th>カード</th>
                        <th>スコア</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($games as $game)
                        @php
                            $result = $game->result_json ?? [];
                            // 先攻チーム（teamA）は常にオリジナルチーム
                            $teamAName = $result['lineups']['teamA']['team_name'] ?? ($game->customTeam->name ?? '不明');
                            
                            // 後攻チーム（teamB）の名前を取得
                            // 1. result_jsonから取得を試みる
                            // 2. teamBリレーションから取得（NPBチームの場合）
                            // 3. それでも取得できない場合は「不明」
                            $teamBName = $result['lineups']['teamB']['team_name'] ?? (optional($game->teamB)->name ?? '不明');
                        @endphp
                        <tr>
                            <td>{{ $game->created_at->format('Y-m-d H:i') }}</td>
                            <td>{{ $game->year }}</td>
                            <td>{{ $teamAName }} vs {{ $teamBName }}</td>
                            <td>{{ $game->score_a }} - {{ $game->score_b }}</td>
                            <td>
                                <a href="{{ route('games.show', $game) }}" class="btn btn-sm btn-outline-primary">
                                    詳細
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">試合結果はまだありません。</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

