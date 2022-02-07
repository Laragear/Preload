<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;
use function max;

class MayAppendExternalFiles
{
    /**
     * Create a new pipe instance.
     *
     * @param  \Laragear\Preload\Preloader  $preload
     */
    public function __construct(protected Preloader $preload)
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
        $count = $listing->files->count();

        foreach ($listing->append as $append) {
            $listing->files = $listing->files->merge($this->preload->getFilesFromFinder($append));
        }

        $listing->files = $listing->files->unique();

        $listing->appendCount = max(0, $listing->files->count() - $count);

        $listing->append = [];

        return $next($listing);
    }
}
