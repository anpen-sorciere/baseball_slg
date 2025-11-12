<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_team_players', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_team_players', 'is_starting_pitcher')) {
                $table->boolean('is_starting_pitcher')->default(false)->after('is_pitcher');
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_team_players', function (Blueprint $table) {
            if (Schema::hasColumn('custom_team_players', 'is_starting_pitcher')) {
                $table->dropColumn('is_starting_pitcher');
            }
        });
    }
};

