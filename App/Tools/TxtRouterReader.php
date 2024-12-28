<?php

namespace App\Tools;

use App\Contracts\RoutesReader;
use App\dto\RouteDto;

class TxtRouterReader implements RoutesReader
{
    private const CONTROLLERS_NAMESPACE = 'App\Controllers\\';


    /**
     * @var RouteDto[]
     */
    protected array $routes = [];

    public function __construct(protected string $filePath) {}


    public function getRoutes(): array
    {
        $this->readFile();
        return $this->routes;
    }

    private function readFile()
    {
        $file = fopen($this->filePath, 'r');
        if (!$file) {
            return;
        }

        while ($line = fgets($file)) {
            if (!$line) {
                return;
            }
            $this->convertLine($line);
        }
        fclose($file);
    }

    private function convertLine($line)
    {

        $line = str_replace(["\n", "\r"], '', $line);
        [$regexp, $className] = explode(" ", $line);
        $className = self::CONTROLLERS_NAMESPACE . $className;
        $this->routes[] = (new RouteDto($regexp, $className));
    }
}
