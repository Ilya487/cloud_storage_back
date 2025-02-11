<?php

namespace App\Router;

use App\Controllers\ControllerInterface;
use Exception;

class ControllerSetup
{
    public function __construct(public readonly string $controllerClassName, public readonly string $method = 'resolve')
    {
        if (!is_subclass_of($controllerClassName, ControllerInterface::class)) {
            throw new Exception($controllerClassName . ' не является Controller');
        }

        if (!method_exists($controllerClassName, $method)) {
            throw new Exception("У класса $controllerClassName отсутствует метод $method");
        }
    }
}
