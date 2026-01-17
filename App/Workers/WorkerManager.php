<?php

namespace App\Workers;

class WorkerManager
{
    public static function startFileBuildWorker(int $sessionId, int $userId)
    {
        shell_exec("nohup php App/Workers/FileBuildWorker.php -u=$userId -s=$sessionId > /dev/null 2>&1 &");
    }

    public static function startPrepareFilesForDownloadWorker(int $userId, int $taskId)
    {
        shell_exec("php App/Workers/PrepareFileForDownloadWorker.php -u=$userId -t=$taskId > /dev/null 2>&1 &");
    }
}
