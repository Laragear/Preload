<?php

namespace Laragear\Preload\Lister\Pipes;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Laragear\Preload\Composer;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Listing;
use Symfony\Component\Finder\Finder;
use function is_bool;

class LoadIncludedAndExcludedLibraries
{
    use Concerns\RetrievesFilesFromFinder;

    /**
     * Create a new pipe instance.
     */
    public function __construct(protected Application $app, protected Filesystem $files, protected Composer $composer)
    {
        //
    }

    /**
     * Handle the incoming preload listing.
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        $libraries = Arr::get($this->files->json($this->app->basePath('composer.json')), 'extra.preload', []);

        foreach ($libraries as $library => $preload) {
            if (!$path = $this->composer->getLibraryPath($library)) {
                throw new PreloadException("The library [$library] is not installed.");
            }

            if (is_bool($preload)) {
                $preload = [$preload ? 'include' : 'exclude' => $path];
            }

            foreach ($preload as $name => $paths) {
                $paths = Arr::wrap($paths);

                $listing->{$name}[] = static function (Finder $finder) use ($paths): void {
                    $finder->files()->in($paths);
                };
            }
        }

        return $next($listing);
    }
}
