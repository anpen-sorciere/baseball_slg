<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('player_seasons', function (Blueprint $table) {
            if (!Schema::hasColumn('player_seasons', 'is_pitcher')) {
                $table->boolean('is_pitcher')->default(false)->after('role');
            }
        });

        // 既存データに対して、投手能力がある選手を投手として設定
        DB::statement("
            UPDATE player_seasons 
            SET is_pitcher = 1 
            WHERE role IN ('starter', 'reliever', 'closer')
               OR pitcher_velocity > 0 
               OR pitcher_control > 0 
               OR pitcher_stamina > 0
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_seasons', function (Blueprint $table) {
            if (Schema::hasColumn('player_seasons', 'is_pitcher')) {
                $table->dropColumn('is_pitcher');
            }
        });
    }
};

