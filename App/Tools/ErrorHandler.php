<?php

namespace App\Tools;

use App\Exceptions\NotFoundException;
use App\Http\Response;
use DateTime;
use DateTimeZone;
use Error;
use Exception;
use PDOException;
use Throwable;

class ErrorHandler
{
    public static function handle(callable $cb)
    {
        try {
            call_user_func($cb);
        } catch (PDOException $error) {
            self::writeLog($error);
            (new Response)->setStatusCode(500)->sendJson(['message' => 'Произошла непредвиденная ошибка. Попробуйте еще раз позднее']);
        } catch (NotFoundException $error) {
            new Response()->setStatusCode(404)->sendJson(['message' => $error->getMessage()]);
        } catch (Exception $error) {
            self::writeLog($error);
            (new Response)->setStatusCode(500)->sendJson(['message' => 'Произошла непредвиденная ошибка. Попробуйте еще раз позднее']);
        } catch (Error $error) {
            self::writeLog($error);
            (new Response)->setStatusCode(500)->sendJson(['message' => 'Произошла непредвиденная ошибка. Попробуйте еще раз позднее']);
        }
    }

    private static function writeLog(Throwable $error)
    {
        $timezone = new DateTimeZone('GMT+09:00');
        $date = (new DateTime('now', $timezone))->format('d.m.Y H:i:s');

        $errorMsg = $error->getMessage();
        $file = $error->getFile();
        $line = $error->getLine();
        $stack = $error->getTraceAsString();

        $errorMsg = "$date  " . $errorMsg . PHP_EOL . $file . ' ' . $line . "\n$stack\n\n";
        file_put_contents('logs', $errorMsg, FILE_APPEND);
    }
}
