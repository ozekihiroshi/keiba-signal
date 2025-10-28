<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create("odds_snapshots", function (Blueprint $table) {
            $table->id();
            $table->foreignId("race_id")->constrained()->cascadeOnDelete()->index();
            $table->timestamp("captured_at")->index();
            $table->json("win")->nullable();
            $table->json("place")->nullable();
            $table->json("quinella")->nullable();
            $table->json("exacta")->nullable();
            $table->json("trio")->nullable();
            $table->json("trifecta")->nullable();
            $table->timestamps();
            $table->unique(["race_id","captured_at"]);
        });
    }
    public function down(): void { Schema::dropIfExists("odds_snapshots"); }
};
