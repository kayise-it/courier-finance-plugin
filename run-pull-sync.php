<?php
/**
 * Simulate Sync Pull from Google Sheet (waybills).
 * Clears debug.log then runs the same pull as the UI Sync button.
 *
 * Run from WordPress root: php wp-content/plugins/courier-finance-plugin/run-pull-sync.php
 */
$wp_load = dirname(__DIR__, 3) . '/wp-load.php';
if (!file_exists($wp_load)) {
    die("wp-load.php not found. Run from WordPress root: php wp-content/plugins/courier-finance-plugin/run-pull-sync.php\n");
}

// Minimal server vars for CLI
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';

require_once $wp_load;

// Allow loading settings.php for seed function without admin access check
if (!defined('COURIER_SEED_SIMULATION')) {
    define('COURIER_SEED_SIMULATION', true);
}

// 1) Clear debug.log
$debug_log = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR . '/debug.log' : (__DIR__ . '/../../debug.log');
if (file_exists($debug_log) && is_writable($debug_log)) {
    file_put_contents($debug_log, '');
    echo "Cleared debug.log\n";
} else {
    echo "Note: Could not clear debug.log (missing or not writable)\n";
}

// 2) Load sync class and waybills seed (same as Sync -> Pull in UI)
if (!class_exists('Courier_Google_Sheets_Sync')) {
    require_once __DIR__ . '/includes/class-google-sheets-sync.php';
}
// Waybills pull needs run_google_sheet_seed() from settings.php
$settings = __DIR__ . '/includes/admin-pages/settings.php';
if (file_exists($settings)) {
    ob_start();
    require_once $settings;
    ob_end_clean();
}

echo "\n=== Simulating Waybills Pull from Google Sheet ===\n\n";

$result = Courier_Google_Sheets_Sync::pull_all('waybills');

echo 'Success: ' . (!empty($result['success']) ? 'YES' : 'NO') . "\n";
echo 'Message: ' . ($result['message'] ?? '') . "\n";
echo 'Count: ' . ($result['count'] ?? 0) . "\n";

if (!empty($result['success'])) {
    echo "\nPull completed. Check All Waybills page; Sync error should clear.\n";
} else {
    echo "\nPull failed. Check debug.log for any new errors: " . $debug_log . "\n";
}

exit(!empty($result['success']) ? 0 : 1);
