<?php

namespace Tests\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Laragear\Preload\Condition;
use Laragear\Preload\Facades\Preload;
use Laragear\Preload\Jobs\StorePreloadScript;
use Laragear\Preload\Listing;
use Tests\TestCase;

class PreloadMiddlewareTest extends TestCase
{
    public function test_creates_list_when_condition_is_true(): void
    {
        $bus = Bus::fake();

        Preload::shouldReceive('list')->once()->andReturn(
            $listing = new Listing(new Collection())
        );

        $this->mock(Condition::class)
            ->allows('shouldGenerate')
            ->once()
            ->withArgs(static function ($request, $response): bool {
                return $request instanceof Request
                    && $response instanceof Response;
            })
            ->andReturnTrue();

        $this->get('test')->assertOk();

        $bus->assertDispatched(StorePreloadScript::class, static function (StorePreloadScript $job) use ($listing): bool {
            static::assertSame($listing, $job->listing);
            return true;
        });
    }

    public function test_job_uses_custom_connection_and_queue(): void
    {
        $bus = Bus::fake();

        $this->app->make('config')->set('preload.job', [
            'connection' => 'foo',
            'queue' => 'bar',
        ]);

        Preload::shouldReceive('list')->once()->andReturn(new Listing(new Collection()));

        $this->mock(Condition::class)
            ->allows('shouldGenerate')
            ->once()
            ->withArgs(static function ($request, $response): bool {
                return $request instanceof Request
                    && $response instanceof Response;
            })
            ->andReturnTrue();

        $this->get('test')->assertOk();

        $bus->assertDispatched(StorePreloadScript::class, static function (StorePreloadScript $job): bool {
            static::assertSame('foo', $job->connection);
            static::assertSame('bar', $job->queue);

            return true;
        });
    }

    public function test_doesnt_create_list_when_condition_is_false(): void
    {
        $bus = Bus::fake();

        Preload::shouldReceive('list')->never();

        $this->mock(Condition::class)
            ->allows('shouldGenerate')
            ->once()
            ->withArgs(static function ($request, $response): bool {
                return $request instanceof Request
                    && $response instanceof Response;
            })
            ->andReturnFalse();

        $this->get('test')->assertOk();

        $bus->assertNotDispatched(StorePreloadScript::class);
    }

    public function test_doesnt_creates_list_if_response_not_successful(): void
    {
        $bus = Bus::fake();

        Preload::shouldReceive('list')->never();
        $this->mock(Condition::class)->allows('shouldGenerate')->never();

        $this->get('test_failed')->assertNotFound();

        $bus->assertNotDispatched(StorePreloadScript::class);
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        $app->make('config')->set('preload.enabled', true);
    }
}
