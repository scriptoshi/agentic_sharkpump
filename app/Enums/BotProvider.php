<?php

namespace App\Enums;

use App\Models\TelegramUpdate;
use App\Services\AnthropicService;
use App\Services\OpenAiService;
use App\Services\GeminiService;
//use App\Services\DeepSeekService; //later

enum BotProvider: string
{
    case ANTHROPIC = 'anthropic';
    case OPENAI = 'openai';
    case GEMINI = 'gemini';
    //case DEEPSEEK = 'deepseek';

    /**
     * Get a description for the bot provider.
     * 
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::ANTHROPIC => 'Anthropic Claude',
            self::OPENAI => 'OpenAI ChatGPT',
            self::GEMINI => 'Google Gemini',
            //self::DEEPSEEK => 'DeepSeek',
        };
    }

    public function service(TelegramUpdate $telegramUpdate)
    {
        return match ($this) {
            self::ANTHROPIC => new AnthropicService($telegramUpdate),
            self::OPENAI => new OpenAiService($telegramUpdate),
            self::GEMINI => new GeminiService($telegramUpdate),
            //self::DEEPSEEK => new DeepSeekService($telegramUpdate),
        };
    }
}
