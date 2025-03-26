<?php

namespace Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Fluent;
use Laragear\Preload\Condition;
use Laragear\Preload\Facades\Preload;
use Laragear\Preload\Opcache;

class ConditionTest extends TestCase
{
    protected function condition(): Condition
    {
        return $this->app->make(Condition::class);
    }

    public function test_default_condition_count_ten_thousand_requests(): void
    {
        $cache = $this->app->make('cache');

        $cache->forever('laragear.preload.count', 9999);

        static::assertFalse(($this->condition())());

        static::assertTrue(($this->condition())());
    }

    public function test_allows_custom_condition(): void
    {
        $called = false;

        $this->condition()->use(function () use (&$called) {
            $called = true;

            return true;
        });

        $result = ($this->condition())();

        static::assertTrue($result);
        static::assertTrue($called);
    }

    public function test_condition_is_resolved_by_container(): void
    {
        $this->app->instance(Fluent::class, $fluent = new Fluent());

        $this->condition()->use(function (Fluent $instance) use ($fluent) {
            static::assertSame($fluent, $instance);

            return true;
        });

        ($this->condition())();
    }
}
