<?php

return [
    'packages' => [
        'free' => [
            'name' => 'Free',
            'description' => 'Perfect for vibecoders',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'features' => [
                'For use in a single project',
                'Only Stripe integration',
                'Support Documentation',
                'No priority updates',
            ],
            'stripe_monthly_price_id' => null,
            'stripe_yearly_price_id' => null,
            'is_popular' => false,
        ],
        'pro' => [
            'name' => 'Professional',
            'description' => 'Perfect for freelancers',
            'monthly_price' => 5,
            'yearly_price' => 50,
            'features' => [
                'For use in infinite projects',
                'Stripe or Paddle integration',
                'Support Documentation',
                'Installation and CI Guides',
                'All System prompts',
                'Vibe coding Transcripts'
            ],
            'stripe_monthly_price_id' => env('STRIPE_PRO_MONTHLY_PRICE_ID'),
            'stripe_yearly_price_id' => env('STRIPE_PRO_YEARLY_PRICE_ID'),
            'is_popular' => true,
        ],
        'max' => [
            'name' => 'Max',
            'description' => 'Best for nano saas startup',
            'monthly_price' => 20,
            'yearly_price' => 199,
            'features' => [
                'All features of Pro',
                'Compete saas licensing bundle',
                'Full resell rights',
                'Free setup and support',
                'Priority updates',
            ],
            'stripe_monthly_price_id' => env('STRIPE_MAX_MONTHLY_PRICE_ID'),
            'stripe_yearly_price_id' => env('STRIPE_MAX_YEARLY_PRICE_ID'),
            'is_popular' => false,
        ],
    ],
];
