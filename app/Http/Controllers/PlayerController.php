<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Player;
use App\Models\Team;

class PlayerController extends Controller
{
    /**
     * 選手一覧＋追加フォーム
     */
    public function index(Request $request)
    {
        $teamId = $request->input('team_id');

        $players = Player::with(['seasons' => function ($query) {
                $query->with('team')->orderByDesc('year');
            }])
            ->orderBy('name')
            ->get();

        if ($teamId) {
            $players = $players->filter(function (Player $player) use ($teamId) {
                $latestSeason = $player->seasons->first();
                return $latestSeason && (string) $latestSeason->team_id === (string) $teamId;
            })->values();
        }

        $teams = Team::orderBy('name')->get();

        return view('admin.players', [
            'players' => $players,
            'teamId' => $teamId,
            'teams' => $teams,
        ]);
    }

    /**
     * 新規選手追加
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'furigana'         => 'nullable|string|max:255',
            'handed_bat'       => 'nullable|in:右,左,両',
            'handed_throw'     => 'nullable|in:右,左',
            'position_1'       => 'required|string|max:10',
            'position_2'       => 'nullable|string|max:10',
            'position_3'       => 'nullable|string|max:10',
            'born_year'        => 'nullable|integer',
        ]);

        Player::create($validated);

        return redirect()
            ->route('players.index', ['team_id' => $request->input('team_id')])
            ->with('status', '選手を登録しました！');
    }

    /**
     * 選手編集フォーム表示
     */
    public function edit(Player $player)
    {
        $teams = Team::orderBy('name')->get();
        return view('admin.players_edit', [
            'player' => $player,
            'teams' => $teams,
        ]);
    }

    /**
     * 選手更新処理
     */
    public function update(Request $request, Player $player)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'furigana'         => 'nullable|string|max:255',
            'handed_bat'       => 'nullable|in:右,左,両',
            'handed_throw'     => 'nullable|in:右,左',
            'position_1'       => 'required|string|max:10',
            'position_2'       => 'nullable|string|max:10',
            'position_3'       => 'nullable|string|max:10',
            'born_year'        => 'nullable|integer',
        ]);

        $player->update($validated);

        return redirect()
            ->route('players.index', ['team_id' => $request->input('team_id')])
            ->with('status', '選手情報を更新しました！');
    }
}
