<?php

namespace Tests\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Laragear\Preload\Preloader;
use Mockery\MockInterface;
use Tests\TestCase;

class StubTest extends TestCase
{
    public function test_stores_placeholder_in_output_path(): void
    {
        $dir = $this->app->basePath();
        $filePath = $dir.'/'.Preloader::NAME_PRELOAD;

        $this->mock(Filesystem::class, function (MockInterface $mock) use ($dir, $filePath) {
            $mock->expects('ensureDirectoryExists')->with($dir);
            $mock->expects('exists')->with($filePath)->andReturnFalse();
            $mock->expects('put')->with($filePath, <<<'PHP'
<?php

$date = (new DateTime())->format('d-m-Y H:i:s');

echo "[$date] Info: This is a preload stub file to be replaced for the application at runtime.";

PHP
            );
        });

        $command = $this->artisan('preload:stub');

        $command->expectsOutput("Stub copied at [$filePath].");
        $command->expectsOutput('Remember to edit your [php.ini] file:');
        $command->expectsOutput("opcache.preload = $filePath");

        $command->assertSuccessful();
    }

    public function test_doesnt_overwrite_same_placeholder(): void
    {
        $this->mock(Filesystem::class, function (MockInterface $mock) {
            $mock->expects('ensureDirectoryExists');
            $mock->expects('exists')->andReturnTrue();
            $mock->expects('put')->never();
        });

        $this->artisan('preload:stub')
            ->expectsOutput('A preload script file already exists.')
            ->assertSuccessful();
    }
}
