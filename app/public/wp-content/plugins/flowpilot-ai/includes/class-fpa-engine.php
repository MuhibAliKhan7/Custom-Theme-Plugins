
<?php

if (!defined('ABSPATH')) {
    exit;
}

class FPA_Engine {

    const MAX_NODES = 30;

    public static function runs_table() {
        global $wpdb;
        return $wpdb->prefix . 'fpa_runs';
    }

    /**
     * Load a saved workflow graph.
     */
    public static function load_graph($workflow_id) {
        global $wpdb;

        $table = FPA_Builder::table();

        $raw = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT graph_json FROM $table
                 WHERE workflow_id = %d",
                absint($workflow_id)
            )
        );

        if (!$raw) {
            return new WP_Error(
                'missing_graph',
                'Save the visual workflow before running it.'
            );
        }

        $graph = json_decode($raw, true);

        if (
            !is_array($graph) ||
            !isset($graph['nodes'], $graph['edges']) ||
            !is_array($graph['nodes']) ||
            !is_array($graph['edges'])
        ) {
            return new WP_Error(
                'invalid_graph',
                'Saved workflow graph is invalid.'
            );
        }

        return $graph;
    }

    /**
     * Validate graph and calculate topological order.
     * All nodes must be reachable from one trigger.
     */
    public static function execution_plan($graph) {
        $nodes = $graph['nodes'];
        $edges = $graph['edges'];

        if (
            empty($nodes) ||
            count($nodes) > self::MAX_NODES ||
            count($edges) > 120
        ) {
            return new WP_Error(
                'graph_size',
                'Workflow must contain 1 to 30 nodes.'
            );
        }

        $map = array();
        $in_degree = array();
        $outgoing = array();
        $triggers = array();

        foreach ($nodes as $node) {
            if (!is_array($node)) {
                return new WP_Error('node', 'Invalid node.');
            }

            $id = $node['id'] ?? '';
            $kind = $node['kind'] ?? '';
            $type = $node['type'] ?? '';

            if (
                !is_string($id) ||
                $id === '' ||
                strlen($id) > 80 ||
                isset($map[$id])
            ) {
                return new WP_Error(
                    'node_id',
                    'Duplicate or invalid node ID.'
                );
            }

            if ($kind === 'trigger') {
                if (!array_key_exists(
                    $type,
                    FPA_Repository::triggers()
                )) {
                    return new WP_Error(
                        'trigger_type',
                        'Unknown trigger type.'
                    );
                }

                $triggers[] = $id;
            } elseif ($kind === 'action') {
                if (!array_key_exists(
                    $type,
                    FPA_Repository::actions()
                )) {
                    return new WP_Error(
                        'action_type',
                        'Unknown action type.'
                    );
                }
            } else {
                return new WP_Error(
                    'node_kind',
                    'Invalid node kind.'
                );
            }

            $map[$id] = $node;
            $in_degree[$id] = 0;
            $outgoing[$id] = array();
        }

        if (count($triggers) !== 1) {
            return new WP_Error(
                'trigger_count',
                'Exactly one trigger is required.'
            );
        }

        $trigger_id = $triggers[0];
        $seen_edges = array();

        foreach ($edges as $edge) {
            if (!is_array($edge)) {
                return new WP_Error('edge', 'Invalid connection.');
            }

            $from = $edge['from'] ?? '';
            $to = $edge['to'] ?? '';

            if (
                !is_string($from) ||
                !is_string($to) ||
                !isset($map[$from], $map[$to]) ||
                $from === $to ||
                $map[$to]['kind'] === 'trigger'
            ) {
                return new WP_Error(
                    'edge',
                    'Invalid connection detected.'
                );
            }

            $edge_key = $from . ':' . $to;

            if (isset($seen_edges[$edge_key])) {
                return new WP_Error(
                    'duplicate_edge',
                    'Duplicate connection detected.'
                );
            }

            $seen_edges[$edge_key] = true;
            $outgoing[$from][] = $to;
            $in_degree[$to]++;
        }

        /**
         * Ensure all nodes can be reached
         * from the single trigger.
         */
        $reachable = array();
        $stack = array($trigger_id);

        while ($stack) {
            $current = array_pop($stack);

            if (isset($reachable[$current])) {
                continue;
            }

            $reachable[$current] = true;

            foreach ($outgoing[$current] as $next) {
                $stack[] = $next;
            }
        }

        if (count($reachable) !== count($map)) {
            return new WP_Error(
                'disconnected',
                'Connect all nodes to the trigger.'
            );
        }

        /**
         * Kahn's algorithm:
         * order nodes by dependencies.
         */
        $queue = array();

        foreach ($in_degree as $id => $degree) {
            if ($degree === 0) {
                $queue[] = $id;
            }
        }

        $ordered = array();

        while ($queue) {
            $current = array_shift($queue);
            $ordered[] = $map[$current];

            foreach ($outgoing[$current] as $next) {
                $in_degree[$next]--;

                if ($in_degree[$next] === 0) {
                    $queue[] = $next;
                }
            }
        }

        if (count($ordered) !== count($nodes)) {
            return new WP_Error(
                'cycle',
                'Circular dependencies are not allowed.'
            );
        }

        return $ordered;
    }

    /**
     * Simulate one workflow step.
     * No external side effects are permitted.
     */
    private static function execute_step($node) {
        $kind = $node['kind'];
        $type = $node['type'];

        if ($kind === 'trigger') {
            if ($type !== 'manual') {
                return array(
                    'status' => 'simulated',
                    'message' => 'Trigger event simulated.'
                );
            }

            return array(
                'status' => 'completed',
                'message' => 'Manual trigger accepted.'
            );
        }

        $labels = FPA_Repository::actions();

        return array(
            'status' => 'simulated',
            'message' => (
                $labels[$type] ?? 'Action'
            ) . ' simulated; no external action executed.'
        );
    }

    /**
     * Run workflow synchronously in test mode.
     */
    public static function run($workflow_id) {
        global $wpdb;

        $workflow_id = absint($workflow_id);

        $workflow = FPA_Repository::get($workflow_id);

        if (!$workflow) {
            return new WP_Error(
                'workflow_missing',
                'Workflow not found.'
            );
        }

        $graph = self::load_graph($workflow_id);

        if (is_wp_error($graph)) {
            return $graph;
        }

        $plan = self::execution_plan($graph);

        if (is_wp_error($plan)) {
            return $plan;
        }

        $table = self::runs_table();

        $started = current_time('mysql');

        $initial = array(
            'mode' => 'test',
            'steps' => array(),
            'message' => 'Run started.'
        );

        $inserted = $wpdb->insert(
            $table,
            array(
                'workflow_id' => $workflow_id,
                'status' => 'running',
                'context_json' => wp_json_encode($initial),
                'started_at' => $started
            ),
            array('%d', '%s', '%s', '%s')
        );

        if ($inserted === false) {
            return new WP_Error(
                'run_create',
                'Could not create run record.'
            );
        }

        $run_id = (int) $wpdb->insert_id;
        $steps = array();

        foreach ($plan as $node) {
            $result = self::execute_step($node);

            $steps[] = array(
                'node_id' => $node['id'],
                'label' => sanitize_text_field(
                    $node['label'] ?? $node['type']
                ),
                'type' => $node['type'],
                'status' => $result['status'],
                'message' => $result['message']
            );
        }

        $context = array(
            'mode' => 'test',
            'steps' => $steps,
            'message' => 'Test run finished.',
            'steps_count' => count($steps)
        );

        $updated = $wpdb->update(
            $table,
            array(
                'status' => 'completed',
                'context_json' => wp_json_encode($context),
                'finished_at' => current_time('mysql')
            ),
            array('id' => $run_id),
            array('%s', '%s', '%s'),
            array('%d')
        );

        if ($updated === false) {
            return new WP_Error(
                'run_update',
                'Run was created but final status could not be saved.'
            );
        }

        return $run_id;
    }

    /**
     * Retrieve latest runs.
     */
    public static function recent_runs($limit = 30) {
        global $wpdb;

        $table = self::runs_table();
        $limit = max(1, min(100, absint($limit)));

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table
                 ORDER BY id DESC LIMIT %d",
                $limit
            )
        );
    }

    public static function get_run($run_id) {
        global $wpdb;

        $table = self::runs_table();

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE id = %d",
                absint($run_id)
            )
        );
    }
}
