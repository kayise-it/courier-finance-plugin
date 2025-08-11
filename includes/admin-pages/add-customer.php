<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include the customer form functions
require_once plugin_dir_path(__FILE__) . '../customers/customers-functions.php';
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Add New Customer</h1>
    <hr class="wp-header-end">

    <!-- Statistics Cards -->
    <div class="dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #2563eb;">
            <h3 style="margin: 0 0 10px 0; color: #2563eb;">Quick Actions</h3>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="?page=08600-customers" class="button" style="text-decoration: none;">← Back to Customers</a>
                <a href="?page=08600-waybill-create" class="button" style="text-decoration: none;">Create Waybill</a>
            </div>
        </div>

        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #059669;">
            <h3 style="margin: 0 0 10px 0; color: #059669;">Customer Form</h3>
            <p style="margin: 5px 0 0 0; color: #64748b;">Fill in the form below to add a new customer</p>
        </div>

        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #dc2626;">
            <h3 style="margin: 0 0 10px 0; color: #dc2626;">Required Fields</h3>
            <p style="margin: 5px 0 0 0; color: #64748b;">Name, Surname, Cell, Email, Company</p>
        </div>

        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #7c3aed;">
            <h3 style="margin: 0 0 10px 0; color: #7c3aed;">Tips</h3>
            <p style="margin: 5px 0 0 0; color: #64748b;">Use accurate contact details for better service</p>
        </div>
    </div>

    <!-- Customer Form Section -->
    <div class="customer-form-section" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin: 0 0 20px 0;">Customer Information</h2>
        
        <?php
        // Display the customer form
        echo customer_form();
        ?>
    </div>

    <!-- Quick Links -->
    <div class="quick-links" style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <a href="?page=08600-customers" style="display: block; padding: 15px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
            <strong>← Back to Customers</strong>
        </a>
        <a href="?page=08600-waybill-create" style="display: block; padding: 15px; background: #059669; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
            <strong>Create Waybill</strong>
        </a>
        <a href="?page=route-management" style="display: block; padding: 15px; background: #dc2626; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
            <strong>Manage Routes</strong>
        </a>
        <a href="?page=kit-deliveries" style="display: block; padding: 15px; background: #7c3aed; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
            <strong>Manage Deliveries</strong>
        </a>
    </div>
</div>
