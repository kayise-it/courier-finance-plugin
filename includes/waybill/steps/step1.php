<div class="bg-white md:bg-transparent shadow md:shadow-transparent rounded-lg md:rounded-none space-y-4 md:space-y-0 md:grid md:grid-cols-2 md:gap-6">
    <div class="space-y-4 md:bg-white md:shadow rounded-lg md:p-6">
        <div class="md:bg-white rounded-lg">
            <div class="">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Create New Waybill</h2>
                <p class="text-xs text-gray-600">
                    <?php echo $is_edit_mode ? 'Edit the waybill details below.' : 'Please fill in the details below to create a new waybill.'; ?>
                </p>
            </div>
            <!-- Waybill Number -->
            <div class="w-full max-w-xs">
                <?php
                echo KIT_Commons::Linput([
                    'label' => 'Waybill No',
                    'name'  => 'waybill_no',
                    'id'    => 'waybill_no',
                    'type'  => 'text',
                    'value' => esc_attr($waybill->waybill_no ?? KIT_Waybills::generate_waybill_number()),
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-500',
                    'special' => 'readonly',
                ]);
                ?>
                <div class="w-full max-w-xs">
                    <?php
                    echo KIT_Commons::TextAreaField([
                        'label' => 'Waybill Description',
                        'name'  => 'waybill_description',
                        'id'    => 'waybill_description',
                        'type'  => 'textarea',
                        'value' => esc_attr($waybill->waybill_description ?? ''),
                    ]);
                    ?>
                </div>
            </div>
        </div>

        <!-- Additional Fees otherz-->
        <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/additionCharges.php'); ?>
    </div>

    <div class="hidden md:block bg-white shadow rounded-lg p-4">

        <?php
        require __DIR__ . '/step2.php'; ?>
    </div>

    <div class="md:hidden flex justify-end mt-6">
        <button type="button" class="next-step inline-flex items-center px-4 py-2 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Next: Customer Details →
        </button>
    </div>
</div>