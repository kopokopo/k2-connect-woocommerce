<?php
/**
 * Request validation utilities class.
 *
 * @package Kopo_Kopo_for_WooCommerce
 */

namespace KKWoo\Security;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_REST_Request;
use WP_REST_Response;
use KKWoo_Authorization;

/**
 * Handles validation of incoming REST API requests.
 *
 * Contains helper methods for validating REST requests and
 * ensuring secure access to WooCommerce orders and payment endpoints.
 */
class KKWoo_Request_Validator {

	/**
	 * Validates that the Kopo Kopo signature header is present.
	 *
	 * This only checks that the `X-KOPOKOPO-SIGNATURE` header exists.
	 * Full signature verification and payload parsing are handled in
	 * {@see self::validate_callback_request()}, which internally calls
	 * `$webhooks->webhookHandler($webhook_payload, $signature)` from the
	 * K2Connect client. That method validates the signature and returns
	 * the parsed webhook/callback response.
	 *
	 * @return true|WP_Error
	 */
	public static function validate_webhook_signature_presence() {
		$signature = sanitize_text_field(
			wp_unslash( $_SERVER['HTTP_X_KOPOKOPO_SIGNATURE'] ?? '' )
		);
		if ( empty( $signature ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Invalid request',
				),
				401
			);
		}

		return true;
	}

	/**
	 * Validates access to a WooCommerce order via REST request.
	 *
	 * Ensures that the requesting user or payload has permission
	 * to access the specified order before processing the request.
	 *
	 * @param WP_REST_Request $request The REST request containing order data.
	 * @return bool|WP_REST_Response True if validation passes, or WP_REST_Response on failure.
	 */
	public static function validate_order_access( WP_REST_Request $request ) {
		$order_key = sanitize_text_field( $request->get_param( 'order_key' ) );
		if ( ! $order_key ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Invalid order request',
				),
				400
			);
		}

		$order_id = wc_get_order_id_by_order_key( $order_key );
		if ( ! $order_id ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Invalid order request',
				),
				400
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Order not found',
				),
				404
			);
		}

		return true;
	}

	/**
	 * Validates the STK push callback request.
	 *
	 * This checks that the `X-KOPOKOPO-SIGNATURE` header exists.
	 * Full signature verification and payload parsing are handled in
	 * {@see self::validate_callback_request()}, which internally calls
	 * `$webhooks->webhookHandler($webhook_payload, $signature)` from the
	 * K2Connect client. That method validates the signature and returns
	 * the parsed webhook/callback response.
	 *
	 * Also checks that the related WooCommerce order exists and the
	 * order key matches the request payload.
	 *
	 * This ensures that only legitimate callbacks are processed.
	 *
	 * @param WP_REST_Request $request The REST request containing order data.
	 * @return array|WP_Error
	 */
	public static function validate_stk_push_callback( WP_REST_Request $request ) {
		$signature = sanitize_text_field(
			wp_unslash( $_SERVER['HTTP_X_KOPOKOPO_SIGNATURE'] ?? '' )
		);
		if ( empty( $signature ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Invalid request',
				),
				401
			);
		}

		$payload = $request->get_json_params();
		if ( empty( $payload['data'] ) ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Invalid request',
				),
				400
			);
		}

		$attributes = $payload['data']['attributes'] ?? array();
		$metadata   = $attributes['metadata'] ?? array();
		$order_key  = $metadata['reference'] ?? null;
		if ( ! $order_key ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Invalid order request',
				),
				400
			);
		}

		$order_id = wc_get_order_id_by_order_key( $order_key );
		if ( ! $order_id ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Invalid order request',
				),
				400
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_REST_Response(
				array(
					'status'  => 'error',
					'message' => 'Order not found',
				),
				404
			);
		}

		return true;
	}

	/**
	 * Validates Kopo Kopo webhook or STK push callbacks.
	 *
	 * This method performs validation by calling
	 * `$webhooks->webhookHandler($payload, $signature)` from the
	 * K2Connect client. That method:
	 * 1. Verifies the X-KOPOKOPO-SIGNATURE header.
	 * 2. Parses the webhook/STK push payload.
	 *
	 * @param WP_REST_Request $request The incoming REST request object.
	 * @return array Parsed webhook or callback response.
	 */
	public static function validate_callback_request( WP_REST_Request $request ) {
		$gateways = \WC()->payment_gateways()->payment_gateways();
		$kkwoo    = $gateways['kkwoo'];

		$k2       = KKWoo_Authorization::get_client( $kkwoo );
		$webhooks = $k2->Webhooks();

		$webhook_payload = $request->get_body();

		$signature = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_KOPOKOPO_SIGNATURE'] ?? '' ) );

		$response = $webhooks->webhookHandler( $webhook_payload, $signature );
		return $response;
	}
}
