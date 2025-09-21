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
    'invalid_order_status' => 'This order is not currently marked as awaiting payment. If you’ve already completed your payment, please allow some time for it to be processed. If the order was cancelled, you can create a new one to try again.',
    'on_hold_status_update' => 'Your payment has been initiated. The order will be updated once the payment is processed.',
    'failed_status_update' => 'We couldn’t complete your payment this time. Please try again.',
    'invalid_stk_push_callback_data' => 'The callback data received has an invalid format from the expected format.',
    'location_url_missing' => 'An order appears to be successful, however the location url is missing. Please check the order manually and ensure it reflects the correct status.',
    'payment_processed' => 'Your payment has been processed.',
    'payment_still_on_hold' => 'We’re waiting to receive your payment. Once it’s confirmed, your order will be updated.',
    ];

    /*
    * @param string $key
    */
    public static function get($key): string
    {
        return isset(self::$user_friendly_messages[ $key ]) ? __(self::$user_friendly_messages[ $key ], 'kkwoo') : '';
    }
}
