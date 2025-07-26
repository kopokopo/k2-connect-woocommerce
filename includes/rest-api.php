<?php

add_action('rest_api_init', function () {
    register_rest_route('kkwoo/v1', '/stk-push', [
        'methods'  => 'POST',
        'callback' => 'kkwoo_handle_stk_push',
        'permission_callback' => '__return_true',
    ]);
});

function kkwoo_handle_stk_push(WP_REST_Request $request)
{
    $phone = sanitize_text_field($request->get_param('phone'));
    $order_key = sanitize_text_field($request->get_param('order_key'));

    $order_id = wc_get_order_id_by_order_key($order_key);
    if (!$order_id) {
        return new WP_REST_Response(['message' => 'Invalid order.'], 400);
    }

    require_once __DIR__ . '/stk-service.php';
    $success = kkwoo_send_stk_push($phone, $order_id);

    if ($success) {
        return new WP_REST_Response(['message' => 'STK push sent.'], 200);
    } else {
        return new WP_REST_Response(['message' => 'STK push failed.'], 500);
    }
}
