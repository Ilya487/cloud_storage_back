<?php

namespace App\Repositories;

use App\Db\Expression;
use App\Db\Query;
use App\Db\QueryBuilder;
use App\Db\TransactionManager;
use App\Tools\DbConnect;
use Exception;
use PDO;
use PDOStatement;

abstract class BaseRepository
{
    private PDO $pdo;
    protected QueryBuilder $queryBuilder;
    protected string $tableName;

    public function __construct(DbConnect $dbConnect, private TransactionManager $tx)
    {
        if (is_null($this->tableName)) throw new Exception('Не задано имя таблицы');

        $this->pdo = $dbConnect->getConnection();
        $this->queryBuilder = new QueryBuilder($this->tableName);
    }

    protected function getOne(array $fieldsForSelect = [], ?Query $whereClauseQuery = null): array|false
    {
        $query = $this->queryBuilder->select($fieldsForSelect);
        if ($whereClauseQuery !== null) $query = $query->whereRaw($whereClauseQuery->query, $whereClauseQuery->params);
        $query = $query->limit(1, 0)->build();

        return $this->fetchOne($query);
    }

    protected function getAll(array $fieldsForSelect = [], ?Query $whereClauseQuery = null, int $limit = 0, int $offset = 0): array|false
    {
        $query = $this->queryBuilder->select($fieldsForSelect);
        if ($whereClauseQuery !== null) $query = $query->whereRaw($whereClauseQuery->query, $whereClauseQuery->params);
        if ($limit > 0)
            $query = $query->limit($limit, $offset);

        return $this->fetchAll($query->build());
    }

    /**
     * @return string inserted entity id
     */
    protected function insert(array $columnValues): string
    {
        $query = $this->queryBuilder
            ->insert(array_keys($columnValues), [$columnValues])
            ->build();

        $this->executeQuery($query);
        return $this->pdo->lastInsertId();
    }

    /**
     * @param array<int,array<string,mixed>> $columnValues
     */
    protected function insertMany(array $fields, array $columnValues): int
    {
        $query = $this->queryBuilder
            ->insert($fields, $columnValues)
            ->build();

        return $this->executeQuery($query)->rowCount();
    }

    protected function update(array $columnValues, Query $whereClauseQuery): int
    {
        $query = $this->queryBuilder
            ->update($columnValues)
            ->whereRaw($whereClauseQuery->query, $whereClauseQuery->params)
            ->build();

        return $this->executeQuery($query)->rowCount();
    }

    protected function delete(Query $whereClauseQuery): int
    {
        $query = $this->queryBuilder
            ->delete()
            ->whereRaw($whereClauseQuery->query, $whereClauseQuery->params)
            ->build();

        $stmt = $this->executeQuery($query);
        return $stmt->rowCount();
    }

    protected function getById(int $id, ?Query $whereClauseQuery = null): array|false
    {
        $query = $this->queryBuilder
            ->select()
            ->where(Expression::equal('id', $id));
        if ($whereClauseQuery !== null) $query->andRaw($whereClauseQuery->query, $whereClauseQuery->params);

        return $this->fetchOne($query->build());
    }

    protected function deleteById(int $id, ?Query $whereClauseQuery = null): int
    {
        $query = $this->queryBuilder
            ->delete()
            ->where(Expression::equal('id', $id));
        if ($whereClauseQuery !== null) $query->andRaw($whereClauseQuery->query, $whereClauseQuery->params);
        $query = $query->build();

        return $this->executeQuery($query)->rowCount();
    }

    protected function updateById(int $id, array $columnValues, ?Query $whereClauseQuery = null): int
    {
        $query = $this->queryBuilder
            ->update($columnValues)
            ->where(Expression::equal('id', $id));
        if ($whereClauseQuery !== null) $query->whereRaw($whereClauseQuery->query, $whereClauseQuery->params);
        $query = $query->build();

        return $this->executeQuery($query)->rowCount();
    }

    protected function count(?Query $whereClauseQuery = null): int
    {
        $query = $this->queryBuilder
            ->select(['COUNT(*) as cnt']);

        if ($whereClauseQuery !== null) {
            $query->whereRaw($whereClauseQuery->query, $whereClauseQuery->params);
        }

        $result = $this->fetchOne($query->build());

        return (int) ($result['cnt'] ?? 0);
    }

    protected function beginTransaction()
    {
        $this->tx->beginTransaction();
    }

    protected function submitTransaction()
    {
        $this->tx->submitTransaction();
    }

    protected function rollBackTransaction()
    {
        $this->tx->rollBackTransaction();
    }

    protected function prepareParamsForIn(array $ids)
    {
        $preparedIds = [];
        foreach ($ids as $key => $value) {
            $preparedIds[":$key"] = $value;
        }

        return $preparedIds;
    }

    protected function formatTimestamp(int $timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp);
    }

    private function fetchOne(Query $query): array|false
    {
        $res = $this->executeQuery($query)->fetch();
        if (empty($res)) return false;
        else return $res;
    }

    private function fetchAll(Query $query): array|false
    {
        $res = $this->executeQuery($query)->fetchAll();
        if (empty($res)) return false;
        else return $res;
    }

    private function executeQuery(Query $query): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($query->query);
            $stmt->execute($query->params);
            return $stmt;
        } catch (Exception $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }
}
