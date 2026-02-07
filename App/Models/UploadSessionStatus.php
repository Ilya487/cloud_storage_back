<?php

namespace App\Models;

enum UploadSessionStatus: string
{
    case UPLOADING = 'uploading';
    case BUILDING = 'building';
    case COMPLETE = 'complete';
    case CANCELLED = 'cancelled';
    case ERROR = 'error';
}
