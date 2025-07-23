    <div>
        <h3 class="text-lg font-medium text-gray-700 mb-3">Price Manipulator</h3>

        <div>
            <label class="inline-flex items-center">
                <input type="checkbox" id="enable_price_manipulator" name="enable_price_manipulator" class="form-checkbox h-4 w-4 text-blue-600">
                <span class="ml-2 text-sm text-gray-700">Enable Price Manipulator</span>
            </label>
        </div>
        <div id="price_manipulator_input_container" style="display: none; margin-top: 1rem;">
            <?php
            echo KIT_Commons::Linput([
                'label' => 'Mass Charge Manipulator (R)',
                'name'  => 'mass_charge_manipulator',
                'id'    => 'mass_charge_manipulator',
                'type'  => 'number',
                'step'  => '0',
                'value' => isset($waybill->mass_charge_manipulator) ? esc_attr($waybill->mass_charge_manipulator) : '',
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-500',
            ]);

            echo KIT_Commons::Linput([
                'label' => 'Volume Charge Manipulator (R)',
                'name'  => 'volume_charge_manipulator',
                'id'    => 'volume_charge_manipulator',
                'type'  => 'number',
                'step'  => '0',
                'value' => isset($waybill->volume_charge_manipulator) ? esc_attr($waybill->volume_charge_manipulator) : '',
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

    <script>
        document.addEventListener("DOMContentLoaded", function() {

            let current_rate = 0;
            const ogMass = jQuery('#total_mass_kg').val();
            const ogRate = jQuery('#mass_rate').val();

            jQuery('#enable_price_manipulator').on('input', function() {
                if (this.checked) {
                    /* When mass_charge_manipulator is changed, then alert the value after 2 seconds */
                    jQuery('#mass_charge_manipulator').on('input', function() {
                        current_rate = jQuery('#mass_rate').val();

                        

                        const manipulatorVal = parseFloat(jQuery('#mass_charge_manipulator').val()) || 0;
                        const rateVal = parseFloat(current_rate) || 0;
                        const sum = rateVal + manipulatorVal;
                        jQuery('#manipulated_mass_charge_display').text('+ R' + manipulatorVal + ' = ' + sum);

                        console.log('current_rate: ' + current_rate);
                        console.log('rateVal: ' + rateVal);
                        console.log('manipulatorVal: ' + manipulatorVal);
                        console.log('sum: ' + sum);

                        /* Now update the #mass_charge where we take now the new value 'sum' and multiply it by the total_mass_kg */
                        const total_mass_kg = parseFloat(jQuery('#total_mass_kg').val()) || 0;
                        const new_mass_charge = sum * total_mass_kg;
                        jQuery('#mass_charge').val(new_mass_charge);
                        jQuery('#mass_rate').val(sum);

                        // Below #mass_rate, show a <p> with font size 7px displaying the sum of rateVal + manipulatorVal
                        // Remove any previous sum display to avoid duplicates
                        jQuery('#mass_rate').next('.sum-rate-display').remove();
                        jQuery('<p>')
                            .addClass('sum-rate-display')
                            .css('font-size', '7px')
                            .text('Sum (' + rateVal + ' + ' + manipulatorVal + '): ' + sum)
                            .insertAfter('#mass_rate');

                        // Take this new new_mass_charge and add create a hidden input called new_mass_rate to send via POST
                        // If the input doesn't exist, create it; otherwise, update its value
                        if (jQuery('#new_mass_rate').length === 0) {
                            jQuery('<input>').attr({
                                type: 'hidden',
                                id: 'new_mass_rate',
                                name: 'new_mass_rate',
                                value: sum
                            }).appendTo('form');
                        } else {
                            jQuery('#new_mass_rate').val(sum);
                        }

                    });
                } else {
                    jQuery('#manipulated_mass_charge_display').text('');
                    jQuery('#mass_charge_manipulator').val('');
                    fetchRatePerKg();
                    jQuery('#mass_charge_display').text();
                }
            });
        });
    </script>