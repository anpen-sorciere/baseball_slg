<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 各 Seeder を順番に実行
        $this->call([
            TeamSeeder::class,          // 球団データ（阪神・巨人など）
            PlayerSeeder::class,        // 選手データ（近本・岡本など）
            PlayerSeasonSeeder::class,  // 年度別能力値データ
        ]);
    }
}
