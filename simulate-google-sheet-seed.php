<?php
/**
 * Simulate Google Sheet seed (dry run, no data committed).
 *
 * Usage:
 *   php simulate-google-sheet-seed.php           # Mock data
 *   php simulate-google-sheet-seed.php --real    # Real Google Sheet (if configured)
 */

// Load WordPress
$wp_load = dirname(__DIR__, 2) . '/wp-load.php';
if (!file_exists($wp_load)) {
    $wp_load = dirname(__DIR__, 3) . '/wp-load.php';
}
if (!file_exists($wp_load)) {
    die("wp-load.php not found. Run from plugin directory.\n");
}

// Suppress HTML output for CLI
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/';
}

require_once $wp_load;

if (!defined('COURIER_FINANCE_PLUGIN_PATH')) {
    define('COURIER_FINANCE_PLUGIN_PATH', __DIR__ . '/');
}
// Bypass settings access check for CLI simulation
define('COURIER_SEED_SIMULATION', true);
// Suppress HTML output from settings.php
ob_start();
require_once __DIR__ . '/includes/admin-pages/settings.php';
ob_end_clean();

// Mock sheet rows matching kit_waybills sheet structure (header + data)
$mock_rows = [
    // Header row - columns normalized: spaces -> underscores
    [
        'Driver', 'Dispatch Date', 'WB No', 'Parcel ID', 'Customer', 'City', 'Description',
        'Direction ID', 'Waybill Items Total', 'Item Length', 'Item Width', 'Item Height',
        'Total Mass KG', 'Total Volume', 'Mass Charge', 'Volume Charge', 'Charge Basis',
        'VAT Include', 'Product Invoice Number',
    ],
    // Data row 1
    [
        'Alex Olifiser', '2025-10-31', '4600', 'WB:- 4600', 'John Doe', 'Johannesburg',
        'Sample parcel', '1', '1500', '100', '50', '30', '25', '0.15', '1000', '0', 'MASS',
        '', '',
    ],
    // Data row 2
    [
        'Alex Olifiser', '2025-10-31', '4601', 'WB:- 4601', 'Jane Smith', 'Cape Town',
        'Document shipment', '1', '800', '50', '40', '20', '5', '0.04', '200', '0', 'MASS',
        'VAT', 'INV-001',
    ],
];

// Normalize header for run_google_sheet_seed (it expects same format as API)
$mock_rows[0] = array_map(function ($c) {
    $h = strtolower(trim((string) $c));
    $h = str_replace(' ', '_', $h);
    return $h;
}, $mock_rows[0]);

// Map normalized headers back - the seed uses $col which maps header->index
// The seed does: $header = array_map(..., $rows[0]) - so we need rows[0] as normalized header
// But rows[1], rows[2] are indexed by position. The $col is built from header. So we need:
// Header: driver, dispatch_date, wb_no, parcel_id, customer, city, description, ...
// The get() uses $col[$key] - so we need keys like: waybill_no, parcel_id, cust_name_ignore, city_name_ignore, etc.
// From the seed: waybill_no, parcel_id, cust_name_ignore, customer_id, city_id, city_name_ignore, delivery, driver, delivery_id, dispatch_date, created_at
// Let me fix the mock to use the exact keys the seed looks for:
$mock_header = [
    'driver', 'dispatch_date', 'waybill_no', 'parcel_id', 'cust_name_ignore', 'city_name_ignore',
    'description', 'direction_id', 'waybill_items_total', 'item_length', 'item_width', 'item_height',
    'total_mass_kg', 'total_volume', 'mass_charge', 'volume_charge', 'charge_basis',
    'vat_include', 'product_invoice_number',
];
$mock_rows[0] = $mock_header;

// Ensure run_google_sheet_seed exists
if (!function_exists('run_google_sheet_seed')) {
    die("run_google_sheet_seed not found. Ensure includes/admin-pages/settings.php is loaded.\n");
}

$use_real_sheet = in_array('--real', $argv ?? [], true);

if ($use_real_sheet) {
    if (!class_exists('Courier_Google_Sheets') || !Courier_Google_Sheets::is_configured()) {
        die("Google Sheets not configured. Use mock mode (no --real) or add credentials.\n");
    }
    $range = defined('COURIER_GOOGLE_SEED_RANGE') ? COURIER_GOOGLE_SEED_RANGE : 'A1:AR5000';
    $sheet_name = defined('COURIER_GOOGLE_SEED_SHEET_NAME') ? COURIER_GOOGLE_SEED_SHEET_NAME : 'kit_waybills';
    echo "=== Google Sheet Seed Simulation (REAL SHEET) ===\n";
    $rows = null;
    $sheet_names_to_try = array_filter([$sheet_name, 'Sheet1', 'Sheet 1', 'Data']);
    foreach ($sheet_names_to_try as $try_sheet) {
        try {
            $rows = Courier_Google_Sheets::get_values('', $range, $try_sheet);
            if (!empty($rows) && count($rows) >= 2) {
                echo "Fetched " . (count($rows) - 1) . " data rows from sheet '{$try_sheet}'.\n\n";
                break;
            }
        } catch (Exception $e) {
            continue;
        }
    }
    if ((empty($rows) || count($rows) < 2) && !empty($sheet_names_to_try)) {
        die("Could not fetch data from any sheet. Try defining COURIER_GOOGLE_SEED_SHEET_NAME in wp-config (e.g. \"kit_waybills\").\n");
    }
    if (empty($rows) || count($rows) < 2) {
        die("Sheet has no data or invalid headers.\n");
    }
} else {
    echo "=== Google Sheet Seed Simulation (MOCK) ===\n";
    echo "Running with " . (count($mock_rows) - 1) . " mock data rows...\n\n";
    $rows = $mock_rows;
}

$result = run_google_sheet_seed($rows, true);

echo ($result['success'] ? "SUCCESS" : "FAILED") . "\n";
echo $result['message'] . "\n";
if (!empty($result['stats']['errors'])) {
    echo "\nErrors/Skip reasons:\n";
    foreach ($result['stats']['errors'] as $e) {
        echo "  - $e\n";
    }
}
if (!empty($result['stats'])) {
    echo "\nStats: ";
    print_r($result['stats']);
}

exit($result['success'] ? 0 : 1);
