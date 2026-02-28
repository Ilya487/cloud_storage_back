<?

namespace App\Services;

use App\Models\Collections\FileSystemObjectCollection;
use App\Storage\DiskStorage;
use App\Storage\DownloadStorage;
use App\Tools\ArchiveException;
use ReflectionProperty;
use SplObjectStorage;

class DownloadArchiveService
{
    public function __construct(
        private DownloadStorage $downloadStorage,
        private DiskStorage $diskStorage,
    ) {}

    function buildArchiveForDownload(int $downloadId, FileSystemObjectCollection $files): FilesPrepareResult
    {
        $archive = $this->downloadStorage->createArchive(
            $downloadId,
            $files->len() == 1 ? $files[0]->getName() : ''
        );

        if ($archive === false) {
            return new FilesPrepareResult(false, '', [], []);
        }

        $successAdded = [];
        $errorAdded = [];

        $files = $this->resolveDuplicates($files);

        foreach ($files as $file) {
            if (!$file->isFile()) {
                $addRes = $archive->createDir($file->getPath());
                if ($addRes === false) {
                    $errorAdded[] = $file;
                    continue;
                }
                $successAdded[] = $file;
                continue;
            }

            $fullPath = $this->diskStorage->getPath($file->id, $file->getExt());
            if ($fullPath === false) {
                $errorAdded[] = $file;
                continue;
            }

            $addRes = $archive->add($fullPath, $file->getPath());
            if ($addRes === false) $errorAdded[] = $file;
            else $successAdded[] = $file;
        }

        if (count($successAdded) == 0) return FilesPrepareResult::createError();

        try {
            $archivePath = $archive->build();
            if ($archivePath === false) return FilesPrepareResult::createError();
            return new FilesPrepareResult(true, $archivePath, $successAdded, $errorAdded);
        } catch (ArchiveException) {
            return FilesPrepareResult::createError();
        }
    }

    private function resolveDuplicates(FileSystemObjectCollection $files)
    {
        while ($this->isNeedToRename($files)) {
            $pathMap = [];
            $renameQueue = new SplObjectStorage;

            foreach ($files as $file) {
                if (!isset($pathMap[$file->getPath()])) {
                    $pathMap[$file->getPath()] = 1;
                    continue;
                }

                $count = ++$pathMap[$file->getPath()];
                $currentPath = $file->getPath();

                $updatedName = $file->getName() . "($count)" . ($file->getExt() ? ".{$file->getExt()}" : '');
                $file->rename($updatedName);
                if ($file->isFile()) continue;

                $renameQueue[$file] = $currentPath;
            }

            foreach ($renameQueue as $file) {
                $oldPath = $renameQueue[$file];

                foreach ($files->getFolderContent($file->getPathIds()) as $subFile) {
                    $path = $subFile->getPath();
                    $path = str_replace($oldPath, $file->getPath(), $path);
                    new ReflectionProperty($subFile, 'path')->setValue($subFile, $path);
                }
            }
        }


        return $files;
    }

    private function isNeedToRename(FileSystemObjectCollection $files): bool
    {
        $pathMap = [];
        foreach ($files as $file) {
            if (!isset($pathMap[$file->getPath()])) {
                $pathMap[$file->getPath()] = 1;
                continue;
            }
            return true;
        }

        return false;
    }
}

class FilesPrepareResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $resPath,
        public readonly array $successAdded,
        public readonly array $errorAdded
    ) {}

    public static function createError()
    {
        return new self(false, '', [], []);
    }
}
