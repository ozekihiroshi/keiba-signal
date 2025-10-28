<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tracks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();   // ex: TOKYO, KYOTO...
            $table->string('name', 64);             // 東京, 京都...
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tracks'); }
};
