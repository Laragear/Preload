<?php

namespace Laragear\Preload\Compiler\Pipes;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;

use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

/**
 * @internal
 */
class WriteListFile
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
        $path = $this->config->get('preload.path').DIRECTORY_SEPARATOR.Preloader::NAME_LIST;

        /** @var \Illuminate\Support\Collection<int, string> $files */
        foreach ($listing->files->chunk(500) as $files) {
            if (! $this->files->put($path, $files->implode(PHP_EOL).PHP_EOL, true)) {
                throw new PreloadException("Couldn't write list file to [$path].");
            }
        }

        $listing->files = new Collection();

        return $next($listing);
    }
}
