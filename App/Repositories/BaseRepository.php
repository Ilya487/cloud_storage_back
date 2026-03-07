<?php

namespace App\Repositories;

use App\Db\QueryBuilder;
use App\Tools\DbConnect;
use Exception;
use PDO;
use PDOStatement;

abstract class BaseRepository
{
    private PDO $pdo;
    protected QueryBuilder $queryBuilder;
    protected string $tableName;
    private bool $isTransactionStartManually = false;

    public function __construct(DbConnect $dbConnect)
    {
        if (is_null($this->tableName)) throw new Exception('Не задано имя таблицы');

        $this->pdo = $dbConnect->getConnection();
        $this->queryBuilder = new QueryBuilder($this->tableName);
    }

    public function withTransaction(callable $callback)
    {
        if ($this->pdo->inTransaction() || $this->isTransactionStartManually)
            throw new Exception('Предыдущая транзакция не завершена!');

        $commit = fn() => $this->pdo->commit();
        $rollBack = fn() => $this->pdo->rollBack();
        try {
            $this->beginTransaction();
            $this->isTransactionStartManually = true;
            $res = $callback($commit, $rollBack);
            $this->isTransactionStartManually = false;
            $this->submitTransaction();

            return $res;
        } catch (Exception $e) {
            $this->rollBackTransaction();
            throw $e;
        }
    }

    protected function fetchAll(string $query, array $columnValues, $returnType = PDO::FETCH_ASSOC): array
    {
        $stmt =  $this->executeQuery($query, $columnValues);
        return $stmt->fetchAll($returnType);
    }

    protected function fetchOne(string $query, array $columnValues, $returnType = PDO::FETCH_ASSOC): mixed
    {
        $stmt = $this->executeQuery($query, $columnValues);
        return $stmt->fetch($returnType);
    }

    protected function fetchColumn(string $query, array $columnValues, int $columnNum = 0)
    {
        $stmt  = $this->executeQuery($query, $columnValues);
        return $stmt->fetchColumn($columnNum);
    }

    /**
     * @return string inserted entity id
     */
    protected function insert(string $query, array $columnValues): string
    {
        $this->executeQuery($query, $columnValues);
        return $this->pdo->lastInsertId();
    }

    protected function update(string $query, array $columnValues): int
    {
        return $this->executeQuery($query, $columnValues)->rowCount();
    }

    protected function delete(string $query, array $columnValues): int
    {
        $stmt = $this->executeQuery($query, $columnValues);
        return $stmt->rowCount();
    }

    protected function beginTransaction()
    {
        if ($this->isTransactionStartManually) return;
        if ($this->pdo->inTransaction()) return;
        $this->pdo->beginTransaction();
    }

    protected function submitTransaction()
    {
        if ($this->isTransactionStartManually) return;
        if ($this->pdo->inTransaction())
            $this->pdo->commit();
    }

    protected function rollBackTransaction()
    {
        if ($this->pdo->inTransaction())
            $this->pdo->rollBack();
    }

    protected function prepareParamsForIn(array $ids)
    {
        $preparedIds = [];
        foreach ($ids as $key => $value) {
            $preparedIds[":$key"] = $value;
        }

        return $preparedIds;
    }

    private function executeQuery(string $query, array $columnValues): PDOStatement
    {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($columnValues);
            return $stmt;
        } catch (Exception $error) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $error;
        }
    }
}
