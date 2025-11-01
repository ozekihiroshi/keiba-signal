<?php

namespace Tests\Feature;

use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class FeedsPullTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function command_registers_jra_source()
    {
        // Run migrations in memory or test DB
        Artisan::call('migrate', ['--force' => true]);

        // Stub: we won't actually fetch remote in test env.
        // Just ensure command can be called and creates Source row when none exists.
        $this->partialMock(\App\Services\Feeds\JraRssClient::class, function ($mock) {
            $mock->shouldReceive('fromConfig')->andReturnSelf();
            $mock->shouldReceive('fetch')->andReturn([]);
        });

        $exit = Artisan::call('feeds:pull', ['--source' => 'jra']);
        $this->assertEquals(0, $exit);

        $this->assertDatabaseHas('sources', ['code' => 'jra']);
        $this->assertNotNull(Source::where('code','jra')->first()->last_fetched_at);
    }
}
