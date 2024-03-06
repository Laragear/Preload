<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;

class MayExcludeExternalFiles
{
    /**
     * Create a new pipe instance.
     */
    public function __construct(protected Preloader $preload)
    {
        //
    }

    /**
     * Handle the incoming preload listing.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        foreach ($listing->exclude as $exclude) {
            $excluded = $this->preload->getFilesFromFinder($exclude)->flip();

            $listing->excludeCount += $excluded->count();

            // @phpstan-ignore-next-line
            $listing->files = $listing->files->diffKeys($excluded);
        }

        $listing->exclude = [];

        return $next($listing);
    }
}
