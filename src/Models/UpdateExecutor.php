<?php

declare(strict_types=1);

namespace NawrasBukhari\Updater\Models;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use NawrasBukhari\Updater\Events\UpdateFailed;
use NawrasBukhari\Updater\Events\UpdateSucceeded;
use NawrasBukhari\Updater\Traits\UseVersionFile;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class UpdateExecutor
{
    use UseVersionFile;

    /**
     * Define the base path where the update should be applied into.
     */
    protected string $basePath;

    public function __construct()
    {
        $this->basePath = base_path();
    }

    /**
     * Use the base_path() function to determine the project root folder.
     * This might be not good when running unit tests.
     *
     *
     * @return $this
     */
    public function setBasePath(string $path): self
    {
        $this->basePath = Str::finish($path, urlSeparator());

        return $this;
    }

    /**
     * @throws Exception
     */
    public function run(Release $release): bool
    {
        if (checkPermissions($this->basePath)) {
            $releaseFolder = createFolderFromFile($release->getStoragePath());

            if ($releaseFolder === '') {
                event(new UpdateFailed($release));

                return false;
            }

            // Move all directories first
            $this->moveFolders($releaseFolder);

            // Now move all the files
            $this->moveFiles($releaseFolder);

            // Delete the folder from the update
            File::deleteDirectory($releaseFolder);

            // Delete the version file
            $this->deleteVersionFile();

            event(new UpdateSucceeded($release));

            return true;
        }

        event(new UpdateFailed($release));

        return false;
    }

    private function moveFiles(string $folder): void
    {
        $files = (new Finder())->in($folder)
                               ->exclude(config('self-update.exclude_folders'))
                               ->ignoreDotFiles(false)
                               ->files();

        collect($files)->each(function (SplFileInfo $file) {
            if ($file->getRealPath()) {
                File::copy(
                    $file->getRealPath(),
                    Str::finish($this->basePath, urlSeparator()).$file->getFilename()
                );
            }
        });
    }

    private function moveFolders(string $folder): void
    {
        $directories = (new Finder())->in($folder)->exclude(config('self-update.exclude_folders'))->directories();

        $sorted = collect($directories->sort(function (SplFileInfo $a, SplFileInfo $b) {
            return strlen($b->getRealpath()) - strlen($a->getRealpath());
        }));

        $sorted->each(function (SplFileInfo $directory) {
            if (! dirsIntersect(File::directories($directory->getRealPath()), config('self-update.exclude_folders'))) {
                File::copyDirectory(
                    $directory->getRealPath(),
                    Str::finish($this->basePath, urlSeparator()).Str::finish($directory->getRelativePath(), DIRECTORY_SEPARATOR).$directory->getBasename()
                );
            }

            File::deleteDirectory($directory->getRealPath());
        });
    }
}
