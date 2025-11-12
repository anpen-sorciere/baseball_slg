@extends('layouts.app')

@section('title', "試合結果 - {$year}年")

@section('content')
    @php
        $innings = $result['innings'] ?? [];
        $lineups = $result['lineups'] ?? ['teamA' => ['batters' => [], 'pitcher' => null], 'teamB' => ['batters' => [], 'pitcher' => null]];
        $battingStats = $result['batting_stats'] ?? ['teamA' => [], 'teamB' => []];
        $pitchingStats = $result['pitching_stats'] ?? ['teamA' => [], 'teamB' => []];
        $score = $result['score'] ?? ['teamA' => 0, 'teamB' => 0];
        
        // チーム名の取得（null安全）
        $teamAName = '先攻';
        if (isset($teamA)) {
            if (is_object($teamA) && isset($teamA->name)) {
                $teamAName = $teamA->name;
            } elseif (is_array($teamA) && isset($teamA['name'])) {
                $teamAName = $teamA['name'];
            }
        }
        
        $teamBName = '後攻';
        if (isset($teamB)) {
            if (is_object($teamB) && isset($teamB->name)) {
                $teamBName = $teamB->name;
            } elseif (is_array($teamB) && isset($teamB['name'])) {
                $teamBName = $teamB['name'];
            }
        }
        
        $playByPlay = isset($playByPlay)
            ? ($playByPlay instanceof \Illuminate\Support\Collection ? $playByPlay : collect($playByPlay))
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
                        <div class="game-year">{{ $year }}年</div>
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
                        @isset($gameId)
                            <div class="game-id">
                                試合ID: <a href="{{ route('games.show', $gameId) }}">{{ $gameId }}</a>
                            </div>
                        @endisset
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 scoreboard-card">
        <div class="card-body table-responsive">
            <h2 class="h5 mb-3">イニング別スコア</h2>
            <table class="table table-bordered text-center align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 120px;"></th>
                        @foreach ($innings as $inning)
                            <th class="inning-header" style="animation-delay: {{ $loop->index * 0.1 }}s;">{{ $inning['inning'] }}</th>
                        @endforeach
                        <th class="inning-header" style="animation-delay: {{ count($innings) * 0.1 }}s;">R</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th class="text-start">先攻（{{ $teamAName }}）</th>
                        @foreach ($innings as $inning)
                            @php
                                $teamAScore = isset($inning['teamA']) ? (int)$inning['teamA'] : 0;
                            @endphp
                            <td class="score-cell" data-score="{{ $teamAScore }}" style="animation-delay: {{ $loop->index * 0.1 }}s;">{{ $teamAScore }}</td>
                        @endforeach
                        <td class="fw-bold total-score" style="animation-delay: {{ count($innings) * 0.1 }}s;">{{ $score['teamA'] }}</td>
                    </tr>
                    <tr>
                        <th class="text-start">後攻（{{ $teamBName }}）</th>
                        @foreach ($innings as $inning)
                            @php
                                $teamBScore = isset($inning['teamB']) ? (int)$inning['teamB'] : null;
                                $displayScore = is_null($teamBScore) ? '–' : $teamBScore;
                            @endphp
                            <td class="score-cell" data-score="{{ is_null($teamBScore) ? '–' : $teamBScore }}" style="animation-delay: {{ ($loop->index + count($innings)) * 0.1 }}s;">{{ $displayScore }}</td>
                        @endforeach
                        <td class="fw-bold total-score" style="animation-delay: {{ (count($innings) * 2) * 0.1 }}s;">{{ $score['teamB'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">先攻（{{ $teamAName }}）スタメン</div>
                <div class="card-body">
                    <div class="row g-3">
                        @forelse($lineups['teamA']['batters'] ?? [] as $index => $batter)
                            <div class="col-12">
                                <div class="player-card" style="animation-delay: {{ $index * 0.05 }}s;">
                                    <div class="player-card-header">
                                        <div class="player-order">{{ $batter['order'] ?? '-' }}</div>
                                        <div class="player-name">{{ $batter['name'] ?? '不明' }}</div>
                                        <div class="player-position">{{ $batter['position'] ?? '--' }}</div>
                                    </div>
                                    <div class="player-stats">
                                        <div class="stat-item">
                                            <div class="stat-label">打</div>
                                            <div class="stat-value">{{ $batter['contact'] ?? 0 }}</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">パ</div>
                                            <div class="stat-value">{{ $batter['power'] ?? 0 }}</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">走</div>
                                            <div class="stat-value">{{ $batter['speed'] ?? 0 }}</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">守</div>
                                            <div class="stat-value">{{ $batter['defense'] ?? 0 }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-4">スタメン情報がありません。</div>
                        @endforelse
                    </div>
                    @if(!empty($lineups['teamA']['pitcher']) && is_array($lineups['teamA']['pitcher']))
                        <div class="mt-3">
                            <div class="player-card">
                                <div class="player-card-header">
                                    <div class="player-order">P</div>
                                    <div class="player-name">{{ $lineups['teamA']['pitcher']['name'] ?? '不明' }}</div>
                                    <div class="player-position">投手</div>
                                </div>
                                <div class="player-stats">
                                    <div class="stat-item">
                                        <div class="stat-label">体</div>
                                        <div class="stat-value">{{ $lineups['teamA']['pitcher']['stamina'] ?? 0 }}</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-label">制</div>
                                        <div class="stat-value">{{ $lineups['teamA']['pitcher']['control'] ?? 0 }}</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-label">速</div>
                                        <div class="stat-value">{{ $lineups['teamA']['pitcher']['velocity'] ?? 0 }}</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-label">変</div>
                                        <div class="stat-value">{{ $lineups['teamA']['pitcher']['movement'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">後攻（{{ $teamBName }}）スタメン</div>
                <div class="card-body">
                    <div class="row g-3">
                        @forelse($lineups['teamB']['batters'] ?? [] as $index => $batter)
                            <div class="col-12">
                                <div class="player-card" style="animation-delay: {{ $index * 0.05 }}s;">
                                    <div class="player-card-header">
                                        <div class="player-order">{{ $batter['order'] ?? '-' }}</div>
                                        <div class="player-name">{{ $batter['name'] ?? '不明' }}</div>
                                        <div class="player-position">{{ $batter['position'] ?? '--' }}</div>
                                    </div>
                                    <div class="player-stats">
                                        <div class="stat-item">
                                            <div class="stat-label">打</div>
                                            <div class="stat-value">{{ $batter['contact'] ?? 0 }}</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">パ</div>
                                            <div class="stat-value">{{ $batter['power'] ?? 0 }}</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">走</div>
                                            <div class="stat-value">{{ $batter['speed'] ?? 0 }}</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-label">守</div>
                                            <div class="stat-value">{{ $batter['defense'] ?? 0 }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-4">スタメン情報がありません。</div>
                        @endforelse
                    </div>
                    @if(!empty($lineups['teamB']['pitcher']) && is_array($lineups['teamB']['pitcher']))
                        <div class="mt-3">
                            <div class="player-card">
                                <div class="player-card-header">
                                    <div class="player-order">P</div>
                                    <div class="player-name">{{ $lineups['teamB']['pitcher']['name'] ?? '不明' }}</div>
                                    <div class="player-position">投手</div>
                                </div>
                                <div class="player-stats">
                                    <div class="stat-item">
                                        <div class="stat-label">体</div>
                                        <div class="stat-value">{{ $lineups['teamB']['pitcher']['stamina'] ?? 0 }}</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-label">制</div>
                                        <div class="stat-value">{{ $lineups['teamB']['pitcher']['control'] ?? 0 }}</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-label">速</div>
                                        <div class="stat-value">{{ $lineups['teamB']['pitcher']['velocity'] ?? 0 }}</div>
                                    </div>
                                    <div class="stat-item">
                                        <div class="stat-label">変</div>
                                        <div class="stat-value">{{ $lineups['teamB']['pitcher']['movement'] ?? 0 }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
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
                                    <td>{{ $stat['order'] ?? '-' }}</td>
                                    <td>{{ $stat['name'] ?? '不明' }}</td>
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
                                    <td>{{ $stat['order'] ?? '-' }}</td>
                                    <td>{{ $stat['name'] ?? '不明' }}</td>
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
                                    <td>{{ $stat['name'] ?? '不明' }}</td>
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
                                    <td>{{ $stat['name'] ?? '不明' }}</td>
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
        <div class="card mb-4 mvp-card" id="mvpCard">
            <div class="card-body text-center">
                <div class="mvp-header">
                    <h2 class="h4 mb-3 mvp-title">🏆 本日のMVP 🏆</h2>
                </div>
                <div class="mvp-content">
                    <div class="mvp-name-display">
                        <strong class="mvp-name">{{ $result['mvp']['name'] ?? '該当なし' }}</strong>
                        <span class="mvp-team">（{{ $result['mvp']['team'] === 'A' ? '先攻' : ($result['mvp']['team'] === 'B' ? '後攻' : '-') }}）</span>
                    </div>
                    @if(!empty($result['mvp']['reason']))
                        <p class="text-muted mt-3 mb-0 mvp-reason">{{ $result['mvp']['reason'] }}</p>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if(isset($playByPlay) && $playByPlay->isNotEmpty())
        <div class="card mb-4 game-progress-card">
            <div class="card-header">試合経過</div>
            <div class="card-body">
                @foreach ($playByPlay as $key => $events)
                    @php
                        [$inningKey, $halfKey] = explode('_', $key);
                        $inningLabel = $inningKey . '回' . ($halfKey === 'top' ? '表' : '裏');
                        $teamLabel = $halfKey === 'top' ? $teamAName : $teamBName;
                    @endphp
                    <div class="inning-section" style="animation-delay: {{ $loop->index * 0.1 }}s;">
                        <h3 class="h6 mt-3">{{ $inningLabel }}（{{ $teamLabel }}）</h3>
                        <ul class="ps-3 play-list">
                            @foreach ($events as $eventIndex => $event)
                                @php
                                    $resultType = $event['result_type'] ?? '';
                                @endphp
                                <li class="play-event play-{{ $resultType }}" style="animation-delay: {{ ($loop->parent->index * 0.1) + ($eventIndex * 0.05) }}s;">
                                    <span class="play-text">{{ $event['description'] ?? '' }}</span>
                                    @if($resultType === 'homerun')
                                        <span class="hr-effect">💥</span>
                                    @elseif(in_array($resultType, ['single', 'double', 'triple']))
                                        <span class="hit-effect">⚡</span>
                                    @elseif($resultType === 'strikeout')
                                        <span class="so-effect">🔥</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">試合ログ</div>
        <div class="card-body">
            @forelse($result['log'] ?? [] as $line)
                <div>{{ $line }}</div>
            @empty
                <div class="text-muted">ログはありません。</div>
            @endforelse
        </div>
    </div>

    @if(isset($customMatch) && $customMatch)
        <a href="{{ route('manager.game.index') }}" class="btn btn-outline-secondary">試合設定に戻る</a>
        <a href="{{ route('manager.games.index') }}" class="btn btn-outline-primary ms-2">試合一覧を見る</a>
    @else
        <a href="{{ route('game.index') }}" class="btn btn-outline-secondary">試合設定に戻る</a>
        <a href="{{ route('games.index') }}" class="btn btn-outline-primary ms-2">試合一覧を見る</a>
    @endif
@endsection

@push('styles')
<style>
/* 基本的なスタイル + 演出 */
.game-header {
    position: relative;
    overflow: hidden;
    border-radius: 16px;
    margin-bottom: 2rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.15);
    animation: fadeInDown 0.6s ease-out;
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
    border: 2px solid transparent;
    transition: transform 0.3s ease;
}

.team-score:hover {
    transform: translateY(-3px);
}

.team-score.winner {
    background: rgba(255, 215, 0, 0.2);
    border-color: rgba(255, 215, 0, 0.5);
    box-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
    animation: winnerPulse 2s ease-in-out infinite;
}

@keyframes winnerPulse {
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
    animation: fadeInUp 0.8s ease-out 0.5s both;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
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

.game-id a {
    color: rgba(255, 255, 255, 0.9);
    text-decoration: underline;
}

.game-id a:hover {
    color: white;
}

/* スコアボードの段階的表示 */
.inning-header,
.score-cell,
.total-score {
    opacity: 0;
    animation: fadeInUp 0.4s ease-out forwards;
}

.score-cell[data-score]:not([data-score="0"]):not([data-score="–"]):not([data-score=""]) {
    background-color: #fff3cd;
    font-weight: bold;
}

/* MVPカードの登場 */
.mvp-card {
    opacity: 0;
    animation: mvpReveal 1s ease-out 0.3s forwards;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.3);
}

@keyframes mvpReveal {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.mvp-title {
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

.mvp-name {
    font-size: 1.8rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
}

/* 選手カード */
.player-card {
    background: white;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    opacity: 0;
    animation: fadeInLeft 0.5s ease-out forwards;
}

@keyframes fadeInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.player-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.player-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

.player-order {
    font-size: 1.5rem;
    font-weight: bold;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
}

.player-name {
    font-size: 1.1rem;
    font-weight: bold;
    flex: 1;
    margin-left: 1rem;
}

.player-position {
    background: #e9ecef;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.9rem;
    font-weight: bold;
}

.player-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.stat-item {
    text-align: center;
    padding: 0.5rem;
    background: #f8f9fa;
    border-radius: 8px;
}

.stat-label {
    font-size: 0.75rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.1rem;
    font-weight: bold;
    color: #495057;
}

/* 試合経過のイベント表示 */
.inning-section {
    opacity: 0;
    animation: fadeInLeft 0.5s ease-out forwards;
}

.play-event {
    margin-bottom: 0.5rem;
    padding: 0.5rem;
    border-left: 3px solid transparent;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.play-event:hover {
    background-color: rgba(0,0,0,0.05);
    padding-left: 0.75rem;
}

.play-homerun {
    border-left-color: #dc3545;
    background-color: rgba(220, 53, 69, 0.1);
    font-weight: bold;
    color: #c92a2a;
}

.play-single,
.play-double,
.play-triple {
    border-left-color: #28a745;
    background-color: rgba(40, 167, 69, 0.1);
    color: #2b8a3e;
}

.play-strikeout {
    border-left-color: #ffc107;
    background-color: rgba(255, 193, 7, 0.1);
    color: #f59f00;
}

.play-walk {
    border-left-color: #17a2b8;
    background-color: rgba(23, 162, 184, 0.1);
    color: #0c5460;
}

.play-out {
    border-left-color: #6c757d;
    background-color: rgba(108, 117, 125, 0.05);
    color: #495057;
}

/* カードのホバーエフェクト */
.card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border: none;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.card-header {
    font-weight: 700;
    font-size: 1.1rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-bottom: 2px solid #dee2e6;
}

.btn {
    transition: all 0.3s ease;
    font-weight: 500;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.btn:active {
    transform: translateY(0);
}

.table tbody tr {
    transition: background-color 0.2s ease;
}

.table tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
}

.score-cell {
    transition: background-color 0.2s ease;
}

.score-cell:hover {
    background-color: rgba(255, 193, 7, 0.3) !important;
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
