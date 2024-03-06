<?php

namespace Laragear\Preload\Compiler\Pipes;

use Closure;
use Laragear\Preload\Listing;

use function number_format;

class UpdateOpcacheConfig
{
    /**
     * Handle the script generation.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        $listing->output = $listing->output->replace(...$this->opcacheConfig($listing->opcache));

        return $next($listing);
    }

    /**
     * Returns a list of replaceable string with Opcache data.
     */
    protected function opcacheConfig(array $opcache): array
    {
        return [
            [
                '@opcache_memory_used',
                '@opcache_memory_free',
                '@opcache_memory_wasted',
                '@opcache_files',
                '@opcache_hit_rate',
                '@opcache_misses',
            ],
            [
                number_format($opcache['memory_usage']['used_memory'] / 1024 ** 2, 1, '.', ''),
                number_format($opcache['memory_usage']['free_memory'] / 1024 ** 2, 1, '.', ''),
                number_format($opcache['memory_usage']['wasted_memory'] / 1024 ** 2, 1, '.', ''),
                $opcache['opcache_statistics']['num_cached_scripts'],
                number_format($opcache['opcache_statistics']['opcache_hit_rate'], 2, '.', ''),
                $opcache['opcache_statistics']['misses'],
            ],
        ];
    }
}
