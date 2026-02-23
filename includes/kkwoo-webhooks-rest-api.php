<?php
/**
 * Webhook subscription and transaction handlers.
 *
 * Registers webhook subscriptions with the K2 Connect API and
 * processes incoming webhook events for Buy Goods and B2B
 * transactions.
 *
 * @package Kopo_Kopo_for_WooCommerce
 */

namespace KKWoo\Webhooks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use KKWoo_Logger;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use KKWoo\Database\KKWoo_Manual_Payments_Tracker_Repository;
use KKWoo\ManualPayments\KKWoo_Manual_Payment_Service;
use KKWoo\Security\KKWoo_Request_Validator;

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'kkwoo/v1',
			'/create-webhook-subscriptions',
			array(
				'methods'             => 'POST',
				'callback'            => __NAMESPACE__ . '\\handle_create_webhook_subscriptions',
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
			)
		);

		register_rest_route(
			'kkwoo/v1',
			'/buygoods-transaction-received',
			array(
				'methods'             => 'POST',
				'callback'            => __NAMESPACE__ . '\\handle_buygoods_transaction_received',
				'permission_callback' => array( KKWoo_Request_Validator::class, 'validate_webhook_signature_presence' ),
			)
		);

		register_rest_route(
			'kkwoo/v1',
			'/b2b-transaction-received',
			array(
				'methods'             => 'POST',
				'callback'            => __NAMESPACE__ . '\\handle_b2b_transaction_received',
				'permission_callback' => array( KKWoo_Request_Validator::class, 'validate_webhook_signature_presence' ),
			)
		);
	}
);

/**
 * Send request to create a webhook subscription for a given event type.
 *
 * Sends a request to Kopo Kopo to register a webhook
 * subscription for the specified event type. Optionally scope
 * the subscription by till number.
 *
 * Triggered via admin action to create
 * all required webhook subscriptions for the plugin.
 *
 * @param string      $event_type The webhook event type to subscribe to.
 * @param string|null $till       Optional. Till number used to scope the subscription.
 * @return bool True on success, false on failure.
 */
function create_webhook_subscription( $event_type, $till = null ): bool {
	try {
		$access_token = \KKWoo_Authorization::get_access_token();
		if ( empty( $access_token ) ) {
			\KKWoo_Logger::log( \KKWoo_User_Friendly_Messages::get( 'auth_token_error' ), 'error' );
			return false;
		}

		$gateways = \WC()->payment_gateways()->payment_gateways();
		$kkwoo    = $gateways['kkwoo'];

		$k2       = \KKWoo_Authorization::get_client( $kkwoo );
		$webhooks = $k2->Webhooks();

		$response = $webhooks->subscribe(
			array(
				'eventType'      => $event_type,
				'url'            => rest_url( 'kkwoo/v1/' . str_replace( '_', '-', $event_type ) ),
				'scope'          => $till ? 'till' : 'company',
				'scopeReference' => $till ?? '',
				'accessToken'    => $access_token,
			)
		);

		if ( ! isset( $response['status'] ) || 'success' !== $response['status'] ) {
			\KKWoo_Logger::log(
				'Webhook subscription failed for event: ' . $event_type . '. Payload received: ' . wp_json_encode( $response ),
				'error'
			);
			return false;
		}

		return true;

	} catch ( \Throwable $e ) {
		\KKWoo_Logger::log(
			'Error while creating webhook subscription for event "' . $event_type . '": ' . $e->getMessage(),
			'error'
		);
		return false;
	}
}

/**
 * Handles callback for sent webhook subscription requests.
 *
 * @return WP_REST_Response|\WP_Error REST response on success, or WP_Error on failure.
 */
function handle_create_webhook_subscriptions() {
	try {
		$gateways = \WC()->payment_gateways()->payment_gateways();
		$kkwoo    = $gateways['kkwoo'] ?? null;

		if ( ! $kkwoo ) {
			return new WP_Error(
				'missing_gateway',
				'Kopo Kopo gateway not found.',
				array( 'status' => 400 )
			);
		}

		$till_no             = $kkwoo->get_option( 'manual_payments_till_no' );
		$paybill_business_no = $kkwoo->get_option( 'paybill_business_no' );
		$paybill_account_no  = $kkwoo->get_option( 'paybill_account_no' );

		$success = true;

		if ( ! empty( $till_no ) && empty( $paybill_account_no ) ) {
			$success &= create_webhook_subscription( 'buygoods_transaction_received', $till_no );
			$success &= create_webhook_subscription( 'b2b_transaction_received', $till_no );
		} elseif ( ! empty( $paybill_account_no ) ) {
			$success &= create_webhook_subscription( 'buygoods_transaction_received' );
			$success &= create_webhook_subscription( 'b2b_transaction_received' );
		} else {
			return new WP_Error(
				'invalid_paybill_or_till',
				\KKWoo_User_Friendly_Messages::get( 'webhook_failed_invalid_paybill_or_till' ),
				array( 'status' => 400 )
			);
		}

		if ( ! $success ) {
			return new WP_Error(
				'webhook_subscription_failed',
				\KKWoo_User_Friendly_Messages::get( 'webhook_subscription_failed' ),
				array( 'status' => 500 )
			);
		}

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => \KKWoo_User_Friendly_Messages::get( 'subscribed_to_webhooks' ),
			)
		);

	} catch ( \Throwable $e ) {
		\KKWoo_Logger::log( 'Webhook subscription error: ' . $e->getMessage(), 'error' );
		return new WP_Error(
			'webhook_subscription_error',
			\KKWoo_User_Friendly_Messages::get( 'webhook_subscription_error' ),
			array( 'status' => 500 )
		);
	}
}

/**
 * Handles Buy Goods transaction received webhook.
 *
 * Validates and processes incoming Buy Goods transaction
 * webhook payload from Kopo Kopo.
 *
 * @param WP_REST_Request $request The REST request containing webhook payload.
 * @return WP_REST_Response REST response acknowledging receipt.
 */
function handle_buygoods_transaction_received( WP_REST_Request $request ) {
	process_request( $request );
	return new \WP_REST_Response( array( 'message' => 'Buygoods transaction webhook received' ), 200 );
}

/**
 * Handles B2B transaction received webhook.
 *
 * Validates and processes incoming B2B transaction
 * webhook payload from Kopo Kopo.
 *
 * @param WP_REST_Request $request The REST request containing webhook payload.
 * @return WP_REST_Response REST response acknowledging receipt.
 */
function handle_b2b_transaction_received( WP_REST_Request $request ) {
	process_request( $request );
	return new \WP_REST_Response( array( 'message' => 'B2B transaction webhook received' ), 200 );
}

/**
 * Processes incoming webhook request.
 *
 * Performs validation, sanitization, and business logic
 * processing for webhook events before returning a response.
 *
 * @param WP_REST_Request $request The REST request object.
 * @return WP_REST_Response|Void REST response indicating processing result.
 */
function process_request( WP_REST_Request $request ) {
	$validated_response = KKWoo_Request_Validator::validate_callback_request( $request );

	$status    = $validated_response['status'] ?? '';
	$data      = $validated_response['data'] ?? array();
	$reference = $data['reference'] ?? '';

	if ( 'success' !== $status ) {
		KKWoo_Logger::log(
			'Webhook validation failed for the following request payload: ' . $request->get_body(),
			'error'
		);
		return new \WP_REST_Response( array( 'message' => 'Invalid webhook received' ), 400 );
	}

	if ( empty( $reference ) ) {
		KKWoo_Logger::log( 'Missing reference in webhook payload', 'warning' );
		return new \WP_REST_Response( array( 'message' => 'Missing reference' ), 400 );
	}

	if ( 'Received' === $data['status'] || 'Complete' === $data['status'] ) {
		$encoded_webhook_payload = wp_json_encode( $validated_response );
		KKWoo_Manual_Payments_Tracker_Repository::upsert(
			$reference,
			null,
			$encoded_webhook_payload,
		);

		$payment_tracker = KKWoo_Manual_Payments_Tracker_Repository::get_by_mpesa_ref( $reference );
		$order_id        = $payment_tracker['order_id'] ?? null;

		if ( isset( $order_id ) ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$manual_payment_service = new KKWoo_Manual_Payment_Service();
				$manual_payment_service->complete( $order, $data );
			}
		}
	}
}
