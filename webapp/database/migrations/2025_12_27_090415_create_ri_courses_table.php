<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ri_courses', function (Blueprint $table) {
            $table->id();

            // UI 用
            $table->string('display_group_key', 32)->index();

            // 内部用
            $table->string('course_key', 64)->unique();

            // 基本情報
            $table->char('jyo_cd', 2)->index();
            $table->string('jyo_name', 20);

            $table->string('course_type', 10);
            $table->string('course_type_label', 10);

            $table->smallInteger('distance')->index();

            // コース構造
            $table->string('turn_direction', 10);
            $table->string('course_detail', 20);
            $table->string('detail_label', 20);

            // 表示制御
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['jyo_cd', 'course_type', 'distance']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ri_courses');
    }
};
