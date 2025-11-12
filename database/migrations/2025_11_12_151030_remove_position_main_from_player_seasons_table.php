<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('player_seasons', function (Blueprint $table) {
            if (Schema::hasColumn('player_seasons', 'position_main')) {
                $table->dropColumn('position_main');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('player_seasons', function (Blueprint $table) {
            if (!Schema::hasColumn('player_seasons', 'position_main')) {
                $table->string('position_main')->nullable()->after('uniform_number');
            }
        });
    }
};
