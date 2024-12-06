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

    private static ?PDO  $connection = null;

    public static function getConnection(): PDO
    {
        if (!is_null(self::$connection)) {
            return self::$connection;
        } else {
            self::createConnection();
            return self::$connection;
        }
    }

    private static function createConnection(): bool
    {
        try {
            self::$connection = new PDO(self::DSN, self::USER_NAME, self::USER_PASSWORD);
            return true;
        } catch (PDOException) {
            //log error...
            throw new Exception();
        }
    }
}
