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
        'edit_pages', // Capability
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
        'edit_pages',
        '08600-waybills',
        'waybill_dashboard_page'
    );

    // Create Waybill
    add_submenu_page(
        '08600-waybills',
        'Create Waybill',
        'Create Waybill',
        'edit_pages',
        '08600-waybill-create',
        'waybill_page'
    );

    // Manage Waybills
    add_submenu_page(
        '08600-waybills',
        'Manage Waybills',
        'Manage Waybills',
        'edit_pages',
        '08600-waybill-manage',
        'plugin_Waybill_list_page'
    );

    // Warehouse
    add_submenu_page(
        '08600-waybills',
        'Warehouse',
        'Warehouse',
        'edit_pages',
        'warehouse-waybills',
        'warehouse_waybills_page'
    );







    // Customers
    add_submenu_page(
        '08600-waybills',
        'Customers',
        'Customers',
        'edit_pages',
        '08600-customers',
        'customer_dashboard'
    );



    // Routes & Destinations
    add_submenu_page(
        '08600-waybills',
        'Routes & Destinations',
        'Routes & Destinations',
        'edit_pages',
        'route-management',
        ['KIT_Routes', 'plugin_route_management_page']
    );

    // Deliveries
    add_submenu_page(
        '08600-waybills',
        'Deliveries',
        'Deliveries',
        'edit_pages',
        'kit-deliveries',
        ['KIT_Deliveries', 'render_admin_page']
    );







    // Hidden pages for direct access
    add_submenu_page(
        null, // No parent menu
        'View Waybill',
        'View Waybill',
        'edit_pages',
        '08600-Waybill-view',
        ['KIT_Waybills', 'waybillView']
    );
    
    add_submenu_page(
        null, // No parent menu
        'Create/Edit Route',
        'Create/Edit Route',
        'edit_pages',
        'route-create',
        ['KIT_Routes', 'route_create_page']
    );

    // Note: Other hidden pages (View Delivery, Create Route, Edit Customer, etc.)
    // are accessed via direct URLs and don't need to be registered as submenu items
    // This prevents blank spaces in the menu structure

    // Quotations (moved under 08600 Waybills menu)
    add_submenu_page(
        '08600-waybills',
        'Quotations',
        'Quotations',
        'manage_options',
        'Quotations',
        'plugin_quotations_list_page'
    );

    // Settings (at bottom)
    add_submenu_page(
        '08600-waybills',
        'Settings',
        'Settings',
        'edit_pages',
        '08600-settings',
        'waybill_settings_page'
    );

    // Help (at bottom bottom)
    add_submenu_page(
        '08600-waybills',
        'Help',
        'Help',
        'edit_pages',
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







