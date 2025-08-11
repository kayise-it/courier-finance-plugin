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
                        'status' => ['label' => 'Status', 'align' => 'text-left'],
                        'total' => ['label' => 'Total', 'align' => 'text-right'],
                        'actions' => ['label' => 'Actions', 'align' => 'text-center'],
                    ];

                    $waybill_actions = function ($key, $row) {
                        if ($key === 'customer_name') {
                            return $row->customer_name . ' ' . $row->customer_surname;
                        }
                        if ($key === 'status') {
                            //return badge
                            return KIT_Commons::statusBadge($row->status, "bg-orange-400");
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
<?php
}
