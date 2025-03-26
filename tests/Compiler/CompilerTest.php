<?php

namespace Tests\Compiler;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\DateFactory;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Stringable;
use Laragear\Preload\Compiler\Compiler;
use Laragear\Preload\Compiler\Pipes\EnsureDirectoryExists;
use Laragear\Preload\Compiler\Pipes\FireEvent;
use Laragear\Preload\Compiler\Pipes\LoadPreloaderStub;
use Laragear\Preload\Compiler\Pipes\LoadStatisticsStub;
use Laragear\Preload\Compiler\Pipes\SetOpcacheConfig;
use Laragear\Preload\Compiler\Pipes\SetPreloadConfig;
use Laragear\Preload\Compiler\Pipes\SetStatistics;
use Laragear\Preload\Compiler\Pipes\WriteListFile;
use Laragear\Preload\Compiler\Pipes\WritePreloaderFile;
use Laragear\Preload\Compiler\Pipes\WriteStatisticsFile;
use Laragear\Preload\Events\PreloadGenerated;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Facades\Preload;
use Laragear\Preload\Listing;
use Laragear\Preload\Opcache;
use Laragear\Preload\Preloader;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;
use Tests\MocksOpcache;
use Tests\TestCase;
use function realpath;
use const DIRECTORY_SEPARATOR;
use const PHP_EOL;

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

        $this->list = Preload::files();
    }

    protected function compiler(): Compiler
    {
        return $this->app->make(Compiler::class);
    }

    protected function getFilesAsString(): string
    {
        return PHP_EOL.
            '    '."'".$this->list->files->implode("',".PHP_EOL."    '")."'".
            PHP_EOL;
    }

    public function test_ensure_pipes_order(): void
    {
        $property = new ReflectionProperty($this->compiler, 'pipes');
        $property->setAccessible(true);

        static::assertSame([
            EnsureDirectoryExists::class,
            WriteListFile::class,
            LoadStatisticsStub::class,
            SetStatistics::class,
            SetOpcacheConfig::class,
            WriteStatisticsFile::class,
            LoadPreloaderStub::class,
            SetPreloadConfig::class,
            WritePreloaderFile::class,
            FireEvent::class,
        ], $property->getValue($this->compiler));
    }

    public function test_ensures_directory_exists(): void
    {
        $this->mock(Filesystem::class, function (MockInterface $mock) {
            $path = $this->app->make('config')->get('preload.path');

            $mock->expects('ensureDirectoryExists')->with($path);
            $mock->expects('isWritable')->with($path)->andReturnTrue();
        });

        $this->app->make(EnsureDirectoryExists::class)->handle(new Listing(), fn($listing) => $listing);
    }

    public function test_ensures_directory_exists_throws_if_not_writable(): void
    {
        $path = $this->app->make('config')->get('preload.path');

        $this->mock(Filesystem::class, function (MockInterface $mock) use ($path) {
            $mock->expects('ensureDirectoryExists')->with($path);
            $mock->expects('isWritable')->with($path)->andReturnFalse();
        });

        $pipe = $this->app->make(EnsureDirectoryExists::class);

        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage("The path [$path] is not writable.");

        $pipe->handle(new Listing(), fn($listing) => $listing);
    }

    public function test_write_list_file(): void
    {
        $path = $this->app->make('config')->get('preload.path').DIRECTORY_SEPARATOR.Preloader::NAME_LIST;

        $this->mock(Filesystem::class)
            ->expects('put')
            ->with($path, 'foo'.PHP_EOL.'bar'.PHP_EOL, true)
            ->andReturnTrue();

        $listing = new Listing(files: new Collection(['foo', 'bar']));

        $this->app->make(WriteListFile::class)
            ->handle($listing, fn($listing) => $listing);

        static::assertEmpty($listing->files);
    }

    public function test_write_list_file_throws_if_writing_files(): void
    {
        $path = $this->app->make('config')->get('preload.path').DIRECTORY_SEPARATOR.Preloader::NAME_LIST;

        $this->mock(Filesystem::class)->expects('put')->andReturnFalse();

        $pipe = $this->app->make(WriteListFile::class);

        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage("Couldn't write list file to [$path].");

        $pipe->handle(new Listing(files: new Collection(['foo', 'bar'])), fn($listing) => $listing);
    }

    public function test_load_statistics_stub(): void
    {
        $listing = $this->app->make(LoadStatisticsStub::class)
            ->handle(new Listing(), fn($listing) => $listing);

        static::assertStringEqualsFile(Preloader::STUB_STATISTICS, $listing->statistics->toString());
    }

    public function test_load_statistics_stub_throws_when_stub_doesnt_exist(): void
    {
        $this->mock(Filesystem::class)
            ->expects('get')
            ->with(Preloader::STUB_STATISTICS)
            ->andThrow(FileNotFoundException::class);

        $pipe = $this->app->make(LoadStatisticsStub::class);

        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage('Cannot read the stub "'.Preloader::STUB_STATISTICS.'" contents.');

        $pipe->handle(new Listing(), fn($listing) => $listing);
    }

    public function test_set_statistics(): void
    {
        $this->app->make('config')->set('preload.path', 'foo');
        $this->mock(DateFactory::class)->expects('now')->andReturn(Carbon::create(2020));

        $replaceable = <<<'STUB'
@generated_at
@output
@mechanism
@preloader_excluded
@preloader_included
@preloader_memory_limit
STUB;

        $listing = $this->app->make(SetStatistics::class)
            ->handle(new Listing(statistics: new Stringable($replaceable), includeCount: 1, excludeCount: 4),
                fn($listing) => $listing);

        static::assertSame(<<<'EXPECTED'
2020-01-01 00:00:00
foo/list.txt
opcache_compile_file
4
1
(disabled)
EXPECTED
            , $listing->statistics->toString());
    }

    public static function provideUseRequire(): array
    {
        return [
            [true, 'require_once'],
            [false, 'opcache_compile_file'],
        ];
    }

    #[DataProvider('provideUseRequire')]
    public function test_set_statistics_with_config(bool $config, string $expected): void
    {
        $listing = new Listing(statistics: new Stringable("@mechanism"));

        $this->app->make('config')->set('preload.use_require', $config);

        $this->app->make(SetStatistics::class)
            ->handle($listing, fn($listing) => $listing);

        static::assertSame($expected, $listing->statistics->toString());
    }

    public static function provideOpcacheConfig(): array
    {
        return [
            ['preloader_included', '10', new Listing(includeCount: 10)],
            ['preloader_excluded', '10', new Listing(excludeCount: 10)],
            ['preloader_memory_limit', '(disabled)', new Listing()],
            ['preloader_memory_limit', '10MB', new Listing(memory: 10)],
        ];
    }

    #[DataProvider('provideOpcacheConfig')]
    public function test_set_statistics_data(string $replaceable, string $expected, Listing $listing): void
    {
        $listing->statistics = new Stringable("$replaceable: @$replaceable");

        $this->mock(DateFactory::class)->expects('now')->andReturn(Carbon::create(2020));

        $this->app->make(SetStatistics::class)
            ->handle($listing, fn($listing) => $listing);

        static::assertSame("$replaceable: $expected", $listing->statistics->toString());
    }

    public function test_set_opcache_config(): void
    {
        $listing = new Listing(
            statistics: new Stringable(implode(PHP_EOL, [
                '@opcache_memory_used',
                '@opcache_memory_free',
                '@opcache_memory_wasted',
                '@opcache_files',
                '@opcache_hit_rate',
                '@opcache_misses',
            ])),
            opcache: [
                'memory_usage' => [
                    'used_memory' => 5 * 1024 ** 2,
                    'free_memory' => 4 * 1024 ** 2,
                    'wasted_memory' => 3 * 1024 ** 2,
                ],
                'opcache_statistics' => [
                    'num_cached_scripts' => 10000,
                    'opcache_hit_rate' => 5.5,
                    'misses' => 10002,
                ],
            ]
        );

        $this->app->make(SetOpcacheConfig::class)
            ->handle($listing, fn($listing) => $listing);

        static::assertSame(<<<'EXPECTED'
5.0
4.0
3.0
10000
5.50
10002
EXPECTED
        , $listing->statistics->toString());
    }

    public function test_writes_statistics_file(): void
    {
        $statistics = new Stringable('test-statistics');

        $this->mock(Filesystem::class)->expects('put')->with(
            $this->app->make('config')->get('preload.path').DIRECTORY_SEPARATOR.Preloader::NAME_STATISTICS,
            $statistics,
            true
        )->andReturnTrue();

        $this->app->make(WriteStatisticsFile::class)
            ->handle(new Listing(statistics: $statistics), fn($listing) => $listing);
    }

    public function test_writes_statistics_file_throws_when_cannot_put(): void
    {
        $this->mock(Filesystem::class)->expects('put')->andReturnFalse();

        $path = $this->app->make('config')->get('preload.path').DIRECTORY_SEPARATOR.Preloader::NAME_STATISTICS;

        $pipe = $this->app->make(WriteStatisticsFile::class);

        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage("Couldn't write statistics file to [$path].");

        $pipe->handle(new Listing(), fn($listing) => $listing);
    }

    public function test_load_preload_stub(): void
    {
        $listing = $this->app->make(LoadPreloaderStub::class)
            ->handle(new Listing(), fn($listing) => $listing);

        static::assertStringEqualsFile(Preloader::STUB_PRELOAD, $listing->preloader->toString());
    }

    public function test_load_preload_stub_throws_when_file_not_found(): void
    {
        $this->mock(Filesystem::class)
            ->expects('get')
            ->with(Preloader::STUB_PRELOAD)
            ->andThrow(FileNotFoundException::class);

        $pipe = $this->app->make(LoadPreloaderStub::class);

        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage('Cannot read the stub "'.Preloader::STUB_PRELOAD.'" contents.');

        $pipe->handle(new Listing(), fn($listing) => $listing);
    }

    public function test_set_preload_config_autoload_as_null(): void
    {
        $config = $this->app->make('config');
        $listing = new Listing(preloader: new Stringable('@autoload'));

        $this->app->make(SetPreloadConfig::class)->handle($listing, fn($listing) => $listing);

        static::assertSame('', $listing->preloader->toString());
    }

    public function test_set_preload_config_autoload_with_path(): void
    {
        $this->mock(Filesystem::class)->expects('missing')->andReturnFalse();

        $config = $this->app->make('config');
        $listing = new Listing(preloader: new Stringable('@autoload'));

        $config->set('preload.use_require', true);

        $this->app->make(SetPreloadConfig::class)->handle($listing, fn($listing) => $listing);

        static::assertSame(
            'require_once \''.realpath($config->get('preload.autoloader')).'\';',
            $listing->preloader->toString()
        );
    }

    public function test_set_preload_config_throws_when_autoload_required_and_missing(): void
    {
        $this->mock(Filesystem::class)->expects('missing')->andReturnTrue();

        $config = $this->app->make('config');
        $listing = new Listing(preloader: new Stringable('@autoload'));

        $config->set('preload.use_require', true);

        $pipe = $this->app->make(SetPreloadConfig::class);

        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage("Composer Autoloader is missing in [{$config->get('preload.autoload')}].");

        $pipe->handle($listing, fn($listing) => $listing);
    }

    public function test_set_preload_config_file(): void
    {
        $listing = $this->app->make(SetPreloadConfig::class)
            ->handle(new Listing(preloader: new Stringable('@file')), fn($listing) => $listing);

        static::assertSame(
            $this->app->make('config')->get('preload.path') . DIRECTORY_SEPARATOR . Preloader::NAME_LIST,
            $listing->preloader->toString()
        );
    }

    public function test_set_preload_config_failure_ignore(): void
    {
        $this->app->make('config')->set('preload.ignore_not_found', true);

        $listing = $this->app->make(SetPreloadConfig::class)
            ->handle(new Listing(preloader: new Stringable('@failure')), fn($listing) => $listing);

        static::assertSame('continue;', $listing->preloader->toString());
    }

    public function test_set_preload_config_failure_throws(): void
    {
        $this->app->make('config')->set('preload.ignore_not_found', false);

        $listing = $this->app->make(SetPreloadConfig::class)
            ->handle(new Listing(preloader: new Stringable('@failure')), fn($listing) => $listing);

        static::assertSame(
            'throw new \Exception("{$file} does not exist or is unreadable.");',
            $listing->preloader->toString()
        );
    }

    public function test_set_preload_config_mechanism_require(): void
    {
        $this->mock(Filesystem::class)->expects('missing')->andReturnFalse();

        $this->app->make('config')->set('preload.use_require', true);

        $listing = $this->app->make(SetPreloadConfig::class)
            ->handle(new Listing(preloader: new Stringable('@mechanism')), fn($listing) => $listing);

        static::assertSame('require_once $file', $listing->preloader->toString());
    }

    public function test_set_preload_config_mechanism_opcache_compile_file(): void
    {
        $this->app->make('config')->set('preload.use_require', false);

        $listing = $this->app->make(SetPreloadConfig::class)
            ->handle(new Listing(preloader: new Stringable('@mechanism')), fn($listing) => $listing);

        static::assertSame('\opcache_compile_file($file)', $listing->preloader->toString());
    }

    public function test_write_preload_file(): void
    {
        $listing = new Listing(preloader: $preloader = new Stringable('foo'));

        $path = $this->app->make('config')->get('preload.path').DIRECTORY_SEPARATOR.Preloader::NAME_PRELOAD;

        $this->mock(Filesystem::class)
            ->expects('put')
            ->with($path, $preloader, true)
            ->andReturnTrue();

        $listing = $this->app->make(WritePreloaderFile::class)->handle($listing, fn($listing) => $listing);

        static::assertEmpty($listing->preloader->toString());
    }

    public function test_write_preload_file_throws_if_writing_files(): void
    {
        $path = $this->app->make('config')->get('preload.path').DIRECTORY_SEPARATOR.Preloader::NAME_PRELOAD;

        $this->mock(Filesystem::class)->expects('put')->andReturnFalse();

        $pipe = $this->app->make(WritePreloaderFile::class);

        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage("Couldn't write preload script to [$path].");

        $pipe->handle(new Listing(files: new Collection(['foo', 'bar'])), fn($listing) => $listing);
    }

    public function test_fires_event(): void
    {
        $event = Event::fake();

        $listing = new Listing();

        $this->app->make(FireEvent::class)->handle($listing, fn($listing) => $listing);

        $event->assertDispatched(PreloadGenerated::class, function (PreloadGenerated $event) use ($listing) {
            static::assertSame($listing, $event->listing);

            return true;
        });
    }
}
