<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlayerSeason;
use App\Models\Team;
use Illuminate\Http\Request;

class PlayerSeasonController extends Controller
{
    public function index(Request $request)
    {
        $years = PlayerSeason::query()
            ->select('year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year');

        $teams = Team::orderBy('name')->get(['id', 'name']);

        $seasonsQuery = PlayerSeason::with(['player', 'team'])
            ->orderByDesc('year')
            ->orderBy('team_id')
            ->orderBy('player_id');

        if ($request->filled('year')) {
            $seasonsQuery->where('year', (int) $request->input('year'));
        }

        if ($request->filled('team_id')) {
            $seasonsQuery->where('team_id', (int) $request->input('team_id'));
        }

        if ($request->filled('keyword')) {
            $keyword = $request->input('keyword');
            $seasonsQuery->whereHas('player', function ($query) use ($keyword) {
                $query->where('name', 'like', '%' . $keyword . '%');
            });
        }

        $playerSeasons = $seasonsQuery->paginate(50)->appends($request->query());

        return view('admin.player_seasons.index', [
            'playerSeasons' => $playerSeasons,
            'years' => $years,
            'teams' => $teams,
            'filters' => [
                'year' => $request->input('year'),
                'team_id' => $request->input('team_id'),
                'keyword' => $request->input('keyword'),
            ],
        ]);
    }
}

