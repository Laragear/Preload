<?php

namespace Laragear\Preload\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigContract;
use Illuminate\Filesystem\Filesystem;
use Laragear\Preload\Preloader;
use Symfony\Component\Console\Attribute\AsCommand;
use const DIRECTORY_SEPARATOR;

/**
 * @internal
 */
#[AsCommand(name: 'preload:stub', description: 'Creates a preload stub if it does not exist.')]
class Stub extends Command
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
    protected $signature = 'preload:stub';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a preload stub if it does not exist.';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $file, ConfigContract $config): void
    {
        $dir = $config->get('preload.path');
        $filePath = $dir . '/' . Preloader::NAME_PRELOAD;

        $file->ensureDirectoryExists($dir);

        if ($file->exists($filePath)) {
            $this->info('A preload script file already exists.');

            return;
        }

        $file->put($filePath, <<<'STUB'
<?php

$date = (new DateTime())->format('d-m-Y H:i:s');

echo "[$date] Info: This is a preload stub file to be replaced for the application at runtime.";

STUB
        );

        $this->info("Stub copied at [$filePath].");
        $this->newLine();
        $this->comment('Remember to edit your [php.ini] file:');
        $this->comment("opcache.preload = $filePath");
    }
}
