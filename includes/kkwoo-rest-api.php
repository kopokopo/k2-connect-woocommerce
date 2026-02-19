<?php
/**
 * STK push REST APIs
 *
 * @package Kopo_Kopo_for_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use KKWoo\Security\KKWoo_Request_Validator;

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kkwoo/v1',
			'/stk-push',
			array(
				'methods'             => 'POST',
				'callback'            => 'kkwoo_handle_stk_push',
				'permission_callback' => array( KKWoo_Request_Validator::class, 'validate_order_access' ),
			)
		);

		register_rest_route(
			'kkwoo/v1',
			'/stk-push-callback',
			array(
				'methods'             => 'POST',
				'callback'            => 'kkwoo_handle_stk_push_callback',
				'permission_callback' => array( KKWoo_Request_Validator::class, 'validate_stk_push_callback' ),
			)
		);

		register_rest_route(
			'kkwoo/v1',
			'/payment-status',
			array(
				'methods'             => 'GET',
				'callback'            => 'kkwoo_get_payment_status',
				'permission_callback' => array( KKWoo_Request_Validator::class, 'validate_order_access' ),
			)
		);

		register_rest_route(
			'kkwoo/v1',
			'/query-incoming-payment-status',
			array(
				'methods'             => 'GET',
				'callback'            => 'kkwoo_handle_query_incoming_payment_status',
				'permission_callback' => array( KKWoo_Request_Validator::class, 'validate_order_access' ),
			)
		);
	}
);

/**
 * Handles the STK push REST request.
 *
 * Initiates an STK push payment request based on the incoming
 * REST API request data and returns a REST response.
 *
 * @param WP_REST_Request $request The REST request object containing STK data.
 * @return WP_REST_Response $array The REST response containing the result of the STK push request or any errors encountered.
 */
function kkwoo_handle_stk_push( WP_REST_Request $request ) {
	$phone     = sanitize_text_field( $request->get_param( 'phone' ) );
	$order_key = sanitize_text_field( $request->get_param( 'order_key' ) );

	$order_id = wc_get_order_id_by_order_key( $order_key );
	if ( ! $order_id ) {
		return new WP_REST_Response(
			array(
				'status' => 'error',
				'data'   => KKWoo_User_Friendly_Messages::get( 'order_not_found' ),
			),
			400
		);
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return new WP_REST_Response(
			array(
				'status' => 'error',
				'data'   => KKWoo_User_Friendly_Messages::get( 'order_not_found' ),
			),
			404
		);
	}

	if ( ! in_array( $order->get_status(), array( 'pending', 'failed' ), true ) ) {
		KKWoo_Logger::log(
			sprintf( 'Customer attempted payment for order %d, but status is "%s".', $order->get_id(), $order->get_status() ),
			'warning'
		);
		return new WP_REST_Response(
			array(
				'status' => 'error',
				'data'   => KKWoo_User_Friendly_Messages::get( 'invalid_order_status' ),
			),
			400
		);
	}

	require_once __DIR__ . '/kkwoo-stk-service.php';
	$response = kkwoo_send_stk_push( $phone, $order );

	if ( is_wp_error( $response ) ) {
		return new WP_REST_Response(
			array(
				'status' => 'error',
				'data'   => $response->get_error_message(),
			),
			500
		);
	}

	if ( isset( $response['status'] ) && 'success' === $response['status'] ) {
		$order->update_status( 'on-hold', KKWoo_User_Friendly_Messages::get( 'on_hold_status_update' ) );
		$order->update_meta_data( 'kkwoo_payment_location', $response['location'] );
		$order->save();

		return new WP_REST_Response(
			array(
				'status' => 'success',
				'data'   => $response,
			),
			200
		);
	}

	return new WP_REST_Response(
		array(
			'status' => 'error',
			'data'   => $response,
		),
		500
	);
}

/**
 * Handles the STK push callback from Kopo Kopo.
 *
 * Processes the incoming webhook request from Kopo Kopo after an
 * STK push attempt, validates the request and
 * delegates order processing.
 *
 * @param WP_REST_Request $request The REST request containing callback data.
 * @return WP_REST_Response $array The REST response on success or failure.
 */
function kkwoo_handle_stk_push_callback( WP_REST_Request $request ) {
	$validated_response = KKWoo_Request_Validator::validate_callback_request( $request );
	return kkwoo_process_stk_push_callback( $validated_response );
}

/**
 * Processes a validated STK push callback response.
 *
 * Updates the corresponding WooCommerce order based on the
 * validated callback payload received from Kopo Kopo.
 *
 * @param array $validated_response The validated callback payload data.
 * @return WP_REST_Response $array The REST response on success or failure
 */
function kkwoo_process_stk_push_callback( $validated_response ) {

	if ( empty( $validated_response['data'] ) ) {
		KKWoo_Logger::log( KKWoo_User_Friendly_Messages::get( 'invalid_stk_push_callback_data' ), 'error' );
		return new WP_REST_Response(
			array(
				'status'  => 'error',
				'message' => 'Invalid callback data',
			),
			400
		);
	}

	$data            = $validated_response['data'];
	$metadata        = $data['metadata'] ?? array();
	$status          = $data['status'] ?? '';
	$resource_status = $data['resourceStatus'] ?? null;
	$errors          = $data['errors'] ?? array();
	$reference       = $metadata['reference'] ?? null;
	$resource_id     = $data['resourceId'] ?? null;

	if ( ! $reference ) {
		KKWoo_Logger::log( KKWoo_User_Friendly_Messages::get( 'invalid_stk_push_callback_data' ), 'error' );
		return new WP_REST_Response(
			array(
				'status'  => 'error',
				'message' => 'Missing order reference',
			),
			400
		);
	}

	$order_id = wc_get_order_id_by_order_key( $reference );
	if ( ! $order_id ) {
		KKWoo_Logger::log( KKWoo_User_Friendly_Messages::get( 'order_not_found' ), 'error' );
		return new WP_REST_Response(
			array(
				'status'  => 'error',
				'message' => 'Order not found',
			),
			404
		);
	}

	$order = wc_get_order( $order_id );

	if ( ! in_array( $order->get_status(), array( 'on-hold' ), true ) ) {
		KKWoo_Logger::log(
			sprintf( 'Cannot complete payment of order %d, with status "%s".', $order->get_id(), $order->get_status() ),
			'warning'
		);
		return new WP_REST_Response(
			array(
				'status' => 'error',
				'data'   => KKWoo_User_Friendly_Messages::get( 'invalid_order_status' ),
			),
			400
		);
	}

	if ( 'Success' === $status && isset( $resource_status ) && 'Received' === $resource_status ) {
		// Mark order as paid.
		$order->payment_complete( $resource_id );
		$order->add_order_note(
			sprintf(
				'Payment received via Kopo Kopo for WooCommerce. Amount: %s %s. Phone: %s',
				$data['currency'] ?? '',
				$data['amount'] ?? '',
				$data['senderPhoneNumber'] ?? ''
			)
		);

		// Necessary to update this to null when successful so as to remove error if earlier saved.
		$order->update_meta_data( 'kkwoo_payment_error_msg', '' );
		$order->save();
		return new WP_REST_Response(
			array(
				'status'  => 'success',
				'message' => 'Payment processed',
			),
			200
		);
	} else {
		// Payment failed or was cancelled.
		$order->update_status( 'failed', KKWoo_User_Friendly_Messages::get( 'failed_status_update' ) );
		$order->update_meta_data( 'kkwoo_payment_error_msg', $errors );
		$order->save();
		return new WP_REST_Response(
			array(
				'status'  => 'error',
				'message' => $errors,
			),
			200
		);
	}
}

/**
 * Retrieves the payment status for a given order.
 *
 * Queries the current payment status from WooCommerce orders table based on
 * data provided in the REST request.
 *
 * @param WP_REST_Request $request The REST request containing order key.
 * @return WP_REST_Response $array The REST response with payment status data.
 */
function kkwoo_get_payment_status( WP_REST_Request $request ) {
	$order_key = $request->get_param( 'order_key' );
	$order_id  = wc_get_order_id_by_order_key( $order_key );
	$order     = $order_id ? wc_get_order( $order_id ) : null;

	return array(
		'status' => $order ? $order->get_status() : 'not_found',
		'data'   => $order ? $order->get_meta( 'kkwoo_payment_error_msg', true ) : '',
	);
}

/**
 * Handles a request to query the status of an incoming payment.
 *
 * Sends a request to Kopo Kopo to retrieve the status of a
 * previously initiated incoming payment.
 *
 * @param WP_REST_Request $request The REST request containing the order key.
 * @return WP_REST_Response $array The REST response with payment status details, or WP_Error on failure.
 */
function kkwoo_handle_query_incoming_payment_status( WP_REST_Request $request ) {
	$order_key = sanitize_text_field( $request->get_param( 'order_key' ) );

	$order_id = wc_get_order_id_by_order_key( $order_key );
	if ( ! $order_id ) {
		return new WP_REST_Response(
			array(
				'status' => 'error',
				'data'   => KKWoo_User_Friendly_Messages::get( 'order_not_found' ),
			),
			400
		);
	}

	$order = wc_get_order( $order_id );
	if ( ! $order ) {
		return new WP_REST_Response(
			array(
				'status' => 'error',
				'data'   => KKWoo_User_Friendly_Messages::get( 'order_not_found' ),
			),
			404
		);
	}

	require_once __DIR__ . '/class-kkwoo-query-incoming-payment-status-service.php';
	$check_payment_status_service = new KKWoo_Query_Incoming_Payment_Status_Service();
	$response                     = $check_payment_status_service->query_incoming_payment_status( $order );

	if ( is_wp_error( $response ) ) {
		return new WP_REST_Response(
			array(
				'status' => 'error',
				'data'   => $response->get_error_message(),
			),
			500
		);
	}

	if ( $response instanceof WP_REST_Response ) {
		return $response;
	}

	return $check_payment_status_service->process_payment_status_response( $order, $response );
}
