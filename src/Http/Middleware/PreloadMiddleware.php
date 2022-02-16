<?php

namespace Laragear\Preload\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Laragear\Preload\Condition;
use Laragear\Preload\Facades\Preload;
use Laragear\Preload\Jobs\StorePreloadScript;
use function resolve;
use Symfony\Component\HttpFoundation\Response;

class PreloadMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        return $next($request);
    }

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function terminate(Request $request, Response $response)
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
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return bool
     */
    protected function conditionIsTrue(Request $request, Response $response): bool
    {
        return resolve(Condition::class)->shouldGenerate($request, $response);
    }
}
