<?php

namespace Laragear\Preload\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laragear\Preload\Condition;
use Laragear\Preload\Jobs\StorePreloadScript;
use Laragear\Preload\Preloader;
use Symfony\Component\HttpFoundation\Response;
use function app;

/**
 * @internal
 */
class PreloadMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        return $next($request);
    }

    /**
     * Perform any final actions for the request lifecycle.
     */
    public function terminate(Request $request, Response $response): void
    {
        if ($response->isSuccessful() && $this->conditionIsTrue($request, $response)) {
            $app = app();

            [
                'preload.job.connection' => $connection,
                'preload.job.queue' => $queue,
            ] = $app->make('config')->getMany(['preload.job.connection', 'preload.job.queue',]);

            StorePreloadScript::dispatch($app->make(Preloader::class)->list())
                ->onConnection($connection)
                ->onQueue($queue);
        }
    }

    /**
     * Checks if the given condition logic is true or false.
     */
    protected function conditionIsTrue(Request $request, Response $response): bool
    {
        return app()->call(Condition::class, [
            'request' => $request,
            'response' => $response,
        ]);
    }
}
