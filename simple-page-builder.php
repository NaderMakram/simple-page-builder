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