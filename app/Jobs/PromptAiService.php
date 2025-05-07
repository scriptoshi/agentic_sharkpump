<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\TelegramUpdate;

class PromptAiService implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public TelegramUpdate $telegramUpdate) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $telegramUpdate = $this->telegramUpdate;
        $telegramUpdate->load(['command', 'chat', 'user', 'bot']);
        $aiService = $telegramUpdate->bot->provider->service($telegramUpdate);
        $message = $aiService->prompt();
        
    }
}
