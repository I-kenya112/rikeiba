<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ri_course_inbreed', function (Blueprint $table) {
            $table->id();

            // 集計単位
            $table->unsignedSmallInteger('year');

            // コース
            $table->string('course_key', 64);
            $table->string('display_group_key', 32);

            // 条件
            $table->string('grade_group', 10);
            $table->string('ancestor_mode', 5);

            // インブリード祖先
            $table->string('ancestor_id', 20);
            $table->string('ancestor_name', 100);

            // 件数
            $table->unsignedInteger('start_count');
            $table->unsignedInteger('win_count');
            $table->unsignedInteger('place_count');
            $table->unsignedInteger('show_count');
            $table->unsignedInteger('board_count');
            $table->unsignedInteger('out_of_board_count');

            // インブリード特有
            $table->decimal('avg_blood_share', 8, 6)->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'year',
                    'course_key',
                    'grade_group',
                    'ancestor_mode',
                    'ancestor_id'
                ],
                'uniq_course_inbreed_year'
            );

            $table->index(
                ['course_key', 'grade_group', 'ancestor_mode'],
                'idx_course_filter'
            );

            $table->index('ancestor_id', 'idx_ancestor_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ri_course_inbreed');
    }
};
