<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Laragear\Preload\Listing;

class LoadPreloadConfig
{
    /**
     * Create a new pipe instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     */
    public function __construct(protected ConfigContract $config)
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
        $listing->memory = (int) $this->config->get('preload.memory');
        $listing->projectOnly = $this->config->get('preload.project_only');

        return $next($listing);
    }
}
