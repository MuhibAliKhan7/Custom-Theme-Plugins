
<?php

if (!defined('ABSPATH')) {
    exit;
}

class FPA_Builder {

    private $page_hook = '';

    public function __construct() {
        add_action('admin_menu', array($this, 'menu'), 20);
        add_action('admin_enqueue_scripts', array($this, 'assets'));
        add_action('rest_api_init', array($this, 'routes'));
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'fpa_graphs';
    }

    public static function maybe_install() {
        if (get_option('fpa_graph_version') !== '0.2.0') {
            self::install();
        }
    }

    public static function install() {
        global $wpdb;

        $table = self::table();
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table (
            workflow_id bigint(20) unsigned NOT NULL,
            graph_json longtext NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (workflow_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('fpa_graph_version', '0.2.0');
    }

    public function menu() {
        $this->page_hook = add_submenu_page(
            'flowpilot-ai',
            'Visual Builder',
            'Visual Builder',
            'manage_options',
            'fpa-builder',
            array($this, 'render')
        );
    }

    public function assets($hook) {
        if ($hook !== $this->page_hook) {
            return;
        }

        wp_enqueue_style(
            'fpa-builder',
            FPA_URL . 'admin/assets/builder.css',
            array(),
            FPA_VERSION
        );

        wp_enqueue_script(
            'fpa-builder',
            FPA_URL . 'admin/assets/builder.js',
            array(),
            FPA_VERSION,
            true
        );

        wp_add_inline_script(
            'fpa-builder',
            'window.FPA_BUILDER = ' . wp_json_encode(array(
                'api' => esc_url_raw(
                    rest_url('flowpilot/v1/graphs/')
                ),
                'nonce' => wp_create_nonce('wp_rest')
            )) . ';',
            'before'
        );
    }

    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied.');
        }

        $workflows = FPA_Repository::all();

        $selected = isset($_GET['workflow_id'])
            ? absint($_GET['workflow_id'])
            : 0;

        if (!$selected && !empty($workflows)) {
            $selected = (int) $workflows[0]->id;
        }

        if ($selected && !FPA_Repository::get($selected)) {
            $selected = 0;
        }
        ?>
        <div class="wrap fpa-builder-page">

            <header class="fpa-builder-header">
                <div>
                    <span class="fpa-builder-kicker">
                        FLOWPILOT AI / AUTOMATION DESIGN
                    </span>
                    <h1>Visual Workflow Builder</h1>
                    <p>Design and connect your automation steps.</p>
                </div>

                <div class="fpa-builder-header-actions">
                    <a class="fpa-back-button"
                       href="<?php echo esc_url(
                           admin_url('admin.php?page=flowpilot-ai')
                       ); ?>">Workflow Library</a>

                    <button id="fpa-save"
                            class="button button-primary">
                        Save Workflow
                    </button>
                </div>
            </header>

            <?php if (empty($workflows)) : ?>
                <div class="notice notice-info">
                    <p>
                        Create a workflow draft in the
                        FlowPilot AI library first.
                    </p>
                </div>
            <?php else : ?>

                <form method="get" class="fpa-workflow-picker">
                    <input type="hidden"
                           name="page"
                           value="fpa-builder">

                    <label for="fpa-workflow">
                        Editing workflow
                    </label>

                    <select id="fpa-workflow"
                            name="workflow_id">
                        <?php foreach ($workflows as $workflow) : ?>
                            <option
                                value="<?php echo esc_attr(
                                    $workflow->id
                                ); ?>"
                                <?php selected(
                                    $selected,
                                    $workflow->id
                                ); ?>>
                                <?php echo esc_html(
                                    $workflow->name
                                ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button class="button" type="submit">
                        Open
                    </button>
                </form>

                <div class="fpa-builder-layout"
                     id="fpa-builder-app"
                     data-workflow-id="<?php
                         echo esc_attr($selected);
                     ?>">

                    <aside class="fpa-node-palette">
                        <h2>Node Library</h2>
                        <p>Add building blocks to your workflow.</p>

                        <button type="button"
                                data-add-node="trigger"
                                data-node-type="manual">
                            <span>⚡</span> Manual Trigger
                        </button>

                        <button type="button"
                                data-add-node="trigger"
                                data-node-type="form_submitted">
                            <span>⚡</span> Form Submitted
                        </button>

                        <button type="button"
                                data-add-node="action"
                                data-node-type="ai_qualify">
                            <span>✦</span> AI Qualification
                        </button>

                        <button type="button"
                                data-add-node="action"
                                data-node-type="create_crm_lead">
                            <span>▣</span> Create CRM Lead
                        </button>

                        <button type="button"
                                data-add-node="action"
                                data-node-type="send_email">
                            <span>✉</span> Send Email
                        </button>

                        <button type="button"
                                data-add-node="action"
                                data-node-type="notify_admin">
                            <span>◉</span> Notify Admin
                        </button>

                        <button type="button"
                                data-add-node="action"
                                data-node-type="webhook">
                            <span>↗</span> Webhook Action
                        </button>
                    </aside>

                    <section class="fpa-canvas-panel"
                             aria-label="Workflow canvas">

                        <div class="fpa-canvas-toolbar">
                            <strong>Workflow Canvas</strong>
                            <div>
                                <button type="button"
                                        id="fpa-connect">
                                    Connect Nodes
                                </button>

                                <button type="button"
                                        id="fpa-delete-node">
                                    Delete Selected
                                </button>

                                <button type="button"
                                        id="fpa-reset-view">
                                    Fit View
                                </button>
                            </div>
                        </div>

                        <div class="fpa-canvas-viewport"
                             id="fpa-viewport">

                            <div class="fpa-canvas"
                                 id="fpa-canvas">

                                <svg id="fpa-edges"
                                     xmlns="http://www.w3.org/2000/svg"
                                     aria-hidden="true">
                                </svg>

                                <div id="fpa-nodes"></div>
                            </div>
                        </div>

                        <div class="fpa-canvas-status"
                             id="fpa-message"
                             role="status"
                             aria-live="polite">
                            Select a node to edit it.
                        </div>
                    </section>

                    <aside class="fpa-inspector">
                        <h2>Node Settings</h2>
                        <p>Select a node to configure its label.</p>

                        <label for="fpa-node-label">
                            Node label
                        </label>

                        <input id="fpa-node-label"
                               type="text"
                               maxlength="100"
                               disabled>

                        <div class="fpa-inspector-help">
                            <strong>How to connect</strong>
                            <p>
                                Click Connect Nodes, then
                                select a source and a target.
                                Connections go from triggers
                                or actions to later actions.
                            </p>
                        </div>
                    </aside>
                </div>

                <p class="fpa-draft-warning">
                    Draft editor only — workflows cannot execute yet.
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    public function routes() {
        register_rest_route(
            'flowpilot/v1',
            '/graphs/(?P<id>\d+)',
            array(
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array($this, 'get_graph'),
                    'permission_callback' => array(
                        $this,
                        'permission'
                    )
                ),
                array(
                    'methods' => WP_REST_Server::EDITABLE,
                    'callback' => array($this, 'save_graph'),
                    'permission_callback' => array(
                        $this,
                        'permission'
                    )
                )
            )
        );
    }

    public function permission() {
        return current_user_can('manage_options');
    }

    public function get_graph($request) {
        global $wpdb;

        $id = absint($request['id']);

        if (!FPA_Repository::get($id)) {
            return new WP_Error(
                'fpa_not_found',
                'Workflow not found.',
                array('status' => 404)
            );
        }

        $raw = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT graph_json FROM " . self::table() .
                " WHERE workflow_id = %d",
                $id
            )
        );

        if ($raw) {
            $graph = json_decode($raw, true);

            if (is_array($graph)) {
                return rest_ensure_response($graph);
            }
        }

        // Initialize a blank graph from existing
        // Phase 1 action definitions.
        $workflow = FPA_Repository::get($id);
        $old_actions = FPA_Repository::decode_actions($workflow);

        $nodes = array();
        $edges = array();

        $trigger_id = 'trigger-' . $id;

        $nodes[] = array(
            'id' => $trigger_id,
            'kind' => 'trigger',
            'type' => $workflow->trigger_type,
            'label' => 'Start: ' . $workflow->trigger_type,
            'x' => 100,
            'y' => 160
        );

        $previous = $trigger_id;

        foreach (array_slice($old_actions, 0, 20) as $i => $action) {
            $node_id = 'action-' . $id . '-' . $i;
            $type = $action['type'] ?? 'notify_admin';

            if (!array_key_exists($type, FPA_Repository::actions())) {
                continue;
            }

            $nodes[] = array(
                'id' => $node_id,
                'kind' => 'action',
                'type' => $type,
                'label' => FPA_Repository::actions()[$type],
                'x' => 420 + $i * 300,
                'y' => 160
            );

            $edges[] = array(
                'from' => $previous,
                'to' => $node_id
            );

            $previous = $node_id;
        }

        return rest_ensure_response(array(
            'nodes' => $nodes,
            'edges' => $edges
        ));
    }

    public function save_graph($request) {
        global $wpdb;

        $id = absint($request['id']);

        if (!FPA_Repository::get($id)) {
            return new WP_Error(
                'fpa_not_found',
                'Workflow not found.',
                array('status' => 404)
            );
        }

        $graph = $request->get_json_params();

        if (
            !is_array($graph) ||
            !isset($graph['nodes'], $graph['edges']) ||
            !is_array($graph['nodes']) ||
            !is_array($graph['edges'])
        ) {
            return new WP_Error(
                'fpa_invalid',
                'Invalid graph format.',
                array('status' => 400)
            );
        }

        if (count($graph['nodes']) > 60 ||
            count($graph['edges']) > 120) {
            return new WP_Error(
                'fpa_limit',
                'Graph size limit exceeded.',
                array('status' => 400)
            );
        }

        $allowed_triggers = array_keys(
            FPA_Repository::triggers()
        );
        $allowed_actions = array_keys(
            FPA_Repository::actions()
        );

        $nodes = array();
        $ids = array();
        $kinds = array();
        $trigger_count = 0;

        foreach ($graph['nodes'] as $node) {
            if (!is_array($node)) {
                return new WP_Error('fpa_node', 'Invalid node.', array('status'=>400));
            }

            $node_id = sanitize_key($node['id'] ?? '');
            $kind = sanitize_key($node['kind'] ?? '');
            $type = sanitize_key($node['type'] ?? '');
            $label = sanitize_text_field($node['label'] ?? '');

            if (
                !$node_id ||
                strlen($node_id) > 80 ||
                isset($ids[$node_id]) ||
                !in_array($kind, array('trigger','action'), true) ||
                ($kind === 'trigger' &&
                    !in_array($type, $allowed_triggers, true)) ||
                ($kind === 'action' &&
                    !in_array($type, $allowed_actions, true)) ||
                strlen($label) > 100
            ) {
                return new WP_Error(
                    'fpa_invalid_node',
                    'Invalid node data.',
                    array('status' => 400)
                );
            }

            $x = $node['x'] ?? null;
            $y = $node['y'] ?? null;

            if (
                !is_numeric($x) || !is_numeric($y) ||
                !is_finite((float)$x) || !is_finite((float)$y) ||
                $x < 0 || $y < 0 ||
                $x > 12000 || $y > 12000
            ) {
                return new WP_Error(
                    'fpa_coordinates',
                    'Invalid node position.',
                    array('status' => 400)
                );
            }

            if ($kind === 'trigger') {
                $trigger_count++;
            }

            $ids[$node_id] = true;
            $kinds[$node_id] = $kind;

            $nodes[] = array(
                'id' => $node_id,
                'kind' => $kind,
                'type' => $type,
                'label' => $label,
                'x' => (float) $x,
                'y' => (float) $y
            );
        }

        if ($trigger_count > 1) {
            return new WP_Error(
                'fpa_triggers',
                'Only one trigger is supported.',
                array('status' => 400)
            );
        }

        $edges = array();
        $seen = array();
        $adjacency = array();

        foreach ($graph['edges'] as $edge) {
            if (!is_array($edge)) {
                return new WP_Error('fpa_edge', 'Invalid edge.', array('status'=>400));
            }

            $from = sanitize_key($edge['from'] ?? '');
            $to = sanitize_key($edge['to'] ?? '');
            $key = $from . ':' . $to;

            if (
                !$from || !$to ||
                !isset($ids[$from], $ids[$to]) ||
                $from === $to ||
                isset($seen[$key]) ||
                $kinds[$to] === 'trigger'
            ) {
                return new WP_Error(
                    'fpa_invalid_edge',
                    'Invalid connection.',
                    array('status' => 400)
                );
            }

            $seen[$key] = true;
            $adjacency[$from][] = $to;

            $edges[] = array(
                'from' => $from,
                'to' => $to
            );
        }

        // Detect directed cycles.
        $visiting = array();
        $visited = array();

        $visit = function ($node) use (
            &$visit,
            &$visiting,
            &$visited,
            $adjacency
        ) {
            if (isset($visiting[$node])) {
                return false;
            }

            if (isset($visited[$node])) {
                return true;
            }

            $visiting[$node] = true;

            foreach (($adjacency[$node] ?? array()) as $next) {
                if (!$visit($next)) {
                    return false;
                }
            }

            unset($visiting[$node]);
            $visited[$node] = true;

            return true;
        };

        foreach (array_keys($ids) as $node_id) {
            if (!$visit($node_id)) {
                return new WP_Error(
                    'fpa_cycle',
                    'Circular connections are not allowed.',
                    array('status' => 400)
                );
            }
        }

        $json = wp_json_encode(array(
            'nodes' => $nodes,
            'edges' => $edges
        ));

        if (!$json) {
            return new WP_Error(
                'fpa_encode',
                'Could not encode graph.',
                array('status' => 500)
            );
        }

        $saved = $wpdb->replace(
            self::table(),
            array(
                'workflow_id' => $id,
                'graph_json' => $json,
                'updated_at' => current_time('mysql')
            ),
            array('%d','%s','%s')
        );

        if ($saved === false) {
            return new WP_Error(
                'fpa_database',
                'Could not save workflow graph.',
                array('status' => 500)
            );
        }

        return rest_ensure_response(array(
            'success' => true,
            'message' => 'Workflow graph saved.'
        ));
    }
}
