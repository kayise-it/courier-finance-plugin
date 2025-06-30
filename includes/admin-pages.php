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
                    <div class="bg-white shadow rounded-lg overflow-hidden p-6">
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
                            ?>

                        <?php
                        $allWaybills = KIT_Waybills::get_waybills(['fields' => 'w.waybill_no, w.product_invoice_number, w.created_at, c.name, c.surname',]);;
                        echo KIT_Waybills::render_table_with_pagination($allWaybills, [
                            'fields' => ['waybill_no', 'delivery_id', 'customer_id', 'approval'],
                            'table_class' => 'min-w-full divide-y divide-gray-200 text-xs',
                            'show_create_quotation' => false
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}
