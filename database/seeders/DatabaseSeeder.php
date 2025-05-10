<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        $user = User::where('email', 'admin@sharktitl.es')->first();
        if (!$user) {
            $user = User::create([
                'name' => 'Admin',
                'email' => 'admin@sharktitl.es',
                'password' => '*sharkTitles123#',
                'is_admin' => true,
                'address' => '0x781a793bb5390bc7DA0879d9b626985D13c61501',
            ]);
        }

        // Call the seeders to generate dummy data
        $this->call([
            ApiSeeder::class,
            //BotCommandSeeder::class,
            VcSeeder::class,
            FileSeeder::class,
        ]);
    }
}
