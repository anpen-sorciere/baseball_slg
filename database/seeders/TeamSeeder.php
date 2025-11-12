<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 阪神タイガース
        Team::create([
            'name'         => '阪神タイガース',
            'short_name'   => 'TIGERS',
            'league'       => 'セ・リーグ',
            'founded_year' => 1935,
        ]);

        // 読売ジャイアンツ
        Team::create([
            'name'         => '読売ジャイアンツ',
            'short_name'   => 'GIANTS',
            'league'       => 'セ・リーグ',
            'founded_year' => 1934,
        ]);

        // 中日ドラゴンズ
        Team::create([
            'name'         => '中日ドラゴンズ',
            'short_name'   => 'DRAGONS',
            'league'       => 'セ・リーグ',
            'founded_year' => 1936,
        ]);
    }
}
