<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ri_uma_manual_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            // ri_uma.id（手動で追加した馬のID）
            $table->unsignedInteger('uma_id');

            // 参考用（ri_uma.KettoNum をそのまま入れる想定）
            $table->string('ketto_num', 255)->nullable();

            // 登録経路: 'netkeiba_scrape' / 'manual' など
            $table->string('source', 32);

            // 出馬表から取れた場合だけ（任意）
            $table->date('race_date')->nullable();

            // 追加情報が欲しくなった時用（任意）
            $table->json('meta')->nullable();

            $table->timestamps();

            // よく使う想定の索引
            $table->index('uma_id');
            $table->index(['source', 'race_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ri_uma_manual_logs');
    }
};
