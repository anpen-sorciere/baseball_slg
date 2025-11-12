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
        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // 名前
            $table->string('furigana')->nullable();          // ふりがな
            $table->enum('handed_bat', ['右', '左', '両'])->nullable(); // 打席
            $table->enum('handed_throw', ['右', '左'])->nullable();      // 投げ手
            $table->string('primary_position')->nullable();  // 主な守備位置（投/捕/内/外）
            $table->integer('born_year')->nullable();        // 生年（年代縛り用など）
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
