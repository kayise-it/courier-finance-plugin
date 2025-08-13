<?php
// Simple script to create warehouse waybills for testing
// Run this from the plugin directory

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

// Get existing customers
$customers = $wpdb->get_results("SELECT cust_id FROM {$wpdb->prefix}kit_customers LIMIT 5");

if (empty($customers)) {
    echo "No customers found. Please create customers first.\n";
    exit;
}

// Create 10 sample waybills in warehouse
for ($i = 1; $i <= 10; $i++) {
    $customer = $customers[array_rand($customers)];
    
    $waybill_data = [
        'waybill_no' => 'WB-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
        'customer_id' => $customer->cust_id,
        'delivery_id' => 1, // Warehouse delivery
        'product_invoice_amount' => rand(500, 5000),
        'total_mass_kg' => rand(10, 100),
        'warehouse' => 1, // This puts it in warehouse
        'status' => 'warehoused',
        'created_at' => current_time('mysql'),
        'created_by' => 1,
        'last_updated_at' => current_time('mysql'),
        'last_updated_by' => 1
    ];
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'kit_waybills',
        $waybill_data
    );
    
    if ($result) {
        echo "Created waybill: {$waybill_data['waybill_no']}\n";
    } else {
        echo "Failed to create waybill: {$waybill_data['waybill_no']} - " . $wpdb->last_error . "\n";
    }
}

echo "\nWarehouse waybills creation completed!\n";
echo "Check your warehouse page to see the new waybills.\n";
?>
