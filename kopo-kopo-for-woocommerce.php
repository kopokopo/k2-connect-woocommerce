<?php
/**
 * Plugin Name: Kopo Kopo for WooCommerce
 * Plugin URI:
 * Description: A Kopo Kopo plugin that integrates seamlessly with your WooCommerce shop, enabling your customers to make secure and convenient payments directly to your Kopo Kopo M-PESA till.
 * Version: 1.0.1
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * Author: Doreen Chemweno
 * Author URI: https://kopokopo.co.ke
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins: woocommerce
 * WC tested up to: 10.4.3
 *
 * @package Kopo_Kopo_for_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/kkwoo-rest-api.php';
require_once __DIR__ . '/includes/class-kkwoo-payment-page.php';
require_once __DIR__ . '/includes/class-kkwoo-logger.php';
require_once __DIR__ . '/includes/class-kkwoo-user-friendly-messages.php';
require_once __DIR__ . '/includes/class-kkwoo-activation-service.php';
require_once __DIR__ . '/includes/class-kkwoo-check-payment-status.php';
require_once __DIR__ . '/includes/kkwoo-authorization-rest-api.php';
require_once __DIR__ . '/includes/kkwoo-webhooks-rest-api.php';
require_once __DIR__ . '/includes/kkwoo-manual-payments-rest-api.php';
require_once __DIR__ . '/includes/class-kkwoo-manual-payments-tracker-repository.php';
require_once __DIR__ . '/includes/class-kkwoo-manual-payment-service.php';
require_once __DIR__ . '/includes/class-kkwoo-request-validator.php';

if ( ! defined( 'KKWOO_PLUGIN_VERSION' ) ) {
	$kkwoo_plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
	define( 'KKWOO_PLUGIN_VERSION', $kkwoo_plugin_data['Version'] );
}

if ( ! defined( 'KKWOO_ASSET_VERSION' ) ) {
	$kkwoo_is_dev        = defined( 'WP_DEBUG' ) && WP_DEBUG; // true for local dev.
	$kkwoo_asset_version = $kkwoo_is_dev ? time() : KKWOO_PLUGIN_VERSION;
	define( 'KKWOO_ASSET_VERSION', $kkwoo_asset_version );
}

if ( ! defined( 'KKWOO_SANDBOX_URL' ) ) {
	define( 'KKWOO_SANDBOX_URL', 'https://sandbox.kopokopo.com' );
}

if ( ! defined( 'KKWOO_PRODUCTION_URL' ) ) {
	define( 'KKWOO_PRODUCTION_URL', 'https://app.kopokopo.com' );
}

if ( ! defined( 'KKWOO_PLUGIN_PATH' ) ) {
	define( 'KKWOO_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'KKWOO_PLUGIN_URL' ) ) {
	define( 'KKWOO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'KKWOO_COUNTRY_CODE' ) ) {
	define( 'KKWOO_COUNTRY_CODE', '+254' );
}

register_activation_hook(
	__FILE__,
	function () {
		KKWoo_Activation_Service::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);

// Register the gateway on plugins_loaded.
add_action( 'plugins_loaded', 'kkwoo_woocommerce_gateway_k2_payment_init', 0 );
/**
 * Initializes Kopo Kopo for WooCommerce payment gateway.
 *
 * Registers this payment gateway class with WooCommerce.
 *
 * @return void
 */
function kkwoo_woocommerce_gateway_k2_payment_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	require_once __DIR__ . '/includes/class-kkwoo-payment-gateway.php';
	require_once __DIR__ . '/includes/class-kkwoo-authorization.php';

	add_filter(
		'woocommerce_payment_gateways',
		function ( $methods ) {
			$methods[] = 'KKWoo_Payment_Gateway';
			return $methods;
		}
	);

	new KKWoo_Check_Payment_Status();
	new KKWoo_Payment_Page();
}

add_action( 'woocommerce_checkout_init', array( 'KKWoo_Authorization', 'maybe_authorize' ) );
add_action( 'woocommerce_view_order', array( 'KKWoo_Authorization', 'maybe_authorize' ), 10, 1 );


add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'kkwoo_wc_settings_link' );
/**
 * Adds a settings link to the WooCommerce plugin actions.
 *
 * @param array $links Existing plugin action links.
 * @return array Modified plugin action links.
 */
function kkwoo_wc_settings_link( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=kkwoo' ) . '">Settings</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

// Add checkout block support - declare that the plugin is compatible with WooCommerce blocks.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);

			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				__FILE__,
				true
			);
		}
	}
);

add_filter( 'woocommerce_currency_symbol', 'kkwoo_custom_currency_symbol', 10, 2 );
/**
 * Filters the currency symbol for supported currencies.
 *
 * Allows customization of the displayed currency symbol,
 * particularly for KES to KSh.
 *
 * @param string $currency_symbol The existing currency symbol.
 * @param string $currency        The currency code.
 * @return string Modified currency symbol.
 */
function kkwoo_custom_currency_symbol( $currency_symbol, $currency ) {
	if ( 'KES' === $currency ) {
		$currency_symbol = 'KSh'; // Use KSh instead of default.
	}
	return $currency_symbol;
}

add_action(
	'woocommerce_init',
	function () {
		$gateways = WC()->payment_gateways()->payment_gateways();
		if ( isset( $gateways['kkwoo'] ) ) {
			$kkwoo = $gateways['kkwoo'];
			$kkwoo->kkwoo_register_gateway_hooks();
		}
	}
);

add_action( 'woocommerce_blocks_loaded', 'kkwoo_register_block_payment_method' );
/**
 * Registers Kopo Kopo for WooCommerce payment method for WooCommerce Blocks.
 *
 * Hooks into WooCommerce Blocks and registers the
 * block-based payment method integration.
 *
 * @return void
 */
function kkwoo_register_block_payment_method() {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		KKWoo_Logger::log( 'AbstractPaymentMethodType not available', 'error' );
		return;
	}

	$blocks_file = __DIR__ . '/includes/class-kkwoo-gateway-blocks.php';
	if ( ! file_exists( $blocks_file ) ) {
		KKWoo_Logger::log( "Blocks file not found at $blocks_file", 'error' );
		return;
	}

	require_once $blocks_file;

	if ( ! class_exists( 'KKWoo_Gateway_Blocks' ) ) {
		KKWoo_Logger::log( 'KKWoo_Gateway_Blocks class not found after including the file', 'error' );
		return;
	}

	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function ( $registry ) {
			$payment_method = new KKWoo_Gateway_Blocks();
			$registry->register( $payment_method );
		}
	);

	require_once __DIR__ . '/includes/class-kkwoo-block-integration.php';
	if ( class_exists( KKWoo_Block_Integration::class ) ) {
		add_action(
			'woocommerce_blocks_checkout_block_registration',
			function ( $integration_registry ) {
				$integration_registry->register( new KKWoo_Block_Integration() );
			}
		);
	}
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( is_checkout() ) {
			wp_enqueue_style(
				'kkwoo-google-font',
				'https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap',
				array(),
				'1.0'
			);

			if ( ! function_exists( 'wc_get_container' ) ) { // Not block checkout.
				wp_enqueue_script(
					'kkwoo-checkout-handler',
					plugin_dir_url( __FILE__ ) . 'assets/js/kkwoo-classic-checkout-handler.js',
					array( 'jquery' ),
					KKWOO_ASSET_VERSION,
					true
				);

				wp_enqueue_style(
					'kkwoo-classic-style',
					plugins_url( 'assets/css/kkwoo-style.css', __FILE__ ),
					array(),
					KKWOO_ASSET_VERSION
				);
			}
		}

		if ( is_wc_endpoint_url( 'view-order' ) || is_wc_endpoint_url( 'order-received' ) ) {
			global $wp;
			if ( is_wc_endpoint_url( 'view-order' ) ) {
				$order_id = absint( $wp->query_vars['view-order'] ?? 0 );
			}
			if ( is_wc_endpoint_url( 'order-received' ) ) {
				$order_id = absint( $wp->query_vars['order-received'] ?? 0 );
			}
			$order          = $order_id ? wc_get_order( $order_id ) : null;
			$localized_data = array(
				'site_url'     => site_url(),
				'nonce'        => wp_create_nonce( 'wp_rest' ),
				'order_key'    => $order->get_order_key(),
				'spinner_icon' => plugins_url( 'images/svg/spinner.svg', __FILE__ ),
			);

			if ( $order ) {
				wp_enqueue_script(
					'kkwoo-order-view-handler',
					plugin_dir_url( __FILE__ ) . 'assets/js/kkwoo-order-view-handler.js',
					array( 'jquery' ),
					KKWOO_ASSET_VERSION,
					true
				);
				wp_localize_script( 'kkwoo-order-view-handler', 'KKWooData', $localized_data );
			}
		}
	}
);

/**
 * Load assets for virtual page - uses template_redirect because wp_enqueue_scripts fires before virtual page URL/params are available
 * Virtual pages process URL rewriting AFTER wp_enqueue_scripts but BEFORE wp_loaded, creating a timing gap
 * Must inject directly to wp_head/wp_footer since wp_enqueue_scripts hook has already fired
 */

add_action(
	'template_redirect',
	function () {
		if ( ! get_query_var( 'lipa_na_mpesa_k2' ) ) {
			return;
		}

		$order_key = sanitize_text_field( get_query_var( 'kkwoo_order_key' ) );
		$order_id  = wc_get_order_id_by_order_key( $order_key );
		$order     = wc_get_order( $order_id );

		$gateways                = WC()->payment_gateways()->payment_gateways();
		$kkwoo                   = $gateways['kkwoo'];
		$enable_manual_payments  = $kkwoo->get_option( 'enable_manual_payments' );
		$manual_payments_till_no = $kkwoo->get_option( 'manual_payments_till_no' );
		$paybill_business_no     = $kkwoo->get_option( 'paybill_business_no' );
		$paybill_account_no      = $kkwoo->get_option( 'paybill_account_no' );

		if ( 'yes' === $enable_manual_payments && ! empty( $manual_payments_till_no ) ) {
			$selected_manual_payment_method = 'till';
		} elseif (
		'yes' === $enable_manual_payments &&
		empty( $manual_payments_till_no ) &&
		! empty( $paybill_business_no ) &&
		! empty( $paybill_account_no )
		) {
			$selected_manual_payment_method = 'paybill';
		} else {
			$selected_manual_payment_method = '';
		}

		if ( ! $order ) {
			status_header( 404 );
			exit( 'Order not found' );
		}

		remove_all_actions( 'wp_head' );
		remove_all_actions( 'wp_footer' );

		$localized_data = array(
			'ajax_url'                       => admin_url( 'admin-ajax.php' ),
			'site_url'                       => site_url(),
			'nonce'                          => wp_create_nonce( 'wp_rest' ),
			'order_key'                      => $order_key,
			'order_status'                   => $order->get_status(),
			'total_amount'                   => $order->get_total(),
			'currency'                       => get_woocommerce_currency_symbol( $order->get_currency() ),
			'store_name'                     => get_bloginfo( 'name' ),
			'selected_manual_payment_method' => $selected_manual_payment_method,
			'order_received_url'             => $order->get_checkout_order_received_url(),
			'this_order_url'                 => $order->get_user_id() ? $order->get_view_order_url() : $order->get_checkout_order_received_url(),
			'plugin_url'                     => plugins_url( '', __FILE__ ),
			'phone_icon'                     => plugins_url( 'images/svg/phone.svg', __FILE__ ),
			'spinner_icon'                   => plugins_url( 'images/svg/spinner.svg', __FILE__ ),
			'k2_logo_with_name_img'          => plugins_url( 'images/svg/k2-logo-with-name.svg', __FILE__ ),
			'kenyan_flag_img'                => plugins_url( 'images/kenyan-flag.png', __FILE__ ),
			'error_circle_icon'              => plugins_url( 'images/svg/alert-circle.svg', __FILE__ ),
			'success_circle_icon'            => plugins_url( 'images/svg/success-circle.svg', __FILE__ ),
			'info_circle_icon'               => plugins_url( 'images/svg/info-circle.svg', __FILE__ ),
		);

		$base_url = plugin_dir_url( __FILE__ ) . 'assets/js/';

		$scripts = array(
			'kkwoo-ui-templates-init'           => 'ui-templates/kkwoo-ui-templates-init.js',
			'kkwoo-mpesa-number-form'           => 'ui-templates/kkwoo-mpesa-number-form.js',
			'kkwoo-pin-instruction'             => 'ui-templates/kkwoo-pin-instruction.js',
			'kkwoo-polling'                     => 'ui-templates/kkwoo-polling.js',
			'kkwoo-payment-success'             => 'ui-templates/kkwoo-payment-success.js',
			'kkwoo-payment-error'               => 'ui-templates/kkwoo-payment-error.js',
			'kkwoo-payment-no-result-yet'       => 'ui-templates/kkwoo-payment-no-result-yet.js',
			'kkwoo-payment-refunded'            => 'ui-templates/kkwoo-payment-refunded.js',
			'kkwoo-manual-payment-instructions' => 'ui-templates/kkwoo-manual-payment-instructions.js',
			'kkwoo-polling-manager'             => 'kkwoo-polling-manager.js',
			'kkwoo-k2-validations'              => 'kkwoo-validations.js',
			'kkwoo-payment-flow-handler'        => 'kkwoo-payment-flow-handler.js',
		);

		wp_enqueue_script( 'jquery' );
		foreach ( $scripts as $handle => $file ) {
			wp_enqueue_script(
				$handle,
				$base_url . $file,
				array( 'jquery' ),
				KKWOO_ASSET_VERSION,
				true
			);
		}

		wp_enqueue_style(
			'kkwoo-google-font',
			'https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap',
			array(),
			'1.0'
		);

		wp_enqueue_style(
			'kkwoo-classic-style',
			plugins_url( 'assets/css/kkwoo-style.css', __FILE__ ),
			array(),
			KKWOO_ASSET_VERSION
		);

		wp_localize_script( 'kkwoo-payment-flow-handler', 'KKWooData', $localized_data );

		status_header( 200 );
		header( 'Content-Type: text/html; charset=utf-8' );

		?>
	<!DOCTYPE html>
	<html lang="en">
		<head>
			<meta charset="utf-8">
			<title>Lipa na M-PESA</title>
			<meta name="viewport" content="width=device-width, initial-scale=1.0">
			<?php
				wp_print_styles( 'kkwoo-google-font' );
			wp_print_styles( 'kkwoo-classic-style' );
			?>
		</head>
		<body>

		<main class="wp-block-group">
			<div class="k2 modal-overlay" style="display: none">
				<div class="modal-body">
					<div class="modal-content"></div>

					<?php if ( 'yes' === $kkwoo->get_option( 'show_credit_text' ) ) : ?>
						<div class="modal-footer">
							Powered by
							<img src="<?php echo esc_url( plugins_url( 'images/svg/k2-logo-with-name.svg', __FILE__ ) ); ?>">
						</div>
					<?php endif; ?>
			</div>
			<p class='switch-to-manual-payments'>Having trouble? Pay via 
				<button id='switch-to-manual-payments' class="link">M-PESA Buy Goods</button>
			</p>
		</main>

		<?php
		wp_print_scripts( 'jquery' );
		foreach ( $scripts as $handle => $file ) {
			wp_print_scripts( $handle );
		}
		?>
	</body>
	</html>
		<?php
		exit;
	}
);
