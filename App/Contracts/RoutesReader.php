<?php

namespace App\Contracts;

use App\dto\RouteDto;

interface  RoutesReader
{
    /**
     * @return RouteDto[]
     */
    public function getRoutes(): array;
}
