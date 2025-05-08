<?php

return [
    /**
     * Who provides the api keys.
     *
     * app - admin provides the api keys
     * user - users provide their own api keys
     */
    'provider' => env('AI_PROVIDER', 'app'),
    /**
     * All user to select a model
     * used only if the provider above is app
     */
    'allow_model_selection' => env('AI_ALLOW_MODEL_SELECTION', true),
    /**
     * Anthropic API Key
     */
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
    ],
    /**
     * OpenAI API Key
     */
    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
    ],
    /**
     * Gemini API Key
     */
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],
    /**
     * Enable bot billing
     */
    'bot_billing' => env('BOT_BILLING', true),
];
