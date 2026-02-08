<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ri_track_code_definitions', function (Blueprint $table) {
            $table->id();

            // JRA TrackCD
            $table->char('track_cd', 2)->unique();

            // コース種別
            $table->string('course_type', 10);        // TURF / DIRT / STEEP / SAND
            $table->string('course_type_label', 10);  // 芝 / ダート / 障害 / サンド

            // 回り方向
            $table->string('turn_direction', 10);     // LEFT / RIGHT / STRAIGHT

            // ★ 内外・構造（解析の軸）
            $table->string('course_layout', 10);      // INNER / OUTER / SINGLE / STRAIGHT
            $table->string('layout_label', 20);       // 内回り / 外回り / 通常 / 直線

            // JRA公式説明（保持用）
            $table->string('description', 100)->nullable();

            // 将来制御用
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ri_track_code_definitions');
    }
};
