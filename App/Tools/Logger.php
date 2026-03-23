<?php

namespace App\Tools;

use DateTime;
use DateTimeZone;

class Logger
{
    public static function writeLogFromError(\Throwable $error)
    {
        $timezone = new DateTimeZone('GMT+09:00');
        $date = (new DateTime('now', $timezone))->format('d.m.Y H:i:s');

        $errorMsg = $error->getMessage();
        $file = $error->getFile();
        $line = $error->getLine();
        $stack = $error->getTraceAsString();

        $errorMsg = "$date  " . $errorMsg . PHP_EOL . $file . ' ' . $line . "\n$stack\n\n";
        file_put_contents(__DIR__ . '/../../logs', $errorMsg, FILE_APPEND);
    }
}
