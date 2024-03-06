<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Illuminate\Support\Collection;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Listing;
use Laragear\Preload\Opcache;

class LoadAcceleratedFiles
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
        $listing->files = new Collection($this->opcache->getScripts());

        if ($listing->files->isEmpty()) {
            throw new PreloadException('Opcache has no cached scripts.');
        }

        return $next($listing);
    }
}
