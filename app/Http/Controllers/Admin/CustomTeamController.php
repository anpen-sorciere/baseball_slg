<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomTeam;
use App\Models\CustomTeamPlayer;
use App\Models\PlayerSeason;
use App\Models\Team;
use App\Services\AutoLineupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class CustomTeamController extends Controller
{
    public function __construct(private AutoLineupService $autoLineupService)
    {
        $this->middleware('auth');
    }

    /**
     * ログインユーザーがチームの所有者かチェック
     */
    protected function authorizeTeam(CustomTeam $customTeam): void
    {
        if ($customTeam->user_id !== auth()->id()) {
            abort(403, 'このチームへのアクセス権限がありません。');
        }
    }

    public function index()
    {
        $teams = CustomTeam::where('user_id', auth()->id())
            ->where('type', 'original')
            ->orderByDesc('year')
            ->orderBy('name')
            ->paginate(20);

        $hasTeam = CustomTeam::where('user_id', auth()->id())
            ->where('type', 'original')
            ->exists();

        return view('admin.custom_teams.index', compact('teams', 'hasTeam'));
    }

    public function create()
    {
        // 既にチームを持っている場合は作成不可
        $existingTeam = CustomTeam::where('user_id', auth()->id())
            ->where('type', 'original')
            ->first();

        if ($existingTeam) {
            return redirect()
                ->route('admin.custom-teams.index')
                ->withErrors(['error' => '既にオリジナルチームを作成済みです。1アカウントにつき1チームまで作成できます。']);
        }

        $teams = Team::orderBy('name')->get();
        return view('admin.custom_teams.create', compact('teams'));
    }

    public function store(Request $request)
    {
        // 既にチームを持っている場合は作成不可
        $existingTeam = CustomTeam::where('user_id', auth()->id())
            ->where('type', 'original')
            ->first();

        if ($existingTeam) {
            return redirect()
                ->route('admin.custom-teams.index')
                ->withErrors(['error' => '既にオリジナルチームを作成済みです。1アカウントにつき1チームまで作成できます。']);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'base_team_id' => ['required', 'integer', 'exists:teams,id'],
        ]);

        $baseTeamId = (int) $data['base_team_id'];
        $latestYear = PlayerSeason::where('team_id', $baseTeamId)->max('year');

        if (!$latestYear) {
            return back()
                ->withInput()
                ->withErrors(['base_team_id' => '選択したチームには利用可能な選手データがありません。']);
        }

        $customTeam = null;

        try {
            DB::transaction(function () use (&$customTeam, $data, $baseTeamId, $latestYear) {
                $customTeam = CustomTeam::create([
                    'user_id' => auth()->id(),
                    'name' => $data['name'],
                    'short_name' => $this->makeShortName($data['name']),
                    'type' => 'original',
                    'year' => $latestYear,
                    'notes' => null,
                ]);

                $this->seedRosterFromBaseTeam($customTeam, $baseTeamId, $latestYear);
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // ユニーク制約違反の場合
            if ($e->getCode() == '23000' && (str_contains($e->getMessage(), 'Duplicate entry') || str_contains($e->getMessage(), 'uniq_custom_teams_user_id'))) {
                return redirect()
                    ->route('admin.custom-teams.index')
                    ->withErrors(['error' => '既にオリジナルチームを作成済みです。1アカウントにつき1チームまで作成できます。']);
            }
            throw $e;
        }

        return redirect()
            ->route('admin.custom-teams.roster.edit', $customTeam)
            ->with('status', 'オリジナルチームを作成し、ベンチ枠を自動登録しました。');
    }

    protected function seedRosterFromBaseTeam(CustomTeam $customTeam, int $baseTeamId, int $year): void
    {
        $seasons = PlayerSeason::with(['player', 'team'])
            ->where('team_id', $baseTeamId)
            ->where('year', $year)
            ->get();

        if ($seasons->isEmpty()) {
            return;
        }

        $auto = $this->autoLineupService->buildForTeamYear($baseTeamId, $year);
        $startingBatters = collect($auto['batters'] ?? [])->filter();
        $startingPitcher = $auto['pitcher'] ?? null;
        $suggestedRelievers = collect($auto['relievers'] ?? [])->filter();

        $battersPool = $seasons->filter(function (PlayerSeason $season) {
            return ($season->batting_contact ?? 0) > 0
                || ($season->batting_power ?? 0) > 0
                || ($season->running_speed ?? 0) > 0
                || in_array($season->role, ['regular', 'bench'], true);
        })->map(function (PlayerSeason $season) {
            $season->lineup_score =
                ($season->batting_contact ?? 0) * 0.6 +
                ($season->batting_power ?? 0) * 0.4;
            return $season;
        })->sortByDesc('lineup_score')
            ->values();

        $selectedBatterIds = collect();

        foreach ($startingBatters as $index => $season) {
            if (!$season || $selectedBatterIds->contains($season->id)) {
                continue;
            }

            CustomTeamPlayer::create([
                'custom_team_id' => $customTeam->id,
                'player_season_id' => $season->id,
                'batting_order' => $index + 1,
                'position' => $season->position_1 ?? null,
                'is_pitcher' => false,
                'is_starting_pitcher' => false,
            ]);

            $selectedBatterIds->push($season->id);
        }

        $remainingBattersNeeded = max(0, 13 - $selectedBatterIds->count());
        $extraBatters = $battersPool->reject(function (PlayerSeason $season) use ($selectedBatterIds) {
            return $selectedBatterIds->contains($season->id);
        })->take($remainingBattersNeeded);

        foreach ($extraBatters as $season) {
            CustomTeamPlayer::create([
                'custom_team_id' => $customTeam->id,
                'player_season_id' => $season->id,
                'batting_order' => null,
                'position' => $season->position_1 ?? null,
                'is_pitcher' => false,
                'is_starting_pitcher' => false,
            ]);
            $selectedBatterIds->push($season->id);
        }

        $pitchersPool = $seasons->filter(function (PlayerSeason $season) {
            return in_array($season->role, ['starter', 'reliever', 'closer'], true)
                || ($season->pitcher_velocity ?? 0) > 0
                || ($season->pitcher_control ?? 0) > 0
                || ($season->pitcher_stamina ?? 0) > 0;
        })->map(function (PlayerSeason $season) {
            $season->relief_score =
                ($season->pitcher_stamina ?? 0) * 0.3 +
                ($season->pitcher_velocity ?? 0) * 0.35 +
                ($season->pitcher_control ?? 0) * 0.35;
            return $season;
        })->sortByDesc('relief_score')
            ->values();

        $selectedPitcherIds = collect();
        $pitcherIdOrder = collect();

        $startingPitcherCandidate = $startingPitcher && !$selectedBatterIds->contains($startingPitcher->id)
            ? $startingPitcher
            : null;

        if (!$startingPitcherCandidate) {
            $startingPitcherCandidate = $pitchersPool->first(function (PlayerSeason $season) use ($selectedBatterIds) {
                return !$selectedBatterIds->contains($season->id);
            });
        }

        if ($startingPitcherCandidate) {
            CustomTeamPlayer::create([
                'custom_team_id' => $customTeam->id,
                'player_season_id' => $startingPitcherCandidate->id,
                'batting_order' => null,
                'position' => 'P',
                'is_pitcher' => true,
                'is_starting_pitcher' => false,
                'pitcher_role' => null,
            ]);
            $selectedPitcherIds->push($startingPitcherCandidate->id);
            $pitcherIdOrder->push($startingPitcherCandidate->id);
        }

        $remainingSlots = 13 - $selectedPitcherIds->count();

        $reliefCandidates = $suggestedRelievers->merge($pitchersPool)
            ->unique('id')
            ->reject(function ($season) use ($selectedBatterIds, $selectedPitcherIds) {
                return !$season
                    || $selectedBatterIds->contains($season->id)
                    || $selectedPitcherIds->contains($season->id);
            })
            ->take($remainingSlots);

        foreach ($reliefCandidates as $season) {
            CustomTeamPlayer::create([
                'custom_team_id' => $customTeam->id,
                'player_season_id' => $season->id,
                'batting_order' => null,
                'position' => 'P',
                'is_pitcher' => true,
                'is_starting_pitcher' => false,
                'pitcher_role' => null,
            ]);
            $selectedPitcherIds->push($season->id);
            $pitcherIdOrder->push($season->id);
        }

        $preferredStarterId = $startingPitcherCandidate?->id;
        $this->syncPitcherRoles($customTeam, $pitcherIdOrder, $preferredStarterId);
    }

    protected function makeShortName(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return 'TEAM';
        }

        $short = Str::limit($trimmed, 12, '');

        if ($short === '') {
            $short = 'TEAM';
        }

        return $short;
    }

    public function edit(CustomTeam $customTeam)
    {
        $this->authorizeTeam($customTeam);
        return view('admin.custom_teams.edit', compact('customTeam'));
    }

    public function editRoster(CustomTeam $customTeam)
    {
        $this->authorizeTeam($customTeam);
        $customTeam->load(['players.playerSeason.player', 'players.playerSeason.team']);

        $playerSeasons = PlayerSeason::with(['player', 'team'])
            ->where('year', $customTeam->year)
            ->orderBy('team_id')
            ->orderBy('player_id')
            ->get();

        $currentBatters = $customTeam->players
            ->where('is_pitcher', false)
            ->pluck('player_season_id')
            ->toArray();

        $currentPitchers = $customTeam->players
            ->where('is_pitcher', true)
            ->pluck('player_season_id')
            ->toArray();

        $pitcherRoles = $customTeam->players
            ->where('is_pitcher', true)
            ->mapWithKeys(function (CustomTeamPlayer $entry) {
                return [$entry->player_season_id => $entry->pitcher_role];
            });

        $batterCandidates = $playerSeasons->filter(function (PlayerSeason $season) {
            // is_pitcherフラグがtrueの選手は野手リストから除外
            if ($season->is_pitcher ?? false) {
                return false;
            }
            
            // 野手としての能力がある選手のみ
            return ($season->batting_contact ?? 0) > 0
                || ($season->batting_power ?? 0) > 0
                || ($season->running_speed ?? 0) > 0
                || in_array($season->role, ['regular', 'bench'], true);
        });

        $pitcherCandidates = $playerSeasons->filter(function (PlayerSeason $season) {
            // is_pitcherフラグがtrueの場合、または投手能力がある場合
            if (($season->is_pitcher ?? false) === true) {
                return true;
            }
            // is_pitcherが設定されていない場合、従来のロジックで判断
            return in_array($season->role, ['starter', 'reliever', 'closer'], true)
                || ($season->pitcher_velocity ?? 0) > 0
                || ($season->pitcher_control ?? 0) > 0
                || ($season->pitcher_stamina ?? 0) > 0;
        });

        return view('admin.custom_teams.roster', [
            'customTeam' => $customTeam,
            'playerSeasons' => $playerSeasons,
            'batterCandidates' => $batterCandidates,
            'pitcherCandidates' => $pitcherCandidates,
            'currentBatters' => $currentBatters,
            'currentPitchers' => $currentPitchers,
            'pitcherRoles' => $pitcherRoles,
            'batterCount' => count($currentBatters),
            'pitcherCount' => count($currentPitchers),
        ]);
    }

    public function updateRoster(Request $request, CustomTeam $customTeam)
    {
        $this->authorizeTeam($customTeam);
        $data = $request->validate([
            'batters' => ['array'],
            'batters.*' => ['integer', 'exists:player_seasons,id'],
            'pitchers' => ['array'],
            'pitchers.*' => ['integer', 'exists:player_seasons,id'],
        ]);

        $batters = collect($data['batters'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $pitchers = collect($data['pitchers'] ?? [])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($batters->intersect($pitchers)->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['pitchers' => '同じ選手を野手と投手の両方に登録することはできません。']);
        }

        $battersExceeded = max(0, $batters->count() - 13);
        $pitchersExceeded = max(0, $pitchers->count() - 13);
        $totalExceeded = max(0, ($batters->count() + $pitchers->count()) - 26);

        $errorsArray = [];

        if ($battersExceeded > 0) {
            $errorsArray['batters'] = "野手枠は最大13人までです（{$batters->count()}人選択中）。";
        }

        if ($pitchersExceeded > 0) {
            $errorsArray['pitchers'] = "投手枠は最大13人までです（{$pitchers->count()}人選択中）。";
        }

        if ($totalExceeded > 0) {
            $errorsArray['total'] = '登録人数の合計は26人までです。';
        }

        if (!empty($errorsArray)) {
            return back()
                ->withInput()
                ->withErrors($errorsArray);
        }

        $previousStarterId = optional(
            $customTeam->players()
                ->where('is_starting_pitcher', true)
                ->first()
        )->player_season_id;

        DB::transaction(function () use ($customTeam, $batters, $pitchers, $previousStarterId) {
            $keepIds = $batters->merge($pitchers)->unique();

            $customTeam->players()
                ->whereNotIn('player_season_id', $keepIds)
                ->delete();

            $currentPlayers = $customTeam->players()
                ->get()
                ->keyBy('player_season_id');

            foreach ($batters as $playerSeasonId) {
                $player = $currentPlayers->get($playerSeasonId);
                if ($player) {
                    $player->fill([
                        'is_pitcher' => false,
                        'is_starting_pitcher' => false,
                    ])->save();
                } else {
                    CustomTeamPlayer::create([
                        'custom_team_id' => $customTeam->id,
                        'player_season_id' => $playerSeasonId,
                        'batting_order' => null,
                        'position' => null,
                        'is_pitcher' => false,
                        'is_starting_pitcher' => false,
                    ]);
                }
            }

            foreach ($pitchers as $playerSeasonId) {
                $player = $currentPlayers->get($playerSeasonId);
                if ($player) {
                    $player->fill([
                        'is_pitcher' => true,
                    ])->save();
                } else {
                    CustomTeamPlayer::create([
                        'custom_team_id' => $customTeam->id,
                        'player_season_id' => $playerSeasonId,
                        'batting_order' => null,
                        'position' => 'P',
                        'is_pitcher' => true,
                        'is_starting_pitcher' => false,
                        'pitcher_role' => null,
                    ]);
                }
            }

            $customTeam->players()
                ->whereNotIn('player_season_id', $batters->toArray())
                ->update(['batting_order' => null]);

            $preferredStarterId = $previousStarterId && $pitchers->contains($previousStarterId)
                ? $previousStarterId
                : null;

            $this->syncPitcherRoles($customTeam, $pitchers, $preferredStarterId);
        });

        return redirect()
            ->route('admin.custom-teams.roster.edit', $customTeam)
            ->with('status', 'ベンチ登録を更新しました。');
    }

    public function update(Request $request, CustomTeam $customTeam)
    {
        $this->authorizeTeam($customTeam);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $customTeam->update([
            'name' => $data['name'],
            'short_name' => $this->makeShortName($data['name']),
        ]);

        return redirect()
            ->route('admin.custom-teams.index')
            ->with('status', 'オリジナルチームを更新しました。');
    }

    public function destroy(CustomTeam $customTeam)
    {
        $this->authorizeTeam($customTeam);
        $customTeam->players()->delete();
        $customTeam->delete();

        return redirect()
            ->route('admin.custom-teams.index')
            ->with('status', 'オリジナルチームを削除しました。');
    }

    public function editLineup(CustomTeam $customTeam)
    {
        $this->authorizeTeam($customTeam);
        $customTeam->load(['players.playerSeason.player', 'players.playerSeason.team']);

        $playerSeasons = PlayerSeason::with(['player', 'team'])
            ->where('year', $customTeam->year)
            ->orderBy('team_id')
            ->orderBy('player_id')
            ->get();

        $pitcher = $customTeam->players
            ->where('is_pitcher', true)
            ->firstWhere('is_starting_pitcher', true)
            ?? $customTeam->players->where('is_pitcher', true)->first();

        $batterCount = $customTeam->players->where('is_pitcher', false)->count();
        $pitcherCount = $customTeam->players->where('is_pitcher', true)->count();

        $rosterBatters = $customTeam->players
            ->where('is_pitcher', false)
            ->sortBy(function (CustomTeamPlayer $player) {
                $order = $player->batting_order ?? 999;
                return str_pad((string) $order, 3, '0', STR_PAD_LEFT) . '_' . str_pad((string) $player->id, 6, '0', STR_PAD_LEFT);
            })
            ->values();

        $rosterPitchers = $customTeam->players
            ->where('is_pitcher', true)
            ->sortBy(function (CustomTeamPlayer $player) {
                $flag = $player->is_starting_pitcher ? '0' : '1';
                return $flag . '_' . str_pad((string) $player->id, 6, '0', STR_PAD_LEFT);
            })
            ->values();

        $startingBatters = $rosterBatters
            ->filter(fn (CustomTeamPlayer $player) => $player->batting_order !== null)
            ->sortBy('batting_order')
            ->values();

        $benchBatters = $rosterBatters
            ->filter(fn (CustomTeamPlayer $player) => $player->batting_order === null)
            ->values();

        $pitcherRoles = $rosterPitchers->mapWithKeys(function (CustomTeamPlayer $player) {
            return [$player->player_season_id => $player->pitcher_role];
        });

        $pitcherRoleCounts = [
            'starter' => $rosterPitchers->where('pitcher_role', 'starter')->count(),
            'reliever' => $rosterPitchers->where('pitcher_role', 'reliever')->count(),
            'closer' => $rosterPitchers->where('pitcher_role', 'closer')->count(),
        ];

        return view('admin.custom_teams.lineup', [
            'customTeam' => $customTeam,
            'playerSeasons' => $playerSeasons,
            'rosterBatters' => $rosterBatters,
            'rosterPitchers' => $rosterPitchers,
            'startingBattersByOrder' => $startingBatters->keyBy(function (CustomTeamPlayer $player) {
                return (int) $player->batting_order;
            }),
            'pitcher' => $pitcher,
            'batterCount' => $batterCount,
            'pitcherCount' => $pitcherCount,
            'benchBatters' => $benchBatters,
            'pitcherRoles' => $pitcherRoles,
            'pitcherRoleCounts' => $pitcherRoleCounts,
        ]);
    }

    public function updateLineup(Request $request, CustomTeam $customTeam)
    {
        $this->authorizeTeam($customTeam);
        $customTeam->load('players');
        
        $data = $request->validate([
            'batters' => ['array'],
            'batters.*.player_season_id' => ['nullable', 'integer', 'exists:player_seasons,id'],
            'batters.*.position' => ['nullable', 'string', 'max:5'],
            'pitcher.player_season_id' => ['nullable', 'integer', 'exists:player_seasons,id'],
        ]);

        $batters = collect($data['batters'] ?? [])
            ->values()
            ->map(function ($row, $index) {
                $playerSeasonId = $row['player_season_id'] ?? null;
                if (!$playerSeasonId) {
                    return null;
                }

                $allowedPositions = ['C', '1B', '2B', '3B', 'SS', 'LF', 'CF', 'RF', 'DH'];
                $position = isset($row['position']) ? strtoupper(trim($row['position'])) : null;
                if (!$position || !in_array($position, $allowedPositions, true)) {
                    $position = null;
                }

                return [
                    'player_season_id' => (int) $playerSeasonId,
                    'position' => $position,
                    'batting_order' => $index + 1,
                ];
            })
            ->filter()
            ->values()
            ->take(9);

        $pitcherSeasonId = isset($data['pitcher']['player_season_id'])
            ? (int) $data['pitcher']['player_season_id']
            : null;

        if ($batters->count() < 1 || !$pitcherSeasonId) {
            return back()
                ->withInput()
                ->withErrors([
                    'batters' => '打者を最低1人、先発投手を選択してください。',
                ]);
        }

        if ($batters->pluck('player_season_id')->contains($pitcherSeasonId)) {
            return back()
                ->withInput()
                ->withErrors([
                    'pitcher.player_season_id' => '先発投手は打者と重複しないようにしてください。',
                ]);
        }

        if ($batters->pluck('player_season_id')->duplicates()->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors([
                    'batters' => '同じ選手を複数の打順に配置することはできません。',
                ]);
        }

        $positions = $batters->pluck('position')->filter();
        if ($positions->duplicates()->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors([
                    'batters' => '同じ守備位置に複数の選手を配置することはできません。',
                ]);
        }

        $roster = $customTeam->players->keyBy('player_season_id');

        $missingBatters = $batters->filter(function ($row) use ($roster) {
            return !$roster->has($row['player_season_id']);
        });

        if ($missingBatters->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['batters' => '打順に設定する選手は先にベンチ登録してください。']);
        }

        $invalidBatters = $batters->filter(function ($row) use ($roster) {
            $player = $roster->get($row['player_season_id']);
            return $player->is_pitcher;
        });

        if ($invalidBatters->isNotEmpty()) {
            return back()
                ->withInput()
                ->withErrors(['batters' => '野手として登録された選手のみ打順に設定できます。']);
        }

        $pitcherRecord = $roster->get($pitcherSeasonId);

        if (!$pitcherRecord) {
            return back()
                ->withInput()
                ->withErrors(['pitcher.player_season_id' => '先発投手はベンチの投手枠に登録してください。']);
        }

        if (!$pitcherRecord->is_pitcher) {
            return back()
                ->withInput()
                ->withErrors(['pitcher.player_season_id' => '投手枠に登録された選手から先発投手を選択してください。']);
        }

        $rosterPitchers = $roster->filter(fn (CustomTeamPlayer $player) => $player->is_pitcher);
        $allowedPitcherRoles = ['starter', 'reliever', 'closer'];
        $pitcherRolesInput = collect($request->input('pitcher_roles', []))
            ->mapWithKeys(function ($role, $id) {
                $cleanId = (int) $id;
                $cleanRole = is_string($role) ? strtolower(trim($role)) : null;
                return [$cleanId => $cleanRole];
            });

        $pitcherRoleErrors = [];

        $rosterPitcherIds = $rosterPitchers->pluck('player_season_id')->map(fn ($id) => (int) $id);
        if ($pitcherRolesInput->keys()->diff($rosterPitcherIds)->isNotEmpty()
            || $rosterPitcherIds->diff($pitcherRolesInput->keys())->isNotEmpty()) {
            $pitcherRoleErrors['pitcher_roles'] = '登録済みの投手すべてに役割を設定してください。';
        }

        $counts = [
            'starter' => 0,
            'reliever' => 0,
            'closer' => 0,
        ];

        foreach ($pitcherRolesInput as $playerSeasonId => $role) {
            if (!in_array($role, $allowedPitcherRoles, true)) {
                $pitcherRoleErrors['pitcher_roles'] = '投手の役割は「先発」「中継ぎ」「抑え」から選択してください。';
                break;
            }
            $counts[$role]++;
        }

        if (($counts['starter'] ?? 0) !== 6
            || ($counts['reliever'] ?? 0) !== 5
            || ($counts['closer'] ?? 0) !== 2) {
            $pitcherRoleErrors['pitcher_roles'] = sprintf(
                '投手の役割は先発6人・中継ぎ5人・抑え2人となるように設定してください。（先発:%d / 中継ぎ:%d / 抑え:%d）',
                $counts['starter'] ?? 0,
                $counts['reliever'] ?? 0,
                $counts['closer'] ?? 0
            );
        }

        if (($pitcherRolesInput->get($pitcherSeasonId) ?? null) !== 'starter') {
            $pitcherRoleErrors['pitcher.player_season_id'] = '先発投手に選択した選手は役割を「先発」に設定してください。';
        }

        if (!empty($pitcherRoleErrors)) {
            return back()
                ->withInput()
                ->withErrors($pitcherRoleErrors);
        }

        DB::transaction(function () use ($customTeam, $batters, $pitcherSeasonId, $pitcherRolesInput) {
            $customTeam->players()
                ->where('is_pitcher', false)
                ->update([
                    'batting_order' => null,
                    'position' => null,
                ]);

            foreach ($batters as $row) {
                $customTeam->players()
                    ->where('player_season_id', $row['player_season_id'])
                    ->update([
                        'batting_order' => $row['batting_order'],
                        'position' => $row['position'],
                        'is_pitcher' => false,
                    ]);
            }

            $customTeam->players()
                ->where('is_pitcher', true)
                ->update([
                    'is_starting_pitcher' => false,
                    'pitcher_role' => null,
                ]);

            foreach ($pitcherRolesInput as $playerSeasonId => $role) {
                $customTeam->players()
                    ->where('player_season_id', $playerSeasonId)
                    ->update([
                        'pitcher_role' => $role,
                        'is_pitcher' => true,
                        'position' => 'P',
                    ]);
            }

            $customTeam->players()
                ->where('player_season_id', $pitcherSeasonId)
                ->update([
                    'is_starting_pitcher' => true,
                    'pitcher_role' => $pitcherRolesInput->get($pitcherSeasonId),
                    'is_pitcher' => true,
                    'position' => 'P',
                ]);
        });

        return redirect()
            ->route('admin.custom-teams.index')
            ->with('status', 'チーム編成を更新しました。');
    }

    protected function syncPitcherRoles(CustomTeam $customTeam, Collection $pitcherIds, ?int $preferredStarterId = null): void
    {
        $pitcherIds = $pitcherIds
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $total = $pitcherIds->count();

        if ($total === 0) {
            $customTeam->players()
                ->where('is_pitcher', true)
                ->update([
                    'pitcher_role' => null,
                    'is_starting_pitcher' => false,
                ]);
            return;
        }

        $seasons = PlayerSeason::whereIn('id', $pitcherIds->all())->get()->keyBy('id');
        $orderedSeasons = $pitcherIds
            ->map(fn ($id) => $seasons->get($id))
            ->filter()
            ->values();

        if ($orderedSeasons->isEmpty()) {
            return;
        }

        $starterTarget = min(6, $orderedSeasons->count());
        $remaining = max(0, $orderedSeasons->count() - $starterTarget);
        $relieverTarget = min(5, $remaining);
        $remaining = max(0, $remaining - $relieverTarget);
        $closerTarget = min(2, $remaining);

        $roles = [];
        $assigned = collect();

        if ($preferredStarterId && $seasons->has($preferredStarterId)) {
            $roles[$preferredStarterId] = 'starter';
            $assigned->push($preferredStarterId);
            $starterTarget = max(0, $starterTarget - 1);
        } else {
            $preferredStarterId = null;
        }

        $assignFromPool = function (Collection $pool, string $role, int &$slots) use (&$roles, &$assigned) {
            if ($slots <= 0) {
                return;
            }

            foreach ($pool as $id) {
                if ($slots <= 0) {
                    break;
                }
                if ($assigned->contains($id)) {
                    continue;
                }
                $roles[$id] = $role;
                $assigned->push($id);
                $slots--;
            }
        };

        $starterPool = $orderedSeasons
            ->sortByDesc(function (PlayerSeason $season) {
                return ($season->pitcher_stamina ?? 0) * 1.0
                    + ($season->pitcher_control ?? 0) * 0.3
                    + ($season->pitcher_velocity ?? 0) * 0.1;
            })
            ->pluck('id');
        $assignFromPool($starterPool, 'starter', $starterTarget);

        $remainingSeasons = $orderedSeasons->filter(fn ($season) => $season && !$assigned->contains($season->id));
        $closerPool = $remainingSeasons
            ->sortByDesc(function (PlayerSeason $season) {
                return ($season->pitcher_velocity ?? 0) * 0.6
                    + ($season->pitcher_control ?? 0) * 0.4;
            })
            ->pluck('id');
        $assignFromPool($closerPool, 'closer', $closerTarget);

        $remainingSeasons = $orderedSeasons->filter(fn ($season) => $season && !$assigned->contains($season->id));
        $relieverPool = $remainingSeasons
            ->sortByDesc(function (PlayerSeason $season) {
                return ($season->pitcher_control ?? 0) * 0.4
                    + ($season->pitcher_movement ?? 0) * 0.35
                    + ($season->pitcher_velocity ?? 0) * 0.25;
            })
            ->pluck('id');
        $assignFromPool($relieverPool, 'reliever', $relieverTarget);

        foreach ($orderedSeasons as $season) {
            if ($season && !isset($roles[$season->id])) {
                $roles[$season->id] = 'reliever';
            }
        }

        $starterIds = collect($roles)
            ->filter(fn ($role) => $role === 'starter')
            ->keys();

        $startingPitcherId = null;
        if ($preferredStarterId && $starterIds->contains($preferredStarterId)) {
            $startingPitcherId = $preferredStarterId;
        } elseif ($starterIds->isNotEmpty()) {
            $startingPitcherId = $starterIds->first();
        } else {
            $startingPitcherId = $orderedSeasons->first()->id;
        }

        $customTeam->players()
            ->where('is_pitcher', true)
            ->update([
                'pitcher_role' => null,
                'is_starting_pitcher' => false,
            ]);

        foreach (['starter', 'reliever', 'closer'] as $roleKey) {
            $roleIds = collect($roles)
                ->filter(fn ($role) => $role === $roleKey)
                ->keys();

            if ($roleIds->isNotEmpty()) {
                $customTeam->players()
                    ->whereIn('player_season_id', $roleIds->all())
                    ->update(['pitcher_role' => $roleKey]);
            }
        }

        $customTeam->players()
            ->where('is_pitcher', true)
            ->whereNull('pitcher_role')
            ->update(['pitcher_role' => 'reliever']);

        if ($startingPitcherId) {
            $customTeam->players()
                ->where('player_season_id', $startingPitcherId)
                ->update(['is_starting_pitcher' => true]);
        }
    }
}

