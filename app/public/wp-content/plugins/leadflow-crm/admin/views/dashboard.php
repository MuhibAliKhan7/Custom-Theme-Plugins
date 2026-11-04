
<?php
if (!defined('ABSPATH')) {
    exit;
}

$counts = $stats['counts'];
$total = $stats['total'];

$cards = array(
    array(
        'label' => 'Total Leads',
        'value' => $total,
        'icon' => 'dashicons-groups',
        'color' => 'purple'
    ),
    array(
        'label' => 'Qualified Leads',
        'value' => $counts['qualified'],
        'icon' => 'dashicons-yes-alt',
        'color' => 'green'
    ),
    array(
        'label' => 'Deals Won',
        'value' => $counts['won'],
        'icon' => 'dashicons-awards',
        'color' => 'orange'
    ),
    array(
        'label' => 'Conversion Rate',
        'value' => $stats['conversion'] . '%',
        'icon' => 'dashicons-chart-line',
        'color' => 'pink'
    )
);

$leads_url = admin_url('admin.php?page=wplf-leads');
?>

<div class="wrap wplf-app">

    <div class="wplf-topbar">
        <div>
            <span class="wplf-eyebrow">WORKSPACE OVERVIEW</span>
            <h1>Dashboard</h1>
            <p>Here's what's happening with your leads.</p>
        </div>

        <a class="wplf-btn wplf-btn-primary"
           href="<?php echo esc_url($leads_url . '#wplf-form'); ?>">
            <span class="dashicons dashicons-plus-alt2"></span>
            Add New Lead
        </a>
    </div>

    <div class="wplf-hero">
        <div>
            <span class="wplf-hero-tag">LEADFLOW CRM</span>
            <h2>Your sales, simplified.</h2>
            <p>
                Manage opportunities, nurture your leads
                and turn conversations into customers.
            </p>
            <a class="wplf-btn wplf-btn-light"
               href="<?php echo esc_url($leads_url); ?>">
                Explore Your Leads
            </a>
        </div>
        <div class="wplf-hero-art" aria-hidden="true">
            <div class="wplf-orbit wplf-orbit-one"></div>
            <div class="wplf-orbit wplf-orbit-two"></div>
            <span class="dashicons dashicons-chart-area"></span>
        </div>
    </div>

    <div class="wplf-stats-grid">
        <?php foreach ($cards as $card) : ?>
            <div class="wplf-stat-card">
                <div class="wplf-stat-top">
                    <span><?php echo esc_html($card['label']); ?></span>
                    <span class="wplf-stat-icon <?php
                        echo esc_attr($card['color']);
                    ?>">
                        <span class="dashicons <?php
                            echo esc_attr($card['icon']);
                        ?>"></span>
                    </span>
                </div>

                <div class="wplf-stat-value">
                    <?php echo esc_html($card['value']); ?>
                </div>
                <div class="wplf-stat-footer">
                    Based on your saved leads
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="wplf-two-column">

        <div class="wplf-panel">
            <div class="wplf-panel-heading">
                <div>
                    <h3>Sales Pipeline</h3>
                    <p>Lead distribution by current status</p>
                </div>
            </div>

            <?php
            $colors = array(
                'new' => '#7867ed',
                'contacted' => '#a38cf5',
                'qualified' => '#18b6a2',
                'proposal' => '#f0b45e',
                'won' => '#38b979',
                'lost' => '#ee8296'
            );
            ?>

            <div class="wplf-pipeline-bar">
                <?php foreach ($counts as $status => $count) :
                    if ($count < 1 || $total < 1) {
                        continue;
                    }
                    $width = ($count / $total) * 100;
                ?>
                    <div
                        title="<?php echo esc_attr(
                            ucfirst($status) . ': ' . $count
                        ); ?>"
                        style="width:<?php echo esc_attr($width); ?>%;
                               background:<?php echo esc_attr(
                                   $colors[$status]
                               ); ?>">
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="wplf-pipeline-list">
                <?php foreach (WPLF_Leads::statuses() as
                               $status => $label) : ?>
                    <div class="wplf-pipeline-item">
                        <span>
                            <i style="background:<?php
                                echo esc_attr($colors[$status]);
                            ?>"></i>
                            <?php echo esc_html($label); ?>
                        </span>
                        <strong><?php
                            echo esc_html($counts[$status]);
                        ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="wplf-panel wplf-insight-panel">
            <h3>Performance Snapshot</h3>
            <p>Your current lead conversion overview.</p>

            <div class="wplf-big-circle">
                <div>
                    <strong><?php
                        echo esc_html($stats['conversion']);
                    ?>%</strong>
                    <span>Won leads</span>
                </div>
            </div>

            <div class="wplf-mini-summary">
                <div>
                    <strong><?php echo esc_html($counts['won']); ?></strong>
                    <span>Won</span>
                </div>
                <div>
                    <strong><?php echo esc_html($counts['lost']); ?></strong>
                    <span>Lost</span>
                </div>
                <div>
                    <strong><?php echo esc_html(
                        $counts['proposal']
                    ); ?></strong>
                    <span>Proposals</span>
                </div>
            </div>
        </div>
    </div>

    <div class="wplf-panel wplf-recent">
        <div class="wplf-panel-heading">
            <div>
                <h3>Recent Leads</h3>
                <p>Your five latest leads</p>
            </div>
            <a href="<?php echo esc_url($leads_url); ?>">
                View all leads
            </a>
        </div>

        <div class="wplf-table-wrap">
            <table class="wplf-table">
                <thead>
                    <tr>
                        <th>Lead</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent['items'])) : ?>
                        <tr><td colspan="4">
                            No leads yet. Add your first lead.
                        </td></tr>
                    <?php else : ?>
                        <?php foreach ($recent['items'] as $lead) : ?>
                            <tr>
                                <td>
                                    <strong><?php
                                        echo esc_html($lead->name);
                                    ?></strong>
                                    <small><?php
                                        echo esc_html($lead->email);
                                    ?></small>
                                </td>
                                <td><?php
                                    echo esc_html($lead->company);
                                ?></td>
                                <td>
                                    <span class="wplf-status wplf-status-<?php
                                        echo esc_attr($lead->status);
                                    ?>">
                                        <?php echo esc_html(
                                            WPLF_Leads::statuses()[
                                                $lead->status
                                            ] ?? $lead->status
                                        ); ?>
                                    </span>
                                </td>
                                <td><?php
                                    echo esc_html(
                                        mysql2date(
                                            'M j, Y',
                                            $lead->created_at
                                        )
                                    );
                                ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
