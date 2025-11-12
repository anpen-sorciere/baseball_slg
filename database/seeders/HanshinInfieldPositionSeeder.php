<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\Player;
use App\Models\PlayerSeason;
use Illuminate\Support\Facades\DB;

class HanshinInfieldPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hanshin = Team::where('name', '阪神タイガース')->first();
        
        if (!$hanshin) {
            $this->command->warn('阪神タイガースが見つかりませんでした。');
            return;
        }

        // 守備位置のマッピング（短縮名 → 標準名）
        $positionMap = [
            '一塁' => '一塁手',
            '二塁' => '二塁手',
            '三塁' => '三塁手',
            '遊撃' => '遊撃手',
        ];

        // 選手データ（背番号、選手名、守備位置）
        $players = [
            ['number' => '3', 'name' => '大山 悠輔', 'positions' => ['一塁']],
            ['number' => '51', 'name' => '中野 拓夢', 'positions' => ['二塁']],
            ['number' => '38', 'name' => '小幡 竜平', 'positions' => ['遊撃']],
            ['number' => '0', 'name' => '木浪 聖也', 'positions' => ['遊撃']],
            ['number' => '4', 'name' => '熊谷 敬宥', 'positions' => ['三塁', '遊撃', '一塁']],
            ['number' => '33', 'name' => '糸原 健斗', 'positions' => ['二塁', '三塁', '一塁']],
            ['number' => '8', 'name' => '佐藤 輝明', 'positions' => ['三塁']],
            ['number' => '62', 'name' => '植田 海', 'positions' => ['二塁']],
            ['number' => '25', 'name' => '渡邉 諒', 'positions' => ['二塁', '三塁']],
            ['number' => '44', 'name' => '戸井 零士', 'positions' => ['遊撃', '三塁']],
            ['number' => '45', 'name' => '佐野 大陽', 'positions' => ['二塁', '三塁']],
            ['number' => '52', 'name' => '山田 脩也', 'positions' => ['遊撃', '二塁']],
            ['number' => '56', 'name' => '百﨑 蒼生', 'positions' => ['遊撃']],
            ['number' => '67', 'name' => '高寺 望夢', 'positions' => ['二塁', '遊撃']],
            ['number' => '94', 'name' => '原口 文仁', 'positions' => ['一塁']],
            ['number' => '95', 'name' => 'ヘルナンデス', 'positions' => ['一塁', '三塁']],
            ['number' => '130', 'name' => '川﨑 俊哲', 'positions' => ['二塁', '遊撃']],
            ['number' => '133', 'name' => 'アルナエス', 'positions' => ['三塁', '一塁']],
        ];

        $updated = 0;
        $notFound = 0;

        foreach ($players as $playerData) {
            $playerName = $playerData['name'];
            $positions = $playerData['positions'];
            
            // 選手を検索（名前で部分一致も試す）
            $player = Player::where('team_id', $hanshin->id)
                ->where(function($query) use ($playerName) {
                    // 完全一致
                    $query->where('name', $playerName)
                        // スペースを除いた一致
                        ->orWhere('name', str_replace(' ', '', $playerName))
                        // 姓のみ（スペース前）
                        ->orWhere('name', 'LIKE', explode(' ', $playerName)[0] . '%');
                })
                ->first();

            if (!$player) {
                $this->command->warn("選手が見つかりませんでした: {$playerName}");
                $notFound++;
                continue;
            }

            // 2025年のPlayerSeasonを検索
            $season = PlayerSeason::where('player_id', $player->id)
                ->where('team_id', $hanshin->id)
                ->where('year', 2025)
                ->first();

            if (!$season) {
                $this->command->warn("2025年のシーズンデータが見つかりませんでした: {$playerName}");
                $notFound++;
                continue;
            }

            // 守備位置を標準名に変換
            $mappedPositions = [];
            foreach ($positions as $pos) {
                $mappedPositions[] = $positionMap[$pos] ?? $pos;
            }

            // position_1, position_2, position_3を設定
            $season->position_1 = $mappedPositions[0] ?? null;
            $season->position_2 = $mappedPositions[1] ?? null;
            $season->position_3 = $mappedPositions[2] ?? null;
            $season->save();

            $this->command->info("更新しました: {$playerName} - position_1: {$season->position_1}, position_2: {$season->position_2}, position_3: {$season->position_3}");
            $updated++;
        }

        $this->command->info("更新完了: {$updated}件の選手データを更新しました。");
        if ($notFound > 0) {
            $this->command->warn("見つからなかった選手: {$notFound}件");
        }
    }
}
