
<?php

function kkwoo_send_stk_push($phone, $order_id)
{
    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_Error('invalid_order', 'Order not found.');
    }

    $options     = get_option('woocommerce_kkwoo_settings', []);
    $till_number = isset($options['till_number']) ? $options['till_number'] : null;

    if (empty($till_number)) {
        return new WP_Error('missing_till_number', 'Till number not configured.');
    }

    $access_token = K2_Authorization::get_access_token();
    if (empty($access_token)) {
        return new WP_Error('auth_error', 'Failed to retrieve access token.');
    }

    $input = [
        'paymentChannel' => 'M-PESA STK Push',
        'tillNumber'     => $till_number,
        'firstName'      => $order->get_billing_first_name(),
        'lastName'       => $order->get_billing_last_name(),
        'phoneNumber'    => KKWOO_COUNTRY_CODE.$phone,
        'currency'       => get_option('woocommerce_currency'),
        'amount'         => $order->get_total(),
        'email'          => $order->get_billing_email(),
        'metadata' => [
            'customer_id' => $order->get_customer_id(),
            'reference'   => $order->get_order_key(),
            'notes'       => 'Payment for invoice ' . $order->get_id(),
        ],
        'callbackUrl'    => rest_url('kkwoo/v1/stk-push-callback'),
        'accessToken'    => $access_token,
    ];

    $gateways = WC()->payment_gateways()->payment_gateways();
    $kkwoo = $gateways['kkwoo'];

    $k2       = K2_Authorization::getClient($kkwoo);
    $stk      = $k2->StkService();
    $response = $stk->initiateIncomingPayment($input);

    return $response;
}
