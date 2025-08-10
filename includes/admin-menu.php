<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the plugin menu and submenu
 */
function plugin_add_menu()
{
    // Main consolidated menu
    add_menu_page(
        'Courier Finance Dashboard', // Page title
        'Courier Finance', // Menu title
        'edit_pages', // Capability
        'courier-finance-dashboard', // Menu slug
        'plugin_main_page', // Callback function (existing dashboard)
        'dashicons-car', // Icon
        6 // Position
    );

    // Dashboard submenu (same as main page)
    add_submenu_page(
        'courier-finance-dashboard', // Parent slug
        'Dashboard', // Page title
        'Dashboard', // Menu title
        'edit_pages', // Capability
        'courier-finance-dashboard', // Same slug as parent
        'plugin_main_page' // Same callback as parent
    );

    // Services & Quotations submenu
    add_submenu_page(
        'courier-finance-dashboard', // Parent slug
        'Services & Quotations', // Page title
        'Services & Quotations', // Menu title
        'edit_pages', // Capability
        '08600-services-quotations', // Keep existing slug
        'plugin_main_page' // Keep existing callback
    );

    // Quotations submenu
    add_submenu_page(
        'courier-finance-dashboard', // Parent slug
        'Quotations', // Page title
        'Quotations', // Menu title
        'manage_options', // Capability
        'Quotations', // Keep existing slug
        'plugin_quotations_list_page' // Keep existing callback
    );

    // View Quotation submenu (hidden)
    add_submenu_page(
        'courier-finance-dashboard', // Parent slug
        'View Quotation', // Page title
        '', // Empty menu title hides it
        'manage_options', // Capability
        'kit-quotation-edit', // Keep existing slug
        'quotation_view_page' // Keep existing callback
    );

    // Waybills submenu
    add_submenu_page(
        'courier-finance-dashboard', // Parent slug
        'Waybills', // Page title
        'Waybills', // Menu title
        'edit_pages', // Capability
        '08600-Waybill', // Keep existing slug
        'plugin_Waybill_list_page' // Keep existing callback
    );

    // Create Waybills submenu
    add_submenu_page(
        'courier-finance-dashboard', // Parent slug
        'Create Waybills', // Page title
        'Create Waybills', // Menu title
        'edit_pages', // Capability
        '08600-Waybill-create', // Keep existing slug
        'waybill_page' // Keep existing callback
    );

    // Test Waybills submenu
    add_submenu_page(
        'courier-finance-dashboard', // Parent slug
        'Test Waybills', // Page title
        'Test Waybills', // Menu title
        'edit_pages', // Capability
        '08600-Waybill-test', // Keep existing slug
        ['KIT_Waybills','waybill_test_page'] // Keep existing callback
    );

    // View Waybill submenu (hidden)
    add_submenu_page(
        'courier-finance-dashboard', // Parent slug
        'View Waybill', // Page title
        '', // Empty menu title hides it
        'edit_pages', // Capability
        '08600-Waybill-view', // Keep existing slug
        ['KIT_Waybills', 'waybillView'] // Keep existing callback
    );

    // Customers submenu
    add_submenu_page(
        'courier-finance-dashboard', // Parent slug
        'Customers', // Page title
        'Customers', // Menu title
        'edit_pages', // Capability
        'customers-dashboard', // Keep existing slug
        'customer_dashboard' // Keep existing callback
    );

    // All Customers submenu
    add_submenu_page(
        'courier-finance-dashboard', // Parent slug
        'All Customers', // Page title
        'All Customers', // Menu title
        'edit_pages', // Capability
        '08600-customers', // Keep existing slug
        'customer_dashboard' // Keep existing callback
    );

    // Edit Customer submenu (hidden)
    add_submenu_page(
        'courier-finance-dashboard', // Parent slug
        'Edit Customer', // Page title
        '', // Empty menu title hides it
        'edit_pages', // Capability
        'edit-customer', // Keep existing slug
        'edit_customer' // Keep existing callback
    );

    // Customer Waybills submenu (hidden)
    add_submenu_page(
        'courier-finance-dashboard', // Parent slug
        'Customer Waybills', // Page title
        '', // Empty menu title hides it
        'edit_pages', // Capability
        'all-customer-waybills', // Keep existing slug
        'view_customer_waybills' // Keep existing callback
    );

    // Invoice Customers submenu (hidden)
    add_submenu_page(
        'courier-finance-dashboard', // Parent slug
        'Invoice Customers', // Page title
        '', // Empty menu title hides it
        'invoice_customers_pages', // Capability
        'invoice-customer-waybills', // Keep existing slug
        'invoice_customer_waybills' // Keep existing callback
    );
}
add_action('admin_menu', 'plugin_add_menu');