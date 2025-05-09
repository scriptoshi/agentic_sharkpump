<?php

return [
    /*
    |--------------------------------------------------------------------------
    | NowPayments API Key
    |--------------------------------------------------------------------------
    |
    | Your NowPayments API key. You can get it from your NowPayments dashboard.
    |
    */
    'api_key' => env('NOWPAYMENTS_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | NowPayments API URL
    |--------------------------------------------------------------------------
    |
    | The base URL for NowPayments API calls.
    |
    */
    'api_url' => env('NOWPAYMENTS_API_URL', 'https://api.nowpayments.io/v1'),

    /*
    |--------------------------------------------------------------------------
    | IPN Secret Key
    |--------------------------------------------------------------------------
    |
    | Your IPN secret key for verifying webhook requests from NowPayments.
    |
    */
    'ipn_secret_key' => env('NOWPAYMENTS_IPN_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Success URL
    |--------------------------------------------------------------------------
    |
    | The URL to redirect to after a successful payment.
    |
    */
    'success_url' => env('NOWPAYMENTS_SUCCESS_URL', '/contributions'),

    /*
    |--------------------------------------------------------------------------
    | Cancel URL
    |--------------------------------------------------------------------------
    |
    | The URL to redirect to if the payment is cancelled.
    |
    */
    'cancel_url' => env('NOWPAYMENTS_CANCEL_URL', '/contributions'),
];
