<?php

namespace App\Http\Controllers;

use App\Models\Bot;
use App\Models\Chat;
use App\Models\TelegramUpdate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Telegram\Bot\Api;
use App\Services\Telegram;

class WebhooksController extends Controller
{
    public function __invoke(Request $request, Bot $bot)
    {
        $telegram = new Api($bot->bot_token, true);
        $update = $telegram->getWebhookUpdate();
        // Get the update ID
        $telegramUpdateId = $update['update_id'] ?? null;
        if (!$telegramUpdateId) {
            Log::error('Telegram update missing update_id', ['bot_id' => $bot->id, 'update' => $update]);
            return response(status: 200); // always return 200 to avoid telegram retrying the update
        }
        // Extract allowed update types
        $allowedTypes = collect(['message', 'edited_message', 'inline_query', 'callback_query', 'pre_checkout_query']);
        $updateKeys = $update->keys();
        if ($updateKeys->intersect($allowedTypes)->isEmpty()) {
            //silently ignore updates that are not allowed
            return response(status: 200);
        }
        $msgEntry = null;
        $msg = null;
        foreach ($allowedTypes as $type) {
            if (isset($update[$type])) continue;
            $msgEntry = [$type => $update[$type]];
            $msg = $update[$type];
            break;
        }
        $user = User::where('telegramId', $msg['from']['id'])->first();
        if (!$user) {
            $user = User::create([
                'telegramId' => $msg['from']['id'],
                'name' => $msg['from']['first_name'],
                'username' => $msg['from']['username'],
            ]);
        }
        $user->botUsers()->attach($bot->id);
        $chat = Chat::where('telegram_chat_id', $msg['chat']['id'])->firstOrCreate(
            [
                'telegram_user_id' => $msg['from']['id'],
                'bot_id' => $bot->id,
            ],
            [
                'user_id' => $user->id,
                'uuid' => Str::uuid(),
                'telegram_chat_id' => $msg['chat']['id'] ?? null,
                'ai_conversation_id' => null,
            ]
        );
        // Store the update
        $command = Telegram::getCommand($update);
        $telegramUpdate = TelegramUpdate::create([
            'bot_id' => $bot->id,
            'user_id' => $user->id,
            'chat_id' => $chat->id,
            'command_id' => $command->id,
            'telegram_update_id' => $telegramUpdateId,
            'type' => array_keys($msgEntry)[0],
            ...$msg,
        ]);
        $telegramUpdate->load(['command', 'chat', 'user', 'bot']);
        $aiService = $bot->provider->service($telegramUpdate);
        $aiService->handle();
        return response()->json(['status' => 'ok']);
    }
}
