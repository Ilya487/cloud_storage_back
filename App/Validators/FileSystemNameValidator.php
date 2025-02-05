<?php

namespace App\Validators;

class FileSystemNameValidator
{
    private array $invalidChars = ['<', '>', ':', '"', '/', '\\', '|', '?', '*'];
    private array $invalidNames = ['CON', 'PRN', 'AUX', 'NUL', 'COM', 'LPT'];
    private string $dirName;
    private array $errors = [];


    public function __construct(string $dirName)
    {
        $this->dirName = strtoupper($dirName);
    }

    public function validate(): array
    {
        $this->checkChars();
        $this->checkName();
        $this->checkLength();

        return $this->errors;
    }

    private function checkName(): bool
    {
        $name = pathinfo($this->dirName, PATHINFO_FILENAME);
        if (in_array($name, $this->invalidNames)) {
            $this->errors[] =  'Недопустимое название';
            return false;
        }

        return true;
    }

    private function checkChars(): bool
    {
        if (strpbrk($this->dirName, implode('', $this->invalidChars))) {
            $this->errors[] =  'Использован недопустимый символ';
            return false;
        } else return true;
    }

    private function checkLength(): bool
    {
        if (strlen($this->dirName) == 0) {
            $this->errors[] = 'Имя должно состоять хотя бы из 1 символа';
            return false;
        }

        if (mb_strlen($this->dirName) > 255) {
            $this->errors[] = 'Длина пути превышает 255 символов';
            return false;
        }

        return true;
    }
}
