<?php

namespace App\Services;

use App\Enums\ApiAuthType;
use App\Models\ToolCall;
use App\Enums\ToolcallStatus;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Exception;

class ApiToolExecutor
{
    /**
     * Execute the API call based on the ToolCall model
     *
     * @param ToolCall $toolCall
     * @return ToolCall The updated tool call with the response
     */
    public function execute(ToolCall $toolCall): ToolCall
    {
        // Start timing the execution
        $startTime = microtime(true);

        try {
            // First, load relationships to get API configuration
            $toolCall->load(['apiTool.api', 'apiTool.headers', 'apiTool.api.headers', 'bot']);
            $apiTool = $toolCall->apiTool;
            $api = $apiTool->api;
            if (!$api->active) {
                throw new Exception("API is not active: {$api->name}");
            }
            // Set up the HTTP request
            $request = $this->configureHttpRequest($api);
            // Apply any API-level headers
            foreach ($api->headers as $header) {
                $request->withHeader($header->header_name, $header->header_value);
            }
            // Apply any tool-specific headers
            foreach ($apiTool->headers as $header) {
                $request->withHeader($header->header_name, $header->header_value);
            }
            // Parse the tool configuration to understand mapping
            $toolConfig = $apiTool->tool_config;
            // Get the input from the tool call
            $input = $toolCall->input;
            // Determine the URL with path parameters replaced
            $url = $this->buildUrl($api->url, $apiTool->path, $toolConfig, $input);
            // Prepare query parameters
            $queryParams = $this->prepareQueryParams($toolConfig, $input);
            // Prepare the request body
            $body = $this->prepareRequestBody($toolConfig, $input);
            // Execute the HTTP request based on the method
            $response = $this->executeRequest($request, $apiTool->method, $url, $queryParams, $body);
            // Calculate execution time
            $executionTime = microtime(true) - $startTime;
            // Check if the response indicates an error based on tool configuration
            $errorInfo = $this->checkForErrors($response, $toolConfig);
            // Create an API log entry
            $this->createApiLog($api->id, $apiTool->id, $response, $executionTime, !$errorInfo['hasError'], $errorInfo['message'], $toolCall->bot->user_id);
            // Update the tool call with the response
            $toolCall->output = $apiTool->transformResponse($response);
            $toolCall->status = $errorInfo['hasError'] ? ToolcallStatus::ERROR : ToolcallStatus::COMPLETED;
            $toolCall->save();
            return $toolCall;
        } catch (Exception $e) {
            // Calculate execution time even for failed requests
            $executionTime = microtime(true) - $startTime;
            // Log the error
            $this->createApiLog($api->id, $apiTool->id, null, $executionTime, false, $e->getMessage(), $toolCall->bot->user_id);
            // Update the tool call to reflect the error
            $toolCall->output = ['error' => $e->getMessage()];
            $toolCall->status = ToolcallStatus::ERROR;
            $toolCall->save();
            return $toolCall;
        }
    }

    /**
     * Configure the HTTP request with authentication
     *
     * @param \App\Models\Api $api
     * @return PendingRequest
     */
    protected function configureHttpRequest($api)
    {
        $request = Http::withHeaders([
            'Content-Type' => $api->content_type ?? 'application/json',
            'Accept' => 'application/json',
        ]);
        return match ($api->auth_type) {
            ApiAuthType::BASIC => $request->withBasicAuth($api->auth_username, $api->auth_password),
            ApiAuthType::BEARER => $request->withToken($api->auth_token),
            ApiAuthType::QUERY_PARAM => $request->withQueryParameters([
                $api->auth_query_key => $api->auth_query_value
            ]),
            ApiAuthType::API_KEY => $request->withHeader($api->auth_query_key, $api->auth_query_value),
            default => $request,
        };
    }

    /**
     * Build the full URL with path parameters replaced
     *
     * @param string $baseUrl
     * @param string $path
     * @param array $toolConfig
     * @param array $input
     * @return string
     */
    protected function buildUrl($baseUrl, $path, $toolConfig, $input)
    {
        $baseUrl = rtrim($baseUrl, '/');
        $path = ltrim($path, '/');
        // Replace path parameters /{username}/
        if (!isset($toolConfig['mapping']['path']) || !is_array($toolConfig['mapping']['path'])) return "{$baseUrl}/{$path}";
        foreach ($toolConfig['mapping']['path'] as $paramName => $inputKey) {
            if (!isset($input[$inputKey])) continue;
            $path = str_replace("{{$paramName}}", $input[$inputKey], $path);
        }
        return "{$baseUrl}/{$path}";
    }

    /**
     * Prepare query parameters based on mapping
     *
     * @param array $toolConfig
     * @param array $input
     * @return array
     */
    protected function prepareQueryParams($toolConfig, $input)
    {
        $queryParams = [];
        if (!isset($toolConfig['mapping']['query']) || !is_array($toolConfig['mapping']['query'])) return $queryParams;
        foreach ($toolConfig['mapping']['query'] as $paramName => $inputKey) {
            if (!isset($input[$inputKey])) continue;
            $queryParams[$paramName] = $input[$inputKey];
        }
        return $queryParams;
    }

    /**
     * Prepare the request body based on mapping
     *
     * @param array $toolConfig
     * @param array $input
     * @return array|null
     */
    protected function prepareRequestBody($toolConfig, $input)
    {
        $body = [];
        if (!isset($toolConfig['mapping']['body']) || !is_array($toolConfig['mapping']['body'])) return $body;
        foreach ($toolConfig['mapping']['body'] as $bodyKey => $inputKey) {
            if (!isset($input[$inputKey])) continue;
            $body[$bodyKey] = $input[$inputKey];
        }
        return $body;
    }

    /**
     * Execute the HTTP request with the appropriate method
     *
     * @param PendingRequest $request
     * @param string $method
     * @param string $url
     * @param array $queryParams
     * @param array|null $body
     * @return \Illuminate\Http\Client\Response
     */
    protected function executeRequest($request, $method, $url, $queryParams, $body)
    {
        $method = strtoupper($method);
        return match ($method) {
            'GET' => $request->get($url, $queryParams),
            'POST' => $request->post($url, $body ?? []),
            'PUT' => $request->put($url, $body ?? []),
            'PATCH' => $request->patch($url, $body ?? []),
            'DELETE' => $request->delete($url, $body ?? []),
            default => throw new Exception("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * Check if the response indicates an error based on tool configuration
     *
     * @param \Illuminate\Http\Client\Response $response
     * @param array $toolConfig
     * @return array ['hasError' => bool, 'message' => string|null]
     */
    protected function checkForErrors($response, $toolConfig)
    {
        $result = ['hasError' => false, 'message' => null];
        // Check HTTP status code first
        if (!$response->successful()) {
            $result['hasError'] = true;
            $result['message'] = "HTTP error: {$response->status()}";
            return $result;
        }
        if (!isset($toolConfig['error'])) return $result;
        $errorConfig = $toolConfig['error'];
        $responseData = $response->json();
        if (!isset($errorConfig['field']) || !isset($errorConfig['value'])) return $result;
        $fieldPath = explode('.', $errorConfig['field']);
        $value = $responseData;
        foreach ($fieldPath as $key) {
            if (!isset($value[$key])) return $result;
            $value = $value[$key];
        }
        if ($value != $errorConfig['value']) return $result;
        $result['hasError'] = true;
        // Try to extract error message if configured
        if (isset($errorConfig['message'])) {
            $messagePath = explode('.', $errorConfig['message']);
            $messageValue = $responseData;
            foreach ($messagePath as $key) {
                if (!isset($messageValue[$key])) return $result;
                $messageValue = $messageValue[$key];
            }
            if (is_string($messageValue)) {
                $result['message'] = $messageValue;
            }
        }
        if (!$result['message']) {
            $result['message'] = "API returned an error indicator";
        }
        return $result;
    }

    /**
     * Create an API log entry
     *
     * @param int $apiId
     * @param int $apiToolId
     * @param \Illuminate\Http\Client\Response|null $response
     * @param float $executionTime
     * @param bool $success
     * @param string|null $errorMessage
     * @return void
     */
    protected function createApiLog($apiId, $apiToolId, $response, $executionTime, $success, $errorMessage = null, $userId = null)
    {
        $apiLog = new \App\Models\ApiLog([
            'api_id' => $apiId,
            'api_tool_id' => $apiToolId,
            'user_id' => $userId,
            'triggered_at' => Carbon::now(),
            'response_code' => $response ? $response->status() : null,
            'response_body' => $response ? ($response->json() ?? $response->body()) : null,
            'execution_time' => $executionTime,
            'success' => $success,
            'error_message' => $errorMessage,
        ]);
        $apiLog->save();
    }
}
