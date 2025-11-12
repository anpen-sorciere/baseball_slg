<?php

namespace App\Services;

use App\Models\PlayerSeason;
use Illuminate\Support\Collection;

class AutoLineupService
{
    public function buildForTeamYear(int $teamId, int $year): array
    {
        $seasons = PlayerSeason::with('player')
            ->where('team_id', $teamId)
            ->where('year', $year)
            ->get();

        $batters = $this->selectBatters($seasons);
        $pitcher = $this->selectPitcher($seasons);
        $reliefResult = $this->selectRelievers($seasons, $pitcher);
        
        // 控え選手を選択（打順に含まれない選手）
        $benchBatters = $seasons->filter(function (PlayerSeason $season) use ($batters) {
            // 打順に含まれていない野手を控えとして選択
            return !$batters->contains('id', $season->id) 
                && (($season->batting_contact ?? 0) > 0 || ($season->batting_power ?? 0) > 0)
                && !in_array($season->role, ['starter', 'reliever', 'closer'], true)
                && ($season->pitcher_velocity ?? 0) === 0;
        })->take(5); // 控えは最大5人

        return [
            'batters' => $batters,
            'bench_batters' => $benchBatters,
            'pitcher' => $pitcher,
            'relievers' => $reliefResult['relievers'] ?? collect(),
            'closers' => $reliefResult['closers'] ?? collect(),
        ];
    }

    protected function selectBatters(Collection $seasons): Collection
    {
        $batters = $seasons->filter(function (PlayerSeason $season) {
            return ($season->batting_contact ?? 0) > 0 || ($season->batting_power ?? 0) > 0;
        })->map(function (PlayerSeason $season) {
            $season->lineup_score =
                ($season->batting_contact ?? 0) * 0.6 +
                ($season->batting_power ?? 0) * 0.4;
            return $season;
        })->sortByDesc('lineup_score')
        ->take(9)
        ->values()
        ->map(function (PlayerSeason $season, $index) {
            // 打順1～9番を自動設定
            $season->batting_order = $index + 1;
            return $season;
        });

        return $batters;
    }

    protected function selectPitcher(Collection $seasons): ?PlayerSeason
    {
        $pitcher = $seasons->filter(function (PlayerSeason $season) {
            return in_array($season->role, ['starter', 'reliever', 'closer'], true)
                || $season->pitcher_velocity > 0;
        })->map(function (PlayerSeason $season) {
            $season->pitch_score =
                ($season->pitcher_stamina ?? 0) * 0.4 +
                ($season->pitcher_velocity ?? 0) * 0.3 +
                ($season->pitcher_control ?? 0) * 0.3;
            return $season;
        })->sortByDesc('pitch_score')
        ->first();

        return $pitcher;
    }

    protected function selectRelievers(Collection $seasons, ?PlayerSeason $starter): array
    {
        // 中継ぎと抑えを分けて選択
        $relievers = collect();
        $closers = collect();
        
        $candidates = $seasons->filter(function (PlayerSeason $season) use ($starter) {
            if ($starter && $season->id === $starter->id) {
                return false;
            }

            return in_array($season->role, ['starter', 'reliever', 'closer'], true)
                || ($season->pitcher_velocity ?? 0) > 0;
        })->map(function (PlayerSeason $season) {
            $season->relief_score =
                ($season->pitcher_stamina ?? 0) * 0.3 +
                ($season->pitcher_velocity ?? 0) * 0.35 +
                ($season->pitcher_control ?? 0) * 0.35;
            return $season;
        })->sortByDesc('relief_score')
        ->values();

        // 抑えを選択（roleがcloserまたは最高スコアの1人）
        $closerCandidates = $candidates->filter(function (PlayerSeason $season) {
            return $season->role === 'closer';
        });
        
        if ($closerCandidates->isNotEmpty()) {
            $closers = $closerCandidates->take(1);
        } else {
            // 抑えがいない場合は、最高スコアの1人を抑えとして扱う
            $closers = $candidates->take(1);
        }

        // 中継ぎを選択（抑え以外）
        $relievers = $candidates->reject(function (PlayerSeason $season) use ($closers) {
            return $closers->contains('id', $season->id);
        })->take(2);

        return [
            'relievers' => $relievers,
            'closers' => $closers,
        ];
    }
}


