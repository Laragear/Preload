<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Laragear\Preload\Events\ListGenerated;
use Laragear\Preload\Listing;

class FireEvent
{
    /**
     * Handle the incoming preload listing.
     *
     * @param  \Laragear\Preload\Listing  $listing
     * @param  \Closure  $next
     * @return \Laragear\Preload\Listing
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        ListGenerated::dispatch($listing);

        return $next($listing);
    }
}
