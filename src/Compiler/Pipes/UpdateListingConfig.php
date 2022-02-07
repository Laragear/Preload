<?php

namespace Laragear\Preload\Compiler\Pipes;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Support\Facades\Config;
use Laragear\Preload\Listing;

class UpdateListingConfig
{
    /**
     * Create a new pipe instance.
     *
     * @param  \Illuminate\Contracts\Config\Repository  $config
     */
    public function __construct(protected ConfigContract $config)
    {
        //
    }

    /**
     * Handle the script generation.
     *
     * @param  \Laragear\Preload\Listing  $listing
     * @param  \Closure  $next
     * @return Listing
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        $listing->path = $this->config->get('preload.path');

        $listing->output = $listing->output->replace(...$this->listConfig($listing));

        return $next($listing);
    }

    /**
     * Returns a list of replaceable string with Preload data.
     *
     * @param  \Laragear\Preload\Listing  $listing
     * @return array
     */
    protected function listConfig(Listing $listing): array
    {
        return [
            [
                '@preloader_memory_limit',
                '@preloader_appended',
                '@preloader_excluded',
            ],
            [
                $listing->memory ? $listing->memory.' MB' : '(disabled)',
                $listing->appendCount,
                $listing->excludeCount,
            ]
        ];
    }
}
