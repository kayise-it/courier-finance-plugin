<?php
// Simple script to check warehouse waybills
// Run this from the plugin directory

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "Checking warehouse waybills...\n\n";

// Check total waybills
$total_waybills = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_waybills");
echo "Total waybills in system: $total_waybills\n";

// Check warehouse waybills
$warehouse_waybills = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_waybills WHERE warehouse = 1");
echo "Waybills in warehouse: $warehouse_waybills\n";

// Check customers
$total_customers = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_customers");
echo "Total customers: $total_customers\n";

// Check deliveries
$total_deliveries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_deliveries");
echo "Total deliveries: $total_deliveries\n";

// Show some sample waybills
echo "\nSample waybills:\n";
$sample_waybills = $wpdb->get_results("SELECT id, waybill_no, warehouse, status FROM {$wpdb->prefix}kit_waybills LIMIT 5");
foreach ($sample_waybills as $waybill) {
    echo "- ID: {$waybill->id}, No: {$waybill->waybill_no}, Warehouse: {$waybill->warehouse}, Status: {$waybill->status}\n";
}

echo "\nDone!\n";
?>
