<?php
/**
 * Unified Table System Examples
 * 
 * This file demonstrates how to use the new KIT_Unified_Table system
 * to replace all existing table implementations.
 */

// Example 1: Simple table with basic data
function example_simple_table() {
    $data = [
        ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'status' => 'active'],
        ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'status' => 'inactive'],
        ['id' => 3, 'name' => 'Bob Johnson', 'email' => 'bob@example.com', 'status' => 'active'],
    ];

    $columns = [
        'id' => 'ID',
        'name' => 'Name',
        'email' => 'Email',
        'status' => 'Status'
    ];

    $actions = [
        [
            'label' => 'Edit',
            'href' => 'edit.php?id={id}',
            'class' => 'text-blue-600 hover:text-blue-800'
        ],
        [
            'label' => 'Delete',
            'href' => 'delete.php?id={id}',
            'class' => 'text-red-600 hover:text-red-800',
            'onclick' => 'return confirm("Are you sure?")'
        ]
    ];

    return KIT_Unified_Table::simple($data, $columns, [
        'title' => 'Users',
        'actions' => $actions,
        'empty_message' => 'No users found'
    ]);
}

// Example 2: Advanced table with all features
function example_advanced_table() {
    $data = [
        ['id' => 1, 'waybill_no' => 'WB001', 'customer' => 'John Doe', 'amount' => 1500.00, 'status' => 'pending'],
        ['id' => 2, 'waybill_no' => 'WB002', 'customer' => 'Jane Smith', 'amount' => 2300.50, 'status' => 'completed'],
        ['id' => 3, 'waybill_no' => 'WB003', 'customer' => 'Bob Johnson', 'amount' => 980.75, 'status' => 'cancelled'],
    ];

    $columns = [
        'waybill_no' => 'Waybill #',
        'customer' => 'Customer',
        'amount' => [
            'label' => 'Amount',
            'callback' => function($value, $row) {
                return 'R ' . number_format($value, 2);
            }
        ],
        'status' => [
            'label' => 'Status',
            'callback' => function($value, $row) {
                $colors = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'completed' => 'bg-green-100 text-green-800',
                    'cancelled' => 'bg-red-100 text-red-800'
                ];
                $color = $colors[$value] ?? 'bg-gray-100 text-gray-800';
                return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $color . '">' . ucfirst($value) . '</span>';
            }
        ]
    ];

    $actions = [
        [
            'label' => 'View',
            'href' => 'view.php?id={id}',
            'class' => 'text-blue-600 hover:text-blue-800'
        ],
        [
            'label' => 'Edit',
            'href' => 'edit.php?id={id}',
            'class' => 'text-green-600 hover:text-green-800'
        ]
    ];

    return KIT_Unified_Table::advanced($data, $columns, [
        'title' => 'Waybills',
        'subtitle' => 'Manage all waybill records',
        'actions' => $actions,
        'exportable' => true,
        'bulk_actions' => true,
        'selectable' => true,
        'primary_action' => [
            'label' => 'Create New',
            'href' => 'create.php',
            'class' => 'bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700'
        ]
    ]);
}

// Example 3: Server-side table (DataTables)
function example_server_side_table() {
    $columns = [
        'route_id' => 'Route ID',
        'origin' => 'Origin',
        'destination' => 'Destination',
        'description' => 'Description',
        'status' => [
            'label' => 'Status',
            'sortable' => false,
            'searchable' => false
        ]
    ];

    $actions = [
        [
            'label' => 'Edit',
            'href' => 'edit-route.php?id={route_id}',
            'class' => 'text-blue-600 hover:text-blue-800'
        ]
    ];

    return KIT_Unified_Table::server_side($columns, [
        'title' => 'Routes',
        'ajax_url' => admin_url('admin-ajax.php'),
        'ajax_action' => 'routes_datatable',
        'actions' => $actions
    ]);
}

// Example 4: Migration from old waybill dashboard
function migrate_waybill_dashboard() {
    // Old code (commented out):
    /*
    echo '<div class="bg-white shadow rounded-lg overflow-hidden">';
    echo '<table class="min-w-full text-left text-xs text-gray-700">';
    echo '<thead class="bg-gray-50">';
    echo '<tr>';
    echo '<th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Waybill #</th>';
    echo '<th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Name</th>';
    echo '<th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Approval</th>';
    echo '<th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Total</th>';
    echo '<th class="px-6 py-3 font-medium text-gray-500 uppercase tracking-wider">Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody class="bg-white divide-y divide-gray-200">';
    
    foreach ($allWaybills as $row) {
        echo '<tr>';
        echo '<td class="px-6 py-4 whitespace-nowrap">' . esc_html($row->waybill_no) . '</td>';
        echo '<td class="px-6 py-4 whitespace-nowrap">' . esc_html($row->customer_name . ' ' . $row->customer_surname) . '</td>';
        echo '<td class="px-6 py-4 whitespace-nowrap">' . esc_html($row->approval) . '</td>';
        echo '<td class="px-6 py-4 whitespace-nowrap text-right">';
        if (KIT_User_Roles::can_see_prices()) {
            echo KIT_Commons::currency() . ' ' . ((int) $row->product_invoice_amount + (int) $row->miscellaneous);
        } else {
            echo '***';
        }
        echo '</td>';
        echo '<td class="px-6 py-4 whitespace-nowrap text-center">';
        echo '<a href="?page=08600-Waybill-view&waybill_id=' . $row->waybill_id . '" class="text-blue-600 hover:underline">View</a> ';
        echo '<a href="?page=08600-Waybill&delete_waybill=' . $row->waybill_no . '" class="text-red-600 hover:underline" onclick="return confirm(\'Are you sure you want to delete this waybill?\');">Delete</a>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    */

    // New code using unified table:
    $allWaybills = KIT_Waybills::get_waybills(['fields' => 'w.waybill_no, w.product_invoice_number, w.created_at, c.name, c.surname',]);

    // Convert to array format for unified table
    $waybillData = [];
    foreach ($allWaybills as $row) {
        $waybillData[] = [
            'waybill_no' => $row->waybill_no,
            'customer_name' => $row->customer_name . ' ' . $row->customer_surname,
            'approval' => $row->approval,
            'total' => KIT_User_Roles::can_see_prices() ? 
                KIT_Commons::currency() . ' ' . ((int) $row->product_invoice_amount + (int) $row->miscellaneous) : '***',
            'waybill_id' => $row->waybill_id,
            'waybill_no_raw' => $row->waybill_no
        ];
    }

    // Define columns
    $columns = [
        'waybill_no' => 'Waybill #',
        'customer_name' => 'Name',
        'approval' => 'Approval',
        'total' => 'Total'
    ];

    // Define actions
    $actions = [
        [
            'label' => 'View',
            'href' => '?page=08600-Waybill-view&waybill_id={waybill_id}',
            'class' => 'text-blue-600 hover:text-blue-800'
        ],
        [
            'label' => 'Delete',
            'href' => '?page=08600-Waybill&delete_waybill={waybill_no_raw}',
            'class' => 'text-red-600 hover:text-red-800',
            'onclick' => 'return confirm("Are you sure you want to delete this waybill?")'
        ]
    ];

    // Render unified table
    return KIT_Unified_Table::simple($waybillData, $columns, [
        'title' => 'All Waybills',
        'actions' => $actions,
        'empty_message' => 'No waybills found'
    ]);
}

// Example 5: Migration from DataTables routes
function migrate_routes_table() {
    // Old code (commented out):
    /*
    echo '<table id="routes-dt" class="display stripe hover" style="width: 100%">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>Route ID</th>';
    echo '<th>Origin</th>';
    echo '<th>Destination</th>';
    echo '<th>Description</th>';
    echo '<th>Status</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody></tbody>';
    echo '</table>';
    // ... DataTables JavaScript ...
    */

    // New code using unified table:
    $routes = KIT_Routes::get_routes();
    
    // Convert to array format for unified table
    $routeData = [];
    foreach ($routes as $route) {
        $status = $route->is_active ? 
            '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Active</span>' : 
            '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">Inactive</span>';
            
        $routeData[] = [
            'route_id' => $route->route_id,
            'origin_country_name' => $route->origin_country_name,
            'destination_country_name' => $route->destination_country_name,
            'description' => $route->description,
            'status' => $status,
            'is_active' => $route->is_active
        ];
    }

    // Define columns
    $columns = [
        'route_id' => 'Route ID',
        'origin_country_name' => 'Origin',
        'destination_country_name' => 'Destination',
        'description' => 'Description',
        'status' => [
            'label' => 'Status',
            'sortable' => false,
            'searchable' => false
        ]
    ];

    // Define actions
    $actions = [
        [
            'label' => 'Edit',
            'href' => '?page=route-create&route_id={route_id}&route_atts=edit_route',
            'class' => 'text-blue-600 hover:text-blue-800'
        ]
    ];

    // Render unified table with advanced features
    return KIT_Unified_Table::advanced($routeData, $columns, [
        'title' => 'All Routes',
        'actions' => $actions,
        'searchable' => true,
        'sortable' => true,
        'exportable' => true,
        'empty_message' => 'No routes found'
    ]);
}

// Usage examples:
// echo example_simple_table();
// echo example_advanced_table();
// echo example_server_side_table();
// echo migrate_waybill_dashboard();
// echo migrate_routes_table();
?>
