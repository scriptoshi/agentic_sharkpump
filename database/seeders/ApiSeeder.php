<?php

namespace Database\Seeders;

use App\Models\Api;
use App\Models\ApiTool;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user or create one if it doesn't exist
        $admin = User::where('email', 'admin@example.com')->first();

        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);
        }

        // Get a regular user or create one if it doesn't exist
        $user = User::where('email', 'user@example.com')->first();

        if (!$user) {
            $user = User::create([
                'name' => 'Regular User',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'is_admin' => false,
                'email_verified_at' => now(),
            ]);
        }

        // Sample APIs for the admin
        $this->createWeatherApi($admin);
        $this->createOpenAIApi($admin);
        $this->createNewsApi($admin);

        // Sample API for the regular user
        $this->createGithubApi($user);
    }

    /**
     * Create a sample Weather API
     */
    private function createWeatherApi(User $user): void
    {
        $api = Api::create([
            'user_id' => $user->id,
            'name' => 'Weather API',
            'url' => 'https://api.weatherapi.com/v1',
            'content_type' => 'application/json',
            'auth_type' => 'query_param',
            'auth_query_key' => 'key',
            'auth_query_value' => 'abc123weatherapikey',
            'active' => true,
            'is_public' => true,
            'description' => 'Weather API for getting current weather and forecasts',
        ]);

        // Add headers
        $api->headers()->createMany([
            [
                'header_name' => 'Accept',
                'header_value' => 'application/json',
            ],
            [
                'header_name' => 'User-Agent',
                'header_value' => 'AiBotsForTelegram/1.0',
            ],
        ]);

        // Add tools
        $currentWeatherTool = $api->tools()->create([
            'user_id' => $user->id,
            'name' => 'Current Weather',
            'description' => 'Get current weather for a location',
            'shouldQueue' => false,
            'version' => '1.0.0',
            'method' => 'GET',
            'path' => '/current.json',
            'query_params' => 'q={location}',
            'tool_config' => [
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'location' => [
                            'type' => 'string',
                            'description' => 'Location name, city, ZIP code, or coordinates',
                            'required' => true
                        ]
                    ],
                    'required' => [
                        'location'
                    ]
                ],
                'inputValidation' => [
                    'location' => 'required|string'
                ],
                'mapping' => [
                    'path' => [],
                    'query' => [
                        'q' => 'location'
                    ],
                    'body' => [],
                    'response' => [
                        'temperature' => 'current.temp_c',
                        'condition' => 'current.condition.text',
                        'wind_speed' => 'current.wind_kph',
                        'humidity' => 'current.humidity',
                        'location' => 'location.name'
                    ]
                ]
            ],
        ]);

        $forecastTool = $api->tools()->create([
            'user_id' => $user->id,
            'name' => 'Weather Forecast',
            'description' => 'Get weather forecast for a location',
            'shouldQueue' => false,
            'version' => '1.0.0',
            'method' => 'GET',
            'path' => '/forecast.json',
            'query_params' => 'q={location}&days={days}',
            'tool_config' => [
                "inputSchema" => [
                    "type" => "object",
                    "properties" => [
                        "location" => [
                            "type" => "string",
                            "description" => "Location name, city, ZIP code, or coordinates"
                        ],
                        "days" => [
                            "type" => "integer",
                            "description" => "Number of days of forecast (1-10)"
                        ]
                    ],
                    "required" => [
                        "location",
                        "days"
                    ]
                ],
                "inputValidation" => [
                    "location" => "required|string|regex:/^[A-Za-z0-9-]+$/|max:39",
                    "days" => "required|string|regex:/^[A-Za-z0-9._-]+$/|max:100",
                ],
                "mapping" => [
                    "path" => [],
                    "query" => [
                        "location" => "q",
                        "days" => "days"
                    ],
                    "response" => [
                        'forecast' => 'forecast.forecastday',
                        'location' => 'location.name',
                    ],
                    "body" => []
                ]
            ],
        ]);

        // Create logs for the tools
        $this->createApiLogs($api, $currentWeatherTool, $forecastTool);
    }

    /**
     * Create a sample OpenAI API
     */
    private function createOpenAIApi(User $user): void
    {
        $api = Api::create([
            'user_id' => $user->id,
            'name' => 'OpenAI API',
            'url' => 'https://api.openai.com/v1',
            'content_type' => 'application/json',
            'auth_type' => 'bearer',
            'auth_token' => 'sk-sample1234567890abcdefghijklmnopqrstuvwxyz',
            'active' => true,
            'is_public' => true,
            'description' => 'OpenAI API for AI completions and embeddings',
        ]);

        // Add headers
        $api->headers()->createMany([
            [
                'header_name' => 'Content-Type',
                'header_value' => 'application/json',
            ],
        ]);

        // Add tools
        $completionsTool = $api->tools()->create([
            'user_id' => $user->id,
            'name' => 'Text Completions',
            'description' => 'Generate text completions with GPT models',
            'shouldQueue' => true,
            'version' => '1.0.0',
            'method' => 'POST',
            'path' => '/chat/completions',
            "tool_config" => [
                "inputSchema" => [
                    "type" => "object",
                    "properties" => [
                        "prompt" => [
                            "type" => "string",
                            "description" => "The prompt to generate completions for",
                            "required" => true
                        ],
                        "model" => [
                            "type" => "string",
                            "description" => "The model to use for completion",
                            "required" => false,
                            "default" => "gpt-3.5-turbo"
                        ],
                        "max_tokens" => [
                            "type" => "integer",
                            "description" => "Maximum number of tokens to generate",
                            "required" => false,
                            "default" => 150
                        ]
                    ],
                    "required" => [
                        "prompt"
                    ]
                ],
                "inputValidation" => [
                    "prompt" => "required|string",
                    "model" => "nullable|string",
                    "max_tokens" => "nullable|integer"
                ],
                "mapping" => [
                    "path" => [],
                    "query" => [],
                    "body" => [
                        "prompt" => "prompt",
                        "model" => "model",
                        "max_tokens" => "max_tokens"
                    ],
                    "response" => [
                        "text" => "choices[0].message.content",
                        "model" => "model"
                    ]
                ]
            ],
        ]);

        $embeddingsTool = $api->tools()->create([
            'user_id' => $user->id,
            'name' => 'Text Embeddings',
            'description' => 'Generate text embeddings with OpenAI models',
            'shouldQueue' => true,
            'version' => '1.0.0',
            'method' => 'POST',
            'path' => '/embeddings',
            'tool_config' => [
                "inputSchema" => [
                    "type" => "object",
                    "properties" => [
                        "text" => [
                            "type" => "string",
                            "description" => "The text to generate embeddings for",
                            "required" => true
                        ],
                        "model" => [
                            "type" => "string",
                            "description" => "The model to use for embeddings",
                            "required" => false,
                            "default" => "text-embedding-ada-002"
                        ],
                    ],
                    "required" => [
                        "text"
                    ]
                ],
                "inputValidation" => [
                    "text" => "required|string",
                    "model" => "nullable|string"
                ],
                "mapping" => [
                    "path" => [],
                    "query" => [],
                    "body" => [
                        "text" => "text",
                        "model" => "model"
                    ],
                    "response" => [
                        "embeddings" => "data[0].embedding",
                        "model" => "model"
                    ]
                ]
            ]
        ]);

        // Create logs for the tools
        $this->createApiLogs($api, $completionsTool, $embeddingsTool);
    }

    /**
     * Create a sample News API
     */
    private function createNewsApi(User $user): void
    {
        $api = Api::create([
            'user_id' => $user->id,
            'name' => 'News API',
            'url' => 'https://newsapi.org/v2',
            'content_type' => 'application/json',
            'auth_type' => 'query_param',
            'auth_query_key' => 'apiKey',
            'auth_query_value' => 'newsapi123456789abcdefg',
            'active' => true,
            'is_public' => true,
            'description' => 'News API for headlines and articles from various sources',
        ]);

        // Add headers
        $api->headers()->createMany([
            [
                'header_name' => 'Accept',
                'header_value' => 'application/json',
            ],
        ]);

        // Add tools
        $headlinesTool = $api->tools()->create([
            'user_id' => $user->id,
            'name' => 'Top Headlines',
            'description' => 'Get top headlines by country, category, or search query',
            'shouldQueue' => false,
            'version' => '1.0.0',
            'method' => 'GET',
            'path' => '/top-headlines',
            'query_params' => 'country={country}&category={category}&q={query}',
            'tool_config' => [
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'country' => [
                            'type' => 'string',
                            'description' => 'The 2-letter ISO 3166-1 code of the country',
                            'required' => false,
                            'default' => 'us'
                        ],
                        'category' => [
                            'type' => 'string',
                            'description' => 'The category of news (business, entertainment, health, science, sports, technology)',
                            'required' => false
                        ],
                        'query' => [
                            'type' => 'string',
                            'description' => 'Keywords or phrases to search for in the article title and body',
                            'required' => false
                        ]
                    ],
                    'required' => []
                ],
                'inputValidation' => [
                    'country' => 'nullable|string',
                    'category' => 'nullable|string',
                    'query' => 'nullable|string'
                ],
                'mapping' => [
                    'path' => [],
                    'query' => [
                        'country' => 'country',
                        'category' => 'category',
                        'q' => 'query'
                    ],
                    'body' => [],
                    'response' => [
                        'articles' => 'articles',
                        'totalResults' => 'totalResults'
                    ]
                ]
            ]
        ]);

        $everythingTool = $api->tools()->create([
            'user_id' => $user->id,
            'name' => 'Everything',
            'description' => 'Search all articles across sources and domains',
            'shouldQueue' => false,
            'version' => '1.0.0',
            'method' => 'GET',
            'path' => '/everything',
            'query_params' => 'q={query}&from={from}&to={to}&language={language}&sortBy={sortBy}',
            'tool_config' => [
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Keywords or phrases to search for in the article title and body',
                            'required' => true
                        ],
                        'from' => [
                            'type' => 'string',
                            'description' => 'A date in YYYY-MM-DD format to search from',
                            'required' => false
                        ],
                        'to' => [
                            'type' => 'string',
                            'description' => 'A date in YYYY-MM-DD format to search to',
                            'required' => false
                        ],
                        'language' => [
                            'type' => 'string',
                            'description' => 'The 2-letter ISO-639-1 code of the language',
                            'required' => false,
                            'default' => 'en'
                        ],
                        'sortBy' => [
                            'type' => 'string',
                            'description' => 'The order to sort the articles in (relevancy, popularity, publishedAt)',
                            'required' => false,
                            'default' => 'publishedAt'
                        ]
                    ],
                    'required' => [
                        'query'
                    ]
                ],
                'inputValidation' => [
                    'query' => 'required|string',
                    'from' => 'nullable|string|date',
                    'to' => 'nullable|string|date',
                    'language' => 'nullable|string',
                    'sortBy' => 'nullable|string'
                ],
                'mapping' => [
                    'path' => [],
                    'query' => [
                        'q' => 'query',
                        'from' => 'from',
                        'to' => 'to',
                        'language' => 'language',
                        'sortBy' => 'sortBy'
                    ],
                    'body' => [],
                    'response' => [
                        'articles' => 'articles',
                        'totalResults' => 'totalResults'
                    ]
                ]
            ]
        ]);

        // Create logs for the tools
        $this->createApiLogs($api, $headlinesTool, $everythingTool);
    }

    /**
     * Create a sample GitHub API
     */
    private function createGithubApi(User $user): void
    {
        $api = Api::create([
            'user_id' => $user->id,
            'name' => 'GitHub API',
            'url' => 'https://api.github.com',
            'content_type' => 'application/json',
            'auth_type' => 'bearer',
            'auth_token' => 'github_pat_fake12345abcdefghijklmnopqrstuvwxyz',
            'active' => true,
            'is_public' => true,
            'description' => 'GitHub API for accessing repository information',
        ]);

        // Add headers
        $api->headers()->createMany([
            [
                'header_name' => 'Accept',
                'header_value' => 'application/vnd.github.v3+json',
            ],
            [
                'header_name' => 'User-Agent',
                'header_value' => 'AiBotsForTelegram/1.0',
            ],
        ]);

        // Add tools
        $reposTool = $api->tools()->create([
            'user_id' => $user->id,
            'name' => 'List Repositories',
            'description' => 'List public repositories for a user',
            'shouldQueue' => false,
            'version' => '1.0.0',
            'method' => 'GET',
            'path' => '/users/{username}/repos',
            'tool_config' => [
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'username' => [
                            'type' => 'string',
                            'description' => 'The GitHub username',
                            'required' => true
                        ]
                    ],
                    'required' => [
                        'username'
                    ]
                ],
                'inputValidation' => [
                    'username' => 'required|string'
                ],
                'mapping' => [
                    'path' => [
                        'username' => 'username'
                    ],
                    'query' => [],
                    'body' => [],
                    'response' => [
                        'repositories' => '[*]'
                    ]
                ]
            ]
        ]);

        $repoDetailsTool = $api->tools()->create([
            'user_id' => $user->id,
            'name' => 'Repository Details',
            'description' => 'Get details about a specific repository',
            'shouldQueue' => false,
            'version' => '1.0.0',
            'method' => 'GET',
            'path' => '/repos/{owner}/{repo}',
            'tool_config' => [
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'owner' => [
                            'type' => 'string',
                            'description' => 'The repository owner',
                            'required' => true
                        ],
                        'repo' => [
                            'type' => 'string',
                            'description' => 'The repository name',
                            'required' => true
                        ]
                    ],
                    'required' => [
                        'owner',
                        'repo'
                    ]
                ],
                'inputValidation' => [
                    'owner' => 'required|string',
                    'repo' => 'required|string'
                ],
                'mapping' => [
                    'path' => [
                        'owner' => 'owner',
                        'repo' => 'repo'
                    ],
                    'query' => [],
                    'body' => [],
                    'response' => [
                        'name' => 'name',
                        'description' => 'description',
                        'stars' => 'stargazers_count',
                        'forks' => 'forks_count',
                        'issues' => 'open_issues_count',
                        'language' => 'language',
                        'owner' => 'owner.login'
                    ]
                ]
            ]
        ]);

        // Create logs for the tools
        $this->createApiLogs($api, $reposTool, $repoDetailsTool);
    }

    /**
     * Create random API logs for tools
     */
    private function createApiLogs(Api $api, ApiTool $tool1, ApiTool $tool2): void
    {
        // Successful logs
        for ($i = 0; $i < 5; $i++) {
            $tool1->logs()->create([
                'user_id' => $tool1->user_id,
                'api_id' => $api->id,
                'triggered_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 24)),
                'response_code' => 200,
                'response_body' => json_encode(['status' => 'success', 'data' => ['sample' => 'response data']]),
                'execution_time' => rand(100, 2000) / 1000, // 0.1 to 2 seconds
                'success' => true,
            ]);
            $tool2->logs()->create([
                'api_id' => $api->id,
                'user_id' => $tool2->user_id,
                'triggered_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 24)),
                'response_code' => 200,
                'response_body' => json_encode(['status' => 'success', 'data' => ['sample' => 'response data']]),
                'execution_time' => rand(100, 2000) / 1000, // 0.1 to 2 seconds
                'success' => true,
            ]);
        }

        // Failed logs with different errors
        $errorCodes = [400, 401, 403, 404, 429, 500, 503];
        $errorMessages = [
            'Invalid request parameters',
            'Authentication failed',
            'Access denied',
            'Resource not found',
            'Rate limit exceeded',
            'Internal server error',
            'Service unavailable',
        ];

        for ($i = 0; $i < 3; $i++) {
            $errorIndex = rand(0, count($errorCodes) - 1);
            $tool1->logs()->create([
                'api_id' => $api->id,
                'user_id' => $tool1->user_id,
                'triggered_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 24)),
                'response_code' => $errorCodes[$errorIndex],
                'response_body' => json_encode(['error' => $errorMessages[$errorIndex]]),
                'execution_time' => rand(50, 1000) / 1000, // 0.05 to 1 second
                'success' => false,
                'error_message' => $errorMessages[$errorIndex],
            ]);
            $tool2->logs()->create([
                'api_id' => $api->id,
                'user_id' => $tool2->user_id,
                'triggered_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 24)),
                'response_code' => $errorCodes[$errorIndex],
                'response_body' => json_encode(['error' => $errorMessages[$errorIndex]]),
                'execution_time' => rand(50, 1000) / 1000, // 0.05 to 1 second
                'success' => false,
                'error_message' => $errorMessages[$errorIndex],
            ]);
        }
    }
}
