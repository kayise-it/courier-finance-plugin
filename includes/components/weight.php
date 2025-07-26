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
                        'value' =>  $massRate,
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-500',
                    ]);
                    ?>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const checkbox = document.getElementById('enable_price_manipulator');
                        const inputContainer = document.getElementById('price_manipulator_input_container');
                        if (checkbox && inputContainer) {
                            checkbox.addEventListener('change', function() {
                                inputContainer.style.display = this.checked ? 'block' : 'none';
                            });
                            // If editing and value exists, show input and check the box
                            <?php if (!empty($waybill->price_manipulator)) : ?>
                                checkbox.checked = true;
                                inputContainer.style.display = 'block';
                            <?php endif; ?>
                        }
                    });
                </script>
            </div>


        </div>
    </div>
</div>