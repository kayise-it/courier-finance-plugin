<?php
// Test the new bulletproof calculator
require_once('bootstrap.php');
require_once('includes/waybill/bulletproof-calculator.php');

echo "<h2>🚀 BULLETPROOF Waybill Calculator Test</h2>";

// Test scenarios
$test_scenarios = [
    [
        'name' => 'Scenario 1: Mass + SAD500 + VAT (from your image)',
        'params' => [
            'mass_charge' => 62500.00,
            'volume_charge' => 2916.00,
            'misc_total' => 0.00,
            'waybill_items_total' => 0.00,
            'charge_basis' => 'mass',
            'include_sad500' => true,
            'include_sadc' => false,
            'include_vat' => true
        ]
    ],
    [
        'name' => 'Scenario 2: Mass + International Price (no VAT)',
        'params' => [
            'mass_charge' => 62500.00,
            'volume_charge' => 2916.00,
            'misc_total' => 0.00,
            'waybill_items_total' => 0.00,
            'charge_basis' => 'mass',
            'include_sad500' => false,
            'include_sadc' => false,
            'include_vat' => false
        ]
    ],
    [
        'name' => 'Scenario 3: Volume + SAD500 + SADC + VAT',
        'params' => [
            'mass_charge' => 10000.00,
            'volume_charge' => 15000.00,
            'misc_total' => 500.00,
            'waybill_items_total' => 1000.00,
            'charge_basis' => 'volume',
            'include_sad500' => true,
            'include_sadc' => true,
            'include_vat' => true
        ]
    ],
    [
        'name' => 'Scenario 4: Auto-select (mass higher) + All charges',
        'params' => [
            'mass_charge' => 20000.00,
            'volume_charge' => 15000.00,
            'misc_total' => 1000.00,
            'waybill_items_total' => 2000.00,
            'charge_basis' => 'auto',
            'include_sad500' => true,
            'include_sadc' => true,
            'include_vat' => true
        ]
    ]
];

foreach ($test_scenarios as $index => $scenario) {
    echo "<h3>Test " . ($index + 1) . ": " . $scenario['name'] . "</h3>";
    
    // Calculate using bulletproof calculator
    $breakdown = KIT_Bulletproof_Calculator::calculate_waybill_total($scenario['params']);
    $display = KIT_Bulletproof_Calculator::format_calculation_display($breakdown);
    
    // Display results
    echo "<div style='background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    
    echo "<h4>📊 Base Charges:</h4>";
    echo "<p><strong>Mass Charge:</strong> R" . $display['mass_charge'] . "</p>";
    echo "<p><strong>Volume Charge:</strong> R" . $display['volume_charge'] . "</p>";
    echo "<p><strong>Selected Charge:</strong> R" . $display['primary_charge'] . " (" . ucfirst($display['charge_basis']) . ")</p>";
    
    echo "<h4>💰 Additional Charges:</h4>";
    echo "<p><strong>Misc Total:</strong> R" . $display['misc_total'] . "</p>";
    echo "<p><strong>SAD500:</strong> R" . $display['sad500'] . " " . ($scenario['params']['include_sad500'] ? "✅" : "❌") . "</p>";
    echo "<p><strong>SADC Certificate:</strong> R" . $display['sadc'] . " " . ($scenario['params']['include_sadc'] ? "✅" : "❌") . "</p>";
    echo "<p><strong>VAT (10%):</strong> R" . $display['vat'] . " " . ($scenario['params']['include_vat'] ? "✅" : "❌") . "</p>";
    echo "<p><strong>International Price:</strong> R" . $display['international_price'] . " " . (!$scenario['params']['include_vat'] ? "✅" : "❌") . "</p>";
    
    echo "<h4>🎯 Final Totals:</h4>";
    echo "<p><strong>Waybill Amount:</strong> R" . $display['waybill_amount'] . "</p>";
    echo "<p><strong>Additional Charges Total:</strong> R" . $display['additional_total'] . "</p>";
    echo "<p><strong>FINAL TOTAL:</strong> <span style='font-size: 18px; font-weight: bold; color: #2563eb;'>R" . $display['final_total'] . "</span></p>";
    
    echo "</div>";
    
    // Show calculation breakdown
    echo "<details style='margin: 10px 0;'>";
    echo "<summary><strong>🔍 Calculation Breakdown</strong></summary>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px; font-size: 12px;'>";
    echo "Primary Charge: R" . $display['primary_charge'] . "\n";
    echo "Misc Total: R" . $display['misc_total'] . "\n";
    echo "SAD500: R" . $display['sad500'] . "\n";
    echo "SADC: R" . $display['sadc'] . "\n";
    echo "VAT: R" . $display['vat'] . "\n";
    echo "International Price: R" . $display['international_price'] . "\n";
    echo "─────────────────────────\n";
    echo "Additional Total: R" . $display['additional_total'] . "\n";
    echo "─────────────────────────\n";
    echo "FINAL TOTAL: R" . $display['final_total'] . "\n";
    echo "</pre>";
    echo "</details>";
    
    echo "<hr>";
}

echo "<h3>✅ Key Benefits of Bulletproof Calculator:</h3>";
echo "<ul>";
echo "<li><strong>Single Source of Truth:</strong> All calculations in one place</li>";
echo "<li><strong>Clear Logic:</strong> Easy to understand and debug</li>";
echo "<li><strong>Consistent Results:</strong> No more duplicate calculation logic</li>";
echo "<li><strong>Detailed Breakdown:</strong> Shows exactly how total is calculated</li>";
echo "<li><strong>Flexible:</strong> Easy to add new charge types</li>";
echo "<li><strong>Testable:</strong> Can be easily unit tested</li>";
echo "</ul>";

echo "<h3>🔧 Next Steps:</h3>";
echo "<ol>";
echo "<li>Replace the old calculation logic with this bulletproof system</li>";
echo "<li>Update the waybill form to use the new calculator</li>";
echo "<li>Update the display components to show the breakdown</li>";
echo "<li>Add validation to ensure all charges are calculated correctly</li>";
echo "</ol>";
?>


