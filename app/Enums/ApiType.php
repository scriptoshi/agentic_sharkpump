<?php

namespace App\Enums;

enum ApiType: string
{
    case USER = 'user';
    case SYSTEM = 'system';
    case GEMINI = 'gemini';
}
