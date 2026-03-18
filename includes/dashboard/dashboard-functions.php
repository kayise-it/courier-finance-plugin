<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard helper functions: KPIs, upcoming deliveries, recent waybills, map data.
 */
class KIT_Dashboard {

    public static function init() {
        add_action('wp_ajax_kit_dashboard_map_deliveries', [self::class, 'ajax_map_deliveries']);
    }

    /**
     * AJAX: return deliveries for route map (next 7 days, scheduled/in_transit).
     */
    public static function ajax_map_deliveries() {
        if (!current_user_can('kit_view_waybills')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }
        $nonce = isset($_POST['nonce']) ? sanitize_text_field($_POST['nonce']) : '';
        if (!wp_verify_nonce($nonce, 'kit_dashboard_map')) {
            wp_send_json_error(['message' => 'Invalid nonce']);
        }
        wp_send_json_success(self::get_deliveries_for_map());
    }

    /**
     * Count waybills created today.
     *
     * @return int
     */
    public static function get_today_waybill_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_waybills';
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = CURDATE()"
        );
        return $count ? (int) $count : 0;
    }

    /**
     * Revenue today: sum of (product_invoice_amount + misc_total from miscellaneous JSON).
     *
     * @return float
     */
    public static function get_revenue_today() {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_waybills';
        $rows  = $wpdb->get_results("SELECT id, product_invoice_amount, miscellaneous FROM {$table} WHERE DATE(created_at) = CURDATE()");
        return self::sum_revenue_rows($rows);
    }

    /**
     * Revenue this month: same sum for current calendar month.
     *
     * @return float
     */
    public static function get_revenue_this_month() {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_waybills';
        $rows  = $wpdb->get_results(
            "SELECT id, product_invoice_amount, miscellaneous FROM {$table}
             WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
             AND created_at < DATE_ADD(DATE_FORMAT(CURDATE(), '%Y-%m-01'), INTERVAL 1 MONTH)"
        );
        return self::sum_revenue_rows($rows);
    }

    /**
     * Sum revenue (product_invoice_amount + misc_total) from waybill rows.
     *
     * @param array $rows Rows with product_invoice_amount, miscellaneous
     * @return float
     */
    private static function sum_revenue_rows($rows) {
        $total = 0.0;
        if (!is_array($rows)) {
            return $total;
        }
        foreach ($rows as $r) {
            $invoice = (float) ($r->product_invoice_amount ?? 0);
            $misc    = 0.0;
            if (!empty($r->miscellaneous)) {
                $data = json_decode($r->miscellaneous, true);
                if (is_array($data) && isset($data['misc_total'])) {
                    $misc = (float) $data['misc_total'];
                }
            }
            $total += $invoice + $misc;
        }
        return $total;
    }

    /**
     * Count deliveries with dispatch_date = today and status in (scheduled, in_transit).
     *
     * @return int
     */
    public static function get_today_deliveries_count() {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_deliveries';
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table}
             WHERE dispatch_date = CURDATE()
             AND status IN ('scheduled', 'in_transit')
             AND delivery_reference != 'pending'"
        );
        return $count ? (int) $count : 0;
    }

    /**
     * Delivery status counts: scheduled, in_transit, delivered (excluding pending reference).
     *
     * @return object { scheduled, in_transit, delivered }
     */
    public static function get_delivery_status_counts() {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_deliveries';
        $row = $wpdb->get_row(
            "SELECT
                SUM(CASE WHEN status = 'scheduled' THEN 1 ELSE 0 END) AS scheduled,
                SUM(CASE WHEN status = 'in_transit' THEN 1 ELSE 0 END) AS in_transit,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) AS delivered
             FROM {$table}
             WHERE delivery_reference != 'pending'"
        );
        return (object) [
            'scheduled'   => $row && $row->scheduled !== null ? (int) $row->scheduled : 0,
            'in_transit' => $row && $row->in_transit !== null ? (int) $row->in_transit : 0,
            'delivered'  => $row && $row->delivered !== null ? (int) $row->delivered : 0,
        ];
    }

    /**
     * Upcoming deliveries: next 5–7 scheduled/in_transit, ordered by dispatch_date ASC.
     *
     * @param int $limit
     * @return array
     */
    public static function get_upcoming_deliveries($limit = 7) {
        global $wpdb;
        $d_table   = $wpdb->prefix . 'kit_deliveries';
        $sd_table  = $wpdb->prefix . 'kit_shipping_directions';
        $oc_table  = $wpdb->prefix . 'kit_operating_countries';
        $wb_table  = $wpdb->prefix . 'kit_waybills';
        $dr_table  = $wpdb->prefix . 'kit_drivers';

        $driver_join = '';
        $driver_cols = '';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$dr_table}'") === $dr_table) {
            $col = $wpdb->get_var($wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'driver_id'",
                DB_NAME,
                $d_table
            ));
            if ($col) {
                $driver_join = "LEFT JOIN {$dr_table} dr ON d.driver_id = dr.id";
                $driver_cols = ", dr.name AS driver_name";
            }
        }

        $sql = "SELECT
                d.id,
                d.delivery_reference,
                d.dispatch_date,
                d.truck_number,
                d.status,
                oc1.country_name AS origin_country_name,
                oc2.country_name AS destination_country_name,
                (SELECT COUNT(*) FROM {$wb_table} w WHERE w.delivery_id = d.id) AS waybill_count
                {$driver_cols}
             FROM {$d_table} d
             LEFT JOIN {$sd_table} sd ON d.direction_id = sd.id
             LEFT JOIN {$oc_table} oc1 ON sd.origin_country_id = oc1.id
             LEFT JOIN {$oc_table} oc2 ON sd.destination_country_id = oc2.id
             {$driver_join}
             WHERE d.delivery_reference != 'pending'
             AND d.status IN ('scheduled', 'in_transit')
             AND d.dispatch_date >= CURDATE()
             ORDER BY d.dispatch_date ASC
             LIMIT " . (int) $limit;

        return $wpdb->get_results($sql);
    }

    /**
     * Recent waybills: last N waybills with customer and destination info.
     *
     * @param int $limit
     * @return array
     */
    public static function get_recent_waybills($limit = 10) {
        global $wpdb;
        $w_table  = $wpdb->prefix . 'kit_waybills';
        $c_table  = $wpdb->prefix . 'kit_customers';
        $cities   = $wpdb->prefix . 'kit_operating_cities';
        $sd_table = $wpdb->prefix . 'kit_shipping_directions';
        $oc_table = $wpdb->prefix . 'kit_operating_countries';

        $sql = "SELECT
                w.id AS waybill_id,
                w.waybill_no,
                w.status,
                w.created_at,
                w.customer_id AS customer_id,
                c.name AS customer_name,
                c.surname AS customer_surname,
                COALESCE(ci.city_name, oc.country_name, '') AS destination
             FROM {$w_table} w
             LEFT JOIN {$c_table} c ON w.customer_id = c.cust_id
             LEFT JOIN {$sd_table} sd ON w.direction_id = sd.id
             LEFT JOIN {$oc_table} oc ON sd.destination_country_id = oc.id
             LEFT JOIN {$cities} ci ON w.city_id = ci.id
             ORDER BY w.created_at DESC
             LIMIT " . (int) $limit;

        return $wpdb->get_results($sql);
    }

    /**
     * Customer ID of the most recent waybill (for "Recent Customer" button on create waybill form).
     *
     * @return int Customer ID or 0 if none.
     */
    public static function get_last_waybill_customer_id() {
        $recent = self::get_recent_waybills(1);
        if (empty($recent) || empty($recent[0]->customer_id)) {
            return 0;
        }
        return (int) $recent[0]->customer_id;
    }

    /**
     * Deliveries for map: active (scheduled + in_transit) with origin/destination and city for geocoding.
     * Filter: today and next 7 days to avoid overcrowding.
     *
     * @return array
     */
    public static function get_deliveries_for_map() {
        global $wpdb;
        $d_table  = $wpdb->prefix . 'kit_deliveries';
        $sd_table = $wpdb->prefix . 'kit_shipping_directions';
        $oc_table = $wpdb->prefix . 'kit_operating_countries';
        $ci_table = $wpdb->prefix . 'kit_operating_cities';
        $dr_table = $wpdb->prefix . 'kit_drivers';

        $driver_join = '';
        $driver_col = '';
        if ($wpdb->get_var("SHOW TABLES LIKE '{$dr_table}'") === $dr_table) {
            $col = $wpdb->get_var($wpdb->prepare(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'driver_id'",
                DB_NAME,
                $d_table
            ));
            if ($col) {
                $driver_join = "LEFT JOIN {$dr_table} dr ON d.driver_id = dr.id";
                $driver_col = ", dr.name AS driver_name";
            }
        }

        $sql = "SELECT
                d.id,
                d.delivery_reference,
                d.dispatch_date,
                d.truck_number,
                d.status,
                oc1.country_name AS origin_country,
                oc2.country_name AS destination_country,
                COALESCE(ci.city_name, '') AS destination_city
                {$driver_col}
             FROM {$d_table} d
             LEFT JOIN {$sd_table} sd ON d.direction_id = sd.id
             LEFT JOIN {$oc_table} oc1 ON sd.origin_country_id = oc1.id
             LEFT JOIN {$oc_table} oc2 ON sd.destination_country_id = oc2.id
             LEFT JOIN {$ci_table} ci ON d.destination_city_id = ci.id
             {$driver_join}
             WHERE d.delivery_reference != 'pending'
             AND d.status IN ('scheduled', 'in_transit')
             AND d.dispatch_date >= CURDATE()
             AND d.dispatch_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
             ORDER BY d.dispatch_date ASC";

        $rows = $wpdb->get_results($sql);
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'                  => (int) $r->id,
                'delivery_reference'  => $r->delivery_reference,
                'dispatch_date'       => $r->dispatch_date,
                'truck_number'        => $r->truck_number ?? '',
                'driver_name'         => isset($r->driver_name) ? $r->driver_name : '',
                'origin_country'      => $r->origin_country ?? '',
                'destination_country' => $r->destination_country ?? '',
                'destination_city'    => $r->destination_city ?? '',
            ];
        }
        return $out;
    }
}
