<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Str;
use Laragear\Preload\Listing;

/**
 * @internal
 */
class MayScopeFilesToProjectPath
{
    /**
     * The base application path.
     *
     * @var string
     */
    protected string $basePath;

    /**
     * Create a new pipe instance.
     */
    public function __construct(Application $app, protected Repository $config)
    {
        $this->basePath = $app->basePath();
    }

    /**
     * Handle the incoming preload listing.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        if ($this->config->get('preload.project_only')) {
            $this->removeNonProjectFiles($listing);
        }

        return $next($listing);
    }

    /**
     * Removes all files that are not inside the project base path.
     */
    protected function removeNonProjectFiles(Listing $listing): void
    {
        $listing->files = $listing->files->filter(function (array $file, string $key): bool {
            return Str::startsWith($key, $this->basePath);
        });
    }
}
