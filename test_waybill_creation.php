<?php
/**
 * Test script to debug waybill creation issues
 * Place this in the plugin root directory and access via browser
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is logged in and has permissions
if (!current_user_can('manage_options')) {
    wp_die('Unauthorized access');
}

echo "<h1>Waybill Creation Debug Test</h1>";

// Test 1: Check if required tables exist
global $wpdb;
$tables_to_check = [
    $wpdb->prefix . 'kit_waybills',
    $wpdb->prefix . 'kit_waybill_items',
    $wpdb->prefix . 'kit_customers',
    $wpdb->prefix . 'kit_deliveries',
    $wpdb->prefix . 'kit_shipping_directions',
    $wpdb->prefix . 'kit_operating_cities'
];

echo "<h2>Database Tables Check</h2>";
foreach ($tables_to_check as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
    echo "<p><strong>$table:</strong> " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "</p>";
}

// Test 2: Check if required data exists
echo "<h2>Required Data Check</h2>";

// Check customers
$customer_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_customers");
echo "<p><strong>Customers:</strong> $customer_count records</p>";

// Check deliveries
$delivery_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_deliveries");
echo "<p><strong>Deliveries:</strong> $delivery_count records</p>";

// Check shipping directions
$direction_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_shipping_directions");
echo "<p><strong>Shipping Directions:</strong> $direction_count records</p>";

// Check operating cities
$city_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_operating_cities");
echo "<p><strong>Operating Cities:</strong> $city_count records</p>";

// Test 3: Check if default records exist (ID = 1)
echo "<h2>Default Records Check (ID = 1)</h2>";

$default_customer = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}kit_customers WHERE cust_id = 1");
echo "<p><strong>Default Customer (ID=1):</strong> " . ($default_customer ? "✅ EXISTS" : "❌ MISSING") . "</p>";

$default_delivery = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}kit_deliveries WHERE id = 1");
echo "<p><strong>Default Delivery (ID=1):</strong> " . ($default_delivery ? "✅ EXISTS" : "❌ MISSING") . "</p>";

$default_direction = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}kit_shipping_directions WHERE id = 1");
echo "<p><strong>Default Direction (ID=1):</strong> " . ($default_direction ? "✅ EXISTS" : "❌ MISSING") . "</p>";

$default_city = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}kit_operating_cities WHERE id = 1");
echo "<p><strong>Default City (ID=1):</strong> " . ($default_city ? "✅ EXISTS" : "❌ MISSING") . "</p>";

// Test 4: Test waybill number generation
echo "<h2>Waybill Number Generation Test</h2>";
if (class_exists('KIT_Waybills')) {
    $waybill_no = KIT_Waybills::generate_waybill_number();
    echo "<p><strong>Generated Waybill Number:</strong> $waybill_no</p>";
} else {
    echo "<p><strong>KIT_Waybills class:</strong> ❌ NOT FOUND</p>";
}

// Test 5: Test customer creation
echo "<h2>Customer Creation Test</h2>";
if (class_exists('KIT_Customers')) {
    $test_customer_data = [
        'customer_select' => 'new',
        'customer_name' => 'Test',
        'customer_surname' => 'Customer',
        'cell' => '123456789',
        'address' => 'Test Address',
        'company_name' => 'Test Company',
        'email_address' => 'test@example.com',
        'country_id' => 1,
        'city_id' => 1,
    ];
    
    $customer_id = KIT_Customers::save_customer($test_customer_data);
    if ($customer_id && !is_wp_error($customer_id)) {
        echo "<p><strong>Test Customer Creation:</strong> ✅ SUCCESS (ID: $customer_id)</p>";
    } else {
        echo "<p><strong>Test Customer Creation:</strong> ❌ FAILED</p>";
        if (is_wp_error($customer_id)) {
            echo "<p>Error: " . $customer_id->get_error_message() . "</p>";
        }
    }
} else {
    echo "<p><strong>KIT_Customers class:</strong> ❌ NOT FOUND</p>";
}

echo "<hr>";
echo "<p><em>Debug test completed. Check the results above to identify issues.</em></p>";
?>
