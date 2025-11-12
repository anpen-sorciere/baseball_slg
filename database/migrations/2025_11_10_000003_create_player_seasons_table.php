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
        Schema::create('player_seasons', function (Blueprint $table) {
            // 主キー
            $table->bigIncrements('id');

            // 外部キー用のカラム（型を明示）
            $table->unsignedBigInteger('player_id');
            $table->unsignedBigInteger('team_id');

            // 年度
            $table->year('year');

            $table->string('uniform_number')->nullable();
            $table->string('position_main')->nullable();

            // 総合力
            $table->integer('overall_rating')->default(50);

            // 打撃系
            $table->integer('batting_contact')->default(50);
            $table->integer('batting_power')->default(50);
            $table->integer('batting_eye')->default(50);
            $table->integer('running_speed')->default(50);
            $table->integer('defense')->default(50);

            // 投手系
            $table->integer('pitcher_stamina')->default(0);
            $table->integer('pitcher_control')->default(0);
            $table->integer('pitcher_velocity')->default(0);
            $table->integer('pitcher_movement')->default(0);
            $table->string('role')->nullable(); // 先発/中継ぎ/抑え/野手など

            $table->timestamps();

            // 🔽 外部キー定義（ここがポイント）
            $table->foreign('player_id')
                ->references('id')->on('players')
                ->onDelete('cascade');

            $table->foreign('team_id')
                ->references('id')->on('teams')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_seasons');
    }
};
