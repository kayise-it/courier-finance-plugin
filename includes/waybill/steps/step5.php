<?php if (!defined('ABSPATH')) {
    exit;
} ?>
<div class="bg-white p-6">
    <div class="grid md:grid-cols-5 gap-4">
        <div class="md:col-span-3 rounded-lg">
            <div class="">
                <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/dimandvol.php'); ?>
            </div>
            <!--  <div class="rounded bg-slate-100 p-6">
                 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                     <div>
                     <?= KIT_Commons::prettyHeading([
                            'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
                            'words' => 'Charge Basis'
                        ]) ?>
                         <?php
                            $chargebasis = [
                                'mass' => 'Mass',
                                'volume' => 'Volume',
                                'both' => 'Both'
                            ];
                            echo KIT_Commons::simpleSelect(
                                'Charge Basis',
                                'charge_basis',
                                'charge_basis',
                                $chargebasis,
                                null
                            );
                            ?>
                     </div>
                     <div>
                         <?= KIT_Commons::Linput([
                                'label' => 'Mass Charge (R)',
                                'name'  => 'mass_charge',
                                'id'  => 'mass_charge',
                                'type'  => 'text',
                                'value' => esc_attr($waybill->mass_charge ?? 40),
                                'class' => '',
                                'special' => 'readonly',
                            ]); ?>
                     </div>
                    
                     <div>
                         <?= KIT_Commons::Linput([
                                'label' => 'Volume Charge (R)',
                                'name'  => 'volume_charge',
                                'id'  => 'volume_charge',
                                'type'  => 'text',
                                'value' => esc_attr($waybill->volume_charge ?? 40),
                                'class' => '',
                                'special' => 'readonly',
                            ]); ?>
                     </div>
                 </div>
             </div> -->
        </div>

        <div class="md:col-span-2 space-y-6 mb-6">

            <div class="p-6 rounded-lg bg-slate-100">
                <table class="table">
                    <tbody>
                        <!-- Dynamic Miscellaneous Charges -->
                        <tr id="misc-charges-container">
                            <td colspan="2">
                                <?php
                                echo KIT_Commons::dynamicItemsControl([
                                    'container_id' => 'misc-items',
                                    'button_id' => 'add-misc-item',
                                    'group_name' => 'misc',
                                    'item_type' => 'misc',
                                    'title' => 'Miscellaneous Items',
                                    'input_class' => '',
                                    'existing_items' => [],
                                    'subtotal_id' => 'misc-total',
                                    'currency_symbol' => KIT_Commons::currency(),
                                    'show_subtotal' => true,
                                    'show_invoices' => true,
                                    'waybill_no' => 'TEMP-' . uniqid()
                                ]);
                                ?>

                                <div id="misc-items-container">
                                    <!-- Existing misc items will be loaded here -->
                                    <?php
                                    // Check if we have waybill data with miscellaneous items
                                    if (isset($waybill) && !empty($waybill->miscellaneous)) {
                                        $misc_items = maybe_unserialize($waybill->miscellaneous);
                                        if (is_array($misc_items) && isset($misc_items['others'])) {
                                            foreach ($misc_items['others'] as $index => $item) {
                                                echo '<div class="misc-item" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                                            <input type="text" name="misc_item[]" value="' . esc_attr($item['name'] ?? '') . '" placeholder="Item description" style="flex: 2; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                                            <input type="number" name="misc_price[]" value="' . esc_attr($item['price'] ?? '') . '" placeholder="Amount" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                                            <button type="button" class="remove-misc-btn bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-sm font-medium transition-colors duration-200">×</button>
                                                        </div>';
                                            }
                                        }
                                    }
                                    ?>
                                </div>

                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="p-6 rounded-lg bg-slate-100">
            <div id="totalOverride">
                <!-- total override, client wants to override the total and manually enter the total -->
                <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/totalOverride.php'); ?>
            </div>
        </div>
    </div>

    <div class="flex justify-between mt-8">
        <?php echo KIT_Commons::renderButton('Back', 'secondary', 'md', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />',
            'iconPosition' => 'left',
            'data-target' => 'step-4',
            'classes' => 'prev-step'
        ]); ?>
        <?php echo KIT_Commons::renderButton($is_edit_mode ? 'Update Waybill' : 'Create Waybill', 'success', 'md', [
            'type' => 'submit',
            'classes' => 'submit-btn',
            'gradient' => true
        ]); ?>
    </div>
</div>