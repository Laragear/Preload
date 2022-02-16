<?php

namespace Tests;

use Laragear\Preload\Opcache;

trait MocksOpcache
{
    protected function list(): array
    {
        return [
            base_path('foo.php') => [ // 3
                'hits' => 10,
                'memory_consumption' => 1024 ** 2,
                'last_used_timestamp' => 1400000000,
            ],
            base_path('bar.php') => [ // 1
                'hits' => 20,
                'memory_consumption' => 3 * (1024 ** 2),
                'last_used_timestamp' => 1400000002,
            ],
            base_path('quz.php') => [ // 2
                'hits' => 20,
                'memory_consumption' => 5 * (1024 ** 2),
                'last_used_timestamp' => 1400000001,
            ],
            base_path('qux.php') => [ // 4
                'hits' => 5,
                'memory_consumption' => 5 * (1024 ** 2),
                'last_used_timestamp' => 1400000010,
            ],
            base_path('baz.php') => [ // 5
                'hits' => 5,
                'memory_consumption' => 6 * (1024 ** 2),
                'last_used_timestamp' => 1400000010,
            ],
            'external/quuz.php' => [ // 5
                'hits' => 15,
                'memory_consumption' => 7 * (1024 ** 2),
                'last_used_timestamp' => 1400000015,
            ],
        ];
    }

    public function mockOpcache(iterable $files = []): array
    {
        $opcache = $this->mock(Opcache::class);

        $opcache->allows('isDisabled')->andReturnFalse();

        $opcache->allows('getMemoryUsage')->once()
            ->andReturn($memoryUsage = [
                'used_memory' => 48 * 1024 ** 2,
                'free_memory' => 80 * 1024 ** 2,
                'wasted_memory' => 0,
            ]);

        $opcache->allows('getStatistics')->once()
            ->andReturn($opcacheStatistics = [
                'num_cached_scripts' => 4321,
                'opcache_hit_rate' => 6000 / 100,
                'misses' => 1234,
            ]);

        $opcache->allows('getScripts')->andReturn(array_merge($this->list(), $files));

        return [$memoryUsage, $opcacheStatistics];
    }
}
