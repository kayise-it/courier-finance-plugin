<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the unified 08600 Solution menu and submenu
 */
function plugin_add_menu()
{
    // Main 08600 Solution Menu (use Manage Waybills as the top-level page)
    add_menu_page(
        '08600 Solution', // Page title
        '08600 Solution', // Menu title
        'kit_view_waybills', // Capability
        '08600-waybill-manage', // Menu slug (top-level now points to Manage Waybills)
        'plugin_Waybill_list_page', // Callback function
        'dashicons-admin-plugins', // Icon
        6 // Position
    );

    // Create Waybill
    add_submenu_page(
        '08600-waybill-manage',
        'Create Waybill',
        'Create Waybill',
        'kit_view_waybills',
        '08600-waybill-create',
        'waybill_page'
    );

    // Manage Waybills
    add_submenu_page(
        '08600-waybill-manage',
        'Manage Waybills',
        'Manage Waybills',
        'kit_view_waybills',
        '08600-waybill-manage',
        'plugin_Waybill_list_page'
    );

    // Warehouse
    add_submenu_page(
        '08600-waybill-manage',
        'Warehouse',
        'Warehouse',
        'kit_view_waybills',
        'warehouse-waybills',
        'warehouse_waybills_page'
    );







    // Customers
    add_submenu_page(
        '08600-waybill-manage',
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
        '08600-waybill-manage',
        'Routes & Destinations',
        'Routes & Destinations',
        'kit_view_waybills',
        'route-management',
        ['KIT_Routes', 'plugin_route_management_page']
    );

    // Countries
    add_submenu_page(
        '08600-waybill-manage',
        'Countries',
        'Countries',
        'kit_view_waybills',
        '08600-countries',
        'countries_management_page'
    );

    // Deliveries
    add_submenu_page(
        '08600-waybill-manage',
        'Deliveries',
        'Deliveries',
        'kit_view_waybills',
        'kit-deliveries',
        ['KIT_Deliveries', 'render_admin_page']
    );

    // Drivers
    add_submenu_page(
        '08600-waybill-manage',
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
        '08600-waybill-manage',
        'Settings',
        'Settings',
        'kit_access_settings', // Only specific administrators (Thando, Mel, Patricia) can see this menu item
        '08600-settings',
        'waybill_settings_page'
    );

    // Help (at bottom bottom)
    add_submenu_page(
        '08600-waybill-manage',
        'Help',
        'Help',
        'kit_view_waybills',
        '08600-help',
        'waybill_help_page'
    );

}

add_action('admin_menu', 'plugin_add_menu');

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
        echo '<div class="wrap"><div class="notice notice-error"><p>Invalid customer ID.</p></div></div>';
        return;
    }
    
    // Call the edit customer form function
    edit_customer_form($customer_id);
}







