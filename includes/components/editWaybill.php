<?php
// Validate waybill data
if (empty($waybill) || !is_array($waybill)) {
    echo '<div class="max-w-6xl mx-auto p-6 bg-white rounded-lg shadow-md">';
    echo '<div class="text-center py-8">';
    echo '<h2 class="text-2xl font-bold text-red-600 mb-4">Error</h2>';
    echo '<p class="text-gray-600">Waybill data not found or invalid.</p>';
    echo '<a href="?page=08600-Waybill-list" class="mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Back to Waybills</a>';
    echo '</div>';
    echo '</div>';
    return;
}

// Ensure required fields exist with defaults
$waybill = array_merge([
    'waybill_no' => '',
    'customer_id' => '',
    'direction_id' => '',
    'customer_name' => '',
    'customer_surname' => '',
    'cell' => '',
    'email_address' => '',
    'address' => '',
    'approval' => 'pending',
    'approved_by_username' => '',
    'last_updated_at' => '',
    'product_invoice_number' => '',
    'product_invoice_amount' => 0,
    'tracking_number' => '',
    'truck_number' => '',
    'dispatch_date' => '',
    'miscellaneous' => [],
    'items' => []
], $waybill);

// Ensure miscellaneous is an array
if (!is_array($waybill['miscellaneous'])) {
    $waybill['miscellaneous'] = [];
}

// Ensure items is an array
if (!is_array($waybill['items'])) {
    $waybill['items'] = [];
}
?>

<div class="max-w-6xl mx-auto p-6 bg-white rounded-lg shadow-md">
    <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>">
        <input type="hidden" name="action" value="update_waybill_action">
        <input type="hidden" name="waybill_id" value="<?= esc_attr($waybill_id) ?>">
        <input type="hidden" name="waybill_no" value="<?= esc_attr($waybill['waybill_no']) ?>">
        <input type="hidden" name="cust_id" value="<?= esc_attr($waybill['customer_id']) ?>">
        <input type="hidden" name="direction_id" value="<?= esc_attr($waybill['direction_id']) ?>">
        <input type="hidden" name="current_rate" value="<?= esc_attr($waybill['miscellaneous']['others']['mass_rate'] ?? '') ?>">

        <?php wp_nonce_field('update_waybill_nonce'); ?>

        <!-- Header Section -->
        <div class="flex justify-between items-center mb-8 border-b pb-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Editing Waybill
                    #<?= esc_html($waybill['waybill_no']) ?></h1>
                <div class="flex items-center mt-2">
                    <span
                        class="px-3 py-1 rounded-full text-xs font-medium <?= ($waybill['approval'] ?? 'pending') === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                        <?= ucfirst($waybill['approval'] ?? 'pending') ?> Approval
                    </span>
                    <span class="ml-2 text-xs text-gray-500">
                        <?php
                        $approval = $waybill['approval'] ?? 'pending';
                        $action_text = $approval === 'pending' ? 'Pending' : ($approval === 'rejected' ? 'Rejected' : ($approval === 'completed' ? 'Completed' : 'Approved'));
                        echo $action_text . ' By: ' . esc_html($waybill['approved_by_username'] ?? 'N/A');
                        ?>
                    </span>
                    <span class="ml-2 text-xs text-gray-500">
                        Last Updated: <?= !empty($waybill['last_updated_at']) ? date('M j, Y', strtotime($waybill['last_updated_at'])) : 'N/A' ?>
                    </span>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="?page=08600-Waybill-view&waybill_id=<?= $waybill_id ?>"
                    class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel Editing
                </a>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Save Changes
                </button>
            </div>
        </div>

        <div class="ps">
            <?php
            echo KIT_Commons::TextAreaField([
                'label' => 'Waybill Description',
                'name'  => 'waybill_description',
                'id'    => 'waybill_description',
                'type'  => 'textarea',
                'value' => $waybill['miscellaneous']['others']['waybill_description'] ?? '',
            ]);
            ?>
        </div>

        <!-- Waybill Information Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Customer Details -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Customer Details</h2>
                <div class="space-y-3">
                    <div class="flex flex-col">
                        <label class="<?= KIT_Commons::labelClass() ?>">Customer Name:</label>
                        <span class="font-medium"><?= esc_html($waybill['customer_name'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex flex-col">
                        <label class="<?= KIT_Commons::labelClass() ?>">Customer Surname:</label>
                        <span class="font-medium"><?= esc_html($waybill['customer_surname'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex flex-col">
                        <label class="<?= KIT_Commons::labelClass() ?>">Address:</label>
                        <span class="font-medium"><?= esc_html($waybill['address'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex flex-col">
                        <label class="<?= KIT_Commons::labelClass() ?>">Contact:</label>
                        <span class="font-medium"><?= esc_html($waybill['cell'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex flex-col">
                        <label class="<?= KIT_Commons::labelClass() ?>">Email:</label>
                        <span class="font-medium"><?= esc_html($waybill['email_address'] ?? 'N/A') ?></span>
                    </div>

                </div>
            </div>
            <!-- Shipment Details (Read-only) -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Cost Details</h2>
                <div class="space-y-3">
                    <div class="flex flex-col">
                        <label class="<?= KIT_Commons::labelClass() ?>">Waybill Number:</label>
                        <span class="font-medium"><?= esc_html($waybill['waybill_no'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex flex-col">
                        <label class="<?= KIT_Commons::labelClass() ?>">Tracking Number:</label>
                        <span class="font-medium"><?= esc_html($waybill['tracking_number'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex flex-col">
                        <label class="<?= KIT_Commons::labelClass() ?>">Invoice Number:</label>
                        <span class="font-medium"><?= esc_html($waybill['product_invoice_number'] ?? 'N/A') ?></span>
                    </div>
                    <div class="flex flex-col">
                        <label class="<?= KIT_Commons::labelClass() ?>">Invoice Amount:</label>
                        <span class="font-medium"><?= KIT_Commons::currency() ?>
                            <span class="waybilltotalMockup"><?= number_format($waybill['product_invoice_amount'] ?? 0, 2) ?></span></span>
                    </div>
                    <div class="flex items-center">
                        <label class="<?= KIT_Commons::labelClass() ?>"></label>
                        <span class="text-xs text-gray-500 italic">
                            Waybill Amount + Misc Total
                        </span>
                    </div>
                    <div class="addCharges">
                        <?php
                        require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/additionCharges.php'); ?>
                    </div>
                    <div class="flex flex-col">

                        <label class="<?= KIT_Commons::labelClass() ?>">Dispatch Date:</label>
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
                        <label class="<?= KIT_Commons::labelClass() ?>">Truck Number:</label>
                        <?php
                        //if dispatch_date is empty
                        if (empty($waybill['dispatch_date']) || $waybill['dispatch_date'] == '0000-00-00' || $waybill['dispatch_date'] == null) {
                            echo '<span class="text-red-500 italic">Truck Number not selected</span>';
                        } else {
                        ?>
                            <span class="font-medium"><?= esc_html($waybill['truck_number'] ?? 'N/A') ?></span>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <!-- Route Information (Editable) -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Route Information</h2>
                <div class="space-y-3">
                    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsOrigin.php'); ?>
                    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsDestination.php'); ?>
                    <div class="items-center">
                        <?php
                        $charge_basis = [
                            'mass' => 'Mass',
                            'volume' => 'Volume',
                        ];

                        echo KIT_Commons::simpleSelect(
                            'Preferred Charge Basis',
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
                <?php 
                // Pass waybill data to dimensions component
                $dimensions_waybill = $waybill;
                require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/dimensions.php'); 
                ?>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4 mt-4">
            <?= KIT_Commons::dynamicItemsControl([
                'container_id' => 'custom-waybill-items',
                'button_id' => 'add-waybill-item',
                'group_name' => 'custom_items',
                'existing_items' => isset($waybill['items']) && is_array($waybill['items']) ? $waybill['items'] : [],
                'input_class' => 'border border-gray-300 rounded px-3 py-2 bg-white',
                'remove_btn_class' => 'bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600',
                'add_btn_class' => 'bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700',
                'specialClass' => '!text-[10px]',
                'item_type' => 'waybill',
                'title' => 'Waybill Items',
                'description' => 'Add items being shipped with details and pricing',
                'subtotal_id' => 'waybill-subtotal',
                'currency_symbol' => 'R'
            ]);
            ?>
            <!-- Miscellaneous Items Section (Editable) -->
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

                <?= KIT_Commons::dynamicItemsControl([
                    'container_id' => 'misc-items',
                    'button_id' => 'add-misc-item',
                    'group_name' => 'misc',
                    'input_class' => 'border border-gray-300 rounded px-3 py-2 bg-white',
                    'existing_items' => $misc_items,
                    'item_type' => 'misc',
                    'title' => 'Miscellaneous Items',
                    'description' => 'Add miscellaneous items with details and pricing',
                    'subtotal_id' => 'misc-total',
                    'currency_symbol' => KIT_Commons::currency(),
                    'show_subtotal' => true
                ]);
                ?>
        </div>


    </form>
</div>