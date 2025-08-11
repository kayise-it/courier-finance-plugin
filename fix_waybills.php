<?php
// Fix waybills to make them available for assignment
require_once('../../../wp-config.php');

global $wpdb;

echo "<h1>🔧 Fixing Waybills for Assignment Testing</h1>";

// Update waybills to be unassigned (delivery_id = 0)
$result = $wpdb->query("
    UPDATE {$wpdb->prefix}kit_waybills 
    SET delivery_id = 0 
    WHERE warehouse = 1 AND delivery_id = 1 AND waybill_no IN (1, 2, 3, 4, 5, 6, 7, 8, 9, 10)
");

if ($result !== false) {
    echo "<p>✅ Successfully updated {$result} waybills to be unassigned (delivery_id = 0)</p>";
} else {
    echo "<p>❌ Failed to update waybills: " . $wpdb->last_error . "</p>";
}

// Check the updated waybills
echo "<h2>📋 Updated Waybills:</h2>";

$updated_waybills = $wpdb->get_results("
    SELECT id, waybill_no, customer_id, delivery_id, warehouse, status, created_at
    FROM {$wpdb->prefix}kit_waybills 
    WHERE warehouse = 1 AND delivery_id = 0 AND waybill_no IN (1, 2, 3, 4, 5, 6, 7, 8, 9, 10)
    ORDER BY waybill_no
");

if (empty($updated_waybills)) {
    echo "<p style='color: red;'>❌ No waybills found after update!</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Waybill No</th><th>Customer ID</th><th>Delivery ID</th><th>Warehouse</th><th>Status</th><th>Created</th></tr>";
    
    foreach ($updated_waybills as $waybill) {
        echo "<tr>";
        echo "<td>{$waybill->id}</td>";
        echo "<td>{$waybill->waybill_no}</td>";
        echo "<td>{$waybill->customer_id}</td>";
        echo "<td>{$waybill->delivery_id}</td>";
        echo "<td>{$waybill->warehouse}</td>";
        echo "<td>{$waybill->status}</td>";
        echo "<td>{$waybill->created_at}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check total unassigned warehouse waybills
$total_unassigned = $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->prefix}kit_waybills 
    WHERE warehouse = 1 AND (delivery_id = 0 OR delivery_id IS NULL)
");

echo "<h2>📊 Summary</h2>";
echo "<p><strong>Total Unassigned Warehouse Waybills:</strong> {$total_unassigned}</p>";

if ($total_unassigned > 0) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Ready for Testing!</h3>";
    echo "<p>You now have {$total_unassigned} waybills available for assignment testing.</p>";
    echo "</div>";
    
    echo "<p><a href='test_assignment.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>🧪 Run Assignment Test</a></p>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ Not Ready for Testing</h3>";
    echo "<p>No unassigned warehouse waybills available.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='test_assignment.php' style='color: blue;'>← Back to Test Assignment</a></p>";
echo "<p><a href='check_waybills.php' style='color: blue;'>← Check Waybills</a></p>";
?>
