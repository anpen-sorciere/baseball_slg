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
        if (Schema::hasTable('custom_team_strategies')) {
            return;
        }

        Schema::create('custom_team_strategies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_team_id')->unique()->constrained('custom_teams')->onDelete('cascade');
            $table->string('offense_style', 50)->nullable(); // 攻撃スタイル
            $table->string('pitching_style', 50)->nullable(); // 投球スタイル
            $table->string('defense_style', 50)->nullable(); // 守備スタイル
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_team_strategies');
    }
};

