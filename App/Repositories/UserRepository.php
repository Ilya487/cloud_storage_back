<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Db\BaseRepository;
use App\Models\User;

class UserRepository extends BaseRepository
{
    protected string $tableName = 'users';

    /**
     * @return string new user id
     */
    public function insertNewUser(string $login, string $password, int $totalDiskSpace): string
    {
        return  $this->insert([
            'login' => $login,
            'password' => $password,
            'available_disk_space' => $totalDiskSpace,
            'total_disk_space' => $totalDiskSpace
        ]);
    }

    public function isLoginExist(string $login): bool
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('login', $login))
            ->build();

        $res = $this->count($query);
        if ($res > 0) return true;
        else return false;
    }

    public function getUserById(int $id): ?User
    {
        $dbRes = $this->getById($id);

        if ($dbRes) {
            return User::createFromArr($dbRes);
        }
        return null;
    }

    public function getByLogin(string $login): ?User
    {
        $query = $this->queryBuilder->newQuery()
            ->where(Expression::equal('login', $login))->build();

        $dbRes = $this->getOne(whereClauseQuery: $query);

        if ($dbRes) {
            return User::createFromArr($dbRes);
        }
        return null;
    }

    public function incrementDownloadSessionCount(int $userId, int $limit)
    {
        $query = $this->queryBuilder
            ->add('download_sessions_count', 1)
            ->where(Expression::equal('id', $userId))
            ->where(Expression::less('download_sessions_count', $limit))
            ->build();

        $rowCount = $this->query($query)->affectedRows;
        return boolval($rowCount);
    }

    public function decrementDownloadSessionCount(int $userId)
    {
        $query = $this->queryBuilder
            ->subtract('download_sessions_count', 1)
            ->where(Expression::equal('id', $userId))
            ->where(Expression::more('download_sessions_count', 0, 'bottom'))
            ->build();

        $rowCount = $this->query($query)->affectedRows;
        return boolval($rowCount);
    }

    public function reserveDiskSpace(int $userId, int $byteSize)
    {
        $query = $this->queryBuilder
            ->subtract('available_disk_space', $byteSize)
            ->where(Expression::moreEqual('available_disk_space', $byteSize))
            ->and(Expression::equal('id', $userId))
            ->build();

        $rowCount = $this->query($query)->affectedRows;
        return boolval($rowCount);
    }

    public function freeUpDiskSpace(int $userId, int $byteSize)
    {
        $query = $this->queryBuilder
            ->add('available_disk_space', $byteSize)
            ->where(Expression::equal('id', $userId))
            ->andRaw('available_disk_space+:size <= total_disk_space', ['size' => $byteSize])
            ->build();

        $rowCount = $this->query($query)->affectedRows;
        return boolval($rowCount);
    }
}
