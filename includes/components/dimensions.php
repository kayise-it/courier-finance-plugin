<?php if (!defined('ABSPATH')) { exit; } ?>
<?php
// Include user roles for permission checking
require_once plugin_dir_path(__FILE__) . '../user-roles.php';

// Use passed waybill data or fall back to global
$waybill = isset($dimensions_waybill) ? $dimensions_waybill : (isset($GLOBALS['waybill']) ? $GLOBALS['waybill'] : null);

// Enqueue required scripts for this component
KIT_Commons::enqueueComponentScripts(['kitscript']);
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
            <?php
            // Support both array and object $waybill when prefilling
            $prefill_volume_charge = 0;
            if (isset($waybill)) {
                if (is_array($waybill) && isset($waybill['volume_charge'])) {
                    $prefill_volume_charge = $waybill['volume_charge'];
                } elseif (is_object($waybill) && isset($waybill->volume_charge)) {
                    $prefill_volume_charge = $waybill->volume_charge;
                }
            }
            ?>
            <?= KIT_Commons::Linput([
                'label' => 'Total Volume Charge (R)',
                'name'  => 'volume_charge',
                'id'  => 'volume_charge',
                'type'  => 'text',
                'value' => esc_attr($prefill_volume_charge),
                'class' => '',
                'special' => 'readonly',
            ]); ?>
        </div>
    </div>
    <!-- Dimension Manipulator (Admin only) -->
    <div class="mt-4">
        <?php
        // Prefill from stored snapshot if available
        $prefill_use_custom = false;
        $prefill_custom_rate = '';
        if (isset($waybill) && isset($waybill->miscellaneous) && !empty($waybill->miscellaneous)) {
            $__misc = maybe_unserialize($waybill->miscellaneous);
            if (is_array($__misc) && isset($__misc['others'])) {
                $prefill_use_custom = !empty($__misc['others']['use_custom_volume_rate']);
                if (isset($__misc['others']['custom_volume_rate_per_m3'])) {
                    $prefill_custom_rate = $__misc['others']['custom_volume_rate_per_m3'];
                }
            }
        }
        ?>
        <label class="inline-flex items-center">
            <input type="checkbox" id="enable_volume_price_manipulator" name="use_custom_volume_rate" class="form-checkbox h-4 w-4 text-blue-600" <?= $prefill_use_custom ? 'checked' : ''; ?>>
            <span class="ml-2 text-sm text-gray-700">Custom Volume Rate</span>
        </label>
        <div id="dimension_manipulator_input_container" style="display: <?= $prefill_use_custom ? 'block' : 'none'; ?>; margin-top: 1rem;">
            <?= KIT_Commons::Linput([
                'label' => 'Volume Rate Manipulator (R)',
                'name'  => 'custom_volume_rate_per_m3',
                'id'    => 'custom_volume_rate_per_m3',
                'type'  => 'number',
                'min'   => '0',
                'step'  => '0.01',
                'value' => esc_attr($prefill_custom_rate),
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 focus:ring-1 focus:ring-blue-500',
            ]); ?>
        </div>
    </div>
    <?php
    $prefill_volume_rate_display = '0.00';
    if (isset($waybill)) {
        if (is_array($waybill) && isset($waybill['volume_charge'])) {
            $prefill_volume_rate_display = $waybill['volume_charge'];
        } elseif (is_object($waybill) && isset($waybill->volume_charge)) {
            $prefill_volume_rate_display = $waybill->volume_charge;
        }
    }
    ?>
    <div id="ttt" class="text-sm text-gray-700 col-span-2">
        = R<span id="volume_charge_display"><?= htmlspecialchars($prefill_volume_rate_display); ?></span> per m3
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
            // Manipulator controls (unique IDs/names for volume)
            manipCheckbox: document.getElementById('enable_volume_price_manipulator'),
            manipInput: document.getElementById('custom_volume_rate_per_m3'),
            manipInputContainer: document.getElementById('dimension_manipulator_input_container')
        };

        let ajaxAbortController = null;
        let debounceTimer = null;
        let baseRate = null; // for manipulation
        let lastBaseRate = null; // to restore when unchecking manipulator

        function validateDimension(value) {
            // Handle both comma and dot decimal separators
            const normalizedValue = String(value).replace(',', '.');
            const num = parseFloat(normalizedValue);
            return !isNaN(num) && num > 0 ? num : null;
        }

        function calculateVolume(immediate = false) {
            const length = validateDimension(elements.lengthInput.value);
            const width = validateDimension(elements.widthInput.value);
            const height = validateDimension(elements.heightInput.value);

            // If any dimension is invalid/missing, do NOT overwrite existing
            // total_volume/volume_charge. This prevents charge-basis changes from
            // wiping a previously calculated volume.
            const allEmpty = (
                (elements.lengthInput.value === '' || elements.lengthInput.value === null) &&
                (elements.widthInput.value === '' || elements.widthInput.value === null) &&
                (elements.heightInput.value === '' || elements.heightInput.value === null)
            );
            if ([length, width, height].some(val => val === null)) {
                // Do not clear here. This function is also triggered by charge-basis
                // changes; if all fields are empty, we want to preserve any existing
                // prefilled volume/charge values. Actual clearing is handled in the
                // dimension input handler when the user empties the fields.
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
            // Show spinner on all dimension inputs using ComponentUtils
            elements.inputs.forEach(input => ComponentUtils.showSpinner(input));

            // Abort previous request if it exists
            if (ajaxAbortController) {
                ajaxAbortController.abort();
            }
            ajaxAbortController = new AbortController();

            // Use ComponentUtils for AJAX call
            ComponentUtils.ajaxCall('handle_get_price_per_m3', {
                'origin_country_id': elements.countrySelect?.value || '',
                'total_volume_m3': volume
            }, function(data) {
                // Handle response
                if (data.success) {
                    const rate = parseFloat(data.data.rate_per_m3);
                    baseRate = rate;
                    lastBaseRate = rate;
                    updateVolumeChargeUI(volume, rate);
                } else {
                    console.error('Server reported error:', data.data.message);
                    clearForm();
                }
                
                // Hide spinners
                elements.inputs.forEach(input => ComponentUtils.hideSpinner(input));
            });
        }

        function updateVolumeChargeUI(volume, fetchedRate) {
            let rate = fetchedRate;
            // If manipulator is enabled, add manip value
            if (elements.manipCheckbox && elements.manipCheckbox.checked) {
                // Handle both comma and dot decimal separators
                const normalizedManipValue = String(elements.manipInput.value).replace(',', '.');
                const manip = parseFloat(normalizedManipValue) || 0;
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
                
                // Normalize the manipulator input value (handle comma/dot separators)
                const normalizedManipValue = String(this.value).replace(',', '.');
                this.value = normalizedManipValue;
                // Prevent negative values
                const parsed = parseFloat(this.value);
                if (!isNaN(parsed) && parsed < 0) {
                    this.value = '0';
                }
                
                const length = validateDimension(elements.lengthInput.value);
                const width = validateDimension(elements.widthInput.value);
                const height = validateDimension(elements.heightInput.value);
                if ([length, width, height].some(val => val === null) || baseRate === null) return;
                const volume = (length * width * height) / CUBIC_CM_TO_CUBIC_M;
                updateVolumeChargeUI(volume, baseRate);
            });

            // If editing and value exists, show input and check the box
            <?php if (isset($prefill_use_custom) && $prefill_use_custom) : ?>
            elements.manipCheckbox.checked = true;
            elements.manipInputContainer.style.display = 'block';
            <?php endif; ?>
        }

        // Set up event listeners
        elements.inputs.forEach(input => {
            input.addEventListener('input', function() {
                // Normalize comma decimal separators to dots for consistent parsing
                const normalizedValue = String(this.value).replace(',', '.');
                if (normalizedValue !== this.value) {
                    this.value = normalizedValue;
                }

                // If user cleared all fields, explicitly clear computed volume/charge
                const l = elements.lengthInput.value;
                const w = elements.widthInput.value;
                const h = elements.heightInput.value;
                if ((l === '' || l === null) && (w === '' || w === null) && (h === '' || h === null)) {
                    clearForm();
                    return;
                }

                calculateVolume(false);
            });
            input.addEventListener('blur', () => calculateVolume(true));
        });

        // Re-evaluate volume when charge basis changes so choosing 'volume' doesn't
        // accidentally zero-out fields
        // Detach charge-basis from dimension recalculation to avoid unintended clears
        // (preferred select no longer affects calculation choice)

        // Initial calculation
        calculateVolume(true);
    });
</script>