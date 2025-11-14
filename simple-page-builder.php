<?php

/**
 * Plugin Name: Simple Page Builder
 * Description: A secure REST API-based bulk page builder with API key authentication and webhooks.
 * Version: 1.0.0
 * Author: Nader Makram
 * Text Domain: simple-page-builder
 * Plugin URI: https://github.com/nadermakram/simple-page-builder
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Define constants for easy reference
 */
define('SPB_VERSION', '1.0.0');
define('SPB_PLUGIN_FILE', __FILE__);
define('SPB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPB_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Autoload classes
 */
spl_autoload_register(function ($class) {
    if (strpos($class, 'SPB_') === 0) {
        $filename = strtolower(str_replace(['SPB_', '_'], ['class-', '-'], $class)) . '.php';
        $base_dir = SPB_PLUGIN_DIR . 'includes/';

        // Recursive directory scan
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base_dir)
        );

        foreach ($iterator as $file) {
            if (basename($file) === $filename) {
                include $file;
                return;
            }
        }
    }
});


/**
 * Initialize the plugin
 */
function spb_init()
{
    // Example: create a main plugin instance later
    if (is_admin()) {
        new SPB_Admin_Menu();
    }
}
add_action('plugins_loaded', 'spb_init');


// create custom table for api keys
register_activation_hook(__FILE__, 'spb_install_plugin');

function spb_install_plugin()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'spb_api_keys';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        key_name VARCHAR(255) NOT NULL,
        api_key_hash VARCHAR(255) NOT NULL,
        secret_hash VARCHAR(255) NOT NULL,
        api_key_preview VARCHAR(20) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'active',
        permissions TEXT NULL,
        created_at DATETIME NOT NULL,
        expires_at DATETIME NULL,
        last_used_at DATETIME NULL,
        request_count BIGINT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        INDEX (status),
        INDEX (created_at)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

// test generate key
add_action('init', function () {
    if (isset($_GET['spb_test_key'])) {
        $manager = new SPB_API_Key_Manager();
        $key = $manager->generate_key('Test Key', null);
        print_r($key);
        echo (wp_hash_password($key['api_key']) . "\n");
        echo (wp_hash_password($key['secret_key']) . "\n");
    }
});

// test use key
add_action('init', function () {
    if (isset($_GET['spb_test_auth'])) {
        $auth = new SPB_API_Auth();
        $result = $auth->authenticate_request();
        print_r($result);
    }
});