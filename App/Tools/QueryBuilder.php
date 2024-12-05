<?php

namespace App\Tools;

class QueryBuilder
{
    public static function buildUpdateByIdQuery(array $fields, string $tableName)
    {
        $values = self::getPreparedParams($fields, true);

        return 'UPDATE ' . $tableName . ' SET ' . $values . ' WHERE id=:id';
    }

    public static function buildInsertQuery(array $fields, string $tableName)
    {
        $preparedParams = self::getPreparedParams($fields, false);

        return 'INSERT INTO ' . $tableName . ' (' . implode(', ', $fields) . ') VALUES (' . $preparedParams . ')';
    }

    private static function getPreparedParams(array $fields, bool $withNames)
    {
        $tmp =  array_map(function ($field) use ($withNames) {
            if ($withNames) {
                return  $field . '=:' . $field;
            }

            return ':' . $field;
        }, $fields);

        return implode(', ', $tmp);
    }
}
