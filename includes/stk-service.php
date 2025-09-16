
<?php

function kkwoo_send_stk_push($phone, $order)
{
    $options     = get_option('woocommerce_kkwoo_settings', []);
    $till_number = isset($options['till_number']) ? $options['till_number'] : null;

    if (empty($till_number)) {
        KKWoo_Logger::log(KKWoo_User_Friendly_Messages::get('till_number_missing'), 'error');
        return new WP_Error('missing_till_number', KKWoo_User_Friendly_Messages::get('generic_customer_message'));
    }

    $access_token = K2_Authorization::get_access_token();
    if (empty($access_token)) {
        KKWoo_Logger::log(KKWoo_User_Friendly_Messages::get('auth_token_error'), 'error');
        return new WP_Error('auth_error', KKWoo_User_Friendly_Messages::get('generic_customer_message'));
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
        'callbackUrl'    =>  rest_url('kkwoo/v1/stk-push-callback'),
        'accessToken'    => $access_token,
    ];

    $gateways = WC()->payment_gateways()->payment_gateways();
    $kkwoo = $gateways['kkwoo'];

    $k2       = K2_Authorization::getClient($kkwoo);
    $stk      = $k2->StkService();
    $response = $stk->initiateIncomingPayment($input);

    return $response;
}
