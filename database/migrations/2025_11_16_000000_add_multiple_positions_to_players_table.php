<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('players', function (Blueprint $table) {
            // position_1カラムが存在しない場合、primary_positionから移行
            if (!Schema::hasColumn('players', 'position_1')) {
                if (Schema::hasColumn('players', 'primary_position')) {
                    // primary_positionをposition_1にコピーしてから削除
                    DB::statement('ALTER TABLE `players` ADD COLUMN `position_1` VARCHAR(10) NULL AFTER `handed_throw`');
                    DB::statement('UPDATE `players` SET `position_1` = `primary_position` WHERE `primary_position` IS NOT NULL');
                    DB::statement('ALTER TABLE `players` DROP COLUMN `primary_position`');
                } else {
                    $table->string('position_1', 10)->nullable()->after('handed_throw');
                }
            }
            
            // 2つ目と3つ目の守備位置を追加
            if (!Schema::hasColumn('players', 'position_2')) {
                $table->string('position_2', 10)->nullable()->after('position_1');
            }
            if (!Schema::hasColumn('players', 'position_3')) {
                $table->string('position_3', 10)->nullable()->after('position_2');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('players', function (Blueprint $table) {
            if (Schema::hasColumn('players', 'position_3')) {
                $table->dropColumn('position_3');
            }
            if (Schema::hasColumn('players', 'position_2')) {
                $table->dropColumn('position_2');
            }
            if (Schema::hasColumn('players', 'position_1')) {
                // position_1をprimary_positionに戻す
                if (!Schema::hasColumn('players', 'primary_position')) {
                    DB::statement('ALTER TABLE `players` ADD COLUMN `primary_position` VARCHAR(10) NULL AFTER `handed_throw`');
                    DB::statement('UPDATE `players` SET `primary_position` = `position_1` WHERE `position_1` IS NOT NULL');
                }
                $table->dropColumn('position_1');
            }
        });
    }
};

