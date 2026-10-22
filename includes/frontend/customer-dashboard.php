<?php
/**
 * Customer portal dashboard shortcode (single linked customer view).
 *
 * @package CourierFinancePlugin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode: [kit_customer_portal]
 *
 * @return string
 */
function kit_customer_portal_shortcode()
{
    if (!is_user_logged_in()) {
        $login = kit_customer_login_url();
        ob_start();
        ?>
        <div class="kit-portal kit-portal-guest">
            <p><?php esc_html_e('Please log in to view your shipments and account.', '08600-services-quotations'); ?></p>
            <a class="kit-portal-btn kit-portal-btn-primary" href="<?php echo esc_url($login); ?>"><?php esc_html_e('Customer login', '08600-services-quotations'); ?></a>
        </div>
        <?php
        return ob_get_clean();
    }

    if (!KIT_User_Roles::is_portal_customer() || !KIT_User_Roles::can_access_customer_portal()) {
        return '<div class="kit-portal"><p class="kit-portal-note kit-portal-note-warn">' . esc_html__('This page is only for customer portal accounts.', '08600-services-quotations') . '</p></div>';
    }

    $cust_id = KIT_User_Roles::get_portal_customer_id();
    if ($cust_id <= 0) {
        return '<div class="kit-portal"><p class="kit-portal-note kit-portal-note-warn">' . esc_html__('Your account is not linked to a customer record. Please contact support.', '08600-services-quotations') . '</p></div>';
    }

    if (isset($_GET['edit_profile']) && (string) $_GET['edit_profile'] === '1') {
        return kit_customer_portal_render_edit_profile($cust_id);
    }

    if (isset($_GET['book']) && (string) $_GET['book'] === '1') {
        return kit_customer_portal_render_booking_form($cust_id);
    }

    $customer = function_exists('get_customer_details') ? get_customer_details($cust_id) : false;
    if (!$customer) {
        return '<div class="kit-portal"><p class="kit-portal-note kit-portal-note-warn">' . esc_html__('Your customer record could not be loaded. Please contact 08600.', '08600-services-quotations') . '</p></div>';
    }

    $full_name = trim(($customer['customer_name'] ?? '') . ' ' . ($customer['customer_surname'] ?? ''));
    if ($full_name === '') {
        $full_name = __('Customer', '08600-services-quotations');
    }
    $company = trim((string) ($customer['company_name'] ?? ''));
    $shipments = kit_customer_portal_shipments($cust_id);
    $counts = kit_customer_portal_shipment_counts($shipments);
    $dash = kit_customer_dashboard_url();

    ob_start();
    ?>
    <div class="kit-portal">
        <?php if (isset($_GET['booked'])) : ?>
            <p class="kit-portal-note" role="status"><?php esc_html_e('Your quote request was sent. 08600 will follow up.', '08600-services-quotations'); ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['profile_error'])) : ?>
            <p class="kit-portal-note kit-portal-note-warn" role="alert">
                <?php
                $code = sanitize_text_field(wp_unslash($_GET['profile_error']));
                if ($code === 'invalid_email') {
                    esc_html_e('Please enter a valid email address.', '08600-services-quotations');
                } elseif ($code === 'email_in_use') {
                    esc_html_e('That email address is already used by another account.', '08600-services-quotations');
                } elseif ($code === '1' && !empty($_GET['msg'])) {
                    echo esc_html(rawurldecode(sanitize_text_field(wp_unslash($_GET['msg']))));
                } else {
                    esc_html_e('Something went wrong. Please try again.', '08600-services-quotations');
                }
                ?>
            </p>
        <?php endif; ?>

        <header class="kit-portal-bar">
            <div class="kit-portal-bar-copy">
                <p class="kit-portal-kicker"><?php esc_html_e('Customer portal', '08600-services-quotations'); ?></p>
                <h1><?php echo esc_html($full_name); ?></h1>
                <?php if ($company !== '') : ?>
                    <p class="kit-portal-sub"><?php echo esc_html($company); ?></p>
                <?php endif; ?>
            </div>
            <div class="kit-portal-actions">
                <a class="kit-portal-btn kit-portal-btn-primary" href="<?php echo esc_url(add_query_arg('book', '1', $dash)); ?>"><?php esc_html_e('Request a quote', '08600-services-quotations'); ?></a>
                <a class="kit-portal-btn" href="<?php echo esc_url(add_query_arg('edit_profile', '1', $dash)); ?>"><?php esc_html_e('Edit details', '08600-services-quotations'); ?></a>
            </div>
        </header>

        <?php
        $waybill_tabs = array(
            'new' => array(
                'label' => __('New', '08600-services-quotations'),
                'empty' => __('No new waybills. Fresh bookings will show here.', '08600-services-quotations'),
            ),
            'scheduled' => array(
                'label' => __('Scheduled', '08600-services-quotations'),
                'empty' => __('Nothing scheduled right now.', '08600-services-quotations'),
            ),
            'transit' => array(
                'label' => __('In transit', '08600-services-quotations'),
                'empty' => __('Nothing is in transit.', '08600-services-quotations'),
            ),
            'old' => array(
                'label' => __('Old', '08600-services-quotations'),
                'empty' => __('No delivered waybills yet.', '08600-services-quotations'),
            ),
            'all' => array(
                'label' => __('All', '08600-services-quotations'),
                'empty' => __('No waybills yet. When 08600 books one for you, it will show here.', '08600-services-quotations'),
            ),
        );
        ?>
        <section class="kit-portal-panel" aria-labelledby="kit-portal-shipments-title">
            <div class="kit-portal-panel-head">
                <h2 id="kit-portal-shipments-title"><?php esc_html_e('Waybills', '08600-services-quotations'); ?></h2>
            </div>
            <div class="kit-portal-tabs" role="tablist" aria-label="<?php esc_attr_e('Browse waybills', '08600-services-quotations'); ?>">
                <?php foreach ($waybill_tabs as $tab_key => $tab) :
                    $tab_count = $tab_key === 'all' ? (int) $counts['all'] : (int) ($counts[$tab_key] ?? 0);
                    ?>
                    <button
                        type="button"
                        class="kit-portal-tab<?php echo $tab_key === 'new' ? ' is-active' : ''; ?>"
                        role="tab"
                        id="kit-wb-tab-<?php echo esc_attr($tab_key); ?>"
                        aria-selected="<?php echo $tab_key === 'new' ? 'true' : 'false'; ?>"
                        aria-controls="kit-wb-panel"
                        data-kit-filter="<?php echo esc_attr($tab_key); ?>"
                        data-kit-empty="<?php echo esc_attr($tab['empty']); ?>"
                    >
                        <span class="kit-portal-tab-label"><?php echo esc_html($tab['label']); ?></span>
                        <span class="kit-portal-tab-count"><?php echo (int) $tab_count; ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div id="kit-wb-panel" role="tabpanel" aria-labelledby="kit-wb-tab-new">
                <?php if (!empty($shipments)) : ?>
                    <div class="kit-portal-ship-list">
                        <?php foreach ($shipments as $shipment) :
                            $view = kit_customer_portal_status_view($shipment->status ?? '', $shipment->delivery_status ?? '');
                            $route = kit_customer_portal_route_label($shipment);
                            $when = kit_customer_portal_date_label($shipment->dispatch_date ?? '', $shipment->created_at ?? '');
                            $truck = trim((string) ($shipment->truck_number ?? ''));
                            $ref = trim((string) ($shipment->delivery_reference ?? ''));
                            $meta_bits = array();
                            if ($truck !== '') {
                                $meta_bits[] = sprintf(__('Truck %s', '08600-services-quotations'), $truck);
                            }
                            if ($ref !== '' && strtolower($ref) !== 'pending') {
                                $meta_bits[] = $ref;
                            }
                            if ($when !== '') {
                                $meta_bits[] = $when;
                            }
                            ?>
                            <article class="kit-portal-ship" data-kit-bucket="<?php echo esc_attr($view['bucket']); ?>"<?php echo $view['bucket'] === 'new' ? '' : ' hidden'; ?>>
                                <div class="kit-portal-ship-id">
                                    <span class="kit-portal-ship-label"><?php esc_html_e('Waybill', '08600-services-quotations'); ?></span>
                                    <strong><?php echo esc_html((string) $shipment->waybill_no); ?></strong>
                                </div>
                                <div class="kit-portal-ship-route">
                                    <p><?php echo esc_html($route); ?></p>
                                    <?php if (!empty($meta_bits)) : ?>
                                        <p class="kit-portal-ship-meta"><?php echo esc_html(implode(' · ', $meta_bits)); ?></p>
                                    <?php endif; ?>
                                </div>
                                <p class="kit-portal-status <?php echo esc_attr($view['class']); ?>"><?php echo esc_html($view['label']); ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <div class="kit-portal-empty"<?php echo ((int) ($counts['new'] ?? 0) > 0) ? ' hidden' : ''; ?>>
                    <p class="kit-portal-empty-copy"><?php echo esc_html($waybill_tabs['new']['empty']); ?></p>
                    <a class="kit-portal-btn kit-portal-btn-primary kit-portal-empty-cta" href="<?php echo esc_url(add_query_arg('book', '1', $dash)); ?>"<?php echo ((int) $counts['all'] === 0) ? '' : ' hidden'; ?>><?php esc_html_e('Request a quote', '08600-services-quotations'); ?></a>
                </div>
            </div>
        </section>

        <?php kit_customer_portal_render_booking_list($cust_id); ?>

        <section class="kit-portal-panel" aria-labelledby="kit-portal-details-title">
            <div class="kit-portal-panel-head">
                <h2 id="kit-portal-details-title"><?php esc_html_e('Your details', '08600-services-quotations'); ?></h2>
                <a class="kit-portal-textlink" href="<?php echo esc_url(add_query_arg('edit_profile', '1', $dash)); ?>"><?php esc_html_e('Edit', '08600-services-quotations'); ?></a>
            </div>
            <dl class="kit-portal-facts">
                <div>
                    <dt><?php esc_html_e('Name', '08600-services-quotations'); ?></dt>
                    <dd><?php echo esc_html($full_name); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Phone', '08600-services-quotations'); ?></dt>
                    <dd><?php echo esc_html(($customer['cell'] ?? '') !== '' ? $customer['cell'] : '—'); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Email', '08600-services-quotations'); ?></dt>
                    <dd><?php echo esc_html(($customer['email_address'] ?? '') !== '' ? $customer['email_address'] : '—'); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Customer number', '08600-services-quotations'); ?></dt>
                    <dd><?php echo esc_html((string) $cust_id); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('Country', '08600-services-quotations'); ?></dt>
                    <dd><?php echo esc_html(($customer['country_name'] ?? '') !== '' ? $customer['country_name'] : '—'); ?></dd>
                </div>
                <div>
                    <dt><?php esc_html_e('City', '08600-services-quotations'); ?></dt>
                    <dd><?php echo esc_html(($customer['city_name'] ?? '') !== '' ? $customer['city_name'] : '—'); ?></dd>
                </div>
                <div class="kit-portal-facts-wide">
                    <dt><?php esc_html_e('Address', '08600-services-quotations'); ?></dt>
                    <dd><?php echo esc_html(($customer['address'] ?? '') !== '' ? $customer['address'] : '—'); ?></dd>
                </div>
            </dl>
        </section>
    </div>
    <script>
    (function () {
        var root = document.querySelector('.kit-portal');
        if (!root) return;
        var buttons = root.querySelectorAll('[data-kit-filter]');
        var rows = root.querySelectorAll('#kit-wb-panel .kit-portal-ship');
        var empty = root.querySelector('.kit-portal-empty');
        var copy = root.querySelector('.kit-portal-empty-copy');
        var cta = root.querySelector('.kit-portal-empty-cta');
        var panel = document.getElementById('kit-wb-panel');
        if (!buttons.length) return;

        function showTab(button) {
            var filter = button.getAttribute('data-kit-filter');
            var visible = 0;
            buttons.forEach(function (other) {
                var on = other === button;
                other.classList.toggle('is-active', on);
                other.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            rows.forEach(function (row) {
                var show = filter === 'all' || row.getAttribute('data-kit-bucket') === filter;
                row.hidden = !show;
                if (show) visible++;
            });
            if (empty) empty.hidden = visible !== 0;
            if (copy) copy.textContent = button.getAttribute('data-kit-empty') || '';
            if (cta) cta.hidden = filter !== 'all' || rows.length !== 0;
            if (panel) panel.setAttribute('aria-labelledby', button.id);
        }

        buttons.forEach(function (button) {
            button.addEventListener('click', function () {
                showTab(button);
            });
        });
    })();
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Waybills for one portal customer, newest first.
 *
 * @param int $cust_id
 * @return array<int,object>
 */
function kit_customer_portal_shipments($cust_id)
{
    global $wpdb;
    $cust_id = (int) $cust_id;
    if ($cust_id <= 0) {
        return array();
    }

    $waybills = $wpdb->prefix . 'kit_waybills';
    $deliveries = $wpdb->prefix . 'kit_deliveries';
    $directions = $wpdb->prefix . 'kit_shipping_directions';
    $countries = $wpdb->prefix . 'kit_operating_countries';
    $cities = $wpdb->prefix . 'kit_operating_cities';

    $sql = "SELECT
            w.waybill_no,
            w.status,
            w.created_at,
            w.total_mass_kg,
            d.status AS delivery_status,
            d.delivery_reference,
            d.dispatch_date,
            d.truck_number,
            origin_country.country_name AS origin_country_name,
            dest_country.country_name AS destination_country_name,
            dest_city.city_name AS destination_city
        FROM {$waybills} w
        LEFT JOIN {$deliveries} d ON w.delivery_id = d.id
        LEFT JOIN {$directions} dir ON d.direction_id = dir.id
        LEFT JOIN {$countries} origin_country ON dir.origin_country_id = origin_country.id
        LEFT JOIN {$countries} dest_country ON dir.destination_country_id = dest_country.id
        LEFT JOIN {$cities} dest_city ON w.city_id = dest_city.id
        WHERE w.customer_id = %d
        ORDER BY w.created_at DESC, w.id DESC";

    $rows = $wpdb->get_results($wpdb->prepare($sql, $cust_id));
    return is_array($rows) ? $rows : array();
}

/**
 * @param array<int,object> $shipments
 * @return array{all:int,new:int,transit:int,scheduled:int,old:int}
 */
function kit_customer_portal_shipment_counts($shipments)
{
    $counts = array(
        'all' => count($shipments),
        'new' => 0,
        'scheduled' => 0,
        'transit' => 0,
        'old' => 0,
    );
    foreach ($shipments as $shipment) {
        $view = kit_customer_portal_status_view($shipment->status ?? '', $shipment->delivery_status ?? '');
        if (isset($counts[$view['bucket']])) {
            $counts[$view['bucket']]++;
        }
    }
    return $counts;
}

/**
 * Customer-facing status. Delivery status wins when the waybill is on a trip.
 *
 * @param string $waybill_status
 * @param string $delivery_status
 * @return array{bucket:string,label:string,class:string}
 */
function kit_customer_portal_status_view($waybill_status, $delivery_status)
{
    $delivery = strtolower(trim((string) $delivery_status));
    $waybill = strtolower(trim((string) $waybill_status));
    $key = $waybill;
    if (in_array($delivery, array('in_transit', 'delivered', 'scheduled', 'unconfirmed'), true)) {
        $key = $delivery;
    }

    $map = array(
        'in_transit' => array('bucket' => 'transit', 'label' => __('In transit', '08600-services-quotations'), 'class' => 'is-transit'),
        'shipped' => array('bucket' => 'transit', 'label' => __('In transit', '08600-services-quotations'), 'class' => 'is-transit'),
        'scheduled' => array('bucket' => 'scheduled', 'label' => __('Scheduled', '08600-services-quotations'), 'class' => 'is-scheduled'),
        'assigned' => array('bucket' => 'scheduled', 'label' => __('Scheduled', '08600-services-quotations'), 'class' => 'is-scheduled'),
        'unconfirmed' => array('bucket' => 'scheduled', 'label' => __('Scheduled', '08600-services-quotations'), 'class' => 'is-scheduled'),
        'delivered' => array('bucket' => 'old', 'label' => __('Delivered', '08600-services-quotations'), 'class' => 'is-delivered'),
        'completed' => array('bucket' => 'old', 'label' => __('Delivered', '08600-services-quotations'), 'class' => 'is-delivered'),
        'quoted' => array('bucket' => 'new', 'label' => __('Quoted', '08600-services-quotations'), 'class' => 'is-other'),
        'invoiced' => array('bucket' => 'new', 'label' => __('Invoiced', '08600-services-quotations'), 'class' => 'is-other'),
        'rejected' => array('bucket' => 'new', 'label' => __('Rejected', '08600-services-quotations'), 'class' => 'is-other'),
        'pending' => array('bucket' => 'new', 'label' => __('Pending', '08600-services-quotations'), 'class' => 'is-other'),
    );

    return $map[$key] ?? array(
        'bucket' => 'new',
        'label' => $key !== '' ? ucwords(str_replace('_', ' ', $key)) : __('Pending', '08600-services-quotations'),
        'class' => 'is-other',
    );
}

/**
 * @param object $shipment
 * @return string
 */
function kit_customer_portal_route_label($shipment)
{
    $from = trim((string) ($shipment->origin_country_name ?? ''));
    $city = trim((string) ($shipment->destination_city ?? ''));
    $to_country = trim((string) ($shipment->destination_country_name ?? ''));
    $to = $city !== '' ? $city : $to_country;
    if ($to !== '' && $to_country !== '' && $city !== '' && strcasecmp($city, $to_country) !== 0) {
        $to .= ', ' . $to_country;
    }
    if ($from !== '' && $to !== '') {
        return $from . ' → ' . $to;
    }
    if ($to !== '') {
        return $to;
    }
    if ($from !== '') {
        return $from;
    }
    return __('Route not set', '08600-services-quotations');
}

/**
 * Prefer dispatch date, otherwise the date the waybill was created.
 *
 * @param string $dispatch
 * @param string $created
 * @return string
 */
function kit_customer_portal_date_label($dispatch, $created)
{
    $raw = trim((string) $dispatch);
    $prefix = __('Dispatch', '08600-services-quotations');
    if ($raw === '' || $raw === '0000-00-00') {
        $raw = trim((string) $created);
        $prefix = __('Booked', '08600-services-quotations');
    }
    if ($raw === '' || $raw === '0000-00-00' || $raw === '0000-00-00 00:00:00') {
        return '';
    }
    $ts = strtotime($raw);
    if (!$ts) {
        return '';
    }
    $formatted = function_exists('date_i18n') ? date_i18n('j M Y', $ts) : date('j M Y', $ts);
    return $prefix . ' ' . $formatted;
}
