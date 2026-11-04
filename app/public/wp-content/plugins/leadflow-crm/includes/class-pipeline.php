
<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPLF_Pipeline {

    private $page_hook = '';

    public function __construct() {
        add_action(
            'admin_menu',
            array($this, 'register_menu'),
            20
        );

        add_action(
            'admin_enqueue_scripts',
            array($this, 'enqueue_assets')
        );

        add_action(
            'rest_api_init',
            array($this, 'register_routes')
        );

        add_action(
            'admin_post_wplf_pipeline_status',
            array($this, 'handle_status_form')
        );
    }

    public function register_menu() {
        $this->page_hook = add_submenu_page(
            'wplf-crm',
            'Sales Pipeline',
            'Sales Pipeline',
            'manage_options',
            'wplf-pipeline',
            array($this, 'render')
        );
    }

    public function enqueue_assets($hook) {
        if ($hook !== $this->page_hook) {
            return;
        }

        wp_enqueue_style(
            'wplf-admin',
            WPLF_URL . 'assets/css/admin.css',
            array(),
            WPLF_VERSION
        );

        wp_enqueue_style(
            'wplf-pipeline',
            WPLF_URL . 'assets/css/pipeline.css',
            array('wplf-admin'),
            WPLF_VERSION
        );

        wp_enqueue_script(
            'wplf-pipeline',
            WPLF_URL . 'assets/js/pipeline.js',
            array(),
            WPLF_VERSION,
            true
        );

        wp_add_inline_script(
            'wplf-pipeline',
            'const wplfPipelineSettings = ' .
            wp_json_encode(
                array(
                    'restUrl' => esc_url_raw(
                        rest_url('leadflow/v1/pipeline/')
                    ),
                    'nonce' => wp_create_nonce('wp_rest')
                )
            ) . ';',
            'before'
        );
    }

    public function render() {
        WPLF_Permissions::check();

        // Retrieve all existing lead records in batches.
        // This is suitable for a local portfolio demo.
        $leads = array();
        $page = 1;

        do {
            $batch = WPLF_Leads::query('', '', $page, 100);

            foreach ($batch['items'] as $lead) {
                $leads[] = $lead;
            }

            $page++;
        } while ($page <= $batch['pages']);

        $statuses = WPLF_Leads::statuses();

        require WPLF_PATH . 'admin/views/pipeline.php';
    }

    public function register_routes() {
        register_rest_route(
            'leadflow/v1',
            '/pipeline/(?P<id>\d+)/status',
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array(
                    $this,
                    'update_status'
                ),
                'permission_callback' => array(
                    'WPLF_Permissions',
                    'can_manage'
                ),
                'args' => array(
                    'id' => array(
                        'required' => true,
                        'validate_callback' => 'is_numeric'
                    ),
                    'status' => array(
                        'required' => true,
                        'type' => 'string',
                        'enum' => array_keys(
                            WPLF_Leads::statuses()
                        )
                    )
                )
            )
        );
    }

    public function update_status($request) {
        $id = absint($request['id']);
        $status = sanitize_key($request['status']);

        $result = WPLF_Leads::change_status(
            $id,
            $status
        );

        if (is_wp_error($result)) {
            return new WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                array('status' => 400)
            );
        }

        return rest_ensure_response(
            array(
                'success' => true,
                'id' => $id,
                'status' => $status,
                'message' => 'Lead stage updated.'
            )
        );
    }

    // Standard form fallback for keyboard users.
    public function handle_status_form() {
        WPLF_Permissions::check();

        check_admin_referer(
            'wplf_pipeline_status'
        );

        $id = isset($_POST['lead_id'])
            ? absint($_POST['lead_id'])
            : 0;

        $status = isset($_POST['status'])
            ? sanitize_key(
                wp_unslash($_POST['status'])
            )
            : '';

        $result = WPLF_Leads::change_status(
            $id,
            $status
        );

        $notice = is_wp_error($result)
            ? 'error'
            : 'saved';

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' => 'wplf-pipeline',
                    'notice' => $notice
                ),
                admin_url('admin.php')
            )
        );

        exit;
    }
}
