<?php

namespace Database\Seeders;

use App\Enums\BotProvider;
use App\Models\Bot;
use App\Models\Command;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BotCommandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get admin user or create if doesn't exist
        $admin = User::where('email', 'admin@example.com')->first();

        if (!$admin) {
            $admin = User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'is_admin' => true,
            ]);
        }

        // Create realistic bots
        $this->createBots($admin);
    }

    /**
     * Create realistic bots with associated commands
     */
    private function createBots(User $admin): void
    {
        // 1. Create an Anthropic-powered assistant bot
        $claudeBot = Bot::create([
            'user_id' => $admin->id,
            'name' => 'Claude Assistant',
            'username' => 'claude_assistant_bot',
            'bot_token' => 'bot' . Str::random(40),
            'bot_provider' => BotProvider::ANTHROPIC,
            'api_key' => 'sk-ant-api' . Str::random(40),
            'system_prompt' => "You are Claude, a helpful AI assistant created by Anthropic. You're designed to be helpful, harmless, and honest. Always respond in a friendly and professional manner. Provide concise, accurate information to user questions. If you don't know something, admit it rather than making up information. When users ask for help with tasks, provide step-by-step guidance. Respect user privacy and don't ask for personal information.",
            'is_active' => true,
            'settings' => [
                'allowed_updates' => ['message', 'edited_message', 'callback_query'],
                'max_tokens' => 2000,
                'temperature' => 0.7,
                'model' => 'claude-3-sonnet-20240229',
            ],
            'last_active_at' => now(),
        ]);

        // Create commands for Claude bot
        $this->createCommandsForBot($claudeBot, $admin->id);

        // 2. Create a customer support bot
        $supportBot = Bot::create([
            'user_id' => $admin->id,
            'name' => 'Customer Support',
            'username' => 'support_helper_bot',
            'bot_token' => 'bot' . Str::random(40),
            'bot_provider' => BotProvider::OPENAI,
            'api_key' => 'sk-' . Str::random(45),
            'system_prompt' => "You are a customer support AI assistant. Your primary goal is to help users resolve their issues efficiently and professionally. Answer questions about products, services, and policies. For complex issues, suggest escalation paths. Maintain a friendly, patient tone even when users are frustrated. Use clear, jargon-free language. If you don't have enough information to resolve an issue, ask clarifying questions. Never make up policies or information you're uncertain about.",
            'is_active' => true,
            'settings' => [
                'allowed_updates' => ['message', 'edited_message', 'callback_query'],
                'max_tokens' => 1500,
                'temperature' => 0.5,
                'model' => 'gpt-4',
            ],
            'last_active_at' => now()->subDays(2),
        ]);

        // Create commands for support bot
        $this->createSupportCommands($supportBot, $admin->id);

        // 3. Create a weather bot
        $weatherBot = Bot::create([
            'user_id' => $admin->id,
            'name' => 'Weather Forecast',
            'username' => 'weather_forecast_bot',
            'bot_token' => 'bot' . Str::random(40),
            'bot_provider' => BotProvider::OPENAI,
            'api_key' => 'sk-' . Str::random(45),
            'system_prompt' => "You are a weather forecasting assistant. When users ask about weather, simulate providing weather information for their location. Ask users for their city/location if not provided. Include temperature (in both Celsius and Fahrenheit), precipitation chance, humidity, wind speed, and a brief description of conditions. For multi-day forecasts, provide a summary for each day. Note: Since you don't have real-time weather data, inform users that this is a simulation and they should check an actual weather service for accurate information.",
            'is_active' => true,
            'settings' => [
                'allowed_updates' => ['message', 'callback_query'],
                'max_tokens' => 1000,
                'temperature' => 0.3,
                'model' => 'gpt-3.5-turbo',
            ],
            'last_active_at' => now()->subHours(5),
        ]);

        // Create commands for weather bot
        $this->createWeatherCommands($weatherBot, $admin->id);

        // 4. Create a language translation bot (inactive)
        $translationBot = Bot::create([
            'user_id' => $admin->id,
            'name' => 'Language Translator',
            'username' => 'translate_master_bot',
            'bot_token' => 'bot' . Str::random(40),
            'bot_provider' => BotProvider::OPENAI,
            'api_key' => 'sk-' . Str::random(45),
            'system_prompt' => "You are a language translation assistant. Your job is to translate text between languages accurately while preserving meaning, tone, and context. When translating, consider cultural nuances and idiomatic expressions. For ambiguous requests, ask users to specify source and target languages. If you're unsure about a translation, indicate your uncertainty and provide alternatives. For technical or specialized content, mention that specialized terminology might require expert verification.",
            'is_active' => false,
            'settings' => [
                'allowed_updates' => ['message'],
                'max_tokens' => 1500,
                'temperature' => 0.2,
                'model' => 'gpt-4',
            ],
            'last_active_at' => now()->subMonth(),
        ]);

        // Create commands for translation bot
        $this->createTranslationCommands($translationBot, $admin->id);
    }

    /**
     * Create standard commands for all bots
     */
    private function createCommandsForBot(Bot $bot, int $userId): void
    {
        Command::create([
            'user_id' => $userId,
            'bot_id' => $bot->id,
            'name' => 'Start',
            'command' => '/start',
            'description' => 'Start interacting with the bot',
            'system_prompt_override' => "You are responding to a /start command. Welcome the user warmly and briefly explain what you can do. Keep it concise and friendly. Suggest 2-3 example commands they can try.",
            'is_active' => true,
            'priority' => 10,
        ]);

        Command::create([
            'user_id' => $userId,
            'bot_id' => $bot->id,
            'name' => 'Help',
            'command' => '/help',
            'description' => 'Display help information',
            'system_prompt_override' => "You are responding to a /help command. List all available commands with brief descriptions. Provide basic usage instructions and example queries. Mention any limitations. End with an encouraging note to try out commands.",
            'is_active' => true,
            'priority' => 9,
        ]);
    }

    /**
     * Create commands specific to the customer support bot
     */
    private function createSupportCommands(Bot $bot, int $userId): void
    {
        // Add standard commands first
        $this->createCommandsForBot($bot, $userId);

        // Support-specific commands
        Command::create([
            'user_id' => $userId,
            'bot_id' => $bot->id,
            'name' => 'FAQ',
            'command' => '/faq',
            'description' => 'Show frequently asked questions',
            'system_prompt_override' => "You are responding to a /faq command. Provide a list of frequently asked questions about our fictional product or service. Include 5-7 common questions and their answers. Keep responses concise but helpful.",
            'is_active' => true,
            'priority' => 8,
        ]);

        Command::create([
            'user_id' => $userId,
            'bot_id' => $bot->id,
            'name' => 'Contact',
            'command' => '/contact',
            'description' => 'Get contact information',
            'system_prompt_override' => "You are responding to a /contact command. Provide fictional contact information including: email address, phone number, business hours, and social media handles. Emphasize that this is simulated data for demonstration purposes.",
            'is_active' => true,
            'priority' => 7,
        ]);

        Command::create([
            'user_id' => $userId,
            'bot_id' => $bot->id,
            'name' => 'Ticket',
            'command' => '/ticket',
            'description' => 'Create a support ticket',
            'system_prompt_override' => "You are responding to a /ticket command. Guide the user through creating a fictional support ticket. Ask for a brief description of their issue and simulate creating a ticket with a generated ID. Explain next steps and expected response time.",
            'is_active' => true,
            'priority' => 6,
        ]);
    }

    /**
     * Create commands specific to the weather bot
     */
    private function createWeatherCommands(Bot $bot, int $userId): void
    {
        // Add standard commands first
        $this->createCommandsForBot($bot, $userId);

        // Weather-specific commands
        Command::create([
            'user_id' => $userId,
            'bot_id' => $bot->id,
            'name' => 'Weather',
            'command' => '/weather',
            'description' => 'Get current weather for a location',
            'system_prompt_override' => "You are responding to a /weather command. Ask for the user's location if not provided. Then simulate providing current weather conditions for that location. Include temperature, conditions, humidity, wind speed, feels-like temperature, and UV index. Remember to note this is simulated data.",
            'is_active' => true,
            'priority' => 8,
        ]);

        Command::create([
            'user_id' => $userId,
            'bot_id' => $bot->id,
            'name' => 'Forecast',
            'command' => '/forecast',
            'description' => 'Get 5-day weather forecast',
            'system_prompt_override' => "You are responding to a /forecast command. Ask for the user's location if not provided. Then simulate providing a 5-day weather forecast for that location. Include high/low temperatures, conditions, and precipitation chance for each day. Present the forecast in a clear, easy-to-read format. Remember to note this is simulated data.",
            'is_active' => true,
            'priority' => 7,
        ]);

        Command::create([
            'user_id' => $userId,
            'bot_id' => $bot->id,
            'name' => 'Alerts',
            'command' => '/alerts',
            'description' => 'Check for weather alerts',
            'system_prompt_override' => "You are responding to a /alerts command. Ask for the user's location if not provided. Then simulate checking for weather alerts or warnings for that area. Most of the time, report no active alerts, but occasionally simulate a minor weather advisory. Remember to note this is simulated data.",
            'is_active' => true,
            'priority' => 6,
        ]);
    }

    /**
     * Create commands specific to the translation bot
     */
    private function createTranslationCommands(Bot $bot, int $userId): void
    {
        // Add standard commands first
        $this->createCommandsForBot($bot, $userId);

        // Translation-specific commands
        Command::create([
            'user_id' => $userId,
            'bot_id' => $bot->id,
            'name' => 'Translate',
            'command' => '/translate',
            'description' => 'Translate text to another language',
            'system_prompt_override' => "You are responding to a /translate command. Ask the user to provide the text they want to translate and the target language. Then provide a translation of their text into the requested language. If they don't specify a language, ask which language they want to translate to.",
            'is_active' => true,
            'priority' => 8,
        ]);

        Command::create([
            'user_id' => $userId,
            'bot_id' => $bot->id,
            'name' => 'Languages',
            'command' => '/languages',
            'description' => 'List available languages',
            'system_prompt_override' => "You are responding to a /languages command. Provide a list of 15-20 commonly used languages that you can translate between. Organize them alphabetically and present in a clear format.",
            'is_active' => true,
            'priority' => 7,
        ]);

        Command::create([
            'user_id' => $userId,
            'bot_id' => $bot->id,
            'name' => 'Detect',
            'command' => '/detect',
            'description' => 'Detect language of text',
            'system_prompt_override' => "You are responding to a /detect command. Ask the user to provide the text they want to analyze. Then identify the language of their text. Include both the language name and confidence level in your response.",
            'is_active' => true,
            'priority' => 6,
        ]);
    }
}
