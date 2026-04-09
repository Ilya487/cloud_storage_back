<?php

namespace App\Models\Collections;

use App\Models\CreateArchiveTask;

/**
 * @extends Collection<CreateArchiveTask>
 */
class CreateArchiveTaskCollection extends Collection
{
    public static function createFromDbArr(array $tasksArr): self
    {
        $arr = [];
        foreach ($tasksArr as $task) {
            $obj = CreateArchiveTask::createFromArr($task);
            $arr[] = $obj;
        }

        return new self($arr);
    }
}
