<?php

// database/migrations/2026_01_XX_000001_create_ri_bloodline_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ri_bloodline', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 論理キー（コード用）
            $table->string('line_key', 32)->unique()
                ->comment('系統キー: sunday / mrp / nd など');

            // 表示名
            $table->string('line_name', 64)
                ->comment('系統名: サンデーサイレンス系 など');

            // 説明（任意）
            $table->text('description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ri_bloodline');
    }
};
