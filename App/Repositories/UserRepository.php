<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Repositories\BaseRepository;
use App\Models\User;
use App\Tools\QueryBuilder;
use PDO;

class UserRepository extends BaseRepository
{
    /**
     * @return string new user id
     */
    public function insertNewUser(string $login, string $password): string
    {
        $query = $this->queryBuilder->insert(['login', 'password'])->build();
        $newUserId =  $this->insert($query, ['login' => $login, 'password' => $password]);
        return $newUserId;
    }

    public function isLoginExist(string $login): bool
    {
        $query = $this->queryBuilder->select()->where(Expression::equal('login'))->build();
        $res = $this->fetchAll($query, ['login' => $login], PDO::FETCH_NUM);

        if ($res == false) return false;
        else return true;
    }

    public function getById(int $id): ?User
    {
        $query = $this->queryBuilder->select()->where(Expression::equal('id'))->build();
        $dbRes = $this->fetchOne($query, ['id' => $id]);

        if ($dbRes) {
            return User::createFromArr($dbRes);
        }
        return null;
    }

    public function getByLogin(string $login): ?User
    {
        $query = $this->queryBuilder->select()->where(Expression::equal('login'))->build();
        $dbRes = $this->fetchOne($query, ['login' => $login]);

        if ($dbRes) {
            return User::createFromArr($dbRes);
        }
        return null;
    }
}
