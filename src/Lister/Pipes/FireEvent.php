<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Laragear\Preload\Events\ListGenerated;
use Laragear\Preload\Listing;

class FireEvent
{
    /**
     * Handle the incoming preload listing.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        ListGenerated::dispatch($listing);

        return $next($listing);
    }
}
