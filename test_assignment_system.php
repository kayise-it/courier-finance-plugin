<?php
// Test the assignment system
require_once('../../../wp-config.php');

global $wpdb;

echo "<h1>🧪 Testing Assignment System</h1>";

// Check warehouse waybills (delivery_id = 1)
$warehouse_waybills = $wpdb->get_results("
    SELECT id, waybill_no, customer_id, delivery_id, status
    FROM {$wpdb->prefix}kit_waybills 
    WHERE warehouse = 1 AND delivery_id = 1
    ORDER BY created_at DESC 
    LIMIT 5
");

echo "<h2>📦 Warehouse Waybills (delivery_id = 1):</h2>";
echo "<p><strong>Count:</strong> " . count($warehouse_waybills) . "</p>";

if (!empty($warehouse_waybills)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Waybill No</th><th>Customer ID</th><th>Delivery ID</th><th>Status</th></tr>";
    
    foreach ($warehouse_waybills as $waybill) {
        echo "<tr>";
        echo "<td>{$waybill->id}</td>";
        echo "<td>{$waybill->waybill_no}</td>";
        echo "<td>{$waybill->customer_id}</td>";
        echo "<td>{$waybill->delivery_id}</td>";
        echo "<td>{$waybill->status}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check available deliveries (excluding warehouse delivery)
$deliveries = $wpdb->get_results("
    SELECT id, delivery_reference, status
    FROM {$wpdb->prefix}kit_deliveries 
    WHERE status = 'scheduled' AND id != 1
    ORDER BY id
");

echo "<h2>🚛 Available Deliveries (excluding warehouse):</h2>";
echo "<p><strong>Count:</strong> " . count($deliveries) . "</p>";

if (!empty($deliveries)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Reference</th><th>Status</th></tr>";
    
    foreach ($deliveries as $delivery) {
        echo "<tr>";
        echo "<td>{$delivery->id}</td>";
        echo "<td>{$delivery->delivery_reference}</td>";
        echo "<td>{$delivery->status}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test assignment
if (isset($_GET['test_assign']) && !empty($warehouse_waybills) && !empty($deliveries)) {
    echo "<h2>🧪 Testing Assignment</h2>";
    
    $waybill_id = $warehouse_waybills[0]->id;
    $delivery_id = $deliveries[0]->id;
    
    // Perform assignment
    $result = $wpdb->update(
        $wpdb->prefix . 'kit_waybills',
        [
            'delivery_id' => $delivery_id,
            'status' => 'assigned',
            'last_updated_at' => current_time('mysql'),
            'last_updated_by' => get_current_user_id()
        ],
        ['id' => $waybill_id]
    );
    
    if ($result !== false) {
        echo "<p style='color: green;'>✅ Successfully assigned waybill #{$warehouse_waybills[0]->waybill_no} to delivery #{$delivery_id}</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to assign waybill</p>";
    }
} else {
    echo "<h2>🧪 Ready to Test</h2>";
    if (!empty($warehouse_waybills) && !empty($deliveries)) {
        echo "<p><a href='?test_assign=1' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>🧪 Test Assignment</a></p>";
    } else {
        echo "<p style='color: red;'>❌ Cannot test: Need both warehouse waybills and deliveries</p>";
    }
}

echo "<hr>";
echo "<p><a href='http://08600.local/wp-admin/admin.php?page=assign-waybills' style='color: blue;'>← Go to Assign Waybills Page</a></p>";
echo "<p><a href='http://08600.local/wp-admin/admin.php?page=warehouse-waybills' style='color: blue;'>← Go to Warehouse Page</a></p>";
?>
