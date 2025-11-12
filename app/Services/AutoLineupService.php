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
        $relievers = $this->selectRelievers($seasons, $pitcher);

        return [
            'batters' => $batters,
            'pitcher' => $pitcher,
            'relievers' => $relievers,
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
        ->values();

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

    protected function selectRelievers(Collection $seasons, ?PlayerSeason $starter): Collection
    {
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
        ->take(2)
        ->values();

        return $candidates;
    }
}


