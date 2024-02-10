<?php

namespace App\dto;

/**
 * Класс для удобного хранения информации о роуте
 */
class RouterDto
{
    function __construct(public readonly string $regexp, public readonly string $controllerClassName)
    {
    }
}
