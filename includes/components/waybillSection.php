<?php
/**
 * Repeatable Waybill Section Component
 * Combines Delivery/Destination (step 4) and Charges & Fees (step 5)
 * 
 * @param int $waybill_index The index of this waybill (0, 1, 2, etc.)
 */
if (!defined('ABSPATH')) {
    exit;
}

$waybill_index = $waybill_index ?? 0;
$is_first = $waybill_index === 0;

// Set global waybill_index for child components to use
$GLOBALS['current_waybill_index'] = $waybill_index;
?>
<div class="waybill-section border-2 border-gray-300 rounded-lg p-6 bg-white mb-6" data-waybill-index="<?php echo esc_attr($waybill_index); ?>">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Waybill #<?php echo esc_html($waybill_index + 1); ?></h2>
        <?php if (!$is_first): ?>
            <button type="button" class="remove-waybill-section bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                Remove Waybill
            </button>
        <?php endif; ?>
    </div>

    <!-- Different city option (hidden for first waybill, shown for subsequent waybills) -->
    <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded-lg" id="different_city_section_<?php echo $waybill_index; ?>" style="<?php echo $is_first ? 'display: none;' : ''; ?>">
        <label class="flex items-center cursor-pointer mb-3">
            <input type="checkbox" 
                   name="waybills[<?php echo $waybill_index; ?>][different_city]" 
                   id="different_city_<?php echo $waybill_index; ?>"
                   class="different-city-checkbox mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
            <span class="text-sm font-medium text-gray-700">Different city?</span>
        </label>
        
        <!-- City dropdown (shown when "Different city?" is checked) -->
        <div id="different_city_dropdown_<?php echo $waybill_index; ?>" class="hidden mt-3">
            <label for="waybill_destination_city_<?php echo $waybill_index; ?>" class="<?= KIT_Commons::labelClass() ?>">Destination City</label>
            <select class="<?= KIT_Commons::selectClass(); ?>" 
                    name="waybills[<?php echo $waybill_index; ?>][destination_city]" 
                    id="waybill_destination_city_<?php echo $waybill_index; ?>">
                <option value="">Select City</option>
            </select>
            <p class="text-xs text-gray-500 mt-1">Select a different destination city for this waybill.</p>
        </div>
        
        <input type="hidden" name="waybills[<?php echo $waybill_index; ?>][destination_country]" id="waybill_destination_country_<?php echo $waybill_index; ?>" value="" />
        <input type="hidden" name="waybills[<?php echo $waybill_index; ?>][delivery_id]" id="waybill_delivery_id_<?php echo $waybill_index; ?>" value="" />
        <input type="hidden" name="waybills[<?php echo $waybill_index; ?>][direction_id]" id="waybill_direction_id_<?php echo $waybill_index; ?>" value="" />
    </div>

    <!-- Step 5 Content: Charges & Fees -->
    <div class="border-t border-gray-300 pt-6">
        <?= KIT_Commons::prettyHeading([
            'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
            'words' => 'Charges & Fees'
        ]) ?>
        
        <div class="grid md:grid-cols-5 gap-4 mt-4">
            <div class="md:col-span-3 rounded-lg">
                <!-- Dimensions and Volume -->
                <div class="space-y-6 mb-6">
                    <div class="rounded bg-slate-100 p-6">
                        <div class="grid grid-cols-1 gap-4 justify-center align-middle">
                            <?php 
                            // Weight component - includes its own "Mass" heading
                            require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/weight.php'); 
                            ?>
                        </div>
                    </div>

                    <div class="rounded bg-slate-100 p-6">
                        <?php 
                        // Dimensions component - includes its own "Volume" heading
                        require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/dimensions.php'); 
                        ?>
                        <p class="text-sm text-gray-600 mb-4"><strong>Standard Volume (m³)</strong> = (Length × Width × Height) ÷ 1,000,000</p>
                    </div>
                </div>
            </div>

            <div class="md:col-span-2 space-y-6 mb-6">
                <!-- Total Override -->
                <div class="p-6 rounded-lg bg-slate-100">
                    <div id="totalOverride-<?php echo $waybill_index; ?>">
                        <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/totalOverride.php'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

