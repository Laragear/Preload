<?php

namespace Laragear\Preload\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Laragear\Preload\Condition;
use Laragear\Preload\Facades\Preload;
use Laragear\Preload\Jobs\StorePreloadScript;
use Symfony\Component\HttpFoundation\Response;

use function resolve;

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
            $config = Config::get([
                'preload.job.connection', 'preload.job.queue',
            ]);

            StorePreloadScript::dispatch(Preload::list())
                ->onConnection($config['preload.job.connection'])
                ->onQueue($config['preload.job.queue']);
        }
    }

    /**
     * Checks if the given condition logic is true or false.
     */
    protected function conditionIsTrue(Request $request, Response $response): bool
    {
        return resolve(Condition::class)->shouldGenerate($request, $response);
    }
}
