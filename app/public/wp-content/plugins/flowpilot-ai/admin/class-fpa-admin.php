
<?php

if (!defined('ABSPATH')) {
    exit;
}

class FPA_Admin {

    private $page_hook = '';

    public function __construct() {
        add_action('admin_menu', array($this, 'menu'));
        add_action(
            'admin_enqueue_scripts',
            array($this, 'assets')
        );

        add_action(
            'admin_post_fpa_save',
            array($this, 'save')
        );

        add_action(
            'admin_post_fpa_delete',
            array($this, 'delete')
        );
    }

    public function menu() {
        $this->page_hook = add_menu_page(
            'FlowPilot AI',
            'FlowPilot AI',
            'manage_options',
            'flowpilot-ai',
            array($this, 'render'),
            'dashicons-randomize',
            26
        );
    }

    public function assets($hook) {
        if ($hook !== $this->page_hook) {
            return;
        }

        wp_enqueue_style(
            'fpa-admin',
            FPA_URL . 'admin/assets/admin.css',
            array(),
            FPA_VERSION
        );

        wp_enqueue_script(
            'fpa-admin',
            FPA_URL . 'admin/assets/admin.js',
            array(),
            FPA_VERSION,
            true
        );
    }

    private function check_access() {
        if (!current_user_can('manage_options')) {
            wp_die(
                'You do not have permission.',
                'Access denied',
                array('response' => 403)
            );
        }
    }

    public function render() {
        $this->check_access();

        $workflows = FPA_Repository::all();
        $total = FPA_Repository::count();

        $edit_id = isset($_GET['edit'])
            ? absint($_GET['edit'])
            : 0;

        $editing = $edit_id
            ? FPA_Repository::get($edit_id)
            : null;

        $notice = isset($_GET['notice'])
            ? sanitize_key(
                wp_unslash($_GET['notice'])
            )
            : '';

        require FPA_PATH . 'admin/views.php';
    }

    public function save() {
        $this->check_access();

        check_admin_referer('fpa_save_workflow');

        $id = isset($_POST['workflow_id'])
            ? absint($_POST['workflow_id'])
            : 0;

        $name = isset($_POST['name'])
            ? sanitize_text_field(
                wp_unslash($_POST['name'])
            )
            : '';

        $description = isset($_POST['description'])
            ? sanitize_textarea_field(
                wp_unslash($_POST['description'])
            )
            : '';

        $trigger = isset($_POST['trigger_type'])
            ? sanitize_key(
                wp_unslash($_POST['trigger_type'])
            )
            : 'manual';

        $actions = isset($_POST['actions']) &&
            is_array($_POST['actions'])
            ? array_map(
                'sanitize_key',
                wp_unslash($_POST['actions'])
            )
            : array();

        $result = FPA_Repository::save(
            array(
                'name' => $name,
                'description' => $description,
                'trigger_type' => $trigger,
                'actions' => $actions
            ),
            $id
        );

        $notice = is_wp_error($result)
            ? 'error'
            : 'saved';

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' => 'flowpilot-ai',
                    'notice' => $notice
                ),
                admin_url('admin.php')
            )
        );
        exit;
    }

    public function delete() {
        $this->check_access();

        check_admin_referer('fpa_delete_workflow');

        $id = isset($_POST['workflow_id'])
            ? absint($_POST['workflow_id'])
            : 0;

        $deleted = $id
            ? FPA_Repository::delete($id)
            : false;

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' => 'flowpilot-ai',
                    'notice' => $deleted
                        ? 'deleted'
                        : 'error'
                ),
                admin_url('admin.php')
            )
        );
        exit;
    }
}
