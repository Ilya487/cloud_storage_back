<?php

namespace App\Tools;

use App\Http\Response;
use DateTime;
use DateTimeZone;
use Exception;
use PDOException;

class ErrorHandler
{
    public static function handle(callable $cb)
    {
        try {
            call_user_func($cb);
        } catch (PDOException $error) {
            self::writeLog($error);
            (new Response)->setStatusCode(500)->sendJson(['message' => 'Произошла непредвиденная ошибка. Попробуйте еще раз позднее']);
        } catch (Exception $error) {
            self::writeLog($error);
        }
    }

    private static function writeLog(Exception $error)
    {
        $timezone = new DateTimeZone('GMT+09:00');
        $date = (new DateTime('now', $timezone))->format('d.m.Y H:i:s');

        $errorMsg = $error->getMessage();
        $file = $error->getFile();
        $line = $error->getLine();
        $errorMsg = "$date  " . $errorMsg . PHP_EOL . $file . ' ' . $line . "\n\n";
        file_put_contents('logs', $errorMsg, FILE_APPEND);
    }
}
