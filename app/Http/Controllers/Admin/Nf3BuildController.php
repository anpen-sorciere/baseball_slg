<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nf3BattingRow;
use App\Models\Nf3PitchingRow;
use App\Services\PlayerSeasonBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class Nf3BuildController extends Controller
{
    public function index()
    {
        // nf3データから利用可能な年度を取得（打撃データと投球データの両方から）
        $battingYears = Nf3BattingRow::select('year')
            ->distinct()
            ->pluck('year')
            ->toArray();
        
        $pitchingYears = Nf3PitchingRow::select('year')
            ->distinct()
            ->pluck('year')
            ->toArray();

        // 両方の年度をマージして重複を除去、降順でソート
        $years = array_unique(array_merge($battingYears, $pitchingYears));
        rsort($years);

        // 年度が存在しない場合は2025をデフォルトとして表示
        if (empty($years)) {
            $years = [2025];
        }

        return view('admin.nf3.build', [
            'years' => $years,
            'defaultYear' => $years[0] ?? 2025,
        ]);
    }

    public function build(Request $request, PlayerSeasonBuilder $builder)
    {
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'league' => 'nullable|string|max:50',
        ]);

        $year = (int) $request->input('year');
        $league = $request->input('league') ?: null;

        try {
            Log::info("Building player seasons for year: {$year}, league: " . ($league ?? 'all'));

            $processed = $builder->buildForYear($year, $league);

            return redirect()
                ->route('admin.nf3.build.index')
                ->with('success', "選手能力値のビルドが完了しました。{$processed}件の選手データを処理しました。");
        } catch (\Throwable $e) {
            Log::error('Error building player seasons', [
                'year' => $year,
                'league' => $league,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('admin.nf3.build.index')
                ->withInput()
                ->withErrors(['error' => 'ビルド処理中にエラーが発生しました: ' . $e->getMessage()]);
        }
    }
}

