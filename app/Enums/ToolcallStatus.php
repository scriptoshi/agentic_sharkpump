<?php

namespace App\Enums;

enum ToolcallStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case ERROR = 'error';
}
