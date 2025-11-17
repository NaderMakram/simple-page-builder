<?php
if (!defined('ABSPATH')) exit;

class SPB_Admin_Menu
{

    private $tabs = ['api_keys' => 'API Keys', 'activity_log' => 'API Activity Log', 'created_pages' => 'Created Pages', 'settings' => 'Settings', 'documentation' => 'API Documentation'];

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_spb_generate_key', [$this, 'handle_generate_key']);
        add_action('admin_post_spb_revoke_key', [$this, 'handle_revoke_key']);
        add_action('admin_post_spb_activate_key', [$this, 'handle_activate_key']);
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
        $current_tab = $_GET['tab'] ?? 'api_keys';

        echo '<div class="wrap"><h1>Simple Page Builder</h1>';

        // Tabs
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($this->tabs as $slug => $label) {
            $active = $current_tab === $slug ? ' nav-tab-active' : '';
            echo '<a href="?page=spb-page-builder&tab=' . esc_attr($slug) . '" class="nav-tab' . $active . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';

        // Tab content
        switch ($current_tab) {
            case 'api_keys':
                $this->render_api_keys_tab();
                break;
            case 'activity_log':
                $this->render_activity_log_tab();
                break;
            case 'created_pages':
                $this->render_created_pages_tab();
                break;
            case 'settings':
                $this->render_settings_tab();
                break;
            case 'documentation':
                $this->render_documentation_tab();
                break;
        }

        echo '</div>';
    }

    private function render_api_keys_tab()
    {
        $manager = new SPB_API_Key_Manager();

        // Check for newly generated key
        $new_key = get_transient('spb_new_api_key_' . get_current_user_id());
        if ($new_key) {
            echo '<div class="notice notice-success"><p>API Key generated!</p></div>';

            echo '<div style="margin-bottom: 20px;">';

            // API Key
            echo '<div class="spb-api-key-item" style="margin-bottom: 10px;">';
            echo '<strong>API Key:</strong> ';
            echo '<input type="text" value="' . esc_attr($new_key['api_key']) . '" readonly id="spb_api_key" style="width:400px;"> ';
            echo '<button class="button spb-copy-btn" data-target="#spb_api_key">Copy</button>';
            echo '</div>';

            // Secret Key
            echo '<div class="spb-api-key-item">';
            echo '<strong>Secret Key:</strong> ';
            echo '<input type="text" value="' . esc_attr($new_key['secret_key']) . '" readonly id="spb_secret_key" style="width:400px;"> ';
            echo '<button class="button spb-copy-btn" data-target="#spb_secret_key">Copy</button>';
            echo '</div>';

            echo '</div>';

            // Delete transient so it shows only once
            delete_transient('spb_new_api_key_' . get_current_user_id());
        }


        // Generate Key Form
        echo '<h2>Generate New API Key</h2>';
        echo '<form method="post" action="' . admin_url('admin-post.php') . '">';
        echo '<input type="hidden" name="action" value="spb_generate_key">';
        echo '<label>Key Name: <input type="text" name="key_name" required></label> ';
        echo '<input type="submit" class="button button-primary" value="Generate Key">';
        echo wp_nonce_field('spb_generate_key_nonce', '_wpnonce', true, false);
        echo '</form><hr>';

        // List existing keys
        $keys = $manager->get_all_keys();
        echo '<h2>Existing API Keys</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Key Name</th><th>Preview</th><th>Status</th><th>Created</th><th>Last Used</th><th>Request Count</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        foreach ($keys as $key) {
            $preview = substr($key->api_key_hash, 0, 8) . '***';
            $status = $key->status === 'active' ? '<span style="color:green">Active</span>' : '<span style="color:red">Revoked</span>';
            echo '<tr>';
            echo '<td>' . esc_html($key->key_name) . '</td>';
            echo '<td>' . esc_html($preview) . '</td>';
            echo '<td>' . $status . '</td>';
            echo '<td>' . esc_html($key->created_at) . '</td>';
            echo '<td>' . esc_html($key->last_used_at) . '</td>';
            echo '<td>' . esc_html($key->request_count) . '</td>';
            echo '<td>';
            if ($key->status === 'active') {
                // Revoke button
                echo '<form method="post" action="' . admin_url('admin-post.php') . '" style="display:inline;">
            <input type="hidden" name="action" value="spb_revoke_key">
            <input type="hidden" name="key_id" value="' . esc_attr($key->id) . '">
            ' . wp_nonce_field('spb_revoke_key_nonce', '_wpnonce', true, false) . '
            <input type="submit" class="button" value="Revoke">
        </form>';
            } else {
                // Activate button
                echo '<form method="post" action="' . admin_url('admin-post.php') . '" style="display:inline;">
            <input type="hidden" name="action" value="spb_activate_key">
            <input type="hidden" name="key_id" value="' . esc_attr($key->id) . '">
            ' . wp_nonce_field('spb_activate_key_nonce', '_wpnonce', true, false) . '
            <input type="submit" class="button button-primary" value="Activate">
        </form>';
            }
            echo '</td>';

            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    public function handle_generate_key()
    {
        check_admin_referer('spb_generate_key_nonce', '_wpnonce');

        if (!current_user_can('manage_options')) wp_die('Permission denied');

        $name = sanitize_text_field($_POST['key_name'] ?? '');
        if (!$name) wp_die('Key name is required');

        $manager = new SPB_API_Key_Manager();
        $key = $manager->generate_key($name);

        // Store keys temporarily in a transient (expires in 1 minute)
        set_transient('spb_new_api_key_' . get_current_user_id(), $key, 60);

        // Redirect to API keys tab
        wp_redirect(admin_url('tools.php?page=spb-page-builder&tab=api_keys&spb_message=1'));
        exit;
    }


    public function handle_revoke_key()
    {
        check_admin_referer('spb_revoke_key_nonce', '_wpnonce');

        if (!current_user_can('manage_options')) wp_die('Permission denied');

        $id = intval($_POST['key_id'] ?? 0);
        if ($id) {
            $manager = new SPB_API_Key_Manager();
            $manager->revoke_key($id);
        }

        wp_redirect(admin_url('tools.php?page=spb-page-builder&tab=api_keys&spb_message=' . urlencode("API Key revoked.")));
        exit;
    }

    public function activate_key($id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'spb_api_keys';
        $wpdb->update($table, ['status' => 'active'], ['id' => $id]);
    }

    public function handle_activate_key()
    {
        check_admin_referer('spb_activate_key_nonce', '_wpnonce');

        if (!current_user_can('manage_options')) wp_die('Permission denied');

        $id = intval($_POST['key_id'] ?? 0);
        if ($id) {
            $this->activate_key($id);
        }

        wp_redirect(admin_url('tools.php?page=spb-page-builder&tab=api_keys&spb_message=' . urlencode("API Key activated.")));
        exit;
    }




    private function render_activity_log_tab()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'spb_api_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table ORDER BY timestamp DESC LIMIT 50");

        echo '<h2>Recent API Activity</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Time</th><th>API Key ID</th><th>Endpoint</th><th>Status</th><th>IP</th></tr></thead><tbody>';
        foreach ($logs as $log) {
            echo '<tr>';
            echo '<td>' . esc_html($log->timestamp) . '</td>';
            echo '<td>' . esc_html($log->api_key_id) . '</td>';
            echo '<td>' . esc_html($log->endpoint) . '</td>';
            echo '<td>' . esc_html($log->status) . '</td>';
            echo '<td>' . esc_html($log->ip_address) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    private function render_created_pages_tab()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'spb_page_logs';
        $pages = $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC LIMIT 50");

        echo '<h2>Pages Created via API</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr><th>Title</th><th>URL</th><th>Created At</th><th>Created By</th></tr></thead><tbody>';
        foreach ($pages as $p) {
            $title = get_the_title($p->post_id);
            $url = get_permalink($p->post_id);
            echo '<tr>';
            echo '<td>' . esc_html($title) . '</td>';
            echo '<td><a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a></td>';
            echo '<td>' . esc_html($p->created_at) . '</td>';
            echo '<td>' . esc_html($p->api_key_name) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }


    private function render_settings_tab()
    {
        $rate = get_option('spb_rate_limit', 100);
        echo '<h2>Settings</h2>';
        echo '<form method="post" action="options.php">';
        settings_fields('spb_settings');
        echo '<label>Rate Limit (requests per hour): <input type="number" name="spb_rate_limit" value="' . esc_attr($rate) . '"></label>';
        submit_button('Save Settings');
        echo '</form>';
    }

    private function render_documentation_tab()
    {
        echo '<h2>API Documentation</h2>';
        echo '<p>Endpoint: <code>POST /wp-json/pagebuilder/v1/create-pages</code></p>';
        echo '<p>Headers: <code>X-SPB-API-Key</code>, <code>X-SPB-API-Secret</code></p>';
        echo '<p>Body: JSON array of pages: <code>{ "pages":[{"title":"About","content":"<p>...</p>"}] }</code></p>';
    }
}
