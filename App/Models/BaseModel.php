<?php

namespace App\Models;

use App\Tools\DbConnect;
use PDO;
use App\Tools\QueryBuilder;

abstract class BaseModel
{
    private PDO $pdo;
    private ?string $id = null;

    public function __construct()
    {
        $this->pdo = DbConnect::getConnection();
    }

    public  function save()
    {
        $tableName = $this->getTableName();
        $fields = $this->getFieldsNames();

        if (is_null($this->id)) {
            $query = QueryBuilder::buildInsertQuery($fields, $tableName);

            $stmt = $this->pdo->prepare($query);
            $stmt->execute($this->getFieldsWithValues());
            $this->id =  $this->pdo->lastInsertId();
        } else {
            $query = QueryBuilder::buildUpdateByIdQuery($this->getFieldsNames(), $this->getTableName());
            $stmt =  $this->pdo->prepare($query);
            $stmt->execute($this->getFieldsWithValues());
        }
    }

    protected abstract function getTableName(): string;
    protected abstract function getFieldsNames(): array;
    protected abstract function getFieldsWithValues(): array;
}
