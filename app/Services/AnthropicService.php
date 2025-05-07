<?php

namespace App\Services;

use App\Enums\MessageRole;
use App\Enums\ToolcallStatus;
use App\Models\ApiTool;
use App\Models\Bot;
use App\Models\Chat;
use App\Models\Message;
use Anthropic;
use Anthropic\Responses\Messages\CreateResponse;
use App\Models\Command;
use App\Models\TelegramUpdate;

class AnthropicService
{
    private $client;
    public Bot $bot;
    public Chat $chat;
    public ?Command $command;

    /**
     * Create a new AnthropicService instance
     * 
     * @param TelegramUpdate $telegramUpdate
     */
    public function __construct(public TelegramUpdate $telegramUpdate)
    {
        $this->bot  = $telegramUpdate->bot;
        $this->chat = $telegramUpdate->chat;
        $this->command = $telegramUpdate->command;
        $this->client = Anthropic::factory()
            ->withApiKey($this->bot->api_key)
            ->withHttpClient(new \GuzzleHttp\Client(['timeout' => 60]))
            ->make();
    }



    /**
     * Process a message from a user and return the assistant's response
     *  
     * @return Message
     */
    public function prompt(): Message
    {
        Message::create([
            'bot_id' => $this->bot->id,
            'chat_id' => $this->chat->id,
            'telegram_update_id' => $this->telegramUpdate->id,
            'role' => MessageRole::USER,
            'content' => ['response' => [$this->telegramUpdate->type => $this->telegramUpdate->getMessage()]],
        ]);
        $messageHistory = $this->getMessageHistory();
        $tools = $this->configureTools();
        $response = $this->callAnthropicApi($messageHistory, $tools);
        return $this->processAnthropicResponse($response);
    }

    /**
     * Build the message history for the conversation
     * 
     * @return array
     */
    private function getMessageHistory(): array
    {
        $messages =  $this->chat->messages()
            ->with(['toolCalls', 'telegramLogs'])
            ->orderBy('created_at', 'desc')
            ->limit(10) // Limit to prevent token overflow
            ->get()
            ->reverse();
        $history = [];
        foreach ($messages as  $message) {
            $content = (is_array($message->content) && isset($message->content['response']))
                ? $message->content['response']
                : $message->content;
            $history[] =   ['role' => $message->role->value, 'content' => $content];
            // check if anthropic response triggered tool use
            if ($message->role === MessageRole::USER) continue;
            if ($message->telegramLogs->count() > 0) {
                $toolsContent = [];
                foreach ($message->telegramLogs as $telegramLog) {
                    $toolsContent[] = [
                        'type' => 'tool_result',
                        'tool_use_id' => $telegramLog->tool_call_id,
                        'content' => $telegramLog->output,
                    ];
                }
                $history[] = ['role' => MessageRole::USER->value, 'content' => $toolsContent];
            }
            if ($message->toolCalls->count() === 0) continue;
            $toolsContent = [];
            foreach ($message->toolCalls as $toolCall) {
                $toolsContent[] = [
                    'type' => 'tool_result',
                    'tool_use_id' => $toolCall->tool_call_id,
                    'content' => $toolCall->output,
                ];
            }
            $history[] = ['role' => MessageRole::USER->value, 'content' => $toolsContent];
        }
        return $history;
    }

    /**
     * Configure tools for the API call
     * 
     * @return array
     */
    private function configureTools(): array
    {
        $tools = [];
        $telegramTools = TelegramToolExecutor::getTools();
        foreach ($telegramTools as $telegramTool) {
            $tools[$telegramTool['name']] = [
                'name' => $telegramTool['name'],
                'description' => $telegramTool['description'],
                'input_schema' => $telegramTool['parameters']
            ];
        }
        $commandTools = $this->command?->tools ?? [];
        foreach ($commandTools as $commandTool) {
            $toolConfig = $commandTool->tool_config;
            $tools[$commandTool->slug] = [
                'name' => $commandTool->slug,
                'description' => $commandTool->description,
                'input_schema' => $toolConfig['inputSchema']
            ];
        }
        $apiTools = $this->bot->apiTools;
        foreach ($apiTools as $apiTool) {
            $toolConfig = $apiTool->tool_config;
            $tools[$apiTool->slug] = [
                'name' => $apiTool->slug,
                'description' => $apiTool->description,
                'input_schema' => $toolConfig['inputSchema']
            ];
        }
        return array_values($tools);
    }

    /**
     * Call the Anthropic API
     * 
     * @param array $messages
     * @param array $tools
     * @return CreateResponse
     */
    private function callAnthropicApi(array $messages, array $tools = []): CreateResponse
    {
        $params = [
            'model' => $this->bot->ai_model,
            'max_tokens' => $this->bot->ai_max_tokens ?? 2048,
            'messages' => $messages,
        ];
        if (!empty($tools))
            $params['tools'] = $tools;
        $systemPrompt = $this->command?->system_prompt_override ?? $this->bot->system_prompt;
        if (!empty($systemPrompt))
            $params['system'] = $systemPrompt;
        return $this->client->messages()->create($params);
    }

    /**
     * Process the response from Anthropic API
     * 
     * @param CreateResponse $response
     * @return Message
     */
    private function processAnthropicResponse(CreateResponse $response): Message
    {
        $message = $this->saveAnthropicMessage($response);
        $apiToolExecutor = app()->make(ApiToolExecutor::class);
        $toolUse = false;
        foreach ($response->content as $contentPart) {
            if ($contentPart->type !== 'tool_use') continue;
            // sends final message to telegram, No need to process tools
            if (TelegramToolExecutor::methodIsTelegram($contentPart->name)) {
                $toolCallId = $contentPart->id;
                TelegramToolExecutor::execute($message, $contentPart->name, $contentPart->input, $toolCallId);
            } else {
                $apiTool = ApiTool::where('slug', $contentPart->name)->firstOrFail();
                $toolCall = $apiTool->toolCalls()->create([
                    'bot_id' => $this->bot->id,
                    'chat_id' => $this->chat->id,
                    'message_id' =>  $message->id,
                    'tool_call_id' => $contentPart->id,
                    'name' => $contentPart->name,
                    'input' => $contentPart->input,
                    'status' => ToolcallStatus::PENDING,
                ]);
                $toolUse = true;
                $apiToolExecutor->execute($toolCall);
            }
        }
        // If there was a tool use, we need to handle the response differently
        if (!$toolUse) return $message;
        return $this->processTools();
    }




    /**
     * Process the tool response and make another API call
     * 
     * @return Message
     */
    private function processTools(): Message
    {
        $messageHistory = $this->getMessageHistory();
        $tools = $this->configureTools();
        $response = $this->callAnthropicApi($messageHistory, $tools);
        return $this->processAnthropicResponse($response);
    }

    /**
     * Save a message from anthropic to the database
     * 
     * @return Message
     */
    private function saveAnthropicMessage(CreateResponse $response): Message
    {
        return Message::create([
            'bot_id' => $this->bot->id,
            'chat_id' => $this->chat->id,
            'telegram_update_id' => $this->telegramUpdate->id,
            'ai_message_id' => $response->id,
            'role' => $response->role,
            'content' => $response->toArray()['content'],
            'stop_reason' => $response->stop_reason,
            'input_tokens' => $response->usage->inputTokens,
            'output_tokens' => $response->usage->outputTokens,
        ]);
    }
}
