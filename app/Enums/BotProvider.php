<?php

namespace App\Enums;

enum BotProvider: string
{
    case ANTHROPIC = 'anthropic';
    case OPENAI = 'openai';
    case GEMINI = 'gemini';
}
