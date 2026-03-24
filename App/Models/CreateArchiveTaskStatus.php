<?php

namespace App\Models;

enum CreateArchiveTaskStatus: string
{
    case PREPARING = 'preparing';
    case READY = 'ready';
    case ERROR = 'error';
}
