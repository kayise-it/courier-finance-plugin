<!-- Destination Selection -->
<div class="mb-8">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Country Selection -->
        <div>
            <?php require COURIER_FINANCE_PLUGIN_PATH . 'includes/components/countriesSelect.php'; ?>
        </div>
        
        <!-- City Selection -->
        <div>
            <label for="destination_city" class="block text-sm font-semibold text-gray-700 mb-2">Destination City</label>
            <div id="destinationWrap">
                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm" name="destination_city" id="destination_city">
                    <option value="">Select country first</option>
                </select>
            </div>
            <p class="mt-2 text-xs text-gray-500">City options will appear after selecting a country.</p>
        </div>
    </div>

    <!-- Warehoused Option -->
    <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
        <label class="flex items-start space-x-3 cursor-pointer">
            <input type="checkbox" name="warehoused" id="warehoused_option" value="1" class="mt-1 h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded">
            <div>
                <div class="text-sm font-semibold text-amber-900">Warehoused Shipment</div>
                <div class="text-sm text-amber-700">Check this if the shipment will be stored in our warehouse facility.</div>
            </div>
        </label>
    </div>
</div>

<!-- Scheduled Deliveries -->
<div class="mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Available Deliveries</h3>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="text-sm font-medium text-blue-900">Select a scheduled delivery</p>
                <p class="text-sm text-blue-700">Choose from available truck deliveries to your destination.</p>
            </div>
        </div>
    </div>
    
    <!-- Scheduled Deliveries Container -->
    <div id="scheduled-deliveries-wrapper" class="space-y-4">
        <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/scheduledDeliveries.php'); ?>
    </div>
    
    <!-- Empty State -->
    <div id="no-deliveries-message" class="hidden text-center py-12 text-gray-500">
        <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-lg font-medium text-gray-600 mb-2">No scheduled deliveries available</p>
        <p class="text-sm text-gray-400">Select a destination country to see available deliveries.</p>
    </div>
</div>