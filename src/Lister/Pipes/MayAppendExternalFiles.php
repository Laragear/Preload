<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use function max;

/**
 * @internal
 */
class MayAppendExternalFiles
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
        $count = $listing->files->count();

        foreach ($listing->include as $key => $include) {
            $listing->files = $listing->files->merge($this->getFilesFromFinder($include));
            unset($listing->include[$key]);
        }

        $listing->files = $listing->files->unique();

        $listing->includeCount = max(0, $listing->files->count() - $count);

        return $next($listing);
    }

}
