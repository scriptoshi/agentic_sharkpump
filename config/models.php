<?php
return [
    'deepseek' => [
        [
            'id' => 'deepseek-chat',
            'name' => 'DeepSeek-V3',
            'input_token' => '$0.27/MTk', // Using cache miss standard price
            'output_token' => '$1.10/MTk',
        ],
        [
            'id' => 'deepseek-reasoner',
            'name' => 'DeepSeek-R1 (Thinking)',
            'input_token' => '$0.55/MTk', // Using cache miss standard price
            'output_token' => '$2.19/MTk',
        ],
    ],

    'gemini' => [
        [
            'id' => 'gemini-2.5-pro-preview-03-25',
            'name' => 'Gemini 2.5 Pro Preview 03-25',
            'input_token' => '$1.25/MTk',
            'output_token' => '$10.00/MTk'
        ],
        [
            'id' => 'gemini-2.5-flash-preview-04-17',
            'name' => 'Gemini 2.5 Flash Preview 04-17',
            'input_token' => '$0.15/MTk',
            'output_token' => '$3.50/MTk',
        ],
    ],
    'anthropic' => [
        [
            'id' => 'claude-3-7-sonnet-latest',
            'name' => 'Claude 3.7 Sonnet',
            'input_token' => "$3/MTk",
            'output_token' => "$15/MTk",
        ],
        [
            'id' => 'claude-3-5-haiku-latest',
            'name' => 'Claude 3.5 Haiku',
            'input_token' => "$0.8/MTk",
            'output_token' => "$4/MTk",
        ],
        [
            'id' => 'claude-3-5-sonnet-latest',
            'name' => 'Claude 3.5 Sonnet',
            'input_token' => "$3/MTk",
            'output_token' => "$15/MTk",
        ],
        [
            'id' => 'claude-3-opus-latest',
            'name' => 'Claude 3 Opus',
            'input_token' => "$15/MTk",
            'output_token' => "$75/MTk",
        ]
    ],
    'openai' => [
        [
            'id' => 'gpt-4.1-2025-04-14',
            'name' => 'GPT-4.1',
            'input_token' => '$2.00/MTk',
            'output_token' => '$8.00/MTk',
        ],
        [
            'id' => 'gpt-4.1-mini-2025-04-14',
            'name' => 'GPT-4.1-mini',
            'input_token' => '$0.40/MTk',
            'output_token' => '$1.60/MTk',
        ],
        [
            'id' => 'gpt-4.1-nano-2025-04-14',
            'name' => 'GPT-4.1-nano',
            'input_token' => '$0.10/MTk',
            'output_token' => '$0.40/MTk',
        ],
        [
            'id' => 'gpt-4.5-preview-2025-02-27',
            'name' => 'GPT-4.5-preview',
            'input_token' => '$75.00/MTk',
            'output_token' => '$150.00/MTk',
        ],
        [
            'id' => 'gpt-4o-2024-08-06',
            'name' => 'GPT-4o',
            'input_token' => '$2.50/MTk',
            'output_token' => '$10.00/MTk',
        ],
        [
            'id' => 'gpt-4o-audio-preview-2024-12-17',
            'name' => 'GPT-4o-audio-preview',
            'input_token' => '$2.50/MTk',
            'output_token' => '$10.00/MTk',
        ],
        [
            'id' => 'gpt-4o-realtime-preview-2024-12-17',
            'name' => 'GPT-4o-realtime-preview',
            'input_token' => '$5.00/MTk',
            'output_token' => '$20.00/MTk',
        ],
        [
            'id' => 'gpt-4o-mini-2024-07-18',
            'name' => 'GPT-4o-mini',
            'input_token' => '$0.15/MTk',
            'output_token' => '$0.60/MTk',
        ],
        [
            'id' => 'gpt-4o-mini-audio-preview-2024-12-17',
            'name' => 'GPT-4o-mini-audio-preview',
            'input_token' => '$0.15/MTk',
            'output_token' => '$0.60/MTk',
        ],
        [
            'id' => 'gpt-4o-mini-realtime-preview-2024-12-17',
            'name' => 'GPT-4o-mini-realtime-preview',
            'input_token' => '$0.60/MTk',
            'output_token' => '$2.40/MTk',
        ],
        [
            'id' => 'o1-2024-12-17',
            'name' => 'o1',
            'input_token' => '$15.00/MTk',
            'output_token' => '$60.00/MTk',
        ],
        [
            'id' => 'o1-pro-2025-03-19',
            'name' => 'o1-pro',
            'input_token' => '$150.00/MTk',
            'output_token' => '$600.00/MTk',
        ],
        [
            'id' => 'o3-2025-04-16',
            'name' => 'o3',
            'input_token' => '$10.00/MTk',
            'output_token' => '$40.00/MTk',
        ],
        [
            'id' => 'o4-mini-2025-04-16',
            'name' => 'o4-mini',
            'input_token' => '$1.10/MTk',
            'output_token' => '$4.40/MTk',
        ],
        [
            'id' => 'o3-mini-2025-01-31',
            'name' => 'o3-mini',
            'input_token' => '$1.10/MTk',
            'output_token' => '$4.40/MTk',
        ],
        [
            'id' => 'o1-mini-2024-09-12',
            'name' => 'o1-mini',
            'input_token' => '$1.10/MTk',
            'output_token' => '$4.40/MTk',
        ],
        [
            'id' => 'gpt-4o-mini-search-preview-2025-03-11',
            'name' => 'GPT-4o-mini-search-preview',
            'input_token' => '$0.15/MTk',
            'output_token' => '$0.60/MTk',
        ],
        [
            'id' => 'gpt-4o-search-preview-2025-03-11',
            'name' => 'GPT-4o-search-preview',
            'input_token' => '$2.50/MTk',
            'output_token' => '$10.00/MTk',
        ],
    ]
];
