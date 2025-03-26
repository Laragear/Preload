<?php

namespace Laragear\Preload\Lister;

use Illuminate\Pipeline\Pipeline;

/**
 * @internal
 */
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
        Pipes\LoadIncludedAndExcludedLibraries::class,
        Pipes\ExcludePreloadVariable::class,
        Pipes\MayScopeFilesToProjectPath::class,
        Pipes\MayExcludeFiles::class,
        Pipes\SortScriptsByHitRatio::class,
        Pipes\CutListByMemoryLimit::class,
        Pipes\NormalizeList::class,
        Pipes\MayIncludeFiles::class,
        Pipes\FireEvent::class,
    ];
}
