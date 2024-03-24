<?php

namespace App\dto;

/**
 * Класс для удобного хранения информации о роуте
 */
class RouteDto
{
    function __construct(public readonly string $regexp, public readonly string $controllerClassName)
    {
    }
}
