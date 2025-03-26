<?php

namespace Tests\Http\Middleware;

use Illuminate\Support\Facades\Bus;
use Laragear\Preload\Condition;
use Laragear\Preload\Jobs\StorePreloadScript;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;
use Tests\TestCase;

use function abort;

class PreloadMiddlewareTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app->make('config')->set('preload.enabled', true);
    }

    protected function defineRoutes($router): void
    {
        $router->get('test', static function (): string {
            return 'ok';
        });

        $router->get('test_fail', static function (): never {
            abort(404);
        });
    }

    protected function preload(): Preloader
    {
        return $this->app->make(Preloader::class);
    }

    public function test_creates_list_when_condition_is_true(): void
    {
        $bus = Bus::fake();

        $this->mock(Preloader::class)->expects('list')->once()->andReturn($listing = new Listing());

        $this->app->afterResolving(Condition::class, static function (Condition $condition) {
            $condition->use(fn () => true);
        });

        $this->get('test')->assertOk();

        $bus->assertDispatched(StorePreloadScript::class, static function (StorePreloadScript $job) use ($listing) {
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

        $this->mock(Preloader::class)->expects('list')->once()->andReturn(new Listing());
        $this->mock(Condition::class)->expects('__invoke')->once()->andReturnTrue();

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

        $this->mock(Preloader::class)->expects('list')->never();

        $this->mock(Condition::class)
            ->allows('__invoke')
            ->once()
            ->andReturnFalse();

        $this->get('test')->assertOk();

        $bus->assertNotDispatched(StorePreloadScript::class);
    }

    public function test_doesnt_creates_list_if_response_not_successful(): void
    {
        $bus = Bus::fake();

        $this->mock(Preloader::class)->expects('list')->never();
        $this->mock(Condition::class)->allows('__invoke')->never();

        $this->get('test_failed')->assertNotFound();

        $bus->assertNotDispatched(StorePreloadScript::class);
    }
}
