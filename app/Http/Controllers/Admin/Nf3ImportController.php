<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nf3BattingRow;
use App\Models\Team;
use App\Services\Import\Nf3BattingPasteParser;
use App\Models\Nf3PitchingRow;
use App\Services\Import\Nf3PitchingPasteParser;
use Illuminate\Http\Request;

class Nf3ImportController extends Controller
{
    public function index()
    {
        $groups = Nf3BattingRow::query()
            ->selectRaw('year, COALESCE(team_id, 0) as team_id, team_name, COUNT(*) as total_rows')
            ->groupBy('year', 'team_id', 'team_name')
            ->orderByDesc('year')
            ->orderBy('team_name')
            ->get();

        return view('admin.nf3.batting_index', [
            'groups' => $groups,
        ]);
    }

    public function show(int $year, ?int $teamId = null)
    {
        $query = Nf3BattingRow::query()->where('year', $year);
        if ($teamId) {
            $query->where('team_id', $teamId);
        } else {
            $query->whereNull('team_id');
        }

        $rows = $query->orderBy('section')->orderBy('row_index')->get();

        $teamName = optional($rows->first())->team_name;
        $team = $teamId ? Team::find($teamId) : null;

        return view('admin.nf3.batting_show', [
            'year' => $year,
            'team' => $team,
            'teamName' => $teamName,
            'rows' => $rows,
        ]);
    }

    public function showBattingPasteForm()
    {
        $teams = Team::orderBy('name')->get();

        return view('admin.nf3.batting_paste', [
            'teams' => $teams,
        ]);
    }

    public function importBattingPaste(Request $request, Nf3BattingPasteParser $parser)
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'team_name' => ['nullable', 'string', 'max:255'],
            'raw_text' => ['required', 'string'],
        ]);

        $year = (int) $data['year'];
        $teamId = $data['team_id'] ?? null;
        $teamName = $data['team_name'] ?? optional(Team::find($teamId))->name;

        $rows = $parser->parse($data['raw_text']);

        foreach ($rows as $row) {
            Nf3BattingRow::create([
                'year' => $year,
                'team_id' => $teamId,
                'team_name' => $teamName,
                'section' => $row['section'],
                'row_index' => $row['row_index'],
                'number' => $row['number'],
                'name' => $row['name'],
                'columns' => $row['columns'],
                'raw_line' => $row['raw_line'],
            ]);
        }

        return redirect()
            ->back()
            ->with('status', "インポート完了: {$year} {$teamName} - " . count($rows) . '件保存しました。');
    }

    public function pitchingIndex()
    {
        $groups = Nf3PitchingRow::query()
            ->selectRaw('year, COALESCE(team_id, 0) as team_id, team_name, COUNT(*) as total_rows')
            ->groupBy('year', 'team_id', 'team_name')
            ->orderByDesc('year')
            ->orderBy('team_name')
            ->get();

        return view('admin.nf3.pitching_index', [
            'groups' => $groups,
        ]);
    }

    public function pitchingShow(int $year, ?int $teamId = null)
    {
        $query = Nf3PitchingRow::query()->where('year', $year);
        if ($teamId) {
            $query->where('team_id', $teamId);
        } else {
            $query->whereNull('team_id');
        }

        $rows = $query->orderBy('row_index')->get();

        $teamName = optional($rows->first())->team_name;
        $team = $teamId ? Team::find($teamId) : null;

        return view('admin.nf3.pitching_show', [
            'year' => $year,
            'team' => $team,
            'teamName' => $teamName,
            'rows' => $rows,
        ]);
    }

    public function showPitchingPasteForm()
    {
        $teams = Team::orderBy('name')->get();

        return view('admin.nf3.pitching_paste', [
            'teams' => $teams,
        ]);
    }

    public function importPitchingPaste(Request $request, Nf3PitchingPasteParser $parser)
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'team_name' => ['nullable', 'string', 'max:255'],
            'raw_text' => ['required', 'string'],
        ]);

        $year = (int) $data['year'];
        $teamId = $data['team_id'] ?? null;
        $teamName = $data['team_name'] ?? optional(Team::find($teamId))->name;

        $rows = $parser->parse($data['raw_text']);

        foreach ($rows as $row) {
            Nf3PitchingRow::create([
                'year' => $year,
                'team_id' => $teamId,
                'team_name' => $teamName,
                'row_index' => $row['row_index'],
                'number' => $row['number'],
                'name' => $row['name'],
                'arm' => $row['arm'],
                'columns' => $row['columns'],
                'raw_line' => $row['raw_line'],
            ]);
        }

        return redirect()
            ->back()
            ->with('status', "投手成績インポート完了: {$year} {$teamName} - " . count($rows) . '件保存しました。');
    }
}


