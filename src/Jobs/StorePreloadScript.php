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

class StorePreloadScript implements ShouldQueue, ShouldBeUnique, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, Queueable;

    /**
     * Name of the overlap key to handle job uniqueness.
     *
     * @var string
     */
    public const OVERLAP_KEY = 'write_preload_script';

    /**
     * Create a new job instance.
     *
     * @param  \Laragear\Preload\Listing  $listing
     */
    public function __construct(public Listing $listing)
    {
        $this->middleware = [new WithoutOverlapping(static::OVERLAP_KEY)];
    }

    /**
     * Execute the job.
     *
     * @param  \Laragear\Preload\Preloader  $preload
     * @return void
     */
    public function handle(Preloader $preload): void
    {
        $preload->generate($this->listing);
    }
}
