<?php

namespace Laragear\Preload\Lister;

use Illuminate\Pipeline\Pipeline;

class Lister extends Pipeline
{
    /**
     * The array of class pipes.
     *
     * @var array
     */
    protected $pipes = [
        Pipes\LoadOpcacheConfig::class,
        Pipes\LoadAcceleratedFiles::class,
        Pipes\LoadPreloadConfig::class,
        Pipes\ExcludePreloadVariable::class,
        Pipes\MayScopeFilesToProjectPath::class,
        Pipes\MayExcludeExternalFiles::class,
        Pipes\SortScriptsByHitRatio::class,
        Pipes\CutListByMemoryLimit::class,
        Pipes\NormalizeList::class,
        Pipes\MayAppendExternalFiles::class,
        Pipes\FireEvent::class,
    ];
}
