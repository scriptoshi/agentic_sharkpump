<?php

namespace App\Enums;

enum BotProvider: string
{
    case ANTHROPIC = 'anthropic';
    case OPENAI = 'openai';
    case CLAUDE = 'claude';

    /**
     * Get a description for the bot provider.
     * 
     * @return string
     */
    public function description(): string
    {
        return match ($this) {
            self::ANTHROPIC => 'Anthropic',
            self::OPENAI => 'OpenAI',
            self::CLAUDE => 'Claude',
        };
    }
}
