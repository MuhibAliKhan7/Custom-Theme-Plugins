
<?php

if (!defined('ABSPATH')) {
    exit;
}

class FPA_Install {

    public static function activate() {
        global $wpdb;

        $charset = $wpdb->get_charset_collate();

        $workflows = $wpdb->prefix . 'fpa_workflows';
        $runs = $wpdb->prefix . 'fpa_runs';

        $sql_workflows = "CREATE TABLE $workflows (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(190) NOT NULL,
            description text NOT NULL,
            trigger_type varchar(60) NOT NULL DEFAULT 'manual',
            actions_json longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'draft',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY trigger_type (trigger_type)
        ) $charset;";

        $sql_runs = "CREATE TABLE $runs (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            workflow_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            context_json longtext NULL,
            started_at datetime NOT NULL,
            finished_at datetime NULL,
            PRIMARY KEY  (id),
            KEY workflow_id (workflow_id),
            KEY status (status)
        ) $charset;";

        require_once ABSPATH .
            'wp-admin/includes/upgrade.php';

        dbDelta($sql_workflows);
        dbDelta($sql_runs);

        update_option('fpa_db_version', FPA_VERSION);
    }
}
