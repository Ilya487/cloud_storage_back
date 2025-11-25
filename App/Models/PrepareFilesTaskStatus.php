<?php

namespace App\Models;

enum PrepareFilesTaskStatus: string
{
    case PREPARING = 'preparing';
    case READY = 'ready';
    case ERROR = 'error';
}
