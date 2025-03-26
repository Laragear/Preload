<?php

namespace Tests\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Mockery\MockInterface;
use Tests\TestCase;

class StubTest extends TestCase
{
    public function test_stores_placeholder_in_output_path(): void
    {
        $path = $this->app->basePath();

        $this->mock(Filesystem::class, function (MockInterface $mock) use ($path) {
            $mock->expects('exists')->andReturnFalse();
            $mock->expects('put')->with($path, <<<'PHP'
<?php

\fwrite(\STDOUT, 'Info: This is a stub file to be replaced for the application at runtime.');

PHP
            );
        });

        $command = $this->artisan('preload:stub');

        $command->expectsOutput("Stub copied at [$path].");
        $command->expectsOutput('Remember to edit your [php.ini] file:');
        $command->expectsOutput("opcache.preload = $path");

        $command->assertSuccessful();
    }

    public function test_doesnt_overwrite_same_placeholder(): void
    {
        $this->mock(Filesystem::class, function (MockInterface $mock) {
            $mock->expects('exists')->andReturnTrue();
            $mock->expects('put')->never();
        });

        $this->artisan('preload:stub')
            ->expectsOutput('A preload script file already exists.')
            ->assertSuccessful();
    }
}
