<?php
// Simulate what the delete would do for a given waybill_no, without deleting
// Usage: php simulate_delete.php 4031

define('WP_USE_THEMES', false);
$root = dirname(__DIR__, 3); // /Applications/MAMP/htdocs/08600
require_once $root . '/wp-load.php';

global $wpdb;

$waybill_no = null;
$apply = false;
if (php_sapi_name() === 'cli') {
    $waybill_no = isset($argv[1]) ? (int)$argv[1] : null;
    $apply = in_array('--apply', $argv, true);
} else {
    $waybill_no = isset($_GET['waybill_no']) ? (int)$_GET['waybill_no'] : null;
    $apply = isset($_GET['apply']) && $_GET['apply'] == '1';
}

if (!$waybill_no) {
    echo "Provide waybill_no as first argument or ?waybill_no=...\n";
    exit(1);
}

$prefix = $wpdb->prefix;
$waybills_table = $prefix . 'kit_waybills';
$items_table = $prefix . 'kit_waybill_items';
$tracking_table = $prefix . 'kit_warehouse_tracking';

$waybill = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$waybills_table} WHERE waybill_no = %d LIMIT 1", $waybill_no), ARRAY_A);
$items_count = (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$items_table} WHERE waybillno = %d", $waybill_no));

$tracking_exists = ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $tracking_table)) === $tracking_table);
$tracking_count = $tracking_exists ? (int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$tracking_table} WHERE waybill_no = %d", $waybill_no)) : 0;

$result = [
    'waybill_no' => $waybill_no,
    'exists' => (bool)$waybill,
    'waybill_row' => $waybill,
    'items_to_delete' => $items_count,
    'tracking_rows_to_delete' => $tracking_count,
    'tables' => [
        'waybills' => $waybills_table,
        'items' => $items_table,
        'tracking' => $tracking_table,
    ],
];

if ($apply && $waybill) {
    // Delete items first
    $wpdb->delete($items_table, ['waybillno' => $waybill_no]);
    // Delete tracking entries if table exists
    if ($tracking_exists) {
        $wpdb->delete($tracking_table, ['waybill_no' => $waybill_no]);
    }
    // Delete waybill
    $deleted = $wpdb->delete($waybills_table, ['waybill_no' => $waybill_no]);
    $result['deleted'] = $deleted;
    $result['last_error'] = $wpdb->last_error;
    $still_exists = (bool)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$waybills_table} WHERE waybill_no = %d", $waybill_no));
    $result['exists_after'] = $still_exists;
}

echo json_encode($result, JSON_PRETTY_PRINT) . "\n";


