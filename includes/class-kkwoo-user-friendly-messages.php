<?php

if (! defined('ABSPATH')) {
    exit;
}

class KKWoo_User_Friendly_Messages
{
    public static $user_friendly_messages = [
    'generic_customer_message' => 'We’re unable to process your payment at the moment. Please try again later or contact support.',
    'auth_token_error' => 'Failed to retrieve access token. Ensure that the Client ID, Client Secret and API Key in the Kopo Kopo for WooCommerce settings are up to date.',
    'till_number_missing' => 'The till number is missing. Update the Kopo Kopo for WooCommerce settings',
    'order_not_found' => 'The payment can’t be processed because the order no longer exists.',
    'invalid_order_status' => 'This order is not awaiting payment. If your payment has already been made, please wait while we process it. If it is cancelled, you can place a new order to try again.',
    'on_hold_status_update' => 'Payment initiated through Kopo Kopo for WooCommerce. Waiting to receive funds.',
    'failed_status_update' => 'Payment through Kopo Kopo for WooCommerce failed.',
    'invalid_stk_push_callback_data' => 'The callback data received has an invalid format from the expected format.',
    ];

    /*
    * @param string $key
    */
    public static function get($key): string
    {
        return isset(self::$user_friendly_messages[ $key ]) ? __(self::$user_friendly_messages[ $key ], 'kkwoo') : '';
    }
}
