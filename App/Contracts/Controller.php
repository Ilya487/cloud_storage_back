<?php

namespace App\Contracts;

abstract class Controller
{
    protected const CONTENT_TYPE_JSON = 'Content-type: application/json; charset=utf-8';

    final public function __construct()
    {
        header("Access-Control-Allow-Origin:" . $_SERVER['HTTP_ORIGIN']);
    }


    abstract public function resolve(): void;
}
