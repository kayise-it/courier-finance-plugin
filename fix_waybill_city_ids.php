<?php
/**
 * Fix waybill city_id values by updating them from customer city_id
 * Run this via WordPress admin or CLI
 */

// Load WordPress
if (!defined('ABSPATH')) {
    require_once(__DIR__ . '/../../../wp-load.php');
}

global $wpdb;

$waybills_table = $wpdb->prefix . 'kit_waybills';
$customers_table = $wpdb->prefix . 'kit_customers';

// Update waybills where city_id is NULL but customer has a city_id
$query = "
    UPDATE {$waybills_table} w
    INNER JOIN {$customers_table} c ON w.customer_id = c.cust_id
    SET w.city_id = c.city_id
    WHERE w.city_id IS NULL 
    AND c.city_id IS NOT NULL
";

$updated = $wpdb->query($query);

echo "Updated {$updated} waybills with city_id from their customers.\n";

// Show summary
$null_count = $wpdb->get_var("SELECT COUNT(*) FROM {$waybills_table} WHERE city_id IS NULL");
$total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$waybills_table}");

echo "\nSummary:\n";
echo "  Total waybills: {$total_count}\n";
echo "  Waybills with NULL city_id: {$null_count}\n";
echo "  Waybills with city_id: " . ($total_count - $null_count) . "\n";


