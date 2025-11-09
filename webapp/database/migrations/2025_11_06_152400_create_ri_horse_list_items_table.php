<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ri_horse_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('list_id')->constrained('ri_horse_lists')->cascadeOnDelete();
            $table->string('horse_id', 10);
            $table->string('horse_name', 50);
            $table->integer('order_no')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ri_horse_list_items');
    }
};
