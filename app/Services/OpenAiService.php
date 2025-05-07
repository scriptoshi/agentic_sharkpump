<?php

namespace App\Services;

use App\Enums\MessageRole;
use App\Enums\ToolcallStatus;
use App\Models\ApiTool;
use App\Models\Bot;
use App\Models\Chat;
use App\Models\Message;
use OpenAI;
use App\Models\Command;
use OpenAI\Responses\Responses\CreateResponse;
use App\Models\TelegramUpdate;

class OpenAiService
{
    private OpenAI\Client $client;
    public Bot $bot;
    public Chat $chat;
    public ?Command $command;

    /**
     * Create a new OpenAiService instance
     * 
     * @param TelegramUpdate $telegramUpdate
     */
    public function __construct(public TelegramUpdate $telegramUpdate)
    {
        $this->bot  = $telegramUpdate->bot;
        $this->chat = $telegramUpdate->chat;
        $this->command = $telegramUpdate->command;
        $this->client = OpenAI::client($this->bot->api_key);
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
            'content' => [
                'type' => 'input_text',
                'text' => $this->telegramUpdate->getMessage(),
            ],
        ]);
        $messageHistory = $this->getMessageHistory();
        $tools = $this->configureTools();
        $response = $this->callOpenAiApi($messageHistory, $tools);
        return $this->processOpenAiResponse($response);
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
        // system prompt
        $systemPrompt = $this->command?->system_prompt_override ?? $this->bot->system_prompt;
        if (!empty($systemPrompt))
            $history[] = ['role' => MessageRole::SYSTEM->value, 'content' => [
                'type' => 'input_text',
                'text' => $systemPrompt
            ]];
        foreach ($messages as  $message) {
            // skip null messages for only tool use.
            if (!is_null($message->content))
                $history[] =   ['role' => $message->role->value, 'content' => $message->content];
            // check if OpenAi response triggered tool use
            if ($message->toolCalls->count() === 0) continue;
            if ($message->telegramLogs->count() > 0) {
                foreach ($message->telegramLogs as  $telegramLog) {
                    $history[] = [
                        "type" => "function_call",
                        "id" => $telegramLog->tool_id,
                        "call_id" => $telegramLog->tool_call_id,
                        "name" => $telegramLog->name,
                        "arguments" => json_encode($telegramLog->input)
                    ];
                    $history[] = [
                        "type" => "function_call_output",
                        "call_id" => $telegramLog->tool_call_id,
                        "output" => json_encode($telegramLog->output)
                    ];
                }
            }
            foreach ($message->toolCalls as $toolCall) {
                $history[] = [
                    "type" => "function_call",
                    "id" => $toolCall->tool_id,
                    "call_id" => $toolCall->tool_call_id,
                    "name" => $toolCall->name,
                    "arguments" => json_encode($toolCall->input)
                ];
                $history[] = [
                    "type" => "function_call_output",
                    "call_id" => $toolCall->tool_call_id,
                    "output" => json_encode($toolCall->output)
                ];
            }
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
                'type' => 'function',
                'name' => $telegramTool['name'],
                'description' => $telegramTool['description'],
                'parameters' => $telegramTool['parameters'],
                'strict' => true
            ];
        }
        $commandTools = $this->command?->tools ?? [];
        foreach ($commandTools as $commandTool) {
            $toolConfig = $commandTool->tool_config;
            $tools[$commandTool->slug] = [
                'type' => 'function',
                'name' => $commandTool->slug,
                'description' => $commandTool->description,
                'parameters' => $toolConfig['inputSchema'],
                'strict' => $commandTool->strict
            ];
        }
        $apiTools = $this->bot->apiTools;
        foreach ($apiTools as $apiTool) {
            $toolConfig = $apiTool->tool_config;
            $tools[$apiTool->slug] = [
                'type' => 'function',
                'name' => $apiTool->slug,
                'description' => $apiTool->description,
                'parameters' => $toolConfig['inputSchema'],
                'strict' => $apiTool->strict
            ];
        }
        return array_values($tools);
    }

    /**
     * Call the OpenAi API
     * 
     * @param array $messages
     * @param array $tools
     * @return CreateResponse
     */
    private function callOpenAiApi(array $messages, array $tools = []): CreateResponse
    {
        $params = [
            'model' => $this->bot->ai_model,
            'temperature' => $this->bot->ai_temperature ?? 1,
            'store' => $this->bot->ai_store ?? true,
            'max_output_tokens' => $this->bot->ai_max_tokens ?? 2048,
            'input' => $messages,
        ];
        if (!empty($tools))
            $params['tools'] = $tools;
        $systemPrompt = $this->command?->system_prompt_override ?? $this->bot->system_prompt;
        if (!empty($systemPrompt))
            $params['system'] = $systemPrompt;
        return $this->client->responses()->create($params);
    }

    /**
     * Process the response from OpenAi API
     * 
     * @param CreateResponse $response
     * @return Message
     */
    private function processOpenAiResponse(CreateResponse $response): ?Message
    {
        // concat all text messages for now.
        $message = $this->saveOpenAiMessage($response);
        $apiToolExecutor = app()->make(ApiToolExecutor::class);
        $toolUse = false;
        foreach ($response->output as $output) {
            if ($output->type !== 'tool_use') continue;
            $arguments = json_decode($output->arguments, true);
            // if tool is a telegram tool, execute it and continue
            if (TelegramToolExecutor::methodIsTelegram($output->name)) {
                $toolCallId =  $output->callId;
                $toolId = $output->id;
                TelegramToolExecutor::execute($message, $output->name, $arguments, $toolCallId, $toolId);
                continue;
            }
            $toolUse = true;
            $apiTool = ApiTool::where('slug', $output->name)->firstOrFail();
            $toolCall = $apiTool->toolCalls()->create([
                'bot_id' => $this->bot->id,
                'chat_id' => $this->chat->id,
                'message_id' =>  $message->id,
                'tool_call_id' => $output->callId,
                'tool_id' => $output->id,
                'name' => $output->name,
                'input' => $arguments,
                'status' => ToolcallStatus::PENDING,
            ]);
            $tools[] =  $apiToolExecutor->execute($toolCall);
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
        $response = $this->callOpenAiApi($messageHistory, $tools);
        return $this->processOpenAiResponse($response);
    }

    /**
     * Save a message from OpenAi to the database
     * 
     * @return Message
     */
    private function saveOpenAiMessage(CreateResponse $response): Message
    {
        $message = new Message;
        $message->bot_id = $this->bot->id;
        $message->chat_id = $this->chat->id;
        $message->telegram_update_id =  $this->telegramUpdate->id;
        $message->ai_message_id = $response->id;
        $message->role = MessageRole::ASSISTANT;
        $message->content = null;
        $message->stop_reason = null;
        $message->input_tokens = $response->usage->inputTokens;
        $message->output_tokens = $response->usage->outputTokens;
        $message->save();
        $output_text = "";
        foreach ($response->output as $output) {
            if ($output->type === 'message') {
                foreach ($output->content as $content) {
                    if ($content->type === 'text') {
                        $output_text .= $content->text;
                    }
                }
            }
        }
        if (empty($output_text)) return $message;
        $message->content = $output_text;
        $message->stop_reason = $output->status;
        $message->save();
        return $message;
    }
}
