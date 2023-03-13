<?php

namespace Tests\Compiler;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Stringable;
use Laragear\Preload\Compiler\Compiler;
use Laragear\Preload\Events\PreloadGenerated;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Facades\Preload;
use Laragear\Preload\Listing;
use Laragear\Preload\Preloader;
use Tests\MocksOpcache;
use Tests\TestCase;

class CompilerTest extends TestCase
{
    use MocksOpcache;

    protected Compiler $compiler;
    protected Listing $list;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler = $this->app->make(Compiler::class);

        $this->mockOpcache();

        $this->list = Preload::list();
    }

    protected function getFilesAsString(): string
    {
        return PHP_EOL.
            '    '."'".$this->list->files->implode("',".PHP_EOL."    '")."'".
            PHP_EOL;
    }

    public function test_generates_script_in_path(): void
    {
        $event = Event::fake();

        $this->travelTo(Carbon::create(2020, 01, 01, 16, 30, 15));

        File::shouldReceive('get')->with(Preloader::STUB)
            ->andReturn(file_get_contents(Preloader::STUB));

        File::shouldReceive('put')
            ->withArgs(function (string $path, Stringable $script, bool $lock): bool {
                static::assertSame(base_path('preload.php'), $path);
                static::assertTrue($lock);

                $files = $this->getFilesAsString();

                static::assertEquals(<<<SCRIPT
<?php
/**
 * Preloader Script
 *
 * This file is generated automatically by the Laragear Preload package.
 *
 * The following script uses `opcache_compile_file(\$file)` syntax to preload each file in this list into Opcache.
 * To full enable preload, add this file to your `php.ini` in `opcache.preload` key to preload
 * this list of files PHP at startup. This file also includes some information about Opcache.
 *
 * Add (or update) this line in `php.ini`:
 *
 *     opcache.preload=$path
 *
 * Server restart is not required.
 *
 * --- Config ---
 * Generated at: 2020-01-01 16:30:15
 * Opcache
 *     - Used Memory: 48.0 MB
 *     - Free Memory: 80.0 MB
 *     - Wasted Memory: 0.0 MB
 *     - Cached files: 4321
 *     - Hit rate: 60.00%
 *     - Misses: 1234
 * Preloader config
 *     - Memory limit: 32 MB
 *     - Files excluded: 0
 *     - Files appended: 0
 *
 *
 * For more information:
 * @see https://github.com/laragear/preload
 */



\$files = [$files];

foreach (\$files as \$file) {
    try {
        if (!(\is_file(\$file) && \is_readable(\$file))) {
            continue;
        }
        opcache_compile_file(\$file);
    } catch (\Throwable \$e) {
        echo 'Preloader Script has stopped with an error' . \PHP_EOL .
             'Message: ' . \$e->getMessage() . \PHP_EOL .
             'File: ' . \$file . \PHP_EOL;

        throw \$e;
    }
}


SCRIPT
                    , $script);

                return true;
            })
            ->andReturnTrue();

        $this->compiler->send($this->list)->thenReturn();

        $event->assertDispatched(PreloadGenerated::class, function (PreloadGenerated $event): bool {
            static::assertSame($this->list, $event->listing);

            return true;
        });
    }

    public function test_informs_no_memory_limit(): void
    {
        $this->list->memory = 0;

        File::shouldReceive('get')->with(Preloader::STUB)
            ->andReturn(file_get_contents(Preloader::STUB));

        File::shouldReceive('put')
            ->withArgs(function (string $path, Stringable $script, bool $lock): bool {
                static::assertSame(base_path('preload.php'), $path);
                static::assertTrue($lock);

                static::assertStringContainsString(<<<'SCRIPT'
 * Preloader config
 *     - Memory limit: (disabled)
SCRIPT
                    , $script);

                return true;
            })
            ->andReturnTrue();

        $this->compiler->send($this->list)->thenReturn();
    }

    public function test_uses_require(): void
    {
        $this->app->make('config')->set('preload.use_require', true);

        File::shouldReceive('get')->with(Preloader::STUB)
            ->andReturn(file_get_contents(Preloader::STUB));

        File::shouldReceive('missing')->with(base_path('vendor/autoload.php'))
            ->andReturnFalse();

        File::shouldReceive('put')
            ->withArgs(function (string $path, Stringable $script, bool $lock): bool {
                static::assertSame(base_path('preload.php'), $path);
                static::assertTrue($lock);

                static::assertStringContainsString(<<<'SCRIPT'
    try {
        if (!(\is_file($file) && \is_readable($file))) {
            continue;
        }
        require_once $file;
    }
SCRIPT
                    , $script);

                static::assertStringContainsString(<<<'SCRIPT'
 * The following script uses `require_once $file` syntax to preload each file in this list into Opcache.
SCRIPT
                    , $script);

                return true;
            })
            ->andReturnTrue();

        $this->compiler->send($this->list)->thenReturn();
    }

    public function test_doesnt_ignore_missing(): void
    {
        $this->app->make('config')->set('preload.ignore_not_found', false);

        File::shouldReceive('get')->with(Preloader::STUB)
            ->andReturn(file_get_contents(Preloader::STUB));

        File::shouldReceive('missing')->with(base_path('vendor/autoload.php'))
            ->andReturnFalse();

        File::shouldReceive('put')
            ->withArgs(function (string $path, Stringable $script, bool $lock): bool {
                static::assertSame(base_path('preload.php'), $path);
                static::assertTrue($lock);

                static::assertStringContainsString(<<<'SCRIPT'
        if (!(\is_file($file) && \is_readable($file))) {
            throw new \Exception("{$file} does not exist or is unreadable.");
        }
SCRIPT
                    , $script);

                return true;
            })
            ->andReturnTrue();

        $this->compiler->send($this->list)->thenReturn();
    }

    public function test_exception_when_destination_is_not_writable(): void
    {
        $path = base_path('preload.php');

        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage("Couldn't write preload script to '$path'.");

        File::shouldReceive('get')->with(Preloader::STUB)
            ->andReturn(file_get_contents(Preloader::STUB));

        File::shouldReceive('put')->andReturnFalse();

        $this->compiler->send($this->list)->thenReturn();
    }

    public function test_exception_when_stub_not_readable(): void
    {
        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage('Cannot read the stub "'.Preloader::STUB.'" contents.');

        File::shouldReceive('get')->with(Preloader::STUB)
            ->andThrow(new FileNotFoundException('foo bar'));

        $this->compiler->send(new Listing(new Collection()))->thenReturn();
    }

    public function test_exception_when_use_require_with_autoloader_missing()
    {
        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage(
            'Composer Autoloader is missing in \''.base_path('vendor/autoload.php').'\''
        );

        $this->app->make('config')->set('preload.use_require', true);

        File::shouldReceive('get')->with(Preloader::STUB)
            ->andReturn(file_get_contents(Preloader::STUB));

        File::shouldReceive('missing')->with(base_path('vendor/autoload.php'))
            ->andReturnTrue();

        File::shouldReceive('put')->never();

        $this->compiler->send($this->list)->thenReturn();
    }
}
