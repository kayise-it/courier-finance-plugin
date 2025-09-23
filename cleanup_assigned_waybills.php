<?php
/**
 * Cleanup script to remove assigned waybills from warehouse tracking table
 * This removes waybills that are already assigned to deliveries from appearing in the assignment interface
 */

// Load WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to run this script.');
}

global $wpdb;

$tracking_table = $wpdb->prefix . 'kit_warehouse_tracking';
$waybills_table = $wpdb->prefix . 'kit_waybills';

echo "<h2>Cleaning up assigned waybills from warehouse tracking</h2>\n";

// First, let's see what assigned waybills exist in tracking
$assigned_waybills = $wpdb->get_results("
    SELECT wt.*, w.waybill_no, w.status, w.delivery_id
    FROM $tracking_table wt
    INNER JOIN $waybills_table w ON wt.waybill_no = w.waybill_no
    WHERE w.status = 'assigned' AND wt.action = 'assigned'
");

echo "<h3>Found " . count($assigned_waybills) . " assigned waybills in tracking table:</h3>\n";
echo "<ul>\n";
foreach ($assigned_waybills as $waybill) {
    echo "<li>Waybill #{$waybill->waybill_no} - Status: {$waybill->status} - Delivery ID: {$waybill->delivery_id}</li>\n";
}
echo "</ul>\n";

// Remove assigned waybills from tracking table
$deleted = $wpdb->query("
    DELETE wt FROM $tracking_table wt 
    INNER JOIN $waybills_table w ON wt.waybill_no = w.waybill_no 
    WHERE w.status = 'assigned' AND wt.action = 'assigned'
");

echo "<h3>Cleanup Results:</h3>\n";
echo "<p>Removed {$deleted} tracking records for assigned waybills.</p>\n";

// Show remaining waybills in tracking
$remaining_waybills = $wpdb->get_results("
    SELECT wt.*, w.waybill_no, w.status
    FROM $tracking_table wt
    LEFT JOIN $waybills_table w ON wt.waybill_no = w.waybill_no
    ORDER BY wt.created_at DESC
");

echo "<h3>Remaining waybills in tracking table (" . count($remaining_waybills) . "):</h3>\n";
echo "<ul>\n";
foreach ($remaining_waybills as $waybill) {
    $status = $waybill->status ?: 'Unknown';
    echo "<li>Waybill #{$waybill->waybill_no} - Status: {$status} - Action: {$waybill->action}</li>\n";
}
echo "</ul>\n";

echo "<p><strong>Cleanup completed!</strong> Assigned waybills have been removed from the warehouse tracking table and will no longer appear in the assignment interface.</p>\n";
?>
