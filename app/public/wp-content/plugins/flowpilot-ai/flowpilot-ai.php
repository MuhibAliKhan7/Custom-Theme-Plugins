
<?php
/**
 * Plugin Name: FlowPilot AI
 * Description: Visual workflow automation platform for WordPress.
 * Version: 0.4.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: Muhib Ali Khan
 * Text Domain: flowpilot-ai
 */

if (!defined('ABSPATH')) {
    exit;
}

define('FPA_VERSION', '0.4.0');
define('FPA_PATH', plugin_dir_path(__FILE__));
define('FPA_URL', plugin_dir_url(__FILE__));

require_once FPA_PATH . 'includes/class-fpa-install.php';
require_once FPA_PATH . 'includes/class-fpa-repository.php';
require_once FPA_PATH . 'includes/class-fpa-builder.php';
require_once FPA_PATH . 'includes/class-fpa-engine.php';
require_once FPA_PATH . 'includes/class-fpa-webhooks.php';
require_once FPA_PATH . 'admin/class-fpa-admin.php';
require_once FPA_PATH . 'admin/class-fpa-runs.php';

register_activation_hook(__FILE__, function () {
    FPA_Install::activate();
    FPA_Builder::install();
    FPA_Webhooks::install();
});

add_action('admin_init', function () {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (get_option('fpa_db_version') !== FPA_VERSION) {
        FPA_Install::activate();
    }

    FPA_Builder::maybe_install();
    FPA_Webhooks::maybe_install();
});

add_action('plugins_loaded', function () {
    if (is_admin()) {
        new FPA_Admin();
        new FPA_Runs();
    }

    new FPA_Builder();
    new FPA_Webhooks();
});
