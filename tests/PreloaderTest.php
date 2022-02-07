<?php

namespace Tests;

use ArrayIterator;
use Illuminate\Support\Collection;
use Laragear\Preload\Compiler\Compiler;
use Laragear\Preload\Facades\Preload;
use Laragear\Preload\Lister\Lister;
use Laragear\Preload\Listing;
use Mockery;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class PreloaderTest extends TestCase
{
    public function test_adds_to_exclusion(): void
    {
        $lister = $this->mock(Lister::class);

        $lister->allows('send')->once()->withArgs(static function (Listing $listing): bool {
            static::assertCount(2, $listing->exclude);
            return true;
        })->andReturnSelf();

        $lister->allows('thenReturn')->once()->andReturn(new Listing(new Collection(['foo', 'bar'])));

        Preload::exclude('foo', function ($finder) {
            $finder->in('bar');
        });

        $list = Preload::list();

        static::assertSame(['foo', 'bar'], $list->files->all());
    }
    public function test_adds_to_append(): void
    {
        $lister = $this->mock(Lister::class);

        $lister->allows('send')->once()->withArgs(static function (Listing $listing): bool {
            static::assertCount(2, $listing->append);
            return true;
        })->andReturnSelf();

        $lister->allows('thenReturn')->once()->andReturn(new Listing(new Collection(['foo', 'bar'])));

        Preload::append('foo', function ($finder) {
            $finder->in('bar');
        });

        $list = Preload::list();

        static::assertSame(['foo', 'bar'], $list->files->all());
    }

    public function test_generates_from_list(): void
    {
        $compiler = $this->mock(Compiler::class);

        $listing = new Listing(new Collection(['foo']));

        $compiler->allows('send')->withArgs(static function (Listing $arg) use ($listing): bool {
            static::assertSame($listing, $arg);

            return true;
        })->andReturnSelf();

        $compiler->allows('thenReturn')->andReturn($listing);

        static::assertSame($listing, Preload::generate($listing));
    }

    public function test_gets_preloading_files_from_finder_callback(): void
    {
        $callback = static function (Finder $finder): void {
            $finder->in('foo')->name('bar');
        };

        $finder = $this->mock(Finder::class);

        $finder->allows('in')->with('foo')->andReturnSelf();
        $finder->allows('name')->with('bar')->andReturnSelf();

        $file = Mockery::mock(SplFileInfo::class);
        $file->allows('getRealPath')->once()->andReturn('baz.php');

        $finder->allows('getIterator')->andReturn(new ArrayIterator([$file]));

        $files = Preload::getFilesFromFinder($callback);

        static::assertSame(['baz.php'], $files->all());
    }
}
