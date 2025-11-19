<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ri_course_ancestor_stats', function (Blueprint $table) {

            $table->id();

            $table->string('course_key', 32);    // 例: "08-TURF-2200"
            $table->string('grade_group', 8);    // G1 / G2 / G3 / JP / ALL
            $table->integer('distance');
            $table->string('track_type', 16);    // TURF / DIRT / STEEP

            // ★ 追加したい祖先分析モード
            $table->string('ancestor_mode', 16)->default('ALL');
            // ALL / F / M / FM

            $table->string('years_range', 32);   // 2006-2025 のような文字列

            $table->string('ancestor_id', 32)->nullable();
            $table->string('ancestor_name', 128)->nullable();

            $table->integer('start_count');
            $table->integer('win_count');
            $table->integer('place_count');
            $table->integer('show_count');
            $table->integer('board_count');
            $table->integer('out_of_board_count');

            $table->decimal('avg_blood_share', 8, 5)->nullable();

            $table->timestamps();

            // ancestor_mode を含む新 UNIQUE KEY
            $table->unique(
                ['course_key', 'grade_group', 'distance', 'track_type', 'years_range', 'ancestor_mode', 'ancestor_id'],
                'ri_course_ancestor_stats_unique'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('ri_course_ancestor_stats');
    }
};
