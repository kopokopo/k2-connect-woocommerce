<?php
/**
 * Kopo Kopo for WooCommerce Payment Gateway class.
 *
 * Handles WooCommerce payment gateway integration for Kopo Kopo for WooCommerce,
 * including configuration, payment processing, admin notices, and
 * manual payment support.
 *
 * @package Kopo_Kopo_for_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extends WooCommerce payment gateway to support Kopo Kopo for WooCommerce.
 */
class KKWoo_Payment_Gateway extends WC_Payment_Gateway {

	/**
	 * The K2 Connect API client ID.
	 *
	 * @var string
	 */
	protected string $client_id;

	/**
	 * The K2 Connect API client secret.
	 *
	 * @var string
	 */
	protected string $client_secret;

	/**
	 * The K2 Connect API key.
	 *
	 * @var string
	 */
	protected string $api_key;

	/**
	 * The environment in use (e.g., 'sandbox' or 'production').
	 *
	 * @var string
	 */
	protected string $environment;

	/**
	 * Flag to enable manual payments ('yes' or 'no').
	 *
	 * @var string
	 */
	protected string $enable_manual_payments;

	/**
	 * Till number used for manual payments.
	 *
	 * @var string
	 */
	protected string $manual_payments_till_no;

	/**
	 * Paybill business number used for manual payments.
	 *
	 * @var string
	 */
	protected string $paybill_business_no;

	/**
	 * Paybill account number used for manual payments.
	 *
	 * @var string
	 */
	protected string $paybill_account_no;

	/**
	 * Initializes gateway settings, hooks, and filters.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->id                 = 'kkwoo';
		$this->icon               = KKWOO_PLUGIN_URL . 'images/svg/k2-logo.svg';
		$this->has_fields         = false;
		$this->method_title       = 'Kopo Kopo for WooCommerce';
		$this->method_description = 'Allows payments with Kopo Kopo for WooCommerce.';

		$this->supports = array( 'products' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Get settings.
		$this->enabled                 = $this->get_option( 'enabled' );
		$this->title                   = $this->get_option( 'title' );
		$this->description             = $this->get_option( 'description' );
		$this->client_id               = $this->get_option( 'client_id' );
		$this->client_secret           = $this->get_option( 'client_secret' );
		$this->api_key                 = $this->get_option( 'api_key' );
		$this->environment             = $this->get_option( 'environment' );
		$this->enable_manual_payments  = $this->get_option( 'enable_manual_payments' );
		$this->manual_payments_till_no = $this->get_option( 'manual_payments_till_no' );
		$this->paybill_business_no     = $this->get_option( 'paybill_business_no' );
		$this->paybill_account_no      = $this->get_option( 'paybill_account_no' );
	}

	/**
	 * Initializes form fields for the gateway settings page.
	 *
	 * @return void
	 */
	public function init_form_fields(): void {
		$this->form_fields = array(
			'enabled'                      => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => 'Enable Kopo Kopo for WooCommerce',
				'default' => 'no',
			),
			'title'                        => array(
				'title'             => 'Title',
				'type'              => 'text',
				'description'       => 'Title shown to customers at checkout.',
				'default'           => 'Lipa na M-PESA',
				'custom_attributes' => array(
					'readonly' => 'readonly',
				),
				'css'               => 'background-color: #f5f5f5; color: #666;',
			),
			'description'                  => array(
				'title'             => 'Description',
				'type'              => 'text',
				'description'       => 'Description shown to customers at checkout.',
				'default'           => 'Click "Proceed to Lipa na M-PESA" below to pay with M-PESA',
				'custom_attributes' => array(
					'readonly' => 'readonly',
				),
				'css'               => 'background-color: #f5f5f5; color: #666;',
			),
			'till_number'                  => array(
				'title'       => 'Till Number',
				'type'        => 'text',
				'description' => 'The till number to receive payments via STK Push.',
				'default'     => '',
			),
			'client_id'                    => array(
				'title'       => 'Client ID',
				'type'        => 'text',
				'description' => 'Your Kopo Kopo Client ID.',
				'default'     => '',
			),
			'client_secret'                => array(
				'title'       => 'Client Secret',
				'type'        => 'password',
				'description' => 'Your Kopo Kopo Client Secret.',
				'default'     => '',
			),
			'api_key'                      => array(
				'title'       => 'API Key',
				'type'        => 'password',
				'description' => 'Your Kopokopo API Key.',
				'default'     => '',
			),
			'environment'                  => array(
				'title'       => 'Environment',
				'type'        => 'select',
				'description' => 'Choose the Kopo Kopo environment.',
				'default'     => 'sandbox',
				'options'     => array(
					'sandbox'    => 'Sandbox (Test)',
					'production' => 'Production (Live)',
				),
			),
			'show_credit_text'             => array(
				'title'       => 'Display Kopo Kopo Branding',
				'type'        => 'checkbox',
				'label'       => 'Display ‘Powered by Kopo Kopo’ branding text on public Lipa na M-PESA checkout and payment pages',
				'description' => 'When enabled, a small “Powered by Kopo Kopo” text will be displayed on the Lipa na M-PESA payment-related pages. This text is disabled by default and can be turned on/off at any time.',
				'default'     => 'no',
				'desc_tip'    => true,
			),
			'manual_payment_option_notice' => array(
				'title'       => 'Manual Payments Settings',
				'type'        => 'title',
				'description' => '⚠️ Provide a Lipa na M-PESA till number or Paybill details. If you provide both, the till option will take priority.',
			),
			'enable_manual_payments'       => array(
				'title'       => 'Enable/Disable Manual Payments',
				'label'       => 'Enable manual payments',
				'description' => 'Provide a Lipa na M-PESA till number or Paybill details as a fallback to STK Push.',
				'type'        => 'checkbox',
				'default'     => 'no',
			),
			'manual_payment_method'        => array(
				'title'       => 'Manual Payment Method',
				'type'        => 'select',
				'description' => 'Choose either Till or Paybill.',
				'default'     => 'till',
				'options'     => array(
					'till'    => 'Till',
					'paybill' => 'Paybill',
				),
			),
			'manual_payments_till_no'      => array(
				'title'       => 'Till Number (optional)',
				'type'        => 'text',
				'description' => 'Enter the Lipa na M-PESA till number to receive payments if STK Push fails.',
				'default'     => '',
			),
			'paybill_business_no'          => array(
				'title'       => 'Paybill Business Number (optional)',
				'description' => 'Enter the Paybill number to receive payments if STK Push fails.',
				'default'     => '',
				'type'        => 'text',
			),
			'paybill_account_no'           => array(
				'title'       => 'Paybill Account Number (optional)',
				'description' => 'Provide the Paybill account number.',
				'default'     => '',
				'type'        => 'text',
			),
		);
	}

	/**
	 * Registers hooks specific to the gateway.
	 *
	 * @return void
	 */
	public function kkwoo_register_gateway_hooks(): void {
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action(
			'woocommerce_update_options_payment_gateways_' . $this->id,
			array( $this, 'after_settings_updated' )
		);

		add_action( 'admin_notices', array( $this, 'admin_missing_settings_notice' ) );
		add_action( 'admin_notices', array( $this, 'admin_currency_warning' ) );
		add_action( 'admin_notices', array( $this, 'admin_permalinks_disabled_warning' ) );

		add_action( 'woocommerce_after_settings_checkout', array( $this, 'render_custom_payment_gateway_settings_buttons' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'kkwoo_enqueue_custom_settings_section_assets' ) );
	}

	/**
	 * Updates the order to Pending for the given order ID.
	 *
	 * This method executes when a customer selects Lipa Na M-Pesa
	 * as the payment method in the checkout page and
	 * proceeds to the Lipa Na M-Pesa flow.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return array Containing Kopo Kopo for WooCommerce payment page URL.
	 */
	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );
		$order->update_status( 'pending', 'Waiting for K2 payment...' );

		return array(
			'result'   => 'success',
			'redirect' => home_url(
				add_query_arg(
					array(
						'kkwoo_order_key' => $order->get_order_key(),
					),
					'/lipa-na-mpesa-k2/'
				)
			),
		);
	}

	/**
	 * Displays admin notice for missing required settings.
	 *
	 * @return void
	 */
	public function admin_missing_settings_notice(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = sanitize_text_field( wp_unslash( $_GET['section'] ?? '' ) );
		if ( $section !== $this->id ) {
			return;
		}

		if ( 'yes' === $this->get_option( 'enabled' ) && ! $this->is_configured() ) {
			echo '<div class="notice notice-error"><p>';
			echo esc_html( 'Kopo Kopo for WooCommerce is enabled but not fully configured. Click ' );
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=kkwoo' ) ) . '">here</a>';
			echo esc_html( ' to complete the setup.' );
			echo '</p></div>';
		}

		if ( 'yes' === $this->settings['enable_manual_payments'] && ! $this->is_configured_for_manual_payments() ) {
			echo '<div class="notice notice-error"><p>';
			echo esc_html( 'Kopo Kopo for WooCommerce is enabled to receive manual payments but not fully configured with Paybill or Till. Click ' );
			echo '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=kkwoo' ) ) . '">here</a>';
			echo esc_html( ' to complete the setup.' );
			echo '</p></div>';
		}
	}

	/**
	 * Checks if the current request is in admin.
	 *
	 * @return bool true if in admin, false otherwise.
	 */
	public function wp_is_admin(): bool {
		return is_admin();
	}

	/**
	 * Returns the configured currency for the gateway.
	 *
	 * @return string Currency code (e.g., 'KES').
	 */
	public function wp_get_currency(): string {
		return get_woocommerce_currency();
	}

	/**
	 * Shows a warning in admin if currency is not supported.
	 *
	 * @return void
	 */
	public function admin_currency_warning(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = sanitize_text_field( wp_unslash( $_GET['section'] ?? '' ) );
		if ( $section !== $this->id ) {
			return;
		}

		if ( $this->wp_is_admin() && 'KES' !== get_woocommerce_currency() && 'yes' === $this->enabled ) {
			echo '<div class="notice notice-warning"><p>';
			echo esc_html( 'Kopo Kopo for WooCommerce requires your store currency to be Kenyan Shillings (KES). Please update it under WooCommerce → Settings → General.' );
			echo '</p></div>';
		}
	}

	/**
	 * Shows a warning im admin if permalinks are not enabled.
	 * 
	 * Pretty permalinks are required because the plugin registers query vars
	 * for custom endpoints (like 'lipa_na_mpesa_k2') via add_rewrite_rule(). 
	 * Without pretty permalinks, these endpoints cannot be recognized and will 404.
	 *
	 * @return void
	 */
	public function admin_permalinks_disabled_warning(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = sanitize_text_field( wp_unslash( $_GET['section'] ?? '' ) );
		if ( $section !== $this->id ) {
			return;
		}

    	// Only show notice if plugin is enabled and permalinks are disabled.
    	if ( $this->wp_is_admin() && '' === get_option('permalink_structure') && 'yes' === $this->enabled ) {
        	echo '<div class="notice notice-warning is-dismissible"><p>';
        	echo esc_html( 'Kopo Kopo for WooCommerce requires pretty permalinks to route its endpoints correctly. Please enable them in Settings → Permalinks.' );
        	echo ' <a href="' . esc_url( admin_url('options-permalink.php') ) . '">Go to Permalinks Settings</a></p>';
        	echo '</div>';
    	}	
	}

	/**
	 * Checks if the gateway is fully configured.
	 *
	 * The Lipa na M-Pesa option is visible to customers only
	 * when the gateway is fully configured.
	 *
	 * @return bool True if configured, false otherwise.
	 */
	public function is_configured(): bool {
		return ! empty( $this->settings['client_id'] ) &&
				! empty( $this->settings['client_secret'] ) &&
				! empty( $this->settings['api_key'] ) &&
				! empty( $this->settings['till_number'] ) &&
				! empty( $this->settings['environment'] );
	}

	/**
	 * Checks if the gateway is configured for manual payments.
	 *
	 * Manual payments is available to the customer only
	 * when the gateway is configured for manual payments.
	 *
	 * @return bool True if manual payments are supported, false otherwise.
	 */
	public function is_configured_for_manual_payments(): bool {
		return ! empty( $this->settings['manual_payments_till_no'] ) ||
				( ! empty( $this->settings['paybill_business_no'] ) && ! empty( $this->settings['paybill_account_no'] ) );
	}

	/**
	 * Returns the gateway icon HTML for checkout display.
	 *
	 * @return string HTML for the payment gateway icon.
	 */
	public function get_icon(): string {
		if ( ! $this->wp_is_admin() ) {
			$icon_url  = KKWOO_PLUGIN_URL . 'images/mpesa-logo.png';
			$icon_html = '<img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr( $this->title ) . '" style="height:45px;"/>';

            // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
			return apply_filters(
				'woocommerce_gateway_icon',
				$icon_html,
				$this->id
			);
            // phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		}

		return parent::get_icon();
	}

	/**
	 * Checks if the payment gateway is available for use.
	 *
	 * @return bool True if available, false otherwise.
	 */
	public function is_available(): bool {
		$is_available = ( 'yes' === $this->enabled );
		if ( ! $this->is_configured() || ( 'KES' !== $this->wp_get_currency() || '' === get_option('permalink_structure') ) ) {
			$is_available = false;
		}
		return $is_available;
	}

	/**
	 * Runs after gateway settings are updated.
	 *
	 * @return void
	 */
	public function after_settings_updated(): void {
		KKWoo_Authorization::maybe_authorize( true );
	}

	/**
	 * Renders custom buttons on the gateway settings page.
	 *
	 * @return void
	 */
	public function render_custom_payment_gateway_settings_buttons(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = sanitize_text_field( wp_unslash( $_GET['section'] ?? '' ) );
		if ( $section !== $this->id ) {
			return;
		}

		include plugin_dir_path( __FILE__ ) . 'templates/kkwoo-custom-payment-gateway-settings-sections.php';
	}

	/**
	 * Enqueues assets for the custom settings section.
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function kkwoo_enqueue_custom_settings_section_assets( $hook ) {
		if ( 'woocommerce_page_wc-settings' !== $hook ) {
			return;
		}

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$section = sanitize_text_field( wp_unslash( $_GET['section'] ?? '' ) );
		if ( $section !== $this->id ) {
			return;
		}

		wp_enqueue_style(
			'kkwoo-custom-settings-section',
			KKWOO_PLUGIN_URL . 'assets/css/admin/kkwoo-custom-settings-section.css',
			array(),
			KKWOO_ASSET_VERSION
		);

		wp_enqueue_script(
			'kkwoo-custom-settings-section',
			KKWOO_PLUGIN_URL . 'assets/js/admin/kkwoo-custom-settings-section.js',
			array(),
			KKWOO_ASSET_VERSION,
			true
		);

		$localized_data = array(
			'site_url' => site_url(),
			'nonce'    => wp_create_nonce( 'wp_rest' ),
		);
		wp_localize_script( 'kkwoo-custom-settings-section', 'KKWooData', $localized_data );
	}
}
