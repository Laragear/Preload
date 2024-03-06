<?php

namespace Laragear\Preload\Events;

use Laragear\Preload\Listing;

class PreloadGenerated
{
    /**
     * Create a new event instance.
     */
    public function __construct(public Listing $listing)
    {
        //
    }
}
