<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            if (!Schema::hasColumn('games', 'custom_team_id')) {
                $table->foreignId('custom_team_id')
                    ->nullable()
                    ->after('team_b_id')
                    ->constrained('custom_teams')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            if (Schema::hasColumn('games', 'custom_team_id')) {
                $table->dropForeign(['custom_team_id']);
                $table->dropColumn('custom_team_id');
            }
        });
    }
};

