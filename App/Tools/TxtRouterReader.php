<?php

namespace App\Tools;

use App\dto\RouterDtoRepo;
use App\dto\RouterDto;
use App\Contracts\RoutesReader;

class TxtRouterReader extends RoutesReader
{
    public function getRoutes(): RouterDtoRepo
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
        [$regexp, $className] = explode(' ', $line);
        $this->routes->addRoute(new RouterDto($regexp, $className));
    }
}
