<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include user roles for permission checking
require_once plugin_dir_path(__FILE__) . '../user-roles.php';

// Include Dashboard Quickies component
require_once plugin_dir_path(__FILE__) . '../components/dashboardQuickies.php';
require_once plugin_dir_path(__FILE__) . '../components/quickStats.php';

// Handle sample warehouse waybills creation
if (isset($_POST['create_sample_warehouse']) && wp_verify_nonce($_POST['sample_nonce'], 'create_sample_warehouse_waybills')) {
    global $wpdb;

    // Get existing customers
    $customers = $wpdb->get_results("SELECT cust_id FROM {$wpdb->prefix}kit_customers LIMIT 5");

    if (!empty($customers)) {
        $created_count = 0;

        // Create 10 sample waybills
        for ($i = 1; $i <= 10; $i++) {
            $customer = $customers[array_rand($customers)];

            $waybill_data = [
                'waybill_no' => 'WB-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer->cust_id,
                'delivery_id' => 1, // Warehouse delivery
                'product_invoice_amount' => rand(500, 5000),
                'total_mass_kg' => rand(10, 100),
                // Warehouse status now managed by warehouse_items table
                'status' => 'warehoused',
                'created_at' => current_time('mysql'),
                'created_by' => get_current_user_id() ?: 1,
                'last_updated_at' => current_time('mysql'),
                'last_updated_by' => get_current_user_id() ?: 1
            ];

            $result = $wpdb->insert(
                $wpdb->prefix . 'kit_waybills',
                $waybill_data
            );

            if ($result) {
                $created_count++;
            }
        }

        if ($created_count > 0) {
            echo '<div class="notice notice-success"><p>Successfully created ' . $created_count . ' sample warehouse waybills!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Failed to create sample waybills. Please try again.</p></div>';
        }
    } else {
        echo '<div class="notice notice-error"><p>No customers found. Please create customers first.</p></div>';
    }
}

// Handle form submission for assignment
if (isset($_POST['assign_waybills']) && wp_verify_nonce($_POST['nonce'], 'assign_waybills_nonce')) {
    global $wpdb;

    $waybill_ids = $_POST['waybill_ids'] ?? [];
    $delivery_id = intval($_POST['delivery_id']);

    if (!empty($waybill_ids) && $delivery_id > 0) {
        $updated = 0;
        foreach ($waybill_ids as $waybill_id) {
            // Get waybill details before update
            $waybill = $wpdb->get_row($wpdb->prepare(
                "SELECT waybill_no, customer_id, status FROM {$wpdb->prefix}kit_waybills WHERE id = %d",
                intval($waybill_id)
            ));

            $result = $wpdb->update(
                $wpdb->prefix . 'kit_waybills',
                [
                    'delivery_id' => $delivery_id,
                    'status' => 'assigned',
                    // Warehouse status now managed by warehouse_items table
                    'last_updated_at' => current_time('mysql'),
                    'last_updated_by' => get_current_user_id()
                ],
                ['id' => intval($waybill_id)]
            );
            if ($result !== false) {
                $updated++;

                // Track the assignment action
                if ($waybill) {
                    KIT_Waybills::track_warehouse_action(
                        $waybill->waybill_no,
                        intval($waybill_id),
                        $waybill->customer_id,
                        'assigned',
                        $waybill->status,
                        'assigned',
                        $delivery_id,
                        'Assigned to delivery from warehouse'
                    );
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

// Get waybills that have warehouse items
$warehouse_waybills_query = "
    SELECT DISTINCT w.*, c.name as customer_name, c.surname as customer_surname, c.company_name
    FROM $waybills_table w
    LEFT JOIN $customers_table c ON w.customer_id = c.cust_id
    INNER JOIN {$wpdb->prefix}kit_warehouse_items wi ON w.id = wi.waybill_id
    WHERE wi.status = 'in_warehouse'
    ORDER BY w.created_at DESC
";
$warehouse_waybills = $wpdb->get_results($warehouse_waybills_query);

// Get warehouse statistics
$total_warehouse_waybills = count($warehouse_waybills);
$pending_waybills = array_filter($warehouse_waybills, function ($w) {
    return $w->status === 'pending';
});
$pending_count = count($pending_waybills);
$created_waybills = array_filter($warehouse_waybills, function ($w) {
    return $w->status === 'created';
});
$created_count = count($created_waybills);
$warehouse_waybills_filtered = array_filter($warehouse_waybills, function ($w) {
    return $w->status === 'warehoused';
});
$warehouse_count = count($warehouse_waybills_filtered);

// Debug information removed for clean production
?>

<div class="wrap">
    <?php
    echo KIT_Commons::showingHeader([
        'title' => 'Warehouse Management',
        'desc' => 'Manage and track all warehouse operations',
    ]);
    ?>
    <hr class="wp-header-end">

    <?php
    // Get statistics for the warehouse
    $total_warehouse_waybills = $wpdb->get_var("SELECT COUNT(DISTINCT waybill_id) FROM {$wpdb->prefix}kit_warehouse_items WHERE status = 'in_warehouse'");
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
    <!-- Tab Navigation -->
    <div class="warehouse-tabs mb-4">
        <div style="display: flex; background: #f3f4f6; padding: 4px; border-radius: 8px; width: fit-content;">
            <?php echo KIT_Commons::renderButton('Overview', 'ghost', 'sm', ['id' => 'overview-tab', 'classes' => 'tab-btn active']); ?>
            <?php echo KIT_Commons::renderButton('Warehouse Tracking', 'ghost', 'sm', ['id' => 'tracking-tab', 'classes' => 'tab-btn']); ?>
        </div>
    </div>

    <!-- Overview Tab Content -->
    <div id="overview-content" class="tab-content is-visible">
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

        <div id="country-deliveries" class="mb-6"></div>

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
        <?php if ($total_warehouse_waybills == 0): ?>
            <form method="post" id="create-sample-form" style="display: none;">
                <?php wp_nonce_field('create_sample_warehouse_waybills', 'sample_nonce'); ?>
                <input type="hidden" name="create_sample_warehouse" value="1">
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
                    <?php wp_nonce_field('assign_waybills_nonce', 'nonce'); ?>
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

                        <?php echo KIT_Commons::renderButton('Assign to Delivery', 'primary', 'md', [
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

        // Initialize tabs and enforce Overview as default
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab-btn');
            tabButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabName = this.id.replace('-tab', '');
                    switchWarehouseTab(tabName);
                });
            });

            // Force Overview tab to be active on every page load
            switchWarehouseTab('overview');
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
        // Get warehouse tracking data
        $tracking_table = $wpdb->prefix . 'kit_warehouse_tracking';
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $customers_table = $wpdb->prefix . 'kit_customers';
        $deliveries_table = $wpdb->prefix . 'kit_deliveries';
        $users_table = $wpdb->users;

        // Build query with joins
        $query = "
            SELECT 
                wt.*,
                w.waybill_no,
                w.product_invoice_amount,
                w.total_mass_kg,
                c.name as customer_name,
                c.surname as customer_surname,
                c.company_name,
                d.delivery_reference,
                u.display_name as action_by
            FROM $tracking_table wt
            LEFT JOIN $waybills_table w ON wt.waybill_no = w.waybill_no
            LEFT JOIN $customers_table c ON wt.customer_id = c.cust_id
            LEFT JOIN $deliveries_table d ON wt.assigned_delivery_id = d.id
            LEFT JOIN $users_table u ON wt.created_by = u.ID
            ORDER BY wt.created_at DESC
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
                                            'warehoused' => 'bg-blue-100 text-blue-800',
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
        // Get warehouse tracking data
        $tracking_table = $wpdb->prefix . 'kit_warehouse_tracking';
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $customers_table = $wpdb->prefix . 'kit_customers';
        $deliveries_table = $wpdb->prefix . 'kit_deliveries';
        $users_table = $wpdb->users;

        // Build query with joins
        $query = "
            SELECT 
                wt.*,
                w.waybill_no,
                w.product_invoice_amount,
                w.total_mass_kg,
                c.name as customer_name,
                c.surname as customer_surname,
                c.company_name,
                d.delivery_reference,
                u.display_name as action_by
            FROM $tracking_table wt
            LEFT JOIN $waybills_table w ON wt.waybill_no = w.waybill_no
            LEFT JOIN $customers_table c ON wt.customer_id = c.cust_id
            LEFT JOIN $deliveries_table d ON wt.assigned_delivery_id = d.id
            LEFT JOIN $users_table u ON wt.created_by = u.ID
            ORDER BY wt.created_at DESC
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
                                            'warehoused' => 'bg-blue-100 text-blue-800',
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