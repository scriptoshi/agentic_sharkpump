<?php

return [
    'packages' => [
        'free' => [
            'name' => 'Free',
            'description' => 'Perfect for testing',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'features' => [
                'Max 30 secs video',
                '10 mins / Month',
                'All features',
                'Watermark',
            ],
            'stripe_monthly_price_id' => null,
            'stripe_yearly_price_id' => null,
        ],
        'pro' => [
            'name' => 'Professional',
            'description' => 'Ideal for creators',
            'monthly_price' => 5,
            'yearly_price' => 50,
            'features' => [
                'Up to 5 mins video',
                '100 mins / Month',
                'All features',
                'No Watermark',
            ],
            'stripe_monthly_price_id' => env('STRIPE_PRO_MONTHLY_PRICE_ID'),
            'stripe_yearly_price_id' => env('STRIPE_PRO_YEARLY_PRICE_ID'),
        ],
        'max' => [
            'name' => 'Max',
            'description' => 'For influencers and streamers',
            'monthly_price' => 11,
            'yearly_price' => 110,
            'features' => [
                'Up to 30 mins video',
                '300 mins / Month',
                'All features',
                'No Watermark',
                'Unlimited Storage',
                'Priority Queue',
            ],
            'stripe_monthly_price_id' => env('STRIPE_MAX_MONTHLY_PRICE_ID'),
            'stripe_yearly_price_id' => env('STRIPE_MAX_YEARLY_PRICE_ID'),
        ],
    ],
];
