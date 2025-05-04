<?php

namespace Database\Factories;

use App\Enums\BotProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Bot>
 */
class BotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->name() . ' Bot',
            'username' => $this->faker->userName() . '_bot',
            'bot_token' => 'bot' . Str::random(40),
            'bot_provider' => $this->faker->randomElement(BotProvider::cases()),
            'api_key' => Str::random(50),
            'system_prompt' => $this->faker->paragraph(),
            'is_active' => $this->faker->boolean(80),
            'settings' => [
                'allowed_updates' => ['message', 'edited_message', 'callback_query'],
                'max_tokens' => 2000,
                'temperature' => 0.7,
            ],
            'last_active_at' => $this->faker->dateTimeThisMonth(),
        ];
    }

    /**
     * Indicate that the bot is active.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
            ];
        });
    }

    /**
     * Indicate that the bot is using Anthropic.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function anthropic()
    {
        return $this->state(function (array $attributes) {
            return [
                'bot_provider' => BotProvider::ANTHROPIC,
            ];
        });
    }
}
