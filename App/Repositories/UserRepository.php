<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Repositories\BaseRepository;
use App\Models\User;
use PDO;

class UserRepository extends BaseRepository
{
    protected string $tableName = 'users';

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

    public function incrementDownloadSessionCount(int $userId, int $limit)
    {
        $query = $this->queryBuilder
            ->update(['download_sessions_count'])
            ->incrementField('download_sessions_count')
            ->where(Expression::less('download_sessions_count'))
            ->and(Expression::equal('id'))
            ->build();
        $rowCount = $this->update($query, ['download_sessions_count' => $limit, 'id' => $userId]);
        return boolval($rowCount);
    }

    public function decrementDownloadSessionCount(int $userId)
    {
        $query = $this->queryBuilder
            ->update(['download_sessions_count'])
            ->decrementField('download_sessions_count')
            ->where(Expression::equal('id'))
            ->build();
        $this->update($query, ['id' => $userId]);
    }
}
