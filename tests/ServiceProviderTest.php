<?php

namespace Tests;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Laragear\Preload\Condition;
use Laragear\Preload\Console\Commands\Placeholder;
use Laragear\Preload\Http\Middleware\PreloadMiddleware;
use Laragear\Preload\Preloader;
use Laragear\Preload\PreloadServiceProvider;

class ServiceProviderTest extends TestCase
{
    public function test_merges_config(): void
    {
        static::assertSame(
            File::getRequire(__DIR__ . '/../config/preload.php'),
            Config::get('preload')
        );
    }

    public function test_registers_preload(): void
    {
        static::assertTrue($this->app->has(Preloader::class));
    }

    public function test_registers_condition(): void
    {
        static::assertTrue($this->app->has(Condition::class));
    }

    public function test_doesnt_registers_global_middleware_on_testing(): void
    {
        static::assertSame('testing', $this->app->environment());
        static::assertFalse(
            app(Kernel::class)->hasMiddleware(PreloadMiddleware::class)
        );
    }

    /**
     * @define-env usesProductionEnvironment
     */
    public function test_registers_global_middleware_on_production(): void
    {
        static::assertTrue(
            app(Kernel::class)->hasMiddleware(PreloadMiddleware::class)
        );
    }

    protected function usesProductionEnvironment(Application $app)
    {
        $app['env'] = 'production';
    }

    /**
     * @define-env setConfigEnableTrue
     */
    public function test_registers_global_middleware_when_config_is_true(): void
    {
        static::assertTrue(
            app(Kernel::class)->hasMiddleware(PreloadMiddleware::class)
        );
    }

    protected function setConfigEnableTrue(Application $app): void
    {
        $app->make('config')->set('preload.enabled', true);
    }

    public function test_registers_command(): void
    {
        static::assertArrayHasKey('preload:placeholder', Artisan::all());
    }

    public function test_publishes_config(): void
    {
        static::assertContains(PreloadServiceProvider::class, ServiceProvider::publishableProviders());

        static::assertArrayHasKey(
            PreloadServiceProvider::CONFIG, ServiceProvider::pathsToPublish(PreloadServiceProvider::class)
        );

        static::assertContains(
            config_path('preload.php'), ServiceProvider::pathsToPublish(PreloadServiceProvider::class)
        );
    }
}
