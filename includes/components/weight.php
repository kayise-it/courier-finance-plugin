<input type="hidden" name="origin_country_id" value="2" id="countrydestination_id" />
<input type="hidden" name="current_rate" id="current_rate" value="<?= isset($waybill['miscellaneous']['others']['mass_rate']) ?>">

<!-- Mass Calculator Card -->
<div class="bg-white border border-gray-200 rounded-lg p-4 space-y-4">
    <!-- Header with Icon -->
    <div class="flex items-center space-x-2 border-b border-gray-100 pb-3">
        <div class="p-2 bg-blue-100 rounded-lg">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16l3-1m-3 1l-3-1"></path>
            </svg>
        </div>
        <h4 class="text-sm font-semibold text-gray-900">Weight & Mass Calculation</h4>
    </div>

    <?php
    $total_mass_kg = null;
    if ($waybill !== null) {
        if (is_object($waybill) && isset($waybill->total_mass_kg)) {
            $total_mass_kg = $waybill->total_mass_kg;
        } elseif (is_array($waybill) && isset($waybill['total_mass_kg'])) {
            $total_mass_kg = $waybill['total_mass_kg'];
        }
    }
    ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left Column: Input Fields -->
        <div class="space-y-4">
            <!-- Mass Input with Visual Enhancement -->
            <div class="relative">
                <?= KIT_Commons::Linput([
                    'label' => "Total Mass (kg)",
                    'name'  => "total_mass_kg",
                    'id'    => "total_mass_kg",
                    'type'  => 'number',
                    'value' => $total_mass_kg,
                    'class' => 'w-full px-3 py-2 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent',
                    'special' => 'step="0.01" min="0" placeholder="0.00"',
                ]); ?>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pt-6">
                    <span class="text-xs text-gray-500 font-medium">kg</span>
                </div>
            </div>

            <!-- Rate Input (Read-only) -->
            <div class="relative">
                <?= KIT_Commons::Linput([
                    'label' => 'Rate per kg (R)',
                    'name'  => 'mass_rate',
                    'id'  => 'mass_rate',
                    'type'  => 'number',
                    'value' => esc_attr($waybill['miscellaneous']['others']['mass_rate'] ?? 0),
                    'class' => 'w-full px-3 py-2 pr-12 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none',
                    'special' => 'readonly step="0.01"',
                ]); ?>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pt-6">
                    <span class="text-xs text-gray-500 font-medium">R/kg</span>
                </div>
            </div>

            <!-- Calculation Display -->
            <div class="bg-blue-50 rounded-lg p-3 border border-blue-200">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-blue-900">Calculated Cost:</span>
                    <div class="text-sm font-bold text-blue-900">
                        R <span id="mass_charge_display"><?= esc_html($waybill['mass_charge'] ?? '0.00'); ?></span>
                        <span id="manipulated_mass_charge_display" class="text-orange-600"></span>
                    </div>
                </div>
                <div class="text-xs text-blue-700 mt-1">
                    <span id="mass_calc_formula">0 kg × R 0.00 = R 0.00</span>
                </div>
            </div>
        </div>

        <!-- Right Column: Total Cost & Options -->
        <div class="space-y-4">
            <!-- Total Cost Display -->
            <div class="relative">
                <?= KIT_Commons::Linput([
                    'label' => 'Total Mass Cost (R)',
                    'name'  => 'mass_charge',
                    'id'  => 'mass_charge',
                    'type'  => 'text',
                    'value' => esc_attr($waybill['mass_charge'] ?? '0.00'),
                    'class' => 'w-full px-3 py-2 pr-8 border border-gray-300 rounded-lg bg-gray-50 font-semibold text-gray-900 focus:outline-none',
                    'special' => 'readonly',
                ]); ?>
                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pt-6">
                    <span class="text-xs text-gray-500 font-medium">ZAR</span>
                </div>
            </div>

            <!-- Custom Pricing Section -->
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <div class="flex items-center space-x-3 mb-3">
                    <input <?= !empty($checkManny) ? 'checked' : '' ?> 
                           type="checkbox" 
                           id="enable_price_manipulator" 
                           name="enable_price_manipulator" 
                           class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
                    <label for="enable_price_manipulator" class="text-sm font-medium text-amber-900 cursor-pointer">
                        Enable Custom Pricing
                    </label>
                </div>
                
                <p class="text-xs text-amber-700 mb-3">Add additional charges or apply discounts to the base rate.</p>
                
                <?php $showing = (!empty($checkManny) == 'checked') ? 'block' : 'none'; ?>
                <div id="price_manipulator_input_container" style="display: <?= $showing ?>;" class="transition-all duration-200">
                    <div class="relative">
                        <?php
                        $massRate = $waybill['miscellaneous']['others']['mass_rate'] ?? 0;
                        echo KIT_Commons::Linput([
                            'label' => 'Additional Charge (+) or Discount (-)',
                            'name'  => 'mass_charge_manipulator',
                            'id'    => 'mass_charge_manipulator',
                            'type'  => 'number',
                            'value' => $massRate,
                            'class' => 'w-full px-3 py-2 pr-8 border border-amber-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent',
                            'special' => 'step="0.01" placeholder="0.00"',
                        ]);
                        ?>
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pt-6">
                            <span class="text-xs text-amber-600 font-medium">R</span>
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-amber-600">
                        <span class="font-medium">Tip:</span> Use negative values for discounts (e.g., -5.00)
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('enable_price_manipulator');
    const inputContainer = document.getElementById('price_manipulator_input_container');
    const massInput = document.getElementById('total_mass_kg');
    const rateInput = document.getElementById('mass_rate');
    const chargeDisplay = document.getElementById('mass_charge_display');
    const formulaDisplay = document.getElementById('mass_calc_formula');
    const totalCostInput = document.getElementById('mass_charge');
    const manipulatorInput = document.getElementById('mass_charge_manipulator');

    // Toggle custom pricing visibility
    if (checkbox && inputContainer) {
        checkbox.addEventListener('change', function() {
            inputContainer.style.display = this.checked ? 'block' : 'none';
            updateCalculation();
        });
    }

    // Real-time calculation updates
    function updateCalculation() {
        const mass = parseFloat(massInput?.value || 0);
        const rate = parseFloat(rateInput?.value || 0);
        const manipulator = checkbox?.checked ? parseFloat(manipulatorInput?.value || 0) : 0;
        
        const baseCharge = mass * rate;
        const totalCharge = baseCharge + manipulator;
        
        // Update displays
        if (chargeDisplay) {
            chargeDisplay.textContent = baseCharge.toFixed(2);
        }
        if (formulaDisplay) {
            formulaDisplay.textContent = `${mass} kg × R ${rate.toFixed(2)} = R ${baseCharge.toFixed(2)}`;
        }
        if (totalCostInput) {
            totalCostInput.value = totalCharge.toFixed(2);
        }
        
        // Show manipulator effect
        const manipulatorDisplay = document.getElementById('manipulated_mass_charge_display');
        if (manipulatorDisplay && manipulator !== 0) {
            manipulatorDisplay.textContent = ` ${manipulator >= 0 ? '+' : ''}${manipulator.toFixed(2)}`;
        } else if (manipulatorDisplay) {
            manipulatorDisplay.textContent = '';
        }
    }

    // Attach event listeners
    [massInput, rateInput, manipulatorInput].forEach(input => {
        if (input) {
            input.addEventListener('input', updateCalculation);
        }
    });

    // Initialize if editing existing waybill
    <?php if (!empty($waybill->price_manipulator)) : ?>
        checkbox.checked = true;
        inputContainer.style.display = 'block';
    <?php endif; ?>
    
    // Initial calculation
    updateCalculation();
});
</script>