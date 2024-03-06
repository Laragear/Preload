<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Laragear\Preload\Listing;

class SortScriptsByHitRatio
{
    /**
     * Handle the incoming preload listing.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        $listing->files = $listing->files->sortByDesc(static function (array $file): array {
            return [$file['hits'], $file['last_used_timestamp']];
        });

        return $next($listing);
    }
}
