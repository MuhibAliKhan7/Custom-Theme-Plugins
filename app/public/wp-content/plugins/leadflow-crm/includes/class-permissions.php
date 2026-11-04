
<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPLF_Permissions {

    public static function can_manage() {
        return current_user_can('manage_options');
    }

    public static function check() {
        if (!self::can_manage()) {
            wp_die(
                esc_html__('Permission denied.', 'wp-leadflow-crm'),
                '',
                array('response' => 403)
            );
        }
    }
}
