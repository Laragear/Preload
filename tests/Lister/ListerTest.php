<?php

namespace Tests\Lister;

use ArrayIterator;
use Illuminate\Support\Facades\Event;
use Laragear\Preload\Events\ListGenerated;
use Laragear\Preload\Exceptions\PreloadException;
use Laragear\Preload\Facades\Preload;
use Laragear\Preload\Opcache;
use Mockery;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Tests\MocksOpcache;
use Tests\TestCase;

class ListerTest extends TestCase
{
    use MocksOpcache;

    public function test_list(): void
    {
        $event = Event::fake();

        [$memoryUsage, $opcacheStatistics] = $this->mockOpcache([
            '$PRELOAD$' => [],
            'external/foo.php' => [ // 3
                'hits' => 20,
                'memory_consumption' => 1024,
                'last_used_timestamp' => 1400000000,
            ],
        ]);

        $list = Preload::list();

        static::assertSame($memoryUsage, $list->opcache['memory_usage']);
        static::assertSame($opcacheStatistics, $list->opcache['opcache_statistics']);

        static::assertSame(32, $list->memory);

        static::assertTrue($list->projectOnly);

        static::assertEmpty($list->exclude);
        static::assertEmpty($list->append);
        static::assertSame(0, $list->excludeCount);
        static::assertSame(0, $list->appendCount);

        static::assertCount(5, $list->files);
        static::assertFalse($list->files->contains('$PRELOAD$'));
        static::assertFalse($list->files->contains('external/quuz.php'));
        static::assertFalse($list->files->contains('external/foo.php'));

        static::assertEmpty($list->path);
        static::assertNull($list->output);

        $event->assertDispatched(
            ListGenerated::class,
            static function (ListGenerated $event) use ($list): bool {
                return $list === $event->listing;
            }
        );
    }

    public function test_cuts_list_by_memory_limit()
    {
        $this->app->make('config')->set('preload.memory', 10);

        $this->mockOpcache(['$PRELOAD$' => []]);

        $list = Preload::list();

        static::assertCount(3, $list->files);
        static::assertNotContains('$PRELOAD$', $list->files);
        static::assertNotContains('external/quuz.php', $list->files);
    }

    public function test_allows_all_files_without_memory_limit()
    {
        $this->app->make('config')->set('preload.memory', null);

        $this->mockOpcache();

        $list = Preload::list();

        static::assertCount(5, $list->files);
        static::assertSame(0, $list->memory);
    }

    public function test_includes_external_project_files()
    {
        $this->app->make('config')->set('preload.project_only', false);

        $this->mockOpcache();

        $list = Preload::list();

        static::assertContains('external/quuz.php', $list->files);
    }

    public function test_excludes_files_manually(): void
    {
        $this->mockOpcache();

        $finder = $this->mock(Finder::class);

        $finder->allows('in')->with('test_path')->andReturnSelf();
        $finder->allows('name')->with('*.php')->andReturnSelf();

        $file = Mockery::mock(SplFileInfo::class);
        $file->allows('getRealPath')->once()->andReturn(base_path('foo.php'));

        $finder->allows('getIterator')->andReturn(new ArrayIterator([$file]));

        Preload::exclude('test_path');

        $list = Preload::list();

        static::assertNotContains(base_path('foo.php'), $list->files);
    }

    public function test_excludes_file_using_callback(): void
    {
        $this->mockOpcache();

        $finder = $this->mock(Finder::class);

        $finder->allows('in')->with('test_path')->andReturnSelf();
        $finder->allows('name')->with('*.php')->andReturnSelf();

        $file = Mockery::mock(SplFileInfo::class);
        $file->allows('getRealPath')->once()->andReturn(base_path('foo.bar'));

        $finder->allows('getIterator')->andReturn(new ArrayIterator([$file]));

        Preload::exclude(function ($finder) {
            $finder->in('test_path')->name('*.php');
        });

        $list = Preload::list();

        static::assertNotContains(base_path('foo.bar'), $list->files);
    }

    public function test_excludes_external_project_files(): void
    {
        $this->app->make('config')->set('preload.project_only', false);

        $this->mockOpcache();

        $finder = $this->mock(Finder::class);

        $finder->allows('in')->with('external/')->andReturnSelf();
        $finder->allows('name')->with('*.php')->andReturnSelf();

        $file = Mockery::mock(SplFileInfo::class);
        $file->allows('getRealPath')->once()->andReturn('external/quuz.php');

        $finder->allows('getIterator')->andReturn(new ArrayIterator([$file]));

        Preload::exclude(function ($finder) {
            $finder->in('external/')->name('*.php');
        });

        $list = Preload::list();

        static::assertNotContains('external/quuz.php', $list->files);
    }

    public function test_excluding_file_also_excludes_it_from_memory_limit(): void
    {
        $this->app->make('config')->set('preload.project_only', false);
        $this->app->make('config')->set('preload.memory', 8);

        $this->mockOpcache();

        $finder = $this->mock(Finder::class);

        $finder->allows('in')->with('test_path')->andReturnSelf();
        $finder->allows('name')->with('*.php')->andReturnSelf();

        $file = Mockery::mock(SplFileInfo::class);
        $file->allows('getRealPath')->once()->andReturn(base_path('foo.php'));

        $finder->allows('getIterator')->andReturn(new ArrayIterator([$file]));

        Preload::exclude('test_path');

        $list = Preload::list();

        static::assertNotContains(base_path('foo.php'), $list->files);
        static::assertCount(2, $list->files);

        static::assertContains(base_path('bar.php'), $list->files);
        static::assertContains(base_path('quz.php'), $list->files);
    }

    public function test_appends_files_manually(): void
    {
        $this->mockOpcache();

        $finder = $this->mock(Finder::class);

        $finder->allows('in')->with('test_path')->andReturnSelf();
        $finder->allows('name')->with('*.php')->andReturnSelf();

        $file = Mockery::mock(SplFileInfo::class);
        $file->allows('getRealPath')->once()->andReturn('append/foo.php');

        $finder->allows('getIterator')->andReturn(new ArrayIterator([$file]));

        Preload::append('test_path');

        $list = Preload::list();

        static::assertSame('append/foo.php', $list->files->last());
        static::assertCount(6, $list->files);
    }

    public function test_appending_files_does_not_count_for_memory_limit(): void
    {
        $this->app->make('config')->set('preload.memory', 9);

        $this->mockOpcache();

        $finder = $this->mock(Finder::class);

        $finder->allows('in')->with('test_path')->andReturnSelf();
        $finder->allows('name')->with('*.php')->andReturnSelf();

        $file = Mockery::mock(SplFileInfo::class);
        $file->allows('getRealPath')->once()->andReturn('append/foo.php');

        $finder->allows('getIterator')->andReturn(new ArrayIterator([$file]));

        Preload::append('test_path');

        $list = Preload::list();

        static::assertCount(4, $list->files);
        static::assertContains(base_path('foo.php'), $list->files);
        static::assertContains(base_path('bar.php'), $list->files);
        static::assertContains(base_path('quz.php'), $list->files);
        static::assertContains('append/foo.php', $list->files);
    }

    public function test_append_does_not_duplicates_file(): void
    {
        $this->mockOpcache();

        $finder = $this->mock(Finder::class);

        $finder->allows('in')->with('test_path')->andReturnSelf();
        $finder->allows('name')->with('*.php')->andReturnSelf();

        $file = Mockery::mock(SplFileInfo::class);
        $file->allows('getRealPath')->once()->andReturn(base_path('foo.php'));

        $finder->allows('getIterator')->andReturn(new ArrayIterator([$file]));

        Preload::append('test_path');

        $list = Preload::list();

        static::assertSame(base_path('foo.php'), $list->files->get(2));
        static::assertCount(5, $list->files);
    }

    public function test_exception_list_when_opcache_disabled(): void
    {
        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage('Opcache is disabled.');

        $this->mock(Opcache::class)->allows('isDisabled')->andReturnTrue();

        Preload::list();
    }

    public function test_exception_list_when_opcache_scripts_empty(): void
    {
        $this->expectException(PreloadException::class);
        $this->expectExceptionMessage('Opcache has no cached scripts.');

        $opcache = $this->mock(Opcache::class);

        $opcache->allows('isDisabled')->andReturnFalse();
        $opcache->allows('getMemoryUsage')->andReturn(['memory_usage' => []]);
        $opcache->allows('getStatistics')->andReturn(['opcache_statistics' => []]);

        $opcache->allows('getScripts')->andReturn([]);

        Preload::list();
    }
}
