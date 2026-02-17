<?php
/**
 * Authorization REST APIs.
 *
 * @package Kopo_Kopo_for_WooCommerce
 */

namespace KKWoo\Authorization;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Request;
use WP_Error;

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kkwoo/v1',
			'/force-refresh-access-token',
			array(
				'methods'             => 'POST',
				'callback'            => __NAMESPACE__ . '\\handle_force_refresh_access_token',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);
	}
);

/**
 * Forces a refresh of the Kopo Kopo API access token.
 *
 * Handles the process of renewing the stored API access token when
 * it needs to be refreshed manually. Returns a response suitable
 * for REST API consumption.
 *
 * @return array|\WP_Error Array containing 'success' => true and a 'message' on success,
 *                         or a WP_Error object on failure.
 */
function handle_force_refresh_access_token() {
	try {
		\KKWoo_Authorization::maybe_authorize( true );

		$access_token = get_transient( 'kopokopo_access_token' );

		if ( $access_token ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => 'Access token refreshed successfully.',
				)
			);
		}

		return new WP_Error(
			'refresh_failed',
			'Token was not refreshed. Please check your credentials and try again.',
			array( 'status' => 500 )
		);

	} catch ( \Throwable $e ) {
		return new WP_Error(
			'refresh_exception',
			'Access token refresh failed: ' . $e->getMessage(),
			array( 'status' => 500 )
		);
	}
}
