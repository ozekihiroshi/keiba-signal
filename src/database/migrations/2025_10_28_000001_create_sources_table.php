<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sources', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type')->default('rss'); // rss|press|x|wiki etc.
            $table->string('base_url')->nullable();
            $table->string('rss_url')->nullable();
            $table->string('license_tag')->nullable();
            $table->boolean('robots_allowed')->default(true);
            $table->unsignedInteger('fetch_interval_minutes')->default(30);
            $table->timestamp('last_fetched_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sources');
    }
};
