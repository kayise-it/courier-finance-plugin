<?php if (!defined('ABSPATH')) {
    exit;
} ?>
<?php
// Include user roles for permission checking
require_once plugin_dir_path(__FILE__) . '../user-roles.php';

// Use passed waybill data or fall back to global
$waybill = isset($dimensions_waybill) ? $dimensions_waybill : (isset($GLOBALS['waybill']) ? $GLOBALS['waybill'] : null);

// Enqueue required scripts for this component
KIT_Commons::enqueueComponentScripts(['kitscript']);
?>
<div class="">
    <?php echo KIT_Commons::prettyHeading([
        'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
        'words' => 'Volume',
        'classes' => 'mb-6'
    ]); ?>
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
            $prefill_volume_rate_used = null;
            
            // Handle both array and object waybill formats
            $misc_data = null;
            if (isset($waybill)) {
                if (is_array($waybill) && isset($waybill['miscellaneous']) && !empty($waybill['miscellaneous'])) {
                    $misc_data = maybe_unserialize($waybill['miscellaneous']);
                } elseif (is_object($waybill) && isset($waybill->miscellaneous) && !empty($waybill->miscellaneous)) {
                    $misc_data = maybe_unserialize($waybill->miscellaneous);
                }
            }
            
            if (is_array($misc_data) && isset($misc_data['others'])) {
                $prefill_use_custom = !empty($misc_data['others']['use_custom_volume_rate']);
                if (isset($misc_data['others']['custom_volume_rate_per_m3'])) {
                    $prefill_custom_rate = $misc_data['others']['custom_volume_rate_per_m3'];
                }
                if (isset($misc_data['others']['volume_rate_used'])) {
                    $prefill_volume_rate_used = floatval($misc_data['others']['volume_rate_used']);
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
        // Get the rate per m³ from miscellaneous data, not the total charge
        $prefill_volume_rate_display = '0.00';
        if ($prefill_volume_rate_used !== null && $prefill_volume_rate_used > 0) {
            $prefill_volume_rate_display = number_format($prefill_volume_rate_used, 2, '.', '');
        } elseif (isset($waybill)) {
            // Fallback: calculate rate from volume_charge / total_volume if available
            $volume_charge = 0;
            $total_volume = 0;
            if (is_array($waybill)) {
                $volume_charge = floatval($waybill['volume_charge'] ?? 0);
                $total_volume = floatval($waybill['total_volume'] ?? 0);
            } elseif (is_object($waybill)) {
                $volume_charge = floatval($waybill->volume_charge ?? 0);
                $total_volume = floatval($waybill->total_volume ?? 0);
            }
            if ($total_volume > 0 && $volume_charge > 0) {
                $prefill_volume_rate_display = number_format($volume_charge / $total_volume, 2, '.', '');
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
        // Initialize with stored rate from database if available (for editing existing waybills)
        // But we'll always fetch from table when direction_id or volume changes
        let baseRate = <?php echo $prefill_volume_rate_used !== null && $prefill_volume_rate_used > 0 ? $prefill_volume_rate_used : 'null'; ?>;
        let lastBaseRate = <?php echo $prefill_volume_rate_used !== null && $prefill_volume_rate_used > 0 ? $prefill_volume_rate_used : 'null'; ?>;
        let lastDirectionId = null; // Track direction_id changes

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
            
            // CRITICAL: Don't overwrite saved volume if we have a saved volume_charge
            // This preserves the database values when editing existing waybills
            const hasSavedVolumeCharge = elements.volumeCharge && parseFloat(elements.volumeCharge.value.replace(',', '.')) > 0;
            if (elements.volumeField && !hasSavedVolumeCharge) {
                elements.volumeField.value = volume.toFixed(6);
            }

            // Check if direction_id has changed - if so, always fetch from table
            const directionField = document.getElementById('direction_id') || document.querySelector('input[name="direction_id"]');
            const currentDirectionId = directionField ? directionField.value : '';
            const directionChanged = lastDirectionId !== null && lastDirectionId !== currentDirectionId;
            
            // Always fetch from table if:
            // 1. No stored rate exists
            // 2. Direction_id changed (rates may differ by direction)
            // 3. Volume changed significantly (might be in different tier)
            const shouldFetchFromTable = baseRate === null || baseRate <= 0 || directionChanged;
            
            if (shouldFetchFromTable) {
                // Always fetch from wp_kit_shipping_rates_volume table using direction_id
                if (immediate) {
                    fetchVolumeRate(volume);
                } else {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(() => fetchVolumeRate(volume), DEBOUNCE_DELAY_MS);
                }
                lastDirectionId = currentDirectionId;
            } else {
                // Use stored rate only if direction hasn't changed
                updateVolumeChargeUI(volume, baseRate);
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

            // Get direction_id - this is critical for querying wp_kit_shipping_rates_volume table
            const directionField = document.getElementById('direction_id') || document.querySelector('input[name="direction_id"]');
            const directionId = directionField ? directionField.value : '';

            if (!directionId) {
                console.warn('dimensions.php: direction_id is required to fetch volume rate from table');
                elements.inputs.forEach(input => ComponentUtils.hideSpinner(input));
                return;
            }

            // Fetch from wp_kit_shipping_rates_volume table using direction_id
            // The handler queries: WHERE direction_id = %d AND volume BETWEEN min_volume AND max_volume
            ComponentUtils.ajaxCall('handle_get_price_per_m3', {
                'origin_country_id': elements.countrySelect?.value || '',
                'direction_id': directionId, // Required: used to query kit_shipping_rates_volume table
                'total_volume_m3': volume
            }, function(data) {
                // Handle response
                if (data.success) {
                    const rate = parseFloat(data.data.rate_per_m3);
                    baseRate = rate;
                    lastBaseRate = rate;
                    lastDirectionId = directionId; // Update tracked direction_id
                    updateVolumeChargeUI(volume, rate);
                } else {
                    console.error('Server reported error:', data.data.message);
                    // If fetch fails, fall back to stored rate if available
                    if (baseRate !== null && baseRate > 0) {
                        updateVolumeChargeUI(volume, baseRate);
                    } else {
                        clearForm();
                    }
                }

                // Hide spinners
                elements.inputs.forEach(input => ComponentUtils.hideSpinner(input));
            });
        }

        function updateVolumeChargeUI(volume, fetchedRate) {
            // CRITICAL: Don't overwrite saved volume_charge if it exists
            // This preserves database values when editing existing waybills
            const savedVolumeCharge = elements.volumeCharge ? parseFloat(elements.volumeCharge.value.replace(',', '.')) || 0 : 0;
            const savedVolume = elements.volumeField ? parseFloat(elements.volumeField.value.replace(',', '.')) || 0 : 0;
            
            if (savedVolumeCharge > 0 && savedVolume > 0) {
                // Use saved volume from database, not calculated volume
                const savedRate = savedVolumeCharge / savedVolume;
                if (elements.volumeChargeDisplay) {
                    elements.volumeChargeDisplay.textContent = savedRate.toFixed(2);
                }
                return; // Don't update the volume_charge field - preserve saved value
            }
            
            let rate = fetchedRate;
            // If custom rate is enabled, use it as the full rate (not an addition)
            if (elements.manipCheckbox && elements.manipCheckbox.checked && elements.manipInput) {
                // Handle both comma and dot decimal separators
                const normalizedManipValue = String(elements.manipInput.value).replace(',', '.');
                const customRate = parseFloat(normalizedManipValue);
                if (!isNaN(customRate) && customRate > 0) {
                    // Use custom rate as full replacement rate
                    rate = customRate;
                } else {
                    // If no custom rate value, fall back to base rate
                    rate = baseRate !== null ? baseRate : fetchedRate;
                }
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
                // Allow negative values for discounts/credits
                const parsed = parseFloat(this.value);
                if (isNaN(parsed)) {
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

        // Listen for direction_id changes - re-fetch rate from table when direction changes
        const directionField = document.getElementById('direction_id') || document.querySelector('input[name="direction_id"]');
        if (directionField) {
            directionField.addEventListener('change', function() {
                // When direction_id changes, we need to fetch new rate from wp_kit_shipping_rates_volume table
                const length = validateDimension(elements.lengthInput.value);
                const width = validateDimension(elements.widthInput.value);
                const height = validateDimension(elements.heightInput.value);
                
                if (length && width && height) {
                    const volume = (length * width * height) / CUBIC_CM_TO_CUBIC_M;
                    // Reset baseRate to force fetch from table
                    baseRate = null;
                    fetchVolumeRate(volume);
                }
            });
            // Initialize lastDirectionId on page load
            lastDirectionId = directionField.value || null;
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

        // Initial calculation - only if dimensions are filled
        const hasDimensions = elements.lengthInput.value && elements.widthInput.value && elements.heightInput.value;
        
        // Check if we're editing an existing waybill with saved volume_charge
        // CRITICAL: Don't recalculate if we have a saved volume_charge from the database
        const volumeChargeField = elements.volumeCharge;
        const savedVolumeCharge = volumeChargeField ? parseFloat(volumeChargeField.value.replace(',', '.')) || 0 : 0;
        const volumeField = elements.volumeField;
        const savedVolume = volumeField ? parseFloat(volumeField.value.replace(',', '.')) || 0 : 0;
        
        // If we have saved volume_charge and volume, preserve them (don't recalculate)
        if (savedVolumeCharge > 0 && savedVolume > 0) {
            // Calculate the saved rate from saved volume_charge / saved volume
            const savedRate = savedVolumeCharge / savedVolume;
            
            // Only set baseRate if it's not already set from miscellaneous data
            if (baseRate === null || baseRate <= 0) {
                baseRate = savedRate;
                lastBaseRate = savedRate;
            }
            
            // Update UI with saved rate display
            if (elements.volumeChargeDisplay) {
                elements.volumeChargeDisplay.textContent = savedRate.toFixed(2);
            }
            
            // Don't recalculate - preserve the saved volume_charge
            // The volumeCharge field already has the saved value from PHP
            console.log('Preserving saved volume_charge:', savedVolumeCharge, 'with volume:', savedVolume, 'rate:', savedRate);
        } else if (hasDimensions) {
            // Only recalculate if dimensions are filled and we don't have saved values
            calculateVolume(true);
        } else if (baseRate !== null && baseRate > 0 && elements.volumeField && elements.volumeField.value) {
            // If we have a stored rate and volume, calculate charge without fetching
            const volume = parseFloat(elements.volumeField.value.replace(',', '.')) || 0;
            if (volume > 0) {
                updateVolumeChargeUI(volume, baseRate);
            }
        }
    });
</script>