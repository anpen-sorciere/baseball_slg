<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\Player;
use App\Models\PlayerSeason;

class PlayerSeasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // TeamSeeder / PlayerSeeder で入れた名前と完全一致している前提
        $hanshin = Team::where('name', '阪神タイガース')->first();
        $giants  = Team::where('name', '読売ジャイアンツ')->first();
        $dragons = Team::where('name', '中日ドラゴンズ')->first();

        $chikamoto  = Player::where('name', '近本光司')->first();
        $okamoto    = Player::where('name', '岡本和真')->first();
        $okabayashi = Player::where('name', '岡林勇希')->first();
        $ohno       = Player::where('name', '大野雄大')->first();

        // ---- 阪神タイガース：近本光司 2023シーズン例 ----
        if ($hanshin && $chikamoto) {
            PlayerSeason::create([
                'player_id'        => $chikamoto->id,
                'team_id'          => $hanshin->id,
                'year'             => 2023,
                'uniform_number'   => '5',
                'position_main'    => '中堅',
                'overall_rating'   => 80,
                'batting_contact'  => 80,
                'batting_power'    => 60,
                'batting_eye'      => 75,
                'running_speed'    => 85,
                'defense'          => 80,
                'pitcher_stamina'  => 0,
                'pitcher_control'  => 0,
                'pitcher_velocity' => 0,
                'pitcher_movement' => 0,
                'role'             => '野手',
            ]);
        }

        // ---- 読売ジャイアンツ：岡本和真 2023シーズン例 ----
        if ($giants && $okamoto) {
            PlayerSeason::create([
                'player_id'        => $okamoto->id,
                'team_id'          => $giants->id,
                'year'             => 2023,
                'uniform_number'   => '25',
                'position_main'    => '三塁',
                'overall_rating'   => 85,
                'batting_contact'  => 70,
                'batting_power'    => 90,
                'batting_eye'      => 75,
                'running_speed'    => 60,
                'defense'          => 70,
                'pitcher_stamina'  => 0,
                'pitcher_control'  => 0,
                'pitcher_velocity' => 0,
                'pitcher_movement' => 0,
                'role'             => '野手',
            ]);
        }

        // ---- 中日ドラゴンズ：岡林勇希 2023シーズン例 ----
        if ($dragons && $okabayashi) {
            PlayerSeason::create([
                'player_id'        => $okabayashi->id,
                'team_id'          => $dragons->id,
                'year'             => 2023,
                'uniform_number'   => '60',
                'position_main'    => '右翼',
                'overall_rating'   => 78,
                'batting_contact'  => 85,
                'batting_power'    => 55,
                'batting_eye'      => 70,
                'running_speed'    => 85,
                'defense'          => 80,
                'pitcher_stamina'  => 0,
                'pitcher_control'  => 0,
                'pitcher_velocity' => 0,
                'pitcher_movement' => 0,
                'role'             => '野手',
            ]);
        }

        // ---- 中日ドラゴンズ：大野雄大 2023シーズン例 ----
        if ($dragons && $ohno) {
            PlayerSeason::create([
                'player_id'        => $ohno->id,
                'team_id'          => $dragons->id,
                'year'             => 2023,
                'uniform_number'   => '22',
                'position_main'    => '先発',
                'overall_rating'   => 83,
                'batting_contact'  => 30,
                'batting_power'    => 20,
                'batting_eye'      => 25,
                'running_speed'    => 40,
                'defense'          => 50,
                'pitcher_stamina'  => 85,
                'pitcher_control'  => 80,
                'pitcher_velocity' => 88,
                'pitcher_movement' => 82,
                'role'             => '投手',
            ]);
        }
    }
}
