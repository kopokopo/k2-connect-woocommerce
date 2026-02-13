<?php

if (!defined('ABSPATH')) {
    exit;
}

use Kopokopo\SDK\K2;

if (!class_exists('KKWoo_Authorization')) {
    class KKWoo_Authorization
    {
        /**
        * Handles authorization for Kopo Kopo requests.
        *
        * Checks if the Kopo Kopo WooCommerce gateway is enabled and,
        * if needed, obtains a new access token by sending an authorization request.
        *
        * @param bool $must_authorize Optional. If true, forces re-authorization even if an access token exists. Default is false.
        * @return void
        * */
        public static function maybe_authorize($must_authorize = false): void
        {
            if (!function_exists('WC')) {
                return;
            }

            $gateways = WC()->payment_gateways()->payment_gateways();

            if (isset($gateways['kkwoo'])) {
                $kkwoo = $gateways['kkwoo'];

                if ($kkwoo->get_option('enabled') === 'yes') {
                    $access_token = get_transient('kopokopo_access_token');
                    if (!$access_token || $must_authorize) {
                        $must_authorize && delete_transient('kopokopo_access_token');
                        self::send_authorization_request($kkwoo);
                    }
                }
            }
        }

        /**
         * Authorize requests to Kopo Kopo.
         *
         * @param KKWoo_Payment_Gateway $kkwoo. The Kopo Kopo payment gateway instance.
         * @return void
         */
        private static function send_authorization_request($kkwoo): void
        {
            if (! method_exists($kkwoo, 'is_configured') || ! $kkwoo->is_configured()) {
                KKWoo_Logger::log('The Kopo Kopo Gateway is not fully configured. Ensure that you have updated the payment settings fields correctly.', 'error');
                return;
            }

            $environment = $kkwoo->get_option('environment');
            $base_url = $environment === 'production' ? KKWOO_PRODUCTION_URL : KKWOO_SANDBOX_URL;

            $options = [
                'clientId'     => $kkwoo->get_option('client_id'),
                'clientSecret' => $kkwoo->get_option('client_secret'),
                'apiKey'       => $kkwoo->get_option('api_key'),
                'baseUrl'      => $base_url,
            ];

            $K2 = new K2($options);
            $tokens = $K2->TokenService();

            $result = $tokens->getToken();

            if ($result['status'] == 'success') {
                $access_token = $result['data']['accessToken'];
                set_transient('kopokopo_access_token', $access_token, 3600);
            }
        }

        /**
         * Get access token from transient, refreshing if expired.
         *
         * @return string|null
         */
        public static function get_access_token(): ?string
        {
            $access_token = get_transient('kopokopo_access_token');

            if ($access_token) {
                return $access_token;
            }

            if (function_exists('WC')) {
                $gateways = WC()->payment_gateways()->payment_gateways();
                if (isset($gateways['kkwoo'])) {
                    self::send_authorization_request($gateways['kkwoo']);
                    return get_transient('kopokopo_access_token');
                }
            }

            return null;
        }

        /**
        * Get a K2 client instance.
        *
        * @param KKWoo_Payment_Gateway $kkwoo. The Kopo Kopo payment gateway instance.
        * @return \KopoKopo\SDK\K2 The K2 client instance.
        */
        public static function getClient($kkwoo): ?Kopokopo\SDK\K2
        {
            $environment = $kkwoo->get_option('environment');
            $base_url = $environment === 'production' ? KKWOO_PRODUCTION_URL : KKWOO_SANDBOX_URL;

            $options = [
                'clientId'     => $kkwoo->get_option('client_id'),
                'clientSecret' => $kkwoo->get_option('client_secret'),
                'apiKey'       => $kkwoo->get_option('api_key'),
                'baseUrl'      => $base_url,
            ];

            return new K2($options);
        }
    }
}
