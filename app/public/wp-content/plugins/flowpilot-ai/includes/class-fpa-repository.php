
<?php

if (!defined('ABSPATH')) {
    exit;
}

class FPA_Repository {

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'fpa_workflows';
    }

    public static function triggers() {
        return array(
            'manual' => 'Manual Trigger',
            'form_submitted' => 'Form Submitted (planned)',
            'lead_created' => 'Lead Created (planned)',
            'webhook' => 'Incoming Webhook (planned)'
        );
    }

    public static function actions() {
        return array(
            'ai_qualify' => 'AI Lead Qualification',
            'create_crm_lead' => 'Create CRM Lead',
            'send_email' => 'Send Email',
            'notify_admin' => 'Notify Administrator',
            'webhook' => 'Send Webhook'
        );
    }

    public static function all() {
        global $wpdb;

        $table = self::table();

        return $wpdb->get_results(
            "SELECT * FROM $table ORDER BY updated_at DESC, id DESC"
        );
    }

    public static function get($id) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM " . self::table() .
                " WHERE id = %d",
                absint($id)
            )
        );
    }

    public static function count() {
        global $wpdb;

        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM " . self::table()
        );
    }

    public static function validate($input) {
        $name = sanitize_text_field(
            $input['name'] ?? ''
        );

        $description = sanitize_textarea_field(
            $input['description'] ?? ''
        );

        $trigger = sanitize_key(
            $input['trigger_type'] ?? 'manual'
        );

        $raw_actions = $input['actions'] ?? array();

        if (trim($name) === '' || strlen($name) > 190) {
            return new WP_Error(
                'invalid_name',
                'Enter a workflow name (maximum 190 characters).'
            );
        }

        if (!array_key_exists($trigger, self::triggers())) {
            return new WP_Error(
                'invalid_trigger',
                'Select a valid workflow trigger.'
            );
        }

        if (!is_array($raw_actions)) {
            return new WP_Error(
                'invalid_actions',
                'Workflow actions must be an array.'
            );
        }

        $actions = array();

        foreach ($raw_actions as $action) {
            if (!is_scalar($action)) {
                return new WP_Error(
                    'invalid_action',
                    'Invalid action format.'
                );
            }

            $action = sanitize_key((string) $action);

            if (!array_key_exists($action, self::actions())) {
                return new WP_Error(
                    'invalid_action',
                    'One of the selected actions is invalid.'
                );
            }

            $actions[] = array(
                'id' => wp_generate_uuid4(),
                'type' => $action,
                'config' => array()
            );
        }

        if (count($actions) > 20) {
            return new WP_Error(
                'too_many_actions',
                'Maximum 20 actions per workflow.'
            );
        }

        return array(
            'name' => $name,
            'description' => $description,
            'trigger_type' => $trigger,
            'actions_json' => wp_json_encode($actions),
            'status' => 'draft'
        );
    }

    public static function save($input, $id = 0) {
        global $wpdb;

        $data = self::validate($input);

        if (is_wp_error($data)) {
            return $data;
        }

        $now = current_time('mysql');
        $table = self::table();

        if ($id > 0) {
            if (!self::get($id)) {
                return new WP_Error(
                    'not_found',
                    'Workflow not found.'
                );
            }

            $data['updated_at'] = $now;

            $result = $wpdb->update(
                $table,
                $data,
                array('id' => absint($id)),
                array('%s','%s','%s','%s','%s','%s'),
                array('%d')
            );

            return $result === false
                ? new WP_Error('db_error', 'Update failed.')
                : $id;
        }

        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        $result = $wpdb->insert(
            $table,
            $data,
            array('%s','%s','%s','%s','%s','%s','%s')
        );

        return $result === false
            ? new WP_Error('db_error', 'Creation failed.')
            : (int) $wpdb->insert_id;
    }

    public static function delete($id) {
        global $wpdb;

        $result = $wpdb->delete(
            self::table(),
            array('id' => absint($id)),
            array('%d')
        );

        return $result !== false && $result > 0;
    }

    public static function decode_actions($workflow) {
        if (!$workflow) {
            return array();
        }

        $actions = json_decode(
            $workflow->actions_json,
            true
        );

        return is_array($actions) ? $actions : array();
    }
}
