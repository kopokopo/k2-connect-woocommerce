<?php

// tests/fixtures/stk_callback_payloads.php

return array(
	'validated_success' => function ( $order_key ) {
		return array(
			'status' => 'success',
			'data'   => array(
				'id'                => '0895010c-fe23-473c-944a-3277971e239a',
				'type'              => 'incoming_payment',
				'initiationTime'    => '2026-02-12T07:38:56.896+03:00',
				'status'            => 'Success',
				'eventType'         => 'Buygoods Transaction',
				'resourceId'        => '0895010c-fe23-473c-944a-3277971e239a',
				'reference'         => '1770871136',
				'originationTime'   => '2026-02-12T07:38:56.932+03:00',
				'senderPhoneNumber' => '+254928488111',
				'amount'            => '249.0',
				'currency'          => 'KES',
				'tillNumber'        => 'K343143',
				'system'            => 'Lipa Na MPESA',
				'resourceStatus'    => 'Received',
				'senderFirstName'   => 'Doreen',
				'senderMiddleName'  => '',
				'senderLastName'    => 'Chemweno',
				'errors'            => array(),
				'metadata'          => array(
					'notes'       => 'Payment for invoice 1173',
					'reference'   => $order_key, // use dynamic order key
					'customer_id' => 1,
				),
				'linkSelf'          => 'https://sandbox.kopokopo.com/api/v1/incoming_payments/0895010c-fe23-473c-944a-3277971e239a',
				'callbackUrl'       => 'https://example.com/wp-json/kkwoo/v1/stk-push-callback',
			),
		);
	},
	'success'           => function ( $order_key ) {
		return array(
			'data' => array(
				'id'         => 'd7443ea0-d2d1-41f6-b269-aabcc4b8a932',
				'type'       => 'incoming_payment',
				'attributes' => array(
					'initiation_time' => '2025-09-27T19:16:46.658+03:00',
					'status'          => 'Success',
					'event'           => array(
						'type'     => 'Incoming Payment Request',
						'resource' => array(
							'id'                  => '1e6ec016-845d-4505-ad1b-38b243a1f16e',
							'amount'              => 115.0,
							'status'              => 'Received',
							'system'              => 'Lipa Na M-PESA',
							'currency'            => 'KES',
							'reference'           => '12345678ab10',
							'till_number'         => 'K343143',
							'origination_time'    => '2025-09-27T19:17:12+03:00',
							'sender_last_name'    => '',
							'sender_first_name'   => '',
							'sender_middle_name'  => '',
							'hashed_sender_phone' => 'd30d07eef610b83d1f65f3a15280499552e5f0fcb86c3890d7375291c6ba4e70',
							'sender_phone_number' => '+254929488147',
						),
						'errors'   => null,
					),
					'metadata'        => array(
						'notes'       => 'Payment for invoice ' . $order_key,
						'reference'   => $order_key,
						'customer_id' => 0,
					),
					'_links'          => array(
						'callback_url' => 'https://ff3abb3d7502.ngrok-free.app/wp-json/kkwoo/v1/stk-push-callback',
						'self'         => 'https://api.kopokopo.com/api/v1/incoming_payments/d7443ea0-d2d1-41f6-b269-aabcc4b8a932',
					),
				),
			),
		);
	},
	'validated_failed'  => function ( $order_key ) {
		return array(
			'status' => 'success',
			'data'   => array(
				'id'             => 'cac95329-9fa5-42f1-a4fc-c08af7b868fb',
				'type'           => 'incoming_payment',
				'initiationTime' => '2018-06-20T22:45:12.790Z',
				'status'         => 'Failed',
				'eventType'      => 'Incoming Payment Request',
				'resource'       => null,
				'errors'         => 'The balance is insufficient for the transaction',
				'metadata'       => array(
					'customer_id' => 0,
					'reference'   => $order_key,
					'notes'       => 'Payment for invoice ' . $order_key,
				),
				'linkSelf'       => 'https://sandbox.kopokopo.com/payment_request_results/cac95329-9fa5-42f1-a4fc-c08af7b868fb',
				'callbackUrl'    => 'https://webhook.site/fa3645c6-7199-426a-8efa-98e7b754babb',
			),
		);
	},
	'failed'            => function ( $order_key ) {
		return array(
			'data' => array(
				'id'         => '090cceda-2489-4343-8125-08a58ac0d9a0',
				'type'       => 'incoming_payment',
				'attributes' => array(
					'initiation_time' => '2025-09-27T19:32:44.527+03:00',
					'status'          => 'Failed',
					'event'           => array(
						'type'     => 'Incoming Payment Request',
						'resource' => null,
						'errors'   => 'The balance is insufficient for the transaction',
					),
					'metadata'        => array(
						'notes'       => 'Payment for invoice ' . $order_key,
						'reference'   => $order_key,
						'customer_id' => 0,
					),
					'_links'          => array(
						'callback_url' => 'https://ff3abb3d7502.ngrok-free.app/wp-json/kkwoo/v1/stk-push-callback',
						'self'         => 'https://api.kopokopo.com/api/v1/incoming_payments/090cceda-2489-4343-8125-08a58ac0d9a0',
					),
				),
			),
		);
	},
);
