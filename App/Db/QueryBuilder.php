<?php

namespace App\Db;

use App\Db\Query;
use Exception;

class QueryBuilder
{
    private ?QueryType $type = null;

    private array $selectFields = [];
    private string $updateFields = '';
    private array $insertFields = [];
    private string $valuesStr = '';

    private array $whereClause = [];
    private ?int $limit = null;
    private int $offset = 0;

    private bool $lockForUpdate = false;

    private array $params = [];

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

    /**
     * @param array<string,mixed> $fields
     */
    public function update(array $fields)
    {
        if (empty($fields)) throw new Exception('Необходимо передать хотя бы одно поле для обновления');

        $this->setType(QueryType::UPDATE);

        $fields = $this->parseParams($fields);
        $this->updateFields = $this->getPreparedParams($fields) . ', ';

        return $this;
    }

    /**
     * @param array<string,mixed>[] $values
     */
    public function insert(array $fields, array $values)
    {
        $this->setType(QueryType::INSERT);

        if (empty($fields)) throw new Exception('Необходимо передать хотя бы одно поле для вставки');
        if (empty($values)) throw new Exception('Необходимо передать хотя бы один массив значений для вставки');

        $this->insertFields = $fields;
        $this->valuesStr = $this->parseInsertParams($fields, $values);

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
        $this->whereClause[] = ['type' => 'AND', 'condition' => $expression->query];
        $this->params = array_merge($this->params, $expression->params);

        return $this;
    }

    public function whereRaw(string $expression, array $params = [])
    {
        $expression = $this->addSpaceToRawExp($expression);
        $this->whereClause[] = ['type' => 'AND', 'condition' => $expression];
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    public function and(Expression $expression)
    {
        $this->whereClause[] = ['type' => 'AND', 'condition' => $expression->query];
        $this->params = array_merge($this->params, $expression->params);

        return $this;
    }

    public function andRaw(string $expression, array $params = [])
    {
        $expression = $this->addSpaceToRawExp($expression);
        $this->whereClause[] = ['type' => 'AND', 'condition' => $expression];
        $this->params = array_merge($this->params, $params);

        return $this;
    }

    public function or(Expression $expression)
    {
        $this->whereClause[] = ['type' => 'OR', 'condition' => $expression->query];
        $this->params = array_merge($this->params, $expression->params);

        return $this;
    }

    public function orRaw(string $expression, array $params = [])
    {
        $expression = $this->addSpaceToRawExp($expression);
        $this->whereClause[] = ['type' => 'OR', 'condition' => $expression];
        $this->params = array_merge($this->params, $params);

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
        if ($this->type !== null && $this->type !== QueryType::UPDATE)
            throw new Exception('Невозможно использовать оператор subtract в запросе с типом отличным от UPDATE');
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

    public function build(): Query
    {
        $query = '';
        if ($this->type == QueryType::SELECT) $query .= $this->buildSelect();
        else if ($this->type == QueryType::DELETE) $query .= $this->buildDelete();
        else if ($this->type == QueryType::UPDATE) $query .= $this->buildUpdate();
        if ($this->type == QueryType::INSERT) {
            $query .= $this->buildInsert();
            $params = $this->params;
            $this->resetQuery();

            return new Query($query, $params);
        }

        $query .= $this->buildWhere();

        if ($this->type == QueryType::SELECT) {
            $query .= $this->buildLimit();
            $query .= $this->buildForUpdate();
        }

        $params = $this->params;
        $this->resetQuery();

        return new Query($query, $params);
    }

    public function lockForUpdate(): self
    {
        if ($this->type === null) $this->setType(QueryType::SELECT);
        if ($this->type !== QueryType::SELECT)
            throw new Exception('Невозможно сделать FOR UPDATE для запроса с типом отличным от SELECT');

        $this->lockForUpdate = true;
        return $this;
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
        $this->valuesStr = '';
        $this->limit = null;
        $this->offset = 0;
        $this->params = [];
        $this->lockForUpdate = false;

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
        $insertFields = implode(', ', $this->insertFields);

        return "{$this->type->value} INTO {$this->tableName} ($insertFields) VALUES {$this->valuesStr} ";
    }

    private function buildLimit(): string
    {
        if ($this->limit === null) return '';
        return "LIMIT {$this->limit} OFFSET {$this->offset} ";
    }

    private function buildWhere(): string
    {
        if (empty($this->whereClause)) return '';

        $res = $this->type === null ? '' : 'WHERE ';
        foreach ($this->whereClause as $i => $value) {
            if ($i == 0) {
                $res .= $value['condition'];
                continue;
            }

            $res .= "{$value['type']} {$value['condition']}";
        }

        return $res;
    }

    private function buildForUpdate(): string
    {
        if ($this->lockForUpdate) return 'FOR UPDATE ';
        else return '';
    }

    private function setType(QueryType $type)
    {
        if ($this->type !== null) throw new Exception('Тип запроса уже установлен!');
        $this->type = $type;
    }

    private function parseParams(array $params): array
    {
        $paramsForPrepare = [];

        foreach ($params as $name => $val) {
            if (!is_string($name)) continue;
            if ($val instanceof Expression && $val->isRaw) {
                $paramsForPrepare[$name]  = $val->query;
                $this->params = [...$this->params, ...$val->params];
            } else {
                $paramsForPrepare[] = $name;
                $this->params[$name] = $val;
            }
        }

        return $paramsForPrepare;
    }

    private function parseInsertParams(array $fieldsNames, array $params): string
    {
        $valuesStr =  '';

        foreach ($params as $rowNum => $paramRow) {
            $valuesStr .= '(';

            foreach ($fieldsNames as $fieldName) {
                $paramVal = $paramRow[$fieldName];
                if ($paramVal instanceof Expression && $paramVal->isRaw) {
                    $this->params = [...$this->params, ...$paramVal->params];
                    $valuesStr .= $paramVal->query . ', ';
                    continue;
                }

                $this->params[$rowNum . $fieldName] = $paramRow[$fieldName];
                $valuesStr .= ':' . $rowNum . $fieldName . ', ';
            }

            $valuesStr = rtrim($valuesStr, ', ');
            $valuesStr .= '), ';
        }

        $valuesStr = rtrim($valuesStr, ', ');

        return $valuesStr;
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
