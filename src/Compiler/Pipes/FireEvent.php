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
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     */
    public function __construct(protected Dispatcher $dispatcher)
    {
        //
    }

    /**
     * Handle the script generation.
     *
     * @param  \Laragear\Preload\Listing  $listing
     * @param  \Closure  $next
     * @return Listing
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        $this->dispatcher->dispatch(new PreloadGenerated($listing));

        return $next($listing);
    }
}
