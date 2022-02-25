<?php

namespace Laragear\Preload;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\ServiceProvider;
use Laragear\Preload\Http\Middleware\PreloadMiddleware;

class PreloadServiceProvider extends ServiceProvider
{
    /**
     * Location of the package config.
     *
     * @var string
     */
    public const CONFIG = __DIR__.'/../config/preload.php';

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(static::CONFIG, 'preload');

        $this->app->singleton(Preloader::class);

        $this->app->singleton(Condition::class, static function (Application $app): Condition {
            return new Condition($app, Condition::countCondition());
        });
    }

    /**
     * Bootstrap the application services.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @param  \Illuminate\Contracts\Http\Kernel  $kernel
     * @return void
     */
    public function boot(Repository $config, Kernel $kernel): void
    {
        // We will only register the middleware if not Running Unit Tests
        if ($this->shouldRun($config)) {
            $kernel->pushMiddleware(PreloadMiddleware::class);
        }

        if ($this->app->runningInConsole()) {
            $this->publishes([static::CONFIG => $this->app->configPath('preload.php')], 'config');
            $this->commands(Console\Commands\Placeholder::class);
        }
    }

    /**
     * Checks if Preload should run.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return bool
     *
     * @codeCoverageIgnore
     */
    protected function shouldRun(Repository $config): bool
    {
        // If it's null run only on production, otherwise the developer decides.
        return $config->get('preload.enabled') ?? $this->app->environment('production');
    }
}
