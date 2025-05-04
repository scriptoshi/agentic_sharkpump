<?php

namespace Database\Factories;

use App\Models\Bot;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Command>
 */
class CommandFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $commands = [
            '/start' => 'Start interacting with the bot',
            '/help' => 'Display help information',
            '/weather' => 'Get current weather information',
            '/news' => 'Get latest news',
            '/translate' => 'Translate text to another language',
            '/reminder' => 'Set a reminder',
            '/joke' => 'Tell a joke',
            '/quote' => 'Get an inspirational quote',
        ];

        $command = $this->faker->randomElement(array_keys($commands));
        
        return [
            'user_id' => User::factory(),
            'bot_id' => Bot::factory(),
            'command' => $command,
            'description' => $commands[$command],
            'system_prompt_override' => $this->faker->boolean(30) ? $this->faker->paragraph() : null,
            'is_active' => $this->faker->boolean(90),
            'priority' => $this->faker->numberBetween(0, 10),
        ];
    }

    /**
     * Indicate that the command is active.
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
     * Set the command to a specific priority.
     *
     * @param int $priority
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function priority(int $priority)
    {
        return $this->state(function (array $attributes) use ($priority) {
            return [
                'priority' => $priority,
            ];
        });
    }
}
