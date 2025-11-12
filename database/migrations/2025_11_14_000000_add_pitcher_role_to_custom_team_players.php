<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_team_players', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_team_players', 'pitcher_role')) {
                $table->string('pitcher_role', 20)->nullable()->after('is_starting_pitcher');
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_team_players', function (Blueprint $table) {
            if (Schema::hasColumn('custom_team_players', 'pitcher_role')) {
                $table->dropColumn('pitcher_role');
            }
        });
    }
};

