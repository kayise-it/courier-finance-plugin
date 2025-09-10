<?php if (!defined('ABSPATH')) {
    exit;
} ?>
<div class="space-y-6 mb-6">
    <div class="rounded bg-slate-100 p-6">
        <?= KIT_Commons::prettyHeading([
            'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
            'words' => 'Mass'
        ]) ?>
        <hr>
        <!-- Row 1: Total Mass -->
        <div class="grid grid-cols-1 gap-4 justify-center align-middle">
            <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/weight.php'); ?>
        </div>
    </div>

    <!-- Row 2: Length, Width, Height, Total Volume -->
    <div class="rounded bg-slate-100 p-6">
        <?= KIT_Commons::prettyHeading([
            'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
            'words' => 'Volume'
        ]) ?>
        <hr>
        <p class="text-sm text-gray-600 mb-4"><strong>Standard Volume (m³)</strong> = (Length × Width × Height) ÷ 1,000,000</p>
        <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/dimensions.php'); ?>
    </div>
</div>