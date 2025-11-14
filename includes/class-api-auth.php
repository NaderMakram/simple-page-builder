<?php

if (!defined('ABSPATH')) {
    exit;
}

class SPB_API_Auth
{

    private $key_manager;
    private $rate_limit; // requests per hour

    public function __construct()
    {
        $this->key_manager = new SPB_API_Key_Manager();

        // Later we will load this from settings
        $this->rate_limit = apply_filters('spb_rate_limit_per_hour', 100);
    }

    /**
     * Validates an incoming API request.
     * Called inside REST endpoint callback before processing.
     */
    public function authenticate_request()
    {
        // 1. Read headers
        $api_key_raw = $this->get_header('X-SPB-API-Key');
        $secret_raw  = $this->get_header('X-SPB-API-Secret');

        if (!$api_key_raw || !$secret_raw) {
            return new WP_Error(
                'spb_missing_headers',
                __('Missing API authentication headers.', 'simple-page-builder'),
                ['status' => 401]
            );
        }

        // 2. Validate key + secret pair
        $key_obj = $this->key_manager->validate_keys($api_key_raw, $secret_raw);

        if (!$key_obj) {
            $this->log_failed_attempt('invalid_credentials');
            return new WP_Error(
                'spb_invalid_credentials',
                __('Invalid API key or secret.', 'simple-page-builder'),
                ['status' => 401]
            );
        }

        // 3. Check rate limiting
        if ($this->is_rate_limited($key_obj)) {
            $this->log_failed_attempt('rate_limit');
            return new WP_Error(
                'spb_rate_limited',
                __('Rate limit exceeded. Try again later.', 'simple-page-builder'),
                ['status' => 429]
            );
        }

        // 4. Success — log it
        $this->log_successful_attempt($key_obj);

        // Return authenticated key object to the API endpoint
        return $key_obj;
    }

    // private helper funcitnos
    // Read a specific header safely

    private function get_header($name)
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];

        if (isset($headers[$name])) {
            return sanitize_text_field($headers[$name]);
        }

        // WordPress fallback
        $name_alt = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($_SERVER[$name_alt]) ? sanitize_text_field($_SERVER[$name_alt]) : null;
    }


    // Rate limit logic: N requests/hour per API key

    private function is_rate_limited($key_obj)
    {
        $hour_ago = strtotime('-1 hour');
        $last_used = strtotime($key_obj->last_used_at ?? '1970-01-01');

        // If last request is within the same hour and count is above limit
        if ($last_used > $hour_ago && $key_obj->request_count >= $this->rate_limit) {
            return true;
        }

        return false;
    }


    // Logging successful authentication

    private function log_successful_attempt($key_obj)
    {
        // We'll build SPB_Logger in a later step
        if (class_exists('SPB_Logger')) {
            SPB_Logger::log('auth_success', [
                'api_key_id' => $key_obj->id,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
        }
    }


    // Logging failed authentication

    private function log_failed_attempt($reason)
    {
        if (class_exists('SPB_Logger')) {
            SPB_Logger::log('auth_failed', [
                'reason' => $reason,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
        }
    }
}