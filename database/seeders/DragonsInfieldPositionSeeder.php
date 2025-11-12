<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\Player;
use App\Models\PlayerSeason;
use Illuminate\Support\Facades\DB;

class DragonsInfieldPositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dragons = Team::where('name', '中日ドラゴンズ')->first();
        
        if (!$dragons) {
            $this->command->warn('中日ドラゴンズが見つかりませんでした。');
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
            ['number' => '6', 'name' => '山本 泰寛', 'positions' => ['遊撃']],
            ['number' => '2', 'name' => '田中 幹也', 'positions' => ['二塁']],
            ['number' => '27', 'name' => '高橋 周平', 'positions' => ['三塁']],
            ['number' => '18', 'name' => '中田 翔', 'positions' => ['一塁']],
            ['number' => '5', 'name' => '石川 昂弥', 'positions' => ['三塁']],
        ];

        $updated = 0;
        $notFound = 0;

        foreach ($players as $playerData) {
            $playerName = $playerData['name'];
            $positions = $playerData['positions'];
            
            // 選手を検索（名前で部分一致も試す）
            $player = Player::where('team_id', $dragons->id)
                ->where(function($query) use ($playerName) {
                    // 完全一致
                    $query->where('name', $playerName)
                        // スペースを除いた一致
                        ->orWhere('name', str_replace(' ', '', $playerName))
                        // 姓のみ（スペース前）
                        ->orWhere('name', 'LIKE', explode(' ', $playerName)[0] . '%')
                        // 名のみ（スペース後）
                        ->orWhere('name', 'LIKE', '%' . (explode(' ', $playerName)[1] ?? ''));
                })
                ->first();

            if (!$player) {
                $this->command->warn("選手が見つかりませんでした: {$playerName}");
                $notFound++;
                continue;
            }

            // 2025年のPlayerSeasonを検索
            $season = PlayerSeason::where('player_id', $player->id)
                ->where('team_id', $dragons->id)
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
