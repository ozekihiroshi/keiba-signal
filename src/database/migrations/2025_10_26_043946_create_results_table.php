<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete()->unique();
            $table->json('finish_order');   // [{horse_no:xx, place:1, time:..., margin:...}, ...]
            $table->string('time', 16)->nullable(); // 走破タイム文字列
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('results'); }
};
