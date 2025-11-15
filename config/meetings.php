<?php

return [
    'routing' => [
        'mode' => env('MEETINGS_MODE', 'path'),
        'prefix' => 'meetings',
    ],
    'guard' => 'web',

    'navigation' => [
        'route' => 'meetings.dashboard',
        'icon'  => 'heroicon-o-calendar',
        'order' => 25,
    ],

    'sidebar' => [
        [
            'group' => 'Allgemein',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'meetings.dashboard',
                    'icon'  => 'heroicon-o-home',
                ],
                [
                    'label' => 'Meeting erstellen',
                    'route' => 'meetings.create',
                    'icon'  => 'heroicon-o-plus',
                ],
            ],
        ],
        [
            'group' => 'Meetings',
            'dynamic' => [
                'model'     => \Platform\Meetings\Models\Meeting::class,
                'team_based' => true,
                'order_by'  => 'start_date',
                'route'     => 'meetings.show',
                'icon'      => 'heroicon-o-video-camera',
                'label_key' => 'title',
            ],
        ],
    ],
    'billables' => [
        [
            'model' => \Platform\Meetings\Models\Meeting::class,
            'type' => 'per_item',
            'label' => 'Meeting',
            'description' => 'Jedes erstellte Meeting verursacht tägliche Kosten nach Nutzung.',
            'pricing' => [
                [
                    'cost_per_day' => 0.0025,
                    'start_date' => '2025-01-01',
                    'end_date' => null,
                ]
            ],
            'free_quota' => null,
            'min_cost' => null,
            'max_cost' => null,
            'billing_period' => 'daily',
            'start_date' => '2026-01-01',
            'end_date' => null,
            'trial_period_days' => 0,
            'discount_percent' => 0,
            'exempt_team_ids' => [],
            'priority' => 100,
            'active' => true,
        ],
    ]
];

