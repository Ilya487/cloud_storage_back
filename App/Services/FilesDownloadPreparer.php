<?

namespace App\Services;

use App\Models\FileSystemObject;
use App\Models\FsObjectType;
use App\Storage\DiskStorage;
use App\Storage\DownloadStorage;
use App\Tools\ArchiveException;
use Exception;

class FilesDownloadPreparer
{
    public function __construct(
        private DownloadStorage $downloadStorage,
        private DiskStorage $diskStorage,
    ) {}

    /**
     * @param array<FileSystemObject> $files
     */
    function prepareFiles(int $userId, array $files): FilesPrepareResult
    {
        $this->checkTypes($files);

        if (count($files) == 1 && $files[0]->type == FsObjectType::FILE) {
            $file = $files[0];
            $fullPath = $this->diskStorage->getPath($userId, $file->getPath());
            if ($fullPath === false) return FilesPrepareResult::createError();
            else return new FilesPrepareResult(true, $fullPath, [$file], []);
        }

        $archive = $this->downloadStorage->createArchive(
            $userId,
            count($files) == 1 ? $files[0]->getName() : ''
        );

        if ($archive === false) {
            return new FilesPrepareResult(false, '', [], []);
        }

        $successAdded = [];
        $errorAdded = [];

        foreach ($files as $file) {
            $fullPath = $this->diskStorage->getPath($userId, $file->getPath());
            if ($fullPath === false) {
                $errorAdded[] = $file;
                continue;
            }

            $addRes = $archive->add($fullPath);
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

    private function checkTypes(array $files)
    {
        foreach ($files as $file) {
            if (!($file instanceof FileSystemObject))
                throw new Exception($file . ' is not instance of FileSystemObject');
        }
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
