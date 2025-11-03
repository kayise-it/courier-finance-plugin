<?php
/**
 * Total Override Component
 * Allows clients to manually override the calculated total
 * RESTRICTED: Only superadmins (thando, mel, patricia) can use this feature
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if current user is a superadmin
$allowed_superadmins = ['thando', 'mel', 'patricia'];
$is_superadmin = false;

// Use WordPress global current user
global $current_user;

// Load WordPress user if not already loaded
if (!function_exists('wp_get_current_user')) {
    require_once ABSPATH . 'wp-includes/pluggable.php';
}

// Ensure we have a valid user object
if (!isset($current_user) || !is_object($current_user) || !isset($current_user->ID)) {
    // Call wp_get_current_user if it exists (always will in WP context)
    if (function_exists('wp_get_current_user')) {
        /** @var WP_User $current_user */
        $current_user = call_user_func('wp_get_current_user');
    }
}

// Check if user is a superadmin
if ($current_user && isset($current_user->ID) && $current_user->ID > 0 && isset($current_user->user_login)) {
    $is_superadmin = in_array(strtolower($current_user->user_login), $allowed_superadmins);
}

// If not superadmin, don't show this component at all
if (!$is_superadmin) {
    return;
}
?>

<div class="total-override-container bg-white rounded-lg border border-gray-200 p-4 mb-4">
    <div class="flex items-center justify-between mb-3">
        <h3 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
            <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Total Override
        </h3>
        <div class="flex items-center gap-2">
            <label class="inline-flex items-center">
                <input type="checkbox" 
                       id="enable_total_override" 
                       name="enable_total_override" 
                       class="form-checkbox h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 rounded"
                       onchange="toggleTotalOverride(this.checked)">
                <span class="ml-2 text-sm font-medium text-gray-700">Override Total</span>
            </label>
        </div>
    </div>
    
    <div id="total-override-input" class="hidden">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="override_basis" class="block text-sm font-medium text-gray-700 mb-1">
                    Charge Basis
                </label>
                <select id="override_basis" name="override_basis" class="w-full px-3 py-2 border border-gray-300 rounded-md bg-white">
                    <option value="auto">Auto detect</option>
                    <option value="mass">Mass</option>
                    <option value="volume">Volume</option>
                </select>
            </div>
            <div>
                <label for="calculated_total" class="block text-sm font-medium text-gray-700 mb-1">
                    Calculated Total (R)
                </label>
                <input type="text" 
                       id="calculated_total" 
                       name="calculated_total" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600" 
                       readonly>
            </div>
            <div>
                <label for="override_total" class="block text-sm font-medium text-gray-700 mb-1">
                    Override Total (R) <span class="text-red-500">*</span>
                </label>
                <input type="number" 
                       id="override_total" 
                       name="override_total" 
                       step="0.01" 
                       min="0"
                       class="w-full px-3 py-2 border border-orange-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                       placeholder="0.00">
            </div>
        </div>
        
        <!-- Persisted meta for server: which basis was applied -->
        <input type="hidden" id="override_charge_basis" name="override_charge_basis" value="">
        <div id="override_error" class="hidden mt-3 p-2 bg-red-50 border border-red-200 text-red-700 rounded text-sm"></div>
        
        <div class="mt-3 p-3 bg-orange-50 border border-orange-200 rounded-md">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-orange-400 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div class="text-sm text-orange-800">
                    <p class="font-medium mb-1">Override Warning</p>
                    <p>When you override the total, the system will use your manual entry instead of the calculated amount. This bypasses all automatic calculations including VAT, fees, and item totals.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const enableOverrideCheckbox = document.getElementById('enable_total_override');
    const overrideInputContainer = document.getElementById('total-override-input');
    const calculatedTotalInput = document.getElementById('calculated_total');
    const overrideTotalInput = document.getElementById('override_total');
    
    // Calculate current total from form fields (excluding override adjustment row)
    function calculateCurrentTotal() {
        let total = 0;
        
        const massCharge = parseFloat(document.getElementById('mass_charge')?.value || '0');
        total += massCharge;
        
        const volumeCharge = parseFloat(document.getElementById('volume_charge')?.value || '0');
        total += volumeCharge;
        
        const waybillSubtotal = parseFloat(document.getElementById('waybill-subtotal')?.textContent?.replace(/[^\d.-]/g, '') || '0');
        total += waybillSubtotal;
        
        // Sum misc items by iterating rows and excluding the override row
        let miscTotal = 0;
        document.querySelectorAll('#misc-items .dynamic-item').forEach(function(row){
            if (row.classList.contains('override-adjustment-row')) return;
            const priceInput = row.querySelector('input[name*="[misc_price]"]');
            const qtyInput = row.querySelector('input[name*="[misc_quantity]"]');
            const price = parseFloat(priceInput ? priceInput.value : '0') || 0;
            const qty = parseFloat(qtyInput ? qtyInput.value : '0') || 0;
            miscTotal += price * qty;
        });
        total += miscTotal;
        
        return total;
    }

    function detectChargeBasis() {
        const selector = document.getElementById('override_basis');
        const choice = selector ? selector.value : 'auto';
        if (choice === 'mass' || choice === 'volume') return choice;
        const mass = parseFloat(document.getElementById('mass_charge')?.value || '0');
        const volume = parseFloat(document.getElementById('volume_charge')?.value || '0');
        if (mass > 0 && volume <= 0) return 'mass';
        if (volume > 0 && mass <= 0) return 'volume';
        // default to mass when ambiguous
        return 'mass';
    }

    function clearError() {
        const err = document.getElementById('override_error');
        if (err) { err.classList.add('hidden'); err.textContent = ''; }
    }
    function showError(msg) {
        const err = document.getElementById('override_error');
        if (err) { err.textContent = msg; err.classList.remove('hidden'); }
    }

    function parseNum(val){
        if (val === undefined || val === null) return 0;
        const s = String(val).replace(/\s/g,'').replace(',', '.');
        const n = parseFloat(s);
        return Number.isFinite(n) ? n : 0;
    }

    function restoreMassToBase() {
        const baseRateField = document.getElementById('base_rate');
        const totalMassField = document.getElementById('total_mass_kg');
        const massRateField = document.getElementById('mass_rate');
        const massChargeField = document.getElementById('mass_charge');
        const massBaseDisplay = document.getElementById('mass_base_display');
        const baseRate = parseFloat(baseRateField?.value || '0');
        const totalMass = parseFloat(totalMassField?.value || '0');
        if (baseRate > 0) {
            if (massRateField) massRateField.value = baseRate.toFixed(2);
            if (massBaseDisplay) massBaseDisplay.textContent = baseRate.toFixed(2);
            if (massChargeField) massChargeField.value = (Number.isFinite(totalMass) ? totalMass : 0 * baseRate).toFixed(2);
        }
        // Clear manipulator numeric value if present
        const manipInput = document.getElementById('mass_charge_manipulator');
        if (manipInput) manipInput.value = '0';
    }

    function clearVolumeManipulator() {
        const volManipInput = document.getElementById('custom_volume_rate_per_m3');
        if (volManipInput) volManipInput.value = '';
        // Do not force-fetch new base here; volume section will recalc on next input/blur
    }

    // Helper: disable/enable price manipulators when override toggled
    function setManipulatorsDisabled(disabled) {
        const massManip = document.getElementById('enable_price_manipulator');
        const massContainer = document.getElementById('price_manipulator_input_container');
        if (massManip) {
            if (disabled) {
                massManip.checked = false;
                massManip.setAttribute('disabled', 'disabled');
                if (massContainer) massContainer.style.display = 'none';
                restoreMassToBase();
            } else {
                massManip.removeAttribute('disabled');
            }
        }
        const volManip = document.getElementById('enable_volume_price_manipulator');
        const volContainer = document.getElementById('dimension_manipulator_input_container');
        if (volManip) {
            if (disabled) {
                volManip.checked = false;
                volManip.setAttribute('disabled', 'disabled');
                if (volContainer) volContainer.style.display = 'none';
                clearVolumeManipulator();
            } else {
                volManip.removeAttribute('disabled');
            }
        }
    }

    // Apply override by back-calculating the rate for the chosen basis
    function applyOverrideToBasis() {
        if (!enableOverrideCheckbox || !enableOverrideCheckbox.checked) return;
        clearError();
        const desiredTotal = parseNum(overrideTotalInput?.value || '0');
        if (!Number.isFinite(desiredTotal) || desiredTotal <= 0) return;
        const basis = detectChargeBasis();
        const basisInput = document.getElementById('override_charge_basis');
        if (basisInput) basisInput.value = basis;
        
        if (basis === 'mass') {
            const massInput = document.getElementById('total_mass_kg');
            const massRateInput = document.getElementById('mass_rate');
            const massChargeInput = document.getElementById('mass_charge');
            const massBaseDisplay = document.getElementById('mass_base_display');
            const massVal = parseNum(massInput?.value || '0');
            if (!massVal || massVal <= 0) { showError('Enter Total Mass (Kg) to apply override using Mass'); return; }
            const newRate = desiredTotal / massVal;
            if (massRateInput) massRateInput.value = newRate.toFixed(2);
            if (massBaseDisplay) massBaseDisplay.textContent = newRate.toFixed(2);
            if (massChargeInput) massChargeInput.value = desiredTotal.toFixed(2);
        } else {
            const volInput = document.getElementById('total_volume');
            const volChargeInput = document.getElementById('volume_charge');
            const volDisplay = document.getElementById('volume_charge_display');
            const volVal = parseNum(volInput?.value || '0');
            if (!volVal || volVal <= 0) { showError('Enter Total Volume (m³) to apply override using Volume'); return; }
            const newRate = desiredTotal / volVal;
            if (volDisplay) volDisplay.textContent = newRate.toFixed(2);
            if (volChargeInput) volChargeInput.value = desiredTotal.toFixed(2);
        }
    }
    
    // Update calculated total display (basis-specific)
    function updateCalculatedTotal() {
        if (enableOverrideCheckbox && enableOverrideCheckbox.checked) {
            const o = parseNum(overrideTotalInput?.value || '0');
            if (calculatedTotalInput) calculatedTotalInput.value = o.toFixed(2);
            return;
        }
        const basis = detectChargeBasis();
        let current = 0;
        if (basis === 'mass') {
            current = parseNum(document.getElementById('mass_charge')?.value || '0');
        } else if (basis === 'volume') {
            current = parseNum(document.getElementById('volume_charge')?.value || '0');
        }
        if (calculatedTotalInput) {
            calculatedTotalInput.value = current.toFixed(2);
        }
    }
    
    // Toggle override functionality
    window.toggleTotalOverride = function(enabled) {
        if (enabled) {
            overrideInputContainer.classList.remove('hidden');
            setManipulatorsDisabled(true);
            updateCalculatedTotal();
            applyOverrideToBasis();
            
            // Focus on override input
            setTimeout(() => {
                if (overrideTotalInput) {
                    overrideTotalInput.focus();
                }
            }, 100);
        } else {
            overrideInputContainer.classList.add('hidden');
            setManipulatorsDisabled(false);
            if (overrideTotalInput) {
                overrideTotalInput.value = '';
            }
            const basisInput = document.getElementById('override_charge_basis');
            if (basisInput) basisInput.value = '';
            clearError();

            // Restore original calculated values from base rates
            const baseRateField = document.getElementById('base_rate');
            const totalMassField = document.getElementById('total_mass_kg');
            const massRateField = document.getElementById('mass_rate');
            const massChargeField = document.getElementById('mass_charge');
            const massBaseDisplay = document.getElementById('mass_base_display');

            const baseRate = parseFloat(baseRateField?.value || '0');
            const totalMass = parseFloat(totalMassField?.value || '0');
            if (baseRate > 0) {
                if (massRateField) massRateField.value = baseRate.toFixed(2);
                if (massBaseDisplay) massBaseDisplay.textContent = baseRate.toFixed(2);
                if (massChargeField) {
                    const charge = (Number.isFinite(totalMass) ? totalMass : 0) * baseRate;
                    massChargeField.value = charge.toFixed(2);
                }
            }
        }
    };

    // Recompute when user manually switches basis
    const overrideBasisSelect = document.getElementById('override_basis');
    if (overrideBasisSelect) {
        overrideBasisSelect.addEventListener('change', function(){
            if (enableOverrideCheckbox && enableOverrideCheckbox.checked) {
                // Start from base before applying on the new basis
                restoreMassToBase();
                clearVolumeManipulator();
                applyOverrideToBasis();
                updateCalculatedTotal();
            }
        });
    }
    
    // Listen for changes to form fields that affect total calculation
    const totalAffectingFields = [
        'mass_charge',
        'volume_charge'
    ];
    
    totalAffectingFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                if (enableOverrideCheckbox && enableOverrideCheckbox.checked) {
                    updateCalculatedTotal();
                    applyOverrideToBasis();
                }
            });
        }
    });
    
    // Listen for waybill and misc items changes
    const waybillContainer = document.getElementById('custom-waybill-items');
    const miscContainer = document.getElementById('misc-items');
    
    if (waybillContainer) {
        waybillContainer.addEventListener('input', function() {
            if (enableOverrideCheckbox && enableOverrideCheckbox.checked) {
                setTimeout(function(){
                    updateCalculatedTotal();
                    applyOverrideToBasis();
                }, 100); // allow other calculations to complete
            }
        });
    }
    
    if (miscContainer) {
        miscContainer.addEventListener('input', function() {
            if (enableOverrideCheckbox && enableOverrideCheckbox.checked) {
                setTimeout(function(){
                    updateCalculatedTotal();
                    applyOverrideToBasis();
                }, 100);
            }
        });
    }
    
    // Validation for override total
    if (overrideTotalInput) {
        overrideTotalInput.addEventListener('input', function() {
            const value = parseFloat(this.value);
            if (isNaN(value) || value < 0) {
                this.setCustomValidity('Please enter a valid positive number');
            } else {
                this.setCustomValidity('');
            }
            if (enableOverrideCheckbox && enableOverrideCheckbox.checked) {
                updateCalculatedTotal();
                applyOverrideToBasis();
            }
        });
        
        overrideTotalInput.addEventListener('blur', function() {
            if (this.value && !isNaN(parseFloat(this.value))) {
                this.value = parseFloat(this.value).toFixed(2);
                if (enableOverrideCheckbox && enableOverrideCheckbox.checked) {
                    applyOverrideToBasis();
                }
            }
        });
    }
    
    // Initial calculation if override is already enabled
    if (enableOverrideCheckbox && enableOverrideCheckbox.checked) {
        setManipulatorsDisabled(true);
        updateCalculatedTotal();
        applyOverrideToBasis();
    }
});
</script>


