<?php

namespace Laragear\Preload;

use Closure;
use Illuminate\Contracts\Cache\Factory as CacheContract;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Condition
{
    /**
     * Create a new Condition instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @param  \Closure(\Illuminate\Http\Request, \Symfony\Component\HttpFoundation\Response, array):bool  $callback
     */
    public function __construct(protected Application $app, protected Closure $callback)
    {
        //
    }

    /**
     * Use a callback for the condition.
     *
     * @param  \Closure(\Illuminate\Http\Request, \Symfony\Component\HttpFoundation\Response, array):bool  $callback
     * @return void
     */
    public function use(Closure $callback): void
    {
        $this->callback = $callback;
    }

    /**
     * Check if the script should be generated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return bool
     */
    public function shouldGenerate(Request $request, Response $response): bool
    {
        return $this->app->call($this->callback, [
            'options' => $this->app->make('config')->get('preload.condition'),
            'request' => $request,
            'response' => $response,
        ]);
    }

    /**
     * Returns a condition callback based on requests count.
     *
     * Ensure your 'condition' array contains a 'store', 'key' and 'hits' keys.
     *
     * @return \Closure(array<int, midex>, \Illuminate\Contracts\Cache\Factory):bool
     */
    public static function countCondition(): Closure
    {
        return static function (array $options, CacheContract $cache): bool {
            // Increment the count by one. If it doesn't exist, we will start with 1.
            $count = $cache->store($options['store'])->increment($options['key']);

            // If the count is not equal to the number of hits, bail out.
            if ($count !== $options['hits']) {
                return false;
            }

            // Reset the hits back to zero.
            $cache->store($options['store'])->set($options['key'], 0);

            return true;
        };
    }
}
