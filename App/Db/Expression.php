<?php

namespace App\Db;

class Expression
{
    private function __construct(private string $query) {}

    public static function equal(string $field)
    {
        return new self("$field = :$field ");
    }

    public static function notEqual(string $field)
    {
        return new self("$field != :$field ");
    }

    public static function less(string $field)
    {
        return new self("$field < :$field ");
    }

    public static function lessEqual(string $field)
    {
        return new self("$field <= :$field ");
    }

    public static function more(string $field)
    {
        return new self("$field > :$field ");
    }

    public static function moreEqual(string $field, ?string $paramName = null)
    {
        if (is_null($paramName)) $paramName = $field;
        return new self("$field >= :$paramName ");
    }

    public static function isNull(string $field)
    {
        return new self("$field IS NULL ");
    }

    public static function like(string $field, string $pattern)
    {
        return new self("$field LIKE :$pattern ");
    }

    public static function in(string $field, array $values)
    {
        $tmp =  array_map(function ($value) {
            return ':' . $value;
        }, $values);

        $tmp =  implode(', ', $tmp);

        return new self("$field IN ($tmp) ");
    }

    public function __toString()
    {
        return $this->query;
    }
}
