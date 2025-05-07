<?php

return [
    'packages' => [
        'free' => [
            'name' => 'Free',
            'description' => 'Perfect for vibecoders',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'features' => [
                'Host a single bot',
                'No Credits integration',
                '5 Inbuild Tools',
                'No user forwarding',
                'No priority updates',
            ],
            'stripe_monthly_price_id' => null,
            'stripe_yearly_price_id' => null,
            'is_popular' => false,
        ],
        'pro' => [
            'name' => 'Professional',
            'description' => 'Perfect for freelancers',
            'monthly_price' => 9,
            'yearly_price' => 69,
            'features' => [
                'Launch upto 3 bots',
                'Telegram stars billing',
                '10 Tools integration',
                '20 custom tools',
                'User forwarding',
                'Priority updates',
            ],
            'stripe_monthly_price_id' => env('STRIPE_PRO_MONTHLY_PRICE_ID'),
            'stripe_yearly_price_id' => env('STRIPE_PRO_YEARLY_PRICE_ID'),
            'is_popular' => true,
        ],
        'max' => [
            'name' => 'Enterprise',
            'description' => 'Best for nano saas startup',
            'monthly_price' => 49.9,
            'yearly_price' => 399,
            'features' => [
                'All features of Pro',
                'Unlimited bots',
                'Unlimited Tools',
                'Unlimited user forwarding',
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
