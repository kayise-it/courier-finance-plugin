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
// Include modal component
require_once plugin_dir_path(__FILE__) . '../components/modal.php';
// Include deliveries functions for delivery form
require_once plugin_dir_path(__FILE__) . '../deliveries/deliveries-functions.php';
// Ensure unified table class is available for rendering tables
if (!class_exists('KIT_Unified_Table')) {
    $unified_path = plugin_dir_path(__FILE__) . '../class-unified-table.php';
    if (file_exists($unified_path)) {
        require_once $unified_path;
    }
}

// Handle form submission for assignment
try {
    if (isset($_POST['assign_warehouse_items'])) {
        error_log('=== WAREHOUSE ASSIGNMENT POST RECEIVED ===');
        error_log('POST data keys: ' . implode(', ', array_keys($_POST)));

        // Verify nonce
        if (!isset($_POST['assign_nonce']) || !wp_verify_nonce($_POST['assign_nonce'], 'assign_warehouse_items')) {
            error_log('ERROR: Invalid or missing nonce');
            if (!class_exists('KIT_Toast')) {
                require_once plugin_dir_path(__FILE__) . '../components/toast.php';
            }
            KIT_Toast::ensure_toast_loads();
            echo KIT_Toast::error('Security verification failed. Please refresh the page and try again.', 'Error');
            exit;
        }

        error_log('Nonce verified successfully');

        global $wpdb;

        // Extract waybill IDs
        $waybill_ids = [];
        if (isset($_POST['waybill_ids'])) {
            $waybill_ids = is_array($_POST['waybill_ids']) ? $_POST['waybill_ids'] : [$_POST['waybill_ids']];
        }
        error_log('Waybill IDs received: ' . print_r($waybill_ids, true));
        error_log('Waybill IDs count: ' . count($waybill_ids));

        // Accept delivery_id from hidden field or fallback select
        $delivery_id = 0;
        if (isset($_POST['delivery_id']) && $_POST['delivery_id'] !== '') {
            $delivery_id = intval($_POST['delivery_id']);
            error_log('Delivery ID from hidden field: ' . $delivery_id);
        } elseif (isset($_POST['delivery_id_select']) && $_POST['delivery_id_select'] !== '') {
            $delivery_id = intval($_POST['delivery_id_select']);
            error_log('Delivery ID from dropdown: ' . $delivery_id);
        } else {
            error_log('ERROR: No delivery ID found in POST data');
        }
        // #region agent log - DISABLED
        // Logging temporarily disabled
        // #endregion

        // Load toast component for error messages
        if (!class_exists('KIT_Toast')) {
            require_once plugin_dir_path(__FILE__) . '../components/toast.php';
        }
        KIT_Toast::ensure_toast_loads();

        // Validation with specific error messages
        // #region agent log - DISABLED
        // Temporarily disabled logging due to PHP syntax issues
        // #endregion
        if (empty($waybill_ids)) {
            echo KIT_Toast::error('No waybills selected. Please select at least one waybill to assign.', 'Error');
        } elseif ($delivery_id <= 0) {
            echo KIT_Toast::error('No delivery selected. Please select a delivery from the dropdown.', 'Error');
        } else {
            // #region agent log
            // Logging disabled
            // Logging disabled
            $updated = 0;
            $errors = [];
            $assigned_waybill_nos = [];

            foreach ($waybill_ids as $waybill_id) {
                try {
                    $waybill_id = intval($waybill_id);
                    error_log('Processing waybill ID: ' . $waybill_id);

                    if ($waybill_id <= 0) {
                        error_log('WARNING: Invalid waybill ID skipped: ' . $waybill_id);
                        continue; // Skip invalid IDs
                    }

                    // Check if waybill exists
                    $waybill_check = $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}kit_waybills WHERE id = %d",
                        $waybill_id
                    ));

                    if (!$waybill_check) {
                        error_log('ERROR: Waybill ID ' . $waybill_id . ' does not exist in database');
                        $errors[] = 'Waybill ID ' . $waybill_id . ': Waybill not found in database';
                        continue;
                    }

                    error_log('Waybill exists, attempting assignment to delivery ' . $delivery_id);

                    // Use the new warehouse system to assign waybills
                    $result = KIT_Warehouse::assignToDelivery($waybill_id, $delivery_id, get_current_user_id());

                    if (is_wp_error($result)) {
                        $error_msg = $result->get_error_message();
                        error_log('ERROR assigning waybill ' . $waybill_id . ': ' . $error_msg);
                        $errors[] = 'Waybill ID ' . $waybill_id . ': ' . $error_msg;
                    } else {
                        error_log('SUCCESS: Waybill ' . $waybill_id . ' assigned to delivery ' . $delivery_id);
                        $updated++;

                        // Get waybill details for logging
                        $waybill = $wpdb->get_row($wpdb->prepare(
                            "SELECT waybill_no, customer_id FROM {$wpdb->prefix}kit_waybills WHERE id = %d",
                            $waybill_id
                        ));

                        if ($waybill && !empty($waybill->waybill_no)) {
                            $assigned_waybill_nos[] = (int)$waybill->waybill_no;
                            error_log('Assigned waybill number: ' . $waybill->waybill_no);
                        }
                    }
                } catch (Exception $e) {
                    error_log('EXCEPTION processing waybill ' . $waybill_id . ': ' . $e->getMessage());
                    error_log('Stack trace: ' . $e->getTraceAsString());
                    $errors[] = 'Waybill ID ' . $waybill_id . ': Exception - ' . $e->getMessage();
                }
            }

            if ($updated > 0) {
                $message = 'Successfully assigned ' . $updated . ' waybill(s) to delivery!';
                if (!empty($errors)) {
                    $message .= ' (' . count($errors) . ' failed)';
                }
                // Show a success toast; let the user decide when to refresh.
                echo KIT_Toast::success($message, 'Success');
            } else {
                $error_msg = 'Failed to assign waybills. ';
                if (!empty($errors)) {
                    $error_msg .= 'Errors: ' . implode(', ', array_slice($errors, 0, 3));
                    if (count($errors) > 3) {
                        $error_msg .= ' (and ' . (count($errors) - 3) . ' more)';
                    }
                } else {
                    $error_msg .= 'Please check that the waybills and delivery are valid.';
                }
                echo KIT_Toast::error($error_msg, 'Error');
            }
        }
    }
} catch (Exception $e) {
    error_log('EXCEPTION in warehouse assignment handler: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    if (!class_exists('KIT_Toast')) {
        require_once plugin_dir_path(__FILE__) . '../components/toast.php';
    }
    KIT_Toast::ensure_toast_loads();
    echo KIT_Toast::error('An unexpected error occurred: ' . $e->getMessage(), 'Error');
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
?>

<div class="wrap">
    <div class="<?php echo KIT_Commons::containerClasses(); ?>">
        <?php
        echo KIT_Commons::showingHeader([
            'title' => 'Warehouse Management',
            'desc' => 'Manage and track all warehouse operations',
            'icon' => KIT_Commons::icon('warehouse'),
        ]);
        ?>
        <hr class="wp-header-end">

        <?php
        // Data for page
        $stats = KIT_Warehouse::getWarehouseStats();

        // Fetch warehouse waybills to display/assign
        $tracking_rows = KIT_Warehouse::getWarehouseItems();

        // Optimized query: Get all countries and their scheduled deliveries in one query
        // Check if drivers table and driver_id column exist
        $drivers_table = $wpdb->prefix . 'kit_drivers';
        $drivers_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$drivers_table'");
        $driver_id_exists = false;
        if ($drivers_table_exists) {
            $driver_id_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'driver_id'",
                DB_NAME,
                $wpdb->prefix . 'kit_deliveries'
            ));
        }

        $driver_join = "";
        $driver_select = "";
        if ($drivers_table_exists && $driver_id_exists) {
            $driver_join = "LEFT JOIN {$drivers_table} dr ON d.driver_id = dr.id";
            $driver_select = ", dr.name AS driver_name";
        }

        $waybills_table = $wpdb->prefix . 'kit_waybills';
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
            dest.city_name AS destination_city,
            COALESCE(wb.waybill_count, 0) AS waybill_count
            {$driver_select}
        FROM {$wpdb->prefix}kit_operating_countries oc
        LEFT JOIN {$wpdb->prefix}kit_shipping_directions sd ON oc.id = sd.destination_country_id
        LEFT JOIN {$wpdb->prefix}kit_deliveries d ON sd.id = d.direction_id AND d.status = 'scheduled'
        LEFT JOIN {$wpdb->prefix}kit_operating_cities dest ON d.destination_city_id = dest.id
        LEFT JOIN (
            SELECT delivery_id, COUNT(*) AS waybill_count
            FROM {$waybills_table}
            GROUP BY delivery_id
        ) wb ON wb.delivery_id = d.id
        {$driver_join}
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
                    'destination_city' => $row->destination_city,
                    'driver_name' => $row->driver_name ?? null,
                    'waybill_count' => intval($row->waybill_count ?? 0)
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

        // Convert associative array to numeric array for JSON encoding
        $countries_data = array_values($countries_data);

        // Keep backward compatibility for existing code
        $deliveries = $wpdb->get_results("SELECT id, delivery_reference, dispatch_date FROM {$wpdb->prefix}kit_deliveries WHERE status = 'scheduled' ORDER BY dispatch_date ASC");

        // Build option arrays for KIT_Commons::simpleSelect
        $country_options = ['' => 'Choose country…'];
        if (!empty($countries_data)) {
            foreach ($countries_data as $country) {
                $label = $country['name'];
                if (!empty($country['deliveries'])) {
                    $label .= ' (' . count($country['deliveries']) . ' deliveries)';
                }
                $country_options[(string) $country['id']] = $label;
            }
        } else {
            $country_options[''] = 'No active countries found.';
        }

        $delivery_options = ['' => 'Choose delivery…'];
        foreach ($deliveries as $d) {
            $delivery_options[(string) $d->id] = $d->delivery_reference . ' — ' . ($d->dispatch_date ?: 'TBD');
        }

        // Render quick stats
        $warehouse_stats = [
            ['title' => 'In Warehouse', 'value' => intval($stats->in_warehouse ?? 0), 'icon' => 'M9 12l2 2 4-4', 'color' => 'blue', 'clickable' => true, 'filter' => 'pending', 'onclick' => 'filterByStatus("pending")'],
            ['title' => 'Assigned', 'value' => intval($stats->assigned ?? 0), 'icon' => 'M13 10V3L4 14', 'color' => 'green', 'clickable' => true, 'filter' => 'assigned', 'onclick' => 'filterByStatus("assigned")'],
            ['title' => 'Shipped', 'value' => intval($stats->shipped ?? 0), 'icon' => 'M3 3h18v4', 'color' => 'yellow', 'clickable' => true, 'filter' => 'shipped', 'onclick' => 'filterByStatus("shipped")'],
            ['title' => 'Delivered', 'value' => intval($stats->delivered ?? 0), 'icon' => 'M5 13l4 4L19 7', 'color' => 'gray', 'clickable' => true, 'filter' => 'delivered', 'onclick' => 'filterByStatus("delivered")'],
        ];

        echo KIT_QuickStats::render($warehouse_stats, 'Warehouse Overview', [
            'grid_cols' => 'grid-cols-1 sm:grid-cols-4 md:grid-cols-4',
            'gap' => 'gap-4'
        ]);
        ?>

        <!-- Main Assignment Section -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm mb-6 overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-50 to-white border-b border-gray-200 px-6 py-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-900">Assign Warehouse Items to Delivery</h2>
                            <p class="text-sm text-gray-600 mt-0.5">Select waybills and assign them to a scheduled delivery</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <?php if (empty($tracking_rows)): ?>
                    <!-- Empty State -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-8 mb-6">
                        <div class="text-center max-w-md mx-auto">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">No Items in Warehouse</h3>
                            <p class="text-gray-600 mb-6">There are currently no waybills in the warehouse ready for assignment.</p>
                            <div class="flex gap-3 justify-center">
                                <?php echo KIT_Commons::renderButton('Create New Waybill', 'primary', 'md', [
                                    'href' => '?page=08600-waybill-create',
                                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                                    'iconPosition' => 'left',
                                    'gradient' => true
                                ]); ?>
                                <?php echo KIT_Commons::renderButton('View All Waybills', 'secondary', 'md', [
                                    'href' => '?page=08600-waybill-manage',
                                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />',
                                    'iconPosition' => 'left'
                                ]); ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="post" id="assign-warehouse-form" action="<?php echo esc_url(admin_url('admin.php?page=warehouse-waybills')); ?>">
                        <?php wp_nonce_field('assign_warehouse_items', 'assign_nonce'); ?>

                        <!-- Two Column Grid Layout (12-col grid: col-span-3 + col-span-9) -->
                        <div id="warehouse-two-col-grid" class="grid grid-cols-1 md:grid-cols-12 gap-8">
                            <!-- Left Column - Delivery Assignment -->
                            <div id="warehouse-left-col" class="col-span-1 md:col-span-3 min-w-0">
                                <!-- Delivery Selection Card -->
                                <?= KIT_Commons::prettyHeading([
                                    'words' => 'Delivery Selection',
                                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />',
                                    'size' => 'sm',
                                    'color' => 'gray',
                                    'subheading' => 'Select a delivery to assign the waybills to'
                                ]) ?>
                                <div class="w-full bg-gray-50 rounded-lg border border-gray-200 p-4">

                                    <!-- Country Selection -->
                                    <div class="w-full">
                                        <?php KIT_Commons::simpleSelect('Destination Country', 'country_id', 'country_id_select', $country_options, null); ?>
                                        <p class="mt-1.5 text-xs text-gray-500">Select a country to filter available deliveries</p>
                                    </div>

                                    <!-- Hidden input for delivery ID -->
                                    <input type="hidden" name="delivery_id" id="delivery_id" required>

                                    <!-- Delivery select dropdown -->
                                    <div class="w-full">
                                        <?php KIT_Commons::simpleSelect('Select Delivery', 'delivery_id_select', 'delivery_id_select', $delivery_options, null); ?>
                                        <p class="mt-1.5 text-xs text-gray-500">Or choose directly from all scheduled deliveries</p>
                                    </div>

                                    <!-- Selected Delivery Info -->
                                    <div id="selected-delivery-info" class="hidden mb-5 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                        <div class="flex items-start gap-3">
                                            <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <div class="flex-1">
                                                <p class="text-sm font-medium text-blue-900">Delivery Selected</p>
                                                <p id="selected-delivery-text" class="text-xs text-blue-700 mt-1"></p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Assignment Button -->
                                    <div class="pt-4 border-t border-gray-200">
                                        <?php echo KIT_Commons::renderButton('Assign to Delivery', 'primary', 'lg', [
                                            'type' => 'submit',
                                            'name' => 'assign_warehouse_items',
                                            'id' => 'assign-btn',
                                            'classes' => 'disabled:opacity-50 disabled:cursor-not-allowed w-full relative transition-all duration-200',
                                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />',
                                            'iconPosition' => 'left',
                                            'gradient' => true
                                        ]); ?>

                                        <!-- Loading State -->
                                        <div id="assign-loading" class="hidden mt-3 text-center">
                                            <div class="inline-flex items-center gap-2 text-sm text-blue-600 font-medium">
                                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span>Assigning waybills...</span>
                                            </div>
                                        </div>

                                        <!-- Selection Summary -->
                                        <div class="mt-4 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <span id="sel-count" class="text-sm font-medium text-gray-600">0 selected</span>
                                                <span id="sel-badge" class="hidden inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                                                    <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Ready to assign
                                                </span>
                                            </div>
                                            <a href="#" data-modal="add-delivery-modal" class="text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors flex items-center gap-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                                Add New Delivery
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column - Warehouse Items Table -->
                            <div id="warehouse-table-col" class="col-span-1 md:col-span-9 w-full min-w-0">
                                <!-- Table Header -->
                                <div class="flex items-center justify-between">
                                    <div>
                                        <?= KIT_Commons::prettyHeading([
                                            'words' => 'Waybills in Warehouse',
                                            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />',
                                            'size' => 'sm',
                                            'color' => 'gray',
                                            'classes' => '',
                                            'subheading' => 'Select waybills to assign to the selected delivery'
                                        ]) ?>
                                    </div>
                                </div>

                                <!-- Table Container -->
                                <div class="overflow-hidden">
                                    <?php
                                    // Helper function to get week label from date
                                    function get_week_label($date_string)
                                    {
                                        if (empty($date_string)) {
                                            return 'Unassigned Week';
                                        }
                                        $date = new DateTime($date_string);
                                        $day_of_week = (int)$date->format('w');
                                        $days_to_monday = $day_of_week == 0 ? 6 : $day_of_week - 1;
                                        $monday = clone $date;
                                        $monday->modify("-{$days_to_monday} days");
                                        $sunday = clone $monday;
                                        $sunday->modify('+6 days');
                                        $monday_str = $monday->format('M j');
                                        $sunday_str = $sunday->format('M j, Y');
                                        if ($monday->format('M Y') === $sunday->format('M Y')) {
                                            return 'Week of ' . $monday_str . ' - ' . $sunday->format('j, Y');
                                        }
                                        return 'Week of ' . $monday_str . ' - ' . $sunday_str;
                                    }

                                    // Convert warehouse items to data array for unified table
                                    $table_data = [];
                                    foreach ($tracking_rows as $row) {
                                        $date_string = $row->last_updated_at ?? $row->created_at ?? '';
                                        $waybill_no = $row->waybill_no ?? '';

                                        // Get dimensions
                                        $item_length = isset($row->item_length) ? floatval($row->item_length) : 0;
                                        $item_width = isset($row->item_width) ? floatval($row->item_width) : 0;
                                        $item_height = isset($row->item_height) ? floatval($row->item_height) : 0;

                                        // Show actual dimensions
                                        $dimension_display = ($item_length > 0 && $item_width > 0 && $item_height > 0)
                                            ? number_format($item_length, 1) . ' × ' . number_format($item_width, 1) . ' × ' . number_format($item_height, 1) . ' cm'
                                            : 'N/A';

                                        // Format total mass
                                        $total_mass_display = number_format((float)($row->total_mass_kg ?? 0), 2);

                                        $table_data[] = [
                                            'waybill_id' => $waybill_no,
                                            'waybill_no' => $waybill_no,
                                            'description' => $row->description ?? '',
                                            'waybill_db_id' => intval($row->id ?? 0),
                                            'customer_name' => trim(($row->customer_name ?? '') . ' ' . ($row->customer_surname ?? '')),
                                            'total_mass_kg' => $total_mass_display,
                                            'dimension' => $dimension_display,
                                            'action' => ucfirst($row->status ?? ''),
                                            'created_at' => $date_string,
                                            'week' => get_week_label($date_string),
                                        ];
                                    }

                                    // Define columns for the unified table
                                    $columns = [
                                        'checkbox' => [
                                            'label' => '',
                                            'callback' => function ($value, $row) {
                                                return '<input type="checkbox" class="wi waybill-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500" name="waybill_ids[]" value="' . intval($row['waybill_db_id']) . '">';
                                            }
                                        ],
                                        'waybill_no' => [
                                            'label' => 'Waybill',
                                            'callback' => function ($value, $row) {
                                                $db_id = intval($row['waybill_db_id'] ?? 0);
                                                $url = admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . urlencode($db_id));
                                                return '<a href="' . esc_url($url) . '" class="font-semibold text-blue-600 hover:text-blue-800 hover:underline transition-colors">#' . esc_html($value) . '</a>';
                                            }
                                        ],
                                        'description' => [
                                            'label' => 'Description',
                                            'cell_class' => 'text-left text-sm max-w-[200px] truncate',
                                            'callback' => function ($value, $row) {
                                                $desc = $row['description'] ?? $value ?? '';
                                                return esc_html($desc ?: '—');
                                            }
                                        ],
                                        'customer_name' => 'Customer',
                                        'total_mass_kg' => 'Weight (kg)',
                                        'dimension' => 'Dimension',
                                        'action' => 'Status',
                                        'created_at' => 'Updated'
                                    ];

                                    echo KIT_Unified_Table::infinite($table_data, $columns, [
                                        'title' => '',
                                        'searchable' => true,
                                        'sortable' => true,
                                        'pagination' => true,
                                        'items_per_page' => 20,
                                        'empty_message' => 'No items currently in warehouse',
                                        'class' => 'min-w-full divide-y divide-gray-200',
                                        'groupby' => 'week',
                                        'group_heading_prefix' => '',
                                        'preserve_order' => false,
                                        'group_collapsible' => true,
                                        'group_collapsed' => false,
                                    ]);
                                    ?>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Add New Delivery Modal -->
        <?php
        $delivery_form_content = KIT_Deliveries::deliveryForm(null, true);
        echo KIT_Modal::render(
            'add-delivery-modal',
            'Add New Delivery',
            $delivery_form_content,
            '3xl',
            false
        );
        ?>

        <style>
            /* Force 12-col grid so col-span-9 works when Tailwind responsive classes aren't loaded (e.g. in admin) */
            @media (min-width: 768px) {
                #assign-warehouse-form #warehouse-two-col-grid {
                    display: grid !important;
                    grid-template-columns: repeat(12, 1fr);
                    gap: 2rem;
                }

                #assign-warehouse-form #warehouse-left-col {
                    grid-column: span 3;
                }

                #assign-warehouse-form #warehouse-table-col {
                    grid-column: span 9;
                    min-width: 0;
                }
            }

            /* Professional styling improvements */
            .warehouse-page {
                background: #f9fafb;
            }

            /* Enhanced form inputs */
            select:focus {
                outline: none;
                box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            }

            /* Table row selection styling */
            tr.waybill-selected {
                background-color: #eff6ff !important;
                border-left: 4px solid #3b82f6;
            }

            tr.waybill-selected:hover {
                background-color: #dbeafe !important;
            }

            /* Smooth transitions */
            * {
                transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
                transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
                transition-duration: 150ms;
            }

            /* Button hover effects */
            button:not(:disabled):hover {
                transform: translateY(-1px);
            }

            button:not(:disabled):active {
                transform: translateY(0);
            }

            /* Card hover effects */
            .hover\:shadow-md:hover {
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }

            /* Loading spinner */
            @keyframes spin {
                to {
                    transform: rotate(360deg);
                }
            }

            .animate-spin {
                animation: spin 1s linear infinite;
            }

            /* Selection badge animation */
            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateX(-10px);
                }

                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            #sel-badge:not(.hidden) {
                animation: slideIn 0.3s ease-out;
            }
        </style>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('Warehouse page JavaScript initializing...');

                const headerSelect = document.getElementById('select-all-checkbox');
                const selectAll = document.getElementById('select-all');
                const deselectAll = document.getElementById('deselect-all');
                const count = document.getElementById('sel-count');
                const assignBtn = document.getElementById('assign-btn');
                const deliverySelect = document.getElementById('delivery_id');
                const deliverySelectFallback = document.getElementById('delivery_id_select');
                const countrySelect = document.getElementById('country_id_select');
                const selectedDeliveryInfo = document.getElementById('selected-delivery-info');
                const selectedDeliveryText = document.getElementById('selected-delivery-text');

                // Countries data from PHP
                const countriesData = <?php echo json_encode($countries_data ?? []); ?> || [];
                console.log('Countries data loaded:', countriesData.length, 'countries');

                // Function to get all waybill checkboxes (handles dynamic content)
                function getWaybillCheckboxes() {
                    // Try multiple selectors to find checkboxes
                    const selectors = [
                        'input[name="waybill_ids[]"]',
                        'input.waybill-checkbox',
                        'input.wi.waybill-checkbox',
                        'input.wi[name="waybill_ids[]"]'
                    ];

                    let checkboxes = [];
                    selectors.forEach(selector => {
                        const found = document.querySelectorAll(selector);
                        found.forEach(cb => {
                            // Only add if not already in array (avoid duplicates)
                            if (!checkboxes.includes(cb)) {
                                checkboxes.push(cb);
                            }
                        });
                    });

                    console.log('Found checkboxes:', checkboxes.length, 'using selectors:', selectors);
                    return checkboxes;
                }

                function refresh() {
                    const boxes = getWaybillCheckboxes();
                    const n = boxes.filter(b => b.checked).length;
                    // Check both hidden field and fallback dropdown
                    const deliverySelected = (deliverySelect && deliverySelect.value && deliverySelect.value !== '') ||
                        (deliverySelectFallback && deliverySelectFallback.value && deliverySelectFallback.value !== '');

                    console.log('Refresh: waybills selected:', n, 'delivery selected:', deliverySelected,
                        'hidden value:', deliverySelect?.value, 'dropdown value:', deliverySelectFallback?.value);

                    // Update count with better formatting
                    if (count) {
                        if (n === 0) {
                            count.textContent = '0 selected';
                            count.className = 'text-sm font-medium text-gray-500';
                        } else {
                            count.textContent = `${n} waybill${n !== 1 ? 's' : ''} selected`;
                            count.className = 'text-sm font-semibold text-blue-700';
                        }
                    }

                    // Show/hide badge
                    const badge = document.getElementById('sel-badge');
                    if (badge) {
                        if (n > 0 && deliverySelected) {
                            badge.classList.remove('hidden');
                            badge.classList.add('inline-flex');
                        } else {
                            badge.classList.add('hidden');
                            badge.classList.remove('inline-flex');
                        }
                    }

                    // Update button state
                    if (assignBtn) {
                        const canAssign = n > 0 && deliverySelected;
                        assignBtn.disabled = !canAssign;

                        // Visual feedback
                        if (canAssign) {
                            assignBtn.classList.add('ring-2', 'ring-blue-500', 'ring-offset-2');
                        } else {
                            assignBtn.classList.remove('ring-2', 'ring-blue-500', 'ring-offset-2');
                        }
                    }

                    // Highlight selected rows
                    boxes.forEach(box => {
                        const row = box.closest('tr');
                        if (row) {
                            if (box.checked) {
                                row.classList.add('waybill-selected');
                            } else {
                                row.classList.remove('waybill-selected');
                            }
                        }
                    });
                }

                // Selection handlers using event delegation for dynamic content
                if (headerSelect) {
                    headerSelect.addEventListener('change', () => {
                        const state = headerSelect.checked;
                        const boxes = getWaybillCheckboxes();
                        boxes.forEach(b => b.checked = state);
                        refresh();
                    });
                }

                if (selectAll) {
                    selectAll.addEventListener('click', () => {
                        const boxes = getWaybillCheckboxes();
                        boxes.forEach(b => b.checked = true);
                        if (headerSelect) headerSelect.checked = true;
                        refresh();
                    });
                }

                if (deselectAll) {
                    deselectAll.addEventListener('click', () => {
                        const boxes = getWaybillCheckboxes();
                        boxes.forEach(b => b.checked = false);
                        if (headerSelect) headerSelect.checked = false;
                        refresh();
                    });
                }

                // Use event delegation for checkboxes (handles dynamically loaded content)
                document.addEventListener('change', function(e) {
                    if (e.target && (e.target.classList.contains('wi') || e.target.classList.contains('waybill-checkbox') || e.target.name === 'waybill_ids[]')) {
                        refresh();
                    }
                });

                // Delivery selection handlers
                function updateDeliverySelection() {
                    // Check both hidden field and dropdown for delivery ID
                    const selectedDeliveryId = (deliverySelect && deliverySelect.value) || (deliverySelectFallback && deliverySelectFallback.value) || '';
                    let selectedOptionText = '';

                    // Get selected option text from the visible dropdown
                    if (deliverySelectFallback && deliverySelectFallback.selectedIndex >= 0) {
                        const selectedOption = deliverySelectFallback.options[deliverySelectFallback.selectedIndex];
                        if (selectedOption && selectedOption.value) {
                            selectedOptionText = selectedOption.text;
                            // Ensure hidden field is also set
                            if (deliverySelect && !deliverySelect.value) {
                                deliverySelect.value = selectedOption.value;
                            }
                        }
                    }

                    if (selectedDeliveryId && selectedOptionText) {
                        if (selectedDeliveryInfo) {
                            selectedDeliveryInfo.classList.remove('hidden');
                        }
                        if (selectedDeliveryText) {
                            selectedDeliveryText.textContent = selectedOptionText;
                        }
                    } else {
                        if (selectedDeliveryInfo) {
                            selectedDeliveryInfo.classList.add('hidden');
                        }
                    }
                    // IMPORTANT: Call refresh to update button state
                    refresh();
                }

                // Update hidden field when visible dropdown changes
                if (deliverySelectFallback) {
                    deliverySelectFallback.addEventListener('change', function() {
                        console.log('Delivery dropdown changed:', this.value);
                        const selectedValue = this.value;
                        if (deliverySelect) {
                            deliverySelect.value = selectedValue;
                            console.log('Hidden delivery_id updated to:', selectedValue);
                        }
                        updateDeliverySelection();
                    });
                } else {
                    console.warn('deliverySelectFallback not found');
                }

                // Country selection and delivery filtering
                let filterDeliveriesByCountryFn = null;
                let allDeliveryOptions = [];

                // Store all delivery options for filtering (must be done before filtering)
                if (deliverySelectFallback && countrySelect) {
                    // Store all delivery options for filtering
                    for (let i = 1; i < deliverySelectFallback.options.length; i++) {
                        allDeliveryOptions.push({
                            value: deliverySelectFallback.options[i].value,
                            text: deliverySelectFallback.options[i].text,
                            element: deliverySelectFallback.options[i].cloneNode(true)
                        });
                    }

                    // Function to filter deliveries by country
                    filterDeliveriesByCountryFn = function(countryId) {
                        if (!deliverySelectFallback) {
                            console.warn('deliverySelectFallback not available for filtering');
                            return;
                        }

                        console.log('Filtering deliveries, countryId:', countryId, 'allDeliveryOptions:', allDeliveryOptions.length);

                        // Clear all options except the first "Choose delivery..." option
                        while (deliverySelectFallback.options.length > 1) {
                            deliverySelectFallback.remove(1);
                        }

                        if (countryId && Array.isArray(countriesData) && countriesData.length > 0) {
                            const country = countriesData.find(c => parseInt(c.id) === parseInt(countryId));
                            console.log('Found country:', country ? country.name : 'not found', 'deliveries:', country ? country.deliveries.length : 0);

                            if (country && country.deliveries && country.deliveries.length > 0) {
                                // Add deliveries for this country
                                country.deliveries.forEach(delivery => {
                                    const option = document.createElement('option');
                                    option.value = delivery.id;
                                    const dateStr = delivery.dispatch_date || 'TBD';
                                    option.textContent = delivery.reference + ' — ' + dateStr;
                                    deliverySelectFallback.appendChild(option);
                                });
                                console.log('Added', country.deliveries.length, 'deliveries to dropdown');
                            } else {
                                // No deliveries for this country
                                const option = document.createElement('option');
                                option.value = '';
                                option.textContent = 'No deliveries available for this country';
                                option.disabled = true;
                                deliverySelectFallback.appendChild(option);
                                console.log('No deliveries found for country');
                            }
                        } else {
                            // No country selected - show all deliveries
                            console.log('No country selected, showing all', allDeliveryOptions.length, 'deliveries');
                            allDeliveryOptions.forEach(opt => {
                                deliverySelectFallback.appendChild(opt.element.cloneNode(true));
                            });
                        }
                    };
                }

                if (countrySelect) {
                    countrySelect.addEventListener('change', function() {
                        const countryId = parseInt(this.value);
                        console.log('Country dropdown changed:', countryId);

                        // Clear delivery selection when country changes
                        if (deliverySelect) {
                            deliverySelect.value = '';
                        }
                        if (deliverySelectFallback) {
                            deliverySelectFallback.value = '';
                        }

                        // Filter the dropdown based on selected country
                        if (filterDeliveriesByCountryFn) {
                            console.log('Filtering deliveries for country:', countryId);
                            filterDeliveriesByCountryFn(countryId);
                        } else {
                            console.warn('filterDeliveriesByCountryFn not available');
                        }

                        updateDeliverySelection();
                        refresh();
                    });
                } else {
                    console.warn('countrySelect not found');
                }

                // Initialize: If a country is already selected on page load, filter the dropdown
                if (countrySelect && countrySelect.value && filterDeliveriesByCountryFn) {
                    const initialCountryId = parseInt(countrySelect.value);
                    if (initialCountryId) {
                        filterDeliveriesByCountryFn(initialCountryId);
                    }
                }

                // Form submission is handled entirely by PHP - no JavaScript interception needed
                // The form will submit naturally with all checkbox values and form fields

                // Initial refresh
                refresh();

                // Watch for dynamically loaded table content
                const tableContainer = document.querySelector('.unified-table-container, [id*="unified-table"], table');
                if (tableContainer) {
                    const observer = new MutationObserver(function(mutations) {
                        // When table content changes, refresh the selection state
                        let shouldRefresh = false;
                        mutations.forEach(function(mutation) {
                            if (mutation.addedNodes.length > 0 || mutation.removedNodes.length > 0) {
                                shouldRefresh = true;
                            }
                        });
                        if (shouldRefresh) {
                            refresh();
                        }
                    });

                    observer.observe(tableContainer, {
                        childList: true,
                        subtree: true
                    });
                }
            });
        </script>
    </div>
</div>