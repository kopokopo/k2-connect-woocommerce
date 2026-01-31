<?php

if (! defined('ABSPATH')) {
    exit;
}

class KKWoo_Logger
{
    /*
    * @param string $message
    * @param string $level One of 'emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'
    */
    public static function log($message, $level = 'info'): void
    {
        if (class_exists('WC_Logger')) {
            $logger  = wc_get_logger();
            $context = [ 'source' => 'kkwoo' ];

            // Always log errors, but log "info/debug" only when WP_DEBUG is true
            if ($level === 'error' || (defined('WP_DEBUG') && WP_DEBUG)) {
                $msg = is_scalar($message) ? $message : wp_json_encode($message);
                $logger->log($level, $msg, $context);
            }
        }
    }
}
