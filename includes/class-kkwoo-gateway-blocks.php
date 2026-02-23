<?php
/**
 * Kopo Kopo for WooCommerce Gateway Blocks integration class.
 *
 * @package Kopo_Kopo_for_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Provides block-based payment method integration for Kopo Kopo
 * within WooCommerce using the AbstractPaymentMethodType interface.
 */
class KKWoo_Gateway_Blocks extends AbstractPaymentMethodType {

	/**
	 * The gateway instance used for processing payments.
	 *
	 * @var object
	 */
	private $gateway;

	/**
	 * The internal name of the payment method.
	 *
	 * @var string
	 */
	protected $name = 'kkwoo';

	/**
	 * Returns the internal name of the payment method.
	 *
	 * @return string Payment method name.
	 */
	public function get_name(): string {
		return $this->name;
	}

	/**
	 * Initializes the payment method, registering hooks and assets.
	 *
	 * @return void
	 */
	public function initialize(): void {
		$this->settings = get_option( 'woocommerce_my_custom_gateway_settings', array() );
		$gateways       = WC()->payment_gateways()->payment_gateways();
		if ( isset( $gateways['kkwoo'] ) ) {
			$this->gateway = $gateways['kkwoo'];
		} else {
			// If no gateway is available, it is likely that there is an issue in the K2 payment Gateway.
			$this->gateway = null;
			KKWoo_Logger::log( 'KKWoo_Payment_Gateway was not instantiated when KKWoo_Gateway_Blocks initialize() ran.', 'error' );
		}
	}

	/**
	 * Returns the script handles required by the payment method.
	 *
	 * @return array List of script handles.
	 */
	public function get_payment_method_script_handles(): array {
		$asset_path = KKWOO_PLUGIN_PATH . 'build/index.asset.php';

		if ( ! file_exists( $asset_path ) ) {
			KKWoo_Logger::log( 'KKWOO: Asset file not found', 'error' );
			return array();
		}

		$asset      = include $asset_path;
		$handle     = 'kkwoo-gateway-block';
		$script_url = KKWOO_PLUGIN_URL . 'build/index.js';

		wp_register_script(
			$handle,
			$script_url,
			array(
				'wc-blocks-registry',
				'wc-blocks-checkout',
				'wc-settings',
				'wp-element',
				'wp-html-entities',
				'wp-i18n',
			),
			$asset['version'],
			true
		);

		wp_localize_script(
			$handle,
			'kkwoo_data',
			$this->get_payment_method_data()
		);

		return array( $handle );
	}

	/**
	 * Returns the payment method data to be used in the frontend.
	 *
	 * @return array Payment method data.
	 */
	public function get_payment_method_data(): array {
		return array(
			'title'       => ! empty( $this->gateway->title ) ? $this->gateway->title : 'Lipa na M-PESA',
			'description' => ! empty( $this->gateway->method_description ) ? $this->gateway->method_description : '',
			'supports'    => $this->gateway->supports,
		);
	}

	/**
	 * Checks if the payment method is active.
	 *
	 * @return bool True if active, false otherwise.
	 */
	public function is_active(): bool {
		return $this->gateway->is_available();
	}
}
