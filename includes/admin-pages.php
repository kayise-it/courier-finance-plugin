<?php
// ob_start(); // Start buffering the output - REMOVED FOR DEBUGGING

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Include user roles for permission checking
require_once plugin_dir_path(__FILE__) . 'user-roles.php';

// Include unified table class if not already loaded
if (!class_exists('KIT_Unified_Table')) {
    $class_file = plugin_dir_path(__FILE__) . 'class-unified-table.php';
    if (file_exists($class_file)) {
        require_once $class_file;
    } else {
        error_log('KIT_Unified_Table class file not found at: ' . $class_file);
        // Try alternative path
        $alt_class_file = dirname(plugin_dir_path(__FILE__)) . '/class-unified-table.php';
        if (file_exists($alt_class_file)) {
            require_once $alt_class_file;
        } else {
            error_log('KIT_Unified_Table class file not found at alternative path: ' . $alt_class_file);
        }
    }
}

function waybill_page()
{
    // Show success toast if waybill was created using KITToast
    if (isset($_GET['success']) && $_GET['success'] == '1') {
        $waybill_no = isset($_GET['waybill_no']) ? sanitize_text_field($_GET['waybill_no']) : '';
        $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : 'Waybill created successfully!';

        if ($waybill_no) {
            $message = $message . ' Waybill #: ' . $waybill_no;
        }

        // Load and show the toast using the component
        if (!class_exists('KIT_Toast')) {
            require_once plugin_dir_path(__FILE__) . 'components/toast.php';
        }

        KIT_Toast::ensure_toast_loads();
        echo KIT_Toast::show($message, 'success', 'Waybill Created');
    }

    echo do_shortcode('[kit_waybill_form]');
}
function plugin_Waybill_list_page()
{
    // Debug: Check if class exists
    if (!class_exists('KIT_Unified_Table')) {
        error_log('KIT_Unified_Table class not found in plugin_Waybill_list_page');
        echo '<div class="error"><p>Error: KIT_Unified_Table class not found. Please check the logs.</p></div>';
        return;
    }

    // Warehouse database updates and simulations no longer needed - using kit_waybills table directly

    // Handle delete BEFORE any redirects so the action is not lost
    if (isset($_GET["delete_waybill"])) {
        KIT_Waybills::delete_waybill($_GET["delete_waybill"]);
        exit;
    }

    // Remove legacy per_page redirect to support infinite scroll and AJAX search

    if (isset($_GET['generate_quote'])) {
        echo "Generating quote...";
        KIT_Waybills::generate_Waybill_quote();
    }

    // Delete is handled earlier to avoid losing the action due to redirects
    // Include the modal component
    $customers   = KIT_Customers::tholaMaCustomer();
    $form_action = admin_url('admin-post.php?action=add_waybill_action');

    $modal_path = realpath(plugin_dir_path(__FILE__) . './components/modal.php');

    if ($modal_path && file_exists($modal_path)) {
        require_once $modal_path;
    } else {
        error_log("Modal.php not found at: " . ($modal_path ?: '(null)'));
        // Optional: Show a safe error or fallback content
    }



    // Handle search functionality
    $search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

    // Handle sorting functionality
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'w.created_at';
    $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

    // Map frontend column names to database fields
    $orderby_map = [
        'waybill_no' => 'w.waybill_no',
        'customer_name' => 'c.name',
        'created_by' => 'u.display_name',
        'destination' => 'dest_city.city_name',
        'approval' => 'w.approval',
        'total' => 'w.product_invoice_amount'
    ];
    $warehouse = "false";

    $db_orderby = isset($orderby_map[$orderby]) ? $orderby_map[$orderby] : 'w.created_at';

    $total_waybills = KIT_Waybills::get_waybill_count($search_term);
    $allWaybills = KIT_Waybills::getAllWaybills();

    // Convert to array format for unified table
    $waybillData = [];
    foreach ($allWaybills as $row) {
        // Debug: Check if customer data exists
        $customer_name = '';
        if (isset($row->customer_name) && isset($row->customer_surname)) {
            $customer_name = trim($row->customer_name . ' ' . $row->customer_surname);
        } else {
            $customer_name = 'No Customer Data';
        }

        // Build destination: prefer City, Country. Append route if meaningful (not "Warehoused items").
        $route_desc = isset($row->route_description) ? trim((string)$row->route_description) : '';
        $city_name  = isset($row->customer_city) ? trim((string)$row->customer_city) : '';
        $country    = isset($row->destination_country_name) ? trim((string)$row->destination_country_name) : '';

        $dest_parts = [];
        if ($city_name !== '') {
            $dest_parts[] = $city_name;
        }
        if ($country !== '') {
            $dest_parts[] = $country;
        }
        $dest_label = implode(', ', $dest_parts);

        if ($route_desc !== '' && strcasecmp($route_desc, 'Warehoused items') !== 0) {
            $dest_label = $route_desc . ($dest_label !== '' ? ' — ' . $dest_label : '');
        }

        $waybillData[] = [
            'waybill_no' => $row->waybill_no ?? 'N/A',
            'customer_name' => $customer_name,
            'created_by' => $row->created_by ?? 'Unknown User',
            'destination' => $dest_label !== '' ? $dest_label : '—',
            'approval' => $row->approval ?? 'pending',
            'warehouse' => $row->warehouse ?? '',
            'status' => $row->status ?? 'created',
            'total' => KIT_User_Roles::can_see_prices() ?
                KIT_Commons::currency() . ' ' . ((int) ($row->product_invoice_amount ?? 0) + (int) ($row->miscellaneous ?? 0)) : '***',
            'delivery_status' => $row->delivery_status ?? 'No Delivery',
            'truck_number' => $row->truck_number ?? '',
            'delivery_reference' => $row->delivery_reference ?? '',
            'dispatch_date' => $row->dispatch_date ?? '',
            'delivery_id' => $row->delivery_id ?? 0,
            'driver_name' => $row->driver_name ?? '',
            'waybill_id' => $row->id ?? 0,
            'waybill_no_raw' => $row->waybill_no ?? '',
            'warehouse_action' => $row->warehouse_action ?? '',
            'warehouse_assigned_delivery_id' => $row->warehouse_assigned_delivery_id ?? '',
            'source_table' => $row->source_table ?? '',
            // Add mass and dimensions fields
            'item_length' => $row->item_length ?? 0,
            'item_width' => $row->item_width ?? 0,
            'item_height' => $row->item_height ?? 0,
            'total_mass_kg' => $row->total_mass_kg ?? 0,
            'total_volume' => $row->total_volume ?? 0
        ];
    }
    // Debug statement removed - was causing JSON parsing errors
?>
    <div class="wrap" style="max-width: 100vw; overflow-x: hidden;">
        <?php
        echo KIT_Commons::showingHeader([
            'title' => 'Waybill Dashboard',
            'icon' => KIT_Commons::icon('receipt'),
            'content' => KIT_Modal::render(
                'create-waybill-modal',
                'Create New Waybill',
                kit_render_waybill_multiform([
                    'form_action'          => $form_action,
                    'waybill_id'           => '',
                    'is_edit_mode'         => '0',
                    'waybill'              => '{}',
                    'customer_id'          => '0',
                    'is_existing_customer' => '0',
                    'customer'             => $customers,
                    'is_modal' => true
                ]),
                '3xl'
            )
        ]);
        ?>
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- Total Waybills Card -->
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="font-medium text-gray-500">Total Wssaybills</h3>
                    <p class="text-2xl font-bold"><?= number_format(KIT_Waybills::get_waybill_count()) ?></p>
                </div>

                <!-- Recent Waybills Card -->
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="font-medium text-gray-500">Recent Waybills</h3>
                    <p class="text-2xl font-bold"><?= number_format(KIT_Waybills::get_recent_waybill_count()) ?></p>
                    <p class="text-xs text-gray-500">Last 7 days</p>
                </div>

                <!-- Pending Waybills Card -->
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="font-medium text-gray-500">Pending</h3>
                    <p class="text-2xl font-bold"><?= number_format(KIT_Waybills::get_pending_waybill_count()) ?></p>
                </div>

            </div>
            <div class="space-y-4">
                <?php
                // Define actions
                $summary_url = plugins_url('pdf-summary.php', dirname(__FILE__));
                $actions = [
                    [
                        'label' => 'Download',
                        'title' => 'Download summary PDF',
                        'target' => '_blank',
                        'href' => $summary_url . '?waybill_no={waybill_no_raw}',
                        'class' => 'text-xs font-medium text-green-600 hover:text-green-800 hover:underline'
                    ],
                    [
                        'label' => 'View',
                        'title' => 'View waybill',
                        'href' => '?page=08600-Waybill-view&waybill_id={waybill_id}',
                        'class' => 'text-xs font-medium text-blue-600 hover:text-blue-800 hover:underline'
                    ],
                    [
                        'label' => 'Delete',
                        'title' => 'Delete waybill',
                        'href' => '?page=08600-waybill-manage&delete_waybill={waybill_no_raw}',
                        'class' => 'text-xs font-medium text-red-600 hover:text-red-800 hover:underline',
                        'onclick' => 'return confirm("Are you sure you want to delete this waybill?")'
                    ]
                ];
                $columns = [
                    'index' => [
                        'label' => '#',
                        'header_class' => 'w-8 max-w-8',
                        'cell_class' => 'text-center w-8 max-w-8 text-xs',
                        'callback' => function ($value, $row, $rowIndex) {
                            // $rowIndex is a global index when using infinite scroll; just add 1 for display
                            return $rowIndex + 1;
                        }
                    ],
                    'waybill_no' => [
                        'label' => 'Waybill #',
                        'header_class' => 'w-20 text-left max-w-20',
                        'cell_class' => 'text-left w-20 max-w-20 text-xs',
                        'callback' => function ($value, $row, $rowIndex) {
                            $waybill_no = $value ?? 'N/A';
                            $waybill_id = $row['waybill_id'] ?? 0;
                            
                            // Get creator name
                            $created_by = $row['created_by'] ?? 0;
                            $user_data = function_exists('get_userdata') ? get_userdata($created_by) : null;
                            $created_by_name = $user_data ? $user_data->display_name : '';
                            
                            $waybill_link = $waybill_id > 0 
                                ? '<a href="' . esc_url(admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $waybill_id)) . '" class="text-blue-600 hover:text-blue-800 hover:underline font-medium">' . esc_html($waybill_no) . '</a>'
                                : esc_html($waybill_no);
                            
                            if (!empty($created_by_name)) {
                                return '<div class="flex flex-col">' . 
                                       $waybill_link . 
                                       '<span class="text-[10px] text-gray-400 mt-0.5">' . esc_html($created_by_name) . '</span>' . 
                                       '</div>';
                            }
                            
                            return $waybill_link;
                        }
                    ],
                    'customer_name' => [
                        'label' => 'Name & Surname',
                        'header_class' => 'w-40 text-left max-w-40',
                        'cell_class' => 'text-left break-words max-w-40 truncate',
                    ],
                    /* Waybill mass and dimensions */
                    'mass_and_dimensions' => [
                        'label' => 'Mass & Dims',
                        'sortable' => true,
                        'searchable' => true,
                        'header_class' => 'w-32 text-left max-w-32',
                        'cell_class' => 'text-left w-32 max-w-32 text-xs whitespace-normal',
                        'callback' => function ($value, $row, $rowIndex) {
                            $mass = $row['total_mass_kg'] ?? 0;
                            $length = $row['item_length'] ?? 0;
                            $width = $row['item_width'] ?? 0;
                            $height = $row['item_height'] ?? 0;
                            $volume = $row['total_volume'] ?? 0;
                            
                            // Format mass
                            $mass_display = ($mass > 0) ? number_format($mass, 1) . ' kg' : '0 kg';
                            
                            // Format dimensions
                            $dimensions_display = ($length > 0 && $width > 0 && $height > 0) 
                                ? number_format($length, 0) . ' x ' . number_format($width, 0) . ' x ' . number_format($height, 0)
                                : '0 x 0 x 0';
                            
                            // Format volume
                            $volume_display = ($volume > 0) ? number_format($volume, 3) . ' m³' : '0 m³';
                            
                            return '<div class="text-xs text-gray-500">' . 
                                   esc_html($mass_display) . ' <br> ' . 
                                   esc_html($dimensions_display) . ' <br> ' . 
                                   esc_html($volume_display) . '</div>';
                        }
                    ],
                    'destination' => [
                        'label' => 'Destination',
                        'sortable' => false,
                        'searchable' => true,
                        'header_class' => 'w-48 text-left max-w-48',
                        'cell_class' => 'text-left w-48 max-w-48 text-xs',
                        'callback' => function ($value, $row, $rowIndex) {
                            $destination = $value ?? '—';
                            $truck_number = $row['truck_number'] ?? '';
                            $delivery_reference = $row['delivery_reference'] ?? '';
                            $dispatch_date = $row['dispatch_date'] ?? '';
                            
                            // Truncate long destination text
                            $dest_display = mb_strlen($destination) > 35 ? mb_substr($destination, 0, 32) . '...' : $destination;
                            
                            $html = '<div class="space-y-0.5">';
                            $html .= '<div class="font-medium text-gray-900 truncate" title="' . esc_attr($destination) . '">' . esc_html($dest_display) . '</div>';
                            
                            if (!empty($truck_number) || !empty($delivery_reference) || !empty($dispatch_date)) {
                                $html .= '<div class="flex flex-wrap gap-1">';
                                
                                if (!empty($truck_number)) {
                                    $truck_display = mb_strlen($truck_number) > 10 ? mb_substr($truck_number, 0, 8) . '..' : $truck_number;
                                    $html .= '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 text-[10px] font-medium" title="Truck: ' . esc_attr($truck_number) . '">';
                                    $html .= '<svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"/></svg>';
                                    $html .= esc_html($truck_display) . '</span>';
                                }
                                
                                if (!empty($delivery_reference)) {
                                    $ref_display = mb_strlen($delivery_reference) > 12 ? mb_substr($delivery_reference, 0, 10) . '..' : $delivery_reference;
                                    $html .= '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded bg-green-50 text-green-700 text-[10px] font-medium" title="Ref: ' . esc_attr($delivery_reference) . '">';
                                    $html .= '<svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>';
                                    $html .= esc_html($ref_display) . '</span>';
                                }
                                
                                if (!empty($dispatch_date)) {
                                    $html .= '<div class="text-[10px] text-gray-500 truncate">';
                                    $html .= date('M j', strtotime($dispatch_date));
                                    $html .= '</div>';
                                }
                                
                                $html .= '</div>';
                            }
                            
                            $html .= '</div>';
                            return $html;
                        }
                    ],
                    'approval' => [
                        'label' => 'Approval',
                        'sortable' => true,
                        'searchable' => false,
                        'header_class' => 'w-24 text-left max-w-24',
                        'cell_class' => 'text-left w-24 max-w-24 truncate text-xs',
                        'callback' => function ($value, $row, $rowIndex) {
                            $approval = strtolower($value ?? 'pending');
                            $config = [
                                'approved' => [
                                    'class' => 'bg-green-100 text-green-800',
                                    'icon' => '✓',
                                    'text' => 'Approved'
                                ],
                                'pending' => [
                                    'class' => 'bg-yellow-100 text-yellow-800',
                                    'icon' => '⏳',
                                    'text' => 'Pending'
                                ],
                                'rejected' => [
                                    'class' => 'bg-red-100 text-red-800',
                                    'icon' => '✗',
                                    'text' => 'Rejected'
                                ]
                            ];
                            
                            $settings = $config[$approval] ?? [
                                'class' => 'bg-gray-100 text-gray-800',
                                'icon' => '?',
                                'text' => ucfirst($value)
                            ];
                            
                            return '<span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-full text-[10px] font-medium ' . $settings['class'] . '">
                        <span>' . $settings['icon'] . '</span>
                        <span>' . $settings['text'] . '</span>
                    </span>';
                        }
                    ],
                    'total' => [
                        'label' => 'Total',
                        'sortable' => true,
                        'searchable' => false,
                        'header_class' => 'w-20 text-right max-w-20',
                        'cell_class' => 'text-right w-20 max-w-20 text-xs'
                    ]
                    // Note: Actions column is rendered by KIT_Unified_Table::advanced via the 'actions' option
                ];
                // Get current page and items per page for pagination
                $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
               
                // Render table (infinite scroll) and pass actions so they appear in the dedicated Actions column
                if (class_exists('KIT_Unified_Table')) {
                    echo KIT_Unified_Table::infinite($waybillData, $columns, [
                        'title' => 'All Waybeeills',
                        'actions' => $actions,
                        'searchable' => true,
                        'sortable' => true,
                        'exportable' => true,
                        'empty_message' => 'No waybills found'
                    ]);
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Bulk invoice functionality
            const selectAllBtn = document.getElementById('select-all-waybills');
            const generateBulkInvoiceBtn = document.getElementById('generate-bulk-invoice');
            const selectedCountSpan = document.getElementById('selected-count');

            // Only proceed if the required elements exist
            if (!selectAllBtn || !generateBulkInvoiceBtn || !selectedCountSpan) {
                console.log('Bulk invoice elements not found, skipping bulk invoice functionality');
                return;
            }

            let selectedWaybills = new Set();

            // Select all functionality
            function updateSelectAllButton() {
                const checkboxes = document.querySelectorAll('.waybill-checkbox');
                const checkedCount = document.querySelectorAll('.waybill-checkbox:checked').length;

                if (checkedCount === 0) {
                    selectAllBtn.textContent = 'Select All';
                } else if (checkedCount === checkboxes.length) {
                    selectAllBtn.textContent = 'Deselect All';
                } else {
                    selectAllBtn.textContent = `Select All (${checkedCount}/${checkboxes.length})`;
                }

                // Update selected count
                selectedCountSpan.textContent = checkedCount;

                // Enable/disable generate button
                generateBulkInvoiceBtn.disabled = checkedCount === 0;
            }

            // Select all button click
            selectAllBtn.addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('.waybill-checkbox');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);

                checkboxes.forEach(checkbox => {
                    checkbox.checked = !allChecked;
                    if (!allChecked) {
                        selectedWaybills.add(checkbox.value);
                    } else {
                        selectedWaybills.delete(checkbox.value);
                    }
                });

                updateSelectAllButton();
            });

            // Individual checkbox change
            document.addEventListener('change', function(e) {
                if (e.target.classList.contains('waybill-checkbox')) {
                    if (e.target.checked) {
                        selectedWaybills.add(e.target.value);
                    } else {
                        selectedWaybills.delete(e.target.value);
                    }
                    updateSelectAllButton();
                }
            });

            // Generate bulk invoice
            generateBulkInvoiceBtn.addEventListener('click', function() {
                if (selectedWaybills.size === 0) {
                    alert('Please select at least one waybill to generate a bulk invoice.');
                    return;
                }

                const selectedIds = Array.from(selectedWaybills).join(',');
                const url = '<?php echo plugin_dir_url(__FILE__); ?>customers/pdf-bulkinvoicing.php?selected_ids=' + selectedIds;

                // Open in new window/tab
                window.open(url, '_blank');
            });

            // Initialize
            updateSelectAllButton();
        });
    </script>

<?php
}
