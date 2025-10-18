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
    const LIKE = 'LIKE';

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

    public function where(string $field, string $operation, string $value = '')
    {
        $this->query .= str_contains($this->query, 'WHERE') ? '' : 'WHERE ';

        switch ($operation) {
            case self::IS_NULL:
                $this->query .= "$field $operation ";
                break;

            case self::LIKE:
                $this->query .= "$field $operation :$value ";
                break;

            default:
                $this->query .= "$field $operation :$field ";
                break;
        }

        return $this;
    }

    public function not()
    {
        $this->query .= 'NOT ';
        return $this;
    }

    public function and(string $field, string $operation, string $value = '')
    {
        $this->query .= 'AND ';
        return $this->where($field, $operation, $value);
    }

    public function or(string $field, string $operation, string $value = '')
    {
        $this->query .= 'OR ';
        return $this->where($field, $operation, $value);
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
