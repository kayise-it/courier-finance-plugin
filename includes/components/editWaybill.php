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
    'company_name' => '',
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

// Determine client type based on company_name
$is_business = !empty($waybill['company_name']) && $waybill['company_name'] !== '1ndividual';

// Ensure miscellaneous is an array
if (!is_array($waybill['miscellaneous'])) {
    $waybill['miscellaneous'] = [];
}

// Ensure items is an array
if (!is_array($waybill['items'])) {
    $waybill['items'] = [];
}

// Load customers for selection
try {
    $customers = tholaMaCustomer();
    if (empty($customers)) {
        error_log('tholaMaCustomer returned empty result');
        $customers = [];
    }
} catch (Exception $e) {
    error_log('Error calling tholaMaCustomer: ' . $e->getMessage());
    $customers = [];
}

if (class_exists('KIT_Commons')) {
    KIT_Commons::enqueueComponentScripts(['kitscript']);
} elseif (function_exists('wp_enqueue_script')) {
    wp_enqueue_script('kitscript', COURIER_FINANCE_PLUGIN_URL . 'js/kitscript.js', ['jquery'], null, true);
}

if (function_exists('wp_localize_script')) {
    wp_localize_script('kitscript', 'EditWaybillData', [
        'customers' => $customers,
    ]);
}

// Debug: Check what's in the waybill data
echo "<!-- DEBUG: waybill data structure -->";
echo "<!-- DEBUG: miscellaneous = " . print_r($waybill['miscellaneous'], true) . " -->";
if (isset($waybill['miscellaneous']['others'])) {
    echo "<!-- DEBUG: others = " . print_r($waybill['miscellaneous']['others'], true) . " -->";
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
            // Get waybill description - priority: 1) Direct 'description' column, 2) miscellaneous['others']['waybill_description']
            $waybill_description_value = '';
            if (!empty($waybill['description'])) {
                $waybill_description_value = $waybill['description'];
            } elseif (!empty($waybill['miscellaneous']['others']['waybill_description'])) {
                $waybill_description_value = $waybill['miscellaneous']['others']['waybill_description'];
            }
            
            echo KIT_Commons::TextAreaField([
                'label' => 'Waybill Description',
                'name'  => 'waybill_description',
                'id'    => 'waybill_description',
                'type'  => 'textarea',
                'value' => $waybill_description_value,
            ]);
            ?>
        </div>

        <!-- Waybill Information Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Customer Details -->
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Customer Details</h2>
                
                <!-- Customer Search Section -->
                <div class="mb-4">
                    <label for="customer-search-edit" class="block text-sm font-semibold text-gray-700 mb-2">Select Customer</label>
                    <div class="relative">
                        <input
                            type="text"
                            id="customer-search-edit"
                            name="customer_search"
                            placeholder="Type to search customers..."
                            class="block w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition px-4 py-2 bg-white text-gray-800"
                            autocomplete="off">
                        <input type="hidden" id="customer-select-edit" name="customer_select" value="<?= esc_attr(!empty($waybill['customer_id']) ? $waybill['customer_id'] : 'new') ?>">

                        <div id="customer-results-edit" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto">
                            <!-- Results will be populated by JavaScript -->
                        </div>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button type="button" id="add-new-customer-btn-edit" class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition">
                            + Add New Customer
                        </button>
                        <button type="button" id="recent-customers-btn-edit" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition">
                            Recent Customers
                        </button>
                    </div>
                </div>

                <!-- Client Type -->
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Is the client a business or an individual?</label>
                    <div class="flex gap-4">
                        <label class="inline-flex items-center">
                            <input type="radio" name="client_type" id="client_type_business_edit" value="business" class="client-type-radio-edit" <?= $is_business ? 'checked' : '' ?>>
                            <span class="ml-2">Business</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="client_type" id="client_type_individual_edit" value="individual" class="client-type-radio-edit" <?= !$is_business ? 'checked' : '' ?>>
                            <span class="ml-2">Individual</span>
                        </label>
                    </div>
                </div>

                <!-- Customer Details Form -->
                <div class="rounded-xl border border-gray-200 bg-gradient-to-br from-blue-50 to-white shadow-inner">
                    <div class="grid grid-cols-2 gap-3">
                        <div id="company_name_wrapper_edit">
                            <?= KIT_Commons::Linput([
                                'label' => 'Company Name',
                                'name'  => 'company_name',
                                'id'    => 'company_name_edit',
                                'type'  => 'text',
                                'value' => esc_attr($waybill['company_name'] ?? ''),
                                'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-gray-800 bg-white transition',
                                'special' => 'autocomplete="organization"'
                            ]); ?>
                        </div>
                        <div>
                            <?= KIT_Commons::Linput([
                                'label' => 'Customer Name',
                                'name'  => 'customer_name',
                                'id'    => 'customer_name_edit',
                                'type'  => 'text',
                                'value' => esc_attr($waybill['customer_name'] ?? ''),
                                'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-gray-800 bg-white transition',
                                'special' => 'autocomplete="given-name"'
                            ]); ?>
                        </div>
                        <div>
                            <?= KIT_Commons::Linput([
                                'label' => 'Customer Surname',
                                'name'  => 'customer_surname',
                                'id'    => 'customer_surname_edit',
                                'type'  => 'text',
                                'value' => esc_attr($waybill['customer_surname'] ?? ''),
                                'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-gray-800 bg-white transition',
                                'special' => 'autocomplete="family-name"'
                            ]); ?>
                        </div>
                        <div>
                            <?= KIT_Commons::Linput([
                                'label' => 'Telephone',
                                'name'  => 'telephone',
                                'id'    => 'telephone_edit',
                                'type'  => 'tel',
                                'value' => esc_attr($waybill['cell'] ?? ''),
                                'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-gray-800 bg-white transition',
                                'special' => 'autocomplete="tel"'
                            ]); ?>
                        </div>
                        <div>
                            <?= KIT_Commons::Linput([
                                'label' => 'Cell',
                                'name'  => 'cell',
                                'id'    => 'cell_edit',
                                'type'  => 'tel',
                                'value' => esc_attr($waybill['cell'] ?? ''),
                                'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-gray-800 bg-white transition',
                                'special' => 'autocomplete="tel"'
                            ]); ?>
                        </div>
                        <div>
                            <?= KIT_Commons::Linput([
                                'label' => 'Email',
                                'name'  => 'email_address',
                                'id'    => 'email_address_edit',
                                'type'  => 'email',
                                'value' => esc_attr($waybill['email_address'] ?? ''),
                                'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-gray-800 bg-white transition',
                                'special' => 'autocomplete="email"'
                            ]); ?>
                        </div>
                        <div class="col-span-2">
                            <?= KIT_Commons::Linput([
                                'label' => 'Address',
                                'name'  => 'address',
                                'id'    => 'address_edit',
                                'type'  => 'text',
                                'value' => esc_attr($waybill['address'] ?? ''),
                                'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 text-gray-800 bg-white transition',
                                'special' => 'autocomplete="street-address"'
                            ]); ?>
                    </div>
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
                    <?php if (class_exists('KIT_User_Roles') && !KIT_User_Roles::can_see_prices()): ?>

                        <?php else: ?>
                        <div class="flex flex-col">
                            <label class="<?= KIT_Commons::labelClass() ?>">Invoice Amount:</label>
                            <span class="font-medium"><?= KIT_Commons::currency() ?>
                                <span class="waybilltotalMockup"><?= number_format($waybill['product_invoice_amount'] ?? 0, 2) ?></span></span>
                        </div>
                    <?php endif; ?>

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
                        <input type="date" id="dispatch_date_edit" name="dispatch_date" 
                            value="<?= esc_attr($waybill['dispatch_date'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 bg-white">
                    </div>
                    <div class="flex flex-col">
                        <label class="<?= KIT_Commons::labelClass() ?>">Truck Number:</label>
                        <input type="text" id="truck_number_edit" name="truck_number" 
                            value="<?= esc_attr($waybill['truck_number'] ?? '') ?>"
                            class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 bg-white">
                    </div>
                    <div class="flex flex-col">
                        <label class="<?= KIT_Commons::labelClass() ?>">Truck Driver:</label>
                        <select id="truck_driver_edit" name="truck_driver" 
                            class="w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-3 py-2 bg-white">
                            <option value="">Select Driver</option>
                        <?php
                            // Load drivers
                            global $wpdb;
                            $drivers_table = $wpdb->prefix . 'kit_drivers';
                            $drivers = $wpdb->get_results("SELECT * FROM $drivers_table WHERE is_active = 1 ORDER BY name ASC");
                            foreach ($drivers as $driver) {
                                $selected = isset($waybill['truck_driver']) && $waybill['truck_driver'] == $driver->id ? 'selected' : '';
                                echo '<option value="' . esc_attr($driver->id) . '" ' . $selected . '>' . esc_html($driver->name) . '</option>';
                            }
                            ?>
                        </select>
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

                    <!-- Delivery Assignment Section -->

                    <div class="mt-4 border border-gray-200 rounded-lg">
                        <!-- Header -->
                        <div class="bg-gray-50 border-b border-gray-200">
                            <h3 class="text-md font-medium text-gray-800">Change Truck Assignment</h3>
                            <p class="text-sm text-gray-600 mt-1">Select a different delivery/truck for this waybill</p>
                        </div>

                        <div>
                            <div class="<?= KIT_Commons::yspacingClass(); ?>">

                                <label for="pending_option" class="<?= KIT_Commons::labelClass(); ?>">Warehoused
                                    <input type="checkbox" name="warehouse" id="pending_option" value="1" class="mr-2" <?= (isset($waybill['warehouse']) && $waybill['warehouse']) ? 'checked' : '' ?>>
                                </label>
                                <p class="text-xs text-gray-500 mt-1">Check this if the item is to be pending (no destination required)</p>
                            </div>
                            <!-- Hidden field to persist the selected delivery id across steps/submission -->
                            <input type="hidden" name="delivery_id" id="selected_delivery_id" value="" />
                        </div>

                        <!-- Hidden fields -->
                        <input type="hidden" name="current_delivery_id" id="current_delivery_id" value="<?= esc_attr($waybill['delivery_id'] ?? '') ?>">
                        <input type="hidden" name="delivery_id" id="delivery_id" value="<?= esc_attr($waybill['delivery_id'] ?? '') ?>">
                        <input type="hidden" name="direction_id" id="direction_id" value="<?= esc_attr($waybill['direction_id'] ?? '') ?>">
                        <input type="hidden" name="warehouse_status" id="warehouse_status" value="<?= esc_attr(isset($waybill['warehouse']) && $waybill['warehouse'] ? '1' : '0') ?>">

                        <!-- Delivery Cards Selection -->
                        <div class="">
                            <?php
                            $atts = ['hide_header' => true];
                            $atts['small_width'] = true;
                            require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/scheduledDeliveries.php');
                            ?>

                        </div>
                    </div>

                    <div id="totalOverride">
                        <!-- total override, client wants to override the total and manually enter the total -->
                        <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/totalOverride.php'); ?>
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
                'currency_symbol' => 'R',
                'show_invoices' => true,
                'waybill_no' => $waybill['waybill_no'] ?? 'TEMP-' . uniqid()
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
                $misc_total = floatval($misc['misc_total'] ?? 0);

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
                'show_subtotal' => true,
                'show_invoices' => false,
            ]);
            ?>
        </div>


    </form>
</div>