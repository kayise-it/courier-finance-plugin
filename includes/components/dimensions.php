<!-- Dimension Inputs -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <?php
    $dimensions = [
        ['field' => 'item_length', 'label' => 'Length (cm)', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
        ['field' => 'item_width',  'label' => 'Width (cm)', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
        ['field' => 'item_height', 'label' => 'Height (cm)', 'icon' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z'],
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
                'value' => esc_attr($waybill[$field] ?? ''),
                'class' => 'dimension-input w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent text-sm',
                'special' => 'placeholder="0" step="0.01"',
            ]); ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- Volume Results -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
    <div>
        <?= KIT_Commons::Linput([
            'label' => "Total Volume (m³)",
            'name'  => 'total_volume',
            'id'  => 'total_volume',
            'type'  => 'number',
            'value' => esc_attr($waybill['total_volume'] ?? ''),
            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg bg-purple-50 text-purple-900 font-medium text-sm',
            'special' => 'readonly placeholder="Auto-calculated"',
        ]); ?>
    </div>
    <div>
        <?= KIT_Commons::Linput([
            'label' => 'Volume Charge (R)',
            'name'  => 'volume_charge',
            'id'  => 'volume_charge',
            'type'  => 'text',
            'value' => esc_attr($waybill->volume_charge ?? '0.00'),
            'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg bg-purple-50 text-purple-900 font-medium text-sm',
            'special' => 'readonly placeholder="Auto-calculated"',
        ]); ?>
    </div>
</div>

<!-- Rate Display -->
<div class="p-3 bg-purple-50 border border-purple-200 rounded-lg">
    <div class="flex items-center justify-between">
        <span class="text-sm font-medium text-purple-900">Rate per m³:</span>
        <span class="text-sm font-bold text-purple-900">R <span id="volume_charge_display"><?= esc_html($waybill->volume_charge ?? '0.00'); ?></span></span>
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