<?php

namespace App\Tools;

use Redis;

class RedisConntect
{
    private const
        URL = 'redis',
        PASSWORD = '123';

    private ?Redis  $connection = null;

    public function __construct() {}

    public function getConnection(): Redis
    {
        if (isset($this->connection)) return $this->connection;
        else {
            $this->connection = $this->createConnection();
            return $this->connection;
        }
    }

    private function createConnection(): Redis
    {
        $connection = new Redis();
        $connection->connect(self::URL);
        $connection->auth(self::PASSWORD);
        return $connection;
    }
}
