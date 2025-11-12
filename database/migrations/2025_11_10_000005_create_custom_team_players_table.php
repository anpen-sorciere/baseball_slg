<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 既にテーブルが存在する場合はスキップ
        if (Schema::hasTable('custom_team_players')) {
            return;
        }

        Schema::create('custom_team_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_team_id')->constrained('custom_teams')->onDelete('cascade');
            $table->foreignId('player_season_id')->constrained('player_seasons')->onDelete('cascade');
            $table->unsignedTinyInteger('batting_order')->nullable(); // 打順（1-9、控えはnull）
            $table->string('position', 5)->nullable(); // 守備位置（C, 1B, 2B, 3B, SS, LF, CF, RF, DH, P）
            $table->string('role', 20)->nullable(); // 役割（batter, pitcher など）
            $table->boolean('is_pitcher')->default(false); // 投手かどうか
            $table->boolean('is_starting_pitcher')->default(false); // 先発投手かどうか
            $table->string('pitcher_role', 20)->nullable(); // 投手の役割（starter, reliever, closer）
            $table->timestamps();

            // 同じチーム内で同じ選手を重複登録できないようにする
            $table->unique(['custom_team_id', 'player_season_id'], 'team_player_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_team_players');
    }
};

