<?php

namespace App\Db;

use Exception;

class QueryBuilder
{
    private ?QueryType $type = null;

    private array $selectFields = [];
    private string $updateFields = '';
    private array $insertFields = [];
    private int $insertCount = 1;

    private array $whereClause = [];
    private ?int $limit = null;
    private int $offset = 0;

    public function __construct(private string $tableName) {}

    public function select(array $fields = [])
    {
        $this->selectFields = $fields;
        $this->setType(QueryType::SELECT);

        return $this;
    }

    public function delete()
    {
        $this->setType(QueryType::DELETE);

        return $this;
    }

    public function update(array $fields)
    {
        if (empty($fields)) throw new Exception('Необходимо передать хотя бы одно поле для обновления');

        $this->setType(QueryType::UPDATE);

        $this->updateFields = $this->getPreparedParams($fields) . ', ';

        return $this;
    }

    public function insert(array $fields, int $insertCount = 1)
    {
        $this->setType(QueryType::INSERT);

        if (empty($fields)) throw new Exception('Необходимо передать хотя бы одно поле для вставки');
        if ($insertCount < 1) throw new Exception('Неккоректное значение для параметра $insertCount');

        $this->insertFields = $fields;
        $this->insertCount = $insertCount;

        return $this;
    }

    public function count()
    {
        $this->setType(QueryType::SELECT);
        $this->selectFields = ['COUNT(*)'];

        return $this;
    }

    public function where(Expression $expression)
    {
        $this->whereClause[] = ['type' => 'AND', 'condition' => $expression];

        return $this;
    }

    public function whereRaw(string $expression)
    {
        $expression = $this->addSpaceToRawExp($expression);
        $this->whereClause[] = ['type' => 'AND', 'condition' => $expression];

        return $this;
    }

    public function and(Expression $expression)
    {
        $this->whereClause[] = ['type' => 'AND', 'condition' => $expression];;

        return $this;
    }

    public function andRaw(string $expression)
    {
        $expression = $this->addSpaceToRawExp($expression);
        $this->whereClause[] = ['type' => 'AND', 'condition' => $expression];;

        return $this;
    }

    public function or(Expression $expression)
    {
        $this->whereClause[] = ['type' => 'OR', 'condition' => $expression];;

        return $this;
    }

    public function orRaw(string $expression)
    {
        $expression = $this->addSpaceToRawExp($expression);
        $this->whereClause[] = ['type' => 'OR', 'condition' => $expression];;

        return $this;
    }

    public function add(string $fieldName, int $value)
    {
        if ($this->type !== null && $this->type !== QueryType::UPDATE)
            throw new Exception('Невозможно использовать оператор add в запросе с типом отличным от UPDATE');
        if ($this->type == null)
            $this->setType(QueryType::UPDATE);
        $this->updateFields .= "$fieldName=$fieldName+$value, ";

        return $this;
    }

    public function subtract(string $fieldName, int $value)
    {
        if ($this->type !== QueryType::UPDATE) throw new Exception('Невозможно использовать оператор add в запросе с типом отличным от UPDATE');
        if ($this->type == null)
            $this->setType(QueryType::UPDATE);
        $this->updateFields .= "$fieldName=$fieldName-$value, ";

        return $this;
    }

    public function limit(int $limit, int $offset = 0)
    {
        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    public function build(): string
    {
        $res = '';
        if ($this->type == QueryType::SELECT) $res .= $this->buildSelect();
        if ($this->type == QueryType::DELETE) $res .= $this->buildDelete();
        if ($this->type == QueryType::UPDATE) $res .= $this->buildUpdate();
        if ($this->type == QueryType::INSERT) {
            $res .= $this->buildInsert();
            $this->resetQuery();

            return $res;
        }

        $res .= $this->buildWhere();

        if ($this->type == QueryType::SELECT) {
            $res .= $this->buildLimit();
        }

        $this->resetQuery();
        return $res;
    }

    public function newQuery(?string $tableName = null): self
    {
        $tableName = $tableName ?? $this->tableName;
        return new self($tableName);
    }


    private function resetQuery()
    {
        $this->type = null;
        $this->whereClause = [];
        $this->selectFields = [];
        $this->updateFields = '';
        $this->insertFields = [];
        $this->insertCount = 1;
        $this->limit = null;
        $this->offset = 0;

        return $this;
    }

    private function buildSelect(): string
    {
        $fieldsForSelect = '';
        if (empty($this->selectFields)) $fieldsForSelect = '*';
        else $fieldsForSelect = join(', ', $this->selectFields);

        return "{$this->type->value} $fieldsForSelect FROM {$this->tableName} ";
    }

    private function buildDelete(): string
    {
        return "{$this->type->value} FROM {$this->tableName} ";
    }

    private function buildUpdate(): string
    {
        $updateFields = rtrim($this->updateFields, ', ');
        return "{$this->type->value} {$this->tableName} SET $updateFields ";
    }

    private function buildInsert(): string
    {
        $insertFields = [];
        foreach ($this->insertFields as $key => $value) {
            if (is_string($key)) $insertFields[] = $key;
            else $insertFields[] = $value;
        }
        $insertFields = implode(', ', $insertFields);

        if ($this->insertCount == 1) {
            $paramNames = $this->getPreparedParams($this->insertFields);
            $paramNames = '(' . rtrim($paramNames, ', ') . ')';
        } else {
            $paramNames = '';
            for ($i = 0; $i < $this->insertCount; $i++) {
                $paramNames .= '(' . $this->getPreparedParams($this->insertFields, $i) . '), ';
            }
            $paramNames = rtrim($paramNames, ', ');
        }


        return "{$this->type->value} INTO {$this->tableName} ($insertFields) VALUES $paramNames ";
    }

    private function buildLimit(): string
    {
        if ($this->limit === null) return '';
        return "LIMIT {$this->limit} OFFSET {$this->offset} ";
    }

    private function buildWhere(): string
    {
        if (empty($this->whereClause)) return '';

        $res = 'WHERE ';
        foreach ($this->whereClause as $i => $value) {
            if ($i == 0) {
                $res .= $value['condition'];
                continue;
            }

            $res .= "{$value['type']} {$value['condition']}";
        }

        return $res;
    }

    private function setType(QueryType $type)
    {
        if ($this->type !== null) throw new Exception('Тип запроса уже установлен!');
        $this->type = $type;
    }

    private function getPreparedParams(array $fields, string $prefix = '')
    {
        $res = '';
        foreach ($fields as $key => $val) {
            if (is_string($key)) {
                $res .= "$key=" . "$val, ";
                continue;
            }
            $res .= "$val=:" . $prefix . "$val, ";
        }

        return rtrim($res, ', ');
    }

    private function addSpaceToRawExp(string $expression): string
    {
        if ($expression[strlen($expression) - 1] !== ' ') return $expression . ' ';
        else return $expression;
    }
}

enum QueryType: string
{
    case SELECT = 'SELECT';
    case INSERT = 'INSERT';
    case UPDATE = 'UPDATE';
    case DELETE = 'DELETE';
}
