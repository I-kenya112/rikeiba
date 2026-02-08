<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ri_hansyoku_bloodline', function (Blueprint $table) {
            // HansyokuNum を主キーにして「1:1拡張」にする
            $table->string('hansyoku_num', 10)->primary();

            $table->string('line_key', 32)->nullable()->comment('major: sunday/roberto/mrp/nd/nasrullah');
            $table->string('line_key_detail', 64)->nullable()->comment('detail: sunday_deep etc');

            // 解決の根拠（任意だがあると神）
            $table->string('resolved_root_hansyoku_num', 10)->nullable()->comment('hit root');
            $table->unsignedTinyInteger('resolved_depth')->nullable()->comment('0..20');
            $table->enum('method', ['seed', 'trace', 'unknown'])->default('unknown');

            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index(['line_key', 'line_key_detail'], 'idx_hansyoku_bloodline_line');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ri_hansyoku_bloodline');
    }
};
