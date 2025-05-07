<?php

namespace App\Services;

use App\Enums\MessageRole;
use App\Enums\ToolcallStatus;
use App\Models\ApiTool;
use App\Models\Bot;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Command;
use App\Models\TelegramUpdate;
use App\Services\ApiToolExecutor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Response as HttpResponse; // Alias for Laravel HTTP Response
use Illuminate\Support\Facades\Log; // For logging API errors

class GeminiService
{
    public Bot $bot;
    private string $apiKey;
    private string $apiBaseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private int $timeout;
    public Chat $chat;
    public ?Command $command;


    /**
     * Create a new GeminiService instance
     *
     * @param TelegramUpdate $telegramUpdate
     */
    public function __construct(public TelegramUpdate $telegramUpdate)
    {
        $this->bot = $telegramUpdate->bot;
        $this->chat = $telegramUpdate->chat;
        $this->command = $telegramUpdate->command;
        $this->apiKey = $this->bot->api_key;
        $this->timeout = $this->bot->ai_request_timeout ?? 60; // Default timeout 60 seconds
    }

    /**
     * Process a message from a user and return the MODEL's response
    
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
        $systemPrompt = $this->command?->system_prompt_override ?? $this->bot->system_prompt;

        $response = $this->callGeminiApi($messageHistory, $tools, $systemPrompt);
        return $this->processGeminiResponse($response);
    }

    /**
     * Build the message history for the Gemini API
     * (This method remains largely the same, focusing on the structure Gemini expects)
     *
     * @return array
     */
    private function getMessageHistory(): array
    {
        $dbMessages = $this->chat->messages()
            ->with('toolCalls')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->reverse();
        $history = [];
        foreach ($dbMessages as $message) {
            if ($message->role !== MessageRole::MODEL || $message->toolCalls->count() == 0) {
                $history[] = $message->content;
                continue;
            }
            $functionResponseParts = [];
            $toolCalls = [];
            foreach ($message->toolCalls as $toolCall) {
                $functionResponseParts[] =  [
                    "functionResponse" => [
                        'name' => $toolCall->name,
                        'response' => $toolCall->output,
                    ]
                ];
                $toolCalls[] = [
                    'functionCall' => [
                        'name' => $toolCall->name,
                        'args' => $toolCall->input
                    ]
                ];
            }
            $parts = [
                ...($message->content['parts'] ?? []), // incase Gemini responded.
                ...$toolCalls
            ];
            //add the tool calls
            $history[] = ['role' => MessageRole::MODEL->value, 'parts' => $parts];
            // add the the tool responses
            $history[] = ['role' => MessageRole::USER->value, 'parts' => $functionResponseParts];
        }
        return $history;
    }


    /**
     * Configure tools for the Gemini API call
     * (This method remains largely the same, focusing on the structure Gemini expects)
     *
     * @return array
     */
    private function configureTools(): array
    {
        $rawTools = [];
        $telegramTools = TelegramToolExecutor::getTools();
        foreach ($telegramTools as $telegramTool) {
            $rawTools[$telegramTool['name']] = $telegramTool;
        }
        $commandTools = $this->command?->tools ?? [];
        foreach ($commandTools as $commandTool) {
            $toolConfig = $commandTool->tool_config;
            $rawTools[$commandTool->slug] = [
                'name' => $commandTool->slug,
                'description' => $commandTool->description,
                'parameters' => $toolConfig['inputSchema'] ?? ['type' => 'object', 'properties' => new \stdClass()]
            ];
        }

        $apiTools = $this->bot->apiTools;
        foreach ($apiTools as $apiTool) {
            $toolConfig = $apiTool->tool_config;
            $rawTools[$apiTool->slug] = [
                'name' => $apiTool->slug,
                'description' => $apiTool->description,
                'parameters' => $toolConfig['inputSchema'] ?? ['type' => 'object', 'properties' => new \stdClass()]
            ];
        }

        if (empty($rawTools)) {
            return [];
        }

        $functionDeclarations = [];
        foreach (array_values($rawTools) as $tool) {
            if (is_array($tool['parameters']) && empty($tool['parameters'])) {
                $tool['parameters'] = (object)['type' => 'object', 'properties' => new \stdClass()];
            }
            // Ensure parameters is an object if it's an empty array after json_decode
            if (is_array($tool['parameters']) && empty($tool['parameters'])) {
                $tool['parameters'] = new \stdClass();
            } else if (is_array($tool['parameters'])) {
                // Ensure properties is an object if it's an empty array
                if (isset($tool['parameters']['properties']) && is_array($tool['parameters']['properties']) && empty($tool['parameters']['properties'])) {
                    $tool['parameters']['properties'] = new \stdClass();
                }
                // Ensure required is an array
                if (isset($tool['parameters']['required']) && !is_array($tool['parameters']['required'])) {
                    $tool['parameters']['required'] = [];
                }
            }
            $functionDeclarations[] = $tool;
        }

        if (!empty($functionDeclarations)) {
            return [['functionDeclarations' => $functionDeclarations]]; // Gemini expects an array of Tool objects
        }
        return [];
    }

    /**
     * Call the Gemini API using Laravel Http facade
     *
     * @param array $messages
     * @param array $tools
     * @param string|null $systemPrompt
     * @return HttpResponse
     */
    private function callGeminiApi(array $messages, array $tools = [], ?string $systemPrompt = null): HttpResponse
    {
        $endpoint = $this->apiBaseUrl . $this->bot->ai_model . ':generateContent?key=' . $this->apiKey;

        $payload = [
            'contents' => $messages,
            'generationConfig' => [
                'maxOutputTokens' => (int)($this->bot->ai_max_tokens ?? 4096),
                'temperature' => (float)($this->bot->ai_temperature ?? 0.7),
                'topP' => (float)($this->bot->ai_top_p ?? 1),
            ],
        ];

        if (!empty($tools)) {
            $payload['tools'] = $tools;
        }

        if (!empty($systemPrompt)) {
            $payload['systemInstruction'] = ['role' => MessageRole::SYSTEM, 'parts' => [['text' => $systemPrompt]]];
        }

        Log::debug("Gemini API Request Payload to {$endpoint}:", $payload);

        $response = Http::timeout($this->timeout)
            ->retry(2, 1000) // Retry 2 times, waiting 1 second between retries
            ->post($endpoint, $payload);

        Log::debug("Gemini API Response Status: " . $response->status());
        Log::debug("Gemini API Response Body: ", $response->json() ?: [$response->body()]);

        if (!$response->successful()) {
            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'response' => $response->body(),
                'request_payload' => $payload
            ]);
            // Consider throwing a custom exception here
            // For now, returning the response to be handled by the caller
        }

        return $response;
    }

    /**
     * Process the response from Gemini API
     *
     * @param HttpResponse $httpResponse
     * @return Message
     */
    private function processGeminiResponse(HttpResponse $httpResponse): Message
    {
        if (!$httpResponse->successful()) return $this->errorMessage($httpResponse);
        $responseBody = $httpResponse->json();
        $message = $this->saveGeminiMessage($responseBody); // Pass parsed JSON
        $candidates = $responseBody['candidates'] ?? [];
        if (empty($candidates)) {
            $message->content = [
                "role" => MessageRole::USER->value,
                "parts" => [["text" => "Gemini API response had no candidates"]]
            ];
            $message->stop_reason = 'no_candidates';
            $message->save();
            return $message;
        }
        // Typically, we use the first candidate
        $contentParts =  $candidates[0]['content']['parts'] ?? [];
        $apiToolExecutor = app()->make(ApiToolExecutor::class);
        $toolUseDetected = false;
        foreach ($contentParts as $part) {
            if (!isset($part['functionCall'])) continue;
            // if tool is a telegram tool, execute it and continue
            if (TelegramToolExecutor::methodIsTelegram($part['functionCall']['name'])) {
                $toolCallId = Str::uuid()->toString();
                TelegramToolExecutor::execute($message, $part['functionCall']['name'], $part['functionCall']['args'], $toolCallId);
                continue;
            }
            $toolUseDetected = true;
            $functionCall = $part['functionCall'];
            $toolName = $functionCall['name'];
            $toolInput = $functionCall['args'] ?? []; // args should be an object/associative array
            $apiTool = ApiTool::where('slug', $toolName)->first();
            if (!$apiTool) continue;
            $toolCall = $apiTool->toolCalls()->create([
                'bot_id' => $this->bot->id,
                'chat_id' => $this->chat->id,
                'message_id' => $message->id,
                'tool_call_id' => Str::uuid()->toString(), // Internal ID
                'name' => $toolName,
                'input' => $toolInput, // Store as is, ApiToolExecutor will handle
                'status' => ToolcallStatus::PENDING,
            ]);
            $apiToolExecutor->execute($toolCall); // This should update $toolCall->output and status
        }
        if (!$toolUseDetected)  return $message;
        return $this->processTools();
    }

    /**
     * Error Message
     *
     * @return Message
     */
    private function errorMessage(HttpResponse $httpResponse): Message
    {
        return Message::create([
            'bot_id' => $this->bot->id,
            'chat_id' => $this->chat->id,
            'telegram_update_id' => $this->telegramUpdate->id,
            'role' => MessageRole::USER,
            'content' => [
                'role' => MessageRole::USER->value,
                'parts' => [['text' => "Error communicating with Gemini API: " . $httpResponse->status() . " - " . ($httpResponse->json('error.message') ?? $httpResponse->body())]]
            ],
            'stop_reason' => 'error',
            'input_tokens' => 0, // Unable to get usage on error reliably
            'output_tokens' => 0,
        ]);
    }

    /**
     * Process the tool responses and make another API call to Gemini
     *
     * @return Message
     */
    private function processTools(): Message
    {
        $messageHistory = $this->getMessageHistory(); // Should now include functionResponse parts
        $tools = $this->configureTools();
        $systemPrompt = $this->command?->system_prompt_override ?? $this->bot->system_prompt;

        $response = $this->callGeminiApi($messageHistory, $tools, $systemPrompt);
        return $this->processGeminiResponse($response);
    }

    /**
     * Save a message from Gemini to the database
     *
     * @param array $responseBody The parsed JSON response body from Gemini API
     * @return Message
     */
    private function saveGeminiMessage(array $responseBody): Message
    {
        $responseText = "";
        $stopReason = null;
        $promptTokenCount = 0;
        $candidatesTokenCount = 0;
        $responseId = 'gemini-' . Str::uuid(); // Generate an ID as Gemini REST might not provide one directly for the message

        if (isset($responseBody['candidates'][0])) {
            $candidate = $responseBody['candidates'][0];
            if (isset($candidate['content']['parts'])) {
                foreach ($candidate['content']['parts'] as $part) {
                    if (isset($part['text'])) {
                        $responseText .= $part['text'];
                    }
                }
            }
            $stopReason = $candidate['finishReason'] ?? null;
        } else {
            if (isset($responseBody['error']['message'])) {
                $responseText = "No candidates found in Gemini response for saving message: " . $responseBody['error']['message'];
                $stopReason = 'error';
            }
        }

        // Usage
        if (isset($responseBody['usageMetadata'])) {
            $promptTokenCount = $responseBody['usageMetadata']['promptTokenCount'] ?? 0;
            $candidatesTokenCount = $responseBody['usageMetadata']['candidatesTokenCount'] ?? 0;
            if ($candidatesTokenCount == 0 && isset($responseBody['usageMetadata']['totalTokenCount'])) {
                $candidatesTokenCount = $responseBody['usageMetadata']['totalTokenCount'] - $promptTokenCount;
            }
        }

        // If responseText is empty but there were function calls, content should be null initially
        $hasFunctionCall = false;
        if (isset($responseBody['candidates'][0]['content']['parts'])) {
            foreach ($responseBody['candidates'][0]['content']['parts'] as $part) {
                if (!isset($part['functionCall'])) continue;
                $hasFunctionCall = true;
                break;
            }
        }

        return Message::create([
            'bot_id' => $this->bot->id,
            'chat_id' => $this->chat->id,
            'telegram_update_id' => $this->telegramUpdate->id,
            'ai_message_id' => $responseId,
            'role' => MessageRole::MODEL,
            'content' => ($hasFunctionCall && empty(trim($responseText)))
                ? null
                : ['role' => MessageRole::MODEL->value, 'parts' => [['text' => $responseText]]],
            'stop_reason' => $stopReason,
            'input_tokens' => $promptTokenCount,
            'output_tokens' => $candidatesTokenCount,
        ]);
    }
}
