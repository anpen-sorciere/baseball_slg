<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\CustomTeam;
use App\Models\CustomTeamPlayer;
use App\Models\PlayerSeason;
use App\Models\Team;
use App\Models\Game;
use App\Services\AutoLineupService;
use App\Services\GameEngine;
use App\Support\BuildsTeamContext;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CustomGameController extends Controller
{
    use BuildsTeamContext;

    public function index()
    {
        // 自分のチーム（先攻チーム用）
        $customTeams = CustomTeam::where('user_id', auth()->id())
            ->where('type', 'original')
            ->orderByDesc('year')
            ->orderBy('name')
            ->get();
        
        // 全ユーザーのオリジナルチーム（対戦相手用）
        $opponentCustomTeams = CustomTeam::with('user')
            ->where('type', 'original')
            ->orderByDesc('year')
            ->orderBy('name')
            ->get();
        
        $teams = Team::orderBy('name')->get();

        return view('manager.game', [
            'customTeams' => $customTeams,
            'opponentCustomTeams' => $opponentCustomTeams,
            'teams' => $teams,
        ]);
    }

    public function gameHistory()
    {
        $customTeamIds = CustomTeam::where('user_id', auth()->id())
            ->where('type', 'original')
            ->pluck('id');

        $games = Game::with(['customTeam', 'teamB'])
            ->whereIn('custom_team_id', $customTeamIds)
            ->latest()
            ->take(20)
            ->get();

        return view('manager.game_history', [
            'games' => $games,
        ]);
    }

    public function simulate(
        Request $request,
        AutoLineupService $lineupService,
        GameEngine $gameEngine
    ) {
        try {
            Log::info('Manager game simulation started');
            
            $data = $request->validate([
                'custom_team_id' => [
                    'required',
                    'integer',
                    function ($attribute, $value, $fail) {
                        $exists = CustomTeam::where('id', $value)
                            ->where('user_id', auth()->id())
                            ->where('type', 'original')
                            ->exists();
                        if (!$exists) {
                            $fail('選択したチームは存在しないか、アクセス権限がありません。');
                        }
                    },
                ],
                'opponent_type' => ['required', 'in:npb,custom'],
                'opponent_team_id' => ['required_if:opponent_type,npb', 'nullable', 'integer', 'exists:teams,id'],
                'opponent_custom_team_id' => [
                    'required_if:opponent_type,custom',
                    'nullable',
                    'integer',
                    function ($attribute, $value, $fail) {
                        if ($value) {
                            $exists = CustomTeam::where('id', $value)
                                ->where('type', 'original')
                                ->exists();
                            if (!$exists) {
                                $fail('選択した対戦相手チームは存在しません。');
                            }
                        }
                    },
                ],
            ]);
            Log::info('Validation passed', ['data' => $data]);

            $customTeam = CustomTeam::with([
                'players' => function ($query) {
                    $query->orderBy('is_pitcher')->orderBy('batting_order');
                },
                'players.playerSeason.player',
                'players.playerSeason.team',
            ])->where('user_id', auth()->id())
              ->where('type', 'original')
              ->findOrFail($data['custom_team_id']);
            Log::info('Custom team loaded', ['team_id' => $customTeam->id]);

            // 対戦相手が同じチームでないかチェック
            if ($data['opponent_type'] === 'custom' && $data['opponent_custom_team_id'] == $data['custom_team_id']) {
                return redirect()
                    ->route('manager.game.index')
                    ->withInput()
                    ->withErrors(['opponent_custom_team_id' => '同じチーム同士で対戦することはできません。']);
            }

            $year = (int) ($customTeam->year ?? date('Y'));
            Log::info('Year determined', ['year' => $year]);

            [$customLineup, $errors] = $this->buildCustomTeamLineup($customTeam);
            Log::info('Custom lineup built', ['errors_count' => $errors->count()]);
            if ($errors->isNotEmpty()) {
                return redirect()
                    ->route('manager.game.index')
                    ->withInput()
                    ->withErrors($errors, 'custom_team');
            }

            $opponentTeam = null;
            $opponentLineup = null;
            $opponentName = '';

            if ($data['opponent_type'] === 'npb') {
                $opponentTeam = Team::findOrFail($data['opponent_team_id']);
                $opponentName = $opponentTeam->name;
                $opponentLineup = $lineupService->buildForTeamYear($opponentTeam->id, $year);
                Log::info('NPB opponent loaded', ['team_id' => $opponentTeam->id]);
            } else {
                $opponentCustomTeam = CustomTeam::with([
                    'players' => function ($query) {
                        $query->orderBy('is_pitcher')->orderBy('batting_order');
                    },
                    'players.playerSeason.player',
                    'players.playerSeason.team',
                ])->where('type', 'original')
                  ->findOrFail($data['opponent_custom_team_id']);

                [$opponentLineup, $opponentErrors] = $this->buildCustomTeamLineup($opponentCustomTeam);
                Log::info('Opponent custom lineup built', ['errors_count' => $opponentErrors->count()]);
                if ($opponentErrors->isNotEmpty()) {
                    return redirect()
                        ->route('manager.game.index')
                        ->withInput()
                        ->withErrors($opponentErrors, 'opponent_custom_team');
                }

                $opponentName = $opponentCustomTeam->name;
                $opponentTeam = $opponentCustomTeam;
            }

            Log::info('Building team contexts');
            $customContext = $this->buildTeamContext('team_a', $customTeam->name, $customLineup);
            Log::info('Custom context built');
            $opponentContext = $this->buildTeamContext('team_b', $opponentName, $opponentLineup);
            Log::info('Opponent context built');

            Log::info('Starting game simulation');
            $result = $gameEngine->runNineInningSimulation($customContext, $opponentContext);
            Log::info('Game simulation completed');

            Log::info('Saving game result');
            // 試合結果を保存
            // result_jsonにチーム名を追加して保存
            $result['team_names'] = [
                'teamA' => $customTeam->name,
                'teamB' => $opponentName,
            ];
            $result['opponent_type'] = $data['opponent_type'];
            if ($data['opponent_type'] === 'custom') {
                $result['opponent_custom_team_id'] = $data['opponent_custom_team_id'];
            }
            
            $game = Game::create([
                'year' => $year,
                'team_a_id' => null, // 先攻チームは常にオリジナルチームなのでnull
                'team_b_id' => $data['opponent_type'] === 'npb' ? $data['opponent_team_id'] : null, // 後攻がNPBチームの場合のみ設定
                'score_a' => $result['score']['teamA'],
                'score_b' => $result['score']['teamB'],
                'result_json' => $result,
                'custom_team_id' => $customTeam->id,
            ]);
            Log::info('Game saved', ['game_id' => $game->id]);

            Log::info('Preparing view data');
            $playByPlay = collect($result['play_by_play'] ?? [])->groupBy(function ($event) {
                return ($event['inning'] ?? 0) . '_' . ($event['half'] ?? 'top');
            });
            Log::info('Play by play grouped', ['count' => $playByPlay->count()]);

            Log::info('Rendering view');
            $view = view('game.result', [
                'year' => $year,
                'teamA' => $customTeam,
                'teamB' => $opponentTeam,
                'result' => $result,
                'gameId' => $game->id,
                'customMatch' => true,
                'playByPlay' => $playByPlay,
            ]);
            Log::info('View created successfully');
            return $view;
        } catch (\Throwable $e) {
            Log::error('Manager game simulation error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'class' => get_class($e),
            ]);

            return redirect()
                ->route('manager.game.index')
                ->withInput()
                ->withErrors(['error' => '試合シミュレーション中にエラーが発生しました: ' . $e->getMessage()]);
        }
    }

    /**
     * @return array{0: array, 1: \Illuminate\Support\Collection}
     */
    protected function buildCustomTeamLineup(CustomTeam $customTeam): array
    {
        $errors = collect();

        $playerEntries = $customTeam->players->loadMissing('playerSeason.player');

        // 打順1～9番の選手（スタメン）
        $startingBattersEntries = $playerEntries
            ->where('is_pitcher', false)
            ->filter(function (CustomTeamPlayer $entry) {
                return $entry->batting_order !== null && $entry->batting_order >= 1 && $entry->batting_order <= 9;
            })
            ->sortBy('batting_order')
            ->values();

        // 控え選手（打順がnullまたは10番以降）
        $benchBattersEntries = $playerEntries
            ->where('is_pitcher', false)
            ->filter(function (CustomTeamPlayer $entry) {
                return $entry->batting_order === null || $entry->batting_order > 9;
            })
            ->values();

        if ($startingBattersEntries->isEmpty()) {
            $errors->push('打順が設定されていません。スタメンを編集してください。');
        }

        if ($startingBattersEntries->count() < 9) {
            $errors->push('打順1～9番がすべて設定されていません。スタメンを編集してください。');
        }

        $pitcherEntry = $playerEntries->firstWhere('is_starting_pitcher', true)
            ?? $playerEntries->where('is_pitcher', true)->sortBy('id')->first();
        if (!$pitcherEntry) {
            $errors->push('先発投手が設定されていません。スタメンを編集してください。');
        }

        // スタメン打者（打順1～9番）
        $batters = $startingBattersEntries->map(function (CustomTeamPlayer $entry) {
            $season = $entry->playerSeason;
            if (!$season) {
                return null;
            }
            $season = clone $season;
            $season->setAttribute('position_1', $entry->position ?? $season->position_1);
            $season->setAttribute('batting_order', $entry->batting_order);
            $season->setAttribute('player_season_id', $season->id);
            return $season;
        })->filter();

        // 控え選手（代打・守備交代用）
        $benchBatters = $benchBattersEntries->map(function (CustomTeamPlayer $entry) {
            $season = $entry->playerSeason;
            if (!$season) {
                return null;
            }
            $season = clone $season;
            $season->setAttribute('position_1', $entry->position ?? $season->position_1);
            $season->setAttribute('player_season_id', $season->id);
            return $season;
        })->filter();

        $pitcher = null;
        $relievers = collect();
        $closers = collect();
        if ($pitcherEntry && $pitcherEntry->playerSeason) {
            $pitcherSeason = clone $pitcherEntry->playerSeason;
            $pitcherSeason->setAttribute('position_1', $pitcherEntry->position ?? ($pitcherSeason->position_1 ?? 'P'));
            $pitcher = $pitcherSeason;

            // 中継ぎと抑えを分けて取得
            $relieverEntries = $playerEntries
                ->where('is_pitcher', true)
                ->reject(function (CustomTeamPlayer $entry) use ($pitcherEntry) {
                    return $pitcherEntry && $entry->id === $pitcherEntry->id;
                })
                ->filter(function (CustomTeamPlayer $entry) {
                    return $entry->pitcher_role === 'reliever';
                })
                ->sortBy('id')
                ->values();

            $closerEntries = $playerEntries
                ->where('is_pitcher', true)
                ->reject(function (CustomTeamPlayer $entry) use ($pitcherEntry) {
                    return $pitcherEntry && $entry->id === $pitcherEntry->id;
                })
                ->filter(function (CustomTeamPlayer $entry) {
                    return $entry->pitcher_role === 'closer';
                })
                ->sortBy('id')
                ->values();

            // 中継ぎ投手を取得
            $relievers = $relieverEntries->map(function (CustomTeamPlayer $entry) {
                if (!$entry->playerSeason) {
                    return null;
                }
                $season = clone $entry->playerSeason;
                if (!$season->relationLoaded('player')) {
                    $season->load('player');
                }
                return $season;
            })->filter()->values();

            // 抑え投手を取得
            $closers = $closerEntries->map(function (CustomTeamPlayer $entry) {
                if (!$entry->playerSeason) {
                    return null;
                }
                $season = clone $entry->playerSeason;
                if (!$season->relationLoaded('player')) {
                    $season->load('player');
                }
                return $season;
            })->filter()->values();

            // フォールバック：リリーフ投手が登録されていない場合
            if ($relievers->isEmpty() && $closers->isEmpty() && $pitcherSeason->team_id) {
                $fallbackRelievers = PlayerSeason::with('player')
                    ->where('team_id', $pitcherSeason->team_id)
                    ->where('year', $customTeam->year)
                    ->where('id', '<>', $pitcherSeason->id)
                    ->where(function ($query) {
                        $query->whereIn('role', ['reliever', 'closer'])
                            ->orWhere('pitcher_velocity', '>', 0);
                    })
                    ->orderByDesc('pitcher_velocity')
                    ->take(2)
                    ->get()
                    ->map(function (PlayerSeason $season) {
                        $cloned = clone $season;
                        if (!$cloned->relationLoaded('player')) {
                            $cloned->load('player');
                        }
                        return $cloned;
                    });
                
                // フォールバックの投手を中継ぎとして扱う
                $relievers = $fallbackRelievers;
            }
        }

        return [[
            'batters' => $batters instanceof Collection ? $batters : collect($batters),
            'bench_batters' => $benchBatters instanceof Collection ? $benchBatters : collect($benchBatters),
            'pitcher' => $pitcher,
            'relievers' => $relievers instanceof Collection ? $relievers : collect($relievers),
            'closers' => $closers instanceof Collection ? $closers : collect($closers),
        ], $errors];
    }
}

