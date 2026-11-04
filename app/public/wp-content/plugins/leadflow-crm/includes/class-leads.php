
<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPLF_Leads {

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'wplf_leads';
    }

    public static function statuses() {
        return array(
            'new'       => 'New',
            'contacted' => 'Contacted',
            'qualified' => 'Qualified',
            'proposal'  => 'Proposal',
            'won'       => 'Won',
            'lost'      => 'Lost'
        );
    }

    public static function validate($data) {
        $name = sanitize_text_field($data['name'] ?? '');

        $raw_email = trim((string) ($data['email'] ?? ''));
        $email = sanitize_email($raw_email);

        $status = sanitize_key($data['status'] ?? 'new');

        if ($name === '' || strlen($name) > 190) {
            return new WP_Error(
                'invalid_name',
                'Enter a valid name of 190 characters or fewer.'
            );
        }

        if ($raw_email !== '' &&
            (!is_email($raw_email) || strlen($email) > 190)) {
            return new WP_Error(
                'invalid_email',
                'Enter a valid email address.'
            );
        }

        if (!array_key_exists($status, self::statuses())) {
            return new WP_Error(
                'invalid_status',
                'Invalid lead status.'
            );
        }

        $phone = sanitize_text_field($data['phone'] ?? '');
        $company = sanitize_text_field($data['company'] ?? '');

        if (strlen($phone) > 50 || strlen($company) > 190) {
            return new WP_Error(
                'invalid_field',
                'Phone or company is too long.'
            );
        }

        return array(
            'name'    => $name,
            'email'   => $email,
            'phone'   => $phone,
            'company' => $company,
            'status'  => $status
        );
    }

    public static function create($data) {
        global $wpdb;

        $valid = self::validate($data);

        if (is_wp_error($valid)) {
            return $valid;
        }

        $valid['created_at'] = current_time('mysql');

        $result = $wpdb->insert(
            self::table(),
            $valid,
            array('%s','%s','%s','%s','%s','%s')
        );

        return $result === false
            ? new WP_Error('db_error', 'Could not save lead.')
            : $wpdb->insert_id;
    }

    public static function update($id, $data) {
        global $wpdb;

        if (!self::get($id)) {
            return new WP_Error('not_found', 'Lead not found.');
        }

        $valid = self::validate($data);

        if (is_wp_error($valid)) {
            return $valid;
        }

        $result = $wpdb->update(
            self::table(),
            $valid,
            array('id' => absint($id)),
            array('%s','%s','%s','%s','%s'),
            array('%d')
        );

        return $result === false
            ? new WP_Error('db_error', 'Could not update lead.')
            : true;
    }

    public static function change_status($id, $status) {
        global $wpdb;

        if (!self::get($id)) {
            return new WP_Error('not_found', 'Lead not found.');
        }

        $status = sanitize_key($status);

        if (!array_key_exists($status, self::statuses())) {
            return new WP_Error('invalid_status', 'Invalid status.');
        }

        $result = $wpdb->update(
            self::table(),
            array('status' => $status),
            array('id' => absint($id)),
            array('%s'),
            array('%d')
        );

        return $result === false
            ? new WP_Error('db_error', 'Update failed.')
            : true;
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

    public static function query($search = '', $status = '',
                                 $page = 1, $per_page = 10) {
        global $wpdb;

        $table = self::table();
        $conditions = array();
        $params = array();

        if ($search !== '') {
            $like = '%' . $wpdb->esc_like($search) . '%';

            $conditions[] = "(
                name LIKE %s OR
                email LIKE %s OR
                phone LIKE %s OR
                company LIKE %s
            )";

            array_push($params, $like, $like, $like, $like);
        }

        if (array_key_exists($status, self::statuses())) {
            $conditions[] = 'status = %s';
            $params[] = $status;
        }

        $where = $conditions
            ? ' WHERE ' . implode(' AND ', $conditions)
            : '';

        $count_sql = "SELECT COUNT(*) FROM $table $where";

        if ($params) {
            $count_sql = $wpdb->prepare($count_sql, $params);
        }

        $total = (int) $wpdb->get_var($count_sql);

        $page = max(1, (int) $page);
        $per_page = max(1, min(100, (int) $per_page));
        $offset = ($page - 1) * $per_page;

        $sql = "SELECT * FROM $table $where
                ORDER BY id DESC LIMIT %d OFFSET %d";

        $query_params = array_merge(
            $params,
            array($per_page, $offset)
        );

        $items = $wpdb->get_results(
            $wpdb->prepare($sql, $query_params)
        );

        return array(
            'items' => $items,
            'total' => $total,
            'pages' => (int) ceil($total / $per_page)
        );
    }

    public static function stats() {
        global $wpdb;

        $table = self::table();

        $rows = $wpdb->get_results(
            "SELECT status, COUNT(*) AS total
             FROM $table GROUP BY status"
        );

        $counts = array_fill_keys(
            array_keys(self::statuses()),
            0
        );

        foreach ($rows as $row) {
            if (isset($counts[$row->status])) {
                $counts[$row->status] = (int) $row->total;
            }
        }

        $total = array_sum($counts);

        return array(
            'total' => $total,
            'counts' => $counts,
            'conversion' => $total > 0
                ? round(($counts['won'] / $total) * 100, 1)
                : 0
        );
    }
}
