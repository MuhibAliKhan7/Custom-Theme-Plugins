
<?php
if (!defined('ABSPATH')) {
    exit;
}

$stage_colors = array(
    'new'       => '#7763eb',
    'contacted' => '#5685e5',
    'qualified' => '#13ad97',
    'proposal'  => '#e7a94e',
    'won'       => '#39ae72',
    'lost'      => '#e57690'
);

$notice = isset($_GET['notice'])
    ? sanitize_key(wp_unslash($_GET['notice']))
    : '';

$grouped = array_fill_keys(
    array_keys($statuses),
    array()
);

foreach ($leads as $lead) {
    if (isset($grouped[$lead->status])) {
        $grouped[$lead->status][] = $lead;
    }
}

$total_leads = count($leads);

$won_count = count($grouped['won']);
$qualified_count = count($grouped['qualified']);
$proposal_count = count($grouped['proposal']);

$leads_url = admin_url('admin.php?page=wplf-leads');
?>

<div class="wrap wplf-app wplf-pipeline-app">

    <header class="wplf-topbar">
        <div>
            <span class="wplf-eyebrow">
                SALES WORKSPACE
            </span>

            <h1>Sales Pipeline</h1>

            <p>
                Move leads between stages and track
                every sales opportunity.
            </p>
        </div>

        <a href="<?php echo esc_url(
            $leads_url . '#wplf-form'
        ); ?>" class="wplf-btn wplf-btn-primary">
            <span class="dashicons dashicons-plus-alt2"
                  aria-hidden="true"></span>
            Add New Lead
        </a>
    </header>

    <div class="wplf-pipeline-banner">
        <div>
            <span>LEADFLOW CRM</span>
            <h2>Every opportunity, in one place.</h2>
            <p>
                Drag leads to update their stage, or use
                the stage selector on each card.
            </p>
        </div>
        <span class="dashicons dashicons-chart-bar"
              aria-hidden="true"></span>
    </div>

    <div class="wplf-pipeline-summary">

        <div class="wplf-pipeline-metric">
            <span>Total Leads</span>
            <strong><?php
                echo esc_html($total_leads);
            ?></strong>
        </div>

        <div class="wplf-pipeline-metric">
            <span>Qualified</span>
            <strong><?php
                echo esc_html($qualified_count);
            ?></strong>
        </div>

        <div class="wplf-pipeline-metric">
            <span>Proposals</span>
            <strong><?php
                echo esc_html($proposal_count);
            ?></strong>
        </div>

        <div class="wplf-pipeline-metric">
            <span>Won Deals</span>
            <strong><?php
                echo esc_html($won_count);
            ?></strong>
        </div>

    </div>

    <?php if ($notice === 'saved') : ?>
        <div class="wplf-notice success" role="status">
            Lead stage updated successfully.
        </div>
    <?php elseif ($notice === 'error') : ?>
        <div class="wplf-notice error" role="alert">
            The stage could not be updated.
        </div>
    <?php endif; ?>

    <div id="wplf-pipeline-message"
         class="wplf-pipeline-message"
         role="status"
         aria-live="polite"></div>

    <!-- KANBAN BOARD -->

    <div class="wplf-kanban-board"
         id="wplf-kanban-board">

        <?php foreach ($statuses as $key => $label) : ?>

            <section class="wplf-kanban-column"
                     data-status="<?php
                        echo esc_attr($key);
                     ?>">

                <div class="wplf-kanban-column-header">

                    <div class="wplf-column-heading">
                        <span class="wplf-stage-dot"
                              style="background:<?php
                                echo esc_attr(
                                    $stage_colors[$key]
                                );
                              ?>"></span>

                        <h2><?php
                            echo esc_html($label);
                        ?></h2>
                    </div>

                    <span class="wplf-stage-count">
                        <?php
                        echo esc_html(
                            count($grouped[$key])
                        );
                        ?>
                    </span>

                </div>

                <div class="wplf-kanban-dropzone"
                     data-dropzone="<?php
                        echo esc_attr($key);
                     ?>">

                    <?php foreach ($grouped[$key] as $lead) : ?>

                        <?php
                        $edit_url = add_query_arg(
                            array(
                                'page' => 'wplf-leads',
                                'edit' => absint($lead->id)
                            ),
                            admin_url('admin.php')
                        );

                        $initial = strtoupper(
                            substr($lead->name, 0, 1)
                        );
                        ?>

                        <article
                            class="wplf-kanban-card"
                            draggable="true"
                            data-lead-id="<?php
                                echo esc_attr($lead->id);
                            ?>"
                            data-status="<?php
                                echo esc_attr($lead->status);
                            ?>">

                            <div class="wplf-card-top">
                                <span class="wplf-card-avatar">
                                    <?php
                                    echo esc_html($initial);
                                    ?>
                                </span>

                                <span class="wplf-card-drag"
                                      aria-hidden="true"
                                      title="Drag lead">
                                    <span class="dashicons
                                        dashicons-move"></span>
                                </span>
                            </div>

                            <h3>
                                <?php
                                echo esc_html($lead->name);
                                ?>
                            </h3>

                            <p class="wplf-card-company">
                                <?php
                                echo esc_html(
                                    $lead->company ?: 'No company'
                                );
                                ?>
                            </p>

                            <?php if ($lead->email) : ?>
                                <p class="wplf-card-detail">
                                    <span class="dashicons
                                        dashicons-email-alt"
                                          aria-hidden="true"></span>
                                    <?php
                                    echo esc_html($lead->email);
                                    ?>
                                </p>
                            <?php endif; ?>

                            <?php if ($lead->phone) : ?>
                                <p class="wplf-card-detail">
                                    <span class="dashicons
                                        dashicons-phone"
                                          aria-hidden="true"></span>
                                    <?php
                                    echo esc_html($lead->phone);
                                    ?>
                                </p>
                            <?php endif; ?>

                            <div class="wplf-card-footer">

                                <a href="<?php
                                    echo esc_url($edit_url);
                                ?>" class="wplf-card-edit">
                                    View / Edit
                                </a>

                                <span class="wplf-card-id">
                                    #<?php
                                    echo esc_html($lead->id);
                                    ?>
                                </span>
                            </div>

                            <!-- ACCESSIBLE STAGE CONTROLS -->

                            <form method="post"
                                  class="wplf-card-stage-form"
                                  action="<?php echo esc_url(
                                      admin_url('admin-post.php')
                                  ); ?>">

                                <input type="hidden"
                                       name="action"
                                       value="wplf_pipeline_status">

                                <input type="hidden"
                                       name="lead_id"
                                       value="<?php
                                           echo esc_attr($lead->id);
                                       ?>">

                                <?php wp_nonce_field(
                                    'wplf_pipeline_status'
                                ); ?>

                                <label for="wplf-stage-<?php
                                    echo esc_attr($lead->id);
                                ?>">
                                    Move to stage
                                </label>

                                <div class="wplf-stage-controls">
                                    <select
                                        id="wplf-stage-<?php
                                            echo esc_attr($lead->id);
                                        ?>"
                                        name="status">

                                        <?php foreach (
                                            $statuses as $s => $s_label
                                        ) : ?>
                                            <option value="<?php
                                                echo esc_attr($s);
                                            ?>" <?php selected(
                                                $lead->status,
                                                $s
                                            ); ?>>
                                                <?php
                                                echo esc_html($s_label);
                                                ?>
                                            </option>
                                        <?php endforeach; ?>

                                    </select>

                                    <button type="submit">
                                        Move
                                    </button>
                                </div>

                            </form>

                        </article>

                    <?php endforeach; ?>

                </div>

                <div class="wplf-column-footer">
                    <span class="dashicons dashicons-plus-alt2"
                          aria-hidden="true"></span>
                    Drop leads here
                </div>

            </section>

        <?php endforeach; ?>

    </div>

</div>
