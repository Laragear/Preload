<?php

namespace Laragear\Preload\Compiler\Pipes;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Stringable;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;

use const DIRECTORY_SEPARATOR;

/**
 * @internal
 */
class WriteStatisticsFile
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
        $path = $this->config->get('preload.path').DIRECTORY_SEPARATOR.Preloader::NAME_STATISTICS;

        if ($this->files->put($path, $listing->statistics, true)) {
            $listing->statistics = new Stringable;

            return $next($listing);
        }

        throw new PreloadException("Couldn't write statistics file to [$path].");
    }
}
