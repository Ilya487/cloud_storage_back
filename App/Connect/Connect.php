<?php

namespace App\Connect;

use PDO;
use PDOException;

class Connect
{
    private const
        DSN = 'mysql:host=localhost;dbname=blog',
        USER_NAME = 'root',
        USER_PASSWORD = 'root';

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
