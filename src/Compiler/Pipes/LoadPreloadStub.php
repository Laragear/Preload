<?php

namespace Laragear\Preload\Compiler\Pipes;

use Closure;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Stringable;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;

class LoadPreloadStub
{
    /**
     * Create a new pipe instance.
     */
    public function __construct(protected Filesystem $files)
    {
        //
    }

    /**
     * Handle the script generation.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        $listing->output = new Stringable($this->stubContents());

        return $next($listing);
    }

    /**
     * Return the contents of the stub preload script file.
     *
     * @return string
     */
    protected function stubContents(): string
    {
        try {
            return $this->files->get(Preloader::STUB);
        } catch (FileNotFoundException $e) {
            throw new PreloadException('Cannot read the stub "'.Preloader::STUB.'" contents.', $e->getCode(), $e);
        }
    }
}
