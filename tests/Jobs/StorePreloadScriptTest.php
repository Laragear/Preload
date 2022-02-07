<?php

namespace Tests\Jobs;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Laragear\Preload\Facades\Preload;
use Laragear\Preload\Jobs\StorePreloadScript;
use Laragear\Preload\Listing;
use Tests\TestCase;

class StorePreloadScriptTest extends TestCase
{
    public function test_calls_preloader_for_generation(): void
    {
        $bus = Bus::fake();

        $listing = new Listing(new Collection());

        Preload::shouldReceive('generate')
            ->with($listing)
            ->andReturn($listing);

        StorePreloadScript::dispatch($listing);

        $bus->assertDispatched(StorePreloadScript::class, function (StorePreloadScript $job) use ($listing): bool {
            static::assertSame($listing, $job->listing);

            $this->app->call([$job, 'handle']);

            return true;
        });
    }
}
