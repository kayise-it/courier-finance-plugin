<?php
// Minimal runner for KIT_Bulletproof_Calculator without WordPress.

// Define WP functions used by the calculator if not present
if (!function_exists('get_transient')) {
    function get_transient($key) { return false; }
}

require_once __DIR__ . '/includes/waybill/bulletproof-calculator.php';

// Shim $wpdb-backed getters used by the calculator with safe defaults
if (!class_exists('wpdb')) {
    class wpdb {}
}
global $wpdb; $wpdb = null; // ensure not used

// Monkey patch SAD500/SADC getters by defining wrapper functions if needed
if (!function_exists('kit_sim_sad500')) {
    function kit_sim_sad500() { return 500.00; }
}


function rand_float($min, $max, $dec = 2) {
    $scale = pow(10, $dec);
    return mt_rand($min * $scale, $max * $scale) / $scale;
}

$cases = 100;
$results = [
    'cases' => $cases,
    'violations' => 0,
    'stats' => [
        'basis_mass' => 0,
        'basis_volume' => 0,
        'vat_on' => 0,
        'intl_on' => 0,
    ],
    'samples' => []
];

for ($i = 0; $i < $cases; $i++) {
    $massCharge = rand_float(500, 6000, 2);
    $volumeCharge = rand_float(500, 6000, 2);

    // Totals
    $itemsTotal = 0.0;
    $itemsCount = mt_rand(0, 5);
    for ($j = 0; $j < $itemsCount; $j++) {
        $qty = mt_rand(1, 3);
        $unit = rand_float(100, 2500, 2);
        $itemsTotal += $qty * $unit;
    }

    $miscTotal = 0.0;
    $miscCount = mt_rand(0, 3);
    for ($j = 0; $j < $miscCount; $j++) {
        $qty = mt_rand(1, 2);
        $unit = rand_float(50, 1500, 2);
        $miscTotal += $qty * $unit;
    }

    $includeVat = (bool) mt_rand(0, 1);
    $includeSad500 = (bool) mt_rand(0, 1);
    $includeSadc = (bool) mt_rand(0, 1);

    $params = [
        'mass_charge' => $massCharge,
        'volume_charge' => $volumeCharge,
        'misc_total' => $miscTotal,
        'waybill_items_total' => $itemsTotal,
        'charge_basis' => 'auto',
        'include_sad500' => $includeSad500,
        'include_sadc' => $includeSadc,
        'include_vat' => $includeVat,
    ];

    if (!$includeVat) {
        // Use explicit international price override to avoid WP deps
        $params['international_price_override'] = 100.0 * 18.5; // USD100 × ZAR18.5
    }

    $breakdown = KIT_Bulletproof_Calculator::calculate_waybill_total($params);

    // Invariants
    $viol = [];
    $primary = $breakdown['base_charges']['primary_charge'];
    $add = $breakdown['additional_charges'];
    $tot = $breakdown['totals'];

    $expectedAdd = $add['misc_total'] + $add['sad500'] + $add['sadc'] + $add['vat'] + $add['international_price'];
    if (abs($expectedAdd - $add['total']) > 0.01) $viol[] = 'additional_mismatch';

    $expectedFinal = $primary['amount'] + $add['total'];
    if (abs($expectedFinal - $tot['final_total']) > 0.01) $viol[] = 'final_total_mismatch';

    if ($includeVat && $add['international_price'] > 0.0) $viol[] = 'vat_and_international_both';
    if (!$includeVat && $add['vat'] > 0.0) $viol[] = 'vat_present_when_off';

    if ($primary['basis'] === 'mass') $results['stats']['basis_mass']++; else $results['stats']['basis_volume']++;
    if ($includeVat) $results['stats']['vat_on']++; else $results['stats']['intl_on']++;

    if ($viol) {
        $results['violations'] += 1;
        if (count($results['samples']) < 5) {
            $results['samples'][] = [
                'params' => $params,
                'breakdown' => $breakdown,
                'violations' => $viol,
            ];
        }
    } elseif (count($results['samples']) < 5) {
        $results['samples'][] = [
            'params' => $params,
            'breakdown' => $breakdown,
            'violations' => [],
        ];
    }
}

echo json_encode($results, JSON_PRETTY_PRINT);
?>


