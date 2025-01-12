<?php

namespace App\Core\DiContainer;

class ContainerParam
{
    public function __construct(public readonly string $className, public readonly string $paramName, public readonly string|int|float|bool|null $value) {}
}
