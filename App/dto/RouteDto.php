<?php

namespace App\DTO;

/**
 * Класс для удобного хранения информации о роуте
 */
class RouteDto
{
    function __construct(public readonly string $regexp, public readonly string $controllerClassName) {}
}
