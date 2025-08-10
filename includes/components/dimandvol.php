<!-- Weight & Mass Calculation -->
<div class="mb-8">
    <div class="flex items-center space-x-3 mb-4">
        <div class="w-6 h-6 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-3 h-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16l-3-9m3 9l3-9" />
            </svg>
        </div>
        <h4 class="text-base font-semibold text-gray-900">Weight & Mass</h4>
    </div>
    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/weight.php'); ?>
</div>

<!-- Volume Calculation -->
<div>
    <div class="flex items-center space-x-3 mb-4">
        <div class="w-6 h-6 bg-purple-100 rounded-lg flex items-center justify-center">
            <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
        </div>
        <div>
            <h4 class="text-base font-semibold text-gray-900">Volume Calculation</h4>
            <p class="text-sm text-gray-600">Standard Volume (m³) = (Length × Width × Height) ÷ 1,000,000</p>
        </div>
    </div>
    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/dimensions.php'); ?>
</div>