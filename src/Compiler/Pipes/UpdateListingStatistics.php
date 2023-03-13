<?php

namespace Laragear\Preload\Compiler\Pipes;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Support\Facades\File;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Listing;

use function now;
use function realpath;

class UpdateListingStatistics
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
        if ($this->autoloadMissing($path = $this->config->get('preload.autoload'))) {
            throw new PreloadException("Composer Autoloader is missing in '$path'.");
        }

        $listing->output = $listing->output->replace(...$this->statistics($listing));

        return $next($listing);
    }

    /**
     * Returns a list of replaceable string with statistical data.
     *
     * @param  Listing  $listing
     * @return array
     */
    protected function statistics(Listing $listing): array
    {
        return [
            [
                '@output',
                '@generated_at',
                '@autoload',
                '@failure',
                '@mechanism',
            ],
            [
                $listing->path,
                now()->toDateTimeString(),
                $this->config->get('preload.use_require')
                    ? 'require_once \''.realpath($this->config->get('preload.autoloader')).'\';'
                    : null,
                $this->config->get('preload.ignore_not_found') ? 'continue;' : 'throw new \Exception("{$file} does not exist or is unreadable.");',
                $this->config->get('preload.use_require') ? 'require_once $file' : 'opcache_compile_file($file)',
            ],
        ];
    }

    /**
     * Check if the Composer Autoload is required and exists.
     *
     * @param  string  $autoload
     * @return bool
     */
    protected function autoloadMissing(string $autoload): bool
    {
        return $this->config->get('preload.use_require')
            && File::missing($autoload);
    }
}
