<?php
/**
 * Adds payment status checking functionality.
 *
 * Registers and displays a "Check Payment Status" action
 * for both WooCommerce admin order view pages and
 * customer-facing order pages.
 *
 * @package Kopo_Kopo_for_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles check payment status actions for WooCommerce orders.
 */
class KKWoo_Check_Payment_Status {

	/**
	 * Initializes the check payment status actions and filters.
	 *
	 * Hooks into WooCommerce and WordPress actions/filters to:
	 * - Add custom order actions
	 * - Handle order action callbacks
	 * - Show payment status checks for customers
	 * - Display admin notices
	 * - Protect sensitive meta fields from display
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'woocommerce_order_actions', array( $this, 'custom_order_actions' ) );
		add_action( 'woocommerce_order_action_check_payment_status_action', array( $this, 'check_payment_status_action' ) );
		add_action( 'woocommerce_order_details_before_order_table', array( $this, 'customer_check_payment_status_action' ) );
		add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );
		add_filter(
			'is_protected_meta',
			function ( $current_protected, $meta_key, $meta_type ) {
				if ( ( 'kkwoo_payment_error_msg' === $meta_key || 'kkwoo_payment_location' === $meta_key ) && 'post' === $meta_type ) {
					return true; // hide it.
				}
				return $current_protected;
			},
			10,
			3
		);
	}

	/**
	 * Function for `woocommerce_order_actions` filter-hook.
	 *
	 * @param array $actions The available order actions for the order.
	 *
	 * @return array
	 */
	public function custom_order_actions( array $actions ): array {
		$actions['check_payment_status_action'] = 'Check payment status';
		return $actions;
	}

	/**
	 * Function for `woocommerce_order_action_check_payment_status_action` action-hook.
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @return WP_REST_Response|null
	 */
	public function check_payment_status_action( WC_Order $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		require_once __DIR__ . '/class-kkwoo-query-incoming-payment-status-service.php';
		$check_payment_status_service = new KKWoo_Query_Incoming_Payment_Status_Service();
		$response                     = $check_payment_status_service->query_incoming_payment_status( $order );

		if ( is_wp_error( $response ) ) {
			$check_payment_status_service->handle_admin_payment_status_error( $order, $response );
			return;
		}

		if ( $response instanceof WP_REST_Response ) {
			return $response;
		}

		$check_payment_status_service->process_admin_payment_status_response( $order, $response );
	}

	/**
	 * Function for `woocommerce_order_details_before_order_table` action-hook.
	 *
	 * @param WC_Order $order WooCommerce order object.
	 * @return void
	 */
	public function customer_check_payment_status_action( WC_Order $order ): void {
		if (
			! $order || ! $order->has_status( 'on-hold' ) || 'kkwoo' !== $order->get_payment_method()
		) {
			return;
		}

		echo '<div id="kkwoo-flash-messages" class="woocommerce-NoticeGroup"></div>';
		echo '<button id="check-payment-status" class="k2 outline w-fit">' . esc_html( 'Check payment status' ) . '</button>';
	}

	/**
	 * Determines whether the current screen is the WooCommerce
	 * admin order view page.
	 *
	 * @return bool True if viewing the admin order page, false otherwise.
	 */
	public function is_admin_order_view_page(): bool {
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		if ( 'shop_order' !== $screen->post_type ) {
			return false;
		}

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'edit';
		if ( 'edit' !== $action ) {
			return false;
		}

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : ( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 );
		if ( ! $order_id ) {
			return false;
		}

		return true;
	}

	/**
	 * Displays an admin notice related to payment status.
	 *
	 * Outputs a notice in the WordPress admin order page
	 * after a payment status check is performed.
	 *
	 * @return void
	 */
	public function show_admin_notice(): void {
		// return early if not in desired page.
		if ( ! $this->is_admin_order_view_page() ) {
			return;
		}

		$message = get_transient( 'kkwoo_admin_notice' );
		$error   = get_transient( 'kkwoo_admin_error' );

		if ( $message ) {
			echo '<div class="notice notice-info is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
			delete_transient( 'kkwoo_admin_notice' );
		}

		if ( $error ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error ) . '</p></div>';
			delete_transient( 'kkwoo_admin_error' );
		}
	}
}
