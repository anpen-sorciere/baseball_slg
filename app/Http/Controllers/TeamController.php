<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\PlayerSeason;

class TeamController extends Controller
{
    /**
     * チーム一覧＋追加フォーム
     */
    public function index()
    {
        $teams = Team::orderBy('league')->orderBy('name')->get();

        return view('admin.teams', compact('teams'));
    }

    /**
     * チーム追加処理
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'short_name'   => 'required|string|max:50',
            'league'       => 'required|string|max:50',
            'founded_year' => 'nullable|integer',
        ]);

        Team::create($validated);

        return redirect()
            ->route('teams.index')
            ->with('status', 'チームを登録しました！');
    }

    /**
     * チーム詳細：所属選手一覧
     */
    public function show(Team $team)
    {
        // player_seasons に紐づく選手を年度降順で取得
        $seasons = PlayerSeason::with('player')
            ->where('team_id', $team->id)
            ->orderBy('year', 'desc')
            ->get();

        // シンプルに「投手」と「野手」に分ける
        $pitchers = $seasons->filter(fn ($s) => $s->role === '投手');
        $batters  = $seasons->filter(fn ($s) => $s->role === '野手');

        return view('teams.show', compact('team', 'pitchers', 'batters'));
    }
}
