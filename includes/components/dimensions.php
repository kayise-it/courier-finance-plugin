<?php if (!defined('ABSPATH')) { exit; } ?>
<?php
// Include user roles for permission checking
require_once plugin_dir_path(__FILE__) . '../user-roles.php';
?>
<div class="">
    <div class="grid grid-cols-3 gap-4 mb-4">
        <?php
        $dimensions = [
            ['field' => 'item_length', 'label' => 'Length (cm)'],
            ['field' => 'item_width',  'label' => 'Width (cm)'],
            ['field' => 'item_height', 'label' => 'Height (cm)'],
        ];

        foreach ($dimensions as $dim):
            $field = $dim['field'];
            $label = $dim['label'];
        ?>
            <div>
                <?= KIT_Commons::Linput([
                    'label' => $label,
                    'name'  => esc_attr($field),
                    'id'    => esc_attr($field),
                    'type'  => 'number',
                    'value' => esc_attr($waybill[$field] ?? null),
                    'class' => 'dimension-input w-[50px]',
                    'special' => '',
                ]); ?>
            </div>
        <?php endforeach; ?>

    </div>
    <?php if (KIT_User_Roles::can_see_prices()): ?>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <?= KIT_Commons::Linput([
                'label' => "Total Volume (m³)",
                'name'  => 'total_volume',
                'id'  => 'total_volume',
                'type'  => 'number',
                'value' => esc_attr($waybill['total_volume'] ?? null),
                'class' => 'bg-green-50',
                'special' => 'readonly',
            ]); ?>
            <!-- Auto-calculated Volume -->
        </div>
        <div>
            <?= KIT_Commons::Linput([
                'label' => 'Total Volume Charge (R)',
                'name'  => 'volume_charge',
                'id'  => 'volume_charge',
                'type'  => 'text',
                'value' => esc_attr($waybill->volume_charge ?? 0),
                'class' => '',
                'special' => 'readonly',
            ]); ?>
        </div>
    </div>
    <!-- Dimension Manipulator (Admin only) -->
    <div class="mt-4">
        <label class="inline-flex items-center">
            <input type="checkbox" id="enable_dimension_manipulator" name="enable_dimension_manipulator" class="form-checkbox h-4 w-4 text-blue-600">
            <span class="ml-2 text-sm text-gray-700">Custom Volume Rate</span>
        </label>
        <div id="dimension_manipulator_input_container" style="display: none; margin-top: 1rem;">
            <?= KIT_Commons::Linput([
                'label' => 'Volume Rate Manipulator (R)',
                'name'  => 'dimension_charge_manipulator',
                'id'    => 'dimension_charge_manipulator',
                'type'  => 'number',
                'step'  => '0.01',
                'value' => isset($waybill->dimension_charge_manipulator) ? esc_attr($waybill->dimension_charge_manipulator) : '',
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 focus:ring-1 focus:ring-blue-500',
            ]); ?>
        </div>
    </div>
    <div id="ttt" class="text-sm text-gray-700 col-span-2">
        = R<span id="volume_charge_display"><?= htmlspecialchars($waybill->volume_charge ?? '0.00'); ?></span> per m3
    </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const CUBIC_CM_TO_CUBIC_M = 1000000;
        const DEBOUNCE_DELAY_MS = 300;

        // Cache DOM elements
        const elements = {
            inputs: document.querySelectorAll('.dimension-input'),
            lengthInput: document.querySelector('input[name="item_length"]'),
            widthInput: document.querySelector('input[name="item_width"]'),
            heightInput: document.querySelector('input[name="item_height"]'),
            volumeField: document.getElementById('total_volume'),
            volumeCharge: document.getElementById('volume_charge'),
            volumeChargeDisplay: document.getElementById('volume_charge_display'),
            countrySelect: document.getElementById('countrydestination_id'),
            // Manipulator controls
            manipCheckbox: document.getElementById('enable_dimension_manipulator'),
            manipInput: document.getElementById('dimension_charge_manipulator'),
            manipInputContainer: document.getElementById('dimension_manipulator_input_container')
        };

        let ajaxAbortController = null;
        let debounceTimer = null;
        let baseRate = null; // for manipulation
        let lastBaseRate = null; // to restore when unchecking manipulator

        function validateDimension(value) {
            const num = parseFloat(value);
            return !isNaN(num) && num > 0 ? num : null;
        }

        function calculateVolume(immediate = false) {
            const length = validateDimension(elements.lengthInput.value);
            const width = validateDimension(elements.widthInput.value);
            const height = validateDimension(elements.heightInput.value);

            if ([length, width, height].some(val => val === null)) {
                clearForm();
                return;
            }

            const volume = (length * width * height) / CUBIC_CM_TO_CUBIC_M;
            if (elements.volumeField) {
                elements.volumeField.value = volume.toFixed(6);
            }

            if (immediate) {
                fetchVolumeRate(volume);
            } else {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => fetchVolumeRate(volume), DEBOUNCE_DELAY_MS);
            }
        }

        function clearForm() {
            if (elements.volumeField) elements.volumeField.value = '';
            if (elements.volumeCharge) elements.volumeCharge.value = '';
            if (elements.volumeChargeDisplay) elements.volumeChargeDisplay.textContent = '';
        }

        function fetchVolumeRate(volume) {
            // Show spinner on all dimension inputs
            elements.inputs.forEach(input => SpinnerManager.show(input));

            // Abort previous request if it exists
            if (ajaxAbortController) {
                ajaxAbortController.abort();
            }
            ajaxAbortController = new AbortController();

            // Prepare form data
            const formData = new FormData();
            formData.append('action', 'handle_get_price_per_m3');
            formData.append('origin_country_id', elements.countrySelect?.value || '');
            formData.append('total_volume_m3', volume);
            formData.append('nonce', myPluginAjax.nonces.get_waybills_nonce);

            fetch(myPluginAjax.ajax_url, {
                    method: 'POST',
                    signal: ajaxAbortController.signal,
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const rate = parseFloat(data.data.rate_per_m3);
                        baseRate = rate;
                        lastBaseRate = rate; // always update lastBaseRate to latest fetched
                        updateVolumeChargeUI(volume, rate);
                    } else {
                        console.error('Server reported error:', data.data.message);
                        clearForm();
                    }
                })
                .catch(error => {
                    if (error.name !== 'AbortError') {
                        console.error('Fetch error:', error);
                        clearForm();
                    }
                });
        }

        function updateVolumeChargeUI(volume, fetchedRate) {
            let rate = fetchedRate;
            // If manipulator is enabled, add manip value
            if (elements.manipCheckbox && elements.manipCheckbox.checked) {
                const manip = parseFloat(elements.manipInput.value) || 0;
                rate = (baseRate !== null ? baseRate : rate) + manip;
            }
            if (elements.volumeChargeDisplay) {
                elements.volumeChargeDisplay.textContent = rate.toFixed(2);
            }
            if (elements.volumeCharge) {
                elements.volumeCharge.value = (rate * volume).toFixed(2);
            }
        }

        // Manipulator logic
        if (elements.manipCheckbox && elements.manipInput && elements.manipInputContainer) {
            // Show/hide manipulator input
            elements.manipCheckbox.addEventListener('change', function() {
                elements.manipInputContainer.style.display = this.checked ? 'block' : 'none';
                if (!this.checked) {
                    elements.manipInput.value = '';
                    // Restore the rate to the last base rate before manipulation
                    const length = validateDimension(elements.lengthInput.value);
                    const width = validateDimension(elements.widthInput.value);
                    const height = validateDimension(elements.heightInput.value);
                    if ([length, width, height].some(val => val === null) || lastBaseRate === null) return;
                    const volume = (length * width * height) / CUBIC_CM_TO_CUBIC_M;
                    // Use lastBaseRate, not baseRate, to restore
                    if (elements.volumeChargeDisplay) {
                        elements.volumeChargeDisplay.textContent = lastBaseRate.toFixed(2);
                    }
                    if (elements.volumeCharge) {
                        elements.volumeCharge.value = (lastBaseRate * volume).toFixed(2);
                    }
                } else {
                    // When enabling, recalc with manipulator
                    const length = validateDimension(elements.lengthInput.value);
                    const width = validateDimension(elements.widthInput.value);
                    const height = validateDimension(elements.heightInput.value);
                    if ([length, width, height].some(val => val === null) || baseRate === null) return;
                    const volume = (length * width * height) / CUBIC_CM_TO_CUBIC_M;
                    updateVolumeChargeUI(volume, baseRate);
                }
            });

            // Manipulator input changes
            elements.manipInput.addEventListener('input', function() {
                if (!elements.manipCheckbox.checked) return;
                const length = validateDimension(elements.lengthInput.value);
                const width = validateDimension(elements.widthInput.value);
                const height = validateDimension(elements.heightInput.value);
                if ([length, width, height].some(val => val === null) || baseRate === null) return;
                const volume = (length * width * height) / CUBIC_CM_TO_CUBIC_M;
                updateVolumeChargeUI(volume, baseRate);
            });

            // If editing and value exists, show input and check the box
            <?php if (!empty($waybill->dimension_charge_manipulator)) : ?>
            elements.manipCheckbox.checked = true;
            elements.manipInputContainer.style.display = 'block';
            <?php endif; ?>
        }

        // Set up event listeners
        elements.inputs.forEach(input => {
            input.addEventListener('input', () => calculateVolume(false));
            input.addEventListener('blur', () => calculateVolume(true));
        });

        // Initial calculation
        calculateVolume(true);
    });
</script>