<?php

namespace Laragear\Preload\Compiler\Pipes;

use Closure;
use Illuminate\Contracts\Events\Dispatcher;
use Laragear\Preload\Events\PreloadGenerated;
use Laragear\Preload\Listing;

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
     * Handle the script generation.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        $this->dispatcher->dispatch(new PreloadGenerated($listing));

        return $next($listing);
    }
}
