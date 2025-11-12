<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) 新しいカラム・インデックスの追加（ここは Schema ビルダーでOK）
        Schema::table('player_seasons', function (Blueprint $table) {
            // league 追加（なければ）
            if (!Schema::hasColumn('player_seasons', 'league')) {
                $table->string('league', 10)->nullable()->after('year');
            }

            // is_two_way / nf3_* 追加（なければ）
            if (!Schema::hasColumn('player_seasons', 'is_two_way')) {
                $table->boolean('is_two_way')->default(false)->after('role');
            }
            if (!Schema::hasColumn('player_seasons', 'nf3_batting_row_id')) {
                $table->unsignedBigInteger('nf3_batting_row_id')->nullable()->after('is_two_way');
            }
            if (!Schema::hasColumn('player_seasons', 'nf3_pitching_row_id')) {
                $table->unsignedBigInteger('nf3_pitching_row_id')->nullable()->after('nf3_batting_row_id');
            }

            // インデックス類（存在チェックはせずそのまま。既にあればエラーになるので
            // その場合は一度手動で DROP してから実行してください）
            $table->unique(['player_id', 'year'], 'uniq_player_year');
            $table->index(['team_id', 'year'], 'idx_team_year');
            $table->index('nf3_batting_row_id', 'idx_nf3_batting_row_id');
            $table->index('nf3_pitching_row_id', 'idx_nf3_pitching_row_id');
        });

        // 2) 型変更は Doctrine DBAL を使わず、生 SQL でやる
        // 能力値カラムを TINYINT UNSIGNED (0〜255) に変更
        DB::statement("ALTER TABLE player_seasons MODIFY overall_rating TINYINT UNSIGNED NOT NULL DEFAULT 50");
        DB::statement("ALTER TABLE player_seasons MODIFY batting_contact TINYINT UNSIGNED NOT NULL DEFAULT 50");
        DB::statement("ALTER TABLE player_seasons MODIFY batting_power TINYINT UNSIGNED NOT NULL DEFAULT 50");
        DB::statement("ALTER TABLE player_seasons MODIFY batting_eye TINYINT UNSIGNED NOT NULL DEFAULT 50");
        DB::statement("ALTER TABLE player_seasons MODIFY running_speed TINYINT UNSIGNED NOT NULL DEFAULT 50");
        DB::statement("ALTER TABLE player_seasons MODIFY defense TINYINT UNSIGNED NOT NULL DEFAULT 50");
        DB::statement("ALTER TABLE player_seasons MODIFY pitcher_stamina TINYINT UNSIGNED NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE player_seasons MODIFY pitcher_control TINYINT UNSIGNED NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE player_seasons MODIFY pitcher_velocity TINYINT UNSIGNED NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE player_seasons MODIFY pitcher_movement TINYINT UNSIGNED NOT NULL DEFAULT 0");

        // 3) 文字列カラムの長さを縮小
        DB::statement("ALTER TABLE player_seasons MODIFY uniform_number VARCHAR(4) NULL");
        DB::statement("ALTER TABLE player_seasons MODIFY position_main VARCHAR(10) NULL");
        DB::statement("ALTER TABLE player_seasons MODIFY role VARCHAR(20) NULL");

        // ※ id の AUTO_INCREMENT / PRIMARY KEY 変更は、既存の制約状況によって壊れやすいので
        //   今回の migration では触らずにおきます。
        //   必要であれば別途専用 migration or 手動 ALTER で設定してください。
    }

    public function down(): void
    {
        Schema::table('player_seasons', function (Blueprint $table) {
            // インデックス削除
            $table->dropUnique('uniq_player_year');
            $table->dropIndex('idx_team_year');
            $table->dropIndex('idx_nf3_batting_row_id');
            $table->dropIndex('idx_nf3_pitching_row_id');

            // 追加カラム削除
            if (Schema::hasColumn('player_seasons', 'league')) {
                $table->dropColumn('league');
            }
            if (Schema::hasColumn('player_seasons', 'is_two_way')) {
                $table->dropColumn('is_two_way');
            }
            if (Schema::hasColumn('player_seasons', 'nf3_batting_row_id')) {
                $table->dropColumn('nf3_batting_row_id');
            }
            if (Schema::hasColumn('player_seasons', 'nf3_pitching_row_id')) {
                $table->dropColumn('nf3_pitching_row_id');
            }
        });

        // 型変更を元に戻す down は、今回は省略（必要なら別途 migration を用意）
    }
};
