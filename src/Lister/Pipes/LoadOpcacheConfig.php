<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Illuminate\Support\Arr;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Listing;
use Laragear\Preload\Opcache;

class LoadOpcacheConfig
{
    /**
     * Create a new pipe instance.
     */
    public function __construct(protected Opcache $opcache)
    {
        //
    }

    /**
     * Handle the incoming preload listing.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        if ($this->opcache->isDisabled()) {
            throw new PreloadException('Opcache is disabled.');
        }

        $listing->opcache['memory_usage'] = Arr::only(
            $this->opcache->getMemoryUsage(), ['used_memory', 'free_memory', 'wasted_memory']
        );

        $listing->opcache['opcache_statistics'] = Arr::only(
            $this->opcache->getStatistics(), ['num_cached_scripts', 'opcache_hit_rate', 'misses']
        );

        return $next($listing);
    }
}
