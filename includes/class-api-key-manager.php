<?php

if (!defined('ABSPATH')) {
    exit;
}

class SPB_API_Key_Manager
{

    private $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'spb_api_keys';
    }

    /**
     * Generate a new API key + secret, store hashed versions
     */
    public function generate_key($key_name, $expires_at = null)
    {
        global $wpdb;

        // Generate raw key values
        $api_key = bin2hex(random_bytes(32));    // 64 chars
        $secret_key = bin2hex(random_bytes(32)); // 64 chars

        // Hash them (never store raw keys)
        $api_hash = wp_hash_password($api_key);
        $secret_hash = wp_hash_password($secret_key);

        $api_key_preview = substr($api_key, 0, 8);


        $wpdb->insert(
            $this->table,
            [
                'key_name'     => $key_name,
                'api_key_hash' => $api_hash,
                'secret_hash'  => $secret_hash,
                'api_key_preview' => $api_key_preview,
                'status'       => 'active',
                'permissions'  => json_encode(['create_pages']),
                'created_at'   => current_time('mysql'),
                'expires_at'   => $expires_at,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        $id = $wpdb->insert_id;

        return [
            'id'         => $id,
            'api_key'    => $api_key,
            'secret_key' => $secret_key,
        ];
    }

    /**
     * Get all keys (for admin table)
     */
    public function get_all_keys()
    {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM {$this->table} ORDER BY created_at DESC");
    }

    /**
     * Mark key as revoked
     */
    public function revoke_key($id)
    {
        global $wpdb;
        return $wpdb->update(
            $this->table,
            ['status' => 'revoked'],
            ['id' => $id],
            ['%s'],
            ['%d']
        );
    }

    /**
     * Validate API + Secret key pair
     */
    public function validate_keys($api_key_raw, $secret_key_raw)
    {

        global $wpdb;

        $preview = substr($api_key_raw, 0, 8);
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} WHERE status = 'active' AND api_key_preview = %s",
                $preview
            )
        );

        if (!$row) {
            return false;
        }

        $api_valid = wp_check_password($api_key_raw, $row->api_key_hash);
        $secret_valid = wp_check_password($secret_key_raw, $row->secret_hash);

        if (!$api_valid || !$secret_valid) {
            return false;
        }

        // Check expiration
        if ($row->expires_at && strtotime($row->expires_at) < time()) {
            return false;
        }

        // Update usage
        $wpdb->update(
            $this->table,
            [
                'last_used_at' => current_time('mysql'),
                'request_count' => $row->request_count + 1
            ],
            ['id' => $row->id],
            ['%s', '%d'],
            ['%d']
        );

        return $row;
    }
}
