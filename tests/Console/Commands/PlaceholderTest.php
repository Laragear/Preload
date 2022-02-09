<?php

namespace Tests\Console\Commands;

use Illuminate\Support\Facades\File;
use Laragear\Preload\Preloader;
use Tests\TestCase;

class PlaceholderTest extends TestCase
{
    public function test_stores_placeholder_in_output_path(): void
    {
        $path = base_path('preload.php');

        File::expects('exists')->andReturnFalse();
        File::expects('put')->with($path, null)->andReturnNull();
        File::expects('get')->with(Preloader::PLACEHOLDER)->andReturnNull();

        $command = $this->artisan('preload:placeholder');

        $command->expectsOutput("Generating a preload placeholder at: $path");
        $command->expectsOutput('Empty preload stub generated');
        $command->expectsOutput('Remember to edit your [php.ini] file:');
        $command->expectsOutput("opcache.preload = '$path';");

        $command->assertSuccessful();
    }

    public function test_doesnt_overwrite_same_placeholder(): void
    {
        File::expects('exists')->andReturnTrue();

        File::expects('hash')->with(base_path('preload.php'))->andReturn('foo');
        File::expects('hash')->with(Preloader::PLACEHOLDER)->andReturn('foo');

        File::expects('put')->never();
        File::expects('get')->never();

        $command = $this->artisan('preload:placeholder');

        $command->expectsOutput('A placeholder preload script already exists, no need to generate it again.');

        $command->assertSuccessful();
    }

    public function test_doesnt_overwrites_live_preload_list(): void
    {
        File::expects('exists')->andReturnTrue();

        File::expects('hash')->with(base_path('preload.php'))->andReturn('foo');
        File::expects('hash')->with(Preloader::PLACEHOLDER)->andReturn('bar');

        File::expects('put')->never();
        File::expects('get')->never();

        $command = $this->artisan('preload:placeholder');

        $command->expectsQuestion('Seems there is already a preload file at the location. Overwrite?', false);
        $command->expectsOutput('A preload script already exists, skipping.');

        $command->assertSuccessful();
    }

    public function test_overwrites_live_preload_list(): void
    {
        File::expects('exists')->andReturnTrue();

        File::expects('hash')->with(base_path('preload.php'))->andReturn('foo');
        File::expects('hash')->with(Preloader::PLACEHOLDER)->andReturn('bar');

        File::expects('put')->with(base_path('preload.php'), null)->andReturnNull();
        File::expects('get')->with(Preloader::PLACEHOLDER)->andReturnNull();

        $command = $this->artisan('preload:placeholder');

        $command->expectsQuestion('Seems there is already a preload file at the location. Overwrite?', true);

        $command->assertSuccessful();
    }

    public function test_overwrites_live_preload_list_with_force(): void
    {
        File::expects('exists')->andReturnTrue();

        File::expects('hash')->with(base_path('preload.php'))->andReturn('foo');
        File::expects('hash')->with(Preloader::PLACEHOLDER)->andReturn('bar');

        File::expects('put')->with(base_path('preload.php'), null)->andReturnNull();
        File::expects('get')->with(Preloader::PLACEHOLDER)->andReturnNull();

        $command = $this->artisan('preload:placeholder --force');

        $command->assertSuccessful();
    }
}
