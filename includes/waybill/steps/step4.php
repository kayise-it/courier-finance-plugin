<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="bg-white p-6">
<?= KIT_Commons::prettyHeading([
                    'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
        'words' => 'Delivery & Destination'
                ]) ?>
    <p class="text-xs text-gray-600 mb-6">
        Select the delivery destination for this waybill. This will set the direction_id.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div>
            <?php
            $is_portal = function_exists('kit_using_employee_portal') && kit_using_employee_portal();
            if ($is_portal) {
                // Portal: no <select> in initial HTML so the theme cannot duplicate it.
                // We inject a single native select via JS after the theme has run.
                $countries = class_exists('KIT_Deliveries') ? KIT_Deliveries::getCountriesObject() : [];
                ?>
                <div class="<?= KIT_Commons::yspacingClass(); ?>" id="waybill-form-country" data-select-class="<?= esc_attr(KIT_Commons::selectClass()); ?>">
                    <label for="stepDestinationSelect" class="<?= KIT_Commons::labelClass() ?>">Destination Country</label>
                    <div id="kit-destination-country-mount"></div>
                </div>
                <script type="application/json" id="kit-step4-countries"><?php echo json_encode($countries); ?></script>
            <?php } else {
                require COURIER_FINANCE_PLUGIN_PATH . 'includes/components/countriesSelect.php';
            } ?>
            <p class="text-xs text-gray-500 mt-1" id="destination-country-help">Required for non-pending items</p>
        </div>
        <div class="<?= KIT_Commons::yspacingClass(); ?>">
            <label for="destination_city" class="<?= KIT_Commons::labelClass() ?>">Destination City</label>
            <div id="destinationWrap" data-select-class="<?= esc_attr(KIT_Commons::selectClass()); ?>">
                <?php
                if (!$is_portal) {
                    $destination_country_id = isset($_POST['destination_country']) ? intval($_POST['destination_country']) : 0;
                    $destination_city_id = isset($_POST['destination_city']) ? intval($_POST['destination_city']) : 0;
                    echo KIT_Deliveries::selectAllCitiesByCountry('destination_city', 'destination_city', $destination_country_id, $destination_city_id, 'required');
                }
                ?>
            </div>
            <p class="text-xs text-gray-500 mt-1" id="destination-city-help">Required for non-pending items</p>
        </div>
        <div class="<?= KIT_Commons::yspacingClass(); ?>">
            <label for="pending_option" class="<?= KIT_Commons::labelClass(); ?>">Warehoused
                <input type="checkbox" name="pending" id="pending_option" value="1" class="mr-2">
            </label>
            <p class="text-xs text-gray-500 mt-1">Check this if the item is to be pending (no destination required)</p>
        </div>
    </div>

    <div id="displayDeliveryID" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg" style="display: none;">
        <span class="font-medium text-blue-900">Delivery ID: </span>
        <span id="delivery_id_display" class="font-bold text-blue-700"></span>
    </div>
    <!-- Hidden field to persist the selected delivery id across steps/submission -->
    <input type="hidden" name="delivery_id" id="selected_delivery_id" value="" />
    <input type="hidden" name="direction_id" id="direction_id" value="" />
    <!-- Backup hidden field for destination_country to ensure it's always submitted -->
    <input type="hidden" name="destination_country_backup" id="destination_country_backup" value="" />

    <!-- Scheduled Deliveries Container -->
    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/scheduledDeliveries.php'); ?>
    
    <!-- Navigation Buttons -->
    <div class="flex justify-between mt-8">
        <?php echo KIT_Commons::renderButton('Back', 'secondary', 'lg', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />',
            'iconPosition' => 'left',
            'data-target' => 'step-1',
            'classes' => 'prev-step'
        ]); ?>
        <?php echo KIT_Commons::renderButton('Next: Charges & Fees', 'primary', 'lg', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />',
            'iconPosition' => 'right',
            'data-target' => 'step-3',
            'classes' => 'next-step',
            'gradient' => true
        ]); ?>
    </div>
    <?php if ($is_portal): ?>
    <script>
    (function() {
        var mountCountry = document.getElementById('kit-destination-country-mount');
        if (!mountCountry) return;
        var dataEl = document.getElementById('kit-step4-countries');
        var countries = dataEl ? (function(){ try { return JSON.parse(dataEl.textContent); } catch(e) { return []; } })() : [];
        var selectClass = (document.getElementById('waybill-form-country') || {}).dataset && document.getElementById('waybill-form-country').dataset.selectClass || 'w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
        function run() {
            if (mountCountry.querySelector('select')) return;
            var countrySelect = document.createElement('select');
            countrySelect.id = 'stepDestinationSelect';
            countrySelect.name = 'destination_country';
            countrySelect.className = selectClass;
            countrySelect.required = true;
            countrySelect.addEventListener('change', function() {
                var v = this.value;
                if (typeof handleCountryChange === 'function') handleCountryChange(v, 'destination_country');
                var back = document.getElementById('destination_country_backup');
                if (back) back.value = v || '';
            });
            var opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = 'Select Country';
            countrySelect.appendChild(opt0);
            countries.forEach(function(c) {
                var o = document.createElement('option');
                o.value = c.id;
                o.textContent = (c && c.country_name) ? c.country_name : '';
                countrySelect.appendChild(o);
            });
            mountCountry.appendChild(countrySelect);
            var cityWrap = document.getElementById('destinationWrap');
            if (cityWrap && !cityWrap.querySelector('select')) {
                var citySelect = document.createElement('select');
                citySelect.id = 'destination_city';
                citySelect.name = 'destination_city';
                citySelect.className = (cityWrap.dataset && cityWrap.dataset.selectClass) || selectClass;
                citySelect.required = true;
                var co = document.createElement('option');
                co.value = '';
                co.textContent = 'Select City';
                citySelect.appendChild(co);
                cityWrap.appendChild(citySelect);
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() { setTimeout(run, 150); });
        } else {
            setTimeout(run, 150);
        }
    })();
    </script>
    <?php endif; ?>
</div>
