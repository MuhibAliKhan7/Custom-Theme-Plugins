
<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPLF_Activator {

    public static function activate() {
        global $wpdb;

        $table = $wpdb->prefix . 'wplf_leads';
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(190) NOT NULL,
            email varchar(190) NOT NULL DEFAULT '',
            phone varchar(50) NOT NULL DEFAULT '',
            company varchar(190) NOT NULL DEFAULT '',
            status varchar(30) NOT NULL DEFAULT 'new',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY status (status),
            KEY email (email)
        ) $charset;";

        require_once ABSPATH .
            'wp-admin/includes/upgrade.php';

        dbDelta($sql);

        update_option('wplf_db_version', WPLF_VERSION);
    }
}
