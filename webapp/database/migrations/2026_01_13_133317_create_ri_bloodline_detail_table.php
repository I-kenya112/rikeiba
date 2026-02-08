<?php

// database/migrations/2026_01_XX_000002_create_ri_bloodline_detail_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ri_bloodline_detail', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 大分類FK
            $table->unsignedBigInteger('bloodline_id');

            // 論理キー
            $table->string('line_key_detail', 64)->unique()
                ->comment('詳細系統キー: sunday_deep / mrp_kingkame など');

            // 表示名
            $table->string('line_name', 64)
                ->comment('詳細系統名: ディープインパクト系 など');

            // 代表祖（HansyokuNum or Uma KettoNum）
            $table->string('root_horse_id', 10)->nullable()
                ->comment('代表祖ID（HansyokuNum or KettoNum）');

            // 備考
            $table->text('description')->nullable();

            $table->timestamps();

            $table->foreign('bloodline_id')
                ->references('id')
                ->on('ri_bloodline')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ri_bloodline_detail');
    }
};
