
<?php
/**
 * Plugin Name: WP LeadFlow CRM
 * Description: Custom WordPress CRM with lead management and Kanban pipeline.
 * Version: 0.5.0
 * Requires PHP: 8.0
 * Author: Muhib Ali Khan
 * Text Domain: wp-leadflow-crm
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPLF_VERSION', '0.5.0');
define('WPLF_PATH', plugin_dir_path(__FILE__));
define('WPLF_URL', plugin_dir_url(__FILE__));

require_once WPLF_PATH . 'includes/class-activator.php';
require_once WPLF_PATH . 'includes/class-permissions.php';
require_once WPLF_PATH . 'includes/class-leads.php';
require_once WPLF_PATH . 'includes/class-rest-api.php';
require_once WPLF_PATH . 'includes/class-pipeline.php';
require_once WPLF_PATH . 'admin/class-admin.php';

register_activation_hook(
    __FILE__,
    array('WPLF_Activator', 'activate')
);

add_action('admin_init', function () {
    if (
        get_option('wplf_db_version') !== WPLF_VERSION &&
        current_user_can('manage_options')
    ) {
        WPLF_Activator::activate();
    }
});

add_action('plugins_loaded', function () {
    new WPLF_Admin();
    new WPLF_Pipeline();
    WPLF_REST_API::init();
});
