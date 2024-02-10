<?php

namespace App\dto;

use App\dto\RouterDto;

/** 
 * Класс нужен, чтобы клиент мог прозрачно работать с типом RouterDto в foreach
 */

class RouterDtoRepo implements \Iterator
{
    private int $position;
    private array $data;

    function addRoute(RouterDto $item)
    {
        $this->data[] = $item;
    }

    function isEmpty(): bool
    {
        return empty($this->data);
    }

    function rewind(): void
    {
        $this->position = 0;
    }

    public function current(): RouterDto
    {
        return $this->data[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        $this->position++;
    }

    public function valid(): bool
    {
        return isset($this->data[$this->position]);
    }
}
