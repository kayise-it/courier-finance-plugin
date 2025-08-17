<?php if (!defined('ABSPATH')) { exit; } ?>
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
    <div id="ttt" class="text-sm text-gray-700 col-span-2">
        = R<span id="volume_charge_display"><?= esc_html($waybill->volume_charge ?? '0.00'); ?></span> per m3
    </div>
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
            countrySelect: document.getElementById('countrydestination_id')
        };

        let ajaxAbortController = null;
        let debounceTimer = null;

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
            elements.volumeField.value = volume.toFixed(6);

            if (immediate) {
                fetchVolumeRate(volume);
            } else {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => fetchVolumeRate(volume), DEBOUNCE_DELAY_MS);
            }
        }

        function clearForm() {
            elements.volumeField.value = '';
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
                        const rate = data.data.rate_per_m3;
                        if (elements.volumeChargeDisplay) {
                            elements.volumeChargeDisplay.textContent = rate;
                        }
                        if (elements.volumeCharge) {
                            elements.volumeCharge.value = (rate * volume).toFixed(2);
                        }
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

        // Set up event listeners
        elements.inputs.forEach(input => {
            input.addEventListener('input', () => calculateVolume(false));
            input.addEventListener('blur', () => calculateVolume(true));
        });

        // Initial calculation
        calculateVolume(true);
    });
</script>