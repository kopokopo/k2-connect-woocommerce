<?php
/**
 * Custom virtual page class.
 *
 * @package Kopo_Kopo_for_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Adds custom rewrite rules and query variables for payment-related actions.
 */
class KKWoo_Payment_Page {

	/**
	 * Constructor.
	 *
	 * Hooks the class methods into WordPress actions and filters.
	 */
	public function __construct() {
		add_action( 'init', array( self::class, 'add_rewrite_rules' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
	}

	/**
	 * Add custom rewrite rules for the payment page.
	 *
	 * @return void
	 */
	public static function add_rewrite_rules(): void {
		add_rewrite_rule(
			'^lipa-na-mpesa-k2/?$',
			'index.php?lipa_na_mpesa_k2=1',
			'top'
		);
	}

	/**
	 * Add custom query vars
	 *
	 * @param array $vars Existing query variables.
	 * @return array Modified query variables including payment vars.
	 */
	public function add_query_vars( $vars ): array {
		$vars[] = 'lipa_na_mpesa_k2';
		$vars[] = 'kkwoo_order_key';
		return $vars;
	}

	/**
	 * Flush rewrite rules if needed.
	 *
	 * @return void
	 */
	public static function flush_rules(): void {
		self::add_rewrite_rules();
		flush_rewrite_rules();
	}
}
