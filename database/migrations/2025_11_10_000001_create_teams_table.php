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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // 球団名（例：阪神タイガース）
            $table->string('short_name');      // 略称（例：TIGERS）
            $table->string('league');          // セ・リーグ / パ・リーグ
            $table->integer('founded_year')->nullable(); // 創設年
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
