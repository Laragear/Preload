<?php

namespace Tests\Jobs;

use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Bus;
use Laragear\Preload\Jobs\StorePreloadScript;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;
use Mockery\MockInterface;
use Tests\TestCase;

class StorePreloadScriptTest extends TestCase
{
    protected function preloader(): Preloader
    {
        return $this->app->make(Preloader::class);
    }

    public function test_calls_preloader_for_generation(): void
    {
        $bus = Bus::fake();

        $listing = new Listing();

        $this->mock(Preloader::class, function (MockInterface $mock) use ($listing) {
            $mock->expects('save')->with($listing)->andReturn($listing);
        });

        StorePreloadScript::dispatch($listing);

        $bus->assertDispatched(StorePreloadScript::class, function (StorePreloadScript $job) use ($listing): bool {
            static::assertSame($listing, $job->listing);
            static::assertEquals([new WithoutOverlapping(StorePreloadScript::OVERLAP_KEY)], $job->middleware());

            $this->app->call([$job, 'handle']);

            return true;
        });
    }
}
