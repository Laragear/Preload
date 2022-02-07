<?php

namespace Laragear\Preload;

use RuntimeException;
use function function_exists;

/**
 * Class Opcache
 *
 * This is just a class to enable mocking for testing.
 *
 * @internal
 */
class Opcache
{
    /**
     * Here we will save the Opcache status instead of retrieving it every time.
     *
     * @var array|false
     */
    protected array|false $status;

    /**
     * Get status information about the cache.
     *
     * @see https://www.php.net/manual/en/function.opcache-get-status.php
     * @return array
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
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->getStatus()['opcache_enabled'];
    }

    /**
     * Returns if Opcache is disabled.
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return ! $this->isEnabled();
    }

    /**
     * Returns the scripts used by Opcache.
     *
     * @return array
     */
    public function getScripts(): array
    {
        return $this->getStatus()['scripts'];
    }

    /**
     * Returns the memory usage of Opcache.
     *
     * @return array
     */
    public function getMemoryUsage(): array
    {
        return $this->getStatus()['memory_usage'];
    }

    /**
     * Return statistics of Opcache.
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return $this->getStatus()['opcache_statistics'];
    }

    /**
     * Returns the number of scripts cached.
     *
     * @return int
     */
    public function getNumberCachedScripts(): int
    {
        return $this->getStatus()['opcache_statistics']['num_cached_scripts'];
    }

    /**
     * Check if Opcache has any cached script.
     *
     * @return bool
     */
    public function cachedScriptsFilled(): bool
    {
        return (bool) $this->getNumberCachedScripts();
    }

    /**
     * Check if Opcache has no cached scripts.
     *
     * @return bool
     */
    public function cachedScriptsEmpty(): bool
    {
        return ! $this->cachedScriptsFilled();
    }

    /**
     * Returns the number of hits in Opcache.
     *
     * @return mixed
     */
    public function getHits(): int
    {
        return $this->getStatus()['opcache_statistics']['hits'];
    }
}
