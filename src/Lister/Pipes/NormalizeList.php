<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Laragear\Preload\Listing;

class NormalizeList
{
    /**
     * Handle the incoming preload listing.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        // @phpstan-ignore-next-line
        $listing->files = $listing->files->keys();

        return $next($listing);
    }
}
