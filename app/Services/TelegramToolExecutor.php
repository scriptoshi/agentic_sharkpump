<?php

namespace App\Services;

use App\Models\TelegramLog;
use App\Models\Message;
use Exception;
use Illuminate\Support\Facades\File;
use Telegram\Bot\Api;

class TelegramToolExecutor
{

    static $tools = [
        'sendTelegramMessage',
        'sendTelegramPhoto',
        'sendTelegramDocument',
        'sendTelegramVideo',
        'sendTelegramAnimation',
        'sendTelegramVoice',
        'sendTelegramVideoNote',
        'sendTelegramSticker',
        'sendTelegramInvoice',
        'sendTelegramGame',
        'sendTelegramPoll',
        'sendTelegramChatAction',
        'sendTelegramLocation',
        'sendTelegramVenue',
        'sendTelegramContact',
    ];
    public static function methodIsTelegram(string $methodName): bool
    {
        return in_array($methodName, self::$tools);
    }

    public static function execute(Message $message, string $method, array $parameters, string $toolCallId, ?string $toolId = null): TelegramLog
    {
        $startTime = microtime(true);
        $message->load(['bot']);
        $bot = $message->bot;
        $telegram = new Api($bot->bot_token, true);
        $methodName = static::getMethodNameFromToolCall($method);
        try {
            if (isset($parameters['reply_markup']) && is_array($parameters['reply_markup'])) {
                $markup_data = $parameters['reply_markup'];
                if (isset($markup_data['inline_keyboard'])) {
                    // can take the full markup structure directly.
                    $parameters['reply_markup'] = $telegram->replyKeyboardMarkup($markup_data);
                } elseif (isset($markup_data['keyboard'])) {
                    // Same as above for ReplyKeyboardMarkup
                    $parameters['reply_markup'] = $telegram->replyKeyboardMarkup($markup_data);
                } elseif (isset($markup_data['remove_keyboard']) && $markup_data['remove_keyboard'] === true) {
                    // replyKeyboardHide (or replyKeyboardRemove in SDK v3) creates the structure
                    $removeOptions = [];
                    if (isset($markup_data['selective'])) {
                        $removeOptions['selective'] = $markup_data['selective'];
                    }
                    $parameters['reply_markup'] = $telegram->replyKeyboardHide($removeOptions);
                } elseif (isset($markup_data['force_reply']) && $markup_data['force_reply'] === true) {
                    // forceReply creates the structure
                    $forceReplyOptions = [];
                    if (isset($markup_data['input_field_placeholder'])) {
                        $forceReplyOptions['input_field_placeholder'] = $markup_data['input_field_placeholder'];
                    }
                    if (isset($markup_data['selective'])) {
                        $forceReplyOptions['selective'] = $markup_data['selective'];
                    }
                    $parameters['reply_markup'] = $telegram->forceReply($forceReplyOptions);
                }
            }
            $response = static::executeTelegramMethod($telegram, $methodName, $parameters);
            return TelegramLog::create([
                'bot_id' => $bot->id,
                'message_id' => $message->id,
                'tool_id' => $toolId,
                'tool_call_id' => $toolCallId,
                'method' => $methodName,
                'parameters' => $parameters,
                'response' => $response,
                'execution_time' => microtime(true) - $startTime,
                'success' => true,
            ]);
        } catch (Exception $e) {
            return  TelegramLog::create([
                'bot_id' => $bot->id,
                'message_id' => $message->id,
                'tool_id' => $toolId,
                'tool_call_id' => $toolCallId,
                'method' => $methodName,
                'parameters' => $parameters,
                'response' => null,
                'execution_time' => microtime(true) - $startTime,
                'success' => false,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    protected static function getMethodNameFromToolCall(string $toolCall): string
    {
        /** we shall adding other tools in the future */
        return match ($toolCall) {
            'sendTelegramMessage' => 'sendMessage',
            'sendTelegramPhoto' => 'sendPhoto',
            'sendTelegramDocument' => 'sendDocument',
            'sendTelegramVideo' => 'sendVideo',
            'sendTelegramAnimation' => 'sendAnimation',
            'sendTelegramVoice' => 'sendVoice',
            'sendTelegramVideoNote' => 'sendVideoNote',
            'sendTelegramSticker' => 'sendSticker',
            'sendTelegramInvoice' => 'sendInvoice',
            'sendTelegramGame' => 'sendGame',
            'sendTelegramPoll' => 'sendPoll',
            'sendTelegramChatAction' => 'sendChatAction',
            'sendTelegramLocation' => 'sendLocation',
            'sendTelegramVenue' => 'sendVenue',
            'sendTelegramContact' => 'sendContact',
            default => throw new Exception("Unsupported Telegram tool name: {$toolCall}"),
        };
    }

    protected static function executeTelegramMethod(Api $telegram, string $methodName, array $parameters): array
    {
        try {
            $response = $telegram->$methodName($parameters);
            if (is_object($response) && method_exists($response, 'toArray')) {
                return $response->toArray();
            } elseif (is_array($response)) {
                return $response;
            }
            return is_scalar($response) ? ['result' => $response] : (array) $response;
        } catch (Exception $e) {
            throw new Exception("Telegram API error for method {$methodName}: {$e->getMessage()}");
        }
    }


    public static function getTools(): array
    {
        $toolsList = [];
        foreach (self::$tools as $tool) {
            if (!File::exists(base_path("tools/{$tool}.json"))) continue;
            $json = File::get(base_path("tools/{$tool}.json"));
            $data = json_decode($json, true);
            $toolsList[] = $data;
        }
        return $toolsList;
    }
}
