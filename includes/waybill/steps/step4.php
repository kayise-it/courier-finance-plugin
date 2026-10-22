<?php
if (! defined('ABSPATH')) {
    exit;
}

$is_portal = function_exists('kit_using_employee_portal') && kit_using_employee_portal();
$destination_country_id = isset($_POST['destination_country']) ? intval($_POST['destination_country']) : 0;
$destination_city_id    = isset($_POST['destination_city']) ? intval($_POST['destination_city']) : 0;
$selected_country       = $destination_country_id > 0 ? $destination_country_id : null;

function displayWarehouseOrSelectDelivery()
{
    // Same size as Back / Next on this step (commons lg).
    $mode_active_classes = trim(
        KIT_Commons::buttonClass() . ' ' .
        KIT_Commons::buttonPrimary('lg', false, true, false) .
        ' kit-btn px-6 kit-step4-mode-btn'
    );
    $mode_inactive_classes = trim(
        KIT_Commons::buttonClass() . ' ' .
        KIT_Commons::buttonSecondary('lg', false, false, true) .
        ' kit-btn px-6 kit-step4-mode-btn'
    );

    ob_start(); ?>
    <div class="kit-step4-mode-wrap w-full flex flex-col items-end text-right"
         role="group"
         aria-label="Delivery mode"
         data-mode-active-class="<?php echo esc_attr($mode_active_classes); ?>"
         data-mode-inactive-class="<?php echo esc_attr($mode_inactive_classes); ?>">
        <span class="<?= esc_attr(KIT_Commons::labelClass()); ?>">Select Delivery</span>
        <div class="inline-flex flex-wrap justify-end gap-2">
            <?php
            echo KIT_Commons::renderButton('Warehouse', 'primary', 'lg', [
                'id'        => 'kit-step4-mode-warehouse',
                'type'      => 'button',
                'classes'   => 'kit-step4-mode-btn',
                'noLoading' => true,
                'gradient'  => true,
                'plain'     => false,
            ]);
            echo KIT_Commons::renderButton('Truck', 'secondary', 'lg', [
                'id'        => 'kit-step4-mode-truck',
                'type'      => 'button',
                'classes'   => 'kit-step4-mode-btn',
                'noLoading' => true,
                'plain'     => true,
            ]);
            ?>
        </div>
        <p id="kit-step4-truck-hint" class="hidden text-right text-xs text-gray-500">
            <span id="kit-step4-truck-count-dup">0</span> trucks available
        </p>
    </div>
    <?php
    return ob_get_clean();
}
?>
<div class="bg-white p-6 space-y-5">
    <?php
    // Backend expects `pending` == 1 for warehouse storage; JS uses #pending_option.
    ?>
    <input type="checkbox" id="pending_option" name="pending" value="1" class="sr-only" checked="checked" tabindex="-1" aria-hidden="true" />

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0 flex-1">
            <h3 class="text-lg font-semibold text-gray-900">Delivery &amp; Destination</h3>
            <?php if (empty($GLOBALS['kit_has_scheduled_deliveries'])) : ?>
                <p class="text-xs text-gray-600 mt-1">
                    No trucks are scheduled. This waybill will be stored in the <strong class="font-medium">warehouse</strong> until it is assigned to a delivery. You do not need to create a truck first.
                </p>
            <?php else : ?>
                <p class="text-xs text-gray-600 mt-1">
                    Choose destination country and city. In <strong class="font-medium">Truck</strong> mode, pick a scheduled delivery to assign the route.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($is_portal) : ?>
        <?php
        $countries = class_exists('KIT_Deliveries') ? KIT_Deliveries::getCountriesObject() : [];
        ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div id="waybill-form-country" data-select-class="<?= esc_attr(KIT_Commons::selectClass()); ?>">
                <label for="stepDestinationSelect" class="<?= KIT_Commons::labelClass() ?>">Destination Country</label>
                <div id="kit-destination-country-mount"></div>
            </div>
            <div>
                <label for="destination_city" class="<?= KIT_Commons::labelClass() ?>">Destination City</label>
                <div id="destinationWrap" data-select-class="<?= esc_attr(KIT_Commons::selectClass()); ?>"></div>
            </div>
            <div class="flex justify-end">
                <?= displayWarehouseOrSelectDelivery(); ?>
            </div>
        </div>
        <script type="application/json" id="kit-step4-countries"><?php echo json_encode($countries); ?></script>
        <script>
        (function() {
            var mount = document.getElementById('kit-destination-country-mount');
            var wrap = document.getElementById('destinationWrap');
            var dataEl = document.getElementById('kit-step4-countries');
            var countries = dataEl ? (function(){ try { return JSON.parse(dataEl.textContent); } catch (e) { return []; } })() : [];
            var selClass = (document.getElementById('waybill-form-country') || {}).dataset && document.getElementById('waybill-form-country').dataset.selectClass;
            selClass = selClass || <?php echo wp_json_encode(KIT_Commons::selectClass()); ?>;
            function run() {
                if (!mount || mount.querySelector('select')) return;
                var countrySelect = document.createElement('select');
                countrySelect.id = 'stepDestinationSelect';
                countrySelect.name = 'destination_country';
                countrySelect.className = selClass;
                countrySelect.required = true;
                countrySelect.addEventListener('change', function() {
                    var v = this.value;
                    if (typeof handleCountryChange === 'function') handleCountryChange(v, 'destination_country');
                    var back = document.getElementById('destination_country_backup');
                    if (back) back.value = v || '';
                });
                var o0 = document.createElement('option');
                o0.value = '';
                o0.textContent = 'Select Country';
                countrySelect.appendChild(o0);
                countries.forEach(function(c) {
                    var o = document.createElement('option');
                    o.value = c.id;
                    o.textContent = (c && c.country_name) ? c.country_name : '';
                    countrySelect.appendChild(o);
                });
                mount.appendChild(countrySelect);
                if (wrap && !wrap.querySelector('select')) {
                    var citySelect = document.createElement('select');
                    citySelect.id = 'destination_city';
                    citySelect.name = 'destination_city';
                    citySelect.className = (wrap.dataset && wrap.dataset.selectClass) || selClass;
                    citySelect.required = true;
                    var co = document.createElement('option');
                    co.value = '';
                    co.textContent = 'Select City';
                    citySelect.appendChild(co);
                    wrap.appendChild(citySelect);
                }
                if (typeof window.kitStep4SyncDestinationRequired === 'function') {
                    window.kitStep4SyncDestinationRequired();
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() { setTimeout(run, 150); });
            } else {
                setTimeout(run, 150);
            }
        })();
        </script>
    <?php else : ?>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
            <div>
                <label for="stepDestinationSelect" class="<?= KIT_Commons::labelClass() ?>">Destination Country</label>
                <?php echo KIT_Deliveries::CountrySelect('destination_country', 'stepDestinationSelect', $selected_country, true, false); ?>
            </div>
            <div>
                <label for="destination_city" class="<?= KIT_Commons::labelClass() ?>">Destination City</label>
                <?php echo KIT_Deliveries::selectAllCitiesByCountry('destination_city', 'destination_city', $destination_country_id, $destination_city_id, 'required'); ?>
            </div>
            <div class="flex justify-end">
                <?= displayWarehouseOrSelectDelivery(); ?>
            </div>
        </div>
    <?php endif; ?>

    <div id="displayDeliveryID" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg" style="display: none;">
        <span class="font-medium text-blue-900">Delivery ID: </span>
        <span id="delivery_id_display" class="font-bold text-blue-700"></span>
    </div>
    <input type="hidden" name="delivery_id" id="selected_delivery_id" value="" />
    <?php
    $waybill_ctx_delivery = isset($atts['delivery_id']) ? (int) $atts['delivery_id'] : 0;
    if ($waybill_ctx_delivery <= 0) :
        ?>
        <input type="hidden" name="direction_id" id="direction_id" value="" />
        <?php
    endif;
    ?>
    <input type="hidden" name="destination_country_backup" id="destination_country_backup" value="" />

    <div id="kit-step4-truck-panel" class="hidden">
        <?php require COURIER_FINANCE_PLUGIN_PATH . 'includes/components/scheduledDeliveries.php'; ?>
    </div>

    <div class="flex justify-between mt-8 pt-4 border-t border-gray-100">
        <?php echo KIT_Commons::renderButton('Back', 'secondary', 'lg', [
            'icon'         => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />',
            'iconPosition' => 'left',
            'data-target'  => 'step-1',
            'classes'      => 'prev-step',
        ]); ?>
        <?php echo KIT_Commons::renderButton('Next: Charges & Fees', 'primary', 'lg', [
            'id'           => 'step4NextBtn',
            'disabled'     => true,
            'icon'         => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />',
            'iconPosition' => 'right',
            'data-target'  => 'step-3',
            'classes'      => 'next-step',
            'gradient'     => true,
        ]); ?>
    </div>

    <script>
    (function () {
        function syncTruckCountDup() {
            var src = document.getElementById('delivery-count');
            var dup = document.getElementById('kit-step4-truck-count-dup');
            if (src && dup) {
                dup.textContent = src.textContent.trim() || '0';
            }
        }

        function setDestinationHtml5Required(warehouse) {
            var c = document.getElementById('stepDestinationSelect');
            var city = document.getElementById('destination_city');
            if (c) {
                if (warehouse) {
                    c.removeAttribute('required');
                } else {
                    c.setAttribute('required', 'required');
                }
            }
            if (city) {
                if (warehouse) {
                    city.removeAttribute('required');
                } else {
                    city.setAttribute('required', 'required');
                }
            }
        }

        function applyCommonsModeClasses(btn, active, wrap) {
            if (!btn || !wrap) {
                return;
            }
            var activeCls = wrap.getAttribute('data-mode-active-class') || '';
            var inactiveCls = wrap.getAttribute('data-mode-inactive-class') || '';
            btn.className = active ? activeCls : inactiveCls;
            btn.setAttribute('aria-pressed', active ? 'true' : 'false');
        }

        function initStep4DeliveryMode() {
            var warehouseBtn = document.getElementById('kit-step4-mode-warehouse');
            var truckBtn = document.getElementById('kit-step4-mode-truck');
            var pending = document.getElementById('pending_option');
            var truckPanel = document.getElementById('kit-step4-truck-panel');
            var truckHint = document.getElementById('kit-step4-truck-hint');
            var modeWrap = warehouseBtn ? warehouseBtn.closest('.kit-step4-mode-wrap') : null;
            if (!warehouseBtn || !truckBtn || !pending) {
                return;
            }

            function setMode(warehouse) {
                pending.checked = !!warehouse;
                if (warehouse) {
                    var directionIdField = document.getElementById('direction_id');
                    if (directionIdField && (!directionIdField.value || directionIdField.value === '0')) {
                        directionIdField.value = '1';
                    }
                }
                if (truckPanel) {
                    truckPanel.classList.toggle('hidden', !!warehouse);
                }
                if (truckHint) {
                    truckHint.classList.toggle('hidden', !!warehouse);
                }
                applyCommonsModeClasses(warehouseBtn, !!warehouse, modeWrap);
                applyCommonsModeClasses(truckBtn, !warehouse, modeWrap);
                setDestinationHtml5Required(!!warehouse);
                if (!warehouse) {
                    syncTruckCountDup();
                }
                pending.dispatchEvent(new Event('change', { bubbles: true }));
            }

            warehouseBtn.addEventListener('click', function () {
                setMode(true);
            });
            truckBtn.addEventListener('click', function () {
                setMode(false);
            });

            // Default: warehouse (panel hidden, pending checked).
            setMode(true);
        }

        window.kitStep4SyncDestinationRequired = function () {
            var pending = document.getElementById('pending_option');
            setDestinationHtml5Required(!!(pending && pending.checked));
        };

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initStep4DeliveryMode);
        } else {
            initStep4DeliveryMode();
        }
        setTimeout(function () {
            window.kitStep4SyncDestinationRequired();
        }, 400);
    })();
    </script>
</div>
