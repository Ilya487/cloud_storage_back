<?php

namespace App\Core\DiContainer;

use App\Core\DiContainer\ContainerException;
use App\Core\DiContainer\Container;

class ContainerBuilder
{
    private array $params = [];
    private array $singletons = [];
    private array $realizations = [];

    public function setParam(ContainerParam $param)
    {
        if (isset($this->params[$param->className][$param->paramName])) throw new ContainerException('Попытка переопределить существущий параметр в контейнере');
        $this->params[$param->className][$param->paramName] = $param->value;
    }

    public function bind(string $abstractionName, string $realizationClass)
    {
        if (is_subclass_of($realizationClass, $abstractionName)) {

            $this->realizations[$abstractionName] = $realizationClass;
        } else throw new ContainerException('Класс ' . $realizationClass . ' не является реализацией абстракции ' . $abstractionName);
    }

    public function share(string $className)
    {
        if (isset($this->singltons[$className])) return;
        $this->singletons[$className] = null;
    }

    public function build(): Container
    {
        return new Container($this->params, $this->singletons, $this->realizations);
    }
}
