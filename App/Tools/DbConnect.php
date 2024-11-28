<?php

namespace App\Tools;

use PDO;
use PDOException;

class DbConnect
{
    private const
        DSN = 'mysql:host=localhost;dbname=testDb',
        USER_NAME = 'root',
        USER_PASSWORD = '123';

    private static ?PDO  $connection = null;

    public static function getConnection(): PDO|false
    {
        if (!is_null(self::$connection)) {
            return self::$connection;
        } else {
            $result = self::createConnection();
            if ($result) return self::$connection;
            else return false;
        }
    }

    private static function createConnection(): bool
    {
        try {
            self::$connection = new PDO(self::DSN, self::USER_NAME, self::USER_PASSWORD);
            return true;
        } catch (PDOException) {
            return false;
        }
    }
}
