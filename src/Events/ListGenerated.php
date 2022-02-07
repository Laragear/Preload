<?php

namespace Laragear\Preload\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Laragear\Preload\Listing;

class ListGenerated
{
    use Dispatchable;

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
