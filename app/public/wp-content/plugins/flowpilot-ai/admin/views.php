
<?php
if (!defined('ABSPATH')) {
    exit;
}

$base_url = admin_url('admin.php?page=flowpilot-ai');

$triggers = FPA_Repository::triggers();
$action_options = FPA_Repository::actions();

$selected_actions = array();

if ($editing) {
    foreach (
        FPA_Repository::decode_actions($editing)
        as $action
    ) {
        if (isset($action['type'])) {
            $selected_actions[] = $action['type'];
        }
    }
}

$editing_mode = (bool) $editing;
$editor_visible = $editing_mode ||
    (isset($_GET['new']) && '1' === $_GET['new']);

$workflow_name = $editing ? $editing->name : '';
$workflow_description = $editing
    ? $editing->description
    : '';

$workflow_trigger = $editing
    ? $editing->trigger_type
    : 'manual';
?>

<div class="wrap fpa-app">

    <div class="fpa-topbar">
        <div>
            <span class="fpa-eyebrow">AUTOMATION WORKSPACE</span>
            <h1>FlowPilot AI</h1>
            <p>Build smarter workflows for your WordPress business.</p>
        </div>

        <a class="fpa-button fpa-button-primary"
           href="<?php echo esc_url(
               add_query_arg(
                   array(
                       'page' => 'flowpilot-ai',
                       'new' => 1
                   ),
                   admin_url('admin.php')
               )
           ); ?>">
            + Create Workflow
        </a>
    </div>

    <div class="fpa-hero">
        <div>
            <span class="fpa-hero-label">
                FLOWPILOT AI / WORKFLOW STUDIO
            </span>

            <h2>Automate the ordinary.<br>
                <em>Focus on the extraordinary.</em>
            </h2>

            <p>
                Design intelligent workflows that connect
                WordPress, customer data and future AI tools.
            </p>

            <span class="fpa-hero-tag">
                PHASE 1 · WORKFLOW DRAFT BUILDER
            </span>
        </div>

        <div class="fpa-hero-visual" aria-hidden="true">
            <div class="fpa-visual-node">
                <span class="dashicons dashicons-feedback"></span>
                Trigger
            </div>
            <div class="fpa-visual-line"></div>
            <div class="fpa-visual-node">
                <span class="dashicons dashicons-admin-tools"></span>
                Actions
            </div>
        </div>
    </div>

    <?php if ($notice === 'saved') : ?>
        <div class="fpa-notice fpa-success" role="status">
            Workflow saved successfully.
        </div>
    <?php elseif ($notice === 'deleted') : ?>
        <div class="fpa-notice fpa-success" role="status">
            Workflow deleted.
        </div>
    <?php elseif ($notice === 'error') : ?>
        <div class="fpa-notice fpa-error" role="alert">
            The operation failed. Check the submitted details.
        </div>
    <?php endif; ?>

    <div class="fpa-stats">
        <div class="fpa-stat">
            <span>Total Workflows</span>
            <strong><?php echo esc_html($total); ?></strong>
            <small>Saved workflow definitions</small>
        </div>

        <div class="fpa-stat">
            <span>Workflow Drafts</span>
            <strong><?php echo esc_html($total); ?></strong>
            <small>Not active yet</small>
        </div>

        <div class="fpa-stat">
            <span>Executed Runs</span>
            <strong>0</strong>
            <small>Execution engine coming later</small>
        </div>
    </div>

    <?php if ($editor_visible) : ?>

        <div class="fpa-panel fpa-editor" id="fpa-editor">

            <div class="fpa-panel-header">
                <div>
                    <h2><?php echo $editing_mode
                        ? 'Edit Workflow'
                        : 'Create New Workflow';
                    ?></h2>

                    <p>Configure your workflow definition.</p>
                </div>

                <a href="<?php echo esc_url($base_url); ?>"
                   class="fpa-button fpa-button-outline">
                    Close
                </a>
            </div>

            <form method="post"
                  action="<?php echo esc_url(
                      admin_url('admin-post.php')
                  ); ?>">

                <input type="hidden"
                       name="action"
                       value="fpa_save">

                <input type="hidden"
                       name="workflow_id"
                       value="<?php echo esc_attr(
                           $editing ? $editing->id : 0
                       ); ?>">

                <?php wp_nonce_field('fpa_save_workflow'); ?>

                <div class="fpa-field">
                    <label for="fpa-name">Workflow Name *</label>
                    <input id="fpa-name"
                           type="text"
                           name="name"
                           maxlength="190"
                           required
                           value="<?php echo esc_attr(
                               $workflow_name
                           ); ?>"
                           placeholder="e.g. Qualify Website Leads">
                </div>

                <div class="fpa-field">
                    <label for="fpa-description">Description</label>
                    <textarea id="fpa-description"
                              name="description"
                              rows="3"
                              placeholder="What should this workflow do?"><?php
                              echo esc_textarea(
                                  $workflow_description
                              );
                    ?></textarea>
                </div>

                <div class="fpa-builder">

                    <div class="fpa-builder-node">
                        <div class="fpa-node-icon">
                            <span class="dashicons dashicons-randomize"></span>
                        </div>

                        <div class="fpa-node-content">
                            <span class="fpa-node-step">STEP 01</span>
                            <h3>When this happens...</h3>
                            <p>Select the workflow trigger.</p>

                            <label for="fpa-trigger">Trigger Type</label>

                            <select id="fpa-trigger" name="trigger_type">
                                <?php foreach (
                                    $triggers as $key => $label
                                ) : ?>
                                    <option value="<?php
                                        echo esc_attr($key);
                                    ?>" <?php selected(
                                        $workflow_trigger,
                                        $key
                                    ); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="fpa-connector">
                        <span></span>
                    </div>

                    <div class="fpa-builder-node">
                        <div class="fpa-node-icon fpa-ai-icon">
                            <span class="dashicons dashicons-superhero"></span>
                        </div>

                        <div class="fpa-node-content">
                            <span class="fpa-node-step">STEP 02</span>
                            <h3>Do these actions...</h3>
                            <p>Select one or more planned actions.</p>

                            <div class="fpa-action-options">
                                <?php foreach (
                                    $action_options as $key => $label
                                ) : ?>
                                    <label class="fpa-action-option">
                                        <input type="checkbox"
                                               name="actions[]"
                                               value="<?php
                                                   echo esc_attr($key);
                                               ?>"
                                               <?php checked(
                                                   in_array(
                                                       $key,
                                                       $selected_actions,
                                                       true
                                                   )
                                               ); ?>>
                                        <span><?php
                                            echo esc_html($label);
                                        ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>

                        </div>
                    </div>

                </div>

                <div class="fpa-editor-footer">
                    <p>
                        Saving creates a draft only.
                        Automations will not run yet.
                    </p>

                    <button type="submit"
                            class="fpa-button fpa-button-primary">
                        Save Workflow Draft
                    </button>
                </div>

            </form>
        </div>

    <?php endif; ?>

    <div class="fpa-panel">

        <div class="fpa-panel-header">
            <div>
                <h2>Your Workflows</h2>
                <p>Manage your saved automation drafts.</p>
            </div>

            <span class="fpa-count">
                <?php echo esc_html($total); ?> workflows
            </span>
        </div>

        <?php if (empty($workflows)) : ?>

            <div class="fpa-empty">
                <span class="dashicons dashicons-randomize"></span>
                <h3>No workflows yet</h3>
                <p>
                    Create your first automation draft to get started.
                </p>
                <a href="<?php echo esc_url(
                    add_query_arg(
                        array(
                            'page' => 'flowpilot-ai',
                            'new' => 1
                        ),
                        admin_url('admin.php')
                    )
                ); ?>" class="fpa-button fpa-button-primary">
                    Create First Workflow
                </a>
            </div>

        <?php else : ?>

            <div class="fpa-table-wrap">
                <table class="fpa-table">
                    <thead>
                        <tr>
                            <th>Workflow</th>
                            <th>Trigger</th>
                            <th>Actions</th>
                            <th>Status</th>
                            <th>Updated</th>
                            <th>Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($workflows as $workflow) : ?>
                            <?php
                            $edit_url = add_query_arg(
                                array(
                                    'page' => 'flowpilot-ai',
                                    'edit' => absint($workflow->id)
                                ),
                                admin_url('admin.php')
                            );

                            $actions = FPA_Repository::decode_actions(
                                $workflow
                            );
                            ?>
                            <tr>
                                <td>
                                    <strong><?php
                                        echo esc_html($workflow->name);
                                    ?></strong>
                                    <small><?php
                                        echo esc_html(
                                            $workflow->description
                                        );
                                    ?></small>
                                </td>

                                <td><?php
                                    echo esc_html(
                                        $triggers[$workflow->trigger_type]
                                        ?? $workflow->trigger_type
                                    );
                                ?></td>

                                <td><?php
                                    echo esc_html(count($actions));
                                ?> planned</td>

                                <td>
                                    <span class="fpa-status">Draft</span>
                                </td>

                                <td><?php
                                    echo esc_html(
                                        mysql2date(
                                            'M j, Y',
                                            $workflow->updated_at
                                        )
                                    );
                                ?></td>

                                <td>
                                    <div class="fpa-actions">
                                        <a href="<?php
                                            echo esc_url($edit_url);
                                        ?>">Edit</a>

                                        <form method="post"
                                              class="fpa-delete-form"
                                              action="<?php
                                                  echo esc_url(
                                                      admin_url(
                                                          'admin-post.php'
                                                      )
                                                  );
                                              ?>">

                                            <input type="hidden"
                                                   name="action"
                                                   value="fpa_delete">

                                            <input type="hidden"
                                                   name="workflow_id"
                                                   value="<?php
                                                       echo esc_attr(
                                                           $workflow->id
                                                       );
                                                   ?>">

                                            <?php wp_nonce_field(
                                                'fpa_delete_workflow'
                                            ); ?>

                                            <button type="submit">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>
</div>
