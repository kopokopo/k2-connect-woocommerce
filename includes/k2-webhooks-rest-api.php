<?php

namespace KKWoo\Webhooks;

if (!defined('ABSPATH')) {
    exit;
}

use KKWoo_Logger;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WC_Order;
use KKWoo\Database\Manual_Payments_Tracker_repository;

add_action('rest_api_init', function () {
    register_rest_route('kkwoo/v1', '/create-webhook-subscriptions', [
        'methods'  => 'POST',
        'callback' => __NAMESPACE__ . '\\handle_create_webhook_subscriptions',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        },
    ]);

    register_rest_route('kkwoo/v1', '/buygoods-transaction-received', [
        'methods'  => 'POST',
        'callback' => __NAMESPACE__ . '\\handle_buygoods_transaction_received',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('kkwoo/v1', '/b2b-transaction-received', [
        'methods'  => 'POST',
        'callback' => __NAMESPACE__ . '\\handle_b2b_transaction_received',
        'permission_callback' => '__return_true',
    ]);
});

function create_webhook_subscription($event_type, $till = null): bool
{
    try {
        $access_token = \K2_Authorization::get_access_token();
        if (empty($access_token)) {
            \KKWoo_Logger::log(\KKWoo_User_Friendly_Messages::get('auth_token_error'), 'error');
            return false;
        }

        $gateways = \WC()->payment_gateways()->payment_gateways();
        $kkwoo = $gateways['kkwoo'];

        $k2 = \K2_Authorization::getClient($kkwoo);
        $webhooks = $k2->Webhooks();

        $response = $webhooks->subscribe([
            'eventType'      => $event_type,
            'url'            => rest_url('kkwoo/v1/' . str_replace("_", "-", $event_type)),
            'scope'          => $till ? 'till' : 'company',
            'scopeReference' => $till ?? '',
            'accessToken'    => $access_token,
        ]);

        if (!isset($response['status']) || $response['status'] !== 'success') {
            \KKWoo_Logger::log(
                'Webhook subscription failed for event: ' . $event_type . '. Payload received: ' . json_encode($response),
                'error'
            );
            return false;
        }

        return true;

    } catch (\Throwable $e) {
        \KKWoo_Logger::log(
            'Error while creating webhook subscription for event "' . $event_type . '": ' . $e->getMessage(),
            'error'
        );
        return false;
    }
}

function handle_create_webhook_subscriptions()
{
    try {
        $gateways = \WC()->payment_gateways()->payment_gateways();
        $kkwoo = $gateways['kkwoo'] ?? null;

        if (!$kkwoo) {
            return new WP_Error(
                'missing_gateway',
                'Kopo Kopo gateway not found.',
                ['status' => 400]
            );
        }

        $till_no = $kkwoo->get_option('manual_payments_till_no');
        $paybill_business_no = $kkwoo->get_option('paybill_business_no');
        $paybill_account_no = $kkwoo->get_option('paybill_account_no');

        $success = true;

        if (!empty($till_no) && empty($paybill_account_no)) {
            $success &= create_webhook_subscription('buygoods_transaction_received', $till_no);
            $success &= create_webhook_subscription('b2b_transaction_received', $till_no);
        } elseif (!empty($paybill_account_no)) {
            $success &= create_webhook_subscription('buygoods_transaction_received');
            $success &= create_webhook_subscription('b2b_transaction_received');
        } else {
            return new WP_Error(
                'invalid_paybill_or_till',
                \KKWoo_User_Friendly_Messages::get('webhook_failed_invalid_paybill_or_till'),
                ['status' => 400]
            );
        }

        if (!$success) {
            return new WP_Error(
                'webhook_subscription_failed',
                \KKWoo_User_Friendly_Messages::get('webhook_subscription_failed'),
                ['status' => 500]
            );
        }

        return rest_ensure_response([
            'success' => true,
            'message' => \KKWoo_User_Friendly_Messages::get('subscribed_to_webhooks')
        ]);

    } catch (\Throwable $e) {
        \KKWoo_Logger::log('Webhook subscription error: ' . $e->getMessage(), 'error');
        return new WP_Error(
            'webhook_subscription_error',
            \KKWoo_User_Friendly_Messages::get('webhook_subscription_error'),
            ['status' => 500]
        );
    }
}

function validate_webhook_request(WP_REST_Request $request): array
{
    $gateways = \WC()->payment_gateways()->payment_gateways();
    $kkwoo = $gateways['kkwoo'];

    $k2 = \K2_Authorization::getClient($kkwoo);
    $webhooks = $k2->Webhooks();

    $webhook_payload = $request->get_body();

    $response = $webhooks->webhookHandler($webhook_payload, $_SERVER['HTTP_X_KOPOKOPO_SIGNATURE']);
    return $response;
}


function handle_buygoods_transaction_received(WP_REST_Request $request)
{
    process_request($request);
    return new \WP_REST_Response(['message' => 'Buygoods transaction webhook received'], 200);
}


function handle_b2b_transaction_received(WP_REST_Request $request)
{
    process_request($request);
    return new \WP_REST_Response(['message' => 'B2B transaction webhook received'], 200);
}

function process_request(WP_REST_Request $request)
{
    $validated_response = validate_webhook_request($request);
    KKWoo_Logger::log($validated_response);

    $status    = $validated_response['status'] ?? '';
    $data      = $validated_response['data'] ?? [];
    $reference = $data['reference'] ?? '';

    if ('success' !== $status) {
        KKWoo_Logger::log(
            'Webhook validation failed for the following request payload: ' . $request->get_body(),
            'error'
        );
        return new \WP_REST_Response(['message' => 'Invalid webhook received'], 400);
    }

    if (empty($reference)) {
        KKWoo_Logger::log('Missing reference in webhook payload', 'warning');
        return new \WP_REST_Response(['message' => 'Missing reference'], 400);
    }

    if ('Received' === $data['status'] || 'Complete' === $data['status']) {
        $payment_tracker = Manual_Payments_Tracker_repository::get_by_mpesa_ref($reference);

        if ($payment_tracker && isset($payment_tracker['id'])) {
            Manual_Payments_Tracker_repository::update_by_id(
                $payment_tracker['id'],
                ['webhook_payload' => json_encode($validated_response)]
            );

            $order = wc_get_order($payment_tracker['order_id']);
            if ($order) {
                process_success_response($order, $data);
            } else {
                KKWoo_Logger::log("Order not found for ID: {$payment_tracker['order_id']}", 'error');
                return new \WP_REST_Response(['message' => 'Order not found'], 404);
            }
        } else {
            KKWoo_Logger::log("Payment tracker not found for reference: {$reference}", 'warning');
        }
    }
}

/**
* Function to process an order once the payment has been received with status 'Success'.
*
* @param WC_Order  $order
* @param array $data
*
* @return void
*/
function process_success_response(WC_Order $order, array $data): void
{
    if (!$order->has_status('processing') && !$order->has_status('completed')) {
        $amount_received = floatval($data['amount']);
        $order_total = floatval($order->get_total());
        $currency_symbol = get_woocommerce_currency_symbol($order->get_currency());

        if ($amount_received >= $order_total) {
            $order->payment_complete($data['id']);
            $order->add_order_note(sprintf(
                'Manual Lipa na M-PESA payment completed. Amount: %s %s. Phone: %s',
                $currency_symbol,
                $data['amount'],
                $data['senderPhoneNumber'] ?? $data['sendingTill']
            ));
        } else {
            $order->add_order_note(sprintf(
                'Received payment of %s%.2f, which is less than the order total of %s%.2f.',
                $currency_symbol,
                $amount_received,
                $currency_symbol,
                $order_total
            ));
        }

        $order->update_meta_data('kkwoo_payment_error_msg', '');
        $order->save();
    }
}
