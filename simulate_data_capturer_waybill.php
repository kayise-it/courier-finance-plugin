<?php
/**
 * Simulate Data Capturer Waybill Creation
 * This script simulates creating a waybill as a Data Capturer with proper data
 */

// Load WordPress
require_once('../../../wp-load.php');

// Load the plugin
require_once('08600-services-quotations.php');

// Check if we're running from command line or web
if (php_sapi_name() !== 'cli') {
    echo "<h1>Data Capturer Waybill Simulation</h1>";
    echo "<p>This script simulates creating a waybill as a Data Capturer.</p>";
}

// Pick an administrator account if available and log in
if (function_exists('wp_set_current_user')) {
    $user_id = 1;
    $admin_users = function_exists('get_users') ? get_users(['role' => 'administrator', 'number' => 1]) : [];
    if (!empty($admin_users)) {
        $user_id = $admin_users[0]->ID;
    }
    wp_set_current_user($user_id);
}

// Grant temporary caps for the simulator run
if (function_exists('add_filter')) {
    add_filter('user_has_cap', function ($allcaps) {
        $allcaps['manage_options'] = true;
        $allcaps['edit_pages'] = true;
        $allcaps['kit_update_data'] = true;
        return $allcaps;
    }, 5);
}

// Simulate Data Capturer POST data
$_POST = [
    '_wpnonce' => wp_create_nonce('add_waybill_nonce'),
    '_wp_http_referer' => '/wp-admin/admin.php?page=08600-waybill-create',
    'waybill_no' => '4020',
    'waybill_description' => 'Test waybill from Data Capturer simulation',
    'include_sadc' => '1',
    'cust_id' => '3693',  // Use cust_id as the primary field
    'customer_search' => 'Test Customer',
    'customer_select' => '3693',  // Also set customer_select
    'client_type' => 'business',
    'company_name' => 'Test Company Ltd',
    'customer_name' => 'John',
    'customer_surname' => 'Doe',
    'telephone' => '+27 11 123 4567',
    'cell' => '+27 82 123 4567',
    'email_address' => 'test@example.com',
    'address' => '123 Test Street, Johannesburg',
    'origin_country' => '2',
    'origin_city' => '17',
    'country_id' => '2',   // Expected by validation
    'city_id' => '17',     // Expected by validation
    'destination_country' => '1',
    'destination_city' => '2',
    'delivery_id' => '1',
    'origin_country_id' => '2',
    'direction_id' => '5',
    'current_rate' => '30.00',
    'base_rate' => '30.00',
    'total_mass_kg' => '200',
    'item_length' => '55',
    'item_width' => '25',
    'item_height' => '18',
    'total_volume' => '0.60',  // Add volume calculation
    'charge_basis' => 'mass',
    'vat_include' => '0',
    'sadc_certificate' => '0',
    'sad500' => '1'
];

// Simulate warehoused flag (checked)
$_POST['warehoused'] = '1';

// Use NON-AJAX path inside process_form (nonce in _wpnonce will be checked)
// define('DOING_AJAX', true);

// Include the waybill functions
require_once('includes/waybill/waybill-functions.php');

echo "<h2>Simulating Data Capturer Waybill Creation</h2>";
echo "<h3>POST Data:</h3>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

// Clear any previous errors
error_log("=== STARTING DATA CAPTURER SIMULATION ===");

// Call the waybill creation function
try {
    $result = KIT_Waybills::process_form();
    
    echo "<h3>Result:</h3>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    if (is_wp_error($result)) {
        echo "<h3 style='color: red;'>Error:</h3>";
        echo "<p>" . $result->get_error_message() . "</p>";
    } else {
        echo "<h3 style='color: green;'>Success!</h3>";
        echo "<p>Waybill created successfully.</p>";
    }
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Exception:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<h3>Recent Log Entries:</h3>";
echo "<pre>";
$log_entries = shell_exec("tail -20 /Applications/MAMP/logs/php_error.log | grep -E 'DEBUG|ERROR|SUCCESS|waybill.*4020'");
echo $log_entries;
echo "</pre>";

echo "<h3>Check Database for Waybill 4020:</h3>";
global $wpdb;
$waybill = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}kit_waybills WHERE waybill_no = %d",
    4020
));

if ($waybill) {
    echo "<p style='color: green;'>✅ Waybill 4020 found in database!</p>";
    echo "<pre>" . print_r($waybill, true) . "</pre>";
    
    // If warehoused flag is set, insert into warehouse tracking
    if (!empty($_POST['warehoused'])) {
        $table = $wpdb->prefix . 'kit_warehouse_tracking';
        $created_by = function_exists('get_current_user_id') ? get_current_user_id() : 0;
        $insert_data = [
            'waybill_no' => intval($waybill->waybill_no),
            'waybill_id' => intval($waybill->id ?? 0),
            'customer_id' => intval($waybill->customer_id ?? 0),
            'action' => 'warehoused',
            'previous_status' => null,
            'new_status' => 'warehoused',
            'assigned_delivery_id' => null,
            'notes' => null,
            'created_by' => $created_by,
            'created_at' => current_time('mysql')
        ];
        $wpdb->insert($table, $insert_data);
        echo "<h3>Warehouse Tracking Insert</h3>";
        if ($wpdb->insert_id) {
            echo "<p style='color: green;'>✅ Inserted tracking row (ID: {$wpdb->insert_id})</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to insert tracking row. Error: " . esc_html($wpdb->last_error) . "</p>";
        }
        // Fetch and display the tracking row(s) for this waybill
        $tracking_rows = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE waybill_no = %d ORDER BY id DESC LIMIT 3",
            4020
        ));
        echo "<pre>" . print_r($tracking_rows, true) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>❌ Waybill 4020 NOT found in database!</p>";
}

echo "<h3>All Waybills with number 4020:</h3>";
$all_waybills = $wpdb->get_results($wpdb->prepare(
    "SELECT waybill_no, customer_id, product_invoice_amount, mass_charge, volume_charge, created_at FROM {$wpdb->prefix}kit_waybills WHERE waybill_no = %d",
    4020
));

if ($all_waybills) {
    echo "<pre>" . print_r($all_waybills, true) . "</pre>";
} else {
    echo "<p>No waybills found with number 4020</p>";
}
?>
