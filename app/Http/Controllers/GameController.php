<?php

namespace App\Http\Controllers;

use App\Models\PlayerSeason;
use App\Models\Team;
use App\Models\Game;
use App\Services\AutoLineupService;
use App\Services\GameEngine;
use App\Support\BuildsTeamContext;
use Illuminate\Http\Request;

class GameController extends Controller
{
    use BuildsTeamContext;

    public function index(Request $request)
    {
        $teams = Team::orderBy('name')->get();
        $years = PlayerSeason::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn ($year) => (int) $year)
            ->toArray();

        if (empty($years)) {
            $years = [2025];
        }

        $requestedYear = $request->input('year');
        $defaultYear = $requestedYear && in_array((int) $requestedYear, $years, true)
            ? (int) $requestedYear
            : $years[0];

        return view('game.index', [
            'teams' => $teams,
            'years' => $years,
            'defaultYear' => $defaultYear,
            'selectedTeamA' => $request->input('team_a_id'),
            'selectedTeamB' => $request->input('team_b_id'),
        ]);
    }

    public function simulate(
        Request $request,
        AutoLineupService $lineupService,
        GameEngine $gameEngine
    ) {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'team_a_id' => ['required', 'integer', 'different:team_b_id', 'exists:teams,id'],
            'team_b_id' => ['required', 'integer', 'exists:teams,id'],
        ]);

        $year = (int) $data['year'];
        $teamA = Team::findOrFail($data['team_a_id']);
        $teamB = Team::findOrFail($data['team_b_id']);

        $lineupA = $lineupService->buildForTeamYear($teamA->id, $year);
        $lineupB = $lineupService->buildForTeamYear($teamB->id, $year);

        $contextA = $this->buildTeamContext('team_a', $teamA->name, $lineupA);
        $contextB = $this->buildTeamContext('team_b', $teamB->name, $lineupB);

        $result = $gameEngine->runNineInningSimulation($contextA, $contextB);
        $playByPlay = collect($result['play_by_play'] ?? [])->groupBy(function ($event) {
            return $event['inning'] . '_' . $event['half'];
        });

        $game = Game::create([
            'year' => $year,
            'team_a_id' => $teamA->id,
            'team_b_id' => $teamB->id,
            'score_a' => $result['score']['teamA'],
            'score_b' => $result['score']['teamB'],
            'result_json' => $result,
        ]);

        return view('game.result', [
            'year' => $year,
            'teamA' => $teamA,
            'teamB' => $teamB,
            'result' => $result,
            'gameId' => $game->id,
            'playByPlay' => $playByPlay,
        ]);
    }

}


