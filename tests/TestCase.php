<?php

namespace Tests;

use Illuminate\Contracts\Foundation\Application;
use Laragear\Preload\Facades\Preload;
use Laragear\Preload\PreloadServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            PreloadServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Preload' => Preload::class,
        ];
    }
}
