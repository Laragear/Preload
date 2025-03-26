<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Laragear\Preload\Listing;

use function round;

/**
 * @internal
 */
class CutListByMemoryLimit
{
    /**
     * Create a new pipe instance.
     */
    public function __construct(protected Repository $config)
    {
        //
    }

    /**
     * Handle the incoming preload listing.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        if ($this->config->get('preload.memory') > 0) {
            $this->cutList($listing);
        }

        return $next($listing);
    }

    /**
     * Cut the listing until the memory threshold is set.
     */
    protected function cutList(Listing $listing): void
    {
        $limit = round($this->config->get('preload.memory') * 1024 ** 2);

        $listing->files = $listing->files->takeUntil(static function (array $file) use ($limit, &$memory): bool {
            $memory += $file['memory_consumption'];

            return $memory > $limit;
        });
    }
}
