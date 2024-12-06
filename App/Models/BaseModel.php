<?php

namespace App\Models;

use App\Tools\DbConnect;
use PDO;
use App\Tools\QueryBuilder;

abstract class BaseModel
{
    private PDO $pdo;
    private ?string $id = null;
    private QueryBuilder $queryBuilder;

    public function __construct()
    {
        $this->pdo = DbConnect::getConnection();
        $this->queryBuilder = new QueryBuilder($this->getTableName());
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public  function save()
    {
        $fields = $this->getFieldsNames();

        if (is_null($this->id)) {
            $query = $this->queryBuilder->insert($fields)->build();

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($this->getFieldsWithValues());
            $this->id =  $this->pdo->lastInsertId();
        } else {
            $query = $this->queryBuilder->update($fields)->where('id', QueryBuilder::EQUAL)->build();
            $stmt =  $this->pdo->prepare($query);
            $stmt->execute(['id' => $this->id, ...$this->getFieldsWithValues()]);
        }
    }

    protected abstract function getTableName(): string;
    protected abstract function getFieldsNames(): array;
    protected abstract function getFieldsWithValues(): array;
}
