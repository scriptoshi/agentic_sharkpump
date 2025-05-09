<?php

namespace Database\Seeders;

use App\Enums\FundAccess;
use App\Models\Fundraiser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class FundraiserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // DOWNLOAD fundraiser - allows user to download and use the software
        $downloadFundraiser = Fundraiser::create([
            'name' => 'Software Download Access',
            'description' => 'Get access to download the complete software sourcecode. Perfect for individuals who want to clone AiBotsForTelegram.com and host it on their own server. Includes one Year of updates and fixes. plus Enterprise membership to AiBotsForTelegram.com',
            'max' => 5,
            'amount' => 299,
            'access' => FundAccess::DOWNLOAD,
            'currency' => 'USD',
            'image' => 'fundraisers/download.png',
        ]);

        // MAX_LIFETIME fundraiser - grants lifetime access to Enterprise subscription
        $maxLifetimeFundraiser = Fundraiser::create([
            'name' => 'Lifetime Enterprise Access',
            'description' => 'A single payment for unlimited lifetime access to our Enterprise tier. Includes unlimited agents, unlimited tools, dedicated server (For one Year), and all premium features without any future payments. Future server payments will be at 432$/Year (Linode dedicated VPS)',
            'max' => 100,
            'amount' => 99.99,
            'access' => FundAccess::MAX_LIFETIME,
            'currency' => 'USD',
            'image' => 'fundraisers/lifetime-enterprise.png',
        ]);

        // PRO_LIFETIME fundraiser - grants lifetime access to Professional subscription
        Fundraiser::create([
            'name' => 'Lifetime Professional Access',
            'description' => 'Get permanent access to our Professional tier with a one-time contribution. Launch up to 3 agents, integrate 10 tools, 20 custom tools, user forwarding, and dedicated bot server (12 months). Future server payments will be at 76$/Year (Linode Shared VPS)',
            'max' => 100,
            'amount' => 69.99,
            'access' => FundAccess::PRO_LIFETIME,
            'currency' => 'USD',
            'image' => 'fundraisers/lifetime-pro.png',
        ]);

        // LIFETIME fundraiser - grants lifetime access to Free subscription
        $lifetimeFundraiser = Fundraiser::create([
            'name' => 'Lifetime Trial Subscription',
            'description' => 'One-time donation that gives you lifetime access to our Trial tier features without renewal. Host a single bot, access to 5 inbuilt tools, and more.',
            'max' => 200,
            'amount' => 49.99,
            'access' => FundAccess::LIFETIME,
            'currency' => 'USD',
            'image' => 'fundraisers/lifetime-free.png',
        ]);

        // 
        $downloadFundraiser->payables()->create([
            'payment_id' =>  Str::uuid(),
            'user_id' => 1,
            'payment_status' => 'finished',
            'pay_address' => '1',
            'pay_amount' => 299,
            'pay_currency' => 'USDT',
            'price_amount' => 299,
            'price_currency' => 'USD',
            'ipn_callback_url' => '1',
            'order_id' => '1',
            'order_description' => '1',
            'purchase_id' => '1',
        ]);
        $downloadFundraiser->payables()->create([
            'payment_id' => Str::uuid(),
            'user_id' => 1,
            'payment_status' => 'finished',
            'pay_address' => '1',
            'pay_amount' => 299,
            'pay_currency' => 'USDT',
            'price_amount' => 299,
            'price_currency' => 'USD',
            'ipn_callback_url' => '1',
            'order_id' => '1',
            'order_description' => '1',
            'purchase_id' => '1',
        ]);

        $maxLifetimeFundraiser->payables()->create([
            'payment_id' => Str::uuid(),
            'user_id' => 1,
            'payment_status' => 'finished',
            'pay_address' => '1',
            'pay_amount' => 99.99,
            'pay_currency' => 'USDT',
            'price_amount' => 99.99,
            'price_currency' => 'USD',
            'ipn_callback_url' => '1',
            'order_id' => '1',
            'order_description' => '1',
            'purchase_id' => '1',
        ]);
        $trials = [
            [
                'payment_id' => Str::uuid(),
                'uuid' => Str::uuid(),
                'user_id' => 1,
                'payment_status' => 'finished',
                'pay_address' => '1',
                'pay_amount' => 49.99,
                'pay_currency' => 'USDT',
                'price_amount' => 49.99,
                'price_currency' => 'USD',
                'ipn_callback_url' => '1',
                'order_id' => '1',
                'order_description' => '1',
                'purchase_id' => '1',
            ],
            [
                'payment_id' => Str::uuid(),
                'uuid' => Str::uuid(),
                'user_id' => 1,
                'payment_status' => 'finished',
                'pay_address' => '1',
                'pay_amount' => 49.99,
                'pay_currency' => 'USDT',
                'price_amount' => 49.99,
                'price_currency' => 'USD',
                'ipn_callback_url' => '1',
                'order_id' => '1',
                'order_description' => '1',
                'purchase_id' => '1',
            ],
            [
                'payment_id' => Str::uuid(),
                'uuid' => Str::uuid(),
                'user_id' => 1,
                'payment_status' => 'finished',
                'pay_address' => '1',
                'pay_amount' => 49.99,
                'pay_currency' => 'USDT',
                'price_amount' => 49.99,
                'price_currency' => 'USD',
                'ipn_callback_url' => '1',
                'order_id' => '1',
                'order_description' => '1',
                'purchase_id' => '1',
            ],
            [
                'payment_id' => Str::uuid(),
                'uuid' => Str::uuid(),
                'user_id' => 1,
                'payment_status' => 'finished',
                'pay_address' => '1',
                'pay_amount' => 49.99,
                'pay_currency' => 'USDT',
                'price_amount' => 49.99,
                'price_currency' => 'USD',
                'ipn_callback_url' => '1',
                'order_id' => '1',
                'order_description' => '1',
                'purchase_id' => '1',
            ],
            [
                'payment_id' => Str::uuid(),
                'uuid' => Str::uuid(),
                'user_id' => 1,
                'payment_status' => 'finished',
                'pay_address' => '1',
                'pay_amount' => 49.99,
                'pay_currency' => 'USDT',
                'price_amount' => 49.99,
                'price_currency' => 'USD',
                'ipn_callback_url' => '1',
                'order_id' => '1',
                'order_description' => '1',
                'purchase_id' => '1',
            ]
        ];
        foreach ($trials as $trial) {
            $lifetimeFundraiser->payables()->create($trial);
        }
    }
}
