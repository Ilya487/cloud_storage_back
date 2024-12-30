<?php

namespace App\Contracts;

interface  RoutesReader
{
    /**
     * @return RouteDto[]
     */
    public function getRoutes(): array;
}
