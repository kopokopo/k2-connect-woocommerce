<?php

namespace KKWoo\Authorization;

if (!defined('ABSPATH')) {
    exit;
}

use WP_REST_Request;
use WP_Error;

add_action('rest_api_init', function () {
    register_rest_route('kkwoo/v1', '/force-refresh-access-token', [
        'methods'  => 'POST',
        'callback' => __NAMESPACE__ . '\\handle_force_refresh_access_token',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        },
    ]);
});

function handle_force_refresh_access_token(WP_REST_Request $request)
{
    try {
        \KKWoo_Authorization::maybe_authorize(true);

        $access_token = get_transient('kopokopo_access_token');

        if ($access_token) {
            return rest_ensure_response([
                'success' => true,
                'message' => 'Access token refreshed successfully.'
            ]);
        }

        return new WP_Error(
            'refresh_failed',
            'Token was not refreshed. Please check your credentials and try again.',
            ['status' => 500]
        );

    } catch (\Throwable $e) {
        return new WP_Error(
            'refresh_exception',
            'Access token refresh failed: ' . $e->getMessage(),
            ['status' => 500]
        );
    }
}
