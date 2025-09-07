<?php if (!defined('ABSPATH')) { exit; } ?>
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
            <div class="w-full">
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
                <div class="w-full">
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
        <?php echo KIT_Commons::renderButton('Next: Waybill Items', 'primary', 'md', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />',
            'iconPosition' => 'right',
            'data-target' => 'step-3',
            'classes' => 'next-step',
            'gradient' => true
        ]);
        ?>
    </div>
</div>