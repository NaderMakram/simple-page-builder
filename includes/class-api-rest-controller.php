<?php

if (!defined('ABSPATH')) {
    exit;
}


class SPB_API_REST_Controller
{

    public function register_routes()
    {

        register_rest_route('pagebuilder/v1', '/create-pages', [
            'methods'  => 'POST',
            'callback' => [$this, 'handle_create_pages'],
            'permission_callback' => [$this, 'permission_check'],
        ]);
    }

    /**
     * Run authentication here using SPB_API_Auth
     */
    public function permission_check($request)
    {
        $auth = new SPB_API_Auth();
        $result = $auth->authenticate_request();

        if (is_wp_error($result)) {
            return $result;
        }

        // Store the key object inside the request — important!
        $request->set_param('_spb_api_key_obj', $result);

        return true;
    }

    /**
     * Main endpoint logic for creating pages
     */
    public function handle_create_pages($request)
    {
        $body = $request->get_json_params();

        if (empty($body['pages']) || !is_array($body['pages'])) {
            return new WP_Error(
                'spb_invalid_payload',
                __('Invalid request payload. "pages" must be an array.', 'simple-page-builder'),
                ['status' => 400]
            );
        }

        $created = [];
        $api_key_obj = $request->get_param('_spb_api_key_obj');

        foreach ($body['pages'] as $page_data) {
            if (empty($page_data['title'])) {
                continue; // skip invalid
            }

            $postarr = [
                'post_title'   => sanitize_text_field($page_data['title']),
                'post_content' => wp_kses_post($page_data['content'] ?? ''),
                'post_status'  => 'publish',
                'post_type'    => 'page'
            ];

            $post_id = wp_insert_post($postarr);

            if (is_wp_error($post_id)) {
                continue;
            }

            $created[] = [
                'id'    => $post_id,
                'title' => get_the_title($post_id),
                'url'   => get_permalink($post_id),
            ];

            // Save info for Admin → Created Pages tab
            SPB_Logger::log_page_created($post_id, $api_key_obj->key_name);
        }

        $response = [
            'request_id' => 'req_' . wp_generate_uuid4(),
            'total'      => count($created),
            'pages'      => $created,
        ];


        return $response;
    }
}
