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
    }

    public function sendJson(array $data)
    {
        header('Content-type: application/json; charset=utf-8');
        $json = json_encode($data);
        if (!$json) {
            throw new Exception('Ошибка JSON: ' . json_last_error_msg());
        }

        echo $json;
    }

    private function setCorsHeaders()
    {
        header("Access-Control-Allow-Origin:" . (new Request)->header('origin'));
    }
}