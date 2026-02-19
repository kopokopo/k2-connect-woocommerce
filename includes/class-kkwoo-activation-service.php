<?php
/**
 * Activation service for the Kopo Kopo for WooCommerce plugin.
 *
 * @package Kopo_Kopo_for_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use KKWoo\Database\KKWoo_Manual_Payments_Tracker_Repository;

/**
 * Handles tasks required when the plugin is activated, such as
 * setting up default options, creating custom tables, or flushing
 * rewrite rules.
 */
class KKWoo_Activation_Service {
	/**
	 * Runs tasks when the plugin is activated.
	 *
	 * This static method is called during plugin activation and is responsible
	 * for initializing default settings, flushing rewrite rules, or any
	 * other setup needed for the plugin to function correctly.
	 *
	 * @return void
	 */
	public static function activate(): void {
		KKWoo_Manual_Payments_Tracker_Repository::create_table();
		KKWoo_Payment_Page::flush_rules();
	}
}
