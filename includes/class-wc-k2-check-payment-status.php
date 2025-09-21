<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_K2_Check_Payment_Status
{
    public function __construct()
    {
        add_filter('woocommerce_order_actions', [$this, 'k2_custom_order_actions']);
        add_action('woocommerce_order_action_wc_k2_check_payment_status_action', [$this, 'wc_k2_check_payment_status_action']);
        add_action('woocommerce_order_details_before_order_table', [$this, 'wc_k2_customer_check_payment_status_action']);
        add_action('admin_notices', [$this, 'show_admin_notice']);
        add_filter('is_protected_meta', function ($protected, $meta_key, $meta_type) {
            if (($meta_key === 'kkwoo_payment_error_msg' || $meta_key === 'kkwoo_payment_location') && $meta_type === 'post') {
                return true; // hide it
            }
            return $protected;
        }, 10, 3);
    }

    /**
    * Function for `woocommerce_order_actions` filter-hook.
    *
    * @param array $actions The available order actions for the order.
    *
    * @return array
    */
    public function k2_custom_order_actions(array $actions): array
    {
        $actions['wc_k2_check_payment_status_action'] = 'Check payment status';
        return $actions;
    }

    /**
    * Function for `woocommerce_order_action_wc_k2_check_payment_status_action` action-hook.
    *
    * @param WC_Order  $order
    *
    * @return void
    */
    public function wc_k2_check_payment_status_action(WC_Order $order): void
    {
        if (! $order instanceof WC_Order) {
            return;
        }

        require_once __DIR__ . '/class-kkwoo-query-incoming-payment-service.php';
        $checkPaymentStatusService = new KKWoo_Query_Incoming_Payment_Status_Service();
        $response = $checkPaymentStatusService->query_incoming_payment_status($order);

        if (is_wp_error($response)) {
            $checkPaymentStatusService->handle_admin_payment_status_error($order, $response);
            return;
        }

        $checkPaymentStatusService->process_admin_payment_status_response($order, $response);
    }

    /**
    * Function for `woocommerce_order_details_before_order_table` action-hook.
    *
    * @param WC_Order  $order
    *
    * @return void
    */
    public function wc_k2_customer_check_payment_status_action(WC_Order $order): void
    {
        if (
            !$order || !$order->has_status('on-hold') || 'kkwoo' !== $order->get_payment_method()
        ) {
            return;
        }

        echo '<div id="kkwoo-flash-messages" class="woocommerce-NoticeGroup"></div>';
        echo '<button id="check-payment-status" class="k2 outline w-fit">' . esc_html('Check payment status') . '</button>';
    }

    public function show_admin_notice(): void
    {
        if ($message = get_transient('kkwoo_admin_notice')) {
            echo '<div class="notice notice-info is-dismissible"><p>' . esc_html($message) . '</p></div>';
            delete_transient('kkwoo_admin_notice');
        }
    }
}
