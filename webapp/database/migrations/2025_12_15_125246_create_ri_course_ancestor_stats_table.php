<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ri_course_ancestor_stats', function (Blueprint $table) {
            $table->bigIncrements('id');

            // 年
            $table->unsignedSmallInteger('year');

            // コース条件
            $table->char('jyo_cd', 2);
            $table->string('course_type', 10);      // TURF / DIRT / STEEP
            $table->string('turn_direction', 10);   // LEFT / RIGHT / STRAIGHT / UNKNOWN
            $table->string('course_detail', 20);    // OUTER / INNER / etc
            $table->unsignedSmallInteger('distance');

            // 条件
            $table->string('grade_group', 10);      // ALL / GRADE / OP etc
            $table->string('ancestor_mode', 5);     // ALL / F / M / FM

            // 祖先
            $table->string('ancestor_id', 20);      // (UNKNOWN) 含む
            $table->string('ancestor_name', 100);

            // 成績
            $table->unsignedInteger('start_count');
            $table->unsignedInteger('win_count');
            $table->unsignedInteger('place_count'); // 1–2着累計
            $table->unsignedInteger('show_count');  // 1–3着累計
            $table->unsignedInteger('board_count'); // 1–5着累計
            $table->unsignedInteger('out_of_board_count');

            // 血量
            $table->decimal('avg_blood_share', 8, 6)->nullable();

            $table->timestamps();

            // ★ 年単位ユニークキー
            $table->unique([
                'year',
                'jyo_cd',
                'course_type',
                'turn_direction',
                'course_detail',
                'distance',
                'grade_group',
                'ancestor_mode',
                'ancestor_id',
            ], 'uniq_course_ancestor_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ri_course_ancestor_stats');
    }
};
