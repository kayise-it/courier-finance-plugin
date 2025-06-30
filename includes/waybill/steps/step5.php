 <div class="">
     <div class="grid md:grid-cols-5 gap-4">
         <div class="md:col-span-3 rounded-lg">
             <div class="">
                 <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/dimandvol.php'); ?>
             </div>
             <div class="rounded bg-slate-100 p-6">
                 <?= KIT_Commons::h2tag(['title' => 'Chargee Basis', 'class' => 'text-lg font-medium text-gray-700 mb-3']) ?>
                 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                     <div>
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
                                $chargebasis
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
             </div>
         </div>

         <div class="md:col-span-2 items-section">
             <div class="p-6 rounded-lg bg-slate-100">
                 <?= KIT_Commons::h2tag(['title' => 'Items', 'class' => 'text-lg font-medium text-gray-700 mb-3']) ?>
                 <table class="table">
                     <tbody>
                         <!-- Dynamic Miscellaneous Charges -->
                         <tr id="misc-charges-container">
                             <td colspan="2">
                                 <?php
                                    echo KIT_Commons::miscItemsControl([
                                        'container_id' => 'misc-items',
                                        'button_id' => 'add-misc-item',
                                        'group_name' => 'misc',
                                        'input_class' => '',
                                        'existing_items' => [
                                            /*  ['misc_item' => 'Shipping', 'misc_price' => 15.00, 'qty' => 1],
                                            ['misc_item' => 'Handling', 'misc_price' => 5.50, 'qty' => 1] */]
                                    ]);
                                    ?>

                                 <div id="misc-items-container">
                                     <!-- Existing misc items will be loaded here -->
                                     <?php
                                        if (!empty($quotation->miscellaneous)) {
                                            $misc_items = json_decode($quotation->miscellaneous, true);
                                            if (is_array($misc_items)) {
                                                foreach ($misc_items as $index => $item) {
                                                    echo '<div class="misc-item" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: center;">
                                                            <input type="text" name="misc_item[]" value="' . esc_attr($item['name']) . '" placeholder="Item description" style="flex: 2; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                                            <input type="number" name="misc_price[]" value="' . esc_attr($item['price']) . '" placeholder="Amount" step="0.01" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                                                            <button type="button" class="remove-misc-btn" style="background-color: #ef4444; color: white; padding: 8px; border-radius: 4px; border: none; cursor: pointer;">×</button>
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
         </div>
     </div>

     <div class="flex justify-between mt-8">
         <button type="button" class="prev-step px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">
             Back
         </button>
         <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
             <?php echo $is_edit_mode ? 'Update Waybill' : 'Create Waybill'; ?>
         </button>
     </div>
 </div>