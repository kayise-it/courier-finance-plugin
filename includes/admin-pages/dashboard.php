<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . '../user-roles.php';
require_once plugin_dir_path(__FILE__) . '../dashboard/dashboard-functions.php';
require_once plugin_dir_path(__FILE__) . '../components/quickStats.php';
require_once plugin_dir_path(__FILE__) . '../components/dashboardQuickies.php';
require_once plugin_dir_path(__FILE__) . '../warehouse/warehouse-functions.php';

$currency = function_exists('KIT_Commons') ? KIT_Commons::currency() : 'R';

$today_waybills   = KIT_Dashboard::get_today_waybill_count();
$warehouse_stats  = KIT_Warehouse::getWarehouseStats();
$in_warehouse     = (int) ($warehouse_stats->in_warehouse ?? 0);
$today_deliveries = KIT_Dashboard::get_today_deliveries_count();
$revenue_today    = KIT_Dashboard::get_revenue_today();
$revenue_month    = KIT_Dashboard::get_revenue_this_month();
$delivery_counts  = KIT_Dashboard::get_delivery_status_counts();
$upcoming         = KIT_Dashboard::get_upcoming_deliveries(7);
$recent_waybills  = KIT_Dashboard::get_recent_waybills(10);

$deliveries_url = admin_url('admin.php?page=kit-deliveries');
$waybills_url   = admin_url('admin.php?page=08600-waybill-manage');
$warehouse_url  = admin_url('admin.php?page=warehouse-waybills');

$kpi_stats = [
    [
        'title'     => 'Waybills today',
        'value'     => number_format($today_waybills),
        'icon'      => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
        'color'     => 'blue',
        'clickable' => true,
        'onclick'   => "window.location.href='" . esc_js($waybills_url) . "'",
    ],
    [
        'title'     => 'In warehouse',
        'value'     => number_format($in_warehouse),
        'icon'      => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'color'     => 'orange',
        'clickable' => true,
        'onclick'   => "window.location.href='" . esc_js($warehouse_url) . "'",
    ],
    [
        'title'     => 'Today\'s deliveries',
        'value'     => number_format($today_deliveries),
        'icon'      => 'M13 10V3L4 14h7v7l9-11h-7z',
        'color'     => 'yellow',
        'clickable' => true,
        'onclick'   => "window.location.href='" . esc_js($deliveries_url) . "'",
    ],
    [
        'title'   => 'Revenue today',
        'value'   => $currency . ' ' . number_format($revenue_today, 2),
        'icon'    => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'color'   => 'green',
    ],
    [
        'title'   => 'Revenue this month',
        'value'   => $currency . ' ' . number_format($revenue_month, 2),
        'icon'    => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        'color'   => 'purple',
    ],
];

$delivery_stats = [
    [
        'title'     => 'Scheduled',
        'value'     => number_format($delivery_counts->scheduled),
        'icon'      => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'color'     => 'green',
        'clickable' => true,
        'onclick'   => "window.location.href='" . esc_js($deliveries_url) . "'",
    ],
    [
        'title'     => 'In transit',
        'value'     => number_format($delivery_counts->in_transit),
        'icon'      => 'M13 10V3L4 14h7v7l9-11h-7z',
        'color'     => 'yellow',
        'clickable' => true,
        'onclick'   => "window.location.href='" . esc_js($deliveries_url) . "'",
    ],
    [
        'title'     => 'Delivered',
        'value'     => number_format($delivery_counts->delivered),
        'icon'      => 'M5 13l4 4L19 7',
        'color'     => 'blue',
        'clickable' => true,
        'onclick'   => "window.location.href='" . esc_js($deliveries_url) . "'",
    ],
];

$quick_actions = [
    [ 'title' => 'Create Waybill', 'href' => admin_url('admin.php?page=08600-waybill-create'), 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6', 'color' => 'blue' ],
    [ 'title' => 'Warehouse', 'href' => admin_url('admin.php?page=warehouse-waybills'), 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'color' => 'orange' ],
    [ 'title' => 'Deliveries', 'href' => admin_url('admin.php?page=kit-deliveries'), 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'color' => 'blue' ],
    [ 'title' => 'Customers', 'href' => admin_url('admin.php?page=08600-customers'), 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', 'color' => 'green' ],
    [ 'title' => 'Routes', 'href' => admin_url('admin.php?page=route-management'), 'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 4m0 13V4m-6 3l6-3', 'color' => 'purple' ],
];
?>
<div class="wrap kit-dashboard-wrap kit-dashboard-modern" id="kit-dashboard">
    <?php
    echo KIT_Commons::showingHeader([
        'title' => 'Dashboard',
        'desc'  => 'Overview of waybills, warehouse, deliveries and revenue',
        'icon'  => KIT_Commons::icon('truck'),
    ]);
    ?>
    <hr class="wp-header-end">

    <div class="kit-dashboard-content">
        <!-- Hero KPIs -->
        <section class="kit-dashboard-hero">
            <div class="kit-dashboard-kpis">
                <?php
                echo KIT_QuickStats::render($kpi_stats, '', [
                    'grid_cols' => 'grid-cols-5',
                    'gap'       => 'gap-4',
                ]);
                ?>
            </div>
        </section>

        <!-- Delivery status strip + Quick actions -->
        <section class="kit-dashboard-actions-row">
            <div class="kit-dashboard-quickies">
                <?php echo KIT_DashboardQuickies::render($quick_actions, ''); ?>
            </div>
        </section>

        <!-- Route map (col-6) + Upcoming deliveries (col-3) + Recent waybills (col-3) -->
        <section class="kit-dashboard-grid kit-dashboard-grid--map-list">
            <!-- Route map col-6 -->
            <article class="kit-dashboard-card kit-dashboard-card--map kit-dashboard-col-6">
                <header class="kit-dashboard-card-header">
                    <div>
                        <h2 class="kit-dashboard-card-title">Route map</h2>
                        <p class="kit-dashboard-card-desc">Planned delivery routes (next 7 days)</p>
                    </div>
                </header>
                <div id="kit-dashboard-map" class="kit-dashboard-map-inner"></div>
            </article>

            <!-- Upcoming deliveries col-3 -->
            <article class="kit-dashboard-card kit-dashboard-card--list kit-dashboard-col-3">
                <header class="kit-dashboard-card-header">
                    <h2 class="kit-dashboard-card-title">Upcoming deliveries</h2>
                    <a href="<?php echo esc_url($deliveries_url); ?>" class="kit-dashboard-card-link">View all</a>
                </header>
                <div class="kit-dashboard-card-body">
                    <?php if (empty($upcoming)) : ?>
                        <p class="kit-dashboard-empty">No upcoming deliveries.</p>
                    <?php else : ?>
                        <ul class="kit-dashboard-list">
                            <?php foreach ($upcoming as $d) : ?>
                                <?php
                                $route = esc_html(($d->origin_country_name ?? '') . ' → ' . ($d->destination_country_name ?? ''));
                                $date  = !empty($d->dispatch_date) && $d->dispatch_date !== '0000-00-00'
                                    ? date('M j, Y', strtotime($d->dispatch_date))
                                    : '';
                                $view_url = admin_url('admin.php?page=view-deliveries&delivery_id=' . (int) $d->id);
                                $driver_display = trim(implode(' · ', array_filter([$d->truck_number ?? '', $d->driver_name ?? ''])));
                                ?>
                                <li>
                                    <a href="<?php echo esc_url($view_url); ?>" class="kit-dashboard-list-item">
                                        <div class="kit-dashboard-list-item-main">
                                            <span class="kit-dashboard-list-item-title"><?php echo esc_html($d->delivery_reference); ?></span>
                                            <span class="kit-dashboard-list-item-meta"><?php echo esc_html($date); ?></span>
                                        </div>
                                        <div class="kit-dashboard-list-item-sub"><?php echo $route; ?></div>
                                        <?php if ($driver_display !== '') : ?>
                                            <div class="kit-dashboard-list-item-extra"><?php echo esc_html($driver_display); ?></div>
                                        <?php endif; ?>
                                        <span class="kit-dashboard-list-item-badge"><?php echo (int) $d->waybill_count; ?> waybills</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </article>

            <!-- Recent waybills col-3 (list layout – no table overflow) -->
            <article class="kit-dashboard-card kit-dashboard-card--list kit-dashboard-col-3">
                <header class="kit-dashboard-card-header">
                    <h2 class="kit-dashboard-card-title">Recent waybills</h2>
                    <a href="<?php echo esc_url($waybills_url); ?>" class="kit-dashboard-card-link">View all</a>
                </header>
                <div class="kit-dashboard-card-body">
                    <?php if (empty($recent_waybills)) : ?>
                        <p class="kit-dashboard-empty">No waybills yet.</p>
                    <?php else : ?>
                        <ul class="kit-dashboard-waybill-list">
                            <?php foreach ($recent_waybills as $w) : ?>
                                <?php
                                $view_url = admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . (int) $w->waybill_id);
                                $name = trim(($w->customer_name ?? '') . ' ' . ($w->customer_surname ?? ''));
                                $created = !empty($w->created_at) ? date('M j, Y', strtotime($w->created_at)) : '';
                                ?>
                                <li class="kit-dashboard-waybill-item">
                                    <a href="<?php echo esc_url($view_url); ?>" class="kit-dashboard-waybill-item-link">
                                        <span class="kit-dashboard-waybill-row kit-dashboard-waybill-row--head">
                                            <span class="kit-dashboard-waybill-no"><?php echo esc_html($w->waybill_no); ?></span>
                                            <span class="kit-dashboard-waybill-date"><?php echo esc_html($created); ?></span>
                                        </span>
                                        <span class="kit-dashboard-waybill-row kit-dashboard-waybill-row--customer"><?php echo esc_html($name ?: '—'); ?></span>
                                        <span class="kit-dashboard-waybill-row kit-dashboard-waybill-row--dest">
                                            <?php echo esc_html($w->destination ?: '—'); ?>
                                            <span class="kit-dashboard-status-pill kit-dashboard-status-pill--inline"><?php echo esc_html($w->status ?? ''); ?></span>
                                        </span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </article>
        </section>
    </div>
</div>
