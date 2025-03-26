<?php

namespace Laragear\Preload\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Laragear\Preload\Condition;
use Laragear\Preload\Preloader;

/**
 * @method static \Laragear\Preload\Listing list()
 * @method static \Laragear\Preload\Listing save(\Laragear\Preload\Listing|null $listing = null)
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
     * Exclude files from the given paths.
     *
     * @param  (\Closure(\Symfony\Component\Finder\Finder):void)|string|string[]  $exclude
     */
    public static function exclude(Closure|string|array $exclude): void
    {
        static::getFacadeApplication()->extend(
            static::getFacadeAccessor(),
            static function (Preloader $preloader) use ($exclude): Preloader {
                $preloader->exclude($exclude);

                return $preloader;
            },
        );
    }

    /**
     * Append files from the given paths.
     *
     * @param  (\Closure(\Symfony\Component\Finder\Finder):void)|string|string[]  $include
     */
    public static function include(Closure|string|array $include): void
    {
        static::getFacadeApplication()->extend(
            static::getFacadeAccessor(),
            static function (Preloader $preloader) use ($include): Preloader {
                $preloader->include($include);

                return $preloader;
            },
        );
    }

    /**
     * Determine if the preload list should be generated using a custom condition.
     */
    public static function use(callable $condition): void
    {
        static::getFacadeApplication()->extend(
            Condition::class,
            static function (Condition $instance) use ($condition): Condition {
                $instance->use($condition);

                return $instance;
            },
        );
    }
}
