
<?php
if (!defined('ABSPATH')) {
    exit;
}

class FPA_Webhooks {

    const VERSION = '0.4.0';
    const MAX_BODY = 65536;
    const MAX_AGE = 300;

    public function __construct() {
        add_action('rest_api_init', array($this, 'routes'));

        add_action(
            'fpa_process_webhook',
            array($this, 'process_job'),
            10,
            1
        );

        add_action('admin_menu', array($this, 'menu'), 40);

        add_action(
            'admin_post_fpa_rotate_webhook',
            array($this, 'rotate_secret')
        );
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'fpa_webhook_jobs';
    }

    public static function maybe_install() {
        if (get_option('fpa_webhook_db_version') !== self::VERSION) {
            self::install();
        }
    }

    public static function install() {
        global $wpdb;

        $table = self::table();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            workflow_id bigint(20) unsigned NOT NULL,
            event_id varchar(128) NOT NULL,
            body_hash char(64) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'queued',
            run_id bigint(20) unsigned NOT NULL DEFAULT 0,
            last_error text NULL,
            created_at datetime NOT NULL,
            processed_at datetime NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY workflow_event (workflow_id,event_id),
            KEY status (status),
            KEY workflow_id (workflow_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('fpa_webhook_db_version', self::VERSION);
    }

    private static function secret_option($id) {
        return 'fpa_webhook_secret_' . absint($id);
    }

    public static function get_secret($id) {
        $value = get_option(self::secret_option($id), '');
        return is_string($value) ? $value : '';
    }

    public static function endpoint($id) {
        return rest_url('flowpilot/v1/hooks/' . absint($id));
    }

    public function routes() {
        register_rest_route(
            'flowpilot/v1',
            '/hooks/(?P<id>\d+)',
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'receive'),
                // The endpoint is reachable without WP login.
                // HMAC authentication happens in receive().
                'permission_callback' => '__return_true'
            )
        );
    }

    private function error($code, $message, $status) {
        return new WP_Error(
            $code,
            $message,
            array('status' => $status)
        );
    }

    public function receive($request) {
        global $wpdb;

        $id = absint($request['id']);
        $workflow = FPA_Repository::get($id);

        if (!$workflow || $workflow->trigger_type !== 'webhook') {
            return $this->error(
                'fpa_not_found',
                'Webhook unavailable.',
                404
            );
        }

        $secret = self::get_secret($id);

        if ($secret === '') {
            return $this->error(
                'fpa_disabled',
                'Webhook is not configured.',
                403
            );
        }

        $body = $request->get_body();

        if (strlen($body) > self::MAX_BODY) {
            return $this->error(
                'fpa_too_large',
                'Payload exceeds 64 KB.',
                413
            );
        }

        $timestamp = $request->get_header(
            'x-flowpilot-timestamp'
        );

        $event_id = $request->get_header(
            'x-flowpilot-event-id'
        );

        $signature = $request->get_header(
            'x-flowpilot-signature'
        );

        if (
            !is_string($timestamp) ||
            !preg_match('/^\d{10,11}$/', $timestamp) ||
            abs(time() - (int) $timestamp) > self::MAX_AGE
        ) {
            return $this->error(
                'fpa_timestamp',
                'Invalid or expired timestamp.',
                401
            );
        }

        if (
            !is_string($event_id) ||
            !preg_match(
                '/^[A-Za-z0-9_.:-]{1,128}$/D',
                $event_id
            )
        ) {
            return $this->error(
                'fpa_event_id',
                'Invalid event ID.',
                400
            );
        }

        // Sign the exact raw request body, not decoded JSON.
        $message = $timestamp . '.' . $event_id . '.' . $body;

        $expected = 'sha256=' . hash_hmac(
            'sha256',
            $message,
            $secret
        );

        if (
            !is_string($signature) ||
            !hash_equals($expected, $signature)
        ) {
            return $this->error(
                'fpa_signature',
                'Invalid signature.',
                401
            );
        }

        $payload = json_decode($body, true, 16);

        if (
            json_last_error() !== JSON_ERROR_NONE ||
            !is_array($payload)
        ) {
            return $this->error(
                'fpa_json',
                'Valid JSON object or array required.',
                400
            );
        }

        // Validate before inserting the job.
        $graph = FPA_Engine::load_graph($id);

        if (is_wp_error($graph)) {
            return $this->error(
                'fpa_graph',
                'Save your workflow graph first.',
                422
            );
        }

        $plan = FPA_Engine::execution_plan($graph);

        if (is_wp_error($plan)) {
            return $this->error(
                'fpa_graph_invalid',
                $plan->get_error_message(),
                422
            );
        }

        // The saved graph must have a webhook trigger.
        $trigger = null;

        foreach ($plan as $node) {
            if ($node['kind'] === 'trigger') {
                $trigger = $node;
                break;
            }
        }

        if (
            !$trigger ||
            $trigger['type'] !== 'webhook'
        ) {
            return $this->error(
                'fpa_wrong_trigger',
                'The graph must use a Webhook trigger.',
                422
            );
        }

        // Best-effort per-workflow admission limit.
        // The unique database key provides authoritative
        // event replay protection.
        $rate_key = 'fpa_hook_rate_' . $id;
        $rate_count = (int) get_transient($rate_key);

        if ($rate_count >= 30) {
            return $this->error(
                'fpa_rate_limit',
                'Webhook rate limit exceeded.',
                429
            );
        }

        set_transient($rate_key, $rate_count + 1, 60);

        // Store a checksum, not the incoming payload.
        $inserted = $wpdb->insert(
            self::table(),
            array(
                'workflow_id' => $id,
                'event_id' => $event_id,
                'body_hash' => hash('sha256', $body),
                'status' => 'queued',
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s')
        );

        if ($inserted === false) {
            // Check unique event identity.
            $existing = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT id FROM " . self::table() .
                    " WHERE workflow_id = %d AND event_id = %s",
                    $id,
                    $event_id
                )
            );

            if ($existing) {
                return $this->error(
                    'fpa_duplicate',
                    'Event already received.',
                    409
                );
            }

            return $this->error(
                'fpa_database',
                'Could not queue event.',
                500
            );
        }

        $job_id = (int) $wpdb->insert_id;

        $scheduled = wp_schedule_single_event(
            time() + 5,
            'fpa_process_webhook',
            array($job_id),
            true
        );

        if (is_wp_error($scheduled) || !$scheduled) {
            $wpdb->update(
                self::table(),
                array(
                    'status' => 'failed',
                    'last_error' => 'Job scheduling failed.'
                ),
                array('id' => $job_id),
                array('%s', '%s'),
                array('%d')
            );

            return $this->error(
                'fpa_schedule',
                'Unable to schedule job.',
                503
            );
        }

        return new WP_REST_Response(
            array(
                'accepted' => true,
                'job_id' => $job_id,
                'status' => 'queued'
            ),
            202
        );
    }

    public function process_job($job_id) {
        global $wpdb;

        $job_id = absint($job_id);

        if (!$job_id) {
            return;
        }

        $table = self::table();

        // Atomic claim: only one worker can change
        // queued -> processing.
        $claimed = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $table
                 SET status = %s
                 WHERE id = %d AND status = %s",
                'processing',
                $job_id,
                'queued'
            )
        );

        if ($claimed !== 1) {
            return;
        }

        $job = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                $job_id
            )
        );

        if (!$job) {
            return;
        }

        try {
            // Phase 3 engine is simulation-only.
            // It creates a separate run-history record.
            $result = FPA_Engine::run(
                (int) $job->workflow_id
            );

            if (is_wp_error($result)) {
                throw new RuntimeException(
                    $result->get_error_message()
                );
            }

            $wpdb->update(
                $table,
                array(
                    'status' => 'completed',
                    'run_id' => (int) $result,
                    'processed_at' => current_time('mysql')
                ),
                array('id' => $job_id),
                array('%s', '%d', '%s'),
                array('%d')
            );

        } catch (Throwable $exception) {
            $wpdb->update(
                $table,
                array(
                    'status' => 'failed',
                    'last_error' => substr(
                        $exception->getMessage(),
                        0,
                        500
                    ),
                    'processed_at' => current_time('mysql')
                ),
                array('id' => $job_id),
                array('%s', '%s', '%s'),
                array('%d')
            );

            error_log(
                'FlowPilot webhook job ' . $job_id .
                ' failed: ' . $exception->getMessage()
            );
        }
    }

    public function menu() {
        add_submenu_page(
            'flowpilot-ai',
            'Webhook Triggers',
            'Webhook Triggers',
            'manage_options',
            'fpa-webhooks',
            array($this, 'render')
        );
    }

    public function rotate_secret() {
        if (!current_user_can('manage_options')) {
            wp_die('Permission denied.');
        }

        check_admin_referer('fpa_rotate_secret');

        $id = isset($_POST['workflow_id'])
            ? absint($_POST['workflow_id'])
            : 0;

        $workflow = FPA_Repository::get($id);

        if (!$workflow || $workflow->trigger_type !== 'webhook') {
            wp_die('Invalid webhook workflow.');
        }

        // Cryptographically random 32-byte secret.
        $secret = bin2hex(random_bytes(32));

        update_option(
            self::secret_option($id),
            $secret,
            false
        );

        wp_safe_redirect(
            add_query_arg(
                array(
                    'page' => 'fpa-webhooks',
                    'workflow_id' => $id,
                    'notice' => 'rotated'
                ),
                admin_url('admin.php')
            )
        );

        exit;
    }

    public function render() {
        global $wpdb;

        if (!current_user_can('manage_options')) {
            wp_die('Permission denied.');
        }

        $workflows = array_filter(
            FPA_Repository::all(),
            function ($workflow) {
                return $workflow->trigger_type === 'webhook';
            }
        );

        $selected = isset($_GET['workflow_id'])
            ? absint($_GET['workflow_id'])
            : 0;

        if (!$selected && $workflows) {
            $first = reset($workflows);
            $selected = (int) $first->id;
        }

        $workflow = FPA_Repository::get($selected);

        if ($workflow && $workflow->trigger_type !== 'webhook') {
            $workflow = null;
        }

        $secret = $workflow
            ? self::get_secret($selected)
            : '';

        $jobs = $wpdb->get_results(
            "SELECT id, workflow_id, event_id, status,
                    run_id, created_at, processed_at
             FROM " . self::table() .
            " ORDER BY id DESC LIMIT 25"
        );

        ?>
        <div class="wrap" style="max-width:1100px;">
            <h1>FlowPilot AI — Webhook Triggers</h1>
            <p>
                Receive signed events and process them
                using the simulation-only execution engine.
            </p>

            <div class="card"
                 style="max-width:none;padding:22px;margin-top:25px;">
                <h2>Webhook Configuration</h2>

                <?php if (!$workflows) : ?>
                    <p>
                        Create a workflow with the
                        <strong>Incoming Webhook</strong>
                        trigger in FlowPilot AI first.
                    </p>
                <?php else : ?>

                    <form method="get"
                          action="<?php echo esc_url(
                              admin_url('admin.php')
                          ); ?>">
                        <input type="hidden"
                               name="page"
                               value="fpa-webhooks">

                        <label for="fpa-hook-workflow">
                            Select workflow
                        </label>

                        <select name="workflow_id"
                                id="fpa-hook-workflow">
                            <?php foreach ($workflows as $item) : ?>
                                <option value="<?php
                                    echo esc_attr($item->id);
                                ?>" <?php selected(
                                    $selected,
                                    $item->id
                                ); ?>>
                                    <?php echo esc_html($item->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button class="button">Open</button>
                    </form>

                    <?php if ($workflow) : ?>
                        <h3>Webhook Endpoint</h3>
                        <input type="text"
                               readonly
                               style="width:100%;max-width:800px;"
                               value="<?php echo esc_attr(
                                   self::endpoint($selected)
                               ); ?>">

                        <h3>Signing Secret</h3>

                        <?php if ($secret !== '') : ?>
                            <input type="text"
                                   readonly
                                   autocomplete="off"
                                   style="width:100%;max-width:800px;"
                                   value="<?php
                                       echo esc_attr($secret);
                                   ?>">
                            <p class="description">
                                Keep this secret private.
                                Rotating it invalidates old signatures.
                            </p>
                        <?php else : ?>
                            <p>
                                No signing secret exists yet.
                                Generate one to enable the endpoint.
                            </p>
                        <?php endif; ?>

                        <form method="post"
                              action="<?php echo esc_url(
                                  admin_url('admin-post.php')
                              ); ?>">
                            <input type="hidden"
                                   name="action"
                                   value="fpa_rotate_webhook">
                            <input type="hidden"
                                   name="workflow_id"
                                   value="<?php
                                       echo esc_attr($selected);
                                   ?>">
                            <?php wp_nonce_field(
                                'fpa_rotate_secret'
                            ); ?>
                            <button class="button button-primary">
                                <?php echo $secret
                                    ? 'Rotate Secret'
                                    : 'Generate Secret';
                                ?>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="card"
                 style="max-width:none;padding:22px;margin-top:24px;">
                <h2>Latest Webhook Jobs</h2>

                <p>
                    Job processing may require another site
                    request because WP-Cron is request-triggered.
                </p>

                <div style="overflow-x:auto;">
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Job</th>
                                <th>Workflow</th>
                                <th>Event ID</th>
                                <th>Status</th>
                                <th>Run</th>
                                <th>Received</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (!$jobs) : ?>
                            <tr>
                                <td colspan="6">
                                    No webhook jobs received.
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($jobs as $job) : ?>
                                <tr>
                                    <td>#<?php
                                        echo esc_html($job->id);
                                    ?></td>
                                    <td><?php
                                        echo esc_html($job->workflow_id);
                                    ?></td>
                                    <td><?php
                                        echo esc_html($job->event_id);
                                    ?></td>
                                    <td><?php
                                        echo esc_html($job->status);
                                    ?></td>
                                    <td><?php
                                        echo esc_html(
                                            $job->run_id ?: '—'
                                        );
                                    ?></td>
                                    <td><?php
                                        echo esc_html($job->created_at);
                                    ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
}
