<?php

namespace App\Repositories;

use App\Tools\DbConnect;
use App\Tools\QueryBuilder;
use Exception;
use PDO;
use PDOStatement;

abstract class BaseRepository
{
    private PDO $pdo;
    protected QueryBuilder $queryBuilder;


    public function __construct(DbConnect $dbConnect, string $tableName)
    {
        $this->pdo = $dbConnect->getConnection();
        $this->queryBuilder = new QueryBuilder($tableName);
    }

    protected function fetchAll(string $query, array $columnValues, $returnType = PDO::FETCH_ASSOC): mixed
    {
        $stmt =  $this->executeQuery($query, $columnValues);
        return $stmt->fetchAll($returnType);
    }

    protected function fetchOne(string $query, array $columnValues, $returnType = PDO::FETCH_ASSOC): mixed
    {
        $stmt = $this->executeQuery($query, $columnValues);
        return $stmt->fetch($returnType);
    }

    /**
     * @return string inserted entity id
     */
    protected function insert(string $query, array $columnValues): string
    {
        $this->executeQuery($query, $columnValues);
        return $this->pdo->lastInsertId();
    }

    protected function update(string $query, array $columnValues): void
    {
        $this->executeQuery($query, $columnValues);
    }

    protected function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    protected function submitTransaction()
    {
        $this->pdo->commit();
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
