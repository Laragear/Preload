<?php

namespace Laragear\Preload\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Filesystem\Filesystem;
use JetBrains\PhpStorm\Pure;
use Laragear\Preload\Preloader;
use function php_ini_loaded_file;

class Placeholder extends Command
{
    /**
     * Indicates whether the command should be shown in the Artisan command list.
     *
     * @var bool
     */
    protected $hidden = true;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'preload:placeholder {--force : Skips overwrite confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a empty preload placeholder file if it doesn\'t exists.';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $file
     * @param  \Illuminate\Contracts\Config\Repository  $config
     * @return void
     */
    public function handle(Filesystem $file, ConfigContract $config): void
    {
        $path = $config->get('preload.path');

        $this->comment('Generating a preload placeholder at: ' . $path);
        $this->newLine();

        if ($file->exists($path)) {
            if ($this->isPlaceholder($file, $path)) {
                $this->info('A placeholder preload script already exists, no need to generate it again.');
                return;
            } elseif ($this->deniesOverwrite()) {
                $this->warn('A preload script already exists, skipping.');
                return;
            }
        }

        $file->put($path, $file->get(Preloader::PLACEHOLDER));

        $this->comment('Empty preload stub generated');
        $this->comment('Remember to edit your [php.ini] file:');
        $this->comment("opcache.preload = '$path';");
    }

    /**
     * Check if the placeholder file already exists (and it's the placeholder).
     *
     * @param  \Illuminate\Filesystem\Filesystem  $file
     * @param  mixed  $path
     * @return bool
     */
    #[Pure]
    protected function isPlaceholder(Filesystem $file, mixed $path): bool
    {
        return $file->hash($path) === $file->hash(Preloader::PLACEHOLDER);
    }

    /**
     * Confirm the preload file overwrite.
     *
     * @return bool
     */
    protected function deniesOverwrite(): bool
    {
        if ($this->option('force')) {
            return false;
        }

        return ! $this->confirm('Seems there is already a preload file at the location. Overwrite?');
    }
}
