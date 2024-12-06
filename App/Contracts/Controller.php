<?php

namespace App\Contracts;

abstract class Controller
{
    protected const CONTENT_TYPE_JSON = 'Content-type: application/json; charset=utf-8';

    final public function __construct()
    {
        header("Access-Control-Allow-Origin:" . $_SERVER['HTTP_ORIGIN']);
    }

    protected function sendAnswer(int $code, array $data = [])
    {
        header(self::CONTENT_TYPE_JSON);
        http_response_code($code);

        $json = json_encode([
            'code' => $code,
            ...$data
        ]);

        echo $json;
    }

    abstract public function resolve(): void;
}
