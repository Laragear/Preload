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
     * Returns the where the installation path of the library if its installed.
     */
    public function getLibraryPath(string $library): ?string
    {
        return InstalledVersions::isInstalled($library) ? realpath(InstalledVersions::getInstallPath($library)) : null;
    }
}
