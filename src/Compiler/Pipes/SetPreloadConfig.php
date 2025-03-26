<?php

namespace Laragear\Preload\Compiler\Pipes;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Listing;

use Laragear\Preload\Preloader;
use function now;
use function realpath;
use const DIRECTORY_SEPARATOR;

/**
 * @internal
 */
class SetPreloadConfig
{
    /**
     * Create a new pipe instance.
     */
    public function __construct(protected ConfigContract $config, protected Filesystem $files)
    {
        //
    }

    /**
     * Handle the script generation.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        if ($this->requiresMissingAutoload($path = $this->config->get('preload.autoload'))) {
            throw new PreloadException("Composer Autoloader is missing in [$path].");
        }

        $listing->preloader = $listing->preloader->replace(...$this->statistics());

        return $next($listing);
    }

    /**
     * Returns a list of replaceable string with statistical data.
     */
    protected function statistics(): array
    {
        return [
            [
                '@autoload',
                '@file',
                '@failure',
                '@mechanism',
            ],
            [
                $this->config->get('preload.use_require')
                    ? 'require_once \''.realpath($this->config->get('preload.autoloader')).'\';'
                    : null,
                $this->config->get('preload.path') . DIRECTORY_SEPARATOR . Preloader::NAME_LIST,
                $this->config->get('preload.ignore_not_found')
                    ? 'continue;'
                    : 'throw new \Exception("{$file} does not exist or is unreadable.");',
                $this->config->get('preload.use_require')
                    ? 'require_once $file'
                    : '\opcache_compile_file($file)',
            ],
        ];
    }

    /**
     * Check if the Composer Autoload is required and exists.
     */
    protected function requiresMissingAutoload(string $autoload): bool
    {
        return $this->config->get('preload.use_require')
            && $this->files->missing($autoload);
    }
}
