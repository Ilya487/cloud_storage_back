<?php

namespace App\Http;

class Request
{
    public readonly string $endPoint;
    public readonly string $method;
    private array $jsonCache;

    public function __construct()
    {
        $this->endPoint = $_SERVER['PATH_INFO'];
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

    public function json(): ?array
    {
        if (isset($this->jsonCache)) return $this->jsonCache;

        $data = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() == JSON_ERROR_NONE) {
            $this->jsonCache = $data;
            return $data;
        } else return null;
    }

    public function body(): string
    {
        return file_get_contents('php://input');
    }
}
