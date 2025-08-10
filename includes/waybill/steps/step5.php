<!-- Review Content -->
<div class="space-y-8">
    <!-- Dimensions and Volume Calculator -->
    <div class="bg-gray-50 rounded-lg p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Shipment Dimensions</h3>
        <div class="space-y-4">
            <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/dimandvol.php'); ?>
        </div>
    </div>

    <!-- Charge Comparison Cards -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Shipping Charges</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <!-- Mass Charge Card -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200" id="mass-charge-card">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-700">Mass-based</h4>
                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">Per KG</span>
                </div>
                <div class="text-xl font-bold text-gray-900 mb-1">
                    R <span id="mass_rate_display">0.00</span>
                </div>
                <div class="text-sm text-gray-500">
                    <span id="total_mass_display">0.0</span> kg → R <span id="mass_charge_total">0.00</span>
                </div>
                <input type="hidden" id="mass_charge" name="mass_charge" value="0">
                <input type="hidden" id="mass_rate" name="mass_rate" value="0">
            </div>

            <!-- Volume Charge Card -->
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow duration-200" id="volume-charge-card">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-700">Volume-based</h4>
                    <span class="px-2 py-1 text-xs font-medium bg-gray-100 text-gray-600 rounded-full">Per m³</span>
                </div>
                <div class="text-xl font-bold text-gray-900 mb-1">
                    R <span id="volume_rate_display">0.00</span>
                </div>
                <div class="text-sm text-gray-500">
                    <span id="total_volume_display">0.0</span> m³ → R <span id="volume_charge_total">0.00</span>
                </div>
                <input type="hidden" id="volume_charge" name="volume_charge" value="0">
                <input type="hidden" id="volume_rate" name="volume_rate" value="0">
            </div>
        </div>
        
        <!-- Auto-selected indicator -->
        <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-sm font-medium text-blue-900">Auto-selected:</span> 
                <span id="charge-basis-indicator" class="text-sm text-blue-700 font-medium">Waiting for calculations...</span>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Additional Items (Full Width) -->
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center space-x-3 mb-6">
            <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Additional Items</h3>
                <p class="text-sm text-gray-600">Add extra charges, fees, or services to this waybill</p>
            </div>
        </div>
        
        <?php
        echo KIT_Commons::miscItemsControl([
            'container_id' => 'misc-items',
            'button_id' => 'add-misc-item',
            'group_name' => 'misc',
            'input_class' => 'text-sm',
            'existing_items' => []
        ]);
        ?>

        <!-- Existing misc items -->
        <div id="misc-items-container" class="space-y-4 mt-6">
            <?php
            if (!empty($quotation->miscellaneous)) {
                $misc_items = json_decode($quotation->miscellaneous, true);
                if (is_array($misc_items)) {
                    foreach ($misc_items as $index => $item) {
                        echo '<div class="misc-item flex gap-4 items-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <input type="text" name="misc_item[]" value="' . esc_attr($item['name']) . '" placeholder="Item description" class="flex-2 text-sm px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <input type="number" name="misc_price[]" value="' . esc_attr($item['price']) . '" placeholder="Amount" class="flex-1 text-sm px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <button type="button" class="remove-misc-btn bg-red-500 text-white p-3 rounded-lg hover:bg-red-600 transition-colors text-sm font-medium">Remove</button>
                            </div>';
                    }
                }
            }
            ?>
        </div>

        <!-- Helper Text -->
        <div class="mt-6 p-4 bg-orange-50 border border-orange-200 rounded-lg">
            <div class="flex items-start space-x-3">
                <svg class="w-5 h-5 text-orange-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="text-sm font-medium text-orange-900">Additional Items</p>
                    <p class="text-sm text-orange-700">These are extra charges like insurance, handling fees, or special services - separate from the main waybill items.</p>
                </div>
            </div>
        </div>
    </div>
</div>