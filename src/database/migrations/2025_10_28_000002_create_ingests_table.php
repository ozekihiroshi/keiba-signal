<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ingests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->cascadeOnDelete();
            $table->string('guid')->nullable();
            $table->string('hash')->unique();
            $table->string('url');
            $table->string('title');
            $table->text('summary_raw')->nullable();
            $table->text('summary')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->string('lang', 10)->nullable();
            $table->string('license_tag')->nullable();
            $table->json('raw_json')->nullable();
            $table->string('status')->default('draft'); // draft|published|hidden
            $table->timestamps();

            $table->unique(['source_id', 'guid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingests');
    }
};
