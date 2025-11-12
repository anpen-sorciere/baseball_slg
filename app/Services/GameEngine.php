<?php

namespace App\Services;

use Illuminate\Support\Arr;

class GameEngine
{
    protected const STRATEGY_KEYS = [
        'offense_style',
        'pitching_style',
        'defense_style',
    ];

    protected const DEFAULT_STRATEGY = [
        'offense_style' => 'balanced',
        'pitching_style' => 'balanced',
        'defense_style' => 'balanced',
    ];

    /**
     * NPB同士の試合シミュレーション。従来の挙動を保ちつつ戦略パラメータを受け取れる。
     *
     * @param Team $teamA
     * @param Team $teamB
     * @param array{team_a?: array, team_b?: array} $strategyParams
     */
    public function simulateGame(array $teamAContext, array $teamBContext): array
    {
        return $this->runNineInningSimulation($teamAContext, $teamBContext);
    }

    /**
     * 実際の9回シミュレーションを実行。
     */
    public function runNineInningSimulation(array $teamAContext, array $teamBContext): array
    {
        $innings = [];
        $log = [];
        $playLog = [];

        $lineups = [
            'teamA' => $this->formatLineup($teamAContext['lineup'] ?? null),
            'teamB' => $this->formatLineup($teamBContext['lineup'] ?? null),
        ];

        $battingState = [
            'teamA' => [
                'index' => 0,
                'lineup' => array_values($lineups['teamA']['batters'] ?? []),
            ],
            'teamB' => [
                'index' => 0,
                'lineup' => array_values($lineups['teamB']['batters'] ?? []),
            ],
        ];

        $scoreA = 0;
        $scoreB = 0;

        $defenseOuts = [
            'teamA' => 0,
            'teamB' => 0,
        ];

        for ($i = 1; $i <= 9; $i++) {
            $scoreABefore = $scoreA;
            $topWalkoff = false;
            $this->simulateHalfInning(
                'teamA',
                'teamB',
                $teamAContext,
                $teamBContext,
                $lineups,
                $battingState,
                $i,
                true,
                $scoreA,
                $scoreB,
                $defenseOuts['teamB'],
                $log,
                $playLog,
                $topWalkoff
            );
            $runsTop = $scoreA - $scoreABefore;

            $runsBottom = null;
            $bottomPlayed = true;

            if ($i < 9 || $scoreA >= $scoreB) {
                $scoreBBefore = $scoreB;
                $walkoff = false;
                $this->simulateHalfInning(
                    'teamB',
                    'teamA',
                    $teamBContext,
                    $teamAContext,
                    $lineups,
                    $battingState,
                    $i,
                    false,
                    $scoreA,
                    $scoreB,
                $defenseOuts['teamA'],
                    $log,
                    $playLog,
                    $walkoff
                );
                $runsBottom = $scoreB - $scoreBBefore;
                if ($walkoff) {
                    $log[] = "ゲームセット！サヨナラで{$teamBContext['name']}の勝利！";
                    $innings[] = [
                        'inning' => $i,
                        'teamA' => $runsTop,
                        'teamB' => $runsBottom,
                    ];
                    break;
                }
                if ($i === 9 && $runsBottom !== null) {
                    if ($scoreB > $scoreA) {
                        $log[] = "ゲームセット！{$teamBContext['name']}の勝利！";
                    } elseif ($scoreB === $scoreA) {
                        $log[] = "ゲームセット！同点で試合終了。";
                    } else {
                        $log[] = "ゲームセット！{$teamAContext['name']}の勝利！";
                    }
                }
            } else {
                $bottomPlayed = false;
                $log[] = "ゲームセット！{$teamBContext['name']}の勝利！";
                $runsBottom = null;
                $innings[] = [
                    'inning' => $i,
                    'teamA' => $runsTop,
                    'teamB' => null,
                ];
                break;
            }

            $innings[] = [
                'inning' => $i,
                'teamA' => $runsTop,
                'teamB' => $bottomPlayed ? $runsBottom : null,
            ];
        }

        $battingStats = [
            'teamA' => $this->generateBattingStats($lineups['teamA']['batters'] ?? [], $scoreA),
            'teamB' => $this->generateBattingStats($lineups['teamB']['batters'] ?? [], $scoreB),
        ];

        $pitchingStats = [
            'teamA' => $this->generatePitchingStats($teamAContext['name'], $lineups['teamA'], $scoreB, $defenseOuts['teamA']),
            'teamB' => $this->generatePitchingStats($teamBContext['name'], $lineups['teamB'], $scoreA, $defenseOuts['teamB']),
        ];

        $mvp = $this->determineMvp($battingStats, $pitchingStats, $lineups);

        return [
            'score' => [
                'teamA' => $scoreA,
                'teamB' => $scoreB,
            ],
            'teamA_score' => $scoreA,
            'teamB_score' => $scoreB,
            'innings' => $innings,
            'lineups' => $lineups,
            'batting_stats' => $battingStats,
            'pitching_stats' => $pitchingStats,
            'log' => array_slice($log, 0, 20),
            'play_by_play' => $playLog,
            'mvp' => $mvp,
            'teamA_ratings' => $teamAContext['ratings'],
            'teamB_ratings' => $teamBContext['ratings'],
            'strategies' => [
                $teamAContext['key'] => $teamAContext['strategy'],
                $teamBContext['key'] => $teamBContext['strategy'],
            ],
        ];
    }

    /**
     * チーム全体の能力値をざっくり集計。
     */
    protected function calculateTeamRatings($seasons): array
    {
        if ($seasons->count() === 0) {
            return [
                'offense' => 50,
                'power' => 50,
                'eye' => 50,
                'speed' => 50,
                'defense' => 50,
                'pitch' => 50,
            ];
        }

        $count = $seasons->count();

        $sumContact = $seasons->sum('batting_contact');
        $sumPower = $seasons->sum('batting_power');
        $sumEye = $seasons->sum('batting_eye');
        $sumSpeed = $seasons->sum('running_speed');
        $sumDefense = $seasons->sum('defense');
        $sumPitchCtl = $seasons->sum('pitcher_control');
        $sumPitchMov = $seasons->sum('pitcher_movement');
        $sumPitchVel = $seasons->sum('pitcher_velocity');

        $offense = ($sumContact + $sumPower) / max(1, 2 * $count);
        $power = $sumPower / max(1, $count);
        $eye = $sumEye / max(1, $count);
        $speed = $sumSpeed / max(1, $count);
        $defense = $sumDefense / max(1, $count);
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

    /**
     * 1イニング（攻撃側 vs 守備側）をシミュレーション。
     */
    protected function simulateHalfInning(
        string $offenseKey,
        string $defenseKey,
        array $offenseContext,
        array $defenseContext,
        array &$lineups,
        array &$battingState,
        int $inning,
        bool $isTop,
        int &$scoreA,
        int &$scoreB,
        int &$defenseOuts,
        array &$summaryLog,
        array &$playLog,
        bool &$walkoff = false
    ): int {
        $outs = 0;
        $bases = [false, false, false];
        $walkoff = false;

        $offenseRatings = $offenseContext['ratings'];
        $defenseRatings = $defenseContext['ratings'];

        $offenseName = $offenseContext['name'];
        $defenseName = $defenseContext['name'];
        $pitcher = $lineups[$defenseKey]['pitcher'] ?? ['name' => $defenseName];

        $halfLabel = $isTop ? '表' : '裏';

        $runsBefore = $offenseKey === 'teamA' ? $scoreA : $scoreB;

        $hitProb = $this->clamp(0.12 + ($offenseRatings['offense'] - $defenseRatings['pitch']) / 900, 0.03, 0.30);
        $hrProb = $this->clamp(0.02 + ($offenseRatings['power'] - $defenseRatings['pitch']) / 1100, 0.005, 0.12);
        $walkProb = $this->clamp(0.06 + ($offenseRatings['eye'] - $defenseRatings['pitch']) / 1000, 0.01, 0.18);

        while ($outs < 3) {
            $batter = $this->getNextBatter($offenseKey, $battingState);
            $batterName = $batter['name'] ?? '無名選手';
            $pitcherName = $pitcher['name'] ?? "{$defenseName}投手";

            $roll = mt_rand() / mt_getrandmax();
            $resultType = 'out';
            $runsScored = 0;
            $rbi = 0;

            $prevBases = $bases;

            if ($roll < $hrProb) {
                $runsScored = 1;
                foreach ($prevBases as $occupied) {
                    if ($occupied) {
                        $runsScored++;
                    }
                }
                $bases = [false, false, false];
                $resultType = 'homerun';
            } elseif ($roll < $hrProb + $hitProb) {
                $hitRoll = mt_rand(1, 100);
                if ($hitRoll <= 10) {
                    $resultType = 'triple';
                    $runsScored += $prevBases[2] ? 1 : 0;
                    $runsScored += $prevBases[1] ? 1 : 0;
                    $runsScored += $prevBases[0] ? 1 : 0;
                    $bases = [false, false, true];
                } elseif ($hitRoll <= 35) {
                    $resultType = 'double';
                    $runsScored += $prevBases[2] ? 1 : 0;
                    $runsScored += $prevBases[1] ? 1 : 0;
                    $bases = [
                        false,
                        true,
                        $prevBases[0],
                    ];
                } else {
                    $resultType = 'single';
                    $runsScored += $prevBases[2] ? 1 : 0;
                    $bases = [
                        true,
                        $prevBases[0],
                        $prevBases[1],
                    ];
                }
            } elseif ($roll < $hrProb + $hitProb + $walkProb) {
                $resultType = 'walk';
                if ($prevBases[0] && $prevBases[1] && $prevBases[2]) {
                    $runsScored++;
                }
                $bases = $this->advanceOnWalk($prevBases);
            } else {
                $strikeoutChance = $this->clamp(0.18 + ($defenseRatings['pitch'] - $offenseRatings['offense']) / 900, 0.08, 0.45);
                $resultType = (mt_rand() / mt_getrandmax() < $strikeoutChance) ? 'strikeout' : 'out';
                $outs++;
            }

            if ($runsScored > 0) {
                $rbi = $runsScored;
                if ($offenseKey === 'teamA') {
                    $scoreA += $runsScored;
                } else {
                    $scoreB += $runsScored;
                }
            }

            $playLog[] = [
                'inning' => $inning,
                'half' => $isTop ? 'top' : 'bottom',
                'batter_team' => $offenseKey === 'teamA' ? 'A' : 'B',
                'batter_name' => $batterName,
                'pitcher_name' => $pitcherName,
                'result_type' => $resultType,
                'rbi' => $rbi,
                'runs_scored' => $runsScored,
                'score_after' => [
                    'teamA' => $scoreA,
                    'teamB' => $scoreB,
                ],
                'description' => $this->buildDescription(
                    $inning,
                    $isTop,
                    $batterName,
                    $pitcherName,
                    $resultType,
                    $runsScored,
                    $rbi,
                    $scoreA,
                    $scoreB
                ),
            ];

            if (!$isTop && $inning >= 9 && $scoreB > $scoreA) {
                $walkoff = true;
                break;
            }

            if ($outs >= 3) {
                break;
            }
        }

        $defenseOuts += $outs;

        $runsScoredHalf = ($offenseKey === 'teamA' ? $scoreA : $scoreB) - $runsBefore;

        if ($walkoff) {
            $summaryLog[] = "{$offenseName}がサヨナラ勝ちを決めた！";
        } elseif ($runsScoredHalf > 0) {
            $summaryLog[] = "{$inning}回{$halfLabel}終了：{$offenseName}が{$runsScoredHalf}点を奪取。";
        } else {
            $summaryLog[] = "{$inning}回{$halfLabel}終了：{$offenseName}は無得点。";
        }

        return $runsScoredHalf;
    }

    protected function formatLineup(?array $lineup): array
    {
        if (!$lineup) {
            return [
                'batters' => [],
                'pitcher' => null,
                'relievers' => [],
            ];
        }

        $batters = [];
        $battersCollection = $lineup['batters'] ?? collect();
        $battersCollection = $battersCollection instanceof \Illuminate\Support\Collection
            ? $battersCollection->values()
            : collect($battersCollection)->values();

        foreach ($battersCollection as $index => $season) {
            if (!$season) {
                continue;
            }
            $player = $season->player ?? null;
            $batters[] = [
                'order' => $index + 1,
                'name' => ($player && isset($player->name)) ? $player->name : '不明',
                'position' => $season->position_main ?? (($player && isset($player->primary_position)) ? $player->primary_position : '--'),
                'contact' => (int) ($season->batting_contact ?? 0),
                'power' => (int) ($season->batting_power ?? 0),
                'eye' => (int) ($season->batting_eye ?? 0),
                'speed' => (int) ($season->running_speed ?? 0),
                'defense' => (int) ($season->defense ?? 0),
            ];
        }

        $pitcherSeason = $lineup['pitcher'] ?? null;
        $pitcherPlayer = $pitcherSeason?->player;
        $pitcher = $pitcherSeason ? [
            'name' => ($pitcherPlayer && isset($pitcherPlayer->name)) ? $pitcherPlayer->name : '不明',
            'position' => $pitcherSeason->position_main ?? 'P',
            'stamina' => (int) ($pitcherSeason->pitcher_stamina ?? 0),
            'control' => (int) ($pitcherSeason->pitcher_control ?? 0),
            'velocity' => (int) ($pitcherSeason->pitcher_velocity ?? 0),
            'movement' => (int) ($pitcherSeason->pitcher_movement ?? 0),
        ] : null;

        $relievers = [];
        $relieversCollection = $lineup['relievers'] ?? collect();
        $relieversCollection = $relieversCollection instanceof \Illuminate\Support\Collection
            ? $relieversCollection->values()
            : collect($relieversCollection)->values();

        foreach ($relieversCollection as $season) {
            if (!$season) {
                continue;
            }
            $player = $season->player ?? null;
            $relievers[] = [
                'name' => ($player && isset($player->name)) ? $player->name : '不明',
                'stamina' => (int) ($season->pitcher_stamina ?? 0),
                'control' => (int) ($season->pitcher_control ?? 0),
                'velocity' => (int) ($season->pitcher_velocity ?? 0),
                'movement' => (int) ($season->pitcher_movement ?? 0),
            ];
        }

        return [
            'batters' => $batters,
            'pitcher' => $pitcher,
            'relievers' => $relievers,
        ];
    }

    protected function generateBattingStats(array $batters, int $totalRuns): array
    {
        $stats = [];
        if (empty($batters)) {
            return $stats;
        }

        $allocatedRuns = 0;
        foreach ($batters as $index => $batter) {
            $atBats = 3 + ($index % 3);
            $contact = $batter['contact'] ?? 50;
            $eye = $batter['eye'] ?? 50;
            $power = $batter['power'] ?? 50;

            $hitChance = $this->clamp(($contact + $eye) / 260, 0.12, 0.48);
            $baseHits = $atBats * $hitChance;
            $extra = mt_rand(0, 100) < 35 ? 1 : 0;
            $hits = (int) $this->clamp(round($baseHits + $extra - 0.5), 0, $atBats);

            $hr = 0;
            if ($hits > 0 && $power > 60 && mt_rand(0, 100) < ($power / 1.3)) {
                $hr = 1;
            }

            $remainingRuns = max(0, $totalRuns - $allocatedRuns);
            $potentialRbi = max($hr * 2, $hits);
            $rbi = min($remainingRuns, $potentialRbi);
            $allocatedRuns += $rbi;

            $stats[] = [
                'order' => $batter['order'],
                'name' => $batter['name'],
                'ab' => $atBats,
                'h' => $hits,
                'hr' => $hr,
                'rbi' => $rbi,
            ];
        }

        return $stats;
    }

    protected function generatePitchingStats(string $teamName, array $pitchingLineup, int $runsAllowed, int $outsRecorded): array
    {
        $starter = $pitchingLineup['pitcher'] ?? null;
        $relievers = $pitchingLineup['relievers'] ?? [];

        if (!$starter && empty($relievers)) {
            return [];
        }

        $outsRecorded = max(0, $outsRecorded);

        if ($outsRecorded === 0) {
            $outsRecorded = 3;
        }

        $starterStamina = $starter['stamina'] ?? 60;
        $starterControl = $starter['control'] ?? 60;
        $starterVelocity = $starter['velocity'] ?? 60;
        $starterMovement = $starter['movement'] ?? 60;

        $remainingOuts = $outsRecorded;
        $remainingRuns = $runsAllowed;
        $remainingStrikeouts = max(0, (int) round(($starterVelocity / 14) + ($starterMovement / 16) + mt_rand(0, 3)));

        $arms = [];
        if ($starter) {
            $arms[] = [
                'name' => $starter['name'] ?? "{$teamName}投手",
                'stamina' => $starterStamina,
                'control' => $starterControl,
                'velocity' => $starterVelocity,
                'movement' => $starterMovement,
            ];
        }

        foreach ($relievers as $index => $reliever) {
            $arms[] = [
                'name' => $reliever['name'] ?? "{$teamName}リリーフ" . ($index + 1),
                'stamina' => (int) ($reliever['stamina'] ?? max(30, $starterStamina - 20 - $index * 5)),
                'control' => (int) ($reliever['control'] ?? max(38, $starterControl - 10 - $index * 5)),
                'velocity' => (int) ($reliever['velocity'] ?? max(40, $starterVelocity - 8 - $index * 4)),
                'movement' => (int) ($reliever['movement'] ?? max(40, $starterMovement - 8 - $index * 4)),
            ];
        }

        $statLines = [];

        foreach ($arms as $arm) {
            if ($remainingOuts <= 0) {
                break;
            }

            $outsPotential = max(1, (int) round(($arm['stamina'] / 100) * 9 + mt_rand(0, 4)));
            $outsThis = min($remainingOuts, $outsPotential);

            $efficiency = max(0.25, 1.05 - ($arm['control'] / 140));
            $erThis = min($remainingRuns, max(0, (int) round($outsThis * $efficiency + mt_rand(-1, 1))));
            $remainingRuns -= $erThis;

            $strikeoutChance = max(0.3, ($arm['velocity'] + $arm['movement']) / 230);
            $soThis = max(0, min($remainingStrikeouts, (int) round($outsThis * $strikeoutChance + mt_rand(0, 1))));
            $remainingStrikeouts -= $soThis;

            $statLines[] = [
                'name' => $arm['name'],
                'outs' => $outsThis,
                'ip' => $this->formatOuts($outsThis),
                'er' => $erThis,
                'so' => $soThis,
            ];

            $remainingOuts -= $outsThis;
        }

        if ($remainingRuns > 0 && !empty($statLines)) {
            $statLines[count($statLines) - 1]['er'] += $remainingRuns;
        }

        return $statLines;
    }

    protected function determineMvp(array $battingStats, array $pitchingStats, array $lineups): array
    {
        $candidates = [];

        foreach (['teamA', 'teamB'] as $teamKey) {
            foreach ($battingStats[$teamKey] ?? [] as $stat) {
                $score = ($stat['rbi'] * 3) + ($stat['hr'] * 4) + ($stat['h'] * 1.5);
                $candidates[] = [
                    'team' => $teamKey,
                    'name' => $stat['name'],
                    'type' => 'batter',
                    'score' => $score,
                    'detail' => sprintf('%d打数%d安打%d本塁打%d打点', $stat['ab'], $stat['h'], $stat['hr'], $stat['rbi']),
                ];
            }

            foreach ($pitchingStats[$teamKey] ?? [] as $stat) {
                $outsValue = $stat['outs'] ?? 0;
                $ipValue = $outsValue / 3;
                $score = ($ipValue * 1.5) + ($stat['so'] * 1.2) - ($stat['er'] * 2.5);
                $candidates[] = [
                    'team' => $teamKey,
                    'name' => $stat['name'],
                    'type' => 'pitcher',
                    'score' => $score,
                    'detail' => sprintf('投球回%s、失点%d、奪三振%d', $stat['ip'], $stat['er'], $stat['so']),
                ];
            }
        }

        if (empty($candidates)) {
            $fallbackTeam = 'A';
            $fallbackBatter = $lineups['teamA']['batters'][0] ?? null;
            if (!$fallbackBatter && !empty($lineups['teamB']['batters'][0])) {
                $fallbackBatter = $lineups['teamB']['batters'][0];
                $fallbackTeam = 'B';
            }

            return [
                'team' => $fallbackTeam,
                'name' => $fallbackBatter['name'] ?? '該当なし',
                'reason' => '活躍が光りました。',
            ];
        }

        usort($candidates, static function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $top = $candidates[0];

        return [
            'team' => $top['team'] === 'teamA' ? 'A' : 'B',
            'name' => $top['name'],
            'type' => $top['type'],
            'reason' => $top['detail'],
        ];
    }

    protected function getNextBatter(string $teamKey, array &$battingState): array
    {
        $lineup = $battingState[$teamKey]['lineup'] ?? [];
        $count = count($lineup);

        if ($count === 0) {
            return [
                'name' => '名無しの打者',
                'order' => 0,
                'position' => '--',
            ];
        }

        $currentIndex = $battingState[$teamKey]['index'] ?? 0;
        $currentIndex = $currentIndex % $count;

        $batter = $lineup[$currentIndex];
        $battingState[$teamKey]['index'] = ($currentIndex + 1) % $count;

        return $batter;
    }

    protected function advanceOnWalk(array $bases): array
    {
        return [
            true,
            $bases[0] || $bases[1],
            $bases[2] || ($bases[1] && $bases[0]),
        ];
    }

    protected function buildDescription(
        int $inning,
        bool $isTop,
        string $batter,
        string $pitcher,
        string $resultType,
        int $runsScored,
        int $rbi,
        int $scoreA,
        int $scoreB
    ): string {
        $scoreLabel = "（{$scoreA}-{$scoreB}）";

        switch ($resultType) {
            case 'homerun':
                $title = $runsScored >= 2 ? "{$runsScored}ランホームラン" : 'ソロホームラン';
                return "{$batter}が{$title}！{$scoreLabel}";
            case 'triple':
                return "{$batter}の三塁打！" . ($runsScored > 0 ? $scoreLabel : '');
            case 'double':
                return "{$batter}の二塁打！" . ($runsScored > 0 ? $scoreLabel : '');
            case 'single':
                return "{$batter}のヒット！" . ($runsScored > 0 ? $scoreLabel : '');
            case 'walk':
                if ($runsScored > 0) {
                    return "{$batter}が押し出し四球！{$scoreLabel}";
                }
                return "{$batter}がフォアボールで出塁。";
            case 'strikeout':
                return "{$pitcher}が{$batter}を三振に斬る！";
            case 'out':
            default:
                return "{$batter}は凡退。";
        }
    }

    protected function formatOuts(int $outs): string
    {
        $innings = intdiv($outs, 3);
        $remainder = $outs % 3;

        if ($remainder === 0) {
            return (string) $innings;
        }

        $fraction = $remainder === 1 ? '1/3' : '2/3';

        if ($innings === 0) {
            return $fraction;
        }

        return "{$innings} {$fraction}";
    }

    protected function normalizeStrategy(array $strategy = []): array
    {
        $filtered = Arr::only($strategy, self::STRATEGY_KEYS);

        $nonEmpty = array_filter(
            $filtered,
            static fn($value) => $value !== null && $value !== ''
        );

        return array_merge(self::DEFAULT_STRATEGY, $nonEmpty);
    }

    protected function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
