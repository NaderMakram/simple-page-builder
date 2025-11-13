<?php

if (!defined('ABSPATH')) {
    exit;
}

class SPB_Admin_Menu
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu()
    {
        add_management_page(
            __('Page Builder', 'simple-page-builder'),
            __('Page Builder', 'simple-page-builder'),
            'manage_options',
            'spb-page-builder',
            [$this, 'render_page']
        );
    }

    public function render_page()
    {
        echo '<div class="wrap"><h1>Simple Page Builder</h1>';
        echo '<p>Welcome to your custom builder plugin! 🚀</p>';
        echo '</div>';
    }
}