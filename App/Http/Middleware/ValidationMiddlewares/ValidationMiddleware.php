<?php

namespace App\Http\Middleware\ValidationMiddlewares;

use App\Http\Middleware\MiddlewareInterface;
use App\Http\Request;
use App\Http\Response;
use Exception;

abstract class ValidationMiddleware implements MiddlewareInterface
{
    const REQUIRE = 1;
    const STRING = 2;
    const INT = 4;
    const INT_OR_EMPTY = 8;
    const JSON = 'json';
    const GET = 'get';

    private array $validators = [
        self::REQUIRE => 'checkRequire',
        self::INT_OR_EMPTY => 'checkIntOrEmpty',
        self::STRING => 'checkStr',
        self::INT => 'checkInt'
    ];

    public function __construct(protected Request $request, protected Response $response) {}

    private function validateJson(): array
    {
        $data = $this->request->json();
        if (is_null($data)) {
            $this->sendError('Неверный JSON');
        }

        return $data;
    }

    protected function sendError(array|string $data)
    {
        if (is_string($data)) {
            $this->response->setStatusCode(400)->sendJson(['message' => $data]);
        }
        if (is_array($data)) {
            $this->response->setStatusCode(400)->sendJson(['errors' => $data]);
        }
    }

    protected function validate(int $rules, string $name, string $source): mixed
    {
        if ($source == self::JSON) {
            $data = $this->validateJson();
            $value = $data[$name];
        } else if ($source == self::GET) {
            $value = $this->request->get($name);
        } else throw new Exception('Указан неизвестный источник');

        if ($this->checkNonRequireValue($rules, $value)) {
            return $value;
        }
        foreach ($this->validators as $rule => $method) {
            if ($rules & $rule) {
                $this->$method($name, $value);
            }
        }

        return $value;
    }

    private function checkNonRequireValue(int $rules, $value): bool
    {
        return !($rules & self::REQUIRE) && ($value == '' || is_null($value));
    }

    private function checkInt(string $name, $value)
    {
        $res = is_int(filter_var($value, FILTER_VALIDATE_INT));
        if (!$res) {
            $this->sendError("$name имеет неверный тип");
        }
    }

    private function checkStr(string $name, $value)
    {
        $res = is_string($value);
        if (!$res) {
            $this->sendError("$name имеет неверный тип");
        }
    }

    private function checkRequire(string $name, $value)
    {
        $res = is_null($value);
        if ($res) {
            $this->sendError("Отсутствует обязательное поле $name");
        }
    }

    private function checkIntOrEmpty(string $name, $value)
    {
        if (is_string($value) && $value === '') {
            return;
        }
        $this->checkInt($name, $value);
    }
}
