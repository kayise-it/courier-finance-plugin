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
                <?=
                KIT_Commons::Linput([
                    'label' => 'Waybill No',
                    'name'  => 'waybill_no',
                    'id'    => 'waybill_no',
                    'type'  => 'text',
                    'value' => esc_attr($waybill->waybill_no ?? rand(5677, 9999)),
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50 focus:outline-none focus:ring-1 focus:ring-blue-500',
                    'special' => 'readonly',
                ]);
                ?>

            </div>
        </div>

        <!-- Additional Fees -->
        <div class="">
            <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider mb-3">Additional Options</h3>
            <div class="flex gap-4">
                <!-- Waybill Fee -->
                <?= KIT_Commons::ButtonBox([
                    'name' => 'Waybill Fee',
                    'value'       => '1',
                    'min_desc'    => 'R50',
                    'data_target' => 'include_waybill_fee',
                    'checked'     => false,
                    'type'        => 'checkbox', // or 'radio'
                    'class'       => 'fee-option text-gray-700 flex items-center justify-center p-3 border border-gray-300 rounded-lg shadow-sm hover:shadow-md hover:border-blue-400 transition-all w-24 h-24',
                    'id'          => 'include_waybill_fee',
                    'disabled'    => false,
                ]);
                ?>
                <!-- SAD500 Fee -->
                <?= KIT_Commons::ButtonBox([
                    'name'        => 'SAD500',
                    'value'       => '1',
                    'min_desc'    => 'R350',
                    'data_target' => 'include_sad500',
                    'checked'     => false,
                    'type'        => 'checkbox', // or 'radio'
                    'class'       => 'fee-option text-gray-700 flex items-center justify-center p-3 border border-gray-300 rounded-lg shadow-sm hover:shadow-md hover:border-blue-400 transition-all w-24 h-24',
                    'id'          => 'include_sad500',
                    'disabled'    => false,
                ]);
                ?>
                <!-- VAT -->
                <?= KIT_Commons::ButtonBox([
                    'name'        => 'VAT',
                    'value'       => '1',
                    'min_desc'    => '10%',
                    'data_target' => 'vat_option',
                    'checked'     => false,
                    'type'        => 'checkbox', // or 'radio'
                    'class'       => 'fee-option text-gray-700 flex items-center justify-center p-3 border border-gray-300 rounded-lg shadow-sm hover:shadow-md hover:border-blue-400 transition-all w-24 h-24',
                    'id'          => 'vat_option',
                    'disabled'    => false,
                ]);
                ?>
            </div>
            <p class="text-xs text-gray-500 mt-2">Note: VAT cannot be combined with other fees</p>
        </div>
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
