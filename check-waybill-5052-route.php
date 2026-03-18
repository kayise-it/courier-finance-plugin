<?php
/**
 * One-off script: verify waybill 5052 route data from DB.
 * Run from WordPress root: php -r "define('ABSPATH','./'); require 'wp-load.php'; require 'wp-content/plugins/courier-finance-plugin/check-waybill-5052-route.php';"
 * Or from plugin dir (if WP is loadable): php check-waybill-5052-route.php
 * Or open in browser as admin: add ?waybill_route_check=5052 to an admin page URL (see below).
 */
if (!defined('ABSPATH')) {
    $wp_load = dirname(__FILE__, 3) . '/wp-load.php';
    if (file_exists($wp_load)) {
        require_once $wp_load;
    }
}
if (!defined('ABSPATH') || !isset($wpdb)) {
    die("Could not load WordPress. Run from site root: php -r \"require 'wp-load.php'; require 'wp-content/plugins/courier-finance-plugin/check-waybill-5052-route.php';\"\n");
}

$waybill_no_or_id = '5052';
if (defined('RUN_WAYBILL_ROUTE_CHECK')) {
    $waybill_no_or_id = (string) RUN_WAYBILL_ROUTE_CHECK;
} elseif (isset($_GET['waybill_route_check'])) {
    $waybill_no_or_id = sanitize_text_field($_GET['waybill_route_check']);
}
$pref = $wpdb->prefix;

// Resolve to waybill row (by id or waybill_no)
$waybill = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT w.id, w.waybill_no, w.delivery_id, w.city_id AS waybill_city_id, w.miscellaneous " .
        "FROM {$pref}kit_waybills w WHERE w.waybill_no = %s OR w.id = %s LIMIT 1",
        $waybill_no_or_id,
        $waybill_no_or_id
    ),
    ARRAY_A
);

if (!$waybill) {
    echo "Waybill not found for: " . esc_html($waybill_no_or_id) . "\n";
    exit;
}

$delivery_id = (int) ($waybill['delivery_id'] ?? 0);
$misc = !empty($waybill['miscellaneous']) ? maybe_unserialize($waybill['miscellaneous']) : [];
$others = is_array($misc) && isset($misc['others']) ? $misc['others'] : [];

$origin_country_id = (int) ($others['origin_country_id'] ?? 0);
$origin_city_id = (int) ($others['origin_city_id'] ?? 0);
$destination_country_id = (int) ($others['destination_country_id'] ?? 0);
$destination_city_id = (int) ($others['destination_city_id'] ?? 0);
$waybill_destination_city_id = (int) ($waybill['waybill_city_id'] ?? 0);

// From delivery/direction if we have delivery_id
$delivery_row = null;
$direction_row = null;
if ($delivery_id) {
    $delivery_row = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT dt.destination_city_id, dt.direction_id FROM {$pref}kit_deliveries dt WHERE dt.id = %d",
            $delivery_id
        ),
        ARRAY_A
    );
    if ($delivery_row && !empty($delivery_row['direction_id'])) {
        $direction_row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT sd.origin_country_id, sd.destination_country_id FROM {$pref}kit_shipping_directions sd WHERE sd.id = %d",
                $delivery_row['direction_id']
            ),
            ARRAY_A
        );
    }
}

// Country/city names
$country_name = function ($id) use ($wpdb, $pref) {
    if ((int) $id <= 0) return '(none)';
    $n = $wpdb->get_var($wpdb->prepare("SELECT country_name FROM {$pref}kit_operating_countries WHERE id = %d", $id));
    return $n ?: "(id=$id)";
};
$city_name = function ($id) use ($wpdb, $pref) {
    if ((int) $id <= 0) return '(none)';
    $n = $wpdb->get_var($wpdb->prepare("SELECT city_name FROM {$pref}kit_cities WHERE id = %d", $id));
    return $n ?: "(id=$id)";
};

header('Content-Type: text/plain; charset=utf-8');
echo "=== Waybill " . esc_html($waybill['waybill_no']) . " (id=" . (int) $waybill['id'] . ") Route from DB ===\n\n";

echo "1) Waybill row:\n";
echo "   delivery_id = " . (int) $waybill['delivery_id'] . "\n";
echo "   city_id (waybill) = " . (int) $waybill['waybill_city_id'] . " " . $city_name($waybill['waybill_city_id']) . "\n\n";

echo "2) miscellaneous.others (saved on waybill):\n";
echo "   origin_country_id = " . $origin_country_id . " " . $country_name($origin_country_id) . "\n";
echo "   origin_city_id = " . $origin_city_id . " " . $city_name($origin_city_id) . "\n";
echo "   destination_country_id = " . $destination_country_id . " " . $country_name($destination_country_id) . "\n";
echo "   destination_city_id = " . $destination_city_id . " " . $city_name($destination_city_id) . "\n\n";

if ($delivery_row) {
    echo "3) Delivery (id=" . $delivery_id . "):\n";
    echo "   destination_city_id = " . (int) ($delivery_row['destination_city_id'] ?? 0) . " " . $city_name($delivery_row['destination_city_id'] ?? 0) . "\n";
    echo "   direction_id = " . (int) ($delivery_row['direction_id'] ?? 0) . "\n";
}
if ($direction_row) {
    echo "4) Direction:\n";
    echo "   origin_country_id = " . (int) ($direction_row['origin_country_id'] ?? 0) . " " . $country_name($direction_row['origin_country_id'] ?? 0) . "\n";
    echo "   destination_country_id = " . (int) ($direction_row['destination_country_id'] ?? 0) . " " . $country_name($direction_row['destination_country_id'] ?? 0) . "\n";
}

echo "\nExpected in Edit Waybill form:\n";
echo "   Origin: " . $country_name($origin_country_id) . ", " . $city_name($origin_city_id) . "\n";
$dest_city = $destination_city_id ?: $waybill_destination_city_id;
echo "   Destination: " . $country_name($destination_country_id) . ", " . ($dest_city ? $city_name($dest_city) : "Select City (no city in DB)") . "\n";
