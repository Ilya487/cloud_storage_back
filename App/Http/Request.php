<?php

namespace App\Http;

class Request
{
    public readonly string $endPoint;
    public readonly string $method;

    public function __construct()
    {
        $this->endPoint = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->method = $_SERVER['REQUEST_METHOD'] ?: 'GET';
    }

    public function get(string $key)
    {
        return $_GET[$key] ?? null;
    }

    public function post(string $key)
    {
        return $_POST[$key] ?? null;
    }

    public function header(string $name): ?string
    {
        $modifyName = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$modifyName] ?? null;
    }
}
