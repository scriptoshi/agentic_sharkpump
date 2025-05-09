<?php

namespace App\Enums;

enum FundAccess: string
{
    case DOWNLOAD = 'download';
    case LIFETIME = 'lifetime';
    case MAX_LIFETIME = 'max_lifetime';
    case PRO_LIFETIME = 'pro_lifetime';
}
