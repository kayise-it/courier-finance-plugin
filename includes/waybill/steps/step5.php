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
            'data-target' => 'step-4',
            'classes' => 'prev-step'
        ]); ?>
        <?php echo KIT_Commons::renderButton('Next: Miscellaneous Items', 'primary', 'lg', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />',
            'iconPosition' => 'right',
            'data-target' => 'step-6',
            'classes' => 'next-step',
            'gradient' => true
        ]); ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Set waybill count to 1 for step 3
    window.waybillCount = 1;
});
</script>
