
<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPLF_Admin {

    private $pages = array();

    public function __construct() {
        add_action('admin_menu', array($this, 'menus'));
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('admin_post_wplf_save', array($this, 'save'));
        add_action('admin_post_wplf_delete', array($this, 'delete'));
    }

    public function menus() {
        $this->pages[] = add_menu_page(
            'LeadFlow CRM',
            'LeadFlow CRM',
            'manage_options',
            'wplf-crm',
            array($this, 'dashboard'),
            'dashicons-chart-area',
            25
        );

        $this->pages[] = add_submenu_page(
            'wplf-crm',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'wplf-crm',
            array($this, 'dashboard')
        );

        $this->pages[] = add_submenu_page(
            'wplf-crm',
            'All Leads',
            'All Leads',
            'manage_options',
            'wplf-leads',
            array($this, 'leads')
        );
    }

    public function assets($hook) {
        if (!in_array($hook, $this->pages, true)) {
            return;
        }

        // Existing dashboard and admin styles.
        wp_enqueue_style(
            'wplf-admin',
            WPLF_URL . 'assets/css/admin.css',
            array(),
            WPLF_VERSION
        );

        // Phase 4 styles load after the original styles.
        wp_enqueue_style(
            'wplf-phase4',
            WPLF_URL . 'assets/css/phase4.css',
            array('wplf-admin'),
            WPLF_VERSION
        );

        wp_enqueue_script(
            'wplf-admin',
            WPLF_URL . 'assets/js/admin.js',
            array(),
            WPLF_VERSION,
            true
        );
    }

    public function dashboard() {
        WPLF_Permissions::check();

        $stats = WPLF_Leads::stats();
        $recent = WPLF_Leads::query('', '', 1, 5);

        require WPLF_PATH . 'admin/views/dashboard.php';
    }

    public function leads() {
        WPLF_Permissions::check();

        $search = isset($_GET['s'])
            ? sanitize_text_field(wp_unslash($_GET['s']))
            : '';

        $status = isset($_GET['status'])
            ? sanitize_key(wp_unslash($_GET['status']))
            : '';

        if (!array_key_exists($status, WPLF_Leads::statuses())) {
            $status = '';
        }

        $page = isset($_GET['paged'])
            ? max(1, absint($_GET['paged']))
            : 1;

        $result = WPLF_Leads::query(
            $search,
            $status,
            $page,
            10
        );

        $edit_id = isset($_GET['edit'])
            ? absint($_GET['edit'])
            : 0;

        $editing = $edit_id
            ? WPLF_Leads::get($edit_id)
            : null;

        require WPLF_PATH . 'admin/views/leads.php';
    }

    public function save() {
        WPLF_Permissions::check();
        check_admin_referer('wplf_save_lead');

        $id = isset($_POST['lead_id'])
            ? absint($_POST['lead_id'])
            : 0;

        $data = array();

        foreach (
            array('name', 'email', 'phone', 'company', 'status')
            as $field
        ) {
            $data[$field] = isset($_POST[$field])
                ? sanitize_text_field(
                    wp_unslash($_POST[$field])
                )
                : '';
        }

        $result = $id
            ? WPLF_Leads::update($id, $data)
            : WPLF_Leads::create($data);

        $notice = is_wp_error($result)
            ? 'error'
            : 'saved';

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' => 'wplf-leads',
                    'notice' => $notice
                ),
                admin_url('admin.php')
            )
        );

        exit;
    }

    public function delete() {
        WPLF_Permissions::check();
        check_admin_referer('wplf_delete_lead');

        $id = isset($_POST['lead_id'])
            ? absint($_POST['lead_id'])
            : 0;

        $deleted = $id
            ? WPLF_Leads::delete($id)
            : false;

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' => 'wplf-leads',
                    'notice' => $deleted ? 'deleted' : 'error'
                ),
                admin_url('admin.php')
            )
        );

        exit;
    }
}
