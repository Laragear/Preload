<?php

namespace Laragear\Preload;

use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;

class Listing
{
    /**
     * Create a new Listing instance.
     *
     * @param  \Illuminate\Support\Collection<int|string,string|string[]>  $files
     * @param  bool  $projectOnly
     * @param  (\Closure(\Symfony\Component\Finder\Finder):void)[]  $exclude
     * @param  (\Closure(\Symfony\Component\Finder\Finder):void)[]  $append
     * @param  int  $excludeCount
     * @param  int  $appendCount
     * @param  int  $memory
     * @param  string  $path
     * @param  \Illuminate\Support\Stringable|null  $output
     * @param  string[]  $opcache
     */
    public function __construct(
        public Collection $files,
        public bool $projectOnly = true,
        public array $exclude = [],
        public array $append = [],
        public int $excludeCount = 0,
        public int $appendCount = 0,
        public int $memory = 0,
        public string $path = '',
        public ?Stringable $output = null,
        public array $opcache = [],
    ) {
        //
    }
}
