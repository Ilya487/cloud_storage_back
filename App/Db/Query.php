<?php

namespace App\Db;

class Query
{
    public function __construct(
        public readonly string $query,
        public readonly array $params
    ) {}
}
