<?php

namespace Laragear\Preload\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;

/**
 * @internal
 */
class StorePreloadScript implements ShouldQueue, ShouldBeUnique, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, Queueable;

    /**
     * Name of the overlap key to handle job uniqueness.
     */
    public const OVERLAP_KEY = 'write_preload_script';

    /**
     * Create a new job instance.
     */
    public function __construct(public Listing $listing)
    {
        //
    }

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping(static::OVERLAP_KEY)];
    }

    /**
     * Execute the job.
     */
    public function handle(Preloader $preload): void
    {
        $preload->save($this->listing);
    }
}
