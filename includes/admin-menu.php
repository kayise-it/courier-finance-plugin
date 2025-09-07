<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the unified 08600 Solution menu and submenu
 */
function plugin_add_menu()
{
    // Main 08600 Solution Menu
    add_menu_page(
        '08600 Solution', // Page title
        '08600 Solution', // Menu title
        'kit_view_waybills', // Capability
        '08600-waybills', // Menu slug
        'waybill_dashboard_page', // Callback function
        'dashicons-admin-plugins', // Icon
        6 // Position
    );

    // Dashboard
    add_submenu_page(
        '08600-waybills',
        'Dashboard',
        'Dashboard',
        'kit_view_waybills',
        '08600-waybills',
        'waybill_dashboard_page'
    );

    // Create Waybill
    add_submenu_page(
        '08600-waybills',
        'Create Waybill',
        'Create Waybill',
        'kit_view_waybills',
        '08600-waybill-create',
        'waybill_page'
    );

    // Manage Waybills
    add_submenu_page(
        '08600-waybills',
        'Manage Waybills',
        'Manage Waybills',
        'kit_view_waybills',
        '08600-waybill-manage',
        'plugin_Waybill_list_page'
    );

    // Warehouse
    add_submenu_page(
        '08600-waybills',
        'Warehouse',
        'Warehouse',
        'kit_view_waybills',
        'warehouse-waybills',
        'warehouse_waybills_page'
    );







    // Customers
    add_submenu_page(
        '08600-waybills',
        'Customers',
        'Customers',
        'kit_view_waybills',
        '08600-customers',
        'customer_dashboard'
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



    // Routes & Destinations
    add_submenu_page(
        '08600-waybills',
        'Routes & Destinations',
        'Routes & Destinations',
        'kit_view_waybills',
        'route-management',
        ['KIT_Routes', 'plugin_route_management_page']
    );

    // Deliveries
    add_submenu_page(
        '08600-waybills',
        'Deliveries',
        'Deliveries',
        'kit_view_waybills',
        'kit-deliveries',
        ['KIT_Deliveries', 'render_admin_page']
    );
    
    // Warehouse Tracking - Now integrated into main warehouse page
    // add_submenu_page(
    //     '08600-waybills',
    //     'Warehouse Tracking',
    //     'Warehouse Tracking',
    //     'edit_pages',
    //     'warehouse-tracking',
    //     'warehouse_tracking_page'
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
        '08600-waybills',
        'Settings',
        'Settings',
        'kit_access_settings', // Only specific administrators (Thando, Mel, Patricia) can see this menu item
        '08600-settings',
        'waybill_settings_page'
    );

    // Help (at bottom bottom)
    add_submenu_page(
        '08600-waybills',
        'Help',
        'Help',
        'kit_view_waybills',
        '08600-help',
        'waybill_help_page'
    );

}

add_action('admin_menu', 'plugin_add_menu');

// Callback functions for new menu pages
function waybill_dashboard_page() {
    include plugin_dir_path(__FILE__) . 'admin-pages/dashboard.php';
}





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

// Handle add customer form submission
function handle_add_customer_form() {
    // Debug: Log form submission
    error_log('Add customer form submitted');
    error_log('POST data: ' . print_r($_POST, true));
    
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

// function warehouse_tracking_page() {
//     include plugin_dir_path(__FILE__) . 'admin-pages/warehouse-tracking.php';
// }







