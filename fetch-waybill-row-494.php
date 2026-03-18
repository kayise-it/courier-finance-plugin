<?php
/**
 * One-off: Fetch kit_waybills row 494 from Google Sheet via API and compare to expected mapping.
 * Run from plugin dir: php fetch-waybill-row-494.php (or via browser if WP loads)
 */
if (php_sapi_name() === 'cli') {
    $plugin_dir = __DIR__;
    $wp_load = $plugin_dir . '/../../../wp-load.php';
    if (!file_exists($wp_load)) {
        $wp_load = $plugin_dir . '/../../../../wp-load.php';
    }
    if (!file_exists($wp_load)) {
        fwrite(STDERR, "wp-load.php not found\n");
        exit(1);
    }
    require_once $wp_load;
}

if (!defined('ABSPATH')) {
    die('ABSPATH not defined');
}

require_once __DIR__ . '/includes/class-google-sheets.php';
require_once __DIR__ . '/includes/class-google-sheets-sync.php';

$sheet_name = Courier_Google_Sheets_Sync::get_sheet_name('waybills');

try {
    $headers = Courier_Google_Sheets::get_values('', 'A1:AN1', $sheet_name);
    $rows = Courier_Google_Sheets::get_values('', 'A494:AN494', $sheet_name);
} catch (Throwable $e) {
    echo "API error: " . $e->getMessage() . "\n";
    exit(1);
}

$header_row = isset($headers[0]) ? $headers[0] : [];
$data_row = isset($rows[0]) ? $rows[0] : [];

echo "=== kit_waybills row 494 (from Google Sheet API) ===\n\n";
echo str_pad('Col', 5) . "| " . str_pad('Header', 22) . "| Value (row 494)\n";
echo str_repeat('-', 5) . '+' . str_repeat('-', 24) . '+' . str_repeat('-', 50) . "\n";

$expected = [
    'A' => 'id (numeric)',
    'B' => 'parcel_id (empty)',
    'C' => 'description',
    'D' => 'direc (direction_id)',
    'E' => 'city_name_ignore',
    'F' => 'city_id (or formula)',
    'G' => 'delivery_id',
    'H' => 'cust_name_ig (customer name)',
    'I' => 'customer_id',
    'J' => 'approval',
    'K' => 'approvi',
    'L' => 'waybill_no',
];

for ($i = 0; $i < max(count($header_row), count($data_row), 13); $i++) {
    $col = $i < 26 ? chr(65 + $i) : chr(64 + (int)($i / 26)) . chr(65 + ($i % 26));
    $h = isset($header_row[$i]) ? substr((string)$header_row[$i], 0, 20) : '';
    $v = isset($data_row[$i]) ? substr((string)$data_row[$i], 0, 48) : '';
    $ok = isset($expected[$col]) ? ' (expected: ' . $expected[$col] . ')' : '';
    echo str_pad($col, 5) . "| " . str_pad($h, 22) . "| " . $v . $ok . "\n";
}

echo "\n=== Mapping check ===\n";
echo "H (cust_name_ig) should be customer name: " . (isset($data_row[7]) ? '"' . $data_row[7] . '"' : 'empty') . "\n";
echo "I (customer_id) should be numeric: " . (isset($data_row[8]) ? '"' . $data_row[8] . '"' : 'empty') . "\n";
echo "D (direc) should be direction_id (number): " . (isset($data_row[3]) ? '"' . $data_row[3] . '"' : 'empty') . "\n";
echo "A (id) should be numeric, not a city name: " . (isset($data_row[0]) ? '"' . $data_row[0] . '"' : 'empty') . "\n";
