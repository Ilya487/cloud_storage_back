<?php

namespace App\Db;

class QueryBuilder
{
    private $query;

    public function __construct(private string $tableName) {}

    public function select(array $fields = [])
    {
        $this->resetQuery();
        $preparedParams = count($fields) == 0 ? '*' : implode(', ', $fields);
        $this->query .= "SELECT $preparedParams FROM $this->tableName ";
        return $this;
    }

    public function delete()
    {
        $this->resetQuery();
        $this->query .= "DELETE FROM $this->tableName ";
        return $this;
    }

    public function update(array $fields)
    {
        $this->resetQuery();
        $preparedParams = $this->getPreparedParams($fields, true);
        $this->query .= "UPDATE $this->tableName SET $preparedParams ";
        return $this;
    }

    public function insert(array $fields)
    {
        $this->resetQuery();
        $this->query .= "INSERT INTO $this->tableName (" . implode(', ', $fields) . ") VALUES (" . $this->getPreparedParams($fields) . ")";
        return $this;
    }

    public function count()
    {
        $this->resetQuery();
        $this->query .= "SELECT COUNT(*) FROM $this->tableName ";
        return $this;
    }

    public function where(Expression $expression)
    {
        $this->query .= str_contains($this->query, 'WHERE') ? '' : 'WHERE ';
        $this->query .= $expression;

        return $this;
    }

    public function not()
    {
        $this->query .= 'NOT ';
        return $this;
    }

    public function and(Expression $expression)
    {
        $this->query .= "AND $expression";
        return $this;
    }

    public function or(Expression $expression)
    {
        $this->query .= "OR $expression";
        return $this;
    }

    public function build()
    {
        return $this->query;
    }

    public function incrementField(string $fieldName)
    {
        $this->resetQuery();
        $this->query = "UPDATE $this->tableName SET $fieldName=$fieldName+1 ";
        return $this;
    }

    private function resetQuery()
    {
        $this->query = '';
    }

    private function getPreparedParams(array $fields, bool $withNames = false)
    {
        $tmp =  array_map(function ($field) use ($withNames) {
            if ($withNames) {
                return  $field . ' = :' . $field;
            }

            return ':' . $field;
        }, $fields);

        return implode(', ', $tmp);
    }
}
