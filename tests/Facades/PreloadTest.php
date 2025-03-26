<?php

namespace Tests\Facades;

use Laragear\Preload\Condition;
use Laragear\Preload\Facades\Preload;
use Laragear\Preload\Preloader;
use Tests\TestCase;

class PreloadTest extends TestCase
{
    public function test_includes(): void
    {
        $arrs = ['foo', fn ($finder) => $finder->in('bar')];

        $this->mock(Preloader::class)->expects('include')->with($arrs);

        Preload::include($arrs);
    }

    public function test_excludes(): void
    {
        $arrs = ['foo', fn ($finder) => $finder->in('bar')];

        $this->mock(Preloader::class)->expects('exclude')->with($arrs);

        Preload::exclude($arrs);
    }

    public function test_condition(): void
    {
        $condition = fn () => true;

        $this->mock(Condition::class)->expects('use')->with($condition);

        Preload::use($condition);
    }
}
