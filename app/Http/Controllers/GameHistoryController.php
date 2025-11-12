<?php

namespace App\Http\Controllers;

use App\Models\Game;

class GameHistoryController extends Controller
{
    public function index()
    {
        $games = Game::with(['teamA', 'teamB'])
            ->whereNull('custom_team_id') // マネージャーモードでない試合のみ
            ->latest()
            ->take(20)
            ->get();

        return view('games.index', compact('games'));
    }

    public function show(Game $game)
    {
        $game->load(['teamA', 'teamB', 'customTeam']);

        return view('games.show', [
            'game' => $game,
            'result' => $game->result_json,
            'customMatch' => $game->custom_team_id !== null,
        ]);
    }
}

