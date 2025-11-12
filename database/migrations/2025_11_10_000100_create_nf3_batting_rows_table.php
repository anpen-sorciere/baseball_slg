<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('nf3_batting_rows', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->foreignId('team_id')->nullable()->constrained('teams');
            $table->string('team_name')->nullable();
            $table->enum('section', ['batters', 'pitchers'])->default('batters');
            $table->unsignedSmallInteger('row_index');
            $table->string('number')->nullable();
            $table->string('name')->nullable();
            $table->json('columns');
            $table->text('raw_line')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nf3_batting_rows');
    }
};


