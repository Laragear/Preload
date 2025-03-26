<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Laragear\Preload\Events\ListGenerated;
use Laragear\Preload\Listing;

/**
 * @internal
 */
class FireEvent
{
    /**
     * Create a new pipe instance.
     */
    public function __construct(protected Dispatcher $dispatcher)
    {
        //
    }

    /**
     * Handle the incoming preload listing.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        $this->dispatcher->dispatch(new ListGenerated($listing));

        return $next($listing);
    }
}
