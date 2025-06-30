<div class="space-y-6 mb-6">
    <div class="rounded bg-slate-100 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Item Dimensions</h3>
        <hr>
        <!-- Row 1: Total Mass -->
        <div class="grid grid-cols-1 gap-4 justify-center align-middle">
            <div class="flex items-center space-x-4">
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => "Total Mass (Kg)",
                        'name'  => "total_mass_kg",
                        'id'    => "total_mass_kg",
                        'type'  => 'number',
                        'value' => esc_attr($waybill->total_mass_kg ?? null),
                        'class' => '',
                        'special' => '',
                    ]); ?>
                </div>
                <div id="ttt" class="text-sm text-gray-700">
                    = R<span id="mass_charge_display"><?= esc_html($waybill->mass_charge ?? '0.00'); ?></span> per Kilo
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Length, Width, Height, Total Volume -->
    <div class="rounded bg-slate-100 p-6 mb-6">
        <p class="text-sm text-gray-600 mb-4"><strong>Standard Volume (m³)</strong> = (Length × Width × Height) ÷ 1,000,000</p>
        <div class="md:grid md:grid-cols-4 md:gap-4 mb-4">
            <div class="grid grid-cols-3 gap-4 mb-4 col-span-3">
                <?php foreach (
                    [
                        'item_length' => 'Length (cm)',
                        'item_width' => 'Width (cm)',
                        'item_height' => 'Height (cm)',
                    ] as $field => $label
                ): ?>
                    <div>
                        <?= KIT_Commons::Linput([
                            'label' => $label,
                            'name'  => esc_attr($field),
                            'id'  => esc_attr($field),
                            'type'  => 'number',
                            'value' => esc_attr($waybill->$field ?? null),
                            'class' => 'dimension-input w-[50px]',
                            'special' => '',
                        ]); ?>
                    </div>
                <?php endforeach; ?>
                <!-- Auto-calculated Volume -->
            </div>
            <div>
                <?= KIT_Commons::Linput([
                    'label' => "Total Volume (m³)",
                    'name'  => 'total_volume',
                    'id'  => 'total_volume',
                    'type'  => 'number',
                    'value' => esc_attr($waybill->total_volume ?? null),
                    'class' => '',
                    'special' => 'readonly',
                ]); ?>
            </div>
            <div id="ttt" class="text-sm text-gray-700 col-span-2">
                = R<span id="volume_charge_display"><?= esc_html($waybill->volume_charge ?? '0.00'); ?></span> per m3
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const inputs = document.querySelectorAll('.dimension-input');
        const volumeField = document.getElementById('total_volume');
        const volumeCharge = document.getElementById('volume_charge'); // Optional display element
        const volumeChargedisplay = document.getElementById('volume_charge_display'); // Optional display element

        const directionId = 1; // Replace with dynamic value if needed

        function calculateVolume() {
            const length = parseFloat(document.querySelector('input[name="item_length"]').value) || 0;
            const width = parseFloat(document.querySelector('input[name="item_width"]').value) || 0;
            const height = parseFloat(document.querySelector('input[name="item_height"]').value) || 0;

            const volume = (length * width * height) / 1000000;

            if (!isNaN(volume) && volume > 0) {
                volumeField.value = volume.toFixed(4);

                // Run AJAX to get rate per m3
                jQuery.ajax({
                    url: myPluginAjax.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'handle_get_price_per_m3',
                        direction_id: directionId,
                        total_volume_m3: volume,
                        nonce: myPluginAjax.nonces.get_waybills_nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            const rate = response.data.rate_per_m3;
                            console.log('Rate per m³: R' + rate);
                            if (volumeCharge) {
                                volumeChargedisplay.textContent = rate;
                                volumeCharge.value = rate * volume; // or .textContent if it's a div/span
                            }
                        } else {
                            console.warn(response.data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                    }
                });
            } else {
                volumeField.value = '';
                if (volumeCharge) volumeCharge.value = '';
            }
        }

        inputs.forEach(input => {
            input.addEventListener('input', calculateVolume);
        });

        calculateVolume();


        jQuery('#total_mass_kg').on('input', function() {
            const total_mass_kg = parseFloat(jQuery(this).val()) || 0;
            const direction_id = jQuery('input[name="direction_id"]').val(); // Assuming you have a hidden input for direction_id

            if (total_mass_kg > 0) {
                jQuery.ajax({
                    url: myPluginAjax.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'handle_get_price_per_kg',
                        total_mass_kg: total_mass_kg,
                        direction_id: direction_id,
                        nonce: myPluginAjax.nonces.get_waybills_nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            const rate = response.data.rate_per_kg;
                            console.log('Rate per kg: R' + rate);
                            // Optionally set this in your DOM:
                            jQuery('#mass_charge_display').text(rate);

                            jQuery('#mass_charge').val(rate * total_mass_kg);


                        } else {
                            console.warn(response.data.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                    }
                });
            }
        });
    });
</script>