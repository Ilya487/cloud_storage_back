<?php

namespace App\Http;

use Exception;

class Response
{
    private string $body = '';

    public function __construct()
    {
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
        $json = json_encode($data);
        if (!$json) {
            throw new Exception('Ошибка JSON: ' . json_last_error_msg());
        }

        echo $json;
        die;
    }

    public function sendDownloadResponse(string $path)
    {
        $filename = rawurlencode(basename($path));
        $this->setHeader('Content-Type', '');
        $this->setHeader('Content-Disposition', "attachment; filename=$filename");
        $this->setHeader('X-Accel-Redirect', $path);
        die;
    }

    public function outputFile($path)
    {
        $filename = rawurlencode(basename($path));
        $this->setHeader('Content-Type', '');
        $this->setHeader('Content-Disposition', "inline; filename=$filename");
        $this->setHeader('X-Accel-Redirect', $path);
        die;
    }
}
