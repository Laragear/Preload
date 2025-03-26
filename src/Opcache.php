<?php

namespace Laragear\Preload;

use RuntimeException;

use function function_exists;

/**
 * Class Opcache.
 *
 * This is just a class to enable mocking for testing.
 *
 * @internal
 */
class Opcache
{
    /**
     * Here we will save the Opcache status instead of retrieving it every time.
     */
    protected array|false $status;

    /**
     * Get status information about the cache.
     *
     * @see https://www.php.net/manual/en/function.opcache-get-status.php
     */
    public function getStatus(): array
    {
        if (! function_exists('opcache_get_status') || ! $this->status = \opcache_get_status(true)) {
            throw new RuntimeException(
                'Opcache is disabled or non-operative. Further reference: https://www.php.net/manual/en/opcache.configuration'
            );
        }

        return $this->status;
    }

    /**
     * Returns if Opcache is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->getStatus()['opcache_enabled'];
    }

    /**
     * Returns if Opcache is disabled.
     */
    public function isDisabled(): bool
    {
        return ! $this->isEnabled();
    }

    /**
     * Returns the scripts used by Opcache.
     *
     * @return array<string, array{hits: int, memory_consumption: int, last_used_timestamp: int}>
     */
    public function getScripts(): array
    {
        return $this->getStatus()['scripts'];
    }

    /**
     * Returns the memory usage of Opcache.
     *
     * @return array{used_memory: int, free_memory: int, wasted_memory: int}
     */
    public function getMemoryUsage(): array
    {
        return $this->getStatus()['memory_usage'];
    }

    /**
     * Return statistics of Opcache.
     *
     * @return array{num_cached_scripts: int, opcache_hit_rate: float, misses: int}
     */
    public function getStatistics(): array
    {
        return $this->getStatus()['opcache_statistics'];
    }
}
