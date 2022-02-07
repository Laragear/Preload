<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Illuminate\Support\Str;
use Laragear\Preload\Listing;

class MayScopeFilesToProjectPath
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
        if ($listing->projectOnly) {
            $this->removeNonProjectFiles($listing);
        }

        return $next($listing);
    }

    /**
     * Removes all files that are not inside the project base path.
     *
     * @param  \Laragear\Preload\Listing  $listing
     * @return void
     */
    protected function removeNonProjectFiles(Listing $listing)
    {
        $listing->files = $listing->files->filter(static function (array $file, string $key): bool {
            return Str::startsWith($key, base_path());
        });
    }
}
