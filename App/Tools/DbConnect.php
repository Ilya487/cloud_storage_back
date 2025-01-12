<?php

namespace App\Tools;

use Exception;
use PDO;
use PDOException;

class DbConnect
{
    private const
        DSN = 'mysql:host=localhost;dbname=testDb',
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
        try {
            $connection = new PDO(self::DSN, self::USER_NAME, self::USER_PASSWORD);
            return $connection;
        } catch (PDOException) {
            //log error...
            throw new Exception();
        }
    }
}
