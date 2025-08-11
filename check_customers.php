<?php
// Check existing customers
require_once('../../../wp-config.php');

global $wpdb;

echo "<h1>👥 Checking Existing Customers</h1>";

$customers = $wpdb->get_results("
    SELECT cust_id, name, surname, company_name 
    FROM {$wpdb->prefix}kit_customers 
    ORDER BY cust_id 
    LIMIT 10
");

echo "<h2>📋 Available Customers:</h2>";

if (empty($customers)) {
    echo "<p style='color: red;'>❌ No customers found in the database!</p>";
    echo "<p>You need to create customers first before creating waybills.</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Customer ID</th><th>Name</th><th>Surname</th><th>Company</th></tr>";
    
    foreach ($customers as $customer) {
        echo "<tr>";
        echo "<td>{$customer->cust_id}</td>";
        echo "<td>{$customer->name}</td>";
        echo "<td>{$customer->surname}</td>";
        echo "<td>{$customer->company_name}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<hr>";
echo "<p><a href='insert_test_data.php' style='color: blue;'>← Back to Insert Test Data</a></p>";
?>
