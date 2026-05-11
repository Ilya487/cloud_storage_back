<?php

namespace App\Http;

use Exception;

class Request
{
    private const TEMP_STREAM_SIZE = 10 * 1024 * 1024;

    public readonly string $endPoint;
    public readonly string $method;
    private array $jsonCache;

    public function __construct()
    {
        $this->endPoint = $_SERVER['PATH_INFO'];
        $this->method = $_SERVER['REQUEST_METHOD'] ?: 'GET';
    }

    public function get(string $key): ?string
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

    /**
     * @return resource
     */
    public function getBodyAsResource()
    {
        $input = fopen('php://input', 'r');
        $resource = fopen('php://temp/maxmemory:' . self::TEMP_STREAM_SIZE, 'r+');

        if ($resource === false || $input === false) {
            if ($input === false) fclose($input);
            if ($resource === false) fclose($resource);

            throw new Exception("Не удалось открыть ресурс для чтения тела запроса");
        }

        if (stream_copy_to_stream($input, $resource) === false) {
            fclose($input);
            fclose($resource);

            throw new Exception("Не удалось прочитать тело запроса");
        }
        fclose($input);

        rewind($resource);
        return $resource;
    }
}
