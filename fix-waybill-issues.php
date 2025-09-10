<?php
/**
 * Fix Waybill Creation Issues
 * This script will help identify and fix common waybill creation problems
 */

// Load WordPress
require_once('../../../wp-load.php');

echo "<h1>Waybill Creation Issue Fixer</h1>\n";

global $wpdb;

// Check database connection
if ($wpdb->db_connect_error) {
    echo "❌ Database connection error: " . $wpdb->db_connect_error . "<br>\n";
    echo "<strong>SOLUTION:</strong> Check your WordPress database configuration in wp-config.php<br>\n";
    echo "Make sure MAMP is running and the database credentials are correct.<br><br>\n";
    exit;
} else {
    echo "✅ Database connection successful<br>\n";
}

// Function to create missing required data
function createMissingData() {
    global $wpdb;
    
    echo "<h3>Creating Missing Required Data:</h3>\n";
    
    // Check and create operating cities
    $cities_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_operating_cities");
    if ($cities_count == 0) {
        echo "Creating default operating cities...<br>\n";
        $wpdb->insert(
            $wpdb->prefix . 'kit_operating_cities',
            [
                'city_name' => 'Johannesburg',
                'country_id' => 1,
                'created_at' => current_time('mysql')
            ]
        );
        $wpdb->insert(
            $wpdb->prefix . 'kit_operating_cities',
            [
                'city_name' => 'Cape Town',
                'country_id' => 1,
                'created_at' => current_time('mysql')
            ]
        );
        echo "✅ Created default operating cities<br>\n";
    } else {
        echo "✅ Operating cities exist ($cities_count records)<br>\n";
    }
    
    // Check and create shipping directions
    $directions_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_shipping_directions");
    if ($directions_count == 0) {
        echo "Creating default shipping directions...<br>\n";
        $wpdb->insert(
            $wpdb->prefix . 'kit_shipping_directions',
            [
                'origin_country_id' => 1,
                'destination_country_id' => 1,
                'created_at' => current_time('mysql')
            ]
        );
        echo "✅ Created default shipping direction<br>\n";
    } else {
        echo "✅ Shipping directions exist ($directions_count records)<br>\n";
    }
    
    // Check and create deliveries
    $deliveries_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_deliveries");
    if ($deliveries_count == 0) {
        echo "Creating default deliveries...<br>\n";
        $wpdb->insert(
            $wpdb->prefix . 'kit_deliveries',
            [
                'delivery_reference' => 'default',
                'origin_country' => 'South Africa',
                'destination_country' => 'South Africa',
                'created_at' => current_time('mysql')
            ]
        );
        $wpdb->insert(
            $wpdb->prefix . 'kit_deliveries',
            [
                'delivery_reference' => 'warehoused',
                'origin_country' => 'South Africa',
                'destination_country' => 'South Africa',
                'created_at' => current_time('mysql')
            ]
        );
        echo "✅ Created default deliveries<br>\n";
    } else {
        echo "✅ Deliveries exist ($deliveries_count records)<br>\n";
    }
    
    // Check and create countries
    $countries_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_countries");
    if ($countries_count == 0) {
        echo "Creating default countries...<br>\n";
        $wpdb->insert(
            $wpdb->prefix . 'kit_countries',
            [
                'country_name' => 'South Africa',
                'country_code' => 'ZA',
                'created_at' => current_time('mysql')
            ]
        );
        echo "✅ Created default country<br>\n";
    } else {
        echo "✅ Countries exist ($countries_count records)<br>\n";
    }
}

// Function to test waybill creation
function testWaybillCreation() {
    echo "<h3>Testing Waybill Creation:</h3>\n";
    
    // Test data
    $test_data = [
        'customer_name' => 'Test Customer',
        'customer_surname' => 'Test Surname',
        'cell' => '0123456789',
        'address' => '123 Test Street, Test City',
        'destination_country' => '1',
        'destination_city' => '1',
        'delivery_id' => '1',
        'direction_id' => '1',
        'item_length' => '10',
        'item_width' => '10',
        'item_height' => '10',
        'total_mass_kg' => '5',
        'total_volume' => '0.001',
        'mass_charge' => '50',
        'volume_charge' => '30',
        'charge_basis' => 'mass',
        // Warehouse status now managed by warehouse_items table
        'vat_include' => '0',
        'include_sad500' => '0',
        'include_sadc' => '0',
        'custom_items' => [
            [
                'item_name' => 'Test Item',
                'quantity' => '1',
                'unit_price' => '100'
            ]
        ]
    ];
    
    // Simulate POST data
    $_POST = $test_data;
    
    try {
        if (class_exists('KIT_Waybills')) {
            $result = KIT_Waybills::save_waybill($test_data);
            
            if (is_wp_error($result)) {
                echo "❌ Waybill creation failed: " . $result->get_error_message() . "<br>\n";
                echo "Error code: " . $result->get_error_code() . "<br>\n";
            } else {
                echo "✅ Waybill created successfully!<br>\n";
                echo "Waybill ID: " . $result['id'] . "<br>\n";
                echo "Waybill Number: " . $result['waybill_no'] . "<br>\n";
            }
        } else {
            echo "❌ KIT_Waybills class not found<br>\n";
        }
    } catch (Exception $e) {
        echo "❌ Waybill creation exception: " . $e->getMessage() . "<br>\n";
    }
}

// Main execution
echo "<h2>Step 1: Database Connection Check</h2>\n";
if ($wpdb->db_connect_error) {
    echo "❌ Database connection failed. Please check your MAMP configuration.<br>\n";
    exit;
}

echo "<h2>Step 2: Table Existence Check</h2>\n";
$required_tables = [
    'kit_waybills' => 'Waybills',
    'kit_waybill_items' => 'Waybill Items',
    'kit_customers' => 'Customers',
    'kit_deliveries' => 'Deliveries',
    'kit_shipping_directions' => 'Shipping Directions',
    'kit_operating_cities' => 'Operating Cities',
    'kit_countries' => 'Countries'
];

$missing_tables = [];
foreach ($required_tables as $table => $name) {
    $table_name = $wpdb->prefix . $table;
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    if ($exists) {
        echo "✅ $name table exists<br>\n";
    } else {
        echo "❌ $name table missing: $table_name<br>\n";
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo "<strong>SOLUTION:</strong> Missing tables detected. Please run the plugin activation to create missing tables.<br>\n";
    echo "Missing tables: " . implode(', ', $missing_tables) . "<br><br>\n";
}

echo "<h2>Step 3: Required Data Check</h2>\n";
createMissingData();

echo "<h2>Step 4: Waybill Creation Test</h2>\n";
testWaybillCreation();

echo "<h2>Step 5: Common Issues and Solutions</h2>\n";
echo "<h3>If waybill creation is still failing, check:</h3>\n";
echo "1. <strong>JavaScript Errors:</strong> Open browser console and check for JavaScript errors<br>\n";
echo "2. <strong>Form Validation:</strong> Ensure all required fields are filled<br>\n";
echo "3. <strong>Nonce Issues:</strong> Check if nonce validation is working<br>\n";
echo "4. <strong>Permissions:</strong> Ensure user has proper capabilities<br>\n";
echo "5. <strong>AJAX Issues:</strong> Check if AJAX requests are reaching the server<br>\n";

echo "<h3>Debug Steps:</h3>\n";
echo "1. Check browser network tab for failed requests<br>\n";
echo "2. Check WordPress error logs<br>\n";
echo "3. Enable WordPress debug mode in wp-config.php<br>\n";
echo "4. Test with a simple waybill (minimal data)<br>\n";

echo "<h3>Form Requirements:</h3>\n";
echo "• Customer name and surname are required<br>\n";
echo "• Cell phone number is required<br>\n";
echo "• Address is required<br>\n";
echo "• For non-warehoused items: destination country and city are required<br>\n";
echo "• At least one delivery must exist in the system<br>\n";

?>
