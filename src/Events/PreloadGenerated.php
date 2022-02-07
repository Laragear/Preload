<?php

namespace Laragear\Preload\Events;

use Laragear\Preload\Listing;

class PreloadGenerated
{
    /**
     * Create a new event instance.
     *
     * @param  \Laragear\Preload\Listing  $listing
     */
    public function __construct(public Listing $listing)
    {
        //
    }
}
