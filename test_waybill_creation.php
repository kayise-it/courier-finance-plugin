<?php
/**
 * Test Waybill Creation Logic
 */

// Load WordPress
require_once('../../../wp-load.php');

// Load the plugin
require_once('08600-services-quotations.php');

echo "<h1>Testing Waybill Creation Logic</h1>";

// Test customer ID extraction logic
$test_data = [
    'cust_id' => '3693',
    'customer_select' => '3693', 
    'customer_id' => '0'
];

echo "<h2>Testing Customer ID Extraction</h2>";
echo "<pre>Test data: " . print_r($test_data, true) . "</pre>";

// Simulate the customer ID extraction logic
$customer_id = 0;
if (isset($test_data['cust_id']) && intval($test_data['cust_id']) > 0) {
    $customer_id = intval($test_data['cust_id']);
    echo "<p>✅ Found customer_id from cust_id: $customer_id</p>";
} elseif (isset($test_data['customer_select']) && intval($test_data['customer_select']) > 0) {
    $customer_id = intval($test_data['customer_select']);
    echo "<p>✅ Found customer_id from customer_select: $customer_id</p>";
} elseif (isset($test_data['customer_id']) && intval($test_data['customer_id']) > 0) {
    $customer_id = intval($test_data['customer_id']);
    echo "<p>✅ Found customer_id from customer_id: $customer_id</p>";
} else {
    echo "<p>❌ No valid customer_id found</p>";
}

// Test charge calculation logic
echo "<h2>Testing Charge Calculation</h2>";

$posted_total_mass = 200;
$posted_total_volume = 0.60;
$current_rate = 30.00;
$base_rate = 30.00;

echo "<p>Mass: $posted_total_mass kg</p>";
echo "<p>Volume: $posted_total_volume m³</p>";
echo "<p>Current Rate: R$current_rate</p>";
echo "<p>Base Rate: R$base_rate</p>";

// Test mass rate fallback logic
$snapshot_mass_rate = 0.0;
if (isset($test_data['mass_rate']) && floatval($test_data['mass_rate']) > 0) {
    $snapshot_mass_rate = floatval($test_data['mass_rate']);
    echo "<p>Using mass_rate: $snapshot_mass_rate</p>";
} elseif ($current_rate > 0) {
    $snapshot_mass_rate = $current_rate;
    echo "<p>Using current_rate: $snapshot_mass_rate</p>";
} elseif ($base_rate > 0) {
    $snapshot_mass_rate = $base_rate;
    echo "<p>Using base_rate: $snapshot_mass_rate</p>";
}

// Calculate charges
$mass_charge = 0.0;
if ($posted_total_mass > 0 && $snapshot_mass_rate > 0) {
    $mass_charge = $posted_total_mass * $snapshot_mass_rate;
    echo "<p>✅ Mass Charge: $posted_total_mass × $snapshot_mass_rate = R$mass_charge</p>";
} else {
    echo "<p>❌ Mass Charge: 0 (no mass or rate)</p>";
}

$volume_charge = 0.0;
if ($posted_total_volume > 0) {
    if ($snapshot_mass_rate > 0) {
        $volume_charge = $posted_total_volume * $snapshot_mass_rate;
        echo "<p>✅ Volume Charge: $posted_total_volume × $snapshot_mass_rate = R$volume_charge</p>";
    } else {
        echo "<p>❌ Volume Charge: 0 (no rate available)</p>";
    }
} else {
    echo "<p>❌ Volume Charge: 0 (no volume)</p>";
}

echo "<h2>Expected Results</h2>";
echo "<p>Mass Charge: R" . number_format($mass_charge, 2) . "</p>";
echo "<p>Volume Charge: R" . number_format($volume_charge, 2) . "</p>";
echo "<p>Total: R" . number_format($mass_charge + $volume_charge, 2) . "</p>";

// Check if waybill 4020 exists
echo "<h2>Database Check</h2>";
global $wpdb;
$waybill = $wpdb->get_row($wpdb->prepare(
    "SELECT waybill_no, customer_id, product_invoice_amount, mass_charge, volume_charge, created_at FROM {$wpdb->prefix}kit_waybills WHERE waybill_no = %d",
    4020
));

if ($waybill) {
    echo "<p style='color: green;'>✅ Waybill 4020 found in database!</p>";
    echo "<pre>" . print_r($waybill, true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ Waybill 4020 NOT found in database!</p>";
}

// Show recent waybills
echo "<h2>Recent Waybills</h2>";
$recent_waybills = $wpdb->get_results(
    "SELECT waybill_no, customer_id, product_invoice_amount, mass_charge, volume_charge, created_at FROM {$wpdb->prefix}kit_waybills ORDER BY created_at DESC LIMIT 5"
);

if ($recent_waybills) {
    echo "<pre>" . print_r($recent_waybills, true) . "</pre>";
} else {
    echo "<p>No waybills found</p>";
}
?>
