<?php

/**
 * PHPUnit bootstrap file.
 *
 * @package Kopo_Kopo_For_Woocommerce
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Forward custom PHPUnit Polyfills configuration to PHPUnit bootstrap file.
$_phpunit_polyfills_path = getenv( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' );
if ( false !== $_phpunit_polyfills_path ) {
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $_phpunit_polyfills_path );
}

if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	echo "Could not find {$_tests_dir}/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

// Load Composer autoloader (to include PHPUnit Polyfills)
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load WooCommerce before your plugin
function _load_woocommerce_for_tests() {
	$wc_file = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
	if ( file_exists( $wc_file ) ) {
		require_once $wc_file;
	} else {
		die( 'WooCommerce not found at ' . $wc_file );
	}
}
tests_add_filter( 'muplugins_loaded', '_load_woocommerce_for_tests', 5 );

// Load your plugin
function _load_plugin_for_tests() {
	require_once dirname( __DIR__ ) . '/kopo-kopo-for-woocommerce.php';
}
tests_add_filter( 'muplugins_loaded', '_load_plugin_for_tests', 10 );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";

// After WordPress is loaded, safely hook to 'wp_loaded' or run code.
add_action(
	'wp_loaded',
	function () {
		global $wp_rewrite;

		if ( null === $wp_rewrite ) {
			$wp_rewrite = new WP_Rewrite();
			$wp_rewrite->init();
		}

		if ( class_exists( 'WC_Install' ) ) {
			WC_Install::install();
			update_option( 'woocommerce_version', WC_VERSION );
		}
	}
);
