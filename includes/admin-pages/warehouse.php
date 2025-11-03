<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include user roles for permission checking
require_once plugin_dir_path(__FILE__) . '../user-roles.php';

// Include Dashboard Quickies component
require_once plugin_dir_path(__FILE__) . '../components/dashboardQuickies.php';
require_once plugin_dir_path(__FILE__) . '../components/quickStats.php';

// Include warehouse functions
require_once plugin_dir_path(__FILE__) . '../warehouse/warehouse-functions.php';
// Ensure unified table class is available for rendering tables
if (!class_exists('KIT_Unified_Table')) {
    $unified_path = plugin_dir_path(__FILE__) . '../class-unified-table.php';
    if (file_exists($unified_path)) {
        require_once $unified_path;
    }
}

// Handle realistic warehouse waybills creation
if (isset($_POST['create_sample_warehouse']) && wp_verify_nonce($_POST['sample_nonce'], 'create_sample_warehouse_waybills')) {
    $count = isset($_POST['waybill_count']) ? intval($_POST['waybill_count']) : 10;
    
    $result = KIT_Warehouse::createRealisticWarehousedWaybills($count);
    
    if (is_wp_error($result)) {
        echo '<div class="notice notice-error"><p>' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
        $message = "Successfully created {$result['created_count']} realistic warehouse waybills!";
        if (!empty($result['errors'])) {
            $message .= " Errors: " . implode(', ', $result['errors']);
        }
        echo '<div class="notice notice-success"><p>' . esc_html($message) . '</p></div>';
    }
}

// Handle form submission for assignment
if (isset($_POST['assign_warehouse_items']) && wp_verify_nonce($_POST['assign_nonce'], 'assign_warehouse_items')) {
    global $wpdb;

    $waybill_ids = $_POST['waybill_ids'] ?? [];
    // Accept delivery_id from hidden field or fallback select
    $delivery_id = 0;
    if (isset($_POST['delivery_id']) && $_POST['delivery_id'] !== '') {
        $delivery_id = intval($_POST['delivery_id']);
    } elseif (isset($_POST['delivery_id_select']) && $_POST['delivery_id_select'] !== '') {
        $delivery_id = intval($_POST['delivery_id_select']);
    }

    if (!empty($waybill_ids) && $delivery_id > 0) {
        $updated = 0;
        $assigned_waybill_nos = [];
        foreach ($waybill_ids as $waybill_id) {
            // Use the new warehouse system to assign waybills
            $result = KIT_Warehouse::assignToDelivery(intval($waybill_id), $delivery_id, get_current_user_id());
            
            if (!is_wp_error($result)) {
                $updated++;
                
                // Get waybill details for logging
                $waybill = $wpdb->get_row($wpdb->prepare(
                    "SELECT waybill_no, customer_id FROM {$wpdb->prefix}kit_waybills WHERE id = %d",
                    intval($waybill_id)
                ));
                
                if ($waybill && !empty($waybill->waybill_no)) {
                    $assigned_waybill_nos[] = (int)$waybill->waybill_no;
                }
            }
        }

        if ($updated > 0) {
            echo '<div class="notice notice-success"><p>Successfully assigned ' . $updated . ' waybill(s) to delivery! The page will refresh in 2 seconds to show updated warehouse status.</p></div>';
            echo '<script>setTimeout(function(){ window.location.reload(); }, 2000);</script>';
        } else {
            echo '<div class="notice notice-error"><p>Failed to assign waybills. Please try again.</p></div>';
        }
    }
}

// Get warehouse waybills
global $wpdb;
$waybills_table = $wpdb->prefix . 'kit_waybills';
$customers_table = $wpdb->prefix . 'kit_customers';

// Get waybills that are in warehouse
$warehouse_waybills = KIT_Warehouse::getWarehouseItems();
$total_warehouse_waybills = count($warehouse_waybills);

// Get status counts from waybills table
$stats = KIT_Warehouse::getWarehouseStats();
$warehouse_count = $stats->in_warehouse ?? 0;
$assigned_count = $stats->assigned ?? 0;
$shipped_count = $stats->shipped ?? 0;
$delivered_count = $stats->delivered ?? 0;

// Debug information removed for clean production
?>

<div class="wrap">
    <?php
    echo KIT_Commons::showingHeader([
        'title' => 'Warehouse Management',
        'desc' => 'Manage and track all warehouse operations',
        'icon' => KIT_Commons::icon('warehouse'),
    ]);
    ?>
    <div class="mb-4 flex items-center gap-3">
        <form method="post">
            <?php if (function_exists('wp_nonce_field')) { wp_nonce_field('fix_warehouse_flags_action', 'fix_warehouse_nonce'); } ?>
            <input type="hidden" name="fix_warehouse_flags" value="1">
            <?php echo KIT_Commons::renderButton('Sync Warehouse Status from Tracking Table', 'primary', 'sm', ['type' => 'submit']); ?>
        </form>
        
        <!-- Realistic Waybill Creation Form -->
        <form method="post" class="flex items-center gap-3">
            <?php wp_nonce_field('create_sample_warehouse_waybills', 'sample_nonce'); ?>
            <input type="hidden" name="create_sample_warehouse" value="1">
            <label class="text-sm font-medium text-gray-700">Create Realistic Waybills:</label>
            <input type="number" name="waybill_count" value="10" min="1" max="50" 
                   class="w-20 px-3 py-1 border border-gray-300 rounded-md text-sm" 
                   placeholder="Count">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                Create Waybills
            </button>
        </form>
    </div>
    <hr class="wp-header-end">
    <?php
    // Minimal functional Warehouse page (new logic)
    // Load warehouse helpers
    require_once plugin_dir_path(__FILE__) . '../warehouse/warehouse-functions.php';
    // Note: warehouse-status-helper.php and warehouse-migration-helper.php are included above

    // Note: assignment is handled above via 'assign_waybills' submit; no duplicate handler here

    // Data for page
    $stats = KIT_Warehouse::getWarehouseStats();
    
    // Tracking-based cleanup removed. Use waybills table only with new warehouse model
    $waybills_table = $wpdb->prefix . 'kit_waybills';
    $customers_table = $wpdb->prefix . 'kit_customers';
    
    // Fetch warehouse waybills to display/assign
    $tracking_rows = KIT_Warehouse::getWarehouseItems();
    global $wpdb;
    
    // Optimized query: Get all countries and their scheduled deliveries in one query
    $countries_with_deliveries = $wpdb->get_results("
        SELECT 
            oc.id as country_id,
            oc.country_name,
            oc.country_code,
            d.id as delivery_id,
            d.delivery_reference,
            d.dispatch_date,
            d.truck_number,
            d.status as delivery_status,
            dest.city_name AS destination_city
        FROM {$wpdb->prefix}kit_operating_countries oc
        LEFT JOIN {$wpdb->prefix}kit_shipping_directions sd ON oc.id = sd.destination_country_id
        LEFT JOIN {$wpdb->prefix}kit_deliveries d ON sd.id = d.direction_id AND d.status = 'scheduled'
        LEFT JOIN {$wpdb->prefix}kit_operating_cities dest ON d.destination_city_id = dest.id
        WHERE oc.is_active = 1
        ORDER BY oc.country_name ASC, d.dispatch_date ASC
    ");
    
    // Organize data by country
    $countries_data = [];
    foreach ($countries_with_deliveries as $row) {
        if (!isset($countries_data[$row->country_id])) {
            $countries_data[$row->country_id] = [
                'id' => $row->country_id,
                'name' => $row->country_name,
                'code' => $row->country_code,
                'deliveries' => []
            ];
        }
        
        if ($row->delivery_id) {
            $countries_data[$row->country_id]['deliveries'][] = [
                'id' => $row->delivery_id,
                'reference' => $row->delivery_reference,
                'dispatch_date' => $row->dispatch_date,
                'truck_number' => $row->truck_number,
                'status' => $row->delivery_status,
                'destination_country' => $row->country_name,
                'destination_city' => $row->destination_city
            ];
        }
    }
    
    // Fallback: If no countries found, get just countries without deliveries
    if (empty($countries_data)) {
        $countries_only = $wpdb->get_results("
            SELECT id, country_name, country_code 
            FROM {$wpdb->prefix}kit_operating_countries 
            WHERE is_active = 1 
            ORDER BY country_name ASC");
        
        foreach ($countries_only as $country) {
            $countries_data[$country->id] = [
                'id' => $country->id,
                'name' => $country->country_name,
                'code' => $country->country_code,
                'deliveries' => []
            ];
        }
    }
    
    // Keep backward compatibility for existing code
    $deliveries = $wpdb->get_results("SELECT id, delivery_reference, dispatch_date FROM {$wpdb->prefix}kit_deliveries WHERE status = 'scheduled' ORDER BY dispatch_date ASC");

    // Render quick stats (lightweight)
    $warehouse_stats = [
        [ 'title' => 'In Warehouse', 'value' => intval($stats->in_warehouse ?? 0), 'icon' => 'M9 12l2 2 4-4', 'color' => 'blue' ],
        [ 'title' => 'Assigned', 'value' => intval($stats->assigned ?? 0), 'icon' => 'M13 10V3L4 14', 'color' => 'green' ],
        [ 'title' => 'Shipped', 'value' => intval($stats->shipped ?? 0), 'icon' => 'M3 3h18v4', 'color' => 'yellow' ],
        [ 'title' => 'Delivered', 'value' => intval($stats->delivered ?? 0), 'icon' => 'M5 13l4 4L19 7', 'color' => 'gray' ],
    ];

    echo KIT_QuickStats::render($warehouse_stats, 'Warehouse Overview');
    ?>

    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Assign Warehouse Items to Delivery</h2>
        <form method="post" id="assign-warehouse-form">
            <?php wp_nonce_field('assign_warehouse_items', 'assign_nonce'); ?>
            <!-- Country Selection -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-3">Select Destination Country</label>
                <div class="flex gap-3">
                    <?php if (!empty($countries_data)): ?>
                        <?php foreach ($countries_data as $country): ?>
                            <label class="country-radio-label cursor-pointer">
                                <input type="radio" name="country_id" value="<?php echo esc_attr($country['id']); ?>" 
                                       class="country-radio sr-only" 
                                       data-country-id="<?php echo esc_attr($country['id']); ?>">
                                <div class="country-radio-button">
                                    <span class="country-name"><?php echo esc_html($country['name']); ?></span>
                                    <span class="delivery-count"><?php echo count($country['deliveries']); ?> deliveries</span>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-span-full text-center text-gray-500 py-8">
                            <p>No active countries found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Delivery Selection (Hidden initially) -->
            <div id="delivery-selection" class="hidden mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Delivery</label>
                <div id="delivery-options" class="grid md:grid-cols-4 gap-4">
                    <!-- Deliveries will be populated by JavaScript -->
                </div>
                <input type="hidden" name="delivery_id" id="delivery_id" required>
            </div>
            <!-- Fallback delivery select (in case JS-based selection is not used) -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Or pick a scheduled delivery</label>
                <select name="delivery_id_select" class="min-w-[280px] px-3 py-2 border border-gray-300 rounded">
                    <option value="">Choose delivery…</option>
                    <?php foreach ($deliveries as $d): ?>
                        <option value="<?php echo intval($d->id); ?>"><?php echo esc_html($d->delivery_reference . ' — ' . ($d->dispatch_date ?: 'TBD')); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <div class="flex items-center justify-between mb-2">
                    <label class="block text-sm font-medium text-gray-700">Waybills in Warehouse (from tracking)</label>
                    <div class="flex gap-2">
                        <button type="button" id="select-all" class="px-3 py-1 text-sm border rounded">Select All</button>
                        <button type="button" id="deselect-all" class="px-3 py-1 text-sm border rounded">Deselect All</button>
                    </div>
                </div>
                <?php
                // Convert warehouse items to data array for unified table
                $table_data = [];
                foreach ($tracking_rows as $row) {
                    $table_data[] = [
                        'waybill_id' => $row->waybill_no ?? '',
                        'waybill_no' => $row->waybill_no ?? '',
                        'waybill_db_id' => intval($row->id ?? 0),
                        'customer_name' => trim(($row->customer_name ?? '') . ' ' . ($row->customer_surname ?? '')),
                        'total_mass_kg' => number_format((float)($row->total_mass_kg ?? 0), 2),
                        'action' => ucfirst($row->status ?? ''),
                        'created_at' => $row->last_updated_at ?? $row->created_at ?? ''
                    ];
                }

                // Define columns for the unified table
                $columns = [
                    'checkbox' => [
                        'label' => '',
                        'callback' => function($value, $row) {
                            return '<input type="checkbox" class="wi" name="waybill_ids[]" value="' . intval($row['waybill_db_id']) . '">';
                        }
                    ],
                    'waybill_no' => [
                        'label' => 'Waybill',
                        'callback' => function($value, $row) {
                            return '<a href="' . esc_url(admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . urlencode($row['waybill_db_id'] ?? ''))) . '" class="text-blue-600 hover:underline">#' . esc_html($value) . '</a>';
                        }
                    ],
                    'customer_name' => 'Customer',
                    'total_mass_kg' => 'Weight (kg)', 
                    'action' => 'Current Action',
                    'created_at' => 'Updated'
                ];

                echo KIT_Unified_Table::infinite($table_data, $columns, [
                    'title' => 'Warehouse Items',
                    'searchable' => true,
                    'sortable' => true,
                    'pagination' => true,
                    'items_per_page' => 20,
                    'empty_message' => 'No items currently in warehouse',
                    'class' => 'min-w-full divide-y divide-gray-200'
                ]);
                ?>
            </div>

            <div class="mt-4 flex items-center justify-between">
                <span id="sel-count" class="text-sm text-gray-600">0 selected</span>
                <?php echo KIT_Commons::renderButton('Assign to Dewwlivery', 'primary', 'md', [
                    'type' => 'submit',
                    'name' => 'assign_warehouse_items',
                    'id' => 'assign-btn',
                    'classes' => 'disabled:opacity-50 disabled:cursor-not-allowed',
                    'gradient' => true
                ]); ?>
            </div>
        </form>
    </div>

    <script>
        // Fix WordPress admin menu collapse
        jQuery(document).ready(function($) {
            // Ensure admin menu stays open
            if ($('#adminmenu').hasClass('folded')) {
                $('#adminmenu').removeClass('folded');
            }
        });
        
        document.addEventListener('DOMContentLoaded', function(){
            const headerSelect = document.getElementById('select-all-checkbox');
            const selectAll = document.getElementById('select-all');
            const deselectAll = document.getElementById('deselect-all');
            const boxes = Array.from(document.querySelectorAll('input.wi'));
            const count = document.getElementById('sel-count');
            const assignBtn = document.getElementById('assign-btn');
            const removeBtn = document.getElementById('remove-btn');
            const deliverySelect = document.getElementById('delivery_id');

            function refresh(){
                const n = boxes.filter(b => b.checked).length;
                count.textContent = `${n} selected`;
                assignBtn.disabled = !(n > 0 && deliverySelect && deliverySelect.value);
                if (removeBtn) removeBtn.disabled = !(n > 0);
            }

            if (headerSelect) headerSelect.addEventListener('change', () => {
                const state = headerSelect.checked;
                boxes.forEach(b => b.checked = state);
                refresh();
            });
            if (selectAll) selectAll.addEventListener('click', () => { boxes.forEach(b => b.checked = true); if (headerSelect) headerSelect.checked = true; refresh(); });
            if (deselectAll) deselectAll.addEventListener('click', () => { boxes.forEach(b => b.checked = false); if (headerSelect) headerSelect.checked = false; refresh(); });
            boxes.forEach(b => b.addEventListener('change', refresh));
            if (deliverySelect) deliverySelect.addEventListener('change', refresh);
            refresh();

            // Country selection and delivery population
            const countryRadios = document.querySelectorAll('.country-radio');
            const deliverySelection = document.getElementById('delivery-selection');
            const deliveryOptions = document.getElementById('delivery-options');
            const deliveryIdInput = document.getElementById('delivery_id');
            const deliverySelectFallback = document.querySelector('select[name="delivery_id_select"]');

            // Countries data from PHP
            const countriesData = <?php echo json_encode($countries_data); ?>;

            countryRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        const countryId = parseInt(this.value);
                        const country = countriesData.find(c => c.id === countryId);
                        
                        if (country && country.deliveries) {
                            // Show delivery selection
                            deliverySelection.classList.remove('hidden');
                            
                            // Populate delivery options
                            deliveryOptions.innerHTML = '';
                            country.deliveries.forEach(delivery => {
                                const deliveryOption = document.createElement('div');
                                deliveryOption.className = 'delivery-option';
                                deliveryOption.innerHTML = `
                                    <div class="font-medium text-gray-900">${delivery.delivery_reference}</div>
                                    <div class="text-sm text-gray-500">${delivery.dispatch_date || 'TBD'}</div>
                                    <div class="text-xs text-gray-400">${delivery.waybill_count || 0} waybills</div>
                                `;
                                deliveryOption.addEventListener('click', function() {
                                    // Remove active class from all options
                                    document.querySelectorAll('.delivery-option').forEach(opt => opt.classList.remove('bg-blue-50', 'border-blue-300'));
                                    // Add active class to clicked option
                                    this.classList.add('bg-blue-50', 'border-blue-300');
                                    // Set the delivery ID
                                    deliveryIdInput.value = delivery.id;
                                    // Also update the fallback select
                                    if (deliverySelectFallback) {
                                        deliverySelectFallback.value = delivery.id;
                                    }
                                    refresh();
                                });
                                deliveryOptions.appendChild(deliveryOption);
                            });
                        }
                    }
                });
            });

            // Handle fallback select change
            if (deliverySelectFallback) {
                deliverySelectFallback.addEventListener('change', function() {
                    deliveryIdInput.value = this.value;
                    refresh();
                });
            }
        });
    </script>

    <style>
        /* Fix WordPress admin menu collapse */
        #adminmenu { display: block !important; }
        #adminmenu .wp-submenu { display: block !important; }
        #adminmenu .wp-has-submenu .wp-submenu { display: none; }
        #adminmenu .wp-has-submenu:hover .wp-submenu { display: block; }
        
        /* Country Radio Button Styles */
        .country-radio-label {
            display: block;
        }
        
        .country-radio-button {
            width: 100px;
            height: 100px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 8px;
            transition: all 0.2s ease;
            background: white;
            position: relative;
        }
        
        .country-radio-button:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
        }
        
        .country-radio:checked + .country-radio-button {
            border-color: #3b82f6;
            background: #eff6ff;
            box-shadow: 0 4px 8px rgba(59, 130, 246, 0.2);
        }
        
        .country-radio:checked + .country-radio-button::after {
            content: '✓';
            position: absolute;
            top: 4px;
            right: 4px;
            color: #3b82f6;
            font-weight: bold;
            font-size: 14px;
        }
        
        .country-name {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
            line-height: 1.2;
            margin-bottom: 2px;
        }
        
        .delivery-count {
            font-size: 10px;
            color: #6b7280;
            font-weight: 500;
        }
        
        .delivery-option {
            padding: 12px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }
        
        .delivery-option:hover {
            border-color: #3b82f6;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
            background: #f8fafc;
        }
        
        .delivery-option.bg-blue-50 {
            background-color: #eff6ff;
            border-color: #3b82f6;
        }
        
        .delivery-option.selected {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .delivery-option input[type="radio"] {
            margin-right: 8px;
        }
    </style>

    <script>
        // Countries and deliveries data from PHP
        const countriesData = <?php echo json_encode($countries_data); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            const countryRadios = document.querySelectorAll('.country-radio');
            const deliverySelection = document.getElementById('delivery-selection');
            const deliveryOptions = document.getElementById('delivery-options');
            const deliveryIdInput = document.getElementById('delivery_id');
            const assignBtn = document.getElementById('assign-btn');
            const boxes = Array.from(document.querySelectorAll('input.wi'));
            
            function refreshAssignButton() {
                const selectedBoxes = boxes.filter(b => b.checked).length;
                const selectedDelivery = deliveryIdInput.value;
                assignBtn.disabled = !(selectedBoxes > 0 && selectedDelivery);
            }
            
            // Handle country selection
            countryRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        const countryId = this.value;
                        const country = countriesData[countryId];
                        
                        // Show delivery selection
                        deliverySelection.classList.remove('hidden');
                        
                        // Clear previous delivery options
                        deliveryOptions.innerHTML = '';
                        
                        if (country.deliveries.length > 0) {
                            // Create delivery options
                            country.deliveries.forEach(delivery => {
                                const deliveryOption = document.createElement('label');
                                deliveryOption.className = 'delivery-option flex items-center cursor-pointer';
                                deliveryOption.innerHTML = `
                                    <input type="radio" name="delivery_radio" value="${delivery.id}" class="delivery-radio">
                                    <div class="flex-1">
                                        <div class="font-medium text-gray-900">${delivery.reference}</div>
                                        <div class="text-sm text-gray-500">
                                            ${delivery.dispatch_date ? new Date(delivery.dispatch_date).toLocaleDateString() : 'TBD'} 
                                            ${delivery.truck_number ? '• Truck: ' + delivery.truck_number : ''}
                                        </div>
                                        <div class="text-xs mt-1">
                                            ${delivery.destination_country ? '<span class="text-gray-600">' + delivery.destination_country + '</span>' : '<span class="text-red-600 font-semibold">Missing destination country</span>'}
                                            ${delivery.destination_city ? '<span class="text-gray-600"> • ' + delivery.destination_city + '</span>' : '<span class="text-red-600 font-semibold"> • Missing city</span>'}
                                        </div>
                                    </div>
                                `;
                                
                                deliveryOption.addEventListener('click', function() {
                                    // Remove selected class from all options
                                    document.querySelectorAll('.delivery-option').forEach(opt => opt.classList.remove('selected'));
                                    // Add selected class to clicked option
                                    this.classList.add('selected');
                                    // Set the hidden input value
                                    deliveryIdInput.value = delivery.id;
                                    // Refresh assign button state
                                    refreshAssignButton();
                                });
                                
                                deliveryOptions.appendChild(deliveryOption);
                            });
                        } else {
                            deliveryOptions.innerHTML = '<div class="text-center text-gray-500 py-4">No scheduled deliveries for this country</div>';
                        }
                        
                        // Clear delivery selection
                        deliveryIdInput.value = '';
                        refreshAssignButton();
                    }
                });
            });
            
            // Handle waybill selection changes
            boxes.forEach(box => {
                box.addEventListener('change', refreshAssignButton);
            });
            
            // Initial state
            refreshAssignButton();
        });
    </script>

    <?php return; ?>

    <?php
    // Get statistics for the warehouse
    $total_warehouse_waybills = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_waybills WHERE status = 'pending'");
    $total_deliveries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_deliveries");
    $scheduled_deliveries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_deliveries WHERE status = 'scheduled'");
    $in_transit_deliveries = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_deliveries WHERE status = 'in_transit'");
    $countries_served = $wpdb->get_var(
        "SELECT COUNT(DISTINCT sd.destination_country_id)
             FROM {$wpdb->prefix}kit_deliveries d
             LEFT JOIN {$wpdb->prefix}kit_shipping_directions sd ON d.direction_id = sd.id"
    );

    $warehouse_stats = [
        [
            'title' => 'Total Deliveries',
            'value' => $total_deliveries,
            'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            'color' => 'blue'
        ],
        [
            'title' => 'Scheduled',
            'value' => $scheduled_deliveries,
            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'color' => 'green'
        ],
        [
            'title' => 'In Transit',
            'value' => $in_transit_deliveries,
            'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
            'color' => 'yellow'
        ],
        [
            'title' => 'Countries Served',
            'value' => $countries_served,
            'icon' => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'color' => 'gray'
        ]
    ];

    echo KIT_QuickStats::render($warehouse_stats, 'Warehouse Overview');
    ?>


    <!-- Overview Tab Content -->
    <div id="overview-content">
        <!-- Quick Stats Section -->
        <?php
        // Country filter and delivery squares component
        require_once plugin_dir_path(__FILE__) . '../components/deliverySquare.php';
        $countries_for_filter = $wpdb->get_results("SELECT id, country_name FROM {$wpdb->prefix}kit_operating_countries WHERE is_active = 1 ORDER BY country_name");
        ?>
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
            <label for="warehouse-country-filter" class="text-sm text-gray-600 mr-2">Destination Country</label>
            <select id="warehouse-country-filter" class="px-3 py-2 border border-gray-300 rounded-md text-sm">
                <option value="">Select a country…</option>
                <?php foreach ($countries_for_filter as $c): ?>
                    <!-- Use country name as value because the AJAX filter expects the name -->
                    <option value="<?php echo esc_attr($c->country_name); ?>"><?php echo esc_html($c->country_name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="country-deliveries" class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-8 gap-3 mb-6"></div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sel = document.getElementById('warehouse-country-filter');
                const container = document.getElementById('country-deliveries');

                function asHtml(items) {
                    if (!items || !items.length) {
                        container.innerHTML = '<div class="text-gray-500">No deliveries scheduled. Create one on the Deliveries page.</div>';
                        return;
                    }
                    let html = '<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">';
                    items.forEach(d => {
                        html += '<div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">' +
                            '<div class="text-center text-sm text-gray-700">' + (d.title || '') + '</div>' +
                            '<div class="text-center font-semibold text-gray-900 mt-2">' + (d.date || '') + '</div>' +
                            '<div class="text-center text-xs text-gray-500 mt-1">' + (d.status || '') + '</div>' +
                            '</div>';
                    });
                    html += '</div>';
                    container.innerHTML = html;
                }

                function load(countryVal) {
                    if (!countryVal) {
                        container.innerHTML = '';
                        return;
                    }
                    const form = new FormData();
                    form.append('action', 'get_deliveries_by_country');
                    // Backend expects 'country' (country name) and a nonce named 'deliveries_nonce'
                    form.append('country', countryVal);
                    form.append('nonce', '<?php echo wp_create_nonce('deliveries_nonce'); ?>');
                    fetch(ajaxurl, {
                            method: 'POST',
                            body: form
                        })
                        .then(r => r.json()).then(data => {
                            if (data && data.success && data.data && data.data.html) {
                                // Inject delivery cards
                                container.innerHTML = data.data.html;

                                // Wire up delivery selection to hidden field inside the assignment form
                                const hiddenDeliveryInput = document.getElementById('delivery_id');
                                const radios = container.querySelectorAll('input[name="delivery_id"]');

                                // If there are radios, select the first one by default and sync value
                                if (radios && radios.length) {
                                    if (![...radios].some(r => r.checked)) {
                                        radios[0].checked = true;
                                    }
                                    const checked = [...radios].find(r => r.checked);
                                    if (hiddenDeliveryInput && checked) {
                                        hiddenDeliveryInput.value = checked.value;
                                    }
                                }

                                // Delegate change handler so choosing a different delivery updates the hidden input
                                container.addEventListener('change', function(e) {
                                    if (e.target && e.target.name === 'delivery_id') {
                                        if (hiddenDeliveryInput) {
                                            hiddenDeliveryInput.value = e.target.value;
                                        }
                                        // Update assign button enabled state/counts
                                        if (typeof updateSelectedCount === 'function') {
                                            updateSelectedCount();
                                        }
                                    }
                                });

                                // Recompute state of Assign button after load
                                if (typeof updateSelectedCount === 'function') {
                                    updateSelectedCount();
                                }
                            } else {
                                asHtml([]);
                            }
                        }).catch(() => asHtml([]));
                }
                if (sel) {
                    sel.addEventListener('change', () => load(sel.value));
                }
            });
        </script>


        <!-- Hidden form for sample data creation -->
        <?php 
      
        if ($total_warehouse_waybills == 0): ?>
            <form method="post" id="create-sample-form" style="display: none;">
                <?php wp_nonce_field('create_sample_warehouse_waybills', 'sample_nonce'); ?>
                <input type="hidden" name="create_sample_warehouse" value="1">
                <input type="number" name="waybill_count" value="10" min="1" max="50">
            </form>
        <?php endif; ?>

        <!-- Assignment Section (Block View) -->
        <div id="block-view" class="assignment-section bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
            <?php if ($total_warehouse_waybills > 0): ?>
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center gap-4">
                        <h2 class="text-xl font-semibold text-gray-900">Warehouse Waybills</h2>
                        <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                            <?php echo $total_warehouse_waybills; ?> Available
                        </span>
                    </div>
                    <a href="?page=08600-waybill-create" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Create New
                    </a>
                </div>
                <!-- Country-first: destination country filter + delivery squares (new feature) already added above -->
                <form method="post" id="assign-waybills-form">
                    <?php wp_nonce_field('assign_waybills', 'nonce'); ?>
                    <input type="hidden" name="delivery_id" id="delivery_id" value="" />

                    <!-- Waybills Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            Select Waybills to Assign
                        </label>
                        <div class="bg-gray-50 rounded-lg p-4 max-h-96 overflow-y-auto">
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                <?php foreach ($warehouse_waybills as $waybill): ?>
                                    <div class="waybill-card bg-white rounded-md border border-gray-200 p-3 hover:shadow-sm transition-all duration-200">
                                        <label class="flex items-start gap-3 cursor-pointer">
                                            <input type="checkbox" name="waybill_ids[]" value="<?php echo $waybill->id; ?>"
                                                class="waybill-checkbox mt-1 w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex justify-between items-start mb-2">
                                                    <strong class="text-gray-900 text-sm font-medium truncate">
                                                        #<?php echo $waybill->waybill_no; ?>
                                                    </strong>
                                                    <?php if (KIT_User_Roles::can_see_prices()): ?>
                                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap">
                                                            R<?php echo number_format($waybill->product_invoice_amount, 2); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap">
                                                            ***
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-gray-600 text-sm mb-1 truncate">
                                                    <?php echo esc_html($waybill->customer_name . ' ' . $waybill->customer_surname); ?>
                                                </div>
                                                <?php if ($waybill->company_name): ?>
                                                    <div class="text-gray-500 text-xs mb-1 truncate">
                                                        <?php echo esc_html($waybill->company_name); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="flex gap-2 text-gray-400 text-xs">
                                                    <span><?php echo $waybill->total_mass_kg; ?>kg</span>
                                                    <span>•</span>
                                                    <span><?php echo date('M j', strtotime($waybill->created_at)); ?></span>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                        <div class="flex items-center gap-4">
                            <?php echo KIT_Commons::renderButton('Select All', 'ghost-primary', 'sm', ['id' => 'select-all-btn', 'type' => 'button']); ?>
                            <?php echo KIT_Commons::renderButton('Deselect All', 'ghost', 'sm', ['id' => 'deselect-all-btn', 'type' => 'button']); ?>
                            <span id="selected-count" class="text-gray-500 text-sm">0 waybills selected</span>
                        </div>

                        <?php echo KIT_Commons::renderButton('Assi22gn to Delivery', 'primary', 'md', [
                            'type' => 'submit',
                            'name' => 'assign_waybills',
                            'id' => 'assign-btn',
                            'classes' => 'disabled:opacity-50 disabled:cursor-not-allowed',
                            'gradient' => true
                        ]); ?>
                    </div>
                </form>
        </div>
    <?php else: ?>
        <!-- Empty State for Block View -->
        <div style="text-align: center; padding: 60px 20px;">
            <div style="background: #f9fafb; border-radius: 12px; padding: 40px; max-width: 500px; margin: 0 auto;">
                <h3 style="color: #374151; font-size: 20px; margin-bottom: 12px; font-weight: 600;">No Waybills in Warehouse</h3>
                <p style="color: #6b7280; margin-bottom: 24px; font-size: 16px;">There are currently no waybills in the warehouse ready for assignment.</p>
                <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                    <a href="?page=08600-waybill-create" style="background: #2563eb; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.2s ease;">
                        Create New Waybill
                    </a>
                    <a href="?page=08600-waybill-manage" style="background: #f3f4f6; color: #374151; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 500; border: 1px solid #d1d5db; transition: all 0.2s ease;">
                        View All Waybills
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <?php // Removed legacy KIT_QuickActions render to prevent fatal error 
    ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllBtn = document.getElementById('select-all-btn');
            const deselectAllBtn = document.getElementById('deselect-all-btn');
            const selectedCountSpan = document.getElementById('selected-count');
            const assignBtn = document.getElementById('assign-btn');
            const deliverySelect = document.getElementById('delivery_id');
            const waybillCheckboxes = document.querySelectorAll('input[name="waybill_ids[]"]');

            if (!selectAllBtn || !deselectAllBtn || !selectedCountSpan || !assignBtn || !deliverySelect) {
                return; // Exit if elements don't exist
            }

            // Update selected count
            function updateSelectedCount() {
                const selectedCount = document.querySelectorAll('input[name="waybill_ids[]"]:checked').length;
                selectedCountSpan.textContent = `${selectedCount} waybill${selectedCount !== 1 ? 's' : ''} selected`;

                // Enable/disable assign button
                const hasSelection = selectedCount > 0;
                const hasDelivery = deliverySelect.value !== '';
                assignBtn.disabled = !hasSelection || !hasDelivery;

                if (assignBtn.disabled) {
                    assignBtn.style.opacity = '0.5';
                    assignBtn.style.cursor = 'not-allowed';
                } else {
                    assignBtn.style.opacity = '1';
                    assignBtn.style.cursor = 'pointer';
                }

                // Debug logging
                console.log('Selected count:', selectedCount);
                console.log('Has selection:', hasSelection);
                console.log('Has delivery:', hasDelivery);
                console.log('Button disabled:', assignBtn.disabled);
            }

            // Select all waybills
            selectAllBtn.addEventListener('click', function() {
                waybillCheckboxes.forEach(checkbox => {
                    checkbox.checked = true;
                });
                updateSelectedCount();
            });

            // Deselect all waybills
            deselectAllBtn.addEventListener('click', function() {
                waybillCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
                updateSelectedCount();
            });

            // Listen for checkbox changes
            waybillCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    console.log('Checkbox changed:', this.checked, this.value);
                    updateSelectedCount();
                });
            });

            // Listen for delivery selection changes
            deliverySelect.addEventListener('change', updateSelectedCount);

            // Form validation
            const form = document.getElementById('assign-waybills-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const selectedWaybills = document.querySelectorAll('input[name="waybill_ids[]"]:checked');
                    const deliveryId = deliverySelect.value;

                    if (selectedWaybills.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one waybill to assign.');
                        return;
                    }

                    if (!deliveryId) {
                        e.preventDefault();
                        alert('Please select a delivery.');
                        return;
                    }

                    // Confirm assignment
                    if (!confirm(`Are you sure you want to assign ${selectedWaybills.length} waybill(s) to this delivery?`)) {
                        e.preventDefault();
                        return;
                    }
                });
            }

            // Initial count update
            updateSelectedCount();

            // View switching functionality
            const blockViewBtn = document.getElementById('block-view-btn');
            const tableViewBtn = document.getElementById('table-view-btn');
            const blockView = document.getElementById('block-view');
            const tableView = document.getElementById('table-view');

            console.log('Elements found:', {
                blockViewBtn: !!blockViewBtn,
                tableViewBtn: !!tableViewBtn,
                blockView: !!blockView,
                tableView: !!tableView
            });

            if (blockViewBtn && tableViewBtn && blockView && tableView) {
                // Set initial state - block view should be visible, table view hidden
                blockView.style.display = 'block';
                tableView.style.display = 'none';
                blockViewBtn.classList.add('active');
                blockViewBtn.style.background = 'white';
                blockViewBtn.style.color = '#374151';
                blockViewBtn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
                tableViewBtn.classList.remove('active');
                tableViewBtn.style.background = 'transparent';
                tableViewBtn.style.color = '#6b7280';
                tableViewBtn.style.boxShadow = 'none';

                blockViewBtn.addEventListener('click', function() {
                    console.log('Block view clicked');
                    blockView.style.display = 'block';
                    tableView.style.display = 'none';
                    blockViewBtn.classList.add('active');
                    blockViewBtn.style.background = 'white';
                    blockViewBtn.style.color = '#374151';
                    blockViewBtn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
                    tableViewBtn.classList.remove('active');
                    tableViewBtn.style.background = 'transparent';
                    tableViewBtn.style.color = '#6b7280';
                    tableViewBtn.style.boxShadow = 'none';
                });

                tableViewBtn.addEventListener('click', function() {
                    console.log('Table view clicked');
                    blockView.style.display = 'none';
                    tableView.style.display = 'block';
                    tableViewBtn.classList.add('active');
                    tableViewBtn.style.background = 'white';
                    tableViewBtn.style.color = '#374151';
                    tableViewBtn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
                    blockViewBtn.classList.remove('active');
                    blockViewBtn.style.background = 'transparent';
                    blockViewBtn.style.color = '#6b7280';
                    blockViewBtn.style.boxShadow = 'none';
                });
            }

            // Table view functionality
            const selectAllTableBtn = document.getElementById('select-all-table-btn');
            const deselectAllTableBtn = document.getElementById('deselect-all-table-btn');
            const selectedCountTableSpan = document.getElementById('selected-count-table');
            const assignTableBtn = document.getElementById('assign-table-btn');
            const deliverySelectTable = document.getElementById('delivery_id_table');
            const waybillCheckboxesTable = document.querySelectorAll('.waybill-checkbox-table');
            const selectAllTableCheckbox = document.getElementById('select-all-table');

            if (selectAllTableBtn && deselectAllTableBtn && selectedCountTableSpan && assignTableBtn && deliverySelectTable) {
                // Update selected count for table view
                function updateSelectedCountTable() {
                    const selectedCount = document.querySelectorAll('.waybill-checkbox-table:checked').length;
                    selectedCountTableSpan.textContent = `${selectedCount} waybill${selectedCount !== 1 ? 's' : ''} selected`;

                    // Enable/disable assign button
                    const hasSelection = selectedCount > 0;
                    const hasDelivery = deliverySelectTable.value !== '';
                    assignTableBtn.disabled = !hasSelection || !hasDelivery;

                    if (assignTableBtn.disabled) {
                        assignTableBtn.style.opacity = '0.5';
                        assignTableBtn.style.cursor = 'not-allowed';
                    } else {
                        assignTableBtn.style.opacity = '1';
                        assignTableBtn.style.cursor = 'pointer';
                    }
                }

                // Select all waybills in table view
                selectAllTableBtn.addEventListener('click', function() {
                    waybillCheckboxesTable.forEach(checkbox => {
                        checkbox.checked = true;
                    });
                    if (selectAllTableCheckbox) selectAllTableCheckbox.checked = true;
                    updateSelectedCountTable();
                });

                // Deselect all waybills in table view
                deselectAllTableBtn.addEventListener('click', function() {
                    waybillCheckboxesTable.forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    if (selectAllTableCheckbox) selectAllTableCheckbox.checked = false;
                    updateSelectedCountTable();
                });

                // Listen for checkbox changes in table view
                waybillCheckboxesTable.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        console.log('Table checkbox changed:', this.checked, this.value);
                        updateSelectedCountTable();
                    });
                });

                // Listen for delivery selection changes in table view
                deliverySelectTable.addEventListener('change', updateSelectedCountTable);

                // Select all checkbox in table header
                if (selectAllTableCheckbox) {
                    selectAllTableCheckbox.addEventListener('change', function() {
                        waybillCheckboxesTable.forEach(checkbox => {
                            checkbox.checked = this.checked;
                        });
                        updateSelectedCountTable();
                    });
                }

                // Form validation for table view
                const tableForm = document.getElementById('assign-waybills-table-form');
                if (tableForm) {
                    tableForm.addEventListener('submit', function(e) {
                        const selectedWaybills = document.querySelectorAll('.waybill-checkbox-table:checked');
                        const deliveryId = deliverySelectTable.value;

                        if (selectedWaybills.length === 0) {
                            e.preventDefault();
                            alert('Please select at least one waybill to assign.');
                            return;
                        }

                        if (!deliveryId) {
                            e.preventDefault();
                            alert('Please select a delivery.');
                            return;
                        }

                        // Confirm assignment
                        if (!confirm(`Are you sure you want to assign ${selectedWaybills.length} waybill(s) to this delivery?`)) {
                            e.preventDefault();
                            return;
                        }
                    });
                }

                // Initial count update for table view
                updateSelectedCountTable();
            }
        });

        // Tab switching functionality
        function switchWarehouseTab(tabName) {
            // Hide all tab contents and remove any inline display styles
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                content.classList.remove('is-visible');
                content.style.removeProperty('display');
            });

            // Remove active state from all tab buttons
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(btn => {
                btn.classList.remove('active');
                btn.style.background = 'transparent';
                btn.style.color = '#6b7280';
                btn.style.boxShadow = 'none';
            });

            // Show selected tab content
            const selectedContent = document.getElementById(tabName + '-content');
            if (selectedContent) {
                selectedContent.classList.add('is-visible');
                selectedContent.style.removeProperty('display');
            }

            // Mark selected button as active with proper styling
            const selectedButton = document.getElementById(tabName + '-tab');
            if (selectedButton) {
                selectedButton.classList.add('active');
                selectedButton.style.background = 'white';
                selectedButton.style.color = '#374151';
                selectedButton.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
            }
        }

        // Initialize tabs and set default from URL (?tab=overview|tracking|suggest)
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabName = this.id.replace('-tab', '');
                    switchWarehouseTab(tabName);
                });
            });

            // Default tab via URL param `tab`, fallback to overview
            try {
                const url = new URL(window.location.href);
                const initialTab = url.searchParams.get('tab') || 'overview';
                switchWarehouseTab(initialTab);
            } catch (e) {
                switchWarehouseTab('overview');
            }
        });
    </script>

    <!-- Suggest Tab Content -->
    <div id="suggest-content" class="tab-content">
        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
            <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 20px 0;">Warehouse Suggestions</h3>
            <div class="text-center py-12">
                <div class="text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Suggestions Coming Soon</h3>
                    <p class="mt-1 text-sm text-gray-500">Intelligent warehouse optimization suggestions will be available here.</p>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- Warehouse Tracking Tab Content -->
    <div id="tracking-content" class="tab-content">
        <?php
        // Build actions log from waybills (no separate tracking table)
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $customers_table = $wpdb->prefix . 'kit_customers';
        $deliveries_table = $wpdb->prefix . 'kit_deliveries';
        $users_table = $wpdb->users;

        $query = "
            SELECT 
                w.id,
                w.waybill_no,
                w.product_invoice_amount,
                w.total_mass_kg,
                w.status,
                w.created_at,
                w.last_updated_at,
                w.created_by,
                w.last_updated_by,
                c.name as customer_name,
                c.surname as customer_surname,
                c.company_name,
                d.delivery_reference,
                ub.display_name as action_by
            FROM $waybills_table w
            LEFT JOIN $customers_table c ON w.customer_id = c.cust_id
            LEFT JOIN $deliveries_table d ON w.delivery_id = d.id
            LEFT JOIN $users_table ub ON w.last_updated_by = ub.ID
            WHERE w.status IN ('pending','assigned','shipped','delivered')
            ORDER BY w.created_at DESC
        ";

        $tracking_data = $wpdb->get_results($query);
        ?>

        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
            <div class="flex items-center justify-between mb-6">
                <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0;">Warehouse Actions Log</h3>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                        <?php echo count($tracking_data); ?> Actions
                    </span>
                </div>
            </div>

            <?php if (!empty($tracking_data)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date/Time
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Waybill
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Customer
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status Change
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Delivery
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action By
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Notes
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($tracking_data as $record): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y g:i A', strtotime($record->created_at)); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            #<?php echo $record->waybill_no; ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            R<?php echo number_format($record->product_invoice_amount, 2); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo esc_html($record->customer_name . ' ' . $record->customer_surname); ?>
                                        </div>
                                        <?php if ($record->company_name): ?>
                                            <div class="text-sm text-gray-500">
                                                <?php echo esc_html($record->company_name); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $action_colors = [
                                            'pending' => 'bg-blue-100 text-blue-800',
                                            'assigned' => 'bg-green-100 text-green-800',
                                            'removed' => 'bg-red-100 text-red-800'
                                        ];
                                        $color_class = $action_colors[$record->action] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $color_class; ?>">
                                            <?php echo ucfirst($record->action); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if ($record->previous_status && $record->new_status): ?>
                                            <div class="text-sm">
                                                <span class="text-gray-500"><?php echo ucfirst($record->previous_status); ?></span>
                                                <span class="mx-1">→</span>
                                                <span class="font-medium"><?php echo ucfirst($record->new_status); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if ($record->delivery_reference): ?>
                                            <span class="font-medium"><?php echo esc_html($record->delivery_reference); ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo esc_html($record->action_by); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php if ($record->notes): ?>
                                            <span class="text-gray-600"><?php echo esc_html($record->notes); ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No tracking records</h3>
                        <p class="mt-1 text-sm text-gray-500">No warehouse actions have been recorded yet.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Warehouse Tracking Tab Content -->
    <div id="tracking-content" class="tab-content">
        <?php
        // Build actions log from waybills (no separate tracking table)
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $customers_table = $wpdb->prefix . 'kit_customers';
        $deliveries_table = $wpdb->prefix . 'kit_deliveries';
        $users_table = $wpdb->users;

        $query = "
            SELECT 
                w.id,
                w.waybill_no,
                w.product_invoice_amount,
                w.total_mass_kg,
                w.status,
                w.created_at,
                w.last_updated_at,
                w.created_by,
                w.last_updated_by,
                c.name as customer_name,
                c.surname as customer_surname,
                c.company_name,
                d.delivery_reference,
                ub.display_name as action_by
            FROM $waybills_table w
            LEFT JOIN $customers_table c ON w.customer_id = c.cust_id
            LEFT JOIN $deliveries_table d ON w.delivery_id = d.id
            LEFT JOIN $users_table ub ON w.last_updated_by = ub.ID
            WHERE w.status IN ('pending','assigned','shipped','delivered')
            ORDER BY w.created_at DESC
        ";

        $tracking_data = $wpdb->get_results($query);
        ?>

        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
            <div class="flex items-center justify-between mb-6">
                <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0;">Warehouse Actions Log</h3>
                <div class="flex items-center gap-4">
                    <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 text-sm font-medium rounded-full">
                        <?php echo count($tracking_data); ?> Actions
                    </span>
                </div>
            </div>

            <?php if (!empty($tracking_data)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date/Time
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Waybill
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Customer
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status Change
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Delivery
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Action By
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Notes
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($tracking_data as $record): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo date('M j, Y g:i A', strtotime($record->created_at)); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            #<?php echo $record->waybill_no; ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            R<?php echo number_format($record->product_invoice_amount, 2); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo esc_html($record->customer_name . ' ' . $record->customer_surname); ?>
                                        </div>
                                        <?php if ($record->company_name): ?>
                                            <div class="text-sm text-gray-500">
                                                <?php echo esc_html($record->company_name); ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $action_colors = [
                                            'pending' => 'bg-blue-100 text-blue-800',
                                            'assigned' => 'bg-green-100 text-green-800',
                                            'removed' => 'bg-red-100 text-red-800'
                                        ];
                                        $color_class = $action_colors[$record->action] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $color_class; ?>">
                                            <?php echo ucfirst($record->action); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if ($record->previous_status && $record->new_status): ?>
                                            <div class="text-sm">
                                                <span class="text-gray-500"><?php echo ucfirst($record->previous_status); ?></span>
                                                <span class="mx-1">→</span>
                                                <span class="font-medium"><?php echo ucfirst($record->new_status); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if ($record->delivery_reference): ?>
                                            <span class="font-medium"><?php echo esc_html($record->delivery_reference); ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo esc_html($record->action_by); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php if ($record->notes): ?>
                                            <span class="text-gray-600"><?php echo esc_html($record->notes); ?></span>
                                        <?php else: ?>
                                            <span class="text-gray-500">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-12">
                    <div class="text-gray-500">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No tracking records</h3>
                        <p class="mt-1 text-sm text-gray-500">No warehouse actions have been recorded yet.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>


    <style>
        /* Tab styling */
        .tab-btn:hover {
            background: #e5e7eb !important;
            color: #374151 !important;
        }

        .tab-btn.active {
            background: white !important;
            color: #374151 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
        }

        .tab-content {
            display: none;
        }

        .tab-content.is-visible {
            display: block;
        }
    </style>
</div>