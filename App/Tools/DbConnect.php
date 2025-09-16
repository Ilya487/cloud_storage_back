<?php

namespace App\Tools;

use PDO;

class DbConnect
{
    private const
        DSN = 'mysql:host=mysql;dbname=testDb',
        USER_NAME = 'root',
        USER_PASSWORD = '123';

    private ?PDO  $connection = null;

    public function __construct() {}

    public function getConnection(): PDO
    {
        if (isset($this->connection)) return $this->connection;
        else {
            $this->connection = $this->createConnection();
            return $this->connection;
        }
    }

    private function createConnection(): PDO
    {
        $connection = new PDO(self::DSN, self::USER_NAME, self::USER_PASSWORD);
        return $connection;
    }
}
