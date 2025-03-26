<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Laragear\Preload\Listing;

/**
 * @internal
 */
class MayExcludeFiles
{
    use Concerns\RetrievesFilesFromFinder;

    /**
     * Create a new pipe instance.
     */
    public function __construct(protected Application $app)
    {
        //
    }

    /**
     * Handle the incoming preload listing.
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        foreach ($listing->exclude as $key => $exclude) {
            $excluded = $this->getFilesFromFinder($exclude)->flip();

            $listing->excludeCount += $excluded->count();

            $listing->files = $listing->files->diffKeys($excluded);

            unset($listing->exclude[$key]);
        }

        return $next($listing);
    }
}
