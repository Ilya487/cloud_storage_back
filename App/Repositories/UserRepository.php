<?php

namespace App\Repositories;

use App\Tools\DbConnect;
use App\Tools\QueryBuilder;
use PDO;

class UserRepository
{
    private PDO $pdo;
    private QueryBuilder $queryBuilder;


    public function __construct()
    {
        $this->pdo = DbConnect::getConnection();
        $this->queryBuilder = new QueryBuilder('users');
    }

    /**
     * @return string new user id
     */
    public function insertNewUser(string $login, string $password): string
    {
        $query = $this->queryBuilder->insert(['login', 'password'])->build();
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['login' => $login, 'password' => $password]);
        return $this->pdo->lastInsertId();
    }

    public function isLoginExist(string $login): bool
    {
        $query = $this->queryBuilder->select()->where('login', QueryBuilder::EQUAL)->build();
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['login' => $login]);

        $res = $stmt->fetch(PDO::FETCH_NUM);
        if ($res == false) return false;
        else return true;
    }
}
