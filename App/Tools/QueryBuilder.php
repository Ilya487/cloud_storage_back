<?php

namespace App\Tools;

class QueryBuilder
{
    public static function buildInsertQuery(array $fields, $tableName)
    {
        $preparedParams = self::getPreparedParams($fields);

        return 'INSERT INTO ' . $tableName . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $preparedParams) . ')';
    }

    private static function getPreparedParams($fields)
    {
        return array_map(function ($val) {
            return ':' . $val;
        }, $fields);
    }
}
