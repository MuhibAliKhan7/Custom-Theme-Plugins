
<?php
if (!defined('ABSPATH')) {
    exit;
}

$statuses = WPLF_Leads::statuses();

$name = $editing ? $editing->name : '';
$email = $editing ? $editing->email : '';
$phone = $editing ? $editing->phone : '';
$company = $editing ? $editing->company : '';
$lead_status = $editing ? $editing->status : 'new';

$base_url = admin_url('admin.php?page=wplf-leads');
$dashboard_url = admin_url('admin.php?page=wplf-crm');

$notice = isset($_GET['notice'])
    ? sanitize_key(wp_unslash($_GET['notice']))
    : '';

$is_editing = (null !== $editing);
?>

<div class="wrap wplf-app wplf-phase4">

    <!-- PAGE HEADER -->

    <div class="wplf-topbar">
        <div>
            <span class="wplf-eyebrow">
                CUSTOMER RELATIONSHIPS
            </span>
            <h1>Lead Management</h1>
            <p>Organize and manage every opportunity.</p>
        </div>

        <button type="button"
                class="wplf-btn wplf-btn-primary"
                data-wplf-open>
            <span class="dashicons dashicons-plus-alt2"></span>
            Add New Lead
        </button>
    </div>

    <!-- NOTIFICATIONS -->

    <?php if ($notice === 'saved') : ?>
        <div class="wplf-notice success" role="status">
            Lead saved successfully.
        </div>
    <?php elseif ($notice === 'deleted') : ?>
        <div class="wplf-notice success" role="status">
            Lead deleted successfully.
        </div>
    <?php elseif ($notice === 'error') : ?>
        <div class="wplf-notice error" role="alert">
            The operation could not be completed.
            Please verify the lead details.
        </div>
    <?php endif; ?>

    <!-- LEADS LIST -->

    <div class="wplf-panel wplf-leads-panel">

        <div class="wplf-panel-heading">
            <div>
                <h3>All Leads</h3>
                <p>
                    <?php echo esc_html($result['total']); ?>
                    matching leads
                </p>
            </div>

            <a href="<?php echo esc_url($dashboard_url); ?>"
               class="wplf-header-link">
                Dashboard
            </a>
        </div>

        <!-- SEARCH AND FILTER -->

        <form method="get"
              class="wplf-filter-form"
              action="<?php echo esc_url(admin_url('admin.php')); ?>">

            <input type="hidden"
                   name="page"
                   value="wplf-leads">

            <div class="wplf-search">
                <span class="dashicons dashicons-search"
                      aria-hidden="true"></span>
                <input type="search"
                       name="s"
                       aria-label="Search leads"
                       placeholder="Search name, email, company..."
                       value="<?php echo esc_attr($search); ?>">
            </div>

            <select name="status" aria-label="Filter lead status">
                <option value="">All Statuses</option>

                <?php foreach ($statuses as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>"
                        <?php selected($status, $key); ?>>
                        <?php echo esc_html($label); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit"
                    class="wplf-btn wplf-btn-primary">
                Filter
            </button>

            <a href="<?php echo esc_url($base_url); ?>"
               class="wplf-btn wplf-btn-outline">
                Reset
            </a>
        </form>

        <!-- LEADS TABLE -->

        <div class="wplf-table-wrap">
            <table class="wplf-table">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Phone</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($result['items'])) : ?>

                    <tr>
                        <td colspan="6" class="wplf-empty">
                            <span class="dashicons dashicons-groups"></span>
                            <strong>No leads found</strong>
                            <p>Try a different filter or add a lead.</p>
                        </td>
                    </tr>

                <?php else : ?>

                    <?php foreach ($result['items'] as $lead) : ?>

                        <?php
                        $edit_url = add_query_arg(
                            array(
                                'page' => 'wplf-leads',
                                'edit' => absint($lead->id)
                            ),
                            admin_url('admin.php')
                        );
                        ?>

                        <tr>
                            <td>
                                <div class="wplf-lead-identity">
                                    <span class="wplf-avatar">
                                        <?php
                                        echo esc_html(
                                            strtoupper(
                                                substr($lead->name, 0, 1)
                                            )
                                        );
                                        ?>
                                    </span>

                                    <div>
                                        <strong><?php
                                            echo esc_html($lead->name);
                                        ?></strong>

                                        <small><?php
                                            echo esc_html($lead->email);
                                        ?></small>
                                    </div>
                                </div>
                            </td>

                            <td><?php
                                echo esc_html($lead->phone ?: '—');
                            ?></td>

                            <td><?php
                                echo esc_html($lead->company ?: '—');
                            ?></td>

                            <td>
                                <span class="wplf-status wplf-status-<?php
                                    echo esc_attr($lead->status);
                                ?>">
                                    <?php
                                    echo esc_html(
                                        $statuses[$lead->status]
                                        ?? $lead->status
                                    );
                                    ?>
                                </span>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    mysql2date(
                                        'M j, Y',
                                        $lead->created_at
                                    )
                                );
                                ?>
                            </td>

                            <td>
                                <div class="wplf-actions">

                                    <a class="wplf-action-edit"
                                       href="<?php
                                       echo esc_url($edit_url);
                                       ?>">
                                        <span class="dashicons dashicons-edit"
                                              aria-hidden="true"></span>
                                        Edit
                                    </a>

                                    <form method="post"
                                          class="wplf-delete-form"
                                          action="<?php echo esc_url(
                                              admin_url('admin-post.php')
                                          ); ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="wplf_delete">

                                        <input type="hidden"
                                               name="lead_id"
                                               value="<?php
                                               echo esc_attr($lead->id);
                                               ?>">

                                        <?php
                                        wp_nonce_field('wplf_delete_lead');
                                        ?>

                                        <button type="submit">
                                            <span class="dashicons dashicons-trash"
                                                  aria-hidden="true"></span>
                                            Delete
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->

        <?php if ($result['pages'] > 1) : ?>
            <div class="wplf-pagination">
                <?php
                echo wp_kses_post(
                    paginate_links(
                        array(
                            'base' => add_query_arg(
                                array(
                                    'page' => 'wplf-leads',
                                    'paged' => '%#%',
                                    's' => $search,
                                    'status' => $status
                                ),
                                admin_url('admin.php')
                            ),
                            'current' => $page,
                            'total' => $result['pages'],
                            'prev_text' => 'Previous',
                            'next_text' => 'Next'
                        )
                    )
                );
                ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- DRAWER OVERLAY -->

    <div class="wplf-drawer-overlay"
         id="wplf-drawer-overlay"
         aria-hidden="true"></div>

    <!-- SLIDE-OUT EDITOR -->

    <aside class="wplf-drawer"
           id="wplf-lead-drawer"
           role="dialog"
           aria-modal="true"
           aria-labelledby="wplf-drawer-title"
           aria-hidden="true"
           data-editing="<?php echo $is_editing ? '1' : '0'; ?>"
           inert>

        <div class="wplf-drawer-header">

            <div class="wplf-drawer-heading">
                <span class="wplf-eyebrow">
                    <?php echo $is_editing
                        ? 'UPDATE CUSTOMER'
                        : 'NEW OPPORTUNITY';
                    ?>
                </span>

                <h2 id="wplf-drawer-title">
                    <?php echo $is_editing
                        ? 'Edit Lead'
                        : 'Add New Lead';
                    ?>
                </h2>

                <p>
                    <?php echo $is_editing
                        ? 'Update the information for this lead.'
                        : 'Enter customer details to get started.';
                    ?>
                </p>
            </div>

            <button type="button"
                    class="wplf-drawer-close"
                    data-wplf-close
                    aria-label="Close lead editor">
                <span class="dashicons dashicons-no-alt"
                      aria-hidden="true"></span>
            </button>
        </div>

        <!-- EDITOR FORM -->

        <form method="post"
              class="wplf-drawer-form"
              action="<?php echo esc_url(
                  admin_url('admin-post.php')
              ); ?>">

            <input type="hidden"
                   name="action"
                   value="wplf_save">

            <input type="hidden"
                   name="lead_id"
                   value="<?php
                   echo esc_attr($editing ? $editing->id : 0);
                   ?>">

            <?php wp_nonce_field('wplf_save_lead'); ?>

            <div class="wplf-drawer-body">

                <div class="wplf-form-section">
                    <h3>Contact Information</h3>
                    <p>Basic details about your lead.</p>

                    <div class="wplf-drawer-fields">

                        <label for="wplf-name">Full Name *</label>
                        <input id="wplf-name"
                               type="text"
                               name="name"
                               maxlength="190"
                               required
                               value="<?php echo esc_attr($name); ?>"
                               placeholder="e.g. Sarah Wilson">

                        <label for="wplf-email">Email Address</label>
                        <input id="wplf-email"
                               type="email"
                               name="email"
                               maxlength="190"
                               value="<?php echo esc_attr($email); ?>"
                               placeholder="sarah@example.com">

                        <label for="wplf-phone">Phone Number</label>
                        <input id="wplf-phone"
                               type="tel"
                               name="phone"
                               maxlength="50"
                               value="<?php echo esc_attr($phone); ?>"
                               placeholder="+44 7700 900000">

                        <label for="wplf-company">Company</label>
                        <input id="wplf-company"
                               type="text"
                               name="company"
                               maxlength="190"
                               value="<?php echo esc_attr($company); ?>"
                               placeholder="Company name">
                    </div>
                </div>

                <div class="wplf-form-section">
                    <h3>Lead Details</h3>
                    <p>Track the current opportunity stage.</p>

                    <div class="wplf-drawer-fields">
                        <label for="wplf-status">Lead Status</label>

                        <select id="wplf-status" name="status">
                            <?php foreach ($statuses as $key => $label) : ?>
                                <option value="<?php
                                    echo esc_attr($key);
                                ?>" <?php selected(
                                    $lead_status,
                                    $key
                                ); ?>>
                                    <?php echo esc_html($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

            </div>

            <!-- STICKY FOOTER -->

            <div class="wplf-drawer-footer">
                <button type="button"
                        class="wplf-btn wplf-btn-outline"
                        data-wplf-close>
                    Cancel
                </button>

                <button type="submit"
                        class="wplf-btn wplf-btn-primary">
                    <?php echo $is_editing
                        ? 'Save Changes'
                        : 'Create Lead';
                    ?>
                </button>
            </div>

        </form>

    </aside>
</div>
