<?php

if (! defined('ABSPATH')) {
    exit;
}

class KKWoo_Query_Incoming_Payment_Status_Service
{
    public function query_incoming_payment_status(WC_Order $order)
    {
        $location_url = $order->get_meta('kkwoo_payment_location');
        if (empty($location_url)) {
            if (!$order->has_status('pending')) {
                return new WP_REST_Response([
                    'status'  => 'success',
                    'message' => KKWoo_User_Friendly_Messages::get('manual_payment_check_status_unavailable'),
                ], 200);
            }
            KKWoo_Logger::log(KKWoo_User_Friendly_Messages::get('location_url_missing'), 'error');
            return new WP_Error('missing_location_url', KKWoo_User_Friendly_Messages::get('generic_customer_message'));
        }

        $access_token = KKWoo_Authorization::get_access_token();
        if (empty($access_token)) {
            KKWoo_Logger::log(KKWoo_User_Friendly_Messages::get('auth_token_error'), 'error');
            return new WP_Error('auth_error', KKWoo_User_Friendly_Messages::get('generic_customer_message'));
        }

        $input = [
            'location'     => $location_url,
            'accessToken'  => $access_token,
        ];

        $gateways = WC()->payment_gateways()->payment_gateways();
        $kkwoo = $gateways['kkwoo'];

        $k2       = KKWoo_Authorization::getClient($kkwoo);
        $stk      = $k2->StkService();
        $response = $stk->getStatus($input);

        return $response;
    }

    /**
    * Function to handle responses received after querying for status ( Used by admin and customer ).
    *
    * @param WC_Order  $order
    * @param array $response
    *
    * @return WP_REST_Response
    */
    public function process_payment_status_response(WC_Order $order, array $response): WP_REST_Response
    {
        $data = $response['data'] ?? [];

        if (!isset($response['status']) || $response['status'] !== 'success') {
            return new WP_REST_Response([
                'status' => 'error',
                'data'   => $response,
            ], 500);
        }

        switch ($data['status']) {
            case 'Success':
                self::process_success_response($order, $data);

                return new WP_REST_Response([
                    'status'  => 'success',
                    'message' => KKWoo_User_Friendly_Messages::get('payment_processed'),
                ], 200);

            case 'Pending':
                self::process_pending_response($order);

                return new WP_REST_Response([
                    'status'  => 'success',
                    'message' => KKWoo_User_Friendly_Messages::get('payment_still_on_hold'),
                ], 200);

            case 'Received':
                self::process_pending_response($order);

                return new WP_REST_Response([
                    'status'  => 'success',
                    'message' => KKWoo_User_Friendly_Messages::get('payment_still_on_hold'),
                ], 200);

            case 'Failed':
                self::process_failed_response($order, $data['errors']);

                return new WP_REST_Response([
                    'status'  => 'error',
                    'message' => $data['errors'],
                ], 200);

            default:
                return new WP_REST_Response([
                    'status' => 'error',
                    'data'   => sprintf('Unknown payment status: %s', $data['status']),
                ], 400);
        }
    }

    /**
    * Function to handle error responses after querying for status ( Used by admin only ).
    *
    * @param WC_Order  $order
    * @param WP_Error $error
    *
    * @return void
    */
    public function handle_admin_payment_status_error(WC_Order $order, WP_Error $error): void
    {
        $message = $error->get_error_message();

        self::process_failed_response($order, $message);
        KKWoo_Logger::log($message, 'error');

        // Store message in transient so we can show it in admin notice
        set_transient('kkwoo_admin_error', $message, 30);
    }

    /**
    * Function to complete payment after querying for status ( Used by admin only ).
    *
    * @param WC_Order  $order
    * @param WP_Error $response
    *
    * @return void
    */
    public function process_admin_payment_status_response(WC_Order $order, array $response): void
    {
        $data = $response['data'] ?? [];

        switch ($data['status']) {
            case 'Success':
                self::process_success_response($order, $data);

                $message = KKWoo_User_Friendly_Messages::get('payment_processed');
                break;

            case 'Pending':
                self::process_pending_response($order);

                $message = KKWoo_User_Friendly_Messages::get('payment_still_on_hold');
                break;

            case 'Received':
                self::process_pending_response($order);

                $message = KKWoo_User_Friendly_Messages::get('payment_still_on_hold');
                break;

            case 'Failed':
                self::process_failed_response($order, $data['errors']);

                $error = $data['errors'];
                break;

            default:
                $error = sprintf('Unknown payment status: %s', $data['status']);
        }

        // Show in WooCommerce admin as a notice
        if (isset($error) && $error !== '') {
            set_transient('kkwoo_admin_error', $error, 30);
        } else {
            set_transient('kkwoo_admin_notice', $message, 30);
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
    private function process_success_response(WC_Order $order, array $data): void
    {
        if (!$order->has_status('processing') && !$order->has_status('completed')) {
            $order->payment_complete($data['id']);
            $order->add_order_note(sprintf(
                'Payment received via Kopo Kopo for WooCommerce. Amount: %s %s.',
                get_woocommerce_currency_symbol($order->get_currency()),
                $data['amount']
            ));
            $order->update_meta_data('kkwoo_payment_error_msg', '');
            $order->save();
        }
    }

    /**
    * Function to process an order once the payment has been received with status 'Pending'.
    *
    * @param WC_Order  $order
    *
    * @return void
    */
    private function process_pending_response(WC_Order $order): void
    {
        $order->update_meta_data('kkwoo_payment_error_msg', '');
        $order->save();
    }

    /**
    * Function to process an order once the payment has been received with status 'Pending'.
    *
    * @param WC_Order  $order
    * @param string $errors
    *
    * @return void
    */
    private function process_failed_response(WC_Order $order, string $errors): void
    {
        if (!$order->has_status('failed')) {
            $order->update_status('failed', KKWoo_User_Friendly_Messages::get('failed_status_update'));
            $order->update_meta_data('kkwoo_payment_error_msg', $errors);
            $order->save();
        }
    }

}
