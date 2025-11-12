@extends('layouts.app')

@section('title', "試合詳細 #{$game->id}")

@section('content')
    @php
        $result = $game->result_json ?? [];
        $innings = $result['innings'] ?? [];
        $lineups = $result['lineups'] ?? ['teamA' => ['batters' => [], 'pitcher' => null], 'teamB' => ['batters' => [], 'pitcher' => null]];
        $battingStats = $result['batting_stats'] ?? ['teamA' => [], 'teamB' => []];
        $pitchingStats = $result['pitching_stats'] ?? ['teamA' => [], 'teamB' => []];
        $score = $result['score'] ?? ['teamA' => $game->score_a ?? 0, 'teamB' => $game->score_b ?? 0];
        
        // チーム名を取得
        $teamAName = $result['lineups']['teamA']['team_name'] ?? (optional($game->customTeam)->name ?? optional($game->teamA)->name ?? '先攻');
        $teamBName = $result['lineups']['teamB']['team_name'] ?? (optional($game->teamB)->name ?? '後攻');
        
        // 試合経過をグループ化
        $playByPlay = isset($result['play_by_play']) && is_array($result['play_by_play'])
            ? collect($result['play_by_play'])->groupBy(function ($event) {
                return ($event['inning'] ?? 0) . '_' . ($event['half'] ?? 'top');
            })
            : collect();
    @endphp

    @php
        $winner = $score['teamA'] > $score['teamB'] ? 'A' : ($score['teamA'] < $score['teamB'] ? 'B' : 'Tie');
        $winnerName = $winner === 'A' ? $teamAName : ($winner === 'B' ? $teamBName : '引き分け');
    @endphp

    <div class="game-header mb-5">
        <div class="game-header-bg"></div>
        <div class="container-fluid px-0">
            <div class="row g-0">
                <div class="col-12">
                    <div class="game-header-content">
                        <div class="game-year">{{ $game->year }}年</div>
                        <div class="game-matchup">
                            <div class="team-score team-a {{ $winner === 'A' ? 'winner' : '' }}">
                                <div class="team-name">{{ $teamAName }}</div>
                                <div class="team-score-value">{{ $score['teamA'] }}</div>
                            </div>
                            <div class="vs-divider">VS</div>
                            <div class="team-score team-b {{ $winner === 'B' ? 'winner' : '' }}">
                                <div class="team-name">{{ $teamBName }}</div>
                                <div class="team-score-value">{{ $score['teamB'] }}</div>
                            </div>
                        </div>
                        @if($winner !== 'Tie')
                            <div class="game-winner">
                                <span class="winner-badge">🏆 勝利</span>
                                <span class="winner-name">{{ $winnerName }}</span>
                            </div>
                        @else
                            <div class="game-winner">
                                <span class="tie-badge">引き分け</span>
                            </div>
                        @endif
                        <div class="game-id">
                            試合ID: {{ $game->id }} | {{ $game->created_at->format('Y-m-d H:i') }}
                        </div>
                        <div class="mt-3">
                            @if(isset($customMatch) && $customMatch)
                                <a href="{{ route('manager.games.index') }}" class="btn btn-outline-light btn-sm">一覧に戻る</a>
                            @else
                                <a href="{{ route('games.index') }}" class="btn btn-outline-light btn-sm">一覧に戻る</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body table-responsive">
            <h2 class="h5 mb-3">イニング別スコア</h2>
            <table class="table table-bordered text-center align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 120px;"></th>
                        @foreach ($innings as $inning)
                            <th>{{ $inning['inning'] }}</th>
                        @endforeach
                        <th>R</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th class="text-start">先攻（{{ $teamAName }}）</th>
                        @foreach ($innings as $inning)
                            <td>{{ $inning['teamA'] ?? 0 }}</td>
                        @endforeach
                        <td class="fw-bold">{{ $score['teamA'] }}</td>
                    </tr>
                    <tr>
                        <th class="text-start">後攻（{{ $teamBName }}）</th>
                        @foreach ($innings as $inning)
                            <td>{{ is_null($inning['teamB'] ?? null) ? '–' : ($inning['teamB'] ?? 0) }}</td>
                        @endforeach
                        <td class="fw-bold">{{ $score['teamB'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">先攻（{{ $teamAName }}）スタメン</div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>打順</th>
                                <th>選手</th>
                                <th>守備</th>
                                <th>打</th>
                                <th>パ</th>
                                <th>走</th>
                                <th>守</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lineups['teamA']['batters'] ?? [] as $batter)
                                <tr>
                                    <td>{{ $batter['order'] ?? '' }}</td>
                                    <td>{{ $batter['name'] ?? '' }}</td>
                                    <td>{{ $batter['position'] ?? '' }}</td>
                                    <td>{{ $batter['contact'] ?? '' }}</td>
                                    <td>{{ $batter['power'] ?? '' }}</td>
                                    <td>{{ $batter['speed'] ?? '' }}</td>
                                    <td>{{ $batter['defense'] ?? '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">スタメン情報がありません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(!empty($lineups['teamA']['pitcher']))
                    <div class="card-footer">
                        <strong>先発投手：</strong>
                        {{ $lineups['teamA']['pitcher']['name'] ?? '' }}
                        （体 {{ $lineups['teamA']['pitcher']['stamina'] ?? '' }},
                        制 {{ $lineups['teamA']['pitcher']['control'] ?? '' }},
                        速 {{ $lineups['teamA']['pitcher']['velocity'] ?? '' }},
                        変 {{ $lineups['teamA']['pitcher']['movement'] ?? '' }}）
                    </div>
                @endif
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">後攻（{{ $teamBName }}）スタメン</div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>打順</th>
                                <th>選手</th>
                                <th>守備</th>
                                <th>打</th>
                                <th>パ</th>
                                <th>走</th>
                                <th>守</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lineups['teamB']['batters'] ?? [] as $batter)
                                <tr>
                                    <td>{{ $batter['order'] ?? '' }}</td>
                                    <td>{{ $batter['name'] ?? '' }}</td>
                                    <td>{{ $batter['position'] ?? '' }}</td>
                                    <td>{{ $batter['contact'] ?? '' }}</td>
                                    <td>{{ $batter['power'] ?? '' }}</td>
                                    <td>{{ $batter['speed'] ?? '' }}</td>
                                    <td>{{ $batter['defense'] ?? '' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">スタメン情報がありません。</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(!empty($lineups['teamB']['pitcher']))
                    <div class="card-footer">
                        <strong>先発投手：</strong>
                        {{ $lineups['teamB']['pitcher']['name'] ?? '' }}
                        （体 {{ $lineups['teamB']['pitcher']['stamina'] ?? '' }},
                        制 {{ $lineups['teamB']['pitcher']['control'] ?? '' }},
                        速 {{ $lineups['teamB']['pitcher']['velocity'] ?? '' }},
                        変 {{ $lineups['teamB']['pitcher']['movement'] ?? '' }}）
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">先攻（{{ $teamAName }}）打撃成績</div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>打順</th>
                                <th>選手</th>
                                <th>打数</th>
                                <th>安打</th>
                                <th>本塁打</th>
                                <th>打点</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($battingStats['teamA'] ?? [] as $stat)
                                <tr>
                                    <td>{{ $stat['order'] ?? '' }}</td>
                                    <td>{{ $stat['name'] ?? '' }}</td>
                                    <td>{{ $stat['ab'] ?? 0 }}</td>
                                    <td>{{ $stat['h'] ?? 0 }}</td>
                                    <td>{{ $stat['hr'] ?? 0 }}</td>
                                    <td>{{ $stat['rbi'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-3">データなし</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">後攻（{{ $teamBName }}）打撃成績</div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>打順</th>
                                <th>選手</th>
                                <th>打数</th>
                                <th>安打</th>
                                <th>本塁打</th>
                                <th>打点</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($battingStats['teamB'] ?? [] as $stat)
                                <tr>
                                    <td>{{ $stat['order'] ?? '' }}</td>
                                    <td>{{ $stat['name'] ?? '' }}</td>
                                    <td>{{ $stat['ab'] ?? 0 }}</td>
                                    <td>{{ $stat['h'] ?? 0 }}</td>
                                    <td>{{ $stat['hr'] ?? 0 }}</td>
                                    <td>{{ $stat['rbi'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-3">データなし</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">先攻（{{ $teamAName }}）投手成績</div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>投手</th>
                                <th>投球回</th>
                                <th>失点</th>
                                <th>奪三振</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pitchingStats['teamA'] ?? [] as $stat)
                                <tr>
                                    <td>{{ $stat['name'] ?? '' }}</td>
                                    <td>{{ $stat['ip'] ?? '0' }}</td>
                                    <td>{{ $stat['er'] ?? 0 }}</td>
                                    <td>{{ $stat['so'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3">データなし</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">後攻（{{ $teamBName }}）投手成績</div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>投手</th>
                                <th>投球回</th>
                                <th>失点</th>
                                <th>奪三振</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pitchingStats['teamB'] ?? [] as $stat)
                                <tr>
                                    <td>{{ $stat['name'] ?? '' }}</td>
                                    <td>{{ $stat['ip'] ?? '0' }}</td>
                                    <td>{{ $stat['er'] ?? 0 }}</td>
                                    <td>{{ $stat['so'] ?? 0 }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3">データなし</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(!empty($result['mvp']))
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h5 mb-2">本日のMVP</h2>
                <p class="mb-1">
                    <strong>{{ $result['mvp']['name'] ?? '該当なし' }}</strong>
                    （{{ ($result['mvp']['team'] ?? '') === 'A' ? '先攻' : (($result['mvp']['team'] ?? '') === 'B' ? '後攻' : '-') }}）
                </p>
                @if(!empty($result['mvp']['reason']))
                    <p class="text-muted mb-0">{{ $result['mvp']['reason'] }}</p>
                @endif
            </div>
        </div>
    @endif

    @if($playByPlay->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header">試合経過</div>
            <div class="card-body">
                @foreach ($playByPlay as $key => $events)
                    @php
                        [$inningKey, $halfKey] = explode('_', $key);
                        $inningLabel = $inningKey . '回' . ($halfKey === 'top' ? '表' : '裏');
                        $teamLabel = $halfKey === 'top' ? $teamAName : $teamBName;
                    @endphp
                    <h3 class="h6 mt-3">{{ $inningLabel }}（{{ $teamLabel }}）</h3>
                    <ul class="ps-3">
                        @foreach ($events as $event)
                            <li>{{ $event['description'] ?? '' }}</li>
                        @endforeach
                    </ul>
                @endforeach
            </div>
        </div>
    @endif

    @if(!empty($result['log']))
        <div class="card mb-4">
            <div class="card-header">試合ログ</div>
            <div class="card-body">
                @foreach($result['log'] as $line)
                    <div>{{ $line }}</div>
                @endforeach
            </div>
        </div>
    @endif

    @if(isset($customMatch) && $customMatch)
        <a href="{{ route('manager.game.index') }}" class="btn btn-outline-secondary">試合設定に戻る</a>
        <a href="{{ route('manager.games.index') }}" class="btn btn-outline-primary ms-2">試合一覧を見る</a>
    @else
        <a href="{{ route('game.index') }}" class="btn btn-outline-secondary">試合設定に戻る</a>
        <a href="{{ route('games.index') }}" class="btn btn-outline-primary ms-2">試合一覧を見る</a>
    @endif
@endsection

@push('styles')
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;500;700;900&display=swap" rel="stylesheet">
<style>
/* タイポグラフィ改善 */
body {
    font-family: 'Noto Sans JP', 'Hiragino Kaku Gothic ProN', 'Hiragino Sans', Meiryo, sans-serif;
}

h1, h2, h3, h4, h5, h6 {
    font-weight: 700;
    letter-spacing: -0.02em;
}

/* 試合結果ヘッダー */
.game-header {
    position: relative;
    overflow: hidden;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}

.game-header-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #7e8ba3 100%);
    opacity: 0.95;
    z-index: 0;
}

.game-header-bg::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
    animation: bgShift 10s ease-in-out infinite;
}

@keyframes bgShift {
    0%, 100% {
        transform: translate(0, 0);
    }
    50% {
        transform: translate(-10px, -10px);
    }
}

.game-header-content {
    position: relative;
    z-index: 1;
    padding: 3rem 2rem;
    color: white;
    text-align: center;
}

.game-year {
    font-size: 1rem;
    font-weight: 500;
    opacity: 0.9;
    margin-bottom: 1rem;
    letter-spacing: 0.1em;
}

.game-matchup {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 2rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.team-score {
    flex: 1;
    min-width: 200px;
    padding: 1.5rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.team-score:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.15);
}

.team-score.winner {
    background: rgba(255, 215, 0, 0.2);
    border-color: rgba(255, 215, 0, 0.5);
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
    animation: winnerGlow 2s ease-in-out infinite;
}

@keyframes winnerGlow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
    }
    50% {
        box-shadow: 0 0 30px rgba(255, 215, 0, 0.6);
    }
}

.team-name {
    font-size: 1.3rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.team-score-value {
    font-size: 4rem;
    font-weight: 900;
    line-height: 1;
    text-shadow: 3px 3px 6px rgba(0,0,0,0.4);
    font-family: 'Noto Sans JP', sans-serif;
}

.vs-divider {
    font-size: 1.5rem;
    font-weight: 700;
    opacity: 0.8;
    padding: 0 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.game-winner {
    margin-top: 1.5rem;
    animation: fadeInDown 0.8s ease-out 0.5s both;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.winner-badge {
    display: inline-block;
    background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
    color: #1e3c72;
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-weight: 700;
    font-size: 1.1rem;
    margin-right: 1rem;
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.4);
    animation: badgePulse 2s ease-in-out infinite;
}

@keyframes badgePulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.winner-name {
    font-size: 1.5rem;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.tie-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.5rem 1.5rem;
    border-radius: 25px;
    font-weight: 700;
    font-size: 1.1rem;
}

.game-id {
    margin-top: 1rem;
    font-size: 0.9rem;
    opacity: 0.8;
}

/* インタラクティブ要素 */
.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn {
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
}

@media (max-width: 768px) {
    .game-header-content {
        padding: 2rem 1rem;
    }
    
    .game-matchup {
        flex-direction: column;
        gap: 1rem;
    }
    
    .team-score {
        width: 100%;
        min-width: auto;
    }
    
    .team-score-value {
        font-size: 3rem;
    }
    
    .vs-divider {
        padding: 0.5rem 0;
    }
}
</style>
@endpush
