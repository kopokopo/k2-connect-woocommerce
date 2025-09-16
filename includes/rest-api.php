<?php

add_action('rest_api_init', function () {
    register_rest_route('kkwoo/v1', '/stk-push', [
        'methods'  => 'POST',
        'callback' => 'kkwoo_handle_stk_push',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('kkwoo/v1', '/get-session-order', [
        'methods'  => 'GET',
        'callback' => function () {
            $order_id = WC()->session->get('kkwoo_order_id');
            return ['order_id' => $order_id];
        },
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('kkwoo/v1', '/stk-push-callback', [
        'methods'  => 'POST',
        'callback' => 'kkwoo_handle_stk_push_callback',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('kkwoo/v1', '/payment-status', [
        'methods' => 'GET',
        'callback' => 'kkwoo_get_payment_status',
        'permission_callback' => '__return_true',
    ]);
});

function kkwoo_handle_stk_push(WP_REST_Request $request)
{
    $phone = sanitize_text_field($request->get_param('phone'));
    $order_key = sanitize_text_field($request->get_param('order_key'));

    $order_id = wc_get_order_id_by_order_key($order_key);
    if (!$order_id) {
        return new WP_REST_Response(['status' => 'error', 'data' => KKWoo_User_Friendly_Messages::get('order_not_found')], 400);
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_REST_Response(['status' => 'error', 'data' => KKWoo_User_Friendly_Messages::get('order_not_found')], 404);
    }

    if (!in_array($order->get_status(), ['pending', 'failed'], true)) {
        KKWoo_Logger::log(
            sprintf( 'Customer attempted payment for order %d, but status is "%s".', $order->get_id(), $order->get_status() ),
            'warning'
        );
        return new WP_REST_Response(['status' => 'error', 'data' => KKWoo_User_Friendly_Messages::get('invalid_order_status')], 400);
    }

    require_once __DIR__ . '/stk-service.php';
    $response = kkwoo_send_stk_push($phone, $order);

    if (is_wp_error($response)) {
        return new WP_REST_Response([
            'status' => 'error',
            'data'   => $response->get_error_message(),
        ], 500);
    }

    if (isset($response['status']) && $response['status'] === 'success') {
        $order->update_status('on-hold', KKWoo_User_Friendly_Messages::get('on_hold_status_update'));

        return new WP_REST_Response([
            'status'   => 'success',
            'data'     => $response
        ], 200);
    }

    return new WP_REST_Response([
        'status'   => 'error',
        'data'     => $response
    ], 500);
}

function kkwoo_handle_stk_push_callback(WP_REST_Request $request)
{
    $payload = $request->get_json_params();

    if (empty($payload['data'])) {
        KKWoo_Logger::log(KKWoo_User_Friendly_Messages::get('invalid_stk_push_callback_data'), 'error');
        return new WP_REST_Response(['status' => 'error', 'message' => 'Invalid callback data'], 400);
    }

    $attributes = $payload['data']['attributes'];
    $metadata   = $attributes['metadata'];
    $event      = $attributes['event']['resource'] ?? [];
    $status     = $attributes['status'];
    $reference  = $metadata['reference'] ?? null;

    if (!$reference) {
        KKWoo_Logger::log(KKWoo_User_Friendly_Messages::get('invalid_stk_push_callback_data'), 'error');
        return new WP_REST_Response(['status' => 'error', 'message' => 'Missing order reference'], 400);
    }

    $order_id = wc_get_order_id_by_order_key($reference);
    if (!$order_id) {
        KKWoo_Logger::log(KKWoo_User_Friendly_Messages::get('order_not_found'), 'error');
        return new WP_REST_Response(['status' => 'error', 'message' => 'Order not found'], 404);
    }

    $order = wc_get_order($order_id);

    if ($status === 'Success' && isset($event['status']) && $event['status'] === 'Received') {
        // Mark order as paid
        $order->payment_complete($event['id']);
        $order->add_order_note(sprintf(
            'Payment received via Kopo Kopo for WooCommerce. Amount: %s %s. Phone: %s',
            $event['currency'],
            $event['amount'],
            $event['sender_phone_number']
        ));

        // Necessary to update this to null when successful so as to remove error if earlier saved 
        $order->update_meta_data('kkwoo_payment_error_msg', '');
        $order->save();
        return new WP_REST_Response(['status' => 'success', 'message' => 'Payment processed'], 200);
    } else {
        // Payment failed or was cancelled
        $order->update_status('failed', KKWoo_User_Friendly_Messages::get('failed_status_update'));
        $order->update_meta_data('kkwoo_payment_error_msg', $attributes['event']['errors']);
        $order->save();
        return new WP_REST_Response(['status' => 'error', 'message' => $attributes['event']['errors']], 200);
    }
}

function kkwoo_get_payment_status(WP_REST_Request $request)
{
    $order_key = $request->get_param('order_key');
    $order_id = wc_get_order_id_by_order_key($order_key);
    $order = $order_id ? wc_get_order($order_id) : null;

    return [
        'status' => $order ? $order->get_status() : 'not_found',
        'data' => $order ? $order->get_meta('kkwoo_payment_error_msg', true) : '',
    ];
}
