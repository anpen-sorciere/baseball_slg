<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_teams', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_teams', 'year')) {
                $table->unsignedInteger('year')->default(2025)->after('name');
            }

            if (!Schema::hasColumn('custom_teams', 'notes')) {
                $table->text('notes')->nullable()->after('year');
            }
        });

        Schema::table('custom_team_players', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_team_players', 'is_pitcher')) {
                $table->boolean('is_pitcher')->default(false)->after('batting_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_team_players', function (Blueprint $table) {
            if (Schema::hasColumn('custom_team_players', 'is_pitcher')) {
                $table->dropColumn('is_pitcher');
            }
        });

        Schema::table('custom_teams', function (Blueprint $table) {
            if (Schema::hasColumn('custom_teams', 'notes')) {
                $table->dropColumn('notes');
            }

            if (Schema::hasColumn('custom_teams', 'year')) {
                $table->dropColumn('year');
            }
        });
    }
};

