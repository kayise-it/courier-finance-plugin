<?php
/**
 * Repair mangled kit_customers names from older Setup Seed splits.
 *
 * Usage:
 *   php repair-mangled-customer-names.php
 *   php repair-mangled-customer-names.php --fix
 */

$wp_load = dirname(__DIR__, 2) . '/wp-load.php';
if (!file_exists($wp_load)) {
    $wp_load = dirname(__DIR__, 3) . '/wp-load.php';
}
if (!file_exists($wp_load)) {
    die("wp-load.php not found.\n");
}

$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
define('COURIER_SEED_SIMULATION', true);

require_once $wp_load;

if (!defined('COURIER_FINANCE_PLUGIN_PATH')) {
    define('COURIER_FINANCE_PLUGIN_PATH', __DIR__ . '/');
}

ob_start();
require_once __DIR__ . '/includes/admin-pages/settings.php';
ob_end_clean();

$apply = in_array('--fix', $argv ?? [], true);

global $wpdb;
$customers_t = $wpdb->prefix . 'kit_customers';
$companies_t = $wpdb->prefix . 'kit_company_customers';

$select_sql = "SELECT c.cust_id, c.name, c.surname, co.company_name
     FROM {$customers_t} c
     LEFT JOIN {$companies_t} co ON c.company_id = co.company_id";

$focus_ids = [8604, 8639, 8733, 8809, 8816, 9044, 9170, 9255];
echo "=== Before (focus rows) ===\n";
foreach ($focus_ids as $id) {
    $r = $wpdb->get_row($wpdb->prepare($select_sql . ' WHERE c.cust_id = %d', $id), ARRAY_A);
    if (!$r) {
        continue;
    }
    echo sprintf(
        "cust_id=%s | name=[%s] | surname=[%s] | company=[%s]\n",
        $r['cust_id'],
        $r['name'],
        $r['surname'],
        $r['company_name']
    );
}

if (!$apply) {
    echo "\nDry run only. Re-run with --fix to apply kit_seed_repair_mangled_customer_names().\n";
    echo "\n=== Would repair ===\n";
    $rows = $wpdb->get_results(
        $select_sql . "
         WHERE c.name LIKE '%Laba%' OR c.name LIKE '%Gaia%' OR c.name LIKE '%Rhino%'
            OR co.company_name LIKE '%Laba%' OR co.company_name LIKE '%Gaia%' OR co.company_name LIKE '%Rhino%'
            OR c.surname LIKE '%Laba%' OR c.surname IN ('Broodryk','Ramos')
         ORDER BY c.cust_id",
        ARRAY_A
    ) ?: [];
    foreach ($rows as $row) {
        $name = trim((string) $row['name']);
        $surname = trim((string) $row['surname']);
        $company = kit_seed_strip_private_company_label(trim((string) $row['company_name']));
        $full = trim($name . ' ' . $surname);
        $n = kit_seed_normalize_customer_row($name, $surname, $company, $full !== '' ? $full : $company);
        $biz = '';
        if (!empty($n['company_name']) && kit_seed_customer_looks_like_business($n['company_name'])) {
            $biz = $n['company_name'];
        } elseif (kit_seed_customer_looks_like_business($full)) {
            $biz = $full;
        } elseif (kit_seed_customer_looks_like_business($name) && $surname !== '') {
            $biz = $name;
        }
        if ($biz !== '' && preg_match('/^(laba\s+laba\b.*?\))\s+laba\b/i', $biz, $m)) {
            $biz = trim($m[1]);
        }
        $new_name = $biz !== '' ? $biz : $n['name'];
        $new_surname = $biz !== '' ? '' : $n['surname'];
        $new_company = $biz !== '' ? $biz : $n['company_name'];
        if ($new_name === $name && $new_surname === $surname && $new_company === $company) {
            continue;
        }
        echo sprintf(
            "cust_id=%s\n  from: name=[%s] surname=[%s] company=[%s]\n  to:   name=[%s] surname=[%s] company=[%s]\n",
            $row['cust_id'],
            $name,
            $surname,
            $company,
            $new_name,
            $new_surname,
            $new_company
        );
    }
    exit(0);
}

$result = kit_seed_repair_mangled_customer_names();
echo "\nRepaired {$result['repaired']} customer row(s).\n";
foreach ($result['details'] as $cust_id => $label) {
    echo "  cust_id={$cust_id} => {$label}\n";
}

echo "\n=== After (focus rows) ===\n";
foreach ($focus_ids as $id) {
    $r = $wpdb->get_row($wpdb->prepare($select_sql . ' WHERE c.cust_id = %d', $id), ARRAY_A);
    if (!$r) {
        continue;
    }
    echo sprintf(
        "cust_id=%s | name=[%s] | surname=[%s] | company=[%s]\n",
        $r['cust_id'],
        $r['name'],
        $r['surname'],
        $r['company_name']
    );
}
