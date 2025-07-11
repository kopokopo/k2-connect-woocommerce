<?php

// includes/class-k2-authorization.php

use Kopokopo\SDK\K2;

if (!class_exists('K2_Authorization')) {
    class K2_Authorization
    {
        public static function maybe_authorize(): void
        {
            if (!function_exists('WC')) {
                return;
            }

            $gateways = WC()->payment_gateways()->payment_gateways();

            if (isset($gateways['kkwoo'])) {
                $kkwoo = $gateways['kkwoo'];

                if ($kkwoo->get_option('enabled') === 'yes') {
                    $access_token = get_transient('kopokopo_access_token');
                    if (!$access_token) {
                        self::send_authorization_request($kkwoo);
                    }
                }
            }
        }

        /**
         * Authorize requests to Kopo Kopo.
         *
         * @param WC_Gateway_K2_Payment $kkwoo. The Kopo Kopo payment gateway instance.
         * @return void
         */
        private static function send_authorization_request($kkwoo): void
        {
            if (! method_exists($kkwoo, 'is_configured') || ! $kkwoo->is_configured()) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('K2_Authorization: Gateway not configured properly.');
                }
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
    }
}
