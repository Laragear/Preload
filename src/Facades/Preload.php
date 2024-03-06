<?php

namespace Laragear\Preload\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Laragear\Preload\Condition;
use Laragear\Preload\Preloader;

/**
 * @method static void exclude(\Closure|string ...$exclude)
 * @method static void append(\Closure|string ...$append)
 * @method static \Laragear\Preload\Listing list()
 * @method static \Laragear\Preload\Listing generate(\Laragear\Preload\Listing $listing = null)
 * @method static \Illuminate\Support\Collection getFilesFromFinder(\Closure $callback)
 * @method static \Laragear\Preload\Preloader getFacadeRoot()
 *
 * @see \Laragear\Preload\Preloader
 */
class Preload extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return Preloader::class;
    }

    /**
     * Determine if the preload list should be generated using a custom condition.
     *
     * @param  \Closure(\Illuminate\Http\Request, \Symfony\Component\HttpFoundation\Response, array):bool  $condition
     */
    public static function condition(Closure $condition): void
    {
        static::getFacadeApplication()->make(Condition::class)->use($condition);
    }
}
