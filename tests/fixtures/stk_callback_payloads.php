<?php

// tests/fixtures/stk_callback_payloads.php

return [
    'success' => function ($order_key) {
        return [
            'data' => [
                'id' => 'd7443ea0-d2d1-41f6-b269-aabcc4b8a932',
                'type' => 'incoming_payment',
                'attributes' => [
                    'initiation_time' => '2025-09-27T19:16:46.658+03:00',
                    'status' => 'Success',
                    'event' => [
                        'type' => 'Incoming Payment Request',
                        'resource' => [
                            'id' => '1e6ec016-845d-4505-ad1b-38b243a1f16e',
                            'amount' => 115.0,
                            'status' => 'Received',
                            'system' => 'Lipa Na M-PESA',
                            'currency' => 'KES',
                            'reference' => '12345678ab10',
                            'till_number' => 'K343143',
                            'origination_time' => '2025-09-27T19:17:12+03:00',
                            'sender_last_name' => '',
                            'sender_first_name' => '',
                            'sender_middle_name' => '',
                            'hashed_sender_phone' => 'd30d07eef610b83d1f65f3a15280499552e5f0fcb86c3890d7375291c6ba4e70',
                            'sender_phone_number' => '+254929488147',
                        ],
                        'errors' => null,
                    ],
                    'metadata' => [
                        'notes' => 'Payment for invoice ' . $order_key,
                        'reference' => $order_key,
                        'customer_id' => 0,
                    ],
                    '_links' => [
                        'callback_url' => 'https://ff3abb3d7502.ngrok-free.app/wp-json/kkwoo/v1/stk-push-callback',
                        'self' => 'https://api.kopokopo.com/api/v1/incoming_payments/d7443ea0-d2d1-41f6-b269-aabcc4b8a932',
                    ],
                ],
            ],
        ];
    },

    'failed' => function ($order_key) {
        return [
            'data' => [
                'id' => '090cceda-2489-4343-8125-08a58ac0d9a0',
                'type' => 'incoming_payment',
                'attributes' => [
                    'initiation_time' => '2025-09-27T19:32:44.527+03:00',
                    'status' => 'Failed',
                    'event' => [
                        'type' => 'Incoming Payment Request',
                        'resource' => null,
                        'errors' => 'The balance is insufficient for the transaction',
                    ],
                    'metadata' => [
                        'notes' => 'Payment for invoice ' . $order_key,
                        'reference' => $order_key,
                        'customer_id' => 0,
                    ],
                    '_links' => [
                        'callback_url' => 'https://ff3abb3d7502.ngrok-free.app/wp-json/kkwoo/v1/stk-push-callback',
                        'self' => 'https://api.kopokopo.com/api/v1/incoming_payments/090cceda-2489-4343-8125-08a58ac0d9a0',
                    ],
                ],
            ],
        ];
    },
];
