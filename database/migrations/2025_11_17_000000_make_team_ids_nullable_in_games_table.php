<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 外部キー制約を削除
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign(['team_a_id']);
            $table->dropForeign(['team_b_id']);
        });

        // カラムをnullableに変更（MySQL用のSQL）
        DB::statement('ALTER TABLE `games` MODIFY `team_a_id` BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE `games` MODIFY `team_b_id` BIGINT UNSIGNED NULL');

        // 外部キー制約を再追加（nullable対応）
        Schema::table('games', function (Blueprint $table) {
            $table->foreign('team_a_id')->references('id')->on('teams')->nullOnDelete();
            $table->foreign('team_b_id')->references('id')->on('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // 外部キー制約を削除
        Schema::table('games', function (Blueprint $table) {
            $table->dropForeign(['team_a_id']);
            $table->dropForeign(['team_b_id']);
        });

        // カラムをNOT NULLに戻す（既存のnullデータがある場合はエラーになる可能性がある）
        DB::statement('ALTER TABLE `games` MODIFY `team_a_id` BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE `games` MODIFY `team_b_id` BIGINT UNSIGNED NOT NULL');

        // 外部キー制約を再追加
        Schema::table('games', function (Blueprint $table) {
            $table->foreign('team_a_id')->references('id')->on('teams')->cascadeOnDelete();
            $table->foreign('team_b_id')->references('id')->on('teams')->cascadeOnDelete();
        });
    }
};

