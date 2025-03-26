<?php

namespace Tests;

use Closure;
use Laragear\Preload\Compiler\Compiler;
use Laragear\Preload\Lister\Lister;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;
use Mockery;

class PreloaderTest extends TestCase
{
    protected function preloader(): Preloader
    {
        return $this->app->make(Preloader::class);
    }

    public function test_excludes(): void
    {
        $this->mock(Lister::class, static function (Mockery\MockInterface $mock) {
            $mock->expects('send')->once()->withArgs(static function (Listing $listing): bool {
                static::assertCount(2, $listing->exclude);
                static::assertInstanceOf(Closure::class, $listing->exclude[0]);
                static::assertInstanceOf(Closure::class, $listing->exclude[1]);

                return true;
            })->andReturnSelf();

            $mock->expects('thenReturn')->once()->andReturn(new Listing());
        });

        $this->preloader()->exclude(['foo', fn ($finder) => $finder->in('bar')]);

        $this->preloader()->files();
    }

    public function test_excludes_with_string(): void
    {
        $this->mock(Lister::class, static function (Mockery\MockInterface $mock) {
            $mock->expects('send')->once()->withArgs(static function (Listing $listing): bool {
                static::assertCount(1, $listing->exclude);
                static::assertInstanceOf(Closure::class, $listing->exclude[0]);

                return true;
            })->andReturnSelf();

            $mock->expects('thenReturn')->once()->andReturn(new Listing());
        });

        $this->preloader()->exclude('foo');

        $this->preloader()->files();
    }

    public function test_includes(): void
    {
        $this->mock(Lister::class, static function (Mockery\MockInterface $mock) {
            $mock->expects('send')->once()->withArgs(static function (Listing $listing): bool {
                static::assertCount(2, $listing->include);
                static::assertInstanceOf(Closure::class, $listing->include[0]);
                static::assertInstanceOf(Closure::class, $listing->include[1]);

                return true;
            })->andReturnSelf();

            $mock->expects('thenReturn')->once()->andReturn(new Listing());
        });

        $this->preloader()->include(['foo', fn ($finder) => $finder->in('bar')]);

        $this->preloader()->files();
    }

    public function test_includes_with_string(): void
    {
        $this->mock(Lister::class, static function (Mockery\MockInterface $mock) {
            $mock->expects('send')->once()->withArgs(static function (Listing $listing): bool {
                static::assertCount(1, $listing->include);
                static::assertInstanceOf(Closure::class, $listing->include[0]);

                return true;
            })->andReturnSelf();

            $mock->expects('thenReturn')->once()->andReturn(new Listing());
        });

        $this->preloader()->include('foo');

        $this->preloader()->files();
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
