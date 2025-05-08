<?php

namespace Database\Seeders;

use App\Enums\BotProvider;
use App\Models\Bot;
use App\Models\User;
use App\Models\Vc;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class VcSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all bots
        $bots = Bot::where('bot_provider', BotProvider::OPENAI)->get();

        // For each bot, create vector collections
        foreach ($bots as $bot) {
            $user = User::find($bot->user_id);

            // Create 2-3 vector collections per bot
            $numCollections = rand(2, 3);

            for ($i = 0; $i < $numCollections; $i++) {
                // Define collection name and status based on index
                if ($i === 0) {
                    // First collection is active and in progress
                    $name = 'Primary Knowledge Base';
                    $status = 'in_progress';
                    $lastActive = now();
                } elseif ($i === 1) {
                    // Second collection is completed
                    $name = 'Reference Documents';
                    $status = 'completed';
                    $lastActive = now()->subDays(rand(1, 10));
                } else {
                    // Third collection (if exists) is either expired or in progress
                    $name = 'Historical Data';
                    $status = rand(0, 1) ? 'expired' : 'in_progress';
                    $lastActive = now()->subDays(rand(20, 40));
                }

                // Create the VC entry
                Vc::create([
                    'bot_id' => $bot->id,
                    'user_id' => $user->id,
                    'vector_id' => 'vc_' . Str::uuid(),
                    'vector_name' => $name . ' - ' . $bot->name,
                    'status' => $status,
                    'last_active_at' => $lastActive,
                    'expires_in_days' => rand(15, 60),
                ]);
            }
        }
    }
}
