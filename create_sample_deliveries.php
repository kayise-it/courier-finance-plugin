<?php
// Script to create sample deliveries for testing
// Run this from the WordPress admin or via command line

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

// Get table names
$deliveries_table = $wpdb->prefix . 'kit_deliveries';
$directions_table = $wpdb->prefix . 'kit_shipping_directions';
$countries_table = $wpdb->prefix . 'kit_operating_countries';

// Check if we have shipping directions
$directions = $wpdb->get_results("SELECT id FROM $directions_table LIMIT 3");

if (empty($directions)) {
    echo "No shipping directions found. Please create routes first.\n";
    exit;
}

// Sample delivery data
$delivery_data = [
    [
        'delivery_reference' => 'TRK-2024-001',
        'direction_id' => $directions[0]->id,
        'destination_city_id' => 1,
        'dispatch_date' => date('Y-m-d', strtotime('+2 days')),
        'truck_number' => 'GP123456',
        'status' => 'scheduled'
    ],
    [
        'delivery_reference' => 'TRK-2024-002',
        'direction_id' => $directions[0]->id,
        'destination_city_id' => 1,
        'dispatch_date' => date('Y-m-d', strtotime('+3 days')),
        'truck_number' => 'GP789012',
        'status' => 'scheduled'
    ],
    [
        'delivery_reference' => 'TRK-2024-003',
        'direction_id' => $directions[0]->id,
        'destination_city_id' => 1,
        'dispatch_date' => date('Y-m-d', strtotime('+5 days')),
        'truck_number' => 'GP345678',
        'status' => 'scheduled'
    ],
    [
        'delivery_reference' => 'TRK-2024-004',
        'direction_id' => $directions[0]->id,
        'destination_city_id' => 1,
        'dispatch_date' => date('Y-m-d', strtotime('+1 week')),
        'truck_number' => 'GP901234',
        'status' => 'scheduled'
    ],
    [
        'delivery_reference' => 'TRK-2024-005',
        'direction_id' => $directions[0]->id,
        'destination_city_id' => 1,
        'dispatch_date' => date('Y-m-d', strtotime('+10 days')),
        'truck_number' => 'GP567890',
        'status' => 'scheduled'
    ]
];

echo "Creating sample deliveries...\n";

$created_count = 0;

foreach ($delivery_data as $data) {
    // Check if delivery already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $deliveries_table WHERE delivery_reference = %s",
        $data['delivery_reference']
    ));
    
    if ($existing) {
        echo "Delivery {$data['delivery_reference']} already exists, skipping...\n";
        continue;
    }
    
    // Insert delivery
    $result = $wpdb->insert($deliveries_table, [
        'delivery_reference' => $data['delivery_reference'],
        'direction_id' => $data['direction_id'],
        'destination_city_id' => $data['destination_city_id'],
        'dispatch_date' => $data['dispatch_date'],
        'truck_number' => $data['truck_number'],
        'status' => $data['status'],
        'created_by' => get_current_user_id() ?: 1,
        'created_at' => current_time('mysql')
    ]);
    
    if ($result !== false) {
        $created_count++;
        echo "✓ Created delivery {$data['delivery_reference']} for {$data['dispatch_date']}\n";
    } else {
        echo "✗ Failed to create delivery {$data['delivery_reference']}\n";
    }
}

echo "\n🎉 Successfully created $created_count sample deliveries!\n";
echo "You can now test the waybill assignment feature.\n";
?>
