<?php
// Initialize $waybill if not set (for new waybills)
if (!isset($waybill) || $waybill === null) {
    $waybill = [];
}

// Ensure $waybill is an array for compatibility with components
if (is_object($waybill)) {
    $waybill = (array) $waybill;
}

// Initialize required fields with defaults if not set
$waybill = array_merge([
    'total_mass_kg' => '',
    'mass_charge' => '0.00',
    'volume_charge' => '0.00',
    'total_volume' => '',
    'item_length' => '',
    'item_width' => '',
    'item_height' => '',
    'miscellaneous' => ['others' => ['mass_rate' => 0]]
], $waybill);

// Convert to object for compatibility with weight.php expectations
$waybill = (object) $waybill;
?>

<!-- Items Section -->
<div class="mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
        </svg>
        Waybill Items
    </h3>
    <?=
    KIT_Commons::waybillItemsControl([
        'container_id' => 'custom-waybill-items',
        'button_id' => 'add-waybill-item',
        'group_name' => 'custom_items',
        'existing_items' => [],
        'input_class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-sm transition-all duration-200',
        'remove_btn_class' => 'bg-red-500 text-white px-4 py-3 rounded-lg hover:bg-red-600 transition-colors text-sm font-medium flex items-center justify-center',
        'add_btn_class' => 'w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-4 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 text-sm font-medium flex items-center justify-center space-x-2',
        'label_class' => 'block text-sm font-medium text-gray-700 mb-2',
        'modern_style' => true,
    ]);
    ?>
</div>

<!-- Dimensions & Volume Section -->
<div class="mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4a1 1 0 011-1h4m0 0V1m0 2h2m-6 0h2m0 0v2m0 0H4m3 0V4m0 0h4m0 0v4m0-4h4a1 1 0 011 1v4M8 16v4a1 1 0 001 1h4m-4-1V18m0 2h2m-2 0v-2m0 0h2m0 0v2m0-2V16m0 0h4m-4 0V14m0 0H8m8 2h4a1 1 0 001-1v-4" />
        </svg>
        Package Dimensions & Volume
    </h3>
    <div class="bg-purple-50 rounded-lg p-6 border border-purple-200">
        <?php 
        // Ensure waybill array format for dimensions component
        $waybill = (array) $waybill;
        require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/dimensions.php'); 
        ?>
    </div>
</div>

<!-- Weight & Mass Calculation Section -->
<div class="mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16l3-1m-3 1l-3-1" />
        </svg>
        Weight & Shipping Charges
    </h3>
    <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
        <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/weight.php'); ?>
    </div>
</div>

<!-- Charge Summary -->
<div class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-lg p-6">
    <h4 class="text-lg font-semibold text-emerald-900 mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
        </svg>
        Shipping Charge Summary
    </h4>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white rounded-lg p-4 border border-emerald-200">
            <div class="text-sm text-emerald-700 mb-1">Mass-based Charge</div>
            <div class="text-lg font-bold text-emerald-900">R <span id="summary-mass-charge">0.00</span></div>
        </div>
        <div class="bg-white rounded-lg p-4 border border-emerald-200">
            <div class="text-sm text-emerald-700 mb-1">Volume-based Charge</div>
            <div class="text-lg font-bold text-emerald-900">R <span id="summary-volume-charge">0.00</span></div>
        </div>
    </div>
    <div class="mt-4 p-3 bg-emerald-100 rounded-lg border border-emerald-300">
        <div class="text-sm text-emerald-800">
            <strong>Note:</strong> The higher of the two charges (mass vs volume) will be used for shipping cost calculation.
        </div>
    </div>
    
    <!-- Validation Status Display -->
    <div id="step3-validation-status" class="mt-4 p-3 rounded-lg border hidden">
        <div class="text-sm font-medium" id="validation-status-text"></div>
        <div class="text-xs mt-1" id="validation-details"></div>
    </div>
</div>

<script>
// Ensure required objects exist
window.SpinnerManager = window.SpinnerManager || {
    show: function(element) {
        if (element) {
            element.style.opacity = '0.6';
            element.style.pointerEvents = 'none';
        }
    },
    hide: function(element) {
        if (element) {
            element.style.opacity = '1';
            element.style.pointerEvents = 'auto';
        }
    }
};

// Provide fallback AJAX object if not available
window.myPluginAjax = window.myPluginAjax || {
    ajax_url: '/wp-admin/admin-ajax.php',
    nonces: {
        get_waybills_nonce: 'fallback_nonce'
    }
};

document.addEventListener('DOMContentLoaded', function() {
    // Rate calculation functions based on business logic
    function calculateMassRate(mass) {
        if (mass <= 0) return 0;
        else if (mass < 10) return 40; // Small packages under 10kg use same rate as 10-500kg
        else if (mass <= 500) return 40;
        else if (mass <= 1000) return 35;
        else if (mass <= 2500) return 30;
        else if (mass <= 5000) return 25;
        else if (mass <= 7500) return 20;
        else if (mass <= 10000) return 17.5;
        else return 15;
    }
    
    function calculateVolumeRate(volume) {
        if (volume <= 1) return 7500;
        else if (volume <= 2) return 7000;
        else if (volume <= 5) return 6500;
        else if (volume <= 10) return 5500;
        else return 5000;
    }
    
    // Calculate mass charge with fallback
    function updateMassCalculation() {
        const massInput = document.getElementById('total_mass_kg');
        const rateInput = document.getElementById('mass_rate');
        const chargeInput = document.getElementById('mass_charge');
        
        if (!massInput || !rateInput || !chargeInput) return;
        
        const mass = parseFloat(massInput.value) || 0;
        
        // Always calculate the correct rate based on mass
        let rate = 0;
        if (mass > 0) {
            rate = calculateMassRate(mass);
            rateInput.value = rate.toFixed(2);
        } else {
            // If no mass, use existing rate or 0
            rate = parseFloat(rateInput.value) || 0;
        }
        
        const charge = mass * rate;
        chargeInput.value = charge.toFixed(2);
        
        updateChargeSummary();
    }
    
    // Calculate volume charge with fallback
    function updateVolumeCalculation() {
        const lengthInput = document.getElementById('item_length');
        const widthInput = document.getElementById('item_width');
        const heightInput = document.getElementById('item_height');
        const volumeInput = document.getElementById('total_volume');
        const chargeInput = document.getElementById('volume_charge');
        
        if (!lengthInput || !widthInput || !heightInput || !volumeInput || !chargeInput) return;
        
        const length = parseFloat(lengthInput.value) || 0;
        const width = parseFloat(widthInput.value) || 0;
        const height = parseFloat(heightInput.value) || 0;
        
        if (length > 0 && width > 0 && height > 0) {
            // Calculate volume in cubic meters
            const volume = (length * width * height) / 1000000;
            volumeInput.value = volume.toFixed(6);
            
            // Calculate charge using fallback rates
            const rate = calculateVolumeRate(volume);
            const charge = volume * rate;
            chargeInput.value = charge.toFixed(2);
            
            // Also update volume charge display if it exists
            const volumeChargeDisplay = document.getElementById('volume_charge_display');
            if (volumeChargeDisplay) {
                volumeChargeDisplay.textContent = rate.toFixed(2);
            }
        } else {
            volumeInput.value = '';
            chargeInput.value = '0.00';
        }
        
        updateChargeSummary();
    }
    
    // Update summary charges when mass/volume charges change
    function updateChargeSummary() {
        const massCharge = document.getElementById('mass_charge')?.value || '0.00';
        const volumeCharge = document.getElementById('volume_charge')?.value || '0.00';
        
        const summaryMass = document.getElementById('summary-mass-charge');
        const summaryVolume = document.getElementById('summary-volume-charge');
        
        if (summaryMass) summaryMass.textContent = parseFloat(massCharge).toFixed(2);
        if (summaryVolume) summaryVolume.textContent = parseFloat(volumeCharge).toFixed(2);
        
        // Also update the main sidebar if function is available
        if (typeof window.updateSummaryTotals === 'function') {
            window.updateSummaryTotals();
        }
        
        // Update validation status
        updateValidationStatus();
    }
    
    // Function to show validation status
    function updateValidationStatus() {
        const massCharge = parseFloat(document.getElementById('mass_charge')?.value || 0);
        const volumeCharge = parseFloat(document.getElementById('volume_charge')?.value || 0);
        const statusDiv = document.getElementById('step3-validation-status');
        const statusText = document.getElementById('validation-status-text');
        const statusDetails = document.getElementById('validation-details');
        
        if (!statusDiv || !statusText || !statusDetails) return;
        
        if (massCharge > 0 || volumeCharge > 0) {
            // Valid - show success
            statusDiv.className = 'mt-4 p-3 rounded-lg border bg-green-50 border-green-200';
            statusText.className = 'text-sm font-medium text-green-800';
            statusText.textContent = '✓ Step 3 Complete - Charges Calculated';
            statusDetails.className = 'text-xs mt-1 text-green-700';
            statusDetails.textContent = `Mass: R${massCharge.toFixed(2)}, Volume: R${volumeCharge.toFixed(2)}`;
            statusDiv.classList.remove('hidden');
        } else {
            // Invalid - show warning
            statusDiv.className = 'mt-4 p-3 rounded-lg border bg-amber-50 border-amber-200';
            statusText.className = 'text-sm font-medium text-amber-800';
            statusText.textContent = '⚠ Enter Weight OR Dimensions to Calculate Shipping Charges';
            statusDetails.className = 'text-xs mt-1 text-amber-700';
            statusDetails.textContent = 'Either enter the total weight or package dimensions to proceed.';
            statusDiv.classList.remove('hidden');
        }
    }
    
    // Attach event listeners
    const massInput = document.getElementById('total_mass_kg');
    const dimensionInputs = [
        document.getElementById('item_length'),
        document.getElementById('item_width'),
        document.getElementById('item_height')
    ];
    
    if (massInput) {
        massInput.addEventListener('input', updateMassCalculation);
        massInput.addEventListener('change', updateMassCalculation);
    }
    
    dimensionInputs.forEach(input => {
        if (input) {
            input.addEventListener('input', updateVolumeCalculation);
            input.addEventListener('change', updateVolumeCalculation);
        }
    });
    
    // Monitor charge inputs directly
    const massChargeInput = document.getElementById('mass_charge');
    const volumeChargeInput = document.getElementById('volume_charge');
    
    if (massChargeInput) {
        massChargeInput.addEventListener('input', updateChargeSummary);
        massChargeInput.addEventListener('change', updateChargeSummary);
    }
    
    if (volumeChargeInput) {
        volumeChargeInput.addEventListener('input', updateChargeSummary);
        volumeChargeInput.addEventListener('change', updateChargeSummary);
    }
    
    // Ensure minimum charge calculation
    function ensureMinimumCharges() {
        const massChargeInput = document.getElementById('mass_charge');
        const volumeChargeInput = document.getElementById('volume_charge');
        const massInput = document.getElementById('total_mass_kg');
        
        // Get current charges
        const massCharge = parseFloat(massChargeInput?.value || 0);
        const volumeCharge = parseFloat(volumeChargeInput?.value || 0);
        
        // If both charges are 0, set a default weight to get a charge
        if (massCharge <= 0 && volumeCharge <= 0) {
            if (massInput && (!massInput.value || parseFloat(massInput.value) === 0)) {
                massInput.value = '1'; // 1kg minimum
                updateMassCalculation();
            }
        }
        
        updateChargeSummary();
    }
    
    // Initial calculations
    setTimeout(() => {
        updateMassCalculation();
        updateVolumeCalculation();
        updateChargeSummary();
    }, 100);
    
    // Ensure charges before form submission
    const form = document.getElementById('progressive-waybill-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            ensureMinimumCharges();
        });
    }
});
</script>
