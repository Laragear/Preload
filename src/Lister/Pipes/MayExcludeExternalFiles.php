<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;

class MayExcludeExternalFiles
{
    /**
     * Create a new pipe instance.
     *
     * @param  \Laragear\Preload\Preloader  $preload
     */
    public function __construct(protected Preloader $preload)
    {
        //
    }

    /**
     * Handle the incoming preload listing.
     *
     * @param  \Laragear\Preload\Listing  $listing
     * @param  \Closure  $next
     * @return \Laragear\Preload\Listing
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        foreach ($listing->exclude as $key => $exclude) {
            $excluded = $this->preload->getFilesFromFinder($exclude)->flip();

            $listing->excludeCount += $excluded->count();

            $listing->files = $listing->files->diffKeys($excluded);
        }

        $listing->exclude = [];

        return $next($listing);
    }
}
