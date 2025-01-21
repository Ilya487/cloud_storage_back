<?php

namespace App\Tools;

class QueryBuilder
{
    const EQUAL = '=';
    const NOT_EQUAL = '!=';
    const LESS = '<';
    const LESS_EQUAL = '<=';
    const MORE = '>';
    const MORE_EQUAL = '>=';
    const IS_NULL = 'IS NULL';

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

    public function where(string $field, string $operation)
    {
        $this->query .= str_contains($this->query, 'WHERE') ? '' : 'WHERE ';
        $this->query .= $operation === QueryBuilder::IS_NULL ? "$field $operation " : "$field $operation :$field ";
        return $this;
    }

    public function not()
    {
        $this->query .= 'NOT ';
        return $this;
    }

    public function and($field, $operation)
    {
        $this->query .= 'AND ';
        return $this->where($field, $operation);
    }

    public function or()
    {
        $this->query .= 'OR ';
        return $this;
    }

    public function build()
    {
        return $this->query;
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
