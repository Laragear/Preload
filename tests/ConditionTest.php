<?php

namespace Tests;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Laragear\Preload\Condition;
use Laragear\Preload\Facades\Preload;
use Laragear\Preload\Opcache;

class ConditionTest extends TestCase
{
    protected Condition $condition;

    protected function setUp(): void
    {
        parent::setUp();

        $this->condition = $this->app->make(Condition::class);
    }

    public function test_default_condition_count_ten_thousand_requests(): void
    {
        Cache::put('preload|request_count', 9998);

        static::assertFalse($this->condition->shouldGenerate(new Request(), new Response()));
        static::assertTrue($this->condition->shouldGenerate(new Request(), new Response()));
    }

    public function test_allows_custom_condition(): void
    {
        $called = false;

        $condition = function () use (&$called) {
            $called = true;
            return true;
        };

        Preload::condition($condition);

        static::assertTrue($this->condition->shouldGenerate(new Request(), new Response()));
        static::assertTrue($called);
    }

    public function test_condition_is_resolved_by_service_container(): void
    {
        $this->app->singleton(Opcache::class, function () {
            return new class extends Opcache
            {
                public string $ok = 'ok';
            };
        });

        $condition = function (Opcache $opcache) {
            return $opcache->ok === 'ok';
        };

        Preload::condition($condition);

        static::assertTrue($this->condition->shouldGenerate(new Request(), new Response()));
    }
}
