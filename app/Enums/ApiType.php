<?php

namespace App\Enums;

enum ApiType: string
{
    case USER = 'user'; // user defined api
    case SYSTEM = 'system'; // system defined api
}
