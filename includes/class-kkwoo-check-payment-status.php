<?php

if (!defined('ABSPATH')) {
    exit;
}

class KKWoo_Check_Payment_Status
{
    public function __construct()
    {
        add_filter('woocommerce_order_actions', [$this, 'custom_order_actions']);
        add_action('woocommerce_order_action_check_payment_status_action', [$this, 'check_payment_status_action']);
        add_action('woocommerce_order_details_before_order_table', [$this, 'customer_check_payment_status_action']);
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
    public function custom_order_actions(array $actions): array
    {
        $actions['check_payment_status_action'] = 'Check payment status';
        return $actions;
    }

    /**
    * Function for `woocommerce_order_action_check_payment_status_action` action-hook.
    *
    * @param WC_Order  $order
    *
    * @return WP_REST_Response|null
    */
    public function check_payment_status_action(WC_Order $order)
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

        if ($response instanceof WP_REST_Response) {
            return $response;
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
    public function customer_check_payment_status_action(WC_Order $order): void
    {
        if (
            !$order || !$order->has_status('on-hold') || 'kkwoo' !== $order->get_payment_method()
        ) {
            return;
        }

        echo '<div id="kkwoo-flash-messages" class="woocommerce-NoticeGroup"></div>';
        echo '<button id="check-payment-status" class="k2 outline w-fit">' . esc_html('Check payment status') . '</button>';
    }

    public function is_admin_order_view_page(): bool
    {
        if (!is_admin() || !function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();

        if ($screen->post_type !== 'shop_order') {
            return false;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : 'edit';
        if ($action !== 'edit') {
            return false;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order_id = isset($_GET['post']) ? absint($_GET['post']) : (isset($_GET['id']) ? absint($_GET['id']) : 0);
        if (!$order_id) {
            return false;
        }

        return true;
    }


    public function show_admin_notice(): void
    {
        // return early if not in desired page
        if (!$this->is_admin_order_view_page()) {
            return;
        }

        if ($message = get_transient('kkwoo_admin_notice')) {
            echo '<div class="notice notice-info is-dismissible"><p>' . esc_html($message) . '</p></div>';
            delete_transient('kkwoo_admin_notice');
        }

        if ($error = get_transient('kkwoo_admin_error')) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error) . '</p></div>';
            delete_transient('kkwoo_admin_error');
        }
    }
}
