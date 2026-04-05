<?php

namespace App\Models\Collections;

use App\Models\UploadSession;

/**
 * @extends Collection<UploadSession>
 */
class UploadSessionCollection extends Collection
{
    public static function createFromDbArr(array $sessionArr): self
    {
        $arr = [];
        foreach ($sessionArr as $session) {
            $obj = UploadSession::createFromArr($session);
            $arr[] = $obj;
        }

        return new self($arr);
    }
}
