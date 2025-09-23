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
    // Show success toast if waybill was created
    if (isset($_GET['success']) && $_GET['success'] == '1') {
        $waybill_no = isset($_GET['waybill_no']) ? sanitize_text_field($_GET['waybill_no']) : '';
        $message = isset($_GET['message']) ? sanitize_text_field($_GET['message']) : 'Waybill created successfully!';

        echo '<div class="notice notice-success is-dismissible" style="margin: 20px 0; padding: 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; color: #155724;">
            <p style="margin: 0; font-size: 16px; font-weight: 600;">
                <span style="color: #28a745;">✓</span> ' . esc_html($message);
        if ($waybill_no) {
            echo ' <strong>Waybill #: ' . esc_html($waybill_no) . '</strong>';
        }
        echo '</p>
        </div>';

        // Add JavaScript to auto-dismiss the toast after 5 seconds
        echo '<script>
        setTimeout(function() {
            var notice = document.querySelector(".notice-success");
            if (notice) {
                notice.style.opacity = "0";
                notice.style.transition = "opacity 0.5s";
                setTimeout(function() {
                    if (notice && notice.parentNode) {
                        notice.parentNode.removeChild(notice);
                    }
                }, 500);
            }
        }, 5000);
        </script>';
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
    
    // Handle delete BEFORE any redirects so the action is not lost
    if (isset($_GET["delete_waybill"])) {
        KIT_Waybills::delete_waybill($_GET["delete_waybill"]);
        exit;
    }
    
    // Force per_page to 20 if not set or if set to 5
    if (!isset($_GET['per_page']) || $_GET['per_page'] == 5) {
        $current_url = admin_url('admin.php');
        $new_url = add_query_arg([
            'page' => '08600-waybill-manage',
            'per_page' => '20',
            'paged' => isset($_GET['paged']) ? $_GET['paged'] : 1
        ], $current_url);
        wp_redirect($new_url);
        exit;
    }

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

    

    $allWaybills = KIT_Waybills::get_waybills(['fields' => 'all']);

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
        
        $waybillData[] = [
            'waybill_no' => $row->waybill_no ?? 'N/A',
            'customer_name' => $customer_name,
            'created_by' => $row->created_by ?? 'Unknown User',
            'approval' => $row->approval ?? 'pending',
            'total' => KIT_User_Roles::can_see_prices() ?
                KIT_Commons::currency() . ' ' . ((int) ($row->product_invoice_amount ?? 0) + (int) ($row->miscellaneous ?? 0)) : '***',
            'waybill_id' => $row->waybill_id ?? 0,
            'waybill_no_raw' => $row->waybill_no ?? ''
        ];
    }

    // Define columns
    $columns = [
        'waybill_no' => 'Waybill #',
        'customer_name' => 'Name',
        'created_by' => [
            'label' => 'Created By',
            'sortable' => true,
            'searchable' => true
        ],
        'approval' => [
            'label' => 'Approval',
            'sortable' => true,
            'searchable' => false
        ],
        'total' => [
            'label' => 'Total',
            'sortable' => true,
            'searchable' => false
        ]
    ];

    // Define actions
    $summary_url = plugins_url('pdf-summary.php', dirname(__FILE__));
    $actions = [
        [
            'label' => 'Download',
            'target' => '_blank',
            'href' => $summary_url . '?waybill_no={waybill_no_raw}',
            'onclick' => 'window.open(this.href, "_blank"); return false;',
            'class' => 'text-green-600 hover:text-green-800'
        ],
        [
            'label' => 'View',
            'href' => '?page=08600-Waybill-view&waybill_id={waybill_id}',
            'class' => 'text-blue-600 hover:text-blue-800'
        ],
        [
            'label' => 'Delete',
            'href' => '?page=08600-waybill-manage&delete_waybill={waybill_no_raw}',
            'class' => 'text-red-600 hover:text-red-800',
            'onclick' => 'return confirm("Are you sure you want to delete this waybill?")'
        ]
    ];
?>
    <div class="wrap">
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
                    'customer'             => $customers
                ]),
                '3xl'
            )
        ]);
        ?>
        <div class="max-w-7xl mx-auto">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                <!-- Total Waybills Card -->
                <div class="bg-white shadow rounded-lg p-4">
                    <h3 class="font-medium text-gray-500">Total Waybills</h3>
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
                // Get current page and items per page for pagination
                $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
                $items_per_page = isset($_GET['items_per_page']) ? max(5, min(100, intval($_GET['items_per_page']))) : 10;
                
                // Render table with fallback if unified table class is not available
                if (class_exists('KIT_Unified_Table')) {
                    echo KIT_Unified_Table::advanced($waybillData, $columns, [
                        'title' => 'All Waybeeills',
                        'actions' => $actions,
                        'searchable' => true,
                        'sortable' => true,
                        'pagination' => true,
                        'items_per_page' => $items_per_page,
                        'current_page' => $current_page,
                        'show_items_per_page' => true,
                        'exportable' => true,
                        'empty_message' => 'No waybills found'
                    ]);
                } else {
                    // Fallback to simple table
                    echo '<div class="bg-white shadow rounded-lg overflow-hidden">';
                    echo '<div class="px-6 py-4 border-b border-gray-200">';
                    echo '<h3 class="text-lg font-medium text-gray-900">All Waffybills</h3>';
                    echo '</div>';
                    
                    if (empty($waybillData)) {
                        echo '<div class="px-6 py-12 text-center">';
                        echo '<p class="text-gray-500">No waybills found</p>';
                        echo '</div>';
                    } else {
                        echo '<div class="overflow-x-auto">';
                        echo '<table class="min-w-full divide-y divide-gray-200">';
                        echo '<thead class="bg-gray-50">';
                        echo '<tr>';
                        echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waybill #</th>';
                        echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>';
                        echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created By</th>';
                        echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Approval</th>';
                        echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>';
                        echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>';
                        echo '</tr>';
                        echo '</thead>';
                        echo '<tbody class="bg-white divide-y divide-gray-200">';
                        
                        foreach ($waybillData as $row) {
                            echo '<tr class="hover:bg-gray-50">';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . esc_html($row['waybill_no']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . esc_html($row['customer_name']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . esc_html($row['created_by']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . esc_html($row['approval']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . esc_html($row['total']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm font-medium">';
                            
                            foreach ($actions as $action) {
                                $href = str_replace(['{waybill_id}', '{waybill_no_raw}'], [$row['waybill_id'], $row['waybill_no_raw']], $action['href']);
                                $class = $action['class'] ?? 'text-blue-600 hover:text-blue-800';
                                $onclick = isset($action['onclick']) ? 'onclick="' . esc_attr($action['onclick']) . '"' : '';
                                $target = isset($action['target']) ? 'target="' . esc_attr($action['target']) . '" rel="noopener"' : '';
                                
                                echo '<a href="' . esc_url($href) . '" class="' . esc_attr($class) . '" ' . $onclick . ' ' . $target . '>' . esc_html($action['label']) . '</a>';
                                if ($action !== end($actions)) {
                                    echo ' | ';
                                }
                            }
                            
                            echo '</td>';
                            echo '</tr>';
                        }
                        
                        echo '</tbody>';
                        echo '</table>';
                        echo '</div>';
                    }
                    
                    echo '</div>';
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
