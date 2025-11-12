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
                            $teamAName = $result['team_names']['teamA'] ?? ($game->customTeam->name ?? '不明');
                            
                            // 後攻チーム（teamB）の名前を取得
                            // 1. result_jsonのteam_namesから取得
                            // 2. opponent_custom_team_idから取得（他のアカウントのオリジナルチームの場合）
                            // 3. teamBリレーションから取得（NPBチームの場合）
                            // 4. それでも取得できない場合は「不明」
                            $teamBName = $result['team_names']['teamB'] ?? '不明';
                            
                            // 対戦相手が他のアカウントのオリジナルチームの場合
                            if ($teamBName === '不明' && isset($result['opponent_custom_team_id'])) {
                                $opponentCustomTeam = \App\Models\CustomTeam::find($result['opponent_custom_team_id']);
                                if ($opponentCustomTeam) {
                                    $teamBName = $opponentCustomTeam->name;
                                }
                            }
                            
                            // NPBチームの場合
                            if ($teamBName === '不明' && $game->teamB) {
                                $teamBName = $game->teamB->name;
                            }
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

