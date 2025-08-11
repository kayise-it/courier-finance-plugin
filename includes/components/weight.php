<input type="hidden" name="origin_country_id" value="2" id="countrydestination_id" />
<input type="hidden" name="current_rate" id="current_rate" value="<?= isset($waybill['miscellaneous']['others']['mass_rate']) ?>">
<div class="bg-slate-100 p-6 rounded">
    <?= KIT_Commons::h2tag(['title' => 'Mass', 'class' => '']) ?>
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
    <div class="grid md:grid-cols-2 gap-4 justify-center align-middle">
        <div class="items-center">
            <div>
                <?= KIT_Commons::Linput([
                    'label' => "Total Mass (Kg)",
                    'name'  => "total_mass_kg",
                    'id'    => "total_mass_kg",
                    'type'  => 'number',
                    'value' => $total_mass_kg,
                    'class' => '',
                    'special' => '',
                    'onclick' => '',
                ]); ?>
            </div>
            <div>
                <?= KIT_Commons::Linput([
                    'label' => 'Mass Rate (R)',
                    'name'  => 'mass_rate',
                    'id'  => 'mass_rate',
                    'type'  => 'number',
                    'value' => esc_attr($waybill['miscellaneous']['others']['mass_rate'] ?? null),
                    'class' => '',
                    'special' => 'readonly',
                    'tabindex' => '1',
                ]); ?>
            </div>
            <div id="" class="text-sm text-gray-700">
                = R<span id="mass_charge_display"><?= esc_html($waybill['mass_charge'] ?? '0.00'); ?></span> <span id="manipulated_mass_charge_display"></span>
            </div>
        </div>

        <div class="">
            <div>
                <?= KIT_Commons::Linput([
                    'label' => 'Mass Total Cost (R)',
                    'name'  => 'mass_charge',
                    'id'  => 'mass_charge',
                    'type'  => 'text',
                    'value' => esc_attr($waybill['mass_charge'] ?? null),
                    'class' => '',
                    'special' => 'readonly',
                ]); ?>
            </div>
            <div>
                <label for="enable_price_manipulator">
                    <div>
                        <span class="block text-xs font-medium text-gray-700 ">Custom Pricing</span>

                        <input <?= !empty($checkManny) ?> type="checkbox" id="enable_price_manipulator" name="enable_price_manipulator" class="form-checkbox my-2 text-blue-600">
                    </div>
                </label>
                <?php $showing = (!empty($checkManny) == 'checked') ? 'block' : 'none'; ?>
                <div id="price_manipulator_input_container" style="display: <?= $showing ?>;">
                    <?php
                    $massRate = $waybill['miscellaneous']['others']['mass_rate'] ?? null;

                    echo KIT_Commons::Linput([
                        'label' => 'Add to Charge  (R)',
                        'name'  => 'mass_charge_manipulator',
                        'id'    => 'mass_charge_manipulator',
                        'type'  => 'number',
                        'value' =>  $waybill['mass_charge_manipulator'] ?? 0,
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-500',
                    ]);
                    ?>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Get all required elements
                        const checkbox = document.getElementById('enable_price_manipulator');
                        const inputContainer = document.getElementById('price_manipulator_input_container');
                        const totalMassInput = document.getElementById('total_mass_kg');
                        const massRateInput = document.getElementById('mass_rate');
                        const massChargeInput = document.getElementById('mass_charge');
                        const massChargeDisplay = document.getElementById('mass_charge_display');
                        const manipulatedMassChargeDisplay = document.getElementById('manipulated_mass_charge_display');
                        const manipulatorInput = document.getElementById('mass_charge_manipulator');
                        
                        // Function to calculate mass charge
                        function calculateMassCharge() {
                            const totalMass = parseFloat(totalMassInput.value) || 0;
                            const massRate = parseFloat(massRateInput.value) || 0;
                            const manipulatorValue = parseFloat(manipulatorInput.value) || 0;
                            
                            let baseCharge = totalMass * massRate;
                            let finalCharge = baseCharge;
                            
                            // Apply price manipulator if enabled
                            if (checkbox && checkbox.checked) {
                                finalCharge = baseCharge + manipulatorValue;
                            }
                            
                            // Update displays
                            massChargeDisplay.textContent = baseCharge.toFixed(2);
                            massChargeInput.value = finalCharge.toFixed(2);
                            
                            if (checkbox && checkbox.checked && manipulatorValue > 0) {
                                manipulatedMassChargeDisplay.textContent = ` + R${manipulatorValue.toFixed(2)} (manipulated)`;
                            } else {
                                manipulatedMassChargeDisplay.textContent = '';
                            }
                        }
                        
                        // Event listeners
                        if (checkbox && inputContainer) {
                            checkbox.addEventListener('change', function() {
                                inputContainer.style.display = this.checked ? 'block' : 'none';
                                calculateMassCharge();
                            });
                            
                            // If editing and value exists, show input and check the box
                            <?php if (!empty($waybill['mass_charge_manipulator'])) : ?>
                                checkbox.checked = true;
                                inputContainer.style.display = 'block';
                            <?php endif; ?>
                        }
                        
                        // Add event listeners for real-time calculation
                        if (totalMassInput) {
                            totalMassInput.addEventListener('input', calculateMassCharge);
                        }
                        
                        if (massRateInput) {
                            massRateInput.addEventListener('input', calculateMassCharge);
                        }
                        
                        if (manipulatorInput) {
                            manipulatorInput.addEventListener('input', calculateMassCharge);
                        }
                        
                        // Initial calculation
                        calculateMassCharge();
                    });
                </script>
            </div>


        </div>
    </div>
</div>