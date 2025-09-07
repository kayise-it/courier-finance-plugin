<?php
// Include user roles for permission checking
require_once plugin_dir_path(__FILE__) . '../user-roles.php';
?>
    <?php if (KIT_User_Roles::can_see_prices()): ?>
    <div>
        <h3 class="text-lg font-medium text-gray-700 mb-3">Volume</h3>

        <div>
            <label class="inline-flex items-center">
                <input type="checkbox" id="enable_volume_price_manipulator" name="enable_volume_price_manipulator" class="form-checkbox h-4 w-4 text-blue-600">
                <span class="ml-2 text-sm text-gray-700">Custom Pricing</span>
            </label>
        </div>
        <div id="volume_price_manipulator_input_container" style="display: none; margin-top: 1rem;">
            <?php
            echo KIT_Commons::Linput([
                'label' => 'Volume Rate Manipulator (R)',
                'name'  => 'volume_charge_manipulator',
                'id'    => 'volume_charge_manipulator',
                'type'  => 'number',
                'step'  => '0.01',
                'value' => isset($waybill->volume_charge_manipulator) ? esc_attr($waybill->volume_charge_manipulator) : '',
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-500',
            ]);
            ?>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const checkbox = document.getElementById('enable_volume_price_manipulator');
                const inputContainer = document.getElementById('volume_price_manipulator_input_container');
                if (checkbox && inputContainer) {
                    checkbox.addEventListener('change', function() {
                        inputContainer.style.display = this.checked ? 'block' : 'none';
                    });
                    // If editing and value exists, show input and check the box
                    <?php if (!empty($waybill->volume_charge_manipulator)) : ?>
                        checkbox.checked = true;
                        inputContainer.style.display = 'block';
                    <?php endif; ?>
                }
            });
        </script>
    </div>

    <script>
    // Custom pricing for volume: allow admin to add a manipulator to the volume rate
    document.addEventListener('DOMContentLoaded', function(){
        const volCheckbox = document.getElementById('enable_volume_price_manipulator');
        const manipInput = document.getElementById('volume_charge_manipulator');
        const totalVolInput = document.getElementById('total_volume');
        const volChargeInput = document.getElementById('volume_charge');
        const rateDisplay = document.getElementById('volume_charge_display');

        if (!volCheckbox || !manipInput || !totalVolInput || !volChargeInput || !rateDisplay) {
            return;
        }

        function parseNum(v){
            if (v === undefined || v === null) return 0;
            const s = (typeof v === 'string') ? v.replace(',', '.') : String(v);
            const num = parseFloat(s);
            return Number.isFinite(num) ? num : 0;
        }

        function getBaseRate(){
            const dataRate = parseNum(rateDisplay.dataset.baseRate);
            if (dataRate > 0) return dataRate;
            const txtRate = parseNum(rateDisplay.textContent || rateDisplay.innerText || '0');
            // Cache base for quick restore
            rateDisplay.dataset.baseRate = String(txtRate);
            return txtRate;
        }

        let rateAnchor = null; // anchored base rate while enabled

        function recalc(){
            const vol = parseNum(totalVolInput.value);
            const base = (volCheckbox.checked)
                ? (rateAnchor !== null ? rateAnchor : getBaseRate())
                : getBaseRate();
            const add = volCheckbox.checked ? parseNum(manipInput.value) : 0;
            const newRate = base + add;

            // Update UI
            if (volCheckbox.checked) {
                rateDisplay.textContent = newRate.toFixed(2);
            } else {
                rateDisplay.textContent = base.toFixed(2);
            }
            volChargeInput.value = (vol * (volCheckbox.checked ? newRate : base)).toFixed(2);

            // Add data attribute for custom pricing state (optional, for debugging/UI)
            volChargeInput.setAttribute('data-custom-pricing', volCheckbox.checked ? '1' : '0');
        }

        volCheckbox.addEventListener('change', function(){
            if (this.checked) {
                rateAnchor = getBaseRate();
            } else {
                // restore base visual and clear manip
                rateAnchor = null;
                manipInput.value = '';
            }
            recalc();
        });

        manipInput.addEventListener('input', recalc);
        totalVolInput.addEventListener('input', recalc);

        // If server fetch updates rate display, keep dataset fresh when disabled
        const mo = new MutationObserver(() => {
            if (!volCheckbox.checked) {
                // refresh cached base rate
                rateDisplay.dataset.baseRate = String(parseNum(rateDisplay.textContent || rateDisplay.innerText || '0'));
            }
            recalc();
        });
        mo.observe(rateDisplay, { childList: true, characterData: true, subtree: true });

        recalc();
    });
    </script>
    <?php endif; ?>
    <!-- No legacy mass logic here; volume totals/rates are handled in their own components. -->