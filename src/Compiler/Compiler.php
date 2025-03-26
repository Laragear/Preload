<?php

namespace Laragear\Preload\Compiler;

use Illuminate\Pipeline\Pipeline;

/**
 * @internal
 */
class Compiler extends Pipeline
{
    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [
        Pipes\EnsureDirectoryExists::class,

        Pipes\WriteListFile::class,

        Pipes\LoadStatisticsStub::class,
        Pipes\SetStatistics::class,
        Pipes\SetOpcacheConfig::class,
        Pipes\WriteStatisticsFile::class,

        Pipes\LoadPreloaderStub::class,
        Pipes\SetPreloadConfig::class,
        Pipes\WritePreloaderFile::class,

        Pipes\FireEvent::class,
    ];
}
