<?php

namespace Laragear\Preload;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Support\Arr;
use Symfony\Component\Finder\Finder;

class Preloader
{
    /**
     * The filename for the statistics file.
     *
     * @const string
     */
    public const string NAME_STATISTICS = 'statistics.md';

    /**
     * The filename for the preload script file.
     *
     * @const string
     */
    public const string NAME_PRELOAD = 'preload.php';

    /**
     * The location of the statistic file.
     *
     * @const string
     */
    public const string STUB_STATISTICS = __DIR__.'/../stubs/'.self::NAME_STATISTICS;

    /**
     * The location of the preload script stub.
     */
    public const string STUB_PRELOAD = __DIR__.'/../stubs/'.self::NAME_PRELOAD.'.stub';

    /**
     * The filename of the list of preload files.
     *
     * @const string
     */
    public const string NAME_LIST = 'list.txt';

    /**
     * Create a new Preload instance.
     *
     * @param  array<string|\Closure(\Symfony\Component\Finder\Finder):void>  $include
     * @param  array<string|\Closure(\Symfony\Component\Finder\Finder):void>  $exclude
     */
    public function __construct(
        protected ConfigContract $config,
        protected Opcache $opcache,
        protected Lister\Lister $lister,
        protected Compiler\Compiler $compiler,
        protected array $include = [],
        protected array $exclude = [],
    ) {
        //
    }

    /**
     * Exclude files from the given paths.
     *
     * @param  (\Closure(\Symfony\Component\Finder\Finder):void)|string|string[]  $exclude
     */
    public function exclude(Closure|string|array $exclude): void
    {
        $this->exclude = $this->normalizeListing($exclude);
    }

    /**
     * Append files from the given paths.
     *
     * @param  (\Closure(\Symfony\Component\Finder\Finder):void)|string|string[]  $append
     */
    public function include(Closure|string|array $append): void
    {
        $this->include = $this->normalizeListing($append);
    }

    /**
     * Normalize the listing from the user.
     *
     * @param  (\Closure(\Symfony\Component\Finder\Finder):void)|(\Closure(\Symfony\Component\Finder\Finder):void)[]|string|string[]  $files
     * @return (\Closure(\Symfony\Component\Finder\Finder):void)[]
     */
    protected function normalizeListing(Closure|string|array $files): array
    {
        $files = Arr::wrap($files);

        foreach ($files as $key => $list) {
            if (! $list instanceof Closure) {
                $files[$key] = static function (Finder $finder) use ($list): void {
                    $finder->in($list)->name('*.php');
                };
            }
        }

        return $files;
    }

    /**
     * Creates a new list.
     */
    public function files(): Listing
    {
        return $this->lister->send(new Listing($this->exclude, $this->include))->thenReturn();
    }

    /**
     * Writes a listing to the filesystem.
     */
    public function save(?Listing $listing = null): Listing
    {
        return $this->compiler->send($listing ?? $this->files())->thenReturn();
    }
}
