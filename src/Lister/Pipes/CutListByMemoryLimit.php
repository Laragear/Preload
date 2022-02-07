<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Laragear\Preload\Listing;
use function round;

class CutListByMemoryLimit
{
    /**
     * Handle the incoming preload listing.
     *
     * @param  \Laragear\Preload\Listing  $listing
     * @param  \Closure  $next
     * @return \Laragear\Preload\Listing
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        if ($listing->memory) {
            $this->cutList($listing);
        }

        return $next($listing);
    }

    /**
     * Cut the listing until the memory threshold is set.
     *
     * @param  Listing  $listing
     * @return void
     */
    protected function cutList(Listing $listing): void
    {
        $limit = (int) round($listing->memory * 1024**2);

        $listing->files = $listing->files->takeUntil(
            static function (array $file) use ($listing, $limit, &$memory): bool {
                $memory += $file['memory_consumption'];

                return $memory > $limit;
            }
        );
    }
}
