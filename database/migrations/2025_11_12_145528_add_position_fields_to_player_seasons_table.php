<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('player_seasons', function (Blueprint $table) {
            if (!Schema::hasColumn('player_seasons', 'position_1')) {
                $table->string('position_1', 10)->nullable()->after('position_main');
            }
            if (!Schema::hasColumn('player_seasons', 'position_2')) {
                $table->string('position_2', 10)->nullable()->after('position_1');
            }
            if (!Schema::hasColumn('player_seasons', 'position_3')) {
                $table->string('position_3', 10)->nullable()->after('position_2');
            }
        });

        // 既存のplayersテーブルのposition_1,2,3データをplayer_seasonsに移行
        // 各player_seasonsレコードに対して、対応するplayerのposition_1,2,3をコピー
        DB::statement("
            UPDATE player_seasons ps
            INNER JOIN players p ON ps.player_id = p.id
            SET ps.position_1 = p.position_1,
                ps.position_2 = p.position_2,
                ps.position_3 = p.position_3
            WHERE p.position_1 IS NOT NULL 
               OR p.position_2 IS NOT NULL 
               OR p.position_3 IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('player_seasons', function (Blueprint $table) {
            if (Schema::hasColumn('player_seasons', 'position_3')) {
                $table->dropColumn('position_3');
            }
            if (Schema::hasColumn('player_seasons', 'position_2')) {
                $table->dropColumn('position_2');
            }
            if (Schema::hasColumn('player_seasons', 'position_1')) {
                $table->dropColumn('position_1');
            }
        });
    }
};
