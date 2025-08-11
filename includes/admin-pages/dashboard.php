<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
global $wpdb;
$waybills_table = $wpdb->prefix . 'kit_waybills';
$customers_table = $wpdb->prefix . 'kit_customers';
$deliveries_table = $wpdb->prefix . 'kit_deliveries';

$total_waybills = $wpdb->get_var("SELECT COUNT(*) FROM $waybills_table");
$total_customers = $wpdb->get_var("SELECT COUNT(*) FROM $customers_table");
$total_deliveries = $wpdb->get_var("SELECT COUNT(*) FROM $deliveries_table");

// Get recent waybills with customer information
$recent_waybills_query = "
    SELECT w.*, c.name as customer_name, c.surname as customer_surname, c.company_name
    FROM $waybills_table w
    LEFT JOIN $customers_table c ON w.customer_id = c.cust_id
    ORDER BY w.created_at DESC 
    LIMIT 5
";
$recent_waybills = $wpdb->get_results($recent_waybills_query);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">08600 Waybills Dashboard</h1>
    <hr class="wp-header-end">

    <!-- Statistics Cards -->
    <div class="dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #2563eb;">
            <h3 style="margin: 0 0 10px 0; color: #2563eb;">Total Waybills</h3>
            <div style="font-size: 2em; font-weight: bold; color: #1e293b;"><?php echo number_format($total_waybills); ?></div>
            <p style="margin: 5px 0 0 0; color: #64748b;">All time waybills created</p>
        </div>

        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #059669;">
            <h3 style="margin: 0 0 10px 0; color: #059669;">Total Customers</h3>
            <div style="font-size: 2em; font-weight: bold; color: #1e293b;"><?php echo number_format($total_customers); ?></div>
            <p style="margin: 5px 0 0 0; color: #64748b;">Registered customers</p>
        </div>

        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #dc2626;">
            <h3 style="margin: 0 0 10px 0; color: #dc2626;">Total Deliveries</h3>
            <div style="font-size: 2em; font-weight: bold; color: #1e293b;"><?php echo number_format($total_deliveries); ?></div>
            <p style="margin: 5px 0 0 0; color: #64748b;">Scheduled deliveries</p>
        </div>

        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #7c3aed;">
            <h3 style="margin: 0 0 10px 0; color: #7c3aed;">Quick Actions</h3>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="?page=08600-waybill-create" class="button button-primary" style="text-decoration: none;">Create Waybill</a>
                <a href="?page=08600-customers" class="button" style="text-decoration: none;">Add Customer</a>
            </div>
        </div>
    </div>

    <!-- Recent Waybills -->
    <div class="recent-waybills" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin: 0 0 20px 0;">Recent Waybills</h2>
        
        <?php
        $options = [
            'itemsPerPage' => 5,
            'currentPage' => $_GET['paged'] ?? 1,
            'tableClass' => 'min-w-full text-left text-xs text-gray-700',
            'emptyMessage' => 'No waybills found. <a href="?page=08600-waybill-create">Create your first waybill</a>',
            'id' => 'dashboardWaybillsTable',
        ];

        $columns = [
            'waybill_no' => ['label' => 'Waybill #', 'align' => 'text-left'],
            'customer_name' => ['label' => 'Customer', 'align' => 'text-left'],
            'company_name' => ['label' => 'Company', 'align' => 'text-left'],
            'product_invoice_amount' => ['label' => 'Amount', 'align' => 'text-right'],
            'status' => ['label' => 'Status', 'align' => 'text-center'],
            'created_at' => ['label' => 'Date', 'align' => 'text-left'],
            'actions' => ['label' => 'Actions', 'align' => 'text-center'],
        ];

        $waybill_actions = function ($key, $row) {
            if ($key === 'customer_name') {
                return $row->customer_name . ' ' . $row->customer_surname;
            }
                                    if ($key === 'product_invoice_amount') {
                            return KIT_Commons::displayWaybillTotal($row->product_invoice_amount);
                        }
            if ($key === 'status') {
                $bg_color = $row->status === 'completed' ? '#10b981' : ($row->status === 'pending' ? '#f59e0b' : '#6b7280');
                return '<span class="status-badge" style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; background: ' . $bg_color . '; color: white;">' . ucfirst($row->status) . '</span>';
            }
            if ($key === 'created_at') {
                return date('M j, Y', strtotime($row->created_at));
            }
            if ($key === 'actions') {
                return '<a href="?page=08600-Waybill-view&waybill_id=' . $row->id . '" class="button button-small">View</a> <a href="?page=08600-Waybill-view&waybill_id=' . $row->id . '&edit=true" class="button button-small">Edit</a>';
            }
            return htmlspecialchars(($row->$key ?? '') ?: '');
        };

        echo KIT_Commons::render_versatile_table($recent_waybills, $columns, $waybill_actions, $options);
        ?>
    </div>

    <!-- Quick Links -->
    <div class="quick-links" style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <a href="?page=08600-waybill-create" style="display: block; padding: 15px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
            <strong>Create New Waybill</strong>
        </a>
        <a href="?page=08600-customers" style="display: block; padding: 15px; background: #059669; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
            <strong>Manage Customers</strong>
        </a>
        <a href="?page=route-management" style="display: block; padding: 15px; background: #dc2626; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
            <strong>Routes & Destinations</strong>
        </a>
        <a href="?page=kit-deliveries" style="display: block; padding: 15px; background: #7c3aed; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
            <strong>Manage Deliveries</strong>
        </a>
    </div>
</div>
