<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Laragear\Preload\Listing;

class ExcludePreloadVariable
{
    /**
     * Handle the incoming preload listing.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        $listing->files->forget('$PRELOAD$');

        return $next($listing);
    }
}
