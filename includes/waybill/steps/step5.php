<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="bg-white p-6">
    <?= KIT_Commons::prettyHeading([
        'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
        'words' => 'Waybill Details'
    ]) ?>
    <p class="text-xs text-gray-600 mb-6">
        Add charge details for the waybill.
    </p>

    <!-- Waybill section -->
    <div id="waybill-sections-container" class="space-y-6">
        <?php 
        $waybill_index = 0;
        require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/waybillSection.php'); 
        ?>
    </div>

    <!-- Navigation Buttons -->
    <div class="flex justify-between mt-8">
        <?php echo KIT_Commons::renderButton('Back', 'secondary', 'lg', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />',
            'iconPosition' => 'left',
            'data-target' => 'step-2',
            'classes' => 'prev-step'
        ]); ?>
        <?php echo KIT_Commons::renderButton('Next: Miscellaneous Items', 'primary', 'lg', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />',
            'iconPosition' => 'right',
            'data-target' => 'step-4',
            'classes' => 'next-step',
            'gradient' => true
        ]); ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure global waybillCount exists but do NOT reset an existing value
    if (typeof window.waybillCount === 'undefined') {
        window.waybillCount = 1;
    }

    /**
     * Step 5 "different city" handling for parcel waybills.
     *
     * Requirements:
     * - NEVER touch the global delivery selection from Step 4:
     *   #direction_id and #selected_delivery_id must remain unchanged.
     * - Per‑waybill city changes only update that waybill's hidden
     *   destination_country / destination_city / delivery_id / direction_id
     *   fields, which the backend reads from $_POST['waybills'][index].
     */
    try {
        const mainDestinationCountry =
            document.getElementById('stepDestinationSelect')?.value ||
            document.getElementById('destination_country_backup')?.value ||
            '';

        const mainDestinationCitySelect = document.getElementById('destination_city');

        /**
         * Populate a per‑waybill city <select> without triggering any of the
         * global country->deliveries logic used in Step 4.
         * This intentionally does NOT call handleCountryChange() and does NOT
         * modify #direction_id or #selected_delivery_id.
         */
        function populateWaybillCitySelect(selectEl, countryId, selectedCityId) {
            if (!selectEl) {
                return;
            }

            // Clear existing options
            selectEl.innerHTML = '';
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select City';
            selectEl.appendChild(placeholder);

            const citiesMap = (window.myPluginAjax && window.myPluginAjax.countryCities) || {};
            const cities = citiesMap && citiesMap[String(countryId)] ? citiesMap[String(countryId)] : [];

            if (Array.isArray(cities) && cities.length) {
                cities.forEach(function(city) {
                    const opt = document.createElement('option');
                    opt.value = city.id;
                    opt.textContent = city.city_name;
                    if (selectedCityId && String(selectedCityId) === String(city.id)) {
                        opt.selected = true;
                    }
                    selectEl.appendChild(opt);
                });
                return;
            }

            // Fallback: clone options from the main destination_city select (Step 4)
            if (mainDestinationCitySelect && mainDestinationCitySelect.options.length > 0) {
                Array.prototype.forEach.call(mainDestinationCitySelect.options, function(optSrc) {
                    const opt = document.createElement('option');
                    opt.value = optSrc.value;
                    opt.textContent = optSrc.textContent;
                    if (selectedCityId && String(selectedCityId) === String(optSrc.value)) {
                        opt.selected = true;
                    }
                    selectEl.appendChild(opt);
                });
            }
        }

        // Wire up all "different city?" checkboxes / selects
        document.querySelectorAll('.waybill-section').forEach(function(sectionEl) {
            const index = sectionEl.getAttribute('data-waybill-index');
            if (index === null || typeof index === 'undefined') {
                return;
            }

            const differentCityCheckbox = document.getElementById('different_city_' + index);
            const dropdownWrapper = document.getElementById('different_city_dropdown_' + index);
            const citySelect = document.getElementById('waybill_destination_city_' + index);
            const hiddenCountry = document.getElementById('waybill_destination_country_' + index);
            const hiddenDeliveryId = document.getElementById('waybill_delivery_id_' + index);
            const hiddenDirectionId = document.getElementById('waybill_direction_id_' + index);

            if (!differentCityCheckbox || !dropdownWrapper || !citySelect || !hiddenCountry) {
                return;
            }

            // For parcel waybills we always start from the Step 4 delivery,
            // so copy its country / ids once and keep them stable.
            const globalDeliveryId = document.getElementById('selected_delivery_id')?.value || '';
            const globalDirectionId = document.getElementById('direction_id')?.value || '';

            if (hiddenCountry && !hiddenCountry.value && mainDestinationCountry) {
                hiddenCountry.value = mainDestinationCountry;
            }
            if (hiddenDeliveryId && !hiddenDeliveryId.value && globalDeliveryId) {
                hiddenDeliveryId.value = globalDeliveryId;
            }
            if (hiddenDirectionId && !hiddenDirectionId.value && globalDirectionId) {
                hiddenDirectionId.value = globalDirectionId;
            }

            function updateDifferentCityState() {
                const checked = !!differentCityCheckbox.checked;
                if (checked) {
                    dropdownWrapper.classList.remove('hidden');
                    dropdownWrapper.style.display = '';

                    // Ensure country is set for this waybill
                    if (hiddenCountry && !hiddenCountry.value && mainDestinationCountry) {
                        hiddenCountry.value = mainDestinationCountry;
                    }

                    // Populate city dropdown for this waybill (no side‑effects)
                    populateWaybillCitySelect(
                        citySelect,
                        hiddenCountry ? hiddenCountry.value : mainDestinationCountry,
                        citySelect.value || ''
                    );
                } else {
                    dropdownWrapper.classList.add('hidden');
                    dropdownWrapper.style.display = 'none';
                    // When toggled off, clear only this waybill's override city
                    if (citySelect) {
                        citySelect.value = '';
                    }
                }
            }

            differentCityCheckbox.addEventListener('change', updateDifferentCityState);

            // When user picks a city for this waybill, just store the value on that
            // select / hidden fields – do NOT call any global handlers.
            citySelect.addEventListener('change', function() {
                const selectedValue = citySelect.value || '';
                // Mirror into hidden destination_city for this waybill; backend
                // reads waybills[index][destination_city].
                if (selectedValue && hiddenCountry && !hiddenCountry.value && mainDestinationCountry) {
                    hiddenCountry.value = mainDestinationCountry;
                }

                // We deliberately do NOT touch #direction_id or #selected_delivery_id here.
            });

            // Initialise UI state on load
            updateDifferentCityState();
        });
    } catch (e) {
        // Fail-safe: log to console but never break the main waybill flow
        if (window.console && console.error) {
            console.error('Waybill Step 5 different-city initialisation error:', e);
        }
    }
});
</script>
