<?php

namespace Laragear\Preload\Lister\Pipes\Concerns;

use Closure;
use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

trait RetrievesFilesFromFinder
{
    /**
     * Return an array of the files from the Finder.
     *
     * @param  \Closure(\Symfony\Component\Finder\Finder):void  $callback
     * @return \Illuminate\Support\Collection<array-key, string>
     *
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getFilesFromFinder(Closure $callback): Collection
    {
        $finder = $this->app->make(Finder::class);

        $callback($finder);

        return Collection::make($finder)->map(static function (SplFileInfo $file): string {
            return $file->getRealPath() ?: $file->getPath();
        });
    }
}
