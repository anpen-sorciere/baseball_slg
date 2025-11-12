<?php

namespace App\Services;

use App\Models\Nf3BattingRow;
use App\Models\Nf3PitchingRow;
use App\Models\Player;
use App\Models\PlayerSeason;
use App\Models\Team;
use Illuminate\Support\Collection;

class PlayerSeasonBuilder
{
    protected int $minPaForFullWeight = 200;
    protected float $minIpForFullWeight = 50.0;
    protected int $minPaTwoWay = 50;
    protected float $minIpTwoWay = 30.0;
    protected float $ratingScale = 15.0;

    public function buildForYear(int $year, ?string $league = null): int
    {
        $battingRows = $this->fetchBattingRows($year, $league);
        $pitchingRows = $this->fetchPitchingRows($year, $league);

        $battingMetrics = $this->prepareBattingMetrics($battingRows, $league);
        $pitchingMetrics = $this->preparePitchingMetrics($pitchingRows, $league);

        $playersData = [];

        $this->applyBattingResults($playersData, $battingRows, $battingMetrics, $league);
        $this->applyPitchingResults($playersData, $pitchingRows, $pitchingMetrics, $league);

        return $this->persistPlayerSeasons($year, $playersData, $league);
    }

    protected function fetchBattingRows(int $year, ?string $league): Collection
    {
        return Nf3BattingRow::with('team')
            ->where('year', $year)
            ->when($league, function ($query) use ($league) {
                $query->where(function ($q) use ($league) {
                    $q->whereHas('team', function ($teamQuery) use ($league) {
                        $teamQuery->where('league', 'LIKE', '%' . $league . '%');
                    })->orWhere('team_name', 'LIKE', '%' . $league . '%');
                });
            })
            ->get();
    }

    protected function fetchPitchingRows(int $year, ?string $league): Collection
    {
        return Nf3PitchingRow::with('team')
            ->where('year', $year)
            ->when($league, function ($query) use ($league) {
                $query->where(function ($q) use ($league) {
                    $q->whereHas('team', function ($teamQuery) use ($league) {
                        $teamQuery->where('league', 'LIKE', '%' . $league . '%');
                    })->orWhere('team_name', 'LIKE', '%' . $league . '%');
                });
            })
            ->get();
    }

    protected function prepareBattingMetrics(Collection $rows, ?string $leagueFilter): array
    {
        $metrics = [];
        $grouped = [];

        foreach ($rows as $row) {
            $metric = $this->buildBattingMetric($row);
            $leagueKey = $this->determineLeagueKey($row->team, $leagueFilter) ?? 'unknown';
            $metric['league'] = $leagueKey;
            $metric['uniform_number'] = $row->number;
            $metrics[$row->id] = $metric;
            $grouped[$leagueKey][] = &$metrics[$row->id];
        }

        foreach ($grouped as &$items) {
            $this->appendZScores($items, [
                'avg',
                'k_rate',
                'iso',
                'hr_rate',
                'bb_rate',
                'obp_diff',
                'sb_attempt_rate',
                'sb_success_rate',
                'runs_per_game',
                'def_metric',
            ]);

            foreach ($items as &$metric) {
                $metric['ratings'] = $this->computeBattingRatings($metric);
            }
        }

        return $metrics;
    }

    protected function preparePitchingMetrics(Collection $rows, ?string $leagueFilter): array
    {
        $metrics = [];
        $grouped = [];

        foreach ($rows as $row) {
            $metric = $this->buildPitchingMetric($row);
            $leagueKey = $this->determineLeagueKey($row->team, $leagueFilter) ?? 'unknown';
            $metric['league'] = $leagueKey;
            $metric['uniform_number'] = $row->number;
            $metrics[$row->id] = $metric;
            $grouped[$leagueKey][] = &$metrics[$row->id];
        }

        foreach ($grouped as &$items) {
            $this->appendZScores($items, [
                'k9',
                'hr9',
                'bb9',
                'whip',
                'kbb',
                'era',
                'ip',
                'ip_per_game',
                'games_started',
            ]);

            foreach ($items as &$metric) {
                $metric['ratings'] = $this->computePitchingRatings($metric);
            }
        }

        return $metrics;
    }

    protected function buildBattingMetric(Nf3BattingRow $row): array
    {
        $pa = $this->toFloat($row->getStat('pa'));
        $ab = $this->toFloat($row->getStat('ab'));
        $hits = $this->toFloat($row->getStat('hits'));
        $doubles = $this->toFloat($row->getStat('doubles'));
        $triples = $this->toFloat($row->getStat('triples'));
        $hr = $this->toFloat($row->getStat('hr'));
        $bb = $this->toFloat($row->getStat('walks'));
        $so = $this->toFloat($row->getStat('strikeouts'));
        $sb = $this->toFloat($row->getStat('stolen_bases'));
        $cs = $this->toFloat($row->getStat('caught_stealing'));
        $errors = $this->toFloat($row->getStat('errors'));
        $games = (int) $this->toFloat($row->getStat('games'));
        $hbp = $this->toFloat($row->getStat('hbp'));
        $sf = $this->toFloat($row->getStat('sac_flies'));
        $runs = $this->toFloat($row->getStat('runs'));

        $avgValue = $this->toNullableFloat($row->getStat('avg'));
        $slgValue = $this->toNullableFloat($row->getStat('slg'));
        $obpValue = $this->toNullableFloat($row->getStat('obp'));

        $singles = max(0, $hits - $doubles - $triples - $hr);
        $totalBases = $singles + (2 * $doubles) + (3 * $triples) + (4 * $hr);

        if ($avgValue === null && $ab > 0) {
            $avgValue = $hits / $ab;
        }

        if ($slgValue === null && $ab > 0) {
            $slgValue = $totalBases / $ab;
        }

        if ($obpValue === null) {
            $denominator = $ab + $bb + $hbp + $sf;
            if ($denominator > 0) {
                $obpValue = ($hits + $bb + $hbp) / $denominator;
            }
        }

        $iso = ($slgValue !== null && $avgValue !== null) ? ($slgValue - $avgValue) : null;
        $hrRate = $pa > 0 ? ($hr / $pa) : null;
        $kRate = $pa > 0 ? ($so / $pa) : null;
        $bbRate = $pa > 0 ? ($bb / $pa) : null;
        $obpDiff = ($obpValue !== null && $avgValue !== null) ? ($obpValue - $avgValue) : null;
        $sbAttempts = $sb + $cs;
        $sbAttemptRate = $pa > 0 ? ($sbAttempts / $pa) : null;
        $sbSuccessRate = $sbAttempts > 0 ? ($sb / $sbAttempts) : null;
        $runsPerGame = $games > 0 ? ($runs / $games) : null;
        $defMetric = $games > 0 ? (0 - ($errors / $games)) : null;

        return [
            'pa' => $pa,
            'ab' => $ab,
            'hits' => $hits,
            'avg' => $avgValue,
            'iso' => $iso,
            'hr_rate' => $hrRate,
            'k_rate' => $kRate,
            'bb_rate' => $bbRate,
            'obp_diff' => $obpDiff,
            'sb_attempt_rate' => $sbAttemptRate,
            'sb_success_rate' => $sbSuccessRate,
            'runs_per_game' => $runsPerGame,
            'def_metric' => $defMetric,
            'errors' => $errors,
            'games' => $games,
            'sb_attempts' => $sbAttempts,
        ];
    }

    protected function buildPitchingMetric(Nf3PitchingRow $row): array
    {
        $ip = $this->parseInnings($row->getStat('innings'));
        $games = (int) $this->toFloat($row->getStat('games'));
        $gamesStarted = (int) $this->toFloat($row->getStat('starts'));
        $reliefGames = (int) $this->toFloat($row->getStat('relief'));
        $wins = (int) $this->toFloat($row->getStat('wins'));
        $losses = (int) $this->toFloat($row->getStat('losses'));
        $holds = (int) $this->toFloat($row->getStat('holds'));
        $saves = (int) $this->toFloat($row->getStat('saves'));
        $strikeouts = $this->toFloat($row->getStat('strikeouts'));
        $walks = $this->toFloat($row->getStat('walks'));
        $homeRuns = $this->toFloat($row->getStat('hr_allowed'));
        $hitsAllowed = $this->toFloat($row->getStat('hits_allowed'));
        $runs = $this->toFloat($row->getStat('runs'));
        $earnedRuns = $this->toFloat($row->getStat('er'));
        $era = $this->toNullableFloat($row->getStat('era'));
        $whipValue = $this->toNullableFloat($row->getStat('whip'));
        $hbp = $this->toFloat($row->getStat('hbp'));

        if ($whipValue === null && $ip > 0) {
            $whipValue = ($hitsAllowed + $walks + $hbp) / $ip;
        }

        $k9 = $ip > 0 ? ($strikeouts * 9 / $ip) : null;
        $bb9 = $ip > 0 ? ($walks * 9 / $ip) : null;
        $hr9 = $ip > 0 ? ($homeRuns * 9 / $ip) : null;
        $kbb = $walks > 0 ? ($strikeouts / $walks) : ($strikeouts > 0 ? $strikeouts : null);
        $ipPerGame = $games > 0 ? ($ip / $games) : null;

        return [
            'ip' => $ip,
            'games' => $games,
            'games_started' => $gamesStarted,
            'relief_games' => $reliefGames,
            'wins' => $wins,
            'losses' => $losses,
            'holds' => $holds,
            'saves' => $saves,
            'era' => $era,
            'whip' => $whipValue,
            'k9' => $k9,
            'bb9' => $bb9,
            'hr9' => $hr9,
            'kbb' => $kbb,
            'ip_per_game' => $ipPerGame,
            'strikeouts' => $strikeouts,
            'walks' => $walks,
            'home_runs' => $homeRuns,
            'hits_allowed' => $hitsAllowed,
            'runs' => $runs,
            'earned_runs' => $earnedRuns,
        ];
    }

    protected function appendZScores(array &$metrics, array $fields): void
    {
        $stats = [];
        foreach ($fields as $field) {
            $values = [];
            foreach ($metrics as $metric) {
                if (array_key_exists($field, $metric) && $metric[$field] !== null) {
                    $values[] = $metric[$field];
                }
            }
            $stats[$field] = $this->calculateMeanStd($values);
        }

        foreach ($metrics as &$metric) {
            foreach ($fields as $field) {
                $metric['z_' . $field] = $this->zScore($metric[$field] ?? null, $stats[$field]);
            }
        }
    }

    protected function computeBattingRatings(array $metric): array
    {
        $pa = $metric['pa'] ?? 0;
        $weight = $this->sampleWeight($pa, $this->minPaForFullWeight);

        $contactZ = 0.7 * ($metric['z_avg'] ?? 0) - 0.3 * ($metric['z_k_rate'] ?? 0);
        $powerZ = 0.7 * ($metric['z_iso'] ?? 0) + 0.3 * ($metric['z_hr_rate'] ?? 0);
        $eyeZ = 0.6 * ($metric['z_bb_rate'] ?? 0) + 0.4 * ($metric['z_obp_diff'] ?? 0) - 0.2 * ($metric['z_k_rate'] ?? 0);
        $speedZ = 0.5 * ($metric['z_sb_attempt_rate'] ?? 0) + 0.3 * ($metric['z_sb_success_rate'] ?? 0) + 0.2 * ($metric['z_runs_per_game'] ?? 0);
        $defZ = $metric['z_def_metric'] ?? 0;

        $contact = $this->applySampleWeight($this->ratingFromZ($contactZ), $weight);
        $power = $this->applySampleWeight($this->ratingFromZ($powerZ), $weight);
        $eye = $this->applySampleWeight($this->ratingFromZ($eyeZ), $weight);
        $speed = $this->applySampleWeight($this->ratingFromZ($speedZ), $weight);
        $defense = $this->ratingFromZ($defZ);

        $overall = (int) round(
            0.35 * $contact +
            0.35 * $power +
            0.15 * $eye +
            0.15 * $speed
        );

        return [
            'contact' => $this->clampRating($contact),
            'power' => $this->clampRating($power),
            'eye' => $this->clampRating($eye),
            'speed' => $this->clampRating($speed),
            'defense' => $this->clampRating($defense),
            'overall' => $this->clampRating($overall),
        ];
    }

    protected function computePitchingRatings(array $metric): array
    {
        $ip = $metric['ip'] ?? 0.0;
        $weight = $this->sampleWeight($ip, $this->minIpForFullWeight);

        $velocityZ = 0.8 * ($metric['z_k9'] ?? 0) - 0.2 * ($metric['z_hr9'] ?? 0);
        $controlZ = -0.5 * ($metric['z_bb9'] ?? 0) - 0.3 * ($metric['z_whip'] ?? 0) + 0.2 * ($metric['z_kbb'] ?? 0);
        $movementZ = -0.6 * ($metric['z_hr9'] ?? 0) - 0.4 * ($metric['z_era'] ?? 0);
        $staminaZ = 0.4 * ($metric['z_ip'] ?? 0) + 0.4 * ($metric['z_ip_per_game'] ?? 0) + 0.2 * ($metric['z_games_started'] ?? 0);

        $velocity = $this->applySampleWeight($this->ratingFromZ($velocityZ), $weight);
        $control = $this->applySampleWeight($this->ratingFromZ($controlZ), $weight);
        $movement = $this->applySampleWeight($this->ratingFromZ($movementZ), $weight);
        $stamina = $this->applySampleWeight($this->ratingFromZ($staminaZ), $weight);

        $overall = (int) round(
            0.3 * $velocity +
            0.3 * $control +
            0.2 * $movement +
            0.2 * $stamina
        );

        return [
            'velocity' => $this->clampRating($velocity),
            'control' => $this->clampRating($control),
            'movement' => $this->clampRating($movement),
            'stamina' => $this->clampRating($stamina),
            'overall' => $this->clampRating($overall),
        ];
    }

    protected function applyBattingResults(array &$playersData, Collection $rows, array $metrics, ?string $leagueFilter): void
    {
        foreach ($rows as $row) {
            $teamId = $this->resolveTeamId($row->team_id, $row->team_name);
            $playerId = $this->resolveOrCreatePlayer($row->name, $teamId, $row->getStat('bats'), null);
            if (!$playerId) {
                continue;
            }

            $metric = $metrics[$row->id] ?? null;
            if (!$metric || empty($metric['ratings'])) {
                continue;
            }

            $leagueKey = $this->determineLeagueKey($row->team, $leagueFilter) ?? ($leagueFilter ?? null);

            $data =& $playersData[$playerId];
            $data['player_name'] = $row->name;
            if ($teamId && empty($data['team_id'])) {
                $data['team_id'] = $teamId;
            }
            if ($leagueKey && empty($data['league'])) {
                $data['league'] = $leagueKey;
            }

            $data['batting'] = [
                'ratings' => $metric['ratings'],
                'pa' => $metric['pa'],
                'games' => $metric['games'],
                'nf3_row_id' => $row->id,
                'uniform_number' => $metric['uniform_number'] ?? null,
                'metrics' => $metric,
            ];
        }
    }

    protected function applyPitchingResults(array &$playersData, Collection $rows, array $metrics, ?string $leagueFilter): void
    {
        foreach ($rows as $row) {
            $teamId = $this->resolveTeamId($row->team_id, $row->team_name);
            $playerId = $this->resolveOrCreatePlayer($row->name, $teamId, null, $row->getStat('throws'));
            if (!$playerId) {
                continue;
            }

            $metric = $metrics[$row->id] ?? null;
            if (!$metric || empty($metric['ratings'])) {
                continue;
            }

            $leagueKey = $this->determineLeagueKey($row->team, $leagueFilter) ?? ($leagueFilter ?? null);

            $data =& $playersData[$playerId];
            $data['player_name'] = $row->name;
            if ($teamId && empty($data['team_id'])) {
                $data['team_id'] = $teamId;
            }
            if ($leagueKey && empty($data['league'])) {
                $data['league'] = $leagueKey;
            }

            $data['pitching'] = [
                'ratings' => $metric['ratings'],
                'ip' => $metric['ip'],
                'games' => $metric['games'],
                'games_started' => $metric['games_started'],
                'relief_games' => $metric['relief_games'],
                'saves' => $metric['saves'],
                'nf3_row_id' => $row->id,
                'uniform_number' => $metric['uniform_number'] ?? null,
                'metrics' => $metric,
            ];
        }
    }

    protected function persistPlayerSeasons(int $year, array $playersData, ?string $leagueFilter): int
    {
        $count = 0;

        foreach ($playersData as $playerId => $data) {
            if (empty($data['batting']) && empty($data['pitching'])) {
                continue;
            }

            $attributes = [];

            if (!empty($data['team_id'])) {
                $attributes['team_id'] = $data['team_id'];
            }

            $leagueValue = $data['league'] ?? $leagueFilter;
            if ($leagueValue !== null) {
                $attributes['league'] = $leagueValue;
            }

            $uniformNumber = $data['batting']['uniform_number'] ?? $data['pitching']['uniform_number'] ?? null;
            if ($uniformNumber !== null) {
                $attributes['uniform_number'] = $uniformNumber;
            }

            [$overall, $isTwoWay] = $this->determineOverallAndTwoWay($data);
            $role = $this->determineRole($data);

            // is_pitcherフラグを設定（投手の役割を持つ、または投手能力がある場合）
            $isPitcher = in_array($role, ['starter', 'reliever', 'closer'], true)
                || isset($data['pitching']);

            $attributes['overall_rating'] = $overall;
            $attributes['is_two_way'] = $isTwoWay;
            $attributes['role'] = $role;
            $attributes['is_pitcher'] = $isPitcher;

            // 投手の場合はposition_1に役割を設定（先発、中継ぎ、抑え）
            if ($isPitcher && in_array($role, ['starter', 'reliever', 'closer'], true)) {
                $positionMap = [
                    'starter' => '先発',
                    'reliever' => '中継ぎ',
                    'closer' => '抑え',
                ];
                $attributes['position_1'] = $positionMap[$role] ?? null;
            }

            if (isset($data['batting'])) {
                $bat = $data['batting']['ratings'];
                $attributes['batting_contact'] = $bat['contact'];
                $attributes['batting_power'] = $bat['power'];
                $attributes['batting_eye'] = $bat['eye'];
                $attributes['running_speed'] = $bat['speed'];
                $attributes['defense'] = $bat['defense'];
                $attributes['nf3_batting_row_id'] = $data['batting']['nf3_row_id'];
            } else {
                $attributes['batting_contact'] = null;
                $attributes['batting_power'] = null;
                $attributes['batting_eye'] = null;
                $attributes['running_speed'] = null;
                $attributes['defense'] = null;
                $attributes['nf3_batting_row_id'] = null;
            }

            if (isset($data['pitching'])) {
                $pit = $data['pitching']['ratings'];
                $attributes['pitcher_velocity'] = $pit['velocity'];
                $attributes['pitcher_control'] = $pit['control'];
                $attributes['pitcher_movement'] = $pit['movement'];
                $attributes['pitcher_stamina'] = $pit['stamina'];
                $attributes['nf3_pitching_row_id'] = $data['pitching']['nf3_row_id'];
            } else {
                // 投手データがない場合は0を設定（NOT NULL制約のため）
                $attributes['pitcher_velocity'] = 0;
                $attributes['pitcher_control'] = 0;
                $attributes['pitcher_movement'] = 0;
                $attributes['pitcher_stamina'] = 0;
                $attributes['nf3_pitching_row_id'] = null;
            }

            PlayerSeason::updateOrCreate(
                [
                    'player_id' => $playerId,
                    'year' => $year,
                ],
                $attributes
            );

            $count++;
        }

        return $count;
    }

    protected function determineOverallAndTwoWay(array $data): array
    {
        $hasBat = isset($data['batting']);
        $hasPit = isset($data['pitching']);

        $pa = $hasBat ? ($data['batting']['pa'] ?? 0) : 0;
        $ip = $hasPit ? ($data['pitching']['ip'] ?? 0.0) : 0.0;

        $batOverall = $hasBat ? ($data['batting']['ratings']['overall'] ?? 50) : null;
        $pitOverall = $hasPit ? ($data['pitching']['ratings']['overall'] ?? 50) : null;

        $hasBatThreshold = $pa >= $this->minPaTwoWay;
        $hasPitThreshold = $ip >= $this->minIpTwoWay;
        $isTwoWay = $hasBatThreshold && $hasPitThreshold;

        if ($hasPit && !$hasBat) {
            return [$this->clampRating($pitOverall ?? 50), false];
        }

        if ($hasBat && !$hasPit) {
            return [$this->clampRating($batOverall ?? 50), false];
        }

        if ($hasBat && $hasPit) {
            $role = $this->determineRole($data);
            if (in_array($role, ['starter', 'reliever', 'closer'], true)) {
                $overall = round(0.7 * ($pitOverall ?? 50) + 0.3 * ($batOverall ?? 50));
            } else {
                $overall = round(0.7 * ($batOverall ?? 50) + 0.3 * ($pitOverall ?? 50));
            }

            return [$this->clampRating((int) $overall), $isTwoWay];
        }

        return [50, false];
    }

    protected function determineRole(array $data): string
    {
        if (isset($data['pitching'])) {
            $metrics = $data['pitching']['metrics'];
            $ip = $metrics['ip'] ?? 0;
            $games = max(1, $metrics['games'] ?? 0);
            $reliefGames = $metrics['relief_games'] ?? 0;
            $saves = $metrics['saves'] ?? 0;

            if ($ip >= $this->minIpTwoWay) {
                if ($saves >= 10) {
                    return 'closer';
                }
                if ($reliefGames >= ($games * 0.5)) {
                    return 'reliever';
                }

                return 'starter';
            }
        }

        if (isset($data['batting'])) {
            $pa = $data['batting']['pa'] ?? 0;

            if ($pa >= 300) {
                return 'regular';
            }

            if ($pa >= 50) {
                return 'part_time';
            }

            return 'bench';
        }

        return 'bench';
    }

    protected function determineLeagueKey(?Team $team, ?string $leagueFilter): ?string
    {
        if ($leagueFilter) {
            return $leagueFilter;
        }

        return $team?->league;
    }

    protected function resolveOrCreatePlayer(?string $name, ?int $teamId, ?string $bats, ?string $throws): ?int
    {
        if (!$name) {
            return null;
        }

        $player = null;

        if ($teamId) {
            $player = Player::where('name', $name)
                ->where('team_id', $teamId)
                ->first();
        }

        if (!$player) {
            $player = Player::where('name', $name)
                ->whereNull('team_id')
                ->first();
        }

        if (!$player) {
            $player = new Player([
                'team_id' => $teamId,
                'name' => $name,
                'handed_bat' => $this->normalizeBatHand($bats),
                'handed_throw' => $this->normalizeThrowHand($throws),
            ]);

            // primary_positionカラムは削除済み（position_1, position_2, position_3に変更）
            // 必要に応じてposition_1などを設定可能だが、ビルド時点では未設定のまま

            $player->save();
        } else {
            $updated = false;

            if ($teamId && $player->team_id !== $teamId) {
                $player->team_id = $teamId;
                $updated = true;
            }

            if (!$player->handed_bat && $bats) {
                $player->handed_bat = $this->normalizeBatHand($bats);
                $updated = true;
            }

            if (!$player->handed_throw && $throws) {
                $player->handed_throw = $this->normalizeThrowHand($throws);
                $updated = true;
            }

            if ($updated) {
                $player->save();
            }
        }

        return $player->id;
    }

    protected function resolveTeamId(?int $teamId, ?string $teamName): ?int
    {
        if ($teamId) {
            return $teamId;
        }

        if ($teamName) {
            $team = Team::where('name', $teamName)->first();
            if ($team) {
                return $team->id;
            }
        }

        return null;
    }

    protected function sampleWeight(float $value, float $threshold): float
    {
        if ($threshold <= 0) {
            return 1.0;
        }

        return max(0.0, min(1.0, $value / $threshold));
    }

    protected function ratingFromZ(float $z): int
    {
        $rating = (int) round(50 + $z * $this->ratingScale);

        return $this->clampRating($rating);
    }

    protected function applySampleWeight(int $rating, float $weight): int
    {
        $adjusted = 50 + ($rating - 50) * $weight;

        return $this->clampRating((int) round($adjusted));
    }

    protected function clampRating(int $rating): int
    {
        return max(0, min(100, $rating));
    }

    protected function calculateMeanStd(array $values): array
    {
        if (empty($values)) {
            return ['mean' => 0.0, 'std' => 0.0];
        }

        $count = count($values);
        $mean = array_sum($values) / $count;

        if ($count === 1) {
            return ['mean' => $mean, 'std' => 0.0];
        }

        $variance = 0.0;
        foreach ($values as $value) {
            $variance += ($value - $mean) ** 2;
        }

        $variance /= $count;

        return ['mean' => $mean, 'std' => sqrt($variance)];
    }

    protected function zScore(?float $value, array $stats): float
    {
        if ($value === null) {
            return 0.0;
        }

        $mean = $stats['mean'] ?? 0.0;
        $std = $stats['std'] ?? 0.0;

        if (abs($std) < 1e-6) {
            return 0.0;
        }

        return ($value - $mean) / $std;
    }

    protected function toFloat($value): float
    {
        $normalized = $this->normalizeNumericString($value);
        if ($normalized === '' || $normalized === null) {
            return 0.0;
        }

        return (float) $normalized;
    }

    protected function toNullableFloat($value): ?float
    {
        $normalized = $this->normalizeNumericString($value);
        if ($normalized === '' || $normalized === null) {
            return null;
        }

        return (float) $normalized;
    }

    protected function normalizeNumericString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '' || $value === '-' || strcasecmp($value, 'NA') === 0) {
            return '';
        }

        $value = str_replace(',', '', $value);

        if (str_contains($value, '%')) {
            $value = str_replace('%', '', $value);
        }

        return $value;
    }

    protected function parseInnings($value): float
    {
        $value = $this->normalizeNumericString($value);
        if ($value === '' || $value === null) {
            return 0.0;
        }

        if (!str_contains($value, '.')) {
            return (float) $value;
        }

        [$whole, $fraction] = explode('.', $value, 2);
        $whole = (int) $whole;
        $fraction = (int) $fraction;

        $partial = 0.0;
        if ($fraction === 1) {
            $partial = 1 / 3;
        } elseif ($fraction === 2) {
            $partial = 2 / 3;
        } else {
            $partial = (float) ('0.' . $fraction);
        }

        return $whole + $partial;
    }

    protected function normalizeBatHand(?string $value): ?string
    {
        $value = $this->normalizeHandString($value);
        if ($value === null) {
            return null;
        }

        return match ($value) {
            'R', '右', '右打', 'r' => '右',
            'L', '左', '左打', 'l' => '左',
            'S', '両', '両打', 'スイッチ', 's' => '両',
            default => null,
        };
    }

    protected function normalizeThrowHand(?string $value): ?string
    {
        $value = $this->normalizeHandString($value);
        if ($value === null) {
            return null;
        }

        return match ($value) {
            'R', '右', '右投', 'r' => '右',
            'L', '左', '左投', 'l' => '左',
            default => null,
        };
    }

    protected function normalizeHandString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $value = str_replace([' ', '　'], '', $value);

        return $value;
    }
}


