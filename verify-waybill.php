<?php
// Verify a waybill's totals against DB and calculator
// Usage (web): verify-waybill.php?id=4010 or verify-waybill.php?no=4010
// Usage (CLI): php verify-waybill.php --id=4010  OR php verify-waybill.php --no=4010

// Bootstrap WordPress and plugin environment robustly
require_once __DIR__ . '/bootstrap.php';

// Resolve input from GET or CLI args
$waybillId = 0;
$waybillNo = 0;
if (isset($_GET['id'])) {
	$waybillId = intval($_GET['id']);
}
if (isset($_GET['no'])) {
	$waybillNo = intval($_GET['no']);
}
if (PHP_SAPI === 'cli') {
	$opts = getopt('', ['id::', 'no::']);
	if (isset($opts['id'])) {
		$waybillId = intval($opts['id']);
	}
	if (isset($opts['no'])) {
		$waybillNo = intval($opts['no']);
	}
}

if ($waybillId <= 0 && $waybillNo <= 0) {
	if (PHP_SAPI !== 'cli') {
		header('Content-Type: text/plain');
	}
	echo "Provide a valid waybill ?id=#### or ?no=#### (or --id/--no).\n";
	exit(1);
}

global $wpdb;

// If only waybill number was provided, resolve to ID
if ($waybillId <= 0 && $waybillNo > 0) {
	$table = $wpdb->prefix . 'kit_waybills';
	$waybillId = intval($wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE waybill_no = %d LIMIT 1", $waybillNo)));
}

// Fetch waybill using existing helper which already joins related tables
if (!method_exists('KIT_Waybills', 'bonaWaybill')) {
	echo "Error: KIT_Waybills::bonaWaybill unavailable. Ensure the plugin is up to date.\n";
	exit(1);
}

$waybill = $waybillId > 0 ? KIT_Waybills::bonaWaybill($waybillId) : null;
if (!$waybill && $waybillNo > 0) {
	// Fallback: find id by number again (in case of stale result) and fetch
	$table = $wpdb->prefix . 'kit_waybills';
	$waybillId = intval($wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE waybill_no = %d LIMIT 1", $waybillNo)));
	if ($waybillId > 0) {
		$waybill = KIT_Waybills::bonaWaybill($waybillId);
	}
}

if (!$waybill) {
	$idOrNo = $waybillNo > 0 ? ('no ' . $waybillNo) : ('id ' . ($waybillId ?: '?'));
	echo "Waybill not found: {$idOrNo}\n";
	exit(1);
}

// Unpack common fields
$massCharge    = floatval($waybill['mass_charge'] ?? 0);
$volumeCharge  = floatval($waybill['volume_charge'] ?? 0);
$misc          = $waybill['miscellaneous'] ?? [];
$miscTotal     = floatval($misc['misc_total'] ?? 0);
$itemsTotal    = floatval($waybill['waybill_items_total'] ?? 0);
$chargeBasis   = $waybill['charge_basis'] ?? 'mass';
$includeSad500 = intval($waybill['include_sad500'] ?? 0) === 1 ? 1 : 0;
$includeSadc   = intval($waybill['include_sadc'] ?? 0) === 1 ? 1 : 0;
$includeVat    = intval($waybill['vat_include'] ?? 0) === 1 ? 1 : 0;
switch (strtolower((string)$chargeBasis)) {
	case 'mass':
		$chargeBasis = 'mass';
		break;
	case 'volume':
		$chargeBasis = 'volume';
		break;
	default:
		$chargeBasis = ($massCharge >= $volumeCharge) ? 'mass' : 'volume';
}
$savedTotal    = floatval($waybill['product_invoice_amount'] ?? 0);

// Recalculate using the bulletproof calculator
require_once __DIR__ . '/includes/waybill/bulletproof-calculator.php';
$params = [
	'mass_charge' => $massCharge,
	'volume_charge' => $volumeCharge,
	'misc_total' => $miscTotal,
	'waybill_items_total' => $itemsTotal,
	'charge_basis' => $chargeBasis,
	'include_sad500' => $includeSad500,
	'include_sadc' => $includeSadc,
	'include_vat' => $includeVat,
];
$calc = KIT_Bulletproof_Calculator::calculate_waybill_total($params);
$calcTotal = floatval($calc['totals']['final_total'] ?? 0);

// Output concise report
if (PHP_SAPI !== 'cli') {
	header('Content-Type: text/plain');
}
$waybillNoOut = $waybill['waybill_no'] ?? ($waybillNo ?: 'N/A');
$currency = method_exists('KIT_Commons', 'currency') ? KIT_Commons::currency() . ' ' : 'R';

// Optional on-demand DB correction via ?update=1
if (isset($_GET['update']) && $_GET['update'] === '1' && class_exists('KIT_Waybills')) {
    $verify = KIT_Waybills::doubleCalcWaybillTotal([
        'waybill_no' => intval($waybillNoOut),
        'update_if_mismatch' => true,
    ]);
}

echo "Waybill #{$waybillNoOut} (ID {$waybillId})\n";
echo str_repeat('-', 40) . "\n";
echo "Charge basis: {$chargeBasis}\n";
echo "Mass charge: {$currency}" . number_format($massCharge, 2) . "\n";
echo "Volume charge: {$currency}" . number_format($volumeCharge, 2) . "\n";
echo "Items total: {$currency}" . number_format($itemsTotal, 2) . "\n";
echo "Misc total: {$currency}" . number_format($miscTotal, 2) . "\n";
echo "SAD500: " . ($includeSad500 ? 'Yes' : 'No') . ", SADC: " . ($includeSadc ? 'Yes' : 'No') . ", VAT: " . ($includeVat ? 'Yes' : 'No') . "\n";

// Always show charge breakdown contributing to total
$primaryAmount = floatval($calc['base_charges']['primary_charge']['amount'] ?? 0);
$sad500 = floatval($calc['additional_charges']['sad500'] ?? 0);
$sadc = floatval($calc['additional_charges']['sadc'] ?? 0);
$vat = floatval($calc['additional_charges']['vat'] ?? 0);
$intl = floatval($calc['additional_charges']['international_price'] ?? 0);
$addTotal = floatval($calc['additional_charges']['total'] ?? 0);

if ($includeVat) {
    $vatBase = $itemsTotal; // VAT applies to items total only
    $vatRate = 0.10;
}

echo str_repeat('-', 40) . "\n";
echo "Waybill amount (primary): {$currency}" . number_format($primaryAmount, 2) . "\n";
echo "Items total:            {$currency}" . number_format($itemsTotal, 2) . "\n";
echo "Misc total:             {$currency}" . number_format($miscTotal, 2) . "\n";
echo "SAD500 charge:          {$currency}" . number_format($sad500, 2) . "\n";
echo "SADC certificate:       {$currency}" . number_format($sadc, 2) . "\n";
if ($includeVat) {
    echo "VAT (10% of " . number_format($vatBase, 2) . "):  {$currency}" . number_format($vat, 2) . "\n";
} else {
    echo "International price:    {$currency}" . number_format($intl, 2) . "\n";
}
echo "Additional charges sum: {$currency}" . number_format($addTotal, 2) . "\n";
echo str_repeat('-', 40) . "\n";
echo "Saved total (DB): {$currency}" . number_format($savedTotal, 2) . "\n";
echo "Recalc total:   {$currency}" . number_format($calcTotal, 2) . "\n";

$delta = round($savedTotal - $calcTotal, 2);
$status = abs($delta) < 0.01 ? 'MATCH' : ('DIFF ' . ($delta > 0 ? '+' : '') . number_format($delta, 2));
echo "Result: {$status}\n";

// If mismatch, dump calculator breakdown briefly
if (abs($delta) >= 0.01) {
	echo "\nBreakdown (calculator):\n";
	foreach ($calc as $section => $values) {
		if (!is_array($values)) continue;
		echo strtoupper($section) . ":\n";
		foreach ($values as $k => $v) {
			if (is_array($v)) continue;
			if (is_numeric($v)) {
				echo "  {$k}: {$currency}" . number_format(floatval($v), 2) . "\n";
			} else {
				echo "  {$k}: {$v}\n";
			}
		}
	}
}
