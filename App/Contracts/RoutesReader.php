<?php

namespace App\Contracts;

use App\dto\RouteDto;

abstract class RoutesReader
{
    /**
     * @var RouteDto[]
     */
    protected array $routes = [];

    public function __construct(protected string $filePath)
    {
    }

    /**
     * @return RouteDto[]
     */
    abstract public function getRoutes(): array;
}
