<?php

namespace App\Console\Commands;

use App\Services\PlayerSeasonBuilder;
use Illuminate\Console\Command;

class BuildPlayerSeasonsFromNf3 extends Command
{
    protected $signature = 'nf3:build-player-seasons {year : Target year (e.g. 2024)} {--league= : League filter (e.g. セ, パ, NPB)}';

    protected $description = 'Build player seasons from nf3 batting/pitching data';

    public function handle(PlayerSeasonBuilder $builder): int
    {
        $year = (int) $this->argument('year');
        $league = $this->option('league') ?: null;

        $this->info(sprintf(
            'Building player seasons for %d%s',
            $year,
            $league ? " (league: {$league})" : ''
        ));

        $processed = $builder->buildForYear($year, $league);

        $this->info(sprintf('Player seasons build completed. %d player(s) updated.', $processed));

        return self::SUCCESS;
    }
}


