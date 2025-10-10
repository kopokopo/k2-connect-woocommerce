<?php

if (! defined('ABSPATH')) {
    exit;
}

class KKWoo_User_Friendly_Messages
{
    public static $user_friendly_messages = [
    'generic_unexpected_error_occured' => 'An unexpected error occurred. Please try again or contact the owner of the site.',
    'generic_customer_message' => 'We’re unable to process your request at the moment. Please try again later or contact the owner of the site.',
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
    'mpesa_ref_missing' => 'M-PESA reference number is required.',
    'mpesa_ref_submitted' => 'The payment reference number has been sent. The order will be updated once the payment has been verified.',
    'mpesa_ref_submission_error' => 'An error occurred while submitting the M-PESA reference number. Please try again or contact the owner of the site.',
    'webhook_failed_invalid_paybill_or_till' => 'No valid till or paybill account found to subscribe to webhooks.',
    'webhook_subscription_failed' => 'One or more webhook subscriptions failed. Check logs for details.',
    'subscribed_to_webhooks' => 'Webhook subscriptions created successfully.',
    'webhook_subscription_error' => 'An error occurred while creating webhook subscriptions. Check logs for details.',
    'manual_payment_check_status_unavailable' => 'This order was placed using Manual Payments via Lipa na M-PESA. Please wait while the payment is being verified, or contact the site owner to confirm the payment status.'
    ];

    /*
    * @param string $key
    */
    public static function get($key): string
    {
        return isset(self::$user_friendly_messages[ $key ]) ? __(self::$user_friendly_messages[ $key ], 'kkwoo') : '';
    }
}
