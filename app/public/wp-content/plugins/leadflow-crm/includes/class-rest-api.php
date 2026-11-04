
<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPLF_REST_API {

    public static function init() {
        add_action(
            'rest_api_init',
            array(__CLASS__, 'routes')
        );
    }

    public static function routes() {
        register_rest_route(
            'leadflow/v1',
            '/leads',
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array(__CLASS__, 'get_leads'),
                'permission_callback' => array(
                    'WPLF_Permissions',
                    'can_manage'
                )
            )
        );
    }

    public static function get_leads($request) {
        $search = sanitize_text_field(
            $request->get_param('search') ?? ''
        );

        $status = sanitize_key(
            $request->get_param('status') ?? ''
        );

        $page = max(
            1,
            absint($request->get_param('page') ?: 1)
        );

        return rest_ensure_response(
            WPLF_Leads::query($search, $status, $page, 20)
        );
    }
}
