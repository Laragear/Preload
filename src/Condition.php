<?php

namespace Laragear\Preload;

use Closure;
use Illuminate\Contracts\Foundation\Application as ApplicationContract;
use Illuminate\Support\Lottery;

class Condition
{
    /**
     * Create a new Condition instance.
     */
    public function __construct(protected ApplicationContract $app, protected Closure $callback)
    {
        //
    }

    /**
     * Check if the script should be generated.
     */
    public function __invoke(): bool
    {
        $result = $this->app->call($this->callback);

        return (bool) ($result instanceof Lottery ? $result->choose() : $result);
    }

    /**
     * Use a callback for the condition.
     */
    public function use(callable $callback): void
    {
        $this->callback = $callback(...);
    }
}
