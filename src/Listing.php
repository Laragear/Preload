<?php

namespace Laragear\Preload;

use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;

class Listing
{
    /**
     * Create a new Listing instance.
     *
     * @param  \Illuminate\Support\Collection<array-key,string|string[]>  $files
     * @param  (\Closure(\Symfony\Component\Finder\Finder):void)[]  $exclude
     * @param  (\Closure(\Symfony\Component\Finder\Finder):void)[]  $include
     * @param  string[]|array[]  $opcache
     */
    public function __construct(
        public array $exclude = [],
        public array $include = [],
        public Collection $files = new Collection,
        public int $excludeCount = 0,
        public int $includeCount = 0,
        public int $memory = 0,
        public Stringable $statistics = new Stringable,
        public Stringable $preloader = new Stringable,
        public array $opcache = [],
    ) {
        //
    }
}
