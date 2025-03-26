<?php

namespace Laragear\Preload\Compiler\Pipes;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Support\DateFactory;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;

use const DIRECTORY_SEPARATOR;

/**
 * @internal
 */
class SetStatistics
{
    /**
     * Create a new pipe instance.
     */
    public function __construct(protected ConfigContract $config, protected DateFactory $date)
    {
        //
    }

    /**
     * Handle the script generation.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        $listing->statistics = $listing->statistics->replace(...$this->listConfig($listing));

        return $next($listing);
    }

    /**
     * Returns a list of replaceable string with Preload data.
     */
    protected function listConfig(Listing $listing): array
    {
        return [
            [
                '@generated_at',
                '@output',
                '@mechanism',
                '@preloader_included',
                '@preloader_excluded',
                '@preloader_memory_limit',
            ],
            [
                $this->date->now()->toDateTimeString(),
                $this->config->get('preload.path').DIRECTORY_SEPARATOR.Preloader::NAME_LIST,
                $this->config->get('preload.use_require') ? 'require_once' : 'opcache_compile_file',
                $listing->includeCount,
                $listing->excludeCount,
                $listing->memory ? $listing->memory.'MB' : '(disabled)',
            ],
        ];
    }
}
