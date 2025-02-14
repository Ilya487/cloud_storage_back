<?php

namespace App\Http;

use App\Http\Request;
use Exception;

class Response
{
    private string $body = '';

    public function __construct()
    {
        $this->setCorsHeaders();
        http_response_code(200);
    }

    public function setHeader(string $name, string $value): Response
    {
        header("$name: $value");
        return $this;
    }

    public function setStatusCode(int $code): Response
    {
        if ($code < 100 || $code > 599) {
            throw new Exception('Некорректный код ответа');
        }
        http_response_code($code);
        return $this;
    }

    public function write(string $data): Response
    {
        $this->body .= $data;
        return $this;
    }

    public function send()
    {
        echo $this->body;
        die;
    }

    public function sendJson(array $data)
    {
        header('Content-type: application/json; charset=utf-8');
        $json = json_encode(['code' => http_response_code(), ...$data]);
        if (!$json) {
            throw new Exception('Ошибка JSON: ' . json_last_error_msg());
        }

        echo $json;
        die;
    }

    private function setCorsHeaders()
    {
        header("Access-Control-Allow-Origin:" . (new Request)->header('origin'));
        header('Access-Control-Allow-Credentials: true');
        header("Access-Control-Allow-Methods: GET, POST, PATCH, DELETE");
        header('Access-Control-Max-Age: 3600');
        $this->setHeader('Access-Control-Allow-Headers', 'X-Session-Id, X-Chunk-Num');
    }
}
