<?php
ob_start(); // Start buffering the output

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function waybill_page()
{

    echo do_shortcode('[kit_waybill_form]');
}
function plugin_Waybill_list_page()
{
    if (isset($_GET['generate_quote'])) {
        echo "Generating quote...";
        KIT_Waybills::generate_Waybill_quote();
    }

    if (isset($_GET["delete_waybill"])) {  
        KIT_Waybills::delete_waybill($_GET["delete_waybill"]);
        exit;
    }

?>
    <div class="wrap">
        <?php echo do_shortcode('[showheader title="Waybill Dashboard" desc=""]'); ?>

        <div class="max-w-7xl mx-auto">
            <!-- Bulk Invoice Section -->
            <div class="bg-white shadow rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Bulk Invoice Generator</h3>
                    <button id="select-all-waybills" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        Select All
                    </button>
                </div>
                <p class="text-sm text-gray-600 mb-4">Select waybills to generate a bulk invoice with waybill number, destination country, city, and grand total.</p>
                <button id="generate-bulk-invoice" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors" disabled>
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Generate Bulk Invoice (<span id="selected-count">0</span> selected)
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
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
            <div class="">
                <div class="">
                    <div class="bg-white shadow rounded-lg p-6">
                        <?php
                        // Include the modal component
                        $customers   = KIT_Customers::tholaMaCustomer();
                        $form_action = admin_url('admin-post.php?action=add_waybill_action');

                        $modal_path = realpath(plugin_dir_path(__FILE__) . './components/modal.php');

                        if (file_exists($modal_path)) {
                            require_once $modal_path;
                        } else {
                            error_log("Modal.php not found at: " . $modal_path);
                            // Optional: Show a safe error or fallback content
                        }

                        echo KIT_Modal::render(
                            'create-waybill-modal',
                            'Create New 23Waybill',
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
                        );

                        $options = [
                            'itemsPerPage' => 5,
                            'currentPage' => $_GET['paged'] ?? 1,
                            'tableClass' => 'min-w-full text-left text-xs text-gray-700',
                            'emptyMessage' => 'No customers records found',
                            'id' => 'customerTable',
                            'role' => 'waybills',

                        ];


                        $columns = [
                            'waybill_no' => ['label' => 'Waybill #', 'align' => 'text-left'],
                            'customer_name' => ['label' => 'Name', 'align' => 'text-left'],
                            'approval' => ['label' => 'Approval', 'align' => 'text-left'],
                            'total' => ['label' => 'Total', 'align' => 'text-right'],
                            'actions' => ['label' => 'Actions', 'align' => 'text-center'],
                        ];

                        $waybill_actions = function ($key, $row) {
                            if ($key === 'customer_name') {
                                return $row->customer_name . ' ' . $row->customer_surname;
                            }
                            if ($key === 'total') {
                                if (KIT_Commons::isAdmin()) {
                                    return KIT_Commons::currency() . ' ' . ((int) $row->product_invoice_amount + (int) $row->miscellaneous);
                                } else {
                                    return '***';
                                }
                            }

                            if ($key === 'actions') {

                                $html = '<a href="?page=08600-Waybill-view&waybill_id=' . $row->waybill_id . '" class="text-blue-600 hover:underline">View</a> ';
                                $html .= '<a href="?page=08600-Waybill&delete_waybill=' . $row->waybill_no . '" class="text-red-600 hover:underline" onclick="return confirm(\'Are you sure you want to delete this waybill?\');">Delete</a>';
                                return $html;
                            }
                            return htmlspecialchars(($row->$key ?? '') ?: '');
                        };
                        $allWaybills = KIT_Waybills::get_waybills(['fields' => 'w.waybill_no, w.product_invoice_number, w.created_at, c.name, c.surname',]);

                        echo KIT_Commons::render_versatile_table($allWaybills, $columns, $waybill_actions, $options);

                        ?>
                    </div>
                </div>
                <div>
                    <?php

                    $options = [
                        'itemsPerPage' => 5,
                        'currentPage' => $_GET['paged'] ?? 1,
                        'tableClass' => 'min-w-full text-left text-xs text-gray-700',
                        'emptyMessage' => 'No customers records found',
                        'id' => 'customerTable',
                        'role' => 'waybills',

                    ];


                    $columns = [
                        'waybill_no' => ['label' => 'Waybill #', 'align' => 'text-left'],
                        'customer_name' => ['label' => 'Name', 'align' => 'text-left'],
                        'total' => ['label' => 'Total', 'align' => 'text-right'],
                        'actions' => ['label' => 'Actions', 'align' => 'text-center'],
                    ];

                    $waybill_actions = function ($key, $row) {
                        if ($key === 'customer_name') {
                            return $row->customer_name . ' ' . $row->customer_surname;
                        }
                        if ($key === 'total') {
                            if (KIT_Commons::isAdmin()) {
                                return KIT_Commons::currency() . ' ' . ((int) $row->product_invoice_amount + (int) $row->miscellaneous);
                            } else {
                                return '***';
                            }
                        }

                        if ($key === 'actions') {
                            $html = '<a href="?page=08600-Waybill-view&waybill_id=' . $row->waybill_id . '" class="text-blue-600 hover:underline">View</a> ';
                            $html .= '<a href="?page=waybill-dashboard&delete_waybill=' . $row->waybill_no . '" class="text-red-600 hover:underline" onclick="return confirm(\'Are you sure you want to delete this waybill?\');">Delete</a>';
                            return $html;
                        }
                        return htmlspecialchars(($row->$key ?? '') ?: '');
                    };
                    $warehouseList = KIT_Waybills::warehouseWaybills();

                    echo KIT_Commons::render_versatile_table($warehouseList, $columns, $waybill_actions, $options);
                    ?>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bulk invoice functionality
    const selectAllBtn = document.getElementById('select-all-waybills');
    const generateBulkInvoiceBtn = document.getElementById('generate-bulk-invoice');
    const selectedCountSpan = document.getElementById('selected-count');
    
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
