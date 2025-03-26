<?php

namespace Tests\Lister;

use ArrayIterator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Laragear\Preload\Composer;
use Laragear\Preload\Events\ListGenerated;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Lister\Lister;
use Laragear\Preload\Lister\Pipes\CutListByMemoryLimit;
use Laragear\Preload\Lister\Pipes\ExcludePreloadVariable;
use Laragear\Preload\Lister\Pipes\FireEvent;
use Laragear\Preload\Lister\Pipes\LoadAcceleratedFiles;
use Laragear\Preload\Lister\Pipes\LoadIncludedAndExcludedLibraries;
use Laragear\Preload\Lister\Pipes\LoadOpcacheConfig;
use Laragear\Preload\Lister\Pipes\MayIncludeFiles;
use Laragear\Preload\Lister\Pipes\MayExcludeFiles;
use Laragear\Preload\Lister\Pipes\MayScopeFilesToProjectPath;
use Laragear\Preload\Lister\Pipes\NormalizeList;
use Laragear\Preload\Lister\Pipes\SortScriptsByHitRatio;
use Laragear\Preload\Listing;
use Laragear\Preload\Opcache;
use Mockery;
use Mockery\MockInterface;
use ReflectionProperty;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

class ListerTest extends TestCase
{
    protected function lister(): Lister
    {
        return $this->app->make(Lister::class);
    }

    public function test_ensure_pipes_order(): void
    {
        $lister = $this->lister();

        $pipes = new ReflectionProperty($lister, 'pipes');

        $pipes->setAccessible(true);

        static::assertSame([
            LoadOpcacheConfig::class,
            LoadAcceleratedFiles::class,
            LoadIncludedAndExcludedLibraries::class,
            ExcludePreloadVariable::class,
            MayScopeFilesToProjectPath::class,
            MayExcludeFiles::class,
            SortScriptsByHitRatio::class,
            CutListByMemoryLimit::class,
            NormalizeList::class,
            MayIncludeFiles::class,
            FireEvent::class,
        ], $pipes->getValue($lister));
    }

    public function test_load_opcache_config(): void
    {
        $memoryUsage = [
            'used_memory' => 1024 ** 2, 'free_memory' => 1024 ** 2, 'wasted_memory' => 1024 ** 2,
        ];

        $statistics = [
            'num_cached_scripts' => 10, 'opcache_hit_rate' => 100, 'misses' => 25,
        ];

        $this->mock(Opcache::class, function (MockInterface $mock) use ($statistics, $memoryUsage) {
            $mock->expects('isDisabled')->andReturnFalse();
            $mock->expects('getMemoryUsage')->andReturn($memoryUsage);
            $mock->expects('getStatistics')->andReturn($statistics);
        });

        $listing = $this->app->make(LoadOpcacheConfig::class)
            ->handle(new Listing(), fn ($value) => $value);

        static::assertSame($memoryUsage, $listing->opcache['memory_usage']);
        static::assertSame($statistics, $listing->opcache['opcache_statistics']);
    }

    public function test_load_opcache_config_throws_when_disabled(): void
    {
        $this->mock(Opcache::class, function (MockInterface $mock) {
            $mock->expects('isDisabled')->andReturnTrue();
            $mock->expects('getMemoryUsage')->never();
            $mock->expects('getStatistics')->never();
        });

        $pipe = $this->app->make(LoadOpcacheConfig::class);

        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage("Cannot gather Opcache data because it's disabled.");

        $pipe->handle(new Listing(), fn ($value) => $value);
    }

    public function test_load_accelerated_files(): void
    {
        $files = [
            '/app/foo.php' => [
                'hits' => 10,
                'memory_consumption' => 1024 ** 2,
                'last_used_timestamp' => 1400000000,
            ],
        ];

        $this->mock(Opcache::class)->expects('getScripts')->andReturn($files);

        $lister = $this->app->make(LoadAcceleratedFiles::class)
            ->handle(new Listing(), fn ($value) => $value);

        static::assertSame($files, $lister->files->toArray());
    }

    public function test_load_accelerated_files_throws_when_list_is_empty(): void
    {
        $this->mock(Opcache::class)->expects('getScripts')->andReturn([]);

        $pipe = $this->app->make(LoadAcceleratedFiles::class);

        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage('Opcache has no cached scripts.');

        $pipe->handle(new Listing(), fn ($value) => $value);
    }

    public function test_includes_and_excludes_packages_from_composer(): void
    {
        $this->mock(Filesystem::class)
            ->expects('json')
            ->with($this->app->basePath('composer.json'))
            ->andReturn([
                'extra' => [
                    'preload' => [
                        'foo' => true,
                        'bar' => false,
                        'baz' => [
                            'include' => 'src/Include/Baz',
                            'exclude' => ['src/Exclude/Baz'],
                        ],
                    ],
                ],
            ]);

        $this->mock(Composer::class, function (MockInterface $mock) {
            $mock->expects('getLibraryPath')->times(3)->andReturnUsing(function (string $library) {
                return "/test/path/$library";
            });
        });

        $listing = $this->app
            ->make(LoadIncludedAndExcludedLibraries::class)
            ->handle(new Listing(), fn ($listing) => $listing);

        $finder = Mockery::mock(Finder::class);
        $finder->expects('files')->andReturnSelf();
        $finder->expects('in')->with(['/test/path/foo']);
        ($listing->include[0])($finder);

        $finder = Mockery::mock(Finder::class);
        $finder->expects('files')->andReturnSelf();
        $finder->expects('in')->with(['/test/path/bar']);
        ($listing->exclude[0])($finder);

        $finder = Mockery::mock(Finder::class);
        $finder->expects('files')->andReturnSelf();
        $finder->expects('in')->with(['src/Include/Baz']);
        ($listing->include[1])($finder);

        $finder = Mockery::mock(Finder::class);
        $finder->expects('files')->andReturnSelf();
        $finder->expects('in')->with(['src/Exclude/Baz']);
        ($listing->exclude[1])($finder);
    }

    public function test_includes_and_excludes_packages_from_composer_throws_if_library_not_installed(): void
    {
        $this->mock(Filesystem::class)
            ->expects('json')
            ->with($this->app->basePath('composer.json'))
            ->andReturn([
                'extra' => [
                    'preload' => [
                        'foo' => true,
                    ],
                ],
            ]);

        $this->mock(Composer::class, function (MockInterface $mock) {
            $mock->expects('getLibraryPath')->with('foo')->andReturnNull();
        });

        $pipe = $this->app->make(LoadIncludedAndExcludedLibraries::class);

        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage('The library [foo] is not installed.');

        $pipe->handle(new Listing(), fn ($listing) => $listing);
    }

    public function test_exclude_preload_variable(): void
    {
        $listing = new Listing(files: new Collection(['$PRELOAD$' => 'test', 'file' => 'bar']));

        $this->app->make(ExcludePreloadVariable::class)->handle($listing, fn ($value) => $value);

        static::assertSame(['file' => 'bar'], $listing->files->toArray());
    }

    public function test_may_scope_files_to_project_path(): void
    {
        $config = $this->app->make('config');

        $config->set('preload.project_only', true);

        $listing = new Listing(files: new Collection([
            $this->app->basePath('/foo.php') => ['test'],
            '/bar.php' => ['test']
        ]));

        $this->app->make(MayScopeFilesToProjectPath::class)->handle($listing, fn ($value) => $value);

        static::assertSame([$this->app->basePath('/foo.php') => ['test']], $listing->files->toArray());

        $config->set('preload.project_only', false);

        $listing = new Listing(files: new Collection($allFiles = [
            $this->app->basePath('/foo.php') => ['test'],
            '/bar.php' => ['test']
        ]));

        $this->app->make(MayScopeFilesToProjectPath::class)->handle($listing, fn ($value) => $value);

        static::assertSame($allFiles, $listing->files->toArray());
    }

    public function test_may_exclude_files(): void
    {
        $this->mock(Finder::class, function (MockInterface $mock) {
            $mock->expects('in')->with('foo')->andReturnSelf();
            $mock->expects('name')->with('*.php')->andReturnSelf();

            $file = Mockery::mock(SplFileInfo::class);
            $file->expects('getRealPath')->andReturn('foo.php');

            $mock->expects('getIterator')->andReturn(new ArrayIterator([
                'foo.php' => $file,
            ]));
        });

        $listing = new Listing(
            exclude: [fn(Finder $finder) => $finder->in('foo')->name('*.php')],
            files: new Collection(['foo.php' => [], 'bar.php' => []])
        );

        $this->app->make(MayExcludeFiles::class)->handle($listing, fn ($value) => $value);

        static::assertSame(['bar.php' => []], $listing->files->toArray());
        static::assertSame(1, $listing->excludeCount);
        static::assertEmpty($listing->exclude);
    }

    public function test_sort_scripts_by_hit_ratio(): void
    {
        $listing = new Listing(files: new Collection([
            'foo' => ['hits' => 10, 'last_used_timestamp' => 100],
            'bar' => ['hits' => 20, 'last_used_timestamp' => 105],
            'quz' => ['hits' => 20, 'last_used_timestamp' => 110],
            'fred' => ['hits' => 5, 'last_used_timestamp' => 50],
        ]));

        $this->app->make(SortScriptsByHitRatio::class)->handle($listing, fn ($value) => $value);

        static::assertSame([
            'quz' => ['hits' => 20, 'last_used_timestamp' => 110],
            'bar' => ['hits' => 20, 'last_used_timestamp' => 105],
            'foo' => ['hits' => 10, 'last_used_timestamp' => 100],
            'fred' => ['hits' => 5, 'last_used_timestamp' => 50],
        ], $listing->files->toArray());
    }

    public function test_cut_list_by_memory_limit(): void
    {
        $this->app->make('config')->set('preload.memory', 5);

        $listing = new Listing(files: new Collection([
            'foo' => ['memory_consumption' => 2 * 1024 ** 2],
            'bar' => ['memory_consumption' => 2 * 1024 ** 2],
            'quz' => ['memory_consumption' => 2 * 1024 ** 2],
            'fred' => ['memory_consumption' => 2 * 1024 ** 2],
        ]));

        $this->app->make(CutListByMemoryLimit::class)->handle($listing, fn ($value) => $value);

        static::assertSame([
            'foo' => ['memory_consumption' => 2 * 1024 ** 2],
            'bar' => ['memory_consumption' => 2 * 1024 ** 2],
        ], $listing->files->toArray());
    }

    public function test_normalize_list(): void
    {
        $listing = new Listing(files: new Collection([
            'foo' => [],
            'bar' => [],
        ]));

        $this->app->make(NormalizeList::class)->handle($listing, fn ($value) => $value);

        static::assertSame(['foo', 'bar'], $listing->files->toArray());
    }

    public function test_may_include_files(): void
    {
        $this->mock(Finder::class, function (MockInterface $mock) {
            $mock->expects('in')->with('foo')->twice()->andReturnSelf();
            $mock->expects('name')->with('*.php')->twice()->andReturnSelf();

            $file = Mockery::mock(SplFileInfo::class);
            $file->expects('getRealPath')->andReturn('foo.php')->twice();

            $mock->expects('getIterator')->andReturn(new ArrayIterator([
                'foo.php' => $file,
            ]))->twice();
        });

        $listing = new Listing(
            include: [
                fn(Finder $finder) => $finder->in('foo')->name('*.php'),
                fn(Finder $finder) => $finder->in('foo')->name('*.php')
            ],
            files: new Collection(['bar.php'])
        );

        $this->app->make(MayIncludeFiles::class)->handle($listing, fn ($value) => $value);

        static::assertSame(['bar.php', 'foo.php'], $listing->files->toArray());
        static::assertSame(1, $listing->includeCount);
        static::assertEmpty($listing->include);
    }

    public function test_fires_event(): void
    {
        $event = Event::fake();

        $this->app->make(FireEvent::class)->handle($listing = new Listing(), fn ($value) => $value);

        $event->assertDispatched(ListGenerated::class, function (ListGenerated $event) use ($listing) {
            static::assertSame($listing, $event->listing);

            return true;
        });
    }
}
