<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ri_pedigree_new', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('horse_id', 255);
            $table->string('horse_name', 36)->nullable();

            $table->string('relation_path', 10);
            $table->char('relation_type', 1)->comment('F or M');

            $table->tinyInteger('generation');
            $table->unsignedSmallInteger('position_index')
                ->comment('binary position index');

            $table->string('ancestor_id_uma', 10)->nullable();
            $table->string('ancestor_id_hansyoku', 10)->nullable();
            $table->string('ancestor_name', 64)->nullable();

            $table->decimal('blood_share', 6, 5)->nullable();

            $table->string('line_key', 50)->nullable();
            $table->string('line_key_detail', 80)->nullable();

            $table->enum('source', ['batch','manual','fuzzy','overseas'])
                ->default('batch');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')
                ->useCurrent()
                ->useCurrentOnUpdate();

            // ★ ここが重要
            $table->primary('id');

            // 論理一意制約として保持
            $table->unique(
                ['horse_id', 'relation_path'],
                'uk_horse_relation'
            );

            $table->index(
                ['horse_id', 'relation_type', 'generation'],
                'idx_horse_relation'
            );

            $table->index(
                ['line_key', 'line_key_detail'],
                'idx_line'
            );

            $table->index(
                ['horse_id', 'relation_type', 'generation', 'line_key'],
                'idx_analyze'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ri_pedigree_new');
    }
};
