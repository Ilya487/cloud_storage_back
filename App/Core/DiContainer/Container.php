<?php

namespace App\Core\DiContainer;

use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionParameter;
use ReflectionUnionType;
use App\Core\DiContainer\ContainerException;

class Container
{
    private array $resolving = [];
    private array $readyObjects = [];

    public function __construct(private array $params, private array $transients, private array $realizations) {}

    /**
     * @template Type
     * @param class-string<Type> $className
     * @return Type
     */
    public function resolve(string $className): object
    {
        if (in_array($className, $this->resolving)) {
            throw new ContainerException('Обнаружена циклическая зависимость на класс ' . $className);
        }
        $this->resolving[]  = $className;

        try {
            if (!$this->isTransient($className) && !is_null($this->readyObjects[$className])) {
                return $this->readyObjects[$className];
            }

            $reflection = new ReflectionClass($className);

            if ($reflection->isInstantiable()) $resultObject = $this->resolveConcreteClass($reflection);
            else $resultObject = $this->resolveAbstraction($className);


            if ($this->isTransient($className))
                return $resultObject;
            $this->readyObjects[$className] = $resultObject;
            return $this->readyObjects[$className];
        } finally {
            array_pop($this->resolving);
        }
    }

    private function resolveAbstraction(string $className): object
    {
        if ($this->hasRealization($className)) return $this->resolve($this->realizations[$className]);
        else throw new ContainerException('Невозможно разрешить класс ' . $className . ' Укажите его реализацию');
    }

    private function resolveConcreteClass(ReflectionClass $reflection): object
    {
        $constructor = $reflection->getConstructor();

        if (!$constructor || $constructor->getParameters() == []) return  $reflection->newInstance();
        else return $this->resolveClassDependencies($reflection);
    }

    private function resolveClassDependencies(ReflectionClass $reflection): object
    {
        $resolvedParams = [];

        $params = $reflection->getConstructor()->getParameters();
        foreach ($params as $param) {
            $resolvedParams[] = $this->resolveParam($param, $reflection->getName());
        }

        return $reflection->newInstance(...$resolvedParams);
    }

    private function resolveParam(ReflectionParameter $param, string $className): mixed
    {
        $paramName = $param->getName();
        $type = $param->getType();

        if (is_null($type)) {
            throw new ContainerException('Невозможно разрешить параметр ' . $paramName . ' класса ' . $className . ' не указан тип');
        }

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            throw new ContainerException('Пересекающиеся и объединяющие типы запрещены: ' . $className . 'параметр ' . $paramName);
        }

        if ($type->isBuiltin()) {
            if (!$this->hasParam($className, $paramName)) {
                throw new ContainerException('Невозможно разрешить класс ' . $className . ' Не передан примитивный параметр ' . "'$paramName'");
            }

            return $this->params[$className][$paramName];
        } else {
            return $this->resolve($type->getName());
        }
    }

    private function hasParam($className, $paramName): bool
    {
        return isset($this->params[$className][$paramName]);
    }

    private function hasRealization(string $abstractionName): bool
    {
        return isset($this->realizations[$abstractionName]);
    }

    private function isTransient($key): bool
    {
        return in_array($key, $this->transients);
    }
}
