<?php

namespace App\Support;

use Illuminate\Support\Collection;

trait BuildsTeamContext
{
    protected function buildTeamContext(string $key, string $teamName, array $lineup, array $strategy = []): array
    {
        $batters = $lineup['batters'] ?? collect();
        if (!$batters instanceof Collection) {
            $batters = collect($batters);
        }

        $pitcher = $lineup['pitcher'] ?? null;
        $relievers = $lineup['relievers'] ?? collect();
        if (!$relievers instanceof Collection) {
            $relievers = collect($relievers);
        }

        $seasons = $batters->values();
        if ($pitcher) {
            $seasons = $seasons->concat([$pitcher]);
        }
        if ($relievers->isNotEmpty()) {
            $seasons = $seasons->concat($relievers);
        }
        $seasons = $seasons->filter();

        $ratings = $this->calculateRatingsFromSeasons($seasons);

        return [
            'key' => $key,
            'name' => $teamName,
            'ratings' => $ratings,
            'strategy' => array_merge([
                'offense_style' => 'balanced',
                'pitching_style' => 'balanced',
                'defense_style' => 'balanced',
            ], $strategy),
            'lineup' => [
                'batters' => $batters,
                'pitcher' => $pitcher,
                'relievers' => $relievers,
            ],
        ];
    }

    protected function calculateRatingsFromSeasons(Collection $seasons): array
    {
        if ($seasons->isEmpty()) {
            return [
                'offense' => 50,
                'power' => 50,
                'eye' => 50,
                'speed' => 50,
                'defense' => 50,
                'pitch' => 50,
            ];
        }

        $count = max(1, $seasons->count());

        $sumContact = $seasons->sum('batting_contact');
        $sumPower = $seasons->sum('batting_power');
        $sumEye = $seasons->sum('batting_eye');
        $sumSpeed = $seasons->sum('running_speed');
        $sumDefense = $seasons->sum('defense');
        $sumPitchCtl = $seasons->sum('pitcher_control');
        $sumPitchMov = $seasons->sum('pitcher_movement');
        $sumPitchVel = $seasons->sum('pitcher_velocity');

        $offense = ($sumContact + $sumPower) / max(1, 2 * $count);
        $power = $sumPower / $count;
        $eye = $sumEye / $count;
        $speed = $sumSpeed / $count;
        $defense = $sumDefense / $count;
        $pitch = ($sumPitchCtl + $sumPitchMov + $sumPitchVel) / max(1, 3 * $count);

        return [
            'offense' => $offense,
            'power' => $power,
            'eye' => $eye,
            'speed' => $speed,
            'defense' => $defense,
            'pitch' => $pitch,
        ];
    }
}

