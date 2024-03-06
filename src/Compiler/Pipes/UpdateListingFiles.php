<?php

namespace Laragear\Preload\Compiler\Pipes;

use Closure;
use Laragear\Preload\Listing;

use const PHP_EOL;

class UpdateListingFiles
{
    /**
     * Handle the script generation.
     */
    public function handle(Listing $listing, Closure $next): Listing
    {
        $listing->output = $listing->output->replace('@list',
            PHP_EOL.
            '    '."'".$listing->files->implode("',".PHP_EOL."    '")."'".
            PHP_EOL
        );

        return $next($listing);
    }
}
