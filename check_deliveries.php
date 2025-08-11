<?php
// Check existing deliveries
require_once('../../../wp-config.php');

global $wpdb;

echo "<h1>🚛 Checking Existing Deliveries</h1>";

$deliveries = $wpdb->get_results("
    SELECT id, delivery_reference, direction_id, destination_city_id, dispatch_date, truck_number, status, created_at
    FROM {$wpdb->prefix}kit_deliveries 
    ORDER BY id
");

echo "<h2>📋 Available Deliveries:</h2>";

if (empty($deliveries)) {
    echo "<p style='color: red;'>❌ No deliveries found in the database!</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Reference</th><th>Direction ID</th><th>City ID</th><th>Dispatch Date</th><th>Truck</th><th>Status</th><th>Created</th></tr>";
    
    foreach ($deliveries as $delivery) {
        echo "<tr>";
        echo "<td>{$delivery->id}</td>";
        echo "<td>{$delivery->delivery_reference}</td>";
        echo "<td>{$delivery->direction_id}</td>";
        echo "<td>{$delivery->destination_city_id}</td>";
        echo "<td>{$delivery->dispatch_date}</td>";
        echo "<td>{$delivery->truck_number}</td>";
        echo "<td>{$delivery->status}</td>";
        echo "<td>{$delivery->created_at}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<p><a href='fix_waybills.php' style='color: blue;'>← Back to Fix Waybills</a></p>";
?>
