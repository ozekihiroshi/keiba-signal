<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('races', function (Blueprint $table) {
            $table->id();
            $table->foreignId('track_id')->constrained()->cascadeOnDelete();
            $table->date('date')->index();
            $table->unsignedTinyInteger('race_no');  // 1-12R
            $table->string('name', 128)->nullable();
            $table->unsignedSmallInteger('distance'); // m
            $table->enum('surface', ['TURF','DIRT'])->index();
            $table->enum('going', ['FIRM','GOOD','SOFT','YIELDING','HEAVY','SLOPPY'])->nullable();
            $table->string('grade', 8)->nullable();  // G1/G2/G3/OP/…
            $table->string('ext_id', 32)->nullable()->unique(); // 外部ID
            $table->timestamps();
            $table->unique(['track_id','date','race_no']);
        });
    }
    public function down(): void { Schema::dropIfExists('races'); }
};
