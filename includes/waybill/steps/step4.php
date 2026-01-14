<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="bg-white p-6">
<?= KIT_Commons::prettyHeading([
                    'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
        'words' => 'Delivery & Destination'
                ]) ?>
    <p class="text-xs text-gray-600 mb-6">
        Select the delivery destination for this waybill. This will set the direction_id.
    </p>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div>
            <?php require COURIER_FINANCE_PLUGIN_PATH . 'includes/components/countriesSelect.php'; ?>
            <p class="text-xs text-gray-500 mt-1" id="destination-country-help">Required for non-pending items</p>
        </div>
        <div class="<?= KIT_Commons::yspacingClass(); ?>">
            <label for="destination_city" class="<?= KIT_Commons::labelClass() ?>">Destination City</label>
            <div id="destinationWrap">
                <select class="<?= KIT_Commons::selectClass(); ?>" name="destination_city" id="destination_city">
                    <!-- Display the options here -->
                    <option value="">Select City</option>
                </select>
            </div>
            <p class="text-xs text-gray-500 mt-1" id="destination-city-help">Required for non-pending items</p>
        </div>
        <div class="<?= KIT_Commons::yspacingClass(); ?>">
            <label for="pending_option" class="<?= KIT_Commons::labelClass(); ?>">Warehoused
                <input type="checkbox" name="pending" id="pending_option" value="1" class="mr-2">
            </label>
            <p class="text-xs text-gray-500 mt-1">Check this if the item is to be pending (no destination required)</p>
        </div>
    </div>

    <div id="displayDeliveryID" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg" style="display: none;">
        <span class="font-medium text-blue-900">Delivery ID: </span>
        <span id="delivery_id_display" class="font-bold text-blue-700"></span>
    </div>
    <!-- Hidden field to persist the selected delivery id across steps/submission -->
    <input type="hidden" name="delivery_id" id="selected_delivery_id" value="" />
    <input type="hidden" name="direction_id" id="direction_id" value="" />
    <!-- Backup hidden field for destination_country to ensure it's always submitted -->
    <input type="hidden" name="destination_country_backup" id="destination_country_backup" value="" />

    <!-- Scheduled Deliveries Container -->
    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/scheduledDeliveries.php'); ?>
    
    <!-- Navigation Buttons -->
    <div class="flex justify-between mt-8">
        <?php echo KIT_Commons::renderButton('Back', 'secondary', 'md', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />',
            'iconPosition' => 'left',
            'data-target' => 'step-1',
            'classes' => 'prev-step'
        ]); ?>
        <?php echo KIT_Commons::renderButton('Next: Charges & Fees', 'primary', 'md', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />',
            'iconPosition' => 'right',
            'data-target' => 'step-5',
            'classes' => 'next-step',
            'gradient' => true
        ]); ?>
    </div>
</div>
