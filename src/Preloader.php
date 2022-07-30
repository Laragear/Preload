<?php

namespace Laragear\Preload;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Support\Collection;
use function is_string;
use function resolve;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Preloader
{
    /**
     * The location of the preload script stub.
     *
     * @var string
     */
    public const STUB = __DIR__.'/../stubs/preload.php.stub';

    /**
     * The location of the placeholder preload stub.
     *
     * @var string
     */
    public const PLACEHOLDER = __DIR__.'/../stubs/preload.php.placeholder.stub';

    /**
     * Create a new Preload instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Laragear\Preload\Opcache  $opcache
     * @param  \Laragear\Preload\Lister\Lister  $lister
     * @param  \Laragear\Preload\Compiler\Compiler  $compiler
     * @param  array<int, string|\Closure(\Symfony\Component\Finder\Finder):void>  $append
     * @param  array<int, string|\Closure(\Symfony\Component\Finder\Finder):void>  $exclude
     */
    public function __construct(
        protected ConfigContract $config,
        protected Opcache $opcache,
        protected Lister\Lister $lister,
        protected Compiler\Compiler $compiler,
        protected array $append = [],
        protected array $exclude = [],
    ) {
        //
    }

    /**
     * Exclude files from the given paths.
     *
     * @param  \Closure|string  ...$exclude
     * @return void
     */
    public function exclude(Closure|string ...$exclude): void
    {
        $this->exclude = $this->normalizeListing($exclude);
    }

    /**
     * Append files from the given paths.
     *
     * @param  \Closure|string  ...$append
     * @return void
     */
    public function append(Closure|string ...$append): void
    {
        $this->append = $this->normalizeListing($append);
    }

    /**
     * Normalize the listing from the user.
     *
     * @param  array<string|\Closure>  $listing
     * @return \Closure[]
     */
    protected function normalizeListing(array $listing): array
    {
        foreach ($listing as $key => $list) {
            if (is_string($list)) {
                $listing[$key] = static function (Finder $finder) use ($list): void {
                    $finder->in($list)->name('*.php');
                };
            }
        }

        return $listing;
    }

    /**
     * Creates a new list.
     *
     * @return \Laragear\Preload\Listing
     */
    public function list(): Listing
    {
        $listing = new Listing(new Collection());

        $listing->exclude = $this->exclude;
        $listing->append = $this->append;

        return $this->lister->send($listing)->thenReturn();
    }

    /**
     * Writes a listing to the filesystem.
     *
     * @param  \Laragear\Preload\Listing|null  $listing
     * @return \Laragear\Preload\Listing
     */
    public function generate(Listing $listing = null): Listing
    {
        return $this->compiler->send($listing)->thenReturn();
    }

    /**
     * Return an array of the files from the Finder.
     *
     * @param  \Closure  $callback
     * @return \Illuminate\Support\Collection<string, string>
     */
    public function getFilesFromFinder(Closure $callback): Collection
    {
        $finder = resolve(Finder::class);

        $callback($finder);

        return Collection::make($finder)->map(static function (SplFileInfo $file): string {
            return $file->getRealPath();
        });
    }
}
