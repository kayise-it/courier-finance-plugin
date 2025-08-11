<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission for assignment
if (isset($_POST['assign_waybills']) && wp_verify_nonce($_POST['nonce'], 'assign_waybills_nonce')) {
    global $wpdb;
    
    $waybill_ids = $_POST['waybill_ids'] ?? [];
    $delivery_id = intval($_POST['delivery_id']);
    
    if (!empty($waybill_ids) && $delivery_id > 0) {
        $updated = 0;
        foreach ($waybill_ids as $waybill_id) {
            $result = $wpdb->update(
                $wpdb->prefix . 'kit_waybills',
                [
                    'delivery_id' => $delivery_id,
                    'status' => 'assigned',
                    'last_updated_at' => current_time('mysql'),
                    'last_updated_by' => get_current_user_id()
                ],
                ['id' => intval($waybill_id)]
            );
            if ($result !== false) {
                $updated++;
            }
        }
        
        if ($updated > 0) {
            echo '<div class="notice notice-success"><p>Successfully assigned ' . $updated . ' waybill(s) to delivery!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Failed to assign waybills. Please try again.</p></div>';
        }
    }
}

// Get warehouse waybills
global $wpdb;
$waybills_table = $wpdb->prefix . 'kit_waybills';
$customers_table = $wpdb->prefix . 'kit_customers';

// Get waybills that are in warehouse (warehouse = 1)
$warehouse_waybills_query = "
    SELECT w.*, c.name as customer_name, c.surname as customer_surname, c.company_name
    FROM $waybills_table w
    LEFT JOIN $customers_table c ON w.customer_id = c.cust_id
    WHERE w.warehouse = 1
    ORDER BY w.created_at DESC
";
$warehouse_waybills = $wpdb->get_results($warehouse_waybills_query);

// Get warehouse statistics
$total_warehouse_waybills = count($warehouse_waybills);
$pending_waybills = array_filter($warehouse_waybills, function($w) { return $w->status === 'pending'; });
$pending_count = count($pending_waybills);
$created_waybills = array_filter($warehouse_waybills, function($w) { return $w->status === 'created'; });
$created_count = count($created_waybills);
$warehouse_waybills_filtered = array_filter($warehouse_waybills, function($w) { return $w->status === 'warehoused'; });
$warehouse_count = count($warehouse_waybills_filtered);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Warehouse Management</h1>
    <hr class="wp-header-end">

    <!-- Statistics Cards -->
    <div class="dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #2563eb;">
            <h3 style="margin: 0 0 10px 0; color: #2563eb;">Total Warehouse</h3>
            <div style="font-size: 2em; font-weight: bold; color: #1e293b;"><?php echo number_format($total_warehouse_waybills); ?></div>
            <p style="margin: 5px 0 0 0; color: #64748b;">All waybills in warehouse</p>
        </div>

        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #059669;">
            <h3 style="margin: 0 0 10px 0; color: #059669;">In Warehouse</h3>
            <div style="font-size: 2em; font-weight: bold; color: #1e293b;"><?php echo number_format($warehouse_count); ?></div>
            <p style="margin: 5px 0 0 0; color: #64748b;">Ready for pickup</p>
        </div>

        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #dc2626;">
            <h3 style="margin: 0 0 10px 0; color: #dc2626;">Pending</h3>
            <div style="font-size: 2em; font-weight: bold; color: #1e293b;"><?php echo number_format($pending_count); ?></div>
            <p style="margin: 5px 0 0 0; color: #64748b;">Awaiting processing</p>
        </div>

        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #7c3aed;">
            <h3 style="margin: 0 0 10px 0; color: #7c3aed;">Quick Actions</h3>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="?page=08600-waybill-create" class="button button-primary" style="text-decoration: none;">Create Waybill</a>
                <a href="?page=08600-waybill-manage" class="button" style="text-decoration: none;">Manage All</a>
            </div>
        </div>
    </div>



    <!-- View Toggle Buttons -->
    <div style="display: flex; justify-content: center; margin-bottom: 20px;">
        <div style="display: flex; background: #f3f4f6; padding: 4px; border-radius: 8px;">
            <button id="block-view-btn" class="view-toggle-btn active" style="padding: 8px 16px; border-radius: 6px; font-weight: 500; font-size: 14px; transition: all 0.2s ease; background: white; color: #374151; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                Block View
            </button>
            <button id="table-view-btn" class="view-toggle-btn" style="padding: 8px 16px; border-radius: 6px; font-weight: 500; font-size: 14px; transition: all 0.2s ease; background: transparent; color: #6b7280;">
                Table View
            </button>
        </div>
    </div>

    <!-- Assignment Section (Block View) -->
    <?php if ($total_warehouse_waybills > 0): ?>
    <div id="block-view" class="assignment-section" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 25px;">
            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600; color: #1f2937;">Warehouse Waybills</h2>
            <div style="display: flex; gap: 12px;">
                <span class="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 text-sm font-medium rounded-lg">
                    <?php echo $total_warehouse_waybills; ?> Available
                </span>
                <a href="?page=08600-waybill-create" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    Create New
                </a>
            </div>
        </div>
            
            <?php
            // Get available deliveries (excluding warehouse delivery)
            $deliveries_query = "
                SELECT d.*, 
                       sd.description as route_description,
                       oc1.country_name as origin_country,
                       oc2.country_name as destination_country
                FROM {$wpdb->prefix}kit_deliveries d
                LEFT JOIN {$wpdb->prefix}kit_shipping_directions sd ON d.direction_id = sd.id
                LEFT JOIN {$wpdb->prefix}kit_operating_countries oc1 ON sd.origin_country_id = oc1.id
                LEFT JOIN {$wpdb->prefix}kit_operating_countries oc2 ON sd.destination_country_id = oc2.id
                WHERE d.status = 'scheduled' AND d.id != 1
                ORDER BY d.dispatch_date ASC
            ";
            $available_deliveries = $wpdb->get_results($deliveries_query);
            $total_available_deliveries = count($available_deliveries);
            ?>
            
            <?php if ($total_available_deliveries > 0): ?>
                <form method="post" id="assign-waybills-form">
                    <?php wp_nonce_field('assign_waybills_nonce', 'nonce'); ?>
                    
                    <!-- Delivery Selection -->
                    <div style="margin-bottom: 20px;">
                        <label for="delivery_id" style="display: block; font-weight: 500; color: #374151; margin-bottom: 8px;">
                            Select Delivery (Truck)
                        </label>
                        <select name="delivery_id" id="delivery_id" required 
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; background: white; font-size: 14px;">
                            <option value="">Choose a delivery...</option>
                            <?php foreach ($available_deliveries as $delivery): ?>
                                <option value="<?php echo $delivery->id; ?>">
                                    <?php echo esc_html($delivery->delivery_reference); ?> - 
                                    <?php echo esc_html($delivery->route_description); ?> 
                                    (<?php echo esc_html($delivery->origin_country); ?> → <?php echo esc_html($delivery->destination_country); ?>)
                                    - <?php echo date('M j, Y', strtotime($delivery->dispatch_date)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Waybills Selection -->
                    <div style="margin-bottom: 20px;">
                        <label style="display: block; font-weight: 500; color: #374151; margin-bottom: 8px;">
                            Select Waybills to Assign
                        </label>
                        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; max-height: 400px; overflow-y: auto;">
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
                                <?php foreach ($warehouse_waybills as $waybill): ?>
                                    <div class="waybill-card" style="background: white; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb; transition: all 0.2s ease;">
                                        <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer;">
                                            <input type="checkbox" name="waybill_ids[]" value="<?php echo $waybill->id; ?>" 
                                                   style="margin-top: 2px; width: 16px; height: 16px;">
                                            <div style="flex: 1;">
                                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                                    <strong style="color: #111827; font-size: 14px;">
                                                        #<?php echo $waybill->waybill_no; ?>
                                                    </strong>
                                                    <span style="background: #dcfce7; color: #166534; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                                        R<?php echo number_format($waybill->product_invoice_amount, 2); ?>
                                                    </span>
                                                </div>
                                                <div style="color: #6b7280; font-size: 13px; margin-bottom: 4px;">
                                                    <?php echo esc_html($waybill->customer_name . ' ' . $waybill->customer_surname); ?>
                                                </div>
                                                <?php if ($waybill->company_name): ?>
                                                    <div style="color: #9ca3af; font-size: 12px; margin-bottom: 4px;">
                                                        <?php echo esc_html($waybill->company_name); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div style="display: flex; gap: 12px; color: #9ca3af; font-size: 12px;">
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
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; gap: 16px; align-items: center;">
                            <button type="button" id="select-all-btn" style="color: #2563eb; font-weight: 500; font-size: 14px; background: none; border: none; cursor: pointer;">
                                Select All
                            </button>
                            <button type="button" id="deselect-all-btn" style="color: #6b7280; font-weight: 500; font-size: 14px; background: none; border: none; cursor: pointer;">
                                Deselect All
                            </button>
                            <span id="selected-count" style="color: #6b7280; font-size: 14px;">0 waybills selected</span>
                        </div>
                        
                        <button type="submit" name="assign_waybills" id="assign-btn" 
                                style="background: #2563eb; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; opacity: 0.5; cursor: not-allowed;">
                            Assign to Delivery
                        </button>
                    </div>
                </form>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; background: #f9fafb; border-radius: 8px;">
                    <h3 style="color: #374151; font-size: 18px; margin-bottom: 8px;">No available deliveries</h3>
                    <p style="color: #6b7280; margin-bottom: 16px;">There are no scheduled deliveries available for assignment.</p>
                    <a href="?page=kit-deliveries" style="background: #2563eb; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500;">
                        Create Delivery
                    </a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

    <!-- Table View Section -->
    <div id="table-view" class="warehouse-table" style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 30px; display: none;">
        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 25px;">
            <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600; color: #1f2937;">Warehouse Waybills Table</h2>
            <div style="display: flex; gap: 12px;">
                <span class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-800 text-sm font-medium rounded-lg">
                    <?php echo $total_warehouse_waybills; ?> Waybills
                </span>
                <a href="?page=08600-waybill-create" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
                    Create New
                </a>
            </div>
        </div>
        
        <?php if (isset($total_available_deliveries) && $total_available_deliveries > 0): ?>
        <!-- Assignment Controls for Table View -->
        <div style="background: #f9fafb; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <form method="post" id="assign-waybills-table-form">
                <?php wp_nonce_field('assign_waybills_nonce', 'nonce'); ?>
                
                <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <label for="delivery_id_table" style="display: block; font-weight: 500; color: #374151; margin-bottom: 8px;">
                            Select Delivery (Truck)
                        </label>
                        <select name="delivery_id" id="delivery_id_table" required 
                                style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; background: white; font-size: 14px;">
                            <option value="">Choose a delivery...</option>
                            <?php foreach ($available_deliveries as $delivery): ?>
                                <option value="<?php echo $delivery->id; ?>">
                                    <?php echo esc_html($delivery->delivery_reference); ?> - 
                                    <?php echo esc_html($delivery->route_description); ?> 
                                    (<?php echo esc_html($delivery->origin_country); ?> → <?php echo esc_html($delivery->destination_country); ?>)
                                    - <?php echo date('M j, Y', strtotime($delivery->dispatch_date)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="display: flex; gap: 12px; align-items: end;">
                        <button type="button" id="select-all-table-btn" style="color: #2563eb; font-weight: 500; font-size: 14px; background: none; border: none; cursor: pointer; padding: 8px 12px;">
                            Select All
                        </button>
                        <button type="button" id="deselect-all-table-btn" style="color: #6b7280; font-weight: 500; font-size: 14px; background: none; border: none; cursor: pointer; padding: 8px 12px;">
                            Deselect All
                        </button>
                        <span id="selected-count-table" style="color: #6b7280; font-size: 14px; padding: 8px 12px;">0 waybills selected</span>
                        <button type="submit" name="assign_waybills" id="assign-table-btn" 
                                style="background: #2563eb; color: white; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; opacity: 0.5; cursor: not-allowed;">
                            Assign to Delivery
                        </button>
                    </div>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 40px; background: #f9fafb; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="color: #374151; font-size: 18px; margin-bottom: 8px;">No available deliveries</h3>
            <p style="color: #6b7280; margin-bottom: 16px;">There are no scheduled deliveries available for assignment.</p>
            <a href="?page=kit-deliveries" style="background: #2563eb; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: 500;">
                Create Delivery
            </a>
        </div>
        <?php endif; ?>
        
        <?php
        $table_options = [
            'itemsPerPage' => 10,
            'currentPage' => $_GET['paged'] ?? 1,
            'tableClass' => 'w-full bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden',
            'emptyMessage' => '<div class="text-center py-16"><h3 class="text-xl font-medium text-gray-900 mb-3">No waybills in warehouse</h3><p class="text-gray-500 mb-6 text-lg">Get started by creating your first waybill</p><a href="?page=08600-waybill-create" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-lg">Create Waybill</a></div>',
            'id' => 'warehouseTable',
            'headerClass' => 'bg-gray-50 border-b border-gray-200',
            'rowClass' => 'hover:bg-gray-50 transition-colors duration-150 border-b border-gray-100',
            'cellClass' => 'px-6 py-4 text-sm text-gray-900',
            'headerCellClass' => 'px-6 py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider',
        ];

        // Table columns
        $table_columns = [
            'checkbox' => ['label' => '<input type="checkbox" id="select-all-table" style="width: 16px; height: 16px;">', 'align' => 'text-center'],
            'waybill_no' => ['label' => 'Waybill No', 'align' => 'text-left'],
            'customer_name' => ['label' => 'Customer', 'align' => 'text-left'],
            'company_name' => ['label' => 'Company', 'align' => 'text-left'],
            'product_invoice_amount' => ['label' => 'Amount', 'align' => 'text-right'],
            'total_mass_kg' => ['label' => 'Weight', 'align' => 'text-center'],
            'created_at' => ['label' => 'Created', 'align' => 'text-center'],
            'actions' => ['label' => 'Actions', 'align' => 'text-center'],
        ];

        $table_actions = function ($key, $row) {
            if ($key === 'checkbox') {
                return '<input type="checkbox" name="waybill_ids[]" value="' . $row->id . '" class="waybill-checkbox-table" style="width: 16px; height: 16px;">';
            }
            if ($key === 'waybill_no') {
                return '<span class="font-semibold text-gray-900">#' . $row->waybill_no . '</span>';
            }
            if ($key === 'customer_name') {
                return esc_html($row->customer_name . ' ' . $row->customer_surname);
            }
            if ($key === 'company_name') {
                return esc_html($row->company_name ?: '-');
            }
                                    if ($key === 'product_invoice_amount') {
                            return '<span class="font-semibold text-green-600">' . KIT_Commons::displayWaybillTotal($row->product_invoice_amount) . '</span>';
                        }
            if ($key === 'total_mass_kg') {
                return '<span class="text-gray-600">' . $row->total_mass_kg . ' kg</span>';
            }
            if ($key === 'created_at') {
                return '<span class="text-gray-500">' . date('M j, Y', strtotime($row->created_at)) . '</span>';
            }
            if ($key === 'actions') {
                $html = '<div class="flex space-x-2">';
                $html .= '<a href="?page=08600-Waybill-view&waybill_id=' . $row->id . '" class="inline-flex items-center px-3 py-2 text-xs font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200 transition-colors">View</a>';
                $html .= '<a href="?page=08600-Waybill-view&waybill_id=' . $row->id . '&edit=true" class="inline-flex items-center px-3 py-2 text-xs font-medium text-green-700 bg-green-100 rounded-lg hover:bg-green-200 transition-colors">Edit</a>';
                $html .= '</div>';
                return $html;
            }
            return htmlspecialchars(($row->$key ?? '') ?: '');
        };

        KIT_Commons::render_versatile_table($warehouse_waybills, $table_columns, $table_options, $table_actions);
        ?>
    </div>

    <!-- Quick Links -->
    <div class="quick-links" style="margin-top: 40px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
        <a href="?page=08600-waybill-create" style="display: block; padding: 20px; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; text-decoration: none; border-radius: 12px; text-align: center; box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2); transition: all 0.3s ease;">
            <strong style="font-size: 1.1rem;">Create New Waybill</strong>
        </a>
        <a href="?page=08600-waybill-manage" style="display: block; padding: 20px; background: linear-gradient(135deg, #059669 0%, #047857 100%); color: white; text-decoration: none; border-radius: 12px; text-align: center; box-shadow: 0 4px 6px rgba(5, 150, 105, 0.2); transition: all 0.3s ease;">
            <strong style="font-size: 1.1rem;">Manage All Waybills</strong>
        </a>
        <a href="?page=08600-customers" style="display: block; padding: 20px; background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%); color: white; text-decoration: none; border-radius: 12px; text-align: center; box-shadow: 0 4px 6px rgba(220, 38, 38, 0.2); transition: all 0.3s ease;">
            <strong style="font-size: 1.1rem;">Manage Customers</strong>
        </a>
        <a href="?page=route-management" style="display: block; padding: 20px; background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%); color: white; text-decoration: none; border-radius: 12px; text-align: center; box-shadow: 0 4px 6px rgba(124, 58, 237, 0.2); transition: all 0.3s ease;">
            <strong style="font-size: 1.1rem;">Manage Routes</strong>
        </a>
    </div>
</div>

<style>
    /* Hide WordPress admin footer text that's overlapping */
    .wp-footer, 
    .wp-admin .wp-footer,
    .wp-admin .wp-footer a,
    .wp-admin .wp-footer p {
        display: none !important;
    }
    
    /* Hide any overlapping WordPress notices */
    .notice,
    .updated,
    .error,
    .warning {
        position: relative !important;
        z-index: 1 !important;
    }
    
    /* Ensure table has proper z-index */
    .warehouse-waybills {
        position: relative;
        z-index: 10;
    }
    
    /* Hide specific overlapping text */
    .wp-admin .wrap:after,
    .wp-admin .wrap:before {
        display: none !important;
    }
    
    /* Hide WordPress footer text specifically */
    .wp-admin .wrap p:contains("Thank you for creating with WordPress"),
    .wp-admin .wrap p:contains("Version"),
    .wp-admin .wrap:contains("Thank you for creating with WordPress"),
    .wp-admin .wrap:contains("Version 6.8.2") {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }
    
    /* Force hide any overlapping elements */
    .wp-admin .wrap > *:last-child:not(.warehouse-waybills):not(.warehouse-table):not(.quick-links) {
        display: none !important;
    }
    
    /* Modern table enhancements */
    .warehouse-waybills table {
        border-collapse: separate;
        border-spacing: 0;
        font-size: 14px;
    }
    
    .warehouse-waybills th {
        background: #f8fafc;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
        padding: 16px 24px !important;
    }
    
    .warehouse-waybills td {
        border-bottom: 1px solid #f3f4f6;
        transition: all 0.2s ease;
        padding: 20px 24px !important;
        vertical-align: middle;
    }
    
    .warehouse-waybills tr:hover td {
        background-color: #f9fafb;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    /* Action buttons styling */
    .warehouse-waybills .actions a {
        font-size: 13px !important;
        padding: 8px 12px !important;
        border-radius: 8px !important;
        transition: all 0.2s ease !important;
    }
    
    .warehouse-waybills .actions a:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }
    
    /* Quick links hover effects */
    .quick-links a:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 8px 12px rgba(0,0,0,0.15) !important;
    }
    
    /* Warehouse table styling */
    .warehouse-table table {
        border-collapse: separate;
        border-spacing: 0;
        font-size: 14px;
    }
    
    .warehouse-table th {
        background: #f8fafc;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
        padding: 16px 24px !important;
    }
    
    .warehouse-table td {
        border-bottom: 1px solid #f3f4f6;
        transition: all 0.2s ease;
        padding: 16px 24px !important;
        vertical-align: middle;
    }
    
    .warehouse-table tr:hover td {
        background-color: #f9fafb;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .warehouse-table .actions a {
        font-size: 12px !important;
        padding: 6px 10px !important;
        border-radius: 6px !important;
        transition: all 0.2s ease !important;
    }
    
    .warehouse-table .actions a:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }
    
    /* Status badges */
    .warehouse-waybills .status-badge {
        padding: 6px 12px !important;
        border-radius: 20px !important;
        font-size: 12px !important;
        font-weight: 500 !important;
    }
    
    /* Enhanced pagination styling */
    .warehouse-waybills .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 0.5rem;
        margin-top: 2rem;
    }
    
    .warehouse-waybills .pagination button,
    .warehouse-waybills .pagination a {
        padding: 0.5rem 1rem;
        border: 1px solid #d1d5db;
        background: white;
        color: #374151;
        border-radius: 0.375rem;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    
    .warehouse-waybills .pagination button:hover,
    .warehouse-waybills .pagination a:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
    }
    
    .warehouse-waybills .pagination .active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    /* Enhanced empty state */
    .warehouse-waybills .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border-radius: 0.5rem;
        margin: 2rem 0;
    }
    
    /* Assignment section styling */
    .assignment-section .waybill-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
</style>

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
        checkbox.addEventListener('change', updateSelectedCount);
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
    
    if (blockViewBtn && tableViewBtn && blockView && tableView) {
        blockViewBtn.addEventListener('click', function() {
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
            checkbox.addEventListener('change', updateSelectedCountTable);
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
</script>
