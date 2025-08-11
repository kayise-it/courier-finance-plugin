<?php
// Insert test data for waybill assignment testing
require_once('../../../wp-config.php');

global $wpdb;

echo "<h1>📦 Creating Test Data for Assignment Testing</h1>";

// First, let's create some sample waybills in the warehouse
$waybill_data = [
    ['WB-2024-001', 1729, 1250.00, 15.5],
    ['WB-2024-002', 1729, 890.50, 8.2],
    ['WB-2024-003', 2308, 2100.75, 22.1],
    ['WB-2024-004', 2308, 675.25, 5.8],
    ['WB-2024-005', 4366, 1850.00, 18.9],
    ['WB-2024-006', 4366, 950.00, 12.3],
    ['WB-2024-007', 4371, 3200.50, 28.7],
    ['WB-2024-008', 4371, 1450.75, 16.4],
    ['WB-2024-009', 6627, 2750.00, 25.6],
    ['WB-2024-010', 6627, 1100.25, 11.2]
];

echo "<h2>📦 Creating Sample Waybills</h2>";
$waybills_created = 0;

foreach ($waybill_data as $data) {
    // Check if waybill already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}kit_waybills WHERE waybill_no = %s",
        $data[0]
    ));
    
    if ($existing) {
        echo "<p>⚠️ Waybill {$data[0]} already exists, skipping...</p>";
        continue;
    }
    
    // Insert waybill
    $result = $wpdb->insert($wpdb->prefix . 'kit_waybills', [
        'waybill_no' => intval(str_replace('WB-2024-', '', $data[0])), // Convert to integer
        'customer_id' => $data[1],
        'direction_id' => 1, // Required field
        'city_id' => 1, // Required field
        'delivery_id' => 0, // Set to 0 for warehouse waybills (not assigned)
        'product_invoice_amount' => $data[2],
        'total_mass_kg' => $data[3],
        'warehouse' => 1,
        'status' => 'warehoused',
        'created_at' => current_time('mysql'),
        'created_by' => get_current_user_id() ?: 1,
        'last_updated_by' => get_current_user_id() ?: 1 // Required field
    ]);
    
    if ($result !== false) {
        $waybills_created++;
        echo "<p>✅ Created waybill {$data[0]} - R{$data[2]} - {$data[3]}kg</p>";
    } else {
        echo "<p>❌ Failed to create waybill {$data[0]} - Error: " . $wpdb->last_error . "</p>";
    }
}

echo "<h3>📊 Waybills Summary</h3>";
echo "<p><strong>Created:</strong> {$waybills_created} new waybills</p>";

// Create sample deliveries if needed
echo "<h2>🚛 Creating Sample Deliveries</h2>";

$delivery_data = [
    ['TRK-2024-001', 1, 1, '+2 days', 'GP123456'],
    ['TRK-2024-002', 1, 1, '+3 days', 'GP789012'],
    ['TRK-2024-003', 1, 1, '+5 days', 'GP345678']
];

$deliveries_created = 0;

foreach ($delivery_data as $data) {
    // Check if delivery already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}kit_deliveries WHERE delivery_reference = %s",
        $data[0]
    ));
    
    if ($existing) {
        echo "<p>⚠️ Delivery {$data[0]} already exists, skipping...</p>";
        continue;
    }
    
    // Insert delivery
    $result = $wpdb->insert($wpdb->prefix . 'kit_deliveries', [
        'delivery_reference' => $data[0],
        'direction_id' => $data[1],
        'destination_city_id' => $data[2],
        'dispatch_date' => date('Y-m-d', strtotime($data[3])),
        'truck_number' => $data[4],
        'status' => 'scheduled',
        'created_by' => get_current_user_id() ?: 1,
        'created_at' => current_time('mysql')
    ]);
    
    if ($result !== false) {
        $deliveries_created++;
        echo "<p>✅ Created delivery {$data[0]} for {$data[3]} - {$data[4]}</p>";
    } else {
        echo "<p>❌ Failed to create delivery {$data[0]}</p>";
    }
}

echo "<h3>📊 Deliveries Summary</h3>";
echo "<p><strong>Created:</strong> {$deliveries_created} new deliveries</p>";

// Show final status
echo "<h2>🎯 Final Status</h2>";

$total_warehouse_waybills = $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->prefix}kit_waybills 
    WHERE warehouse = 1 AND (delivery_id = 0 OR delivery_id IS NULL)
");

$total_deliveries = $wpdb->get_var("
    SELECT COUNT(*) FROM {$wpdb->prefix}kit_deliveries 
    WHERE status = 'scheduled'
");

echo "<p><strong>Warehouse Waybills Available:</strong> {$total_warehouse_waybills}</p>";
echo "<p><strong>Scheduled Deliveries Available:</strong> {$total_deliveries}</p>";

if ($total_warehouse_waybills > 0 && $total_deliveries > 0) {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>🎉 Ready for Testing!</h3>";
    echo "<p>You now have {$total_warehouse_waybills} waybills and {$total_deliveries} deliveries available for assignment testing.</p>";
    echo "</div>";
    
    echo "<p><a href='test_assignment.php' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>🧪 Run Assignment Test</a></p>";
} else {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>⚠️ Not Ready for Testing</h3>";
    echo "<p>We need both waybills and deliveries to test the assignment feature.</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='http://08600.local/wp-admin/admin.php?page=test-assignment' style='color: blue;'>← Back to Test Assignment Page</a></p>";
echo "<p><a href='http://08600.local/wp-admin/admin.php?page=warehouse-waybills' style='color: blue;'>← View Warehouse</a></p>";
?>
