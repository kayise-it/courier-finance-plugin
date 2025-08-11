<?php
// Check existing waybills
require_once('../../../wp-config.php');

global $wpdb;

echo "<h1>📦 Checking Existing Waybills</h1>";

// Check all waybills
$all_waybills = $wpdb->get_results("
    SELECT id, waybill_no, customer_id, delivery_id, warehouse, status, created_at
    FROM {$wpdb->prefix}kit_waybills 
    ORDER BY created_at DESC 
    LIMIT 15
");

echo "<h2>📋 All Waybills (Recent 15):</h2>";

if (empty($all_waybills)) {
    echo "<p style='color: red;'>❌ No waybills found in the database!</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Waybill No</th><th>Customer ID</th><th>Delivery ID</th><th>Warehouse</th><th>Status</th><th>Created</th></tr>";
    
    foreach ($all_waybills as $waybill) {
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

// Check warehouse waybills specifically
echo "<h2>🏭 Warehouse Waybills (warehouse = 1):</h2>";

$warehouse_waybills = $wpdb->get_results("
    SELECT id, waybill_no, customer_id, delivery_id, warehouse, status, created_at
    FROM {$wpdb->prefix}kit_waybills 
    WHERE warehouse = 1
    ORDER BY created_at DESC 
    LIMIT 10
");

if (empty($warehouse_waybills)) {
    echo "<p style='color: red;'>❌ No warehouse waybills found!</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Waybill No</th><th>Customer ID</th><th>Delivery ID</th><th>Warehouse</th><th>Status</th><th>Created</th></tr>";
    
    foreach ($warehouse_waybills as $waybill) {
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

// Check unassigned warehouse waybills
echo "<h2>📦 Unassigned Warehouse Waybills (for assignment):</h2>";

$unassigned_waybills = $wpdb->get_results("
    SELECT id, waybill_no, customer_id, delivery_id, warehouse, status, created_at
    FROM {$wpdb->prefix}kit_waybills 
    WHERE warehouse = 1 AND (delivery_id = 0 OR delivery_id IS NULL)
    ORDER BY created_at DESC 
    LIMIT 10
");

if (empty($unassigned_waybills)) {
    echo "<p style='color: red;'>❌ No unassigned warehouse waybills found!</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Waybill No</th><th>Customer ID</th><th>Delivery ID</th><th>Warehouse</th><th>Status</th><th>Created</th></tr>";
    
    foreach ($unassigned_waybills as $waybill) {
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

echo "<hr>";
echo "<p><a href='test_assignment.php' style='color: blue;'>← Back to Test Assignment</a></p>";
echo "<p><a href='insert_test_data.php' style='color: blue;'>← Back to Insert Test Data</a></p>";
?>
