<?php

namespace App\Enums;

enum ApiAuthType: string
{
    case NONE = 'none';
    case BASIC = 'basic';
    case BEARER = 'bearer';
    case API_KEY = 'api_key';
    case QUERY_PARAM = 'query_param';

    public function label(): string
    {
        return match ($this) {
            self::NONE => __('None'),
            self::BASIC => __('Basic Auth'),
            self::BEARER => __('Bearer Token'),
            self::API_KEY => __('API Key Header'),
            self::QUERY_PARAM => __('Query Parameter'),
        };
    }
}
