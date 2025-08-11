<?php
// Simple test script to assign waybills
// Run this from the WordPress admin or via browser

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

echo "<h1>🧪 Testing Waybill Assignment</h1>";

// Get warehouse waybills (assigned to warehouse delivery)
$warehouse_waybills = $wpdb->get_results("
    SELECT id, waybill_no, customer_id, product_invoice_amount, status, delivery_id
    FROM {$wpdb->prefix}kit_waybills 
    WHERE warehouse = 1 AND delivery_id = 1
    ORDER BY created_at DESC 
    LIMIT 5
");

// Get available deliveries (excluding the warehouse delivery)
$deliveries = $wpdb->get_results("
    SELECT id, delivery_reference, dispatch_date, status
    FROM {$wpdb->prefix}kit_deliveries 
    WHERE status = 'scheduled' AND id != 1
    ORDER BY dispatch_date ASC 
    LIMIT 3
");

echo "<h2>📊 Current Status</h2>";
echo "<p><strong>Warehouse Waybills Available:</strong> " . count($warehouse_waybills) . "</p>";
echo "<p><strong>Deliveries Available:</strong> " . count($deliveries) . "</p>";

if (empty($warehouse_waybills)) {
    echo "<p style='color: red;'>❌ No warehouse waybills available for testing!</p>";
    echo "<p>Please create sample waybills first.</p>";
    exit;
}

if (empty($deliveries)) {
    echo "<p style='color: red;'>❌ No deliveries available for testing!</p>";
    echo "<p>Please create sample deliveries first.</p>";
    exit;
}

// Show available waybills
echo "<h3>📦 Available Waybills:</h3>";
echo "<ul>";
foreach ($warehouse_waybills as $waybill) {
    echo "<li>#{$waybill->waybill_no} - R{$waybill->product_invoice_amount} - Status: {$waybill->status}</li>";
}
echo "</ul>";

// Show available deliveries
echo "<h3>🚛 Available Deliveries:</h3>";
echo "<ul>";
foreach ($deliveries as $delivery) {
    echo "<li>{$delivery->delivery_reference} - {$delivery->dispatch_date} - Status: {$delivery->status}</li>";
}
echo "</ul>";

// Perform test assignment
if (isset($_GET['test']) && $_GET['test'] === 'assign') {
    echo "<h2>🧪 Running Test Assignment</h2>";
    
    $delivery_id = $deliveries[0]->id; // Use first delivery
    $assigned_count = 0;
    $results = [];
    
    foreach ($warehouse_waybills as $waybill) {
        // Get waybill details before update
        $waybill_before = $wpdb->get_row($wpdb->prepare(
            "SELECT waybill_no, delivery_id, status FROM {$wpdb->prefix}kit_waybills WHERE id = %d",
            $waybill->id
        ));
        
        // Update waybill
        $result = $wpdb->update(
            $wpdb->prefix . 'kit_waybills',
            [
                'delivery_id' => $delivery_id,
                'status' => 'assigned',
                'last_updated_at' => current_time('mysql'),
                'last_updated_by' => get_current_user_id()
            ],
            ['id' => $waybill->id]
        );
        
        if ($result !== false) {
            $assigned_count++;
            $results[] = "✅ Waybill #{$waybill_before->waybill_no} assigned successfully to delivery #{$delivery_id}";
        } else {
            $results[] = "❌ Failed to assign waybill #{$waybill_before->waybill_no}";
        }
    }
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>📋 Test Results:</h3>";
    echo "<ul>";
    foreach ($results as $result) {
        echo "<li>{$result}</li>";
    }
    echo "</ul>";
    echo "<p><strong>Summary:</strong> Successfully assigned {$assigned_count} out of " . count($warehouse_waybills) . " waybills.</p>";
    echo "</div>";
    
    // Verify the assignment
    echo "<h3>🔍 Verification:</h3>";
    $assigned_waybills = $wpdb->get_results($wpdb->prepare("
        SELECT waybill_no, delivery_id, status 
        FROM {$wpdb->prefix}kit_waybills 
        WHERE delivery_id = %d AND status = 'assigned'
        ORDER BY waybill_no
    ", $delivery_id));
    
    if (!empty($assigned_waybills)) {
        echo "<p style='color: green;'>✅ Verification successful! Found " . count($assigned_waybills) . " assigned waybills:</p>";
        echo "<ul>";
        foreach ($assigned_waybills as $waybill) {
            echo "<li>#{$waybill->waybill_no} - Delivery ID: {$waybill->delivery_id} - Status: {$waybill->status}</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ Verification failed! No assigned waybills found.</p>";
    }
    
} else {
    echo "<h2>🧪 Ready to Test</h2>";
    echo "<p>Click the button below to run the test assignment:</p>";
    echo "<a href='?test=assign' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>🧪 Run Test Assignment</a>";
}

echo "<hr>";
echo "<p><a href='http://08600.local/wp-admin/admin.php?page=test-assignment' style='color: blue;'>← Back to Test Assignment Page</a></p>";
echo "<p><a href='http://08600.local/wp-admin/admin.php?page=warehouse-waybills' style='color: blue;'>← View Warehouse</a></p>";
?>
