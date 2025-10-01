<?php

namespace KKWoo\ManualPayments;

if (!defined('ABSPATH')) {
    exit;
}

use KKWoo_Logger;
use WP_REST_Request;
use WP_REST_Response;
use KKWoo\Database\Manual_Payments_Tracker_repository;

add_action('rest_api_init', function () {
    register_rest_route('kkwoo/v1', '/save-manual-payment-details', [
        'methods'  => 'POST',
        'callback' => __NAMESPACE__ . '\\handle_save_manual_payment_details',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('kkwoo/v1', '/selected-manual-payment-method', [
        'methods' => 'GET',
        'callback' => __NAMESPACE__ . '\\get_selected_manual_payment_method',
        'permission_callback' => '__return_true',
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

        $create_record_result = Manual_Payments_Tracker_repository::insert($order_id, $mpesa_ref_no);

        if (!$create_record_result) {
            return new WP_REST_Response([
                'status'  => 'error',
                'message' => \KKWoo_User_Friendly_Messages::get('mpesa_ref_submission_error')
            ], 500);
        }

        $order->update_status('on-hold', \KKWoo_User_Friendly_Messages::get('on_hold_status_update'));
        $order->add_order_note(sprintf(
            'The M-PESA reference number provided by the customer for this order is: %s',
            $mpesa_ref_no
        ));
        $order->update_meta_data('kkwoo_payment_location', '');
        $order->save();

        return new WP_REST_Response([
            'status'  => 'success',
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
