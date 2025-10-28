<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('horses', function (Blueprint $table) {
            $table->id();
            $table->string('ext_id', 32)->nullable()->index(); // 外部ID(netkeiba等)
            $table->string('name', 128);
            $table->enum('sex', ['c','f','g','m','h'])->nullable(); // 牡c 牝f セg 牝馬m 牡馬h 等 運用に合わせて
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('sire', 128)->nullable();
            $table->string('dam', 128)->nullable();
            $table->string('trainer', 128)->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('horses'); }
};
