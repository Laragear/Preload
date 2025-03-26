<?php

namespace Laragear\Preload\Compiler\Pipes;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Listing;

class EnsureDirectoryExists
{
    /**
     * Create a new pipe instance.
     */
    public function __construct(protected Filesystem $files, protected Repository $config)
    {
        //
    }

    /**
     * Handle the script generation.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        $path = $this->config->get('preload.path');

        $this->files->ensureDirectoryExists($path);

        if (!$this->files->isWritable($path)) {
            throw new PreloadException("The path [$path] is not writable.");
        }

        return $next($listing);
    }
}
