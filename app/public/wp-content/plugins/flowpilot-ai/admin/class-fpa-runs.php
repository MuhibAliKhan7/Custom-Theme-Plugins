
<?php

if (!defined('ABSPATH')) {
    exit;
}

class FPA_Runs {

    public function __construct() {
        add_action(
            'admin_menu',
            array($this, 'menu'),
            30
        );

        add_action(
            'admin_post_fpa_test_run',
            array($this, 'handle_run')
        );
    }

    public function menu() {
        add_submenu_page(
            'flowpilot-ai',
            'Execution History',
            'Execution History',
            'manage_options',
            'fpa-runs',
            array($this, 'render')
        );
    }

    public function handle_run() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied.');
        }

        check_admin_referer('fpa_test_run');

        $workflow_id = isset($_POST['workflow_id'])
            ? absint($_POST['workflow_id'])
            : 0;

        $result = FPA_Engine::run($workflow_id);

        $args = array(
            'page' => 'fpa-runs'
        );

        if (is_wp_error($result)) {
            $args['notice'] = 'error';
            $args['code'] = sanitize_key(
                $result->get_error_code()
            );
        } else {
            $args['notice'] = 'completed';
            $args['run_id'] = $result;
        }

        wp_safe_redirect(
            add_query_arg(
                $args,
                admin_url('admin.php')
            )
        );

        exit;
    }

    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die('Access denied.');
        }

        $workflows = FPA_Repository::all();
        $runs = FPA_Engine::recent_runs();

        $selected_run = isset($_GET['run_id'])
            ? FPA_Engine::get_run(
                absint($_GET['run_id'])
            )
            : null;

        $notice = isset($_GET['notice'])
            ? sanitize_key(
                wp_unslash($_GET['notice'])
            )
            : '';

        $code = isset($_GET['code'])
            ? sanitize_key(
                wp_unslash($_GET['code'])
            )
            : '';

        $workflow_names = array();

        foreach ($workflows as $workflow) {
            $workflow_names[$workflow->id] =
                $workflow->name;
        }

        ?>
        <div class="wrap fpa-runs">

            <style>
                .fpa-runs {
                    max-width: 1180px;
                    padding: 25px;
                }

                .fpa-runs h1 {
                    font-size: 29px;
                    font-weight: 750;
                    margin-bottom: 12px;
                }

                .fpa-runs .fpa-runs-intro {
                    color: #69748b;
                    margin-bottom: 25px;
                }

                .fpa-runs-panel {
                    background: #fff;
                    border: 1px solid #e5e6ef;
                    border-radius: 15px;
                    padding: 25px;
                    margin-bottom: 22px;
                }

                .fpa-runs-panel h2 {
                    margin-top: 0;
                }

                .fpa-runs .fpa-run-form {
                    display: flex;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 12px;
                }

                .fpa-runs select {
                    min-width: 270px;
                    max-width: 100%;
                }

                .fpa-runs .fpa-run-button {
                    background: #7058e8;
                    color: #fff;
                    border: none;
                    border-radius: 8px;
                    padding: 10px 20px;
                    font-weight: 650;
                    cursor: pointer;
                }

                .fpa-runs table {
                    width: 100%;
                    border-collapse: collapse;
                }

                .fpa-runs th,
                .fpa-runs td {
                    text-align: left;
                    padding: 13px;
                    border-bottom: 1px solid #ecedf4;
                }

                .fpa-runs th {
                    color: #69748b;
                    background: #f7f7fd;
                }

                .fpa-run-status {
                    color: #16835f;
                    background: #e7f7ef;
                    display: inline-block;
                    padding: 6px 10px;
                    border-radius: 7px;
                    font-weight: 700;
                    font-size: 12px;
                }

                .fpa-run-step {
                    padding: 14px 17px;
                    border-left: 3px solid #8062ee;
                    background: #f7f6fd;
                    border-radius: 7px;
                    margin-bottom: 10px;
                }

                .fpa-run-step strong {
                    display: block;
                    margin-bottom: 5px;
                }

                .fpa-run-step p {
                    margin: 0;
                    color: #68748c;
                }
            </style>

            <h1>FlowPilot AI — Execution History</h1>

            <p class="fpa-runs-intro">
                Test workflow execution plans and inspect
                individual step results.
                External actions are simulated in Phase 3.
            </p>

            <?php if ($notice === 'completed') : ?>
                <div class="notice notice-success">
                    <p>
                        Workflow test completed.
                        No external actions were executed.
                    </p>
                </div>
            <?php elseif ($notice === 'error') : ?>
                <div class="notice notice-error">
                    <p>
                        Test could not start.
                        Validation error:
                        <strong><?php
                            echo esc_html($code);
                        ?></strong>
                    </p>
                </div>
            <?php endif; ?>

            <section class="fpa-runs-panel">
                <h2>Run a Workflow Test</h2>

                <?php if (empty($workflows)) : ?>
                    <p>
                        Create a workflow draft first.
                    </p>
                <?php else : ?>

                    <form
                        method="post"
                        class="fpa-run-form"
                        action="<?php
                            echo esc_url(
                                admin_url('admin-post.php')
                            );
                        ?>">

                        <input type="hidden"
                               name="action"
                               value="fpa_test_run">

                        <?php wp_nonce_field(
                            'fpa_test_run'
                        ); ?>

                        <label for="fpa-workflow-id">
                            Workflow
                        </label>

                        <select id="fpa-workflow-id"
                                name="workflow_id">

                            <?php foreach (
                                $workflows as $workflow
                            ) : ?>
                                <option value="<?php
                                    echo esc_attr($workflow->id);
                                ?>">
                                    <?php
                                    echo esc_html(
                                        $workflow->name
                                    );
                                    ?>
                                </option>
                            <?php endforeach; ?>

                        </select>

                        <button
                            type="submit"
                            class="fpa-run-button">
                            Run Test
                        </button>

                    </form>

                <?php endif; ?>
            </section>

            <section class="fpa-runs-panel">
                <h2>Recent Runs</h2>

                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Run ID</th>
                                <th>Workflow</th>
                                <th>Status</th>
                                <th>Started</th>
                                <th>Details</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($runs)) : ?>
                                <tr>
                                    <td colspan="5">
                                        No executions recorded.
                                    </td>
                                </tr>
                            <?php else : ?>

                                <?php foreach ($runs as $run) : ?>
                                    <?php
                                    $url = add_query_arg(
                                        array(
                                            'page' => 'fpa-runs',
                                            'run_id' => $run->id
                                        ),
                                        admin_url('admin.php')
                                    );
                                    ?>

                                    <tr>
                                        <td>#<?php
                                            echo esc_html($run->id);
                                        ?></td>

                                        <td><?php
                                            echo esc_html(
                                                $workflow_names[
                                                    $run->workflow_id
                                                ] ?? (
                                                    'Workflow #' .
                                                    $run->workflow_id
                                                )
                                            );
                                        ?></td>

                                        <td>
                                            <span class="fpa-run-status">
                                                <?php
                                                echo esc_html(
                                                    ucfirst($run->status)
                                                );
                                                ?>
                                            </span>
                                        </td>

                                        <td><?php
                                            echo esc_html(
                                                $run->started_at
                                            );
                                        ?></td>

                                        <td>
                                            <a href="<?php
                                                echo esc_url($url);
                                            ?>">
                                                View Steps
                                            </a>
                                        </td>
                                    </tr>

                                <?php endforeach; ?>

                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <?php if ($selected_run) : ?>
                <?php
                $context = json_decode(
                    $selected_run->context_json,
                    true
                );

                $steps = is_array($context)
                    ? ($context['steps'] ?? array())
                    : array();
                ?>

                <section class="fpa-runs-panel">
                    <h2>
                        Execution Details —
                        Run #<?php
                            echo esc_html(
                                $selected_run->id
                            );
                        ?>
                    </h2>

                    <p>
                        Mode: Test simulation
                    </p>

                    <?php foreach ($steps as $index => $step) : ?>
                        <div class="fpa-run-step">

                            <strong>
                                Step <?php
                                    echo esc_html($index + 1);
                                ?>:
                                <?php
                                echo esc_html(
                                    $step['label'] ?? ''
                                );
                                ?>
                            </strong>

                            <p>
                                <?php
                                echo esc_html(
                                    ucfirst(
                                        $step['status'] ?? ''
                                    )
                                );
                                ?>
                                —
                                <?php
                                echo esc_html(
                                    $step['message'] ?? ''
                                );
                                ?>
                            </p>
                        </div>
                    <?php endforeach; ?>

                </section>
            <?php endif; ?>

        </div>
        <?php
    }
}
