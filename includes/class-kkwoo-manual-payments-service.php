<?php

namespace KKWoo\ManualPayments;

if (!defined('ABSPATH')) {
    exit;
}

use KKWoo_Logger;
use WC_Order;

class Manual_Payment_Service
{
    public function complete(WC_Order $order, array $webhook_payload)
    {
        if (!$order->has_status('processing') && !$order->has_status('completed')) {
            KKWoo_Logger::log($webhook_payload);
            $amount_received = floatval($webhook_payload['amount']);
            $order_total = floatval($order->get_total());
            $currency_symbol = get_woocommerce_currency_symbol($order->get_currency());

            if ($amount_received >= $order_total) {
                $order->payment_complete($webhook_payload['id']);
                $order->add_order_note(sprintf(
                    'Manual Lipa na M-PESA payment completed. Amount: %s %s.',
                    $currency_symbol,
                    $webhook_payload['amount']
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
}
