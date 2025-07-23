<div class="space-y-6 mb-6">
    <div class="rounded bg-slate-100 p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Item Dimensions</h3>
        <hr>
        <!-- Row 1: Total Mass -->
        <div class="grid grid-cols-1 gap-4 justify-center align-middle">
            <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/weight.php'); ?>
        </div>
    </div>

    <!-- Row 2: Length, Width, Height, Total Volume -->
    <div class="rounded bg-slate-100 p-6">
        <p class="text-sm text-gray-600 mb-4"><strong>Standard Volume (m³)</strong> = (Length × Width × Height) ÷ 1,000,000</p>
        <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/dimensions.php'); ?>
    </div>
</div>