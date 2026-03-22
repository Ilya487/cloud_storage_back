<?php

namespace App\Tools;

use App\Exceptions\NotFoundException;
use App\Http\Response;
use App\Tools\Logger;
use Error;
use Exception;

class ErrorHandler
{
    public static function handle(callable $cb)
    {
        try {
            call_user_func($cb);
        } catch (NotFoundException $error) {
            new Response()->setStatusCode(404)->sendJson(['message' => $error->getMessage()]);
        } catch (Exception $error) {
            Logger::writeLogFromError($error);
            (new Response)->setStatusCode(500)->sendJson(['message' => 'Произошла непредвиденная ошибка. Попробуйте еще раз позднее']);
        } catch (Error $error) {
            Logger::writeLogFromError($error);
            (new Response)->setStatusCode(500)->sendJson(['message' => 'Произошла непредвиденная ошибка. Попробуйте еще раз позднее']);
        }
    }
}
