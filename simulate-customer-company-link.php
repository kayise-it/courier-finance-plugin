<?php
/**
 * Simulate Waybills col F (Customer) + col I (Company) → person/company link model.
 *
 * Usage:
 *   php simulate-customer-company-link.php
 *   php simulate-customer-company-link.php --apply   # also ensure DB column + sample upserts (no wipe)
 *
 * @package CourierFinancePlugin
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only.\n");
}

require_once dirname(__DIR__, 3) . '/wp-load.php';

$apply = in_array('--apply', $argv, true);

if (!class_exists('Courier_Google_Sheets') || !Courier_Google_Sheets::is_configured()) {
    fwrite(STDERR, "Google Sheets not configured.\n");
    exit(1);
}

if (class_exists('Database')) {
    Database::ensure_customer_company_id_column();
    Database::create_company_customers_table();
}

/** Mirror Apps Script isLikelyBusinessName_ / kit_seed_customer_looks_like_business. */
function sim_is_business(string $text): bool
{
    $text = trim($text);
    if ($text === '') {
        return false;
    }
    if (function_exists('kit_seed_customer_looks_like_business')) {
        return kit_seed_customer_looks_like_business($text);
    }
    return (bool) preg_match('/\b(ltd|limited|pty|lodge|tours|safari|company)\b/i', $text);
}

function sim_placeholder(string $text): bool
{
    $v = strtolower(trim($text));
    return $v === '' || in_array($v, ['n/a', 'na', 'none', '-', '--', 'null', 'private', 'individual', '0', 'customer', 'client'], true);
}

$ssid = Courier_Google_Sheets::get_default_spreadsheet_id();
$rows = Courier_Google_Sheets::get_values($ssid, 'A1:AJ5000', 'Waybills');
if (!is_array($rows) || count($rows) < 2) {
    fwrite(STDERR, "Waybills empty.\n");
    exit(1);
}

$header = array_map(static fn($c) => strtolower(trim((string) $c)), $rows[0]);
$colF = array_search('customer', $header, true);
$colI = array_search('company', $header, true);
if ($colF === false) {
    $colF = 5;
}
if ($colI === false) {
    $colI = 8;
}

$stats = [
    'rows' => 0,
    'person_only' => 0,
    'company_only' => 0,
    'person_plus_company' => 0,
    'blank' => 0,
    'unique_persons' => [],
    'unique_companies' => [],
    'links' => [], // person => company
];

for ($r = 1; $r < count($rows); $r++) {
    $row = $rows[$r];
    $person = trim((string) ($row[$colF] ?? ''));
    $company = trim((string) ($row[$colI] ?? ''));
    if (sim_placeholder($company)) {
        $company = '';
    }
    if ($person === '' && $company === '') {
        continue;
    }
    $stats['rows']++;

    $fIsBiz = $person !== '' && sim_is_business($person);
    $personLabel = (!$fIsBiz && $person !== '' && !sim_placeholder($person)) ? $person : '';
    $companyLabel = $company !== '' ? $company : ($fIsBiz ? $person : '');

    if ($personLabel !== '' && $companyLabel !== '') {
        $stats['person_plus_company']++;
        $stats['unique_persons'][$personLabel] = true;
        $stats['unique_companies'][$companyLabel] = true;
        $stats['links'][$personLabel] = $companyLabel;
    } elseif ($personLabel !== '') {
        $stats['person_only']++;
        $stats['unique_persons'][$personLabel] = true;
    } elseif ($companyLabel !== '') {
        $stats['company_only']++;
        $stats['unique_companies'][$companyLabel] = true;
    } else {
        $stats['blank']++;
    }
}

echo "=== Waybills F+I customer/company link simulation ===\n";
echo "Spreadsheet: {$ssid}\n";
echo "Rows with party data: {$stats['rows']}\n";
echo "Person only (F): {$stats['person_only']}\n";
echo "Company only (I or business F): {$stats['company_only']}\n";
echo "Person + company (F+I link): {$stats['person_plus_company']}\n";
echo "Unique persons: " . count($stats['unique_persons']) . "\n";
echo "Unique companies: " . count($stats['unique_companies']) . "\n";
echo "Unique person→company links: " . count($stats['links']) . "\n\n";

$samples = array_slice($stats['links'], 0, 15, true);
echo "Sample links (person → company):\n";
foreach ($samples as $p => $c) {
    echo "  - {$p}  →  {$c}\n";
}

global $wpdb;
$col = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM information_schema.columns
     WHERE table_schema = %s AND table_name = %s AND column_name = 'company_id'",
    DB_NAME,
    $wpdb->prefix . 'kit_customers'
));
echo "\nDB kit_customers.company_id column: " . ((int) $col > 0 ? 'YES' : 'NO') . "\n";

if ($apply && class_exists('KIT_Company_Customers') && class_exists('KIT_Customers')) {
    echo "\n--apply: ensuring companies + linking sample persons in DB (upsert by name)...\n";
    $linked = 0;
    $n = 0;
    foreach ($stats['links'] as $person => $company) {
        if (++$n > 25) {
            break;
        }
        $company_id = KIT_Company_Customers::ensure_company($company);
        if ($company_id <= 0) {
            echo "  skip company: {$company}\n";
            continue;
        }
        $parts = preg_split('/\s+/', $person, 2);
        $name = $parts[0] ?? $person;
        $surname = $parts[1] ?? '';
        $cust_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT cust_id FROM {$wpdb->prefix}kit_customers WHERE name = %s AND surname = %s LIMIT 1",
            $name,
            $surname
        ));
        if ($cust_id <= 0) {
            $cust_id = 9000 + $n;
            $wpdb->insert($wpdb->prefix . 'kit_customers', [
                'cust_id' => $cust_id,
                'name' => $name,
                'surname' => $surname,
                'company_id' => $company_id,
                'country_id' => 2,
            ], ['%d', '%s', '%s', '%d', '%d']);
        } else {
            $wpdb->update(
                $wpdb->prefix . 'kit_customers',
                ['company_id' => $company_id],
                ['cust_id' => $cust_id],
                ['%d'],
                ['%d']
            );
        }
        $linked++;
        echo "  linked cust_id={$cust_id} {$person} → company_id={$company_id} {$company}\n";
    }
    echo "Applied links: {$linked}\n";
}

echo "\nDone. Redeploy Code.gs so projection writes kit_customers.company_id from Waybills col I.\n";
exit(0);
