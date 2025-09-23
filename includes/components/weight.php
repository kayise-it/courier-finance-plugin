<?php if (!defined('ABSPATH')) { exit; } ?>
<?php
// Include user roles for permission checking
require_once COURIER_FINANCE_PLUGIN_PATH . 'includes/user-roles.php';
?>
<input type="hidden" name="origin_country_id" value="2" id="countrydestination_id" />
<input type="hidden" name="direction_id" id="direction_id" value="<?= esc_attr($direction_id ?? (isset($waybill) && isset($waybill['direction_id']) ? $waybill['direction_id'] : '') ?? '') ?>" />
<input type="hidden" name="current_rate" id="current_rate" value="<?= isset($waybill) && isset($waybill['miscellaneous']) && isset($waybill['miscellaneous']['others']) && isset($waybill['miscellaneous']['others']['mass_rate']) ? $waybill['miscellaneous']['others']['mass_rate'] : '' ?>">
<input type="hidden" name="base_rate" id="base_rate" value="<?= esc_attr((isset($waybill) && isset($waybill['miscellaneous']) && isset($waybill['miscellaneous']['others']) && isset($waybill['miscellaneous']['others']['mass_rate']) ? $waybill['miscellaneous']['others']['mass_rate'] : '') ?? (isset($waybill) && isset($waybill['mass_rate']) ? $waybill['mass_rate'] : '') ?? '') ?>">
<div class="bg-slate-100">
    <?= KIT_Commons::h2tag(['title' => 'Mass', 'class' => '']) ?>
    
    <!-- ✅ BULLETPROOF: Loading indicator styles -->
    <style>
        .loading {
            position: relative;
            background-color: #f3f4f6 !important;
        }
        .loading::after {
            content: '';
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            border: 2px solid rgba(0,0,0,0.1);
            border-radius: 50%;
            border-top-color: #3498db;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: translateY(-50%) rotate(360deg); }
        }
        .error-message {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            margin-top: 8px;
        }
        .manipulator-focused {
            border: 2px solid #3b82f6 !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
            outline: none !important;
        }
    </style>
    <?php
    $total_mass_kg = null;

    // Safety check for $waybill variable
    if (isset($waybill) && $waybill !== null) {
        if (is_object($waybill) && isset($waybill->total_mass_kg)) {
            $total_mass_kg = $waybill->total_mass_kg;
        } elseif (is_array($waybill) && isset($waybill['total_mass_kg'])) {
            $total_mass_kg = $waybill['total_mass_kg'];
        }
    }
    ?>
    <div class="md:grid md:grid-cols-2 gap-4 justify-center align-middle">
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
            <?php if (KIT_User_Roles::can_see_prices()): ?>
            <div>
                <?= KIT_Commons::Linput([
                    'label' => 'Mass Rate (R)',
                    'name'  => 'mass_rate',
                    'id'  => 'mass_rate',
                    'type'  => 'number',
                    'value' => esc_attr(isset($waybill) ? ($waybill['miscellaneous']['others']['mass_rate'] ?? null) : null),
                    'class' => '',
                    'special' => 'readonly',
                    'tabindex' => '1',
                ]); ?>
            </div>
            <?php endif; ?>
            <div class="text-sm text-gray-700">
                = R<span id="mass_base_display">0.00</span>
                <span id="mass_manip_display"></span>
                <span id="mass_equals_display"></span>
            </div>
        </div>

        <div class="">
            <?php if (KIT_User_Roles::can_see_prices()): ?>
            <div>
                <?= KIT_Commons::Linput([
                    'label' => 'Mass Total Cost (R)',
                    'name'  => 'mass_charge',
                    'id'  => 'mass_charge',
                    'type'  => 'text',
                    'value' => esc_attr(isset($waybill) ? ($waybill['mass_charge'] ?? null) : null),
                    'class' => '',
                    'special' => 'readonly',
                ]); ?>
            </div>
            <?php endif; ?>
            <?php if (KIT_User_Roles::can_see_prices()): ?>
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
                    $massRate = isset($waybill) ? ($waybill['miscellaneous']['others']['mass_rate'] ?? null) : null;

                    echo KIT_Commons::Linput([
                        'label' => 'Add to Charge  (R)',
                        'name'  => 'mass_charge_manipulator',
                        'id'    => 'mass_charge_manipulator',
                        'type'  => 'number',
                        'value' => isset($waybill) ? ($waybill['mass_charge_manipulator'] ?? 0) : 0,
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
                        const massBaseDisplay = document.getElementById('mass_base_display');
                        const massManipDisplay = document.getElementById('mass_manip_display');
                        const massEqualsDisplay = document.getElementById('mass_equals_display');
                        const manipulatorInput = document.getElementById('mass_charge_manipulator');

                        // Access the original base rate (kept in a hidden field and updated by AJAX)
                        const baseRateField = document.getElementById('base_rate');
                        // Helper function to properly parse numbers with comma decimal separators
                        function parseNumber(value) {
                            if (!value || value === '') return 0;
                            // Convert comma to dot for decimal separator
                            const normalized = value.toString().replace(',', '.');
                            const parsed = parseFloat(normalized);
                            return Number.isFinite(parsed) ? parsed : 0;
                        }

                        function getBaseRate() {
                            return parseNumber(baseRateField && baseRateField.value);
                        }

                        // Always use the latest visible rate if present; fallback to the snapshot
                        function getEffectiveRate() {
                            const liveRate = parseNumber(massRateInput && massRateInput.value);
                            const baseRate = getBaseRate();
                            
                            // Debug logging (remove in production)
                            if (window.console && console.log) {
                                console.log('Rate calculation:', {
                                    liveRate: liveRate,
                                    baseRate: baseRate,
                                    effectiveRate: (liveRate > 0) ? liveRate : baseRate
                                });
                            }
                            
                            return (liveRate > 0) ? liveRate : baseRate;
                        }

                        // Anchor the BASE RATE when manipulator is enabled so edits don't compound
                        let rateAnchor = null;

                        // ✅ BULLETPROOF: Function to calculate mass charge with comprehensive validation
                        function calculateMassCharge(showRateError = false) {
                            // Clear any existing calculation errors first
                            const errorDiv = document.getElementById('calculation-error');
                            if (errorDiv) {
                                errorDiv.remove();
                            }
                            // Clear any existing errors first
                            clearCalculationErrors();
                            
                            // ✅ BULLETPROOF: Comprehensive input validation and sanitization
                            const rawMass = totalMassInput ? totalMassInput.value : '';
                            const totalMass = parseNumber(rawMass);
                            
                            // ✅ BULLETPROOF: Input validation with detailed error handling
                            if (rawMass !== '' && totalMass < 0) {
                                console.warn('Invalid mass value detected:', rawMass);
                                showCalculationError('Invalid mass value. Please enter a positive number.');
                                if (massChargeInput) massChargeInput.value = '0.00';
                                return;
                            }
                            
                            if (totalMass === 0) {
                                clearCalculationDisplay();
                                return;
                            }
                            
                            // ✅ BULLETPROOF: Validate mass limits
                            if (totalMass > 10000) {
                                showCalculationError('Mass exceeds maximum limit of 10,000 kg.');
                                return;
                            }
                            
                            // ✅ BULLETPROOF: Safe manipulator value extraction
                            const rawManipulator = manipulatorInput ? manipulatorInput.value : '';
                            const addAmount = (checkbox && checkbox.checked) ? parseNumber(rawManipulator) : 0;
                            
                            // Debug logging (remove in production)
                            if (window.console && console.log) {
                                console.log('Manipulator calculation:', {
                                    checkboxChecked: checkbox && checkbox.checked,
                                    rawManipulator: rawManipulator,
                                    addAmount: addAmount,
                                    manipulatorInputExists: !!manipulatorInput
                                });
                            }
                            
                            // ✅ BULLETPROOF: Validate manipulator value
                            if (checkbox && checkbox.checked && rawManipulator !== '' && addAmount < 0) {
                                showCalculationError('Invalid manipulator value. Please enter a positive number.');
                                return;
                            }

                            // ✅ BULLETPROOF: Safe rate calculation with comprehensive validation
                            try {
                            const effectiveRate = getEffectiveRate();
                                
                                // ✅ BULLETPROOF: Validate effective rate - only show error if explicitly requested
                                if (!Number.isFinite(effectiveRate) || effectiveRate <= 0) {
                                    // Only show error if we have a mass value but no valid rate AND showRateError is true
                                    if (totalMass > 0 && showRateError) {
                                        showCalculationError('Invalid rate. Please ensure a valid rate is set.');
                                    }
                                    return;
                                }
                                
                            let baseRateForManip = (checkbox && checkbox.checked)
                                ? (Number.isFinite(rateAnchor) && rateAnchor !== null && rateAnchor > 0 ? rateAnchor : effectiveRate)
                                : effectiveRate;
                                
                            // Ensure we have a valid base rate for manipulation
                            if (checkbox && checkbox.checked && baseRateForManip <= 0) {
                                // Try to get rate from the visible input as fallback
                                const visibleRate = parseNumber(massRateInput && massRateInput.value);
                                if (visibleRate > 0) {
                                    // Use the visible rate as the base rate
                                    rateAnchor = visibleRate;
                                    baseRateForManip = visibleRate;
                                } else {
                                    showCalculationError('Please set a valid mass rate before using custom pricing.');
                                    return;
                                }
                            }
                                    
                            const newRate = baseRateForManip + (checkbox && checkbox.checked ? addAmount : 0);
                            
                            // Debug logging (remove in production)
                            if (window.console && console.log) {
                                console.log('Final calculation:', {
                                    baseRateForManip: baseRateForManip,
                                    addAmount: addAmount,
                                    newRate: newRate,
                                    checkboxChecked: checkbox && checkbox.checked
                                });
                            }
                                
                                // ✅ BULLETPROOF: Validate new rate
                                if (!Number.isFinite(newRate) || newRate <= 0) {
                                    showCalculationError('Invalid calculated rate. Please check your inputs.');
                                    return;
                                }
                                
                            const finalCharge = totalMass * newRate;

                                // ✅ BULLETPROOF: Validate final charge
                                if (!Number.isFinite(finalCharge) || finalCharge < 0) {
                                    showCalculationError('Invalid calculated charge. Please check your inputs.');
                                    return;
                                }

                                // ✅ BULLETPROOF: Update displays with validation
                            if (massBaseDisplay) {
                                    massBaseDisplay.textContent = baseRateForManip.toFixed(2);
                                }
                                if (massChargeInput) {
                                    massChargeInput.value = finalCharge.toFixed(2);
                                }

                            if (checkbox && checkbox.checked) {
                                if (massManipDisplay) massManipDisplay.textContent = ` + R${addAmount.toFixed(2)}`;
                                if (massEqualsDisplay) massEqualsDisplay.textContent = ` = R${newRate.toFixed(2)}`;
                                if (massRateInput) massRateInput.value = newRate.toFixed(2);
                            } else {
                                if (massManipDisplay) massManipDisplay.textContent = '';
                                if (massEqualsDisplay) massEqualsDisplay.textContent = '';
                                if (massRateInput && Number.isFinite(effectiveRate) && effectiveRate > 0) {
                                    massRateInput.value = effectiveRate.toFixed(2);
                                }
                            }

                                // Clear any previous calculation errors
                                const errorDiv = document.getElementById('calculation-error');
                                if (errorDiv && errorDiv.parentNode) {
                                    errorDiv.parentNode.removeChild(errorDiv);
                                }
                                
                            } catch (error) {
                                console.error('Calculation error:', error);
                                showCalculationError('An error occurred during calculation. Please try again.');
                            }
                        }

                        // Event listeners
                        if (checkbox && inputContainer) {
                            // ✅ BULLETPROOF: Add click event for immediate feedback
                            checkbox.addEventListener('click', function() {
                                if (this.checked && manipulatorInput) {
                                    // Immediate focus when clicked
                                    setTimeout(function() {
                                        manipulatorInput.focus();
                                        manipulatorInput.select();
                                        manipulatorInput.classList.add('manipulator-focused');
                                        setTimeout(function() {
                                            manipulatorInput.classList.remove('manipulator-focused');
                                        }, 3000);
                                    }, 50);
                                }
                            });
                            
                            checkbox.addEventListener('change', function() {
                                inputContainer.style.display = this.checked ? 'block' : 'none';
                                
                                // ✅ BULLETPROOF: Focus and highlight manipulator input when enabled
                                if (this.checked && manipulatorInput) {
                                    // Small delay to ensure the input is visible
                                    setTimeout(function() {
                                        manipulatorInput.focus();
                                        // Highlight/select the current value
                                        manipulatorInput.select();
                                        // Add visual focus styling
                                        manipulatorInput.classList.add('manipulator-focused');
                                        // Remove focus styling after 3 seconds
                                        setTimeout(function() {
                                            manipulatorInput.classList.remove('manipulator-focused');
                                        }, 3000);
                                    }, 100);
                                }
                                
                                // Anchor base rate at toggle time so manipulator adds to this constant
                                const currentRate = getEffectiveRate();
                                if (this.checked) {
                                    // When enabling custom pricing, ensure we have a valid rate to work with
                                    if (currentRate > 0) {
                                        rateAnchor = currentRate;
                                    } else {
                                        // Try to get rate from visible input
                                        const visibleRate = parseNumber(massRateInput && massRateInput.value);
                                        if (visibleRate > 0) {
                                            rateAnchor = visibleRate;
                                        } else {
                                            // Show error if no valid rate is available
                                            showCalculationError('Please set a valid mass rate before using custom pricing.');
                                            this.checked = false; // Uncheck the box
                                            return;
                                        }
                                    }
                                } else {
                                    rateAnchor = null;
                                }
                                // On uncheck, restore the visible rate to the base rate from server
                                if (!this.checked && massRateInput) {
                                    const br = getBaseRate();
                                    if (br > 0) {
                                        massRateInput.value = br.toFixed(2);
                                    }
                                }
                                // If turning on and rate is zero but mass is present, try to fetch the rate
                                const massVal = parseNumber(totalMassInput && totalMassInput.value);
                                const rateVal = parseNumber(massRateInput && massRateInput.value);
                                if (this.checked && massVal > 0 && rateVal <= 0 && typeof fetchRatePerKg === 'function') {
                                    try { fetchRatePerKg(); } catch(e) {
                                        console.error('Rate fetch failed:', e);
                                        showRateError('Unable to fetch rate. Please check your connection.');
                                    }
                                }
                                calculateMassCharge(false); // Don't show rate error on checkbox toggle
                            });

                            // If editing and value exists, show input and check the box
                            <?php if (isset($waybill) && !empty($waybill['mass_charge_manipulator'])) : ?>
                                checkbox.checked = true;
                                inputContainer.style.display = 'block';
                                // ✅ BULLETPROOF: Focus and highlight manipulator input when pre-checked
                                setTimeout(function() {
                                    if (manipulatorInput) {
                                        manipulatorInput.focus();
                                        manipulatorInput.select();
                                        // Add visual focus styling
                                        manipulatorInput.classList.add('manipulator-focused');
                                        // Remove focus styling after 3 seconds
                                        setTimeout(function() {
                                            manipulatorInput.classList.remove('manipulator-focused');
                                        }, 3000);
                                    }
                                }, 200);
                            <?php endif; ?>
                        }

                        // Add debounced calculation for better UX
                        let calculationTimeout;
                        function debouncedCalculate() {
                            clearTimeout(calculationTimeout);
                            calculationTimeout = setTimeout(function() {
                                calculateMassCharge(false); // Don't show rate error during typing
                            }, 500); // 500ms delay
                        }
                        
                        // Add event listeners for real-time calculation (with debouncing)
                        if (totalMassInput) {
                            totalMassInput.addEventListener('input', function() {
                                // Clear any rate error when user starts typing
                                const errorDiv = document.getElementById('calculation-error');
                                if (errorDiv && errorDiv.textContent.includes('Invalid rate')) {
                                    errorDiv.remove();
                                }
                                debouncedCalculate();
                            });
                            totalMassInput.addEventListener('blur', function() {
                                calculateMassCharge(true); // Show rate error on blur
                            });
                        }
                        if (manipulatorInput) {
                            manipulatorInput.addEventListener('input', debouncedCalculate);
                            manipulatorInput.addEventListener('blur', function() {
                                calculateMassCharge(false); // Don't show rate error for manipulator
                            });
                        }
                        if (massRateInput) {
                            massRateInput.addEventListener('input', debouncedCalculate);
                            massRateInput.addEventListener('blur', function() {
                                calculateMassCharge(false); // Don't show rate error for rate input
                            });
                            massRateInput.addEventListener('change', function() {
                                calculateMassCharge(false); // Don't show rate error on change
                            });
                        }

                        // ✅ BULLETPROOF: Comprehensive error handling functions
                        function showRateError(message) {
                            let errorDiv = document.getElementById('rate-fetch-error');
                            if (!errorDiv) {
                                errorDiv = document.createElement('div');
                                errorDiv.id = 'rate-fetch-error';
                                errorDiv.className = 'text-red-600 text-sm mt-2 p-2 bg-red-50 border border-red-200 rounded';
                                if (massRateInput && massRateInput.parentNode) {
                                    massRateInput.parentNode.appendChild(errorDiv);
                                }
                            }
                            errorDiv.textContent = message;
                            
                            // Auto-hide after 8 seconds
                            setTimeout(() => {
                                if (errorDiv && errorDiv.parentNode) {
                                    errorDiv.parentNode.removeChild(errorDiv);
                                }
                            }, 8000);
                        }
                        
                        function showCalculationError(message) {
                            let errorDiv = document.getElementById('calculation-error');
                            if (!errorDiv) {
                                errorDiv = document.createElement('div');
                                errorDiv.id = 'calculation-error';
                                errorDiv.className = 'text-red-600 text-sm mt-2 p-2 bg-red-50 border border-red-200 rounded';
                                if (totalMassInput && totalMassInput.parentNode) {
                                    totalMassInput.parentNode.appendChild(errorDiv);
                                }
                            }
                            errorDiv.textContent = message;
                            
                            // Auto-hide after 3 seconds (shorter duration for better UX)
                            setTimeout(() => {
                                if (errorDiv && errorDiv.parentNode) {
                                    errorDiv.parentNode.removeChild(errorDiv);
                                }
                            }, 3000);
                        }
                        
                        function clearCalculationDisplay() {
                            if (massChargeInput) massChargeInput.value = '0.00';
                            if (massBaseDisplay) massBaseDisplay.textContent = '0.00';
                            if (massManipDisplay) massManipDisplay.textContent = '';
                            if (massEqualsDisplay) massEqualsDisplay.textContent = '';
                            
                            // Clear any calculation errors
                            const errorDiv = document.getElementById('calculation-error');
                            if (errorDiv && errorDiv.parentNode) {
                                errorDiv.parentNode.removeChild(errorDiv);
                            }
                        }
                        
                        function clearRateErrors() {
                            const rateErrorDiv = document.getElementById('rate-fetch-error');
                            if (rateErrorDiv && rateErrorDiv.parentNode) {
                                rateErrorDiv.parentNode.removeChild(rateErrorDiv);
                            }
                        }
                        
                        function clearCalculationErrors() {
                            const errorDiv = document.getElementById('calculation-error');
                            if (errorDiv && errorDiv.parentNode) {
                                errorDiv.parentNode.removeChild(errorDiv);
                            }
                        }
                        
                        // ✅ IMPROVED RATE FETCHING WITH DEBOUNCING
                        let rateFetchTimeout;
                        if (totalMassInput) {
                            totalMassInput.addEventListener('blur', function() {
                                const mass = parseNumber(this.value);
                                if (mass > 0) {
                                    clearTimeout(rateFetchTimeout);
                                    rateFetchTimeout = setTimeout(function() {
                                        if (typeof fetchRatePerKg === 'function') {
                                            try {
                                                fetchRatePerKg();
                                            } catch(e) {
                                                console.error('Rate fetch failed:', e);
                                                showRateError('Unable to fetch rate. Please try again.');
                                            }
                                        }
                                    }, 300); // 300ms debounce
                                }
                            });
                        }

                        // Initial calculation
                        calculateMassCharge(false); // Don't show rate error on page load
                        
                        // ✅ BULLETPROOF: Auto-trigger rate fetch on page load if mass is present
                        function autoTriggerRateFetch() {
                            const initialMass = parseNumber(totalMassInput && totalMassInput.value);
                            const initialRate = parseNumber(massRateInput && massRateInput.value);
                            const directionId = document.getElementById('direction_id') ? document.getElementById('direction_id').value : '';
                            const originCountryId = document.getElementById('countrydestination_id') ? document.getElementById('countrydestination_id').value : '';
                            
                            console.log('Auto-trigger check:', {
                                mass: initialMass,
                                rate: initialRate,
                                directionId: directionId,
                                originCountryId: originCountryId
                            });
                            
                            if (initialMass > 0 && initialRate <= 0 && directionId) {
                                console.log('Auto-triggering rate fetch...');
                                if (typeof fetchRatePerKg === 'function') {
                                    try {
                                        fetchRatePerKg();
                                    } catch(e) {
                                        console.error('Auto rate fetch failed:', e);
                                    }
                                }
                            } else if (initialMass > 0 && initialRate <= 0 && !directionId) {
                                // Silently skip rate fetch if direction_id is missing - this is normal behavior
                                console.log('Rate fetch skipped: direction_id not available yet');
                            }
                        }
                        
                        // Trigger immediately if conditions are met
                        autoTriggerRateFetch();
                        
                        // Also trigger after a delay to ensure all elements are loaded
                        setTimeout(autoTriggerRateFetch, 1000);
                        
                        // ✅ BULLETPROOF: Additional trigger for edit mode scenarios
                        // Check if we're in edit mode (waybill data exists but rate is missing)
                        <?php if (isset($waybill) && !empty($waybill) && (!isset($waybill['miscellaneous']['others']['mass_rate']) || empty($waybill['miscellaneous']['others']['mass_rate']))): ?>
                        setTimeout(function() {
                            console.log('Edit mode detected - triggering rate fetch...');
                            autoTriggerRateFetch();
                        }, 2000);
                        <?php endif; ?>
                        
                        // ✅ BULLETPROOF: Fallback - try to find direction_id from form or simulate click
                        setTimeout(function() {
                            const massValue = parseNumber(totalMassInput && totalMassInput.value);
                            const rateValue = parseNumber(massRateInput && massRateInput.value);
                            
                            if (massValue > 0 && rateValue <= 0) {
                                // Try to find direction_id from form inputs
                                const formDirectionId = document.querySelector('input[name="direction_id"]');
                                if (formDirectionId && formDirectionId.value) {
                                    console.log('Found direction_id in form, updating hidden input...');
                                    const directionIdInput = document.getElementById('direction_id');
                                    if (directionIdInput) {
                                        directionIdInput.value = formDirectionId.value;
                                        console.log('Updated direction_id, retrying rate fetch...');
                                        autoTriggerRateFetch();
                                        return;
                                    }
                                }
                                
                                console.log('Fallback: Simulating click on mass input...');
                                if (totalMassInput) {
                                    // Trigger both click and focus events
                                    totalMassInput.click();
                                    totalMassInput.focus();
                                    totalMassInput.blur();
                                }
                            }
                        }, 3000);
                    });
                </script>
            </div>
            <?php endif; ?>


        </div>
    </div>
</div>