<?php
if (!defined('ABSPATH')) {
    exit;
}

// Warehouse tracking page content
global $wpdb;
    
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
    <div class="wrap">
        <h1 class="wp-heading-inline">Warehouse Tracking History</h1>
        <hr class="wp-header-end">
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-semibold text-gray-900">Warehouse Actions Log</h2>
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
    <?php
