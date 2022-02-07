<?php

namespace Laragear\Preload\Compiler;

use Illuminate\Pipeline\Pipeline;

class Compiler extends Pipeline
{
    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [
        Pipes\LoadPreloadStub::class,
        Pipes\UpdateOpcacheConfig::class,
        Pipes\UpdateListingConfig::class,
        Pipes\UpdateListingStatistics::class,
        Pipes\UpdateListingFiles::class,
        Pipes\WritePreloadFile::class,
        Pipes\FireEvent::class,
    ];
}
