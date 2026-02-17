<?php
/**
 * Logger class.
 *
 * @package Kopo_Kopo_for_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Handles logging functionality for Kopo Kopo for WooCommerce.
 */
class KKWoo_Logger {

	/**
	 * Handles logging functionality.
	 * Always log errors, but log "info/debug" only when WP_DEBUG is true.
	 *
	 * @param string $message The message to be logged.
	 * @param string $level One of 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'.
	 */
	public static function log( $message, $level = 'info' ): void {
		if ( class_exists( 'WC_Logger' ) ) {
			$logger  = wc_get_logger();
			$context = array( 'source' => 'kopo-kopo-for-woocommerce-logger' );

			if ( 'error' === $level || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
				$msg = is_scalar( $message ) ? $message : wp_json_encode( $message );
				$logger->log( $level, $msg, $context );
			}
		}
	}
}
