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
        Schema::table('custom_teams', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_teams', 'type')) {
                $table->string('type', 20)->default('original')->after('short_name');
            }
        });

        // 既存のデータを 'original' に設定
        DB::table('custom_teams')->whereNull('type')->update(['type' => 'original']);

        // ユニークインデックスを追加（既存のテーブルがある場合）
        // インデックスが存在しない場合のみ追加
        $indexExists = DB::select("SHOW INDEX FROM custom_teams WHERE Key_name = 'user_type_unique'");
        if (empty($indexExists)) {
            Schema::table('custom_teams', function (Blueprint $table) {
                $table->unique(['user_id', 'type'], 'user_type_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // インデックスが存在する場合のみ削除
        $indexExists = DB::select("SHOW INDEX FROM custom_teams WHERE Key_name = 'user_type_unique'");
        if (!empty($indexExists)) {
            Schema::table('custom_teams', function (Blueprint $table) {
                $table->dropUnique('user_type_unique');
            });
        }
        
        Schema::table('custom_teams', function (Blueprint $table) {
            if (Schema::hasColumn('custom_teams', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};

