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
        // 既にテーブルが存在する場合はスキップ
        if (Schema::hasTable('custom_teams')) {
            return;
        }

        Schema::create('custom_teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('name'); // チーム名
            $table->string('short_name', 12)->nullable(); // 略称（最大12文字）
            $table->string('type', 20)->default('original'); // 'original' または 'draft'
            $table->string('primary_color', 7)->nullable(); // プライマリカラー（HEX形式）
            $table->string('secondary_color', 7)->nullable(); // セカンダリカラー（HEX形式）
            $table->string('emblem_image_path')->nullable(); // エンブレム画像パス
            $table->unsignedInteger('year')->default(2025); // 年度
            $table->text('notes')->nullable(); // 備考
            $table->timestamps();

            // ユーザーごとにオリジナルチームとドラフトチームをそれぞれ1つずつ持つ制約
            $table->unique(['user_id', 'type'], 'user_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_teams');
    }
};

