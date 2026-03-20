<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the unified 08600 Solution menu and submenu
 */
function plugin_add_menu()
{
    // Main 08600 Solution Menu (Dashboard is the default landing page)
    add_menu_page(
        '08600 Solution',
        '08600 Solution',
        'kit_view_waybills',
        '08600-dashboard',
        'dashboard_page',
        'dashicons-admin-plugins',
        6
    );

    // Dashboard (first submenu = default when clicking parent)
    add_submenu_page(
        '08600-dashboard',
        'Dashboard',
        'Dashboard',
        'kit_view_waybills',
        '08600-dashboard',
        'dashboard_page'
    );

    // Create Waybill
    add_submenu_page(
        '08600-dashboard',
        'Create Waybill',
        'Create Waybill',
        'kit_view_waybills',
        '08600-waybill-create',
        'waybill_page'
    );

    // Manage Waybills
    add_submenu_page(
        '08600-dashboard',
        'Manage Waybills',
        'Manage Waybills',
        'kit_view_waybills',
        '08600-waybill-manage',
        'plugin_Waybill_list_page'
    );

    // Warehouse
    add_submenu_page(
        '08600-dashboard',
        'Warehouse',
        'Warehouse',
        'kit_view_waybills',
        'warehouse-waybills',
        'warehouse_waybills_page'
    );







    // Customers
    add_submenu_page(
        '08600-dashboard',
        'Customers',
        'Customers',
        'kit_view_waybills',
        '08600-customers',
        ['KIT_Customers', 'customer_dashboard_page']
    );

    // Add Customer (hidden page)
    add_submenu_page(
        null, // No parent menu
        'Add Customer',
        'Add Customer',
        'kit_view_waybills',
        '08600-add-customer',
        'add_customer_page'
    );

    // Edit Customer (hidden page)
    add_submenu_page(
        null, // No parent menu
        'Edit Customer',
        'Edit Customer',
        'kit_view_waybills',
        'edit-customer',
        'edit_customer_page'
    );



    // Routes & Destinations
    add_submenu_page(
        '08600-dashboard',
        'Routes & Destinations',
        'Routes & Destinations',
        'kit_view_waybills',
        'route-management',
        ['KIT_Routes', 'plugin_route_management_page']
    );

    // Countries
    add_submenu_page(
        '08600-dashboard',
        'Countries',
        'Countries',
        'kit_view_waybills',
        '08600-countries',
        'countries_management_page'
    );

    // Deliveries
    add_submenu_page(
        '08600-dashboard',
        'Deliveries',
        'Deliveries',
        'kit_view_waybills',
        'kit-deliveries',
        ['KIT_Deliveries', 'render_admin_page']
    );

    // Drivers
    add_submenu_page(
        '08600-dashboard',
        'Manage Drivers',
        'Drivers',
        'kit_view_waybills',
        'manage-drivers',
        'drivers_management_page'
    );
    
    // Warehouse Tracking - Now integrated into main warehouse page
    // add_submenu_page(
    //     '08600-waybills',
    //     'Warehouse Tracking',
    //     'Warehouse Tracking',
    //     'edit_pages',
    //     'warehouse-tracking',
    //     'warehouse_page'
    // );







    // Hidden pages for direct access
<<<<<<< HEAD
    // Google Sheets test (hidden)
    add_submenu_page(
        null,
        'Google Sheets Test',
        'Google Sheets Test',
        'kit_view_waybills',
        '08600-google-sheets-test',
        'courier_google_sheets_test_page'
    );

=======
>>>>>>> 5cbaa90360699e03b8fac099559de25a0a4ad7ff
    add_submenu_page(
        null, // No parent menu
        'View Waybill',
        'View Waybill',
        'kit_view_waybills',
        '08600-Waybill-view',
        ['KIT_Waybills', 'waybillView']
    );
    
    add_submenu_page(
        null, // No parent menu
        'Create/Edit Route',
        'Create/Edit Route',
        'kit_view_waybills',
        'route-create',
        ['KIT_Routes', 'route_create_page']
    );
    
    add_submenu_page(
        null, // No parent menu
        'View Delivery',
        'View Delivery',
        'kit_view_waybills',
        'view-deliveries',
        ['KIT_Deliveries', 'view_deliveries_page']
    );

    // Note: Other hidden pages (Create Route, Edit Customer, etc.)
    // are accessed via direct URLs and don't need to be registered as submenu items
    // This prevents blank spaces in the menu structure

    // Quotations removed - not used

    // Settings (at bottom) - STRICTLY RESTRICTED ACCESS
    add_submenu_page(
        '08600-dashboard',
        'Settings',
        'Settings',
        'kit_access_settings',
        '08600-settings',
        'waybill_settings_page'
    );

    // Help (at bottom bottom)
    add_submenu_page(
        '08600-dashboard',
        'Help',
        'Help',
        'kit_view_waybills',
        '08600-help',
        'waybill_help_page'
    );

}

/**
 * Dashboard page callback (default landing for 08600 Solution)
 */
function dashboard_page() {
    include plugin_dir_path(__FILE__) . 'admin-pages/dashboard.php';
}

<<<<<<< HEAD
function courier_google_sheets_test_page() {
    require COURIER_FINANCE_PLUGIN_PATH . 'includes/admin-pages/google-sheets-test.php';
}

add_action('admin_menu', 'plugin_add_menu');

/**
 * Add "Create New Waybill" to the WordPress admin bar: top-level (visible) and under "New" dropdown.
 */
function plugin_add_admin_bar_waybill($wp_admin_bar) {
    if (!is_admin_bar_showing()) {
        return;
    }
    $can = current_user_can('kit_view_waybills') || current_user_can('manage_options') || current_user_can('edit_pages');
    if (!$can) {
        return;
    }
    $url = admin_url('admin.php?page=08600-waybill-create');
    $title = __('Create New Waybill', '08600-services-quotations');
    // Top-level item so it's visible on the bar without opening a dropdown
    $wp_admin_bar->add_node([
        'id'     => '08600-create-waybill',
        'title'  => $title,
        'href'   => $url,
        'parent' => false,
        'meta'   => ['title' => $title],
    ]);
    // Also under "New" dropdown for consistency with Post, Page, etc.
    $wp_admin_bar->add_node([
        'parent' => 'new-content',
        'id'     => '08600-new-waybill',
        'title'  => $title,
        'href'   => $url,
    ]);
}
add_action('admin_bar_menu', 'plugin_add_admin_bar_waybill', 25);

/**
 * AJAX handler for manual sync (Push to Sheet / Pull from Sheet)
 */
function handle_kit_google_sheet_sync() {
    // Ensure response is only JSON (no stray HTML from other code)
    while (ob_get_level()) {
        ob_end_clean();
    }
    // #region agent log
    $log_path = defined('COURIER_FINANCE_PLUGIN_PATH') ? COURIER_FINANCE_PLUGIN_PATH . '.cursor/debug-a8826f.log' : '';
    if ($log_path) {
        @file_put_contents($log_path, json_encode(['sessionId'=>'a8826f','hypothesisId'=>'B','location'=>'admin-menu.php:handle_kit_google_sheet_sync:entry','message'=>'sync handler entered','data'=>['entity'=>$_POST['entity']??'','direction'=>$_POST['direction']??'','has_nonce'=>isset($_POST['nonce'])],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND | LOCK_EX);
    }
    // #endregion
    if (!current_user_can('kit_view_waybills')) {
        if ($log_path) { @file_put_contents($log_path, json_encode(['sessionId'=>'a8826f','hypothesisId'=>'B','location'=>'admin-menu.php:handle_kit_google_sheet_sync:auth','message'=>'sync rejected: unauthorized','data'=>[],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND | LOCK_EX); }
        wp_send_json_error(['message' => 'Unauthorized']);
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'kit_google_sheet_sync')) {
        if ($log_path) { @file_put_contents($log_path, json_encode(['sessionId'=>'a8826f','hypothesisId'=>'B','location'=>'admin-menu.php:handle_kit_google_sheet_sync:nonce','message'=>'sync rejected: invalid nonce','data'=>[],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND | LOCK_EX); }
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    $entity = sanitize_text_field($_POST['entity'] ?? '');
    $direction = sanitize_text_field($_POST['direction'] ?? '');
    $allowed = ['drivers', 'customers', 'deliveries', 'waybills'];
    if (!in_array($entity, $allowed, true) || !in_array($direction, ['push', 'pull'], true)) {
        if ($log_path) { @file_put_contents($log_path, json_encode(['sessionId'=>'a8826f','hypothesisId'=>'B','location'=>'admin-menu.php:handle_kit_google_sheet_sync:invalid','message'=>'sync rejected: invalid entity or direction','data'=>['entity'=>$entity,'direction'=>$direction],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND | LOCK_EX); }
        wp_send_json_error(['message' => 'Invalid entity or direction']);
    }
    if (!class_exists('Courier_Google_Sheets_Sync')) {
        if ($log_path) { @file_put_contents($log_path, json_encode(['sessionId'=>'a8826f','hypothesisId'=>'B','location'=>'admin-menu.php:handle_kit_google_sheet_sync:class','message'=>'sync rejected: class not found','data'=>[],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND | LOCK_EX); }
        wp_send_json_error(['message' => 'Sync not available']);
    }
    // #region agent log
    if ($log_path) { @file_put_contents($log_path, json_encode(['sessionId'=>'a8826f','hypothesisId'=>'C','location'=>'admin-menu.php:handle_kit_google_sheet_sync:before_sync','message'=>'calling push_all/pull_all','data'=>['entity'=>$entity,'direction'=>$direction],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND | LOCK_EX); }
    // #endregion
    if ($direction === 'push') {
        $result = Courier_Google_Sheets_Sync::push_all($entity);
    } else {
        if ($entity === 'waybills') {
            // Load seed function without running settings-page access check (user already passed kit_view_waybills)
            if (!defined('COURIER_SEED_SIMULATION')) {
                define('COURIER_SEED_SIMULATION', true);
            }
            $settings = COURIER_FINANCE_PLUGIN_PATH . 'includes/admin-pages/settings.php';
            if (file_exists($settings)) {
                ob_start();
                require_once $settings;
                ob_end_clean();
            }
        }
        $result = Courier_Google_Sheets_Sync::pull_all($entity);
    }
    // #region agent log
    if ($log_path) { @file_put_contents($log_path, json_encode(['sessionId'=>'a8826f','hypothesisId'=>'C','location'=>'admin-menu.php:handle_kit_google_sheet_sync:result','message'=>'sync result','data'=>['success'=>!empty($result['success']),'message'=>$result['message']??'','entity'=>$entity,'direction'=>$direction],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND | LOCK_EX); }
    // #endregion
    if (!empty($result['success'])) {
        delete_option('courier_last_sync_error_bulk');
        while (ob_get_level()) {
            ob_end_clean();
        }
        wp_send_json_success($result);
    }
    $message = $result['message'] ?? 'Sync failed';
    update_option('courier_last_sync_error_bulk', [
        'message'   => $message,
        'entity'    => $entity,
        'direction' => $direction,
        'time'      => time(),
    ], false);
    if ($log_path) { @file_put_contents($log_path, json_encode(['sessionId'=>'a8826f','hypothesisId'=>'D','location'=>'admin-menu.php:handle_kit_google_sheet_sync:send_error','message'=>'sending JSON error response','data'=>['message'=>$message],'timestamp'=>round(microtime(true)*1000)])."\n", FILE_APPEND | LOCK_EX); }
    while (ob_get_level()) {
        ob_end_clean();
    }
    wp_send_json_error(['message' => $message]);
}
add_action('wp_ajax_kit_google_sheet_sync', 'handle_kit_google_sheet_sync');

=======
add_action('admin_menu', 'plugin_add_menu');

>>>>>>> 5cbaa90360699e03b8fac099559de25a0a4ad7ff
// Callback functions for menu pages





function waybill_settings_page() {
    include plugin_dir_path(__FILE__) . 'admin-pages/settings.php';
}

function waybill_help_page() {
    include plugin_dir_path(__FILE__) . 'admin-pages/help.php';
}

function warehouse_waybills_page() {
    include plugin_dir_path(__FILE__) . 'admin-pages/warehouse.php';
}

function add_customer_page() {
    include plugin_dir_path(__FILE__) . 'admin-pages/add-customer.php';
}

function drivers_management_page() {
    include plugin_dir_path(__FILE__) . 'admin-pages/drivers.php';
}

// Handle add customer form submission
function handle_add_customer_form() {
    // Debug: Log form submission
    // Optional debug
    // error_log('Add customer form submitted');
    // error_log('POST data: ' . print_r($_POST, true));
    
    if (wp_verify_nonce($_POST['customer_nonce'], 'add_customer_nonce')) {
        // Include customer functions
        require_once plugin_dir_path(__FILE__) . 'customers/customers-functions.php';
        
        $customer_data = [
            'name' => sanitize_text_field($_POST['name'] ?? ''),
            'surname' => sanitize_text_field($_POST['surname'] ?? ''),
            'cell' => sanitize_text_field($_POST['cell'] ?? ''),
            'email_address' => sanitize_text_field($_POST['email_address'] ?? ''),
            'address' => sanitize_textarea_field($_POST['address'] ?? ''),
            'company_name' => sanitize_text_field($_POST['company_name'] ?? ''),
            'country_id' => intval($_POST['country_id'] ?? 0),
            'city_id' => intval($_POST['city_id'] ?? 0),
            'vat_number' => sanitize_text_field($_POST['vat_number'] ?? ''),
        ];
        
        $customer_id = KIT_Customers::save_customer($customer_data);
        
        if ($customer_id) {
            wp_redirect(admin_url('admin.php?page=08600-customers&customer_added=1'));
            exit;
        } else {
            wp_redirect(admin_url('admin.php?page=08600-add-customer&error=1'));
            exit;
        }
    } else {
        wp_redirect(admin_url('admin.php?page=08600-add-customer&error=1'));
        exit;
    }
}

// Register the action hook
add_action('admin_post_add_customer', 'handle_add_customer_form');

// function warehouse_page() {
//     include plugin_dir_path(__FILE__) . 'admin-pages/warehouse-tracking.php';
// }

/**
 * Countries management page callback
 */
function countries_management_page() {
    include plugin_dir_path(__FILE__) . 'admin-pages/countries.php';
}

/**
 * AJAX handler for quick country status toggle
 */
function handle_toggle_country_status() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'toggle_country_status')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Insufficient permissions']);
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'kit_operating_countries';
    $id = intval($_POST['country_id']);
    $new_status = intval($_POST['new_status']);
    
    $result = $wpdb->update($table, ['is_active' => $new_status], ['id' => $id]);
    
    if ($result !== false) {
        wp_send_json_success(['message' => 'Status updated successfully']);
    } else {
        wp_send_json_error(['message' => 'Failed to update status']);
    }
}

// Register AJAX handlers
add_action('wp_ajax_toggle_country_status', 'handle_toggle_country_status');

/**
 * Edit customer page callback
 */
function edit_customer_page() {
    // Include customer functions
    require_once plugin_dir_path(__FILE__) . 'customers/customers-functions.php';
    
    // Get customer ID from URL
    $customer_id = isset($_GET['edit_customer']) ? intval($_GET['edit_customer']) : 0;
    
    if (!$customer_id) {
        if (!class_exists('KIT_Toast')) {
            require_once plugin_dir_path(__FILE__) . 'components/toast.php';
        }
        KIT_Toast::ensure_toast_loads();
        echo '<div class="wrap">';
        echo KIT_Toast::error('Invalid customer ID.', 'Error');
        echo '</div>';
        return;
    }
    
    // Call the edit customer form function
    edit_customer_form($customer_id);
}







