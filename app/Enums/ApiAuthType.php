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
    public function description(): string
    {
        return match ($this) {
            self::NONE => __('You donot need any credentials to connect to this API'),
            self::BASIC => __('You will need to provide a username and password to connect to this API'),
            self::BEARER => __('You will need to provide a bearer token to connect to this API'),
            self::API_KEY => __('You will need to provide an API key to connect to this API'),
            self::QUERY_PARAM => __('You will need to provide an API key to connect to this API'),
        };
    }
}
