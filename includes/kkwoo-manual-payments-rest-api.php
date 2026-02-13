<?php

namespace KKWoo\ManualPayments;

if (!defined('ABSPATH')) {
    exit;
}

use KKWoo_Logger;
use WC_Order;
use WP_REST_Request;
use WP_REST_Response;
use KKWoo\Database\Manual_Payments_Tracker_repository;
use KKWoo\ManualPayments\Manual_Payment_Service;
use KKWoo\Security\Request_Validator;

add_action('rest_api_init', function () {
    register_rest_route('kkwoo/v1', '/save-manual-payment-details', [
        'methods'  => 'POST',
        'callback' => __NAMESPACE__ . '\\handle_save_manual_payment_details',
        'permission_callback' => [Request_Validator::class, 'validate_order_access'],
    ]);

    register_rest_route('kkwoo/v1', '/selected-manual-payment-method/(?P<order_key>.+)', [
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\\get_selected_manual_payment_method',
        'permission_callback' => [Request_Validator::class, 'validate_order_access'],
    ]);
});

function handle_save_manual_payment_details(WP_REST_Request $request): WP_REST_Response
{
    try {
        $mpesa_ref_no = sanitize_text_field($request->get_param('mpesa_ref_no'));
        $order_key = sanitize_text_field($request->get_param('order_key'));

        if (empty($order_key)) {
            return new WP_REST_Response([
                'status' => 'error',
                'data'   => \KKWoo_User_Friendly_Messages::get('order_not_found')
            ], 400);
        }

        $order_id = wc_get_order_id_by_order_key($order_key);
        if (!$order_id) {
            return new WP_REST_Response([
                'status' => 'error',
                'data'   => \KKWoo_User_Friendly_Messages::get('order_not_found')
            ], 404);
        }

        $order = wc_get_order($order_id);
        if (!$order) {
            return new WP_REST_Response([
                'status' => 'error',
                'data'   => \KKWoo_User_Friendly_Messages::get('order_not_found')
            ], 404);
        }

        if (empty($mpesa_ref_no)) {
            return new WP_REST_Response([
                'status' => 'error',
                'data'   => \KKWoo_User_Friendly_Messages::get('mpesa_ref_missing')
            ], 400);
        }

        $payment_tracker = Manual_Payments_Tracker_repository::get_by_mpesa_ref($mpesa_ref_no);

        if ($payment_tracker && $payment_tracker['order_id'] && $payment_tracker['order_id'] !== 0 && $payment_tracker['order_id'] !== $order_id) {
            return new WP_REST_Response([
                'status'  => 'error',
                'message' => \KKWoo_User_Friendly_Messages::get('mpesa_ref_submission_error')
            ], 500);
        }

        $upsert_result = Manual_Payments_Tracker_repository::upsert(
            $order_id,
            $mpesa_ref_no,
        );

        if (!$upsert_result) {
            return new WP_REST_Response([
                'status'  => 'error',
                'message' => \KKWoo_User_Friendly_Messages::get('mpesa_ref_submission_error')
            ], 500);
        }

        place_order_on_hold($order, $mpesa_ref_no);
        $webhook_payload = $payment_tracker['webhook_payload'] ?? null;

        if (!empty($webhook_payload)) {
            $decoded_payload = json_decode($webhook_payload, true);

            if (is_array($decoded_payload)) {
                $manual_payment_service = new Manual_Payment_Service();
                $manual_payment_service->complete($order, $decoded_payload['data']);
            }

            $order_status = $order->get_status();
            if ($order_status == 'processing' || $order_status == 'completed') {
                $amount = $order->get_total();
                $currency = get_woocommerce_currency_symbol($order->get_currency());
                $store_name = get_bloginfo('name');

                return new WP_REST_Response([
                    'status'  => 'success',
                    'message' => sprintf(
                        'You have paid %s %s to %s',
                        $currency,
                        $amount,
                        $store_name
                    ),
                ], 200);
            }
        }

        return new WP_REST_Response([
            'status'  => 'info',
            'message' => \KKWoo_User_Friendly_Messages::get('mpesa_ref_submitted')
        ], 200);

    } catch (\Throwable $e) {
        \KKWoo_Logger::log(
            'Error saving M-PESA reference: ' . $e->getMessage(),
            'error'
        );

        return new WP_REST_Response([
            'status'  => 'error',
            'message' => \KKWoo_User_Friendly_Messages::get('generic_unexpected_error_occured')
        ], 500);
    }
}

function get_selected_manual_payment_method(): WP_REST_Response
{
    $gateways = WC()->payment_gateways()->payment_gateways();
    if (isset($gateways['kkwoo'])) {
        $kkwoo = $gateways['kkwoo'];
        $enable_manual_payments      = $kkwoo->get_option('enable_manual_payments');
        $manual_payments_till_no     = $kkwoo->get_option('manual_payments_till_no');
        $paybill_business_no         = $kkwoo->get_option('paybill_business_no');
        $paybill_account_no          = $kkwoo->get_option('paybill_account_no');

        $data = [
            'enabled' => (bool) $enable_manual_payments,
            'till'    => $manual_payments_till_no ?: '',
            'paybill' => [
                'business_no' => $paybill_business_no ?: '',
                'account_no'  => $paybill_account_no ?: '',
            ],
        ];

        return new WP_REST_Response([
            'status'  => 'success',
            'data' => $data,
        ], 200);
    }

    return new WP_REST_Response([
        'status'  => 'error',
        'message' => \KKWoo_User_Friendly_Messages::get('generic_unexpected_error_occured')
    ], 500);

}

function place_order_on_hold(WC_Order $order, string $mpesa_ref_no)
{
    $order->update_status('on-hold', \KKWoo_User_Friendly_Messages::get('on_hold_status_update'));
    $order->add_order_note(sprintf(
        'The M-PESA reference number provided by the customer for this order is: %s',
        $mpesa_ref_no
    ));
    $order->update_meta_data('kkwoo_payment_location', '');
    $order->save();
}
