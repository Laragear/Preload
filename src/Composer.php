<?php

namespace Laragear\Preload;

use Composer\InstalledVersions;

use function realpath;

/**
 * Class Composer.
 *
 * This is just a class to enable mocking for testing.
 *
 * @internal
 */
class Composer
{
    /**
     * Returns where the installation path of the library if it's installed.
     */
    public function getLibraryPath(string $library): ?string
    {
        return InstalledVersions::isInstalled($library) ? realpath(InstalledVersions::getInstallPath($library)) : null;
    }
}
