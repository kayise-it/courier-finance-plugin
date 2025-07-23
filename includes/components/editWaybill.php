<div class="max-w-6xl mx-auto p-6 bg-white rounded-lg shadow-md">
    <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>">
        <input type="hidden" name="action" value="update_waybill_action">
        <input type="hidden" name="waybill_id" value="<?= $waybill_id ?>">
        <input type="hidden" name="waybill_no" value="<?= $waybill['waybill_no'] ?>">
        <input type="hidden" name="cust_id" value="<?= $waybill['customer_id'] ?>">
        <input type="hidden" name="direction_id" value="<?= $waybill['direction_id'] ?>">
        <input type="hidden" name="current_rate" value="<?= $waybill['miscellaneous']['others']['mass_rate'] ?>">

        <?php wp_nonce_field('update_waybill_nonce'); ?>

        <!-- Header Section -->
        <div class="flex justify-between items-center mb-8 border-b pb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editing Waybill
                    #<?= htmlspecialchars($waybill['waybill_no']) ?></h1>
                <div class="flex items-center mt-2">
                    <span
                        class="px-3 py-1 rounded-full text-xs font-medium <?= $waybill['approval'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                        <?= ucfirst($waybill['approval']) ?> Approval
                    </span>
                    <span class="ml-2 text-xs text-gray-500">
                        <?php
                        $status_text = ucfirst($waybill['approval']);
                        $action_text = $waybill['approval'] === 'pending' ? 'Pending' : ($waybill['approval'] === 'rejected' ? 'Rejected' : ($waybill['approval'] === 'completed' ? 'Completed' : 'Approved'));
                        echo $action_text . ' By: ' . $waybill['approved_by_username'];
                        ?>
                    </span>
                    <span class="ml-2 text-xs text-gray-500">
                        Last Updated: <?= date('M j, Y', strtotime($waybill['last_updated_at'])) ?>
                    </span>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="?page=08600-Waybill-view&waybill_id=<?= $waybill_id ?>"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Canc3el Editing
                </a>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Save Chaunges
                </button>
            </div>
        </div>

        <!-- Waybill Information Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Customer Details -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Customer Details</h2>
                <div class="space-y-3">
                    <div class="flex flex-col">
                        <span class="text-gray-600 font-bold">Customer Name:</span>
                        <span class="font-medium"><?= $waybill['customer_name'] ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-600 font-bold">Customer Surname:</span>
                        <span class="font-medium"><?= $waybill['customer_surname'] ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-600 font-bold">Address:</span>
                        <span class="font-medium"><?= htmlspecialchars($waybill['address']) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-600 font-bold">Contact:</span>
                        <span class="font-medium"><?= htmlspecialchars($waybill['cell']) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-600 font-bold">Email:</span>
                        <span class="font-medium"><?= $waybill['email_address'] ?></span>
                    </div>

                </div>
            </div>
            <!-- Shipment Details (Read-only) -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Cost Details</h2>
                <div class="space-y-3">
                    <div class="flex flex-col">
                        <span class="text-gray-600 font-bold">Waybill Number:</span>
                        <span class="font-medium"><?= htmlspecialchars($waybill['waybill_no']) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-600 font-bold">Tracking Number:</span>
                        <span class="font-medium"><?= htmlspecialchars($waybill['tracking_number']) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-600 font-bold">Invoice Number:</span>
                        <span class="font-medium"><?= htmlspecialchars($waybill['product_invoice_number']) ?></span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-600 font-bold">Invoice Amount:</span>
                        <span class="font-medium"><?= KIT_Commons::currency() ?>
                            <span class="waybilltotalMockup"><?= number_format($waybill['product_invoice_amount'], 2) ?></span></span>
                    </div>
                    <div class="flex items-center">
                        <span class="text-gray-600 font-bold"></span>
                        <span class="text-xs text-gray-500 italic">
                            Waybill Amount + Misc Total
                        </span>
                    </div>
                    <div class="addCharges">
                        <?php
                        $optionChoice = 2;
                        require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/additionCharges.php'); ?>
                    </div>
                    <div class="flex flex-col">

                        <span class="text-gray-600 font-bold">Dispatch Date:</span>
                        <?php
                        //if dispatch_date is empty
                        if (empty($waybill['dispatch_date']) || $waybill['dispatch_date'] == '0000-00-00' || $waybill['dispatch_date'] == null) {
                            echo '<span class="text-red-500 italic">Not yet dispatched</span>';
                        } else {
                        ?>
                            <span class="font-medium"><?= date('M j, Y', strtotime($waybill['dispatch_date'])) ?></span>
                        <?php } ?>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-gray-600 font-bold">Truck Number:</span>
                        <?php
                        //if dispatch_date is empty
                        if (empty($waybill['dispatch_date']) || $waybill['dispatch_date'] == '0000-00-00' || $waybill['dispatch_date'] == null) {
                            echo '<span class="text-red-500 italic">Truck Number not selected</span>';
                        } else {
                        ?>
                            <span class="font-medium"><?= htmlspecialchars($waybill['truck_number']) ?></span>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- bitch2 -->

            <!-- Route Information (Editable) -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Route Information</h2>
                <div class="space-y-3">



                    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsOrigin.php'); ?>
                    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsDestination.php'); ?>

                    <div class="items-center">


                    </div>
                    saodfnasdf


                    <div class="items-center">
                        <?php
                        $charge_basis = [
                            'weight' => 'Weight',
                            'volume' => 'Volume',
                            'value' => 'Value'
                        ];

                        echo KIT_Commons::simpleSelect(
                            'Prefered Charge Basis',
                            'charge_basis',
                            'charge_basis',
                            $charge_basis,
                            $waybill['charge_basis'] ?? null,
                        );
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/weight.php'); ?>
            <div class="bg-slate-100 p-6 rounded">
                <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/dimensions.php'); ?>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <?= KIT_Commons::waybillItemsControl([
                'container_id' => 'custom-waybill-items',
                'button_id' => 'add-waybill-item',
                'group_name' => 'custom_items',
                'existing_items' => $waybill['items'],
                'input_class' => 'border border-gray-300 rounded px-3 py-2 bg-white',
                'remove_btn_class' => 'bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600',
                'add_btn_class' => 'bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700',
                'specialClass' => '!text-[10px]',
            ]);
            ?>
            <!-- Miscellaneous Items Section (Editable) -->
            <div class="bg-gray-50 p-4 rounded-lg mb-8">
                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Miscellaneous Items</h2>
                <?php
                $misc = [];

                if (!empty($waybill['miscellaneous'])) {
                    $misc = $waybill['miscellaneous'] ?? [];
                }
                $misc_items = [];


                // Check if misc data exists and has items
                if (!empty($misc) && is_array($misc) && isset($misc['misc_items']) && is_array($misc['misc_items'])) {
                    $misc_total = floatval($misc['misc_items']['misc_total'] ?? 0);

                    foreach ($misc['misc_items'] as $item) {
                        $name     = isset($item['misc_item'])  ? sanitize_text_field($item['misc_item'])  : '';
                        $price    = isset($item['misc_price']) ? floatval($item['misc_price']) : 0;
                        $quantity = isset($item['misc_quantity'])   ? intval($item['misc_quantity'])     : 1;
                        $subtotal = $price * $quantity;

                        $misc_items[] = [
                            'misc_item'     => sanitize_text_field($name),
                            'misc_price'    => $price,
                            'misc_quantity' => $quantity,
                            'misc_subtotal' => $subtotal,
                        ];
                    }
                } else {
                    $misc_total = 0;
                }
                ?>

                <?php

                echo KIT_Commons::miscItemsControl([
                    'container_id' => 'misc-items',
                    'button_id' => 'add-misc-item',
                    'group_name' => 'misc',
                    'input_class' => '',
                    'existing_items' => $misc_items
                ]);
                ?>


                <div class="mt-4 pt-4 border-t">
                    <div class="flex justify-between items-center">
                        <span class="font-medium">Total Miscellaneous:</span>
                        <span class="font-bold"><?= KIT_Commons::currency() ?>
                            <?= number_format($misc['misc_total'] ?? 0, 2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- askdjnaskdanbskjdnaskjndjkasndjkasndjkasndaksjnd -->
        <div class="flex space-x-3">
            <a href="?page=08600-Waybill-view&waybill_id=<?= $waybill_id ?>"
                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Canc3el Editing
            </a>
            <button type="submit"
                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Save Chaunges
            </button>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Add new misc item row
                document.getElementById('add-misc-item').addEventListener('click', function() {
                    const container = document.getElementById('misc-items-container');
                    const newRow = document.createElement('div');
                    newRow.className = 'flex items-center mb-2 misc-item-row';
                    newRow.innerHTML = `
                                        <input type="text" name="misc_item[]" class="flex-1 px-3 py-2 border rounded-md mr-2" placeholder="Item description">
                                        <input type="number" name="misc_price[]" class="w-1/4 px-3 py-2 border rounded-md" placeholder="Amount">
                                        <button type="button" class="ml-2 px-3 py-2 bg-red-500 text-white rounded-md remove-misc-item">
                                            Remove
                                        </button>`;
                    container.appendChild(newRow);
                });

                // Remove misc item row
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('remove-misc-item')) {
                        e.target.closest('.misc-item-row').remove();
                    }
                });
            });
        </script>
    </form>
</div>