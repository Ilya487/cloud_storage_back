<?php

namespace App\Db;

class Expression
{
    private function __construct(
        public readonly string $query,
        public readonly array $params = [],
        public readonly bool $isRaw = false
    ) {}

    public static function equal(string $field, $value, ?string $paramName = null)
    {
        if ($paramName === null) $paramName = $field;
        return new self("$field = :$paramName ", [$paramName => $value]);
    }

    public static function notEqual(string $field, $value, ?string $paramName = null)
    {
        if ($paramName === null) $paramName = $field;
        return new self("$field != :$paramName ", [$paramName => $value]);
    }

    public static function less(string $field, $value, ?string $paramName = null)
    {
        if ($paramName === null) $paramName = $field;
        return new self("$field < :$paramName ", [$paramName => $value]);
    }

    public static function lessEqual(string $field, $value, ?string $paramName = null)
    {
        if ($paramName === null) $paramName = $field;
        return new self("$field <= :$paramName ", [$paramName => $value]);
    }

    public static function more(string $field, $value, ?string $paramName = null)
    {
        if ($paramName === null) $paramName = $field;
        return new self("$field > :$paramName ", [$paramName => $value]);
    }

    public static function moreEqual(string $field, $value, ?string $paramName = null)
    {
        if ($paramName === null) $paramName = $field;
        return new self("$field >= :$paramName ",  [$paramName => $value]);
    }

    public static function isNull(string $field)
    {
        return new self("$field IS NULL ");
    }

    public static function notNull(string $field)
    {
        return new self("$field IS NOT NULL ");
    }

    public static function like(string $field, $value, ?string $paramName = null)
    {
        if ($paramName === null) $paramName = $field;
        return new self("$field LIKE :$paramName ", [$paramName => $value]);
    }

    public static function in(string $field, array $values, ?string $paramName = null)
    {
        if ($paramName === null) $paramName = $field;

        $params = [];
        $str = '';
        foreach ($values as $i => $val) {
            $name = ":$i" . $paramName;
            $params[$name] = $val;
            $str .= "$name,";
        }
        $str = rtrim($str, ',');

        return new self("$field IN ($str) ", $params);
    }

    public static function raw(string $query, array $params = [])
    {
        return new self($query, $params, true);
    }
}
