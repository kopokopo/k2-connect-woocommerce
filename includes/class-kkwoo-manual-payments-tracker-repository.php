<?php

namespace KKWoo\Database;

class Manual_Payments_Tracker_repository
{
    private static $base_table_name = 'kkwoo_manual_payments_tracker';
    public static function create_table(): void
    {
        global $wpdb;

        $table_name = $wpdb->prefix . self::$base_table_name;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL UNIQUE,
    mpesa_ref_no VARCHAR(50) NOT NULL UNIQUE,
    webhook_payload LONGTEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
    ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function insert(int $order_id, string $mpesa_ref_no): bool
    {
        global $wpdb;

        $wpdb->hide_errors(); // Prevent showing HTML error blocks

        $result = $wpdb->insert(
            $wpdb->prefix . self::$base_table_name,
            [
                'order_id'     => $order_id,
                'mpesa_ref_no' => $mpesa_ref_no,
            ],
            [
                '%d',
                '%s',
            ]
        );
        return $result;
    }

    public static function get_by_mpesa_ref(string $mpesa_ref_no): ?array
    {
        global $wpdb;

        $mpesa_ref_no = sanitize_text_field($mpesa_ref_no);
        $table = $wpdb->prefix . self::$base_table_name;

        return $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE mpesa_ref_no = %s LIMIT 1", $mpesa_ref_no),
            ARRAY_A
        );
    }


    public static function update_by_id(int $id, array $data): bool
    {
        global $wpdb;

        // Full table name
        $table = $wpdb->prefix . self::$base_table_name;

        // Define formats dynamically based on values
        $formats = [];
        foreach ($data as $value) {
            if (is_int($value)) {
                $formats[] = '%d';
            } elseif (is_float($value)) {
                $formats[] = '%f';
            } else {
                $formats[] = '%s';
            }
        }

        // Where clause
        $where = ['id' => $id];
        $where_format = ['%d'];

        $result = $wpdb->update(
            $table,
            $data,
            $where,
            $formats,
            $where_format
        );

        return $result !== false;
    }
}
