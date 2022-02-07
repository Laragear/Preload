<?php

namespace Laragear\Preload\Compiler\Pipes;

use Closure;
use Illuminate\Filesystem\Filesystem;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Listing;

class WritePreloadFile
{
    /**
     * Create a new pipe instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $files
     */
    public function __construct(protected Filesystem $files)
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
        if ($this->files->put($listing->path, $listing->output, true)) {
            return $next($listing);
        }

        throw new PreloadException("Couldn't write preload script to '$listing->path'.");
    }
}
