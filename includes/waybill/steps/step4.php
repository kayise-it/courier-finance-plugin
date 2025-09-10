<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="bg-white p-6">
<?= KIT_Commons::prettyHeading([
                    'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
                    'words' => 'Charges & Fees'
                ]) ?>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div>
            <?php require COURIER_FINANCE_PLUGIN_PATH . 'includes/components/countriesSelect.php'; ?>
            <p class="text-xs text-gray-500 mt-1" id="destination-country-help">Required for non-warehoused items</p>
        </div>
        <div class="<?= KIT_Commons::yspacingClass(); ?>">
            <label for="destination_city" class="<?= KIT_Commons::labelClass() ?>">Destination City</label>
            <div id="destinationWrap">
                <select class="<?= KIT_Commons::selectClass(); ?>" name="destination_city" id="destination_city">
                    <!-- Display the options here -->
                    <option value="">Select City</option>
                </select>
            </div>
            <p class="text-xs text-gray-500 mt-1" id="destination-city-help">Required for non-warehoused items</p>
        </div>
        <div class="<?= KIT_Commons::yspacingClass(); ?>">

            <label for="warehoused_option" class="<?= KIT_Commons::labelClass(); ?>">Warehoused
                <input type="checkbox" name="warehoused" id="warehoused_option" value="1" class="mr-2">
            </label>
            <p class="text-xs text-gray-500 mt-1">Check this if the item is to be warehoused (no destination required)</p>
        </div>
    </div>

    <!-- Scheduled Deliveries Container -->
    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/scheduledDeliveries.php'); ?>
    <div class="flex justify-between mt-8">
        <button type="button" class="md:hidden prev-step px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400" data-target="step-3">
            Back
        </button>
        <button type="button" class="hidden md:block prev-step px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400" data-target="step-3">
            Back
        </button>
        <?php echo KIT_Commons::renderButton('Next: Charges & Fees', 'secondary', 'md', [
            'id' => 'step4NextBtn',
            'disabled' => true,
            'data-target' => 'step-5',
            'classes' => 'next-step'
        ]); ?>
    </div>
</div>