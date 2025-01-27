<?php

namespace App\Repositories;

use App\Tools\DbConnect;
use App\Tools\QueryBuilder;
use PDO;
use PDOStatement;

class BaseRepository
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

    protected function fetchOne(string $query, array $columnValues, $returnType = PDO::FETCH_ASSOC)
    {
        $stmt = $this->executeQuery($query, $columnValues);
        return $stmt->fetch($returnType);
    }

    protected function insertAndGetId(string $query, array $columnValues): string
    {
        $this->executeQuery($query, $columnValues);
        return $this->pdo->lastInsertId();
    }

    private function executeQuery(string $query, array $columnValues): PDOStatement
    {
        $stmt =  $this->pdo->prepare($query);
        $stmt->execute($columnValues);
        return $stmt;
    }
}
