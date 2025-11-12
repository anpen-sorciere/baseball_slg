<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Player;

class PlayerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 阪神タイガース：近本光司
        Player::create([
            'name'             => '近本光司',
            'furigana'         => 'ちかもとこうじ',
            'handed_bat'       => '左',
            'handed_throw'     => '左',
            'primary_position' => '外',
            'born_year'        => 1994,
        ]);

        // 読売ジャイアンツ：岡本和真
        Player::create([
            'name'             => '岡本和真',
            'furigana'         => 'おかもとかずま',
            'handed_bat'       => '右',
            'handed_throw'     => '右',
            'primary_position' => '内',
            'born_year'        => 1996,
        ]);

        // 中日ドラゴンズ：岡林勇希
        Player::create([
            'name'             => '岡林勇希',
            'furigana'         => 'おかばやしゆうき',
            'handed_bat'       => '左',
            'handed_throw'     => '右',
            'primary_position' => '外',
            'born_year'        => 2002,
        ]);

        // 中日ドラゴンズ：大野雄大
        Player::create([
            'name'             => '大野雄大',
            'furigana'         => 'おおのゆうだい',
            'handed_bat'       => '左',
            'handed_throw'     => '左',
            'primary_position' => '投',
            'born_year'        => 1988,
        ]);
    }
}
