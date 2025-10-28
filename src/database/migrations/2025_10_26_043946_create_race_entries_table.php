<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('race_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('race_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('horse_id')->constrained()->cascadeOnDelete()->index();
            $table->unsignedTinyInteger('frame_no')->nullable(); // 枠番
            $table->unsignedTinyInteger('horse_no')->nullable(); // 馬番
            $table->unsignedInteger('weight')->nullable();       // 斤量(kg*10)など運用で
            $table->string('jockey', 128)->nullable();
            $table->float('morning_odds')->nullable();
            $table->timestamps();
            $table->unique(['race_id','horse_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('race_entries'); }
};
