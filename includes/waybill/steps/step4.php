<div class="mb-6">
    <h3 class="text-lg font-medium text-gray-700 mb-3">Item Information</h3>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div>
            <?php require COURIER_FINANCE_PLUGIN_PATH . 'includes/components/countriesSelect.php'; ?>
        </div>
        <div class="<?= KIT_Commons::yspacingClass(); ?>">
            <label for="destination_city" class="<?= KIT_Commons::labelClass() ?>">Destination City</label>
            <div id="destinationWrap">
                <select class="<?= KIT_Commons::selectClass(); ?>" name="destination_city" id="destination_city">
                    <!-- Display the options here -->
                    <option value="">Select Country</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Scheduled Deliveries Container -->
    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/scheduledDeliveries.php'); ?>
</div>




<div class="flex justify-between mt-8">
    <button type="button" class="md:hidden prev-step px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
        Back
    </button>
    <button type="button" class="hidden md:block prev-step px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400" data-target="step-3">
        Back
    </button>
    <button type="button" class="next-step px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
        Next: Charges & Fees
    </button>
</div>