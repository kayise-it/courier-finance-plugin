<?php
// Script to create 10 waybills in warehouse
// Run this from the WordPress admin or via command line

// Include WordPress
require_once('../../../wp-config.php');

global $wpdb;

// Get table names
$waybills_table = $wpdb->prefix . 'kit_waybills';
$customers_table = $wpdb->prefix . 'kit_customers';
$deliveries_table = $wpdb->prefix . 'kit_deliveries';

// Sample customer data (using existing customers or create new ones)
$customers = $wpdb->get_results("SELECT cust_id, name, surname, company_name FROM $customers_table LIMIT 5");

if (empty($customers)) {
    echo "No customers found. Please create customers first.\n";
    exit;
}

// Sample delivery data
$deliveries = $wpdb->get_results("SELECT id FROM $deliveries_table LIMIT 3");

if (empty($deliveries)) {
    echo "No deliveries found. Please create deliveries first.\n";
    exit;
}

// Sample waybill data
$waybill_data = [
    [
        'waybill_no' => 1001,
        'product_invoice_number' => 'INV-2024-001',
        'product_invoice_amount' => 1250.00,
        'item_length' => 50.0,
        'item_width' => 30.0,
        'item_height' => 20.0,
        'total_mass_kg' => 15.5,
        'total_volume' => 0.03,
        'mass_charge' => 155.00,
        'volume_charge' => 120.00,
        'status' => 'warehoused',
        'warehouse' => 1
    ],
    [
        'waybill_no' => 1002,
        'product_invoice_number' => 'INV-2024-002',
        'product_invoice_amount' => 890.50,
        'item_length' => 40.0,
        'item_width' => 25.0,
        'item_height' => 15.0,
        'total_mass_kg' => 8.2,
        'total_volume' => 0.015,
        'mass_charge' => 82.00,
        'volume_charge' => 75.00,
        'status' => 'pending',
        'warehouse' => 1
    ],
    [
        'waybill_no' => 1003,
        'product_invoice_number' => 'INV-2024-003',
        'product_invoice_amount' => 2100.75,
        'item_length' => 60.0,
        'item_width' => 40.0,
        'item_height' => 30.0,
        'total_mass_kg' => 25.8,
        'total_volume' => 0.072,
        'mass_charge' => 258.00,
        'volume_charge' => 180.00,
        'status' => 'warehoused',
        'warehouse' => 1
    ],
    [
        'waybill_no' => 1004,
        'product_invoice_number' => 'INV-2024-004',
        'product_invoice_amount' => 675.25,
        'item_length' => 35.0,
        'item_width' => 20.0,
        'item_height' => 12.0,
        'total_mass_kg' => 6.5,
        'total_volume' => 0.0084,
        'mass_charge' => 65.00,
        'volume_charge' => 42.00,
        'status' => 'created',
        'warehouse' => 1
    ],
    [
        'waybill_no' => 1005,
        'product_invoice_number' => 'INV-2024-005',
        'product_invoice_amount' => 1850.00,
        'item_length' => 55.0,
        'item_width' => 35.0,
        'item_height' => 25.0,
        'total_mass_kg' => 18.3,
        'total_volume' => 0.048,
        'mass_charge' => 183.00,
        'volume_charge' => 120.00,
        'status' => 'warehoused',
        'warehouse' => 1
    ],
    [
        'waybill_no' => 1006,
        'product_invoice_number' => 'INV-2024-006',
        'product_invoice_amount' => 950.80,
        'item_length' => 45.0,
        'item_width' => 28.0,
        'item_height' => 18.0,
        'total_mass_kg' => 10.1,
        'total_volume' => 0.023,
        'mass_charge' => 101.00,
        'volume_charge' => 58.00,
        'status' => 'pending',
        'warehouse' => 1
    ],
    [
        'waybill_no' => 1007,
        'product_invoice_number' => 'INV-2024-007',
        'product_invoice_amount' => 3200.50,
        'item_length' => 70.0,
        'item_width' => 45.0,
        'item_height' => 35.0,
        'total_mass_kg' => 32.7,
        'total_volume' => 0.110,
        'mass_charge' => 327.00,
        'volume_charge' => 275.00,
        'status' => 'warehoused',
        'warehouse' => 1
    ],
    [
        'waybill_no' => 1008,
        'product_invoice_number' => 'INV-2024-008',
        'product_invoice_amount' => 750.00,
        'item_length' => 38.0,
        'item_width' => 22.0,
        'item_height' => 14.0,
        'total_mass_kg' => 7.8,
        'total_volume' => 0.012,
        'mass_charge' => 78.00,
        'volume_charge' => 30.00,
        'status' => 'created',
        'warehouse' => 1
    ],
    [
        'waybill_no' => 1009,
        'product_invoice_number' => 'INV-2024-009',
        'product_invoice_amount' => 1450.25,
        'item_length' => 52.0,
        'item_width' => 32.0,
        'item_height' => 22.0,
        'total_mass_kg' => 14.2,
        'total_volume' => 0.037,
        'mass_charge' => 142.00,
        'volume_charge' => 93.00,
        'status' => 'warehoused',
        'warehouse' => 1
    ],
    [
        'waybill_no' => 1010,
        'product_invoice_number' => 'INV-2024-010',
        'product_invoice_amount' => 1100.75,
        'item_length' => 48.0,
        'item_width' => 30.0,
        'item_height' => 19.0,
        'total_mass_kg' => 11.5,
        'total_volume' => 0.027,
        'mass_charge' => 115.00,
        'volume_charge' => 68.00,
        'status' => 'pending',
        'warehouse' => 1
    ]
];

echo "Creating 10 warehouse waybills...\n";

$created_count = 0;

foreach ($waybill_data as $index => $data) {
    // Get random customer and delivery
    $customer = $customers[array_rand($customers)];
    $delivery = $deliveries[array_rand($deliveries)];
    
    // Check if waybill number already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $waybills_table WHERE waybill_no = %d",
        $data['waybill_no']
    ));
    
    if ($existing) {
        echo "Waybill {$data['waybill_no']} already exists, skipping...\n";
        continue;
    }
    
    // Prepare waybill data
    $waybill_insert = [
        'direction_id' => 1, // Default direction
        'delivery_id' => $delivery->id,
        'customer_id' => $customer->cust_id,
        'city_id' => 1, // Default city
        'waybill_no' => $data['waybill_no'],
        'product_invoice_number' => $data['product_invoice_number'],
        'product_invoice_amount' => $data['product_invoice_amount'],
        'waybill_items_total' => $data['product_invoice_amount'] * 0.8, // 80% of invoice amount
        'item_length' => $data['item_length'],
        'item_width' => $data['item_width'],
        'item_height' => $data['item_height'],
        'total_mass_kg' => $data['total_mass_kg'],
        'total_volume' => $data['total_volume'],
        'mass_charge' => $data['mass_charge'],
        'volume_charge' => $data['volume_charge'],
        'charge_basis' => 'mass',
        'miscellaneous' => 'Sample warehouse waybill',
        'include_sad500' => 0,
        'include_sadc' => 0,
        'vat_include' => 'VAT',
        'tracking_number' => 'TRK-' . strtoupper(wp_generate_password(8, false)),
        'created_by' => get_current_user_id() ?: 1,
        'last_updated_by' => get_current_user_id() ?: 1,
        'status' => $data['status'],
        'warehouse' => $data['warehouse'],
        'approval' => 'approved',
        'created_at' => current_time('mysql'),
        'last_updated_at' => current_time('mysql')
    ];
    
    // Insert waybill
    $result = $wpdb->insert($waybills_table, $waybill_insert);
    
    if ($result !== false) {
        $created_count++;
        echo "✓ Created waybill {$data['waybill_no']} for {$customer->name} {$customer->surname} ({$customer->company_name}) - R" . number_format($data['product_invoice_amount'], 2) . "\n";
    } else {
        echo "✗ Failed to create waybill {$data['waybill_no']}\n";
    }
}

echo "\n🎉 Successfully created $created_count warehouse waybills!\n";
echo "You can now view them in the Warehouse page.\n";
?>
