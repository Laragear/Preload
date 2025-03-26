<?php

namespace Tests;

use Closure;
use Laragear\Preload\Compiler\Compiler;
use Laragear\Preload\Lister\Lister;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;
use Mockery;
use Symfony\Component\Finder\Finder;

class PreloaderTest extends TestCase
{
    protected function preloader(): Preloader
    {
        return $this->app->make(Preloader::class);
    }

    public function test_excludes(): void
    {
        /** @var \Closure $closure */
        $closure = null;

        $this->mock(Lister::class, static function (Mockery\MockInterface $mock) use (&$closure) {
            $mock->expects('send')->once()->withArgs(static function (Listing $listing) use (&$closure) {
                static::assertCount(2, $listing->exclude);
                static::assertInstanceOf(Closure::class, $listing->exclude[0]);
                static::assertInstanceOf(Closure::class, $listing->exclude[1]);

                $closure = $listing->exclude[0];

                return true;
            })->andReturnSelf();

            $mock->expects('thenReturn')->once()->andReturn(new Listing());
        });

        $this->preloader()->exclude(['foo', fn ($finder) => $finder->in('bar')]);

        $this->preloader()->files();

        $finder = Mockery::mock(Finder::class);
        $finder->expects('in')->with('foo')->andReturnSelf();
        $finder->expects('name')->with('*.php')->andReturnSelf();

        $closure($finder);
    }

    public function test_excludes_with_string(): void
    {
        /** @var \Closure $closure */
        $closure = null;

        $this->mock(Lister::class, static function (Mockery\MockInterface $mock) use (&$closure) {
            $mock->expects('send')->once()->withArgs(static function (Listing $listing) use (&$closure) {
                static::assertCount(1, $listing->exclude);
                static::assertInstanceOf(Closure::class, $listing->exclude[0]);

                $closure = $listing->exclude[0];

                return true;
            })->andReturnSelf();

            $mock->expects('thenReturn')->once()->andReturn(new Listing());
        });

        $this->preloader()->exclude('foo');

        $this->preloader()->files();

        $finder = Mockery::mock(Finder::class);
        $finder->expects('in')->with('foo')->andReturnSelf();
        $finder->expects('name')->with('*.php')->andReturnSelf();

        $closure($finder);
    }

    public function test_includes(): void
    {
        /** @var \Closure $closure */
        $closure = null;

        $this->mock(Lister::class, static function (Mockery\MockInterface $mock) use (&$closure) {
            $mock->expects('send')->once()->withArgs(static function (Listing $listing) use (&$closure) {
                static::assertCount(2, $listing->include);
                static::assertInstanceOf(Closure::class, $listing->include[0]);
                static::assertInstanceOf(Closure::class, $listing->include[1]);

                $closure = $listing->include[0];

                return true;
            })->andReturnSelf();

            $mock->expects('thenReturn')->once()->andReturn(new Listing());
        });

        $this->preloader()->include(['foo', fn ($finder) => $finder->in('bar')]);

        $this->preloader()->files();

        $finder = Mockery::mock(Finder::class);
        $finder->expects('in')->with('foo')->andReturnSelf();
        $finder->expects('name')->with('*.php')->andReturnSelf();

        $closure($finder);
    }

    public function test_includes_with_string(): void
    {
        /** @var \Closure $closure */
        $closure = null;

        $this->mock(Lister::class, static function (Mockery\MockInterface $mock) use (&$closure) {
            $mock->expects('send')->once()->withArgs(static function (Listing $listing)  use (&$closure) {
                static::assertCount(1, $listing->include);
                static::assertInstanceOf(Closure::class, $listing->include[0]);

                $closure = $listing->include[0];

                return true;
            })->andReturnSelf();

            $mock->expects('thenReturn')->once()->andReturn(new Listing());
        });

        $this->preloader()->include('foo');

        $this->preloader()->files();

        $finder = Mockery::mock(Finder::class);
        $finder->expects('in')->with('foo')->andReturnSelf();
        $finder->expects('name')->with('*.php')->andReturnSelf();

        $closure($finder);
    }

    public function test_save(): void
    {
        $listing = new Listing();

        $this->mock(Lister::class, static function (Mockery\MockInterface $mock) use ($listing): void {
            $mock->expects('send')->with(Mockery::type(Listing::class))->once()->andReturnSelf();
            $mock->expects('thenReturn')->andReturn($listing);
        });

        $this->mock(Compiler::class, static function (Mockery\MockInterface $mock) use ($listing): void {
            $mock->expects('send')->withArgs(static function (Listing $arg) use ($listing): bool {
                static::assertSame($listing, $arg);

                return true;
            })->andReturnSelf();

            $mock->expects('thenReturn')->andReturn($listing);
        });

        static::assertSame($listing, $this->preloader()->save());
    }

    public function test_save_with_custom_listing(): void
    {
        $listing = new Listing();

        $this->mock(Compiler::class, static function (Mockery\MockInterface $mock) use ($listing): void {
            $mock->expects('send')->withArgs(static function (Listing $arg) use ($listing): bool {
                static::assertSame($listing, $arg);

                return true;
            })->andReturnSelf();

            $mock->expects('thenReturn')->andReturn($listing);
        });

        static::assertSame($listing, $this->preloader()->save($listing));
    }
}
