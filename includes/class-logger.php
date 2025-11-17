<?php
if (!defined('ABSPATH')) {
    exit;
}

class SPB_Logger
{

    private static $api_log_table;
    private static $page_log_table;

    public static function init()
    {
        global $wpdb;
        self::$api_log_table = $wpdb->prefix . 'spb_api_logs';
        self::$page_log_table = $wpdb->prefix . 'spb_page_logs';
    }

    /**
     * Log an API authentication or request attempt
     */
    public static function log($type, $data = [])
    {
        global $wpdb;

        $table = self::$api_log_table;

        $wpdb->insert($table, [
            'timestamp' => current_time('mysql'),
            'type'      => $type,
            'api_key_id' => $data['api_key_id'] ?? null,
            'endpoint'  => $data['endpoint'] ?? $_SERVER['REQUEST_URI'],
            'status'    => $type === 'auth_failed' ? 'failed' : 'success',
            'ip_address' => $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'],
            'response_time' => $data['response_time'] ?? null,
        ], [
            '%s',
            '%s',
            '%d',
            '%s',
            '%s',
            '%s',
            '%d'
        ]);
    }

    /**
     * Log a page created via API
     */
    public static function log_page_created($post_id, $api_key_name)
    {
        global $wpdb;
        $wpdb->insert(self::$page_log_table, [
            'post_id'      => $post_id,
            'api_key_name' => $api_key_name,
            'created_at'   => current_time('mysql'),
        ]);
    }
}
SPB_Logger::init();
