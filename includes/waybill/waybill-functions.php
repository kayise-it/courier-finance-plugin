<?php
// includes/waybill/waybill-functions.php

if (! defined('ABSPATH')) {
    exit;
}

class KIT_Waybills
{
    public static function init()
    {
        add_action('wp_ajax_load_waybill_page', [self::class, 'myplugin_ajax_load_waybill_page']);
        add_action('wp_ajax_nopriv_load_waybill_page', [self::class, 'myplugin_ajax_load_waybill_page']);
        add_action('admin_post_update_WaybillApproval', [self::class, 'update_waybillApproval']);
        add_action('admin_post_nopriv_update_WaybillApproval', [self::class, 'update_waybillApproval']);
        add_action('admin_post_assign_waybill_to_delivery', [self::class, 'assign_waybill_to_delivery']);
        add_action('admin_post_nopriv_assign_waybill_to_delivery', [self::class, 'assign_waybill_to_delivery']);
        // For JS fallback (POST without AJAX)
        add_action('admin_post_waybillQuoteStatus_update', [self::class, 'waybillQuoteStatus_update']);
        add_action('admin_post_nopriv_waybillQuoteStatus_update', [self::class, 'waybillQuoteStatus_update']);

        // For AJAX (with JS enabled)
        add_action('wp_ajax_waybillQuoteStatus_update', [self::class, 'waybillQuoteStatus_update']);
        add_action('wp_ajax_nopriv_waybillQuoteStatus_update', [self::class, 'waybillQuoteStatus_update']);
        add_shortcode('kit_waybill_form', [__CLASS__, 'display_waybill_form']);
        add_action('admin_post_add_waybill_action', [self::class, 'process_form']);
        add_action('wp_ajax_process_waybill_form', [self::class, 'process_form']);
        add_action('wp_ajax_nopriv_process_waybill_form', [self::class, 'process_form']);
        add_action('admin_post_update_waybill_action', [self::class, 'update_waybill_action']);
        add_action('admin_post_delete_waybill', [__CLASS__, 'handle_delete_waybill']);
        add_action('wp_ajax_delete_waybill', [__CLASS__, 'handle_delete_waybill']);
        add_action('wp_ajax_nopriv_delete_waybill', [__CLASS__, 'handle_delete_waybill']);
        add_action('admin_post_generate_quote', [self::class, 'generate_Waybill_quote']);
        add_action('admin_post_add_waybill_dash', [self::class, 'add_waybill_dash']);
        add_action('admin_post_get_waybill_count', [self::class, 'get_waybill_count']);
        add_action('admin_post_get_recent_waybill_count', [self::class, 'get_recent_waybill_count']);
        add_action('admin_post_get_pending_waybill_count', [self::class, 'get_pending_waybill_count']);
        add_action('admin_post_get_latest_waybill_date', [self::class, 'get_latest_waybill_date']);
        add_action('admin_post_approve_waybill', [self::class, 'approve_waybill']);
        add_action('admin_post_update_destinationCountry', [self::class, 'update_destinationCountry']);
        add_action('admin_post_nopriv_update_destinationCountry', [self::class, 'update_destination']);
        add_action('admin_post_getDestinationCountry', [self::class, 'getDestinationCountry']);
        add_action('admin_post_nopriv_getDestinationCountry', [self::class, 'getDestinationCountry']);

        // PDF generation ajax endpoint removed – legacy pdf-generator.php handles output
    }
    // generate_pdf handler removed – use legacy pdf-generator.php file

    public static function chargeGroup($country_id)
    {
        // Get country using original country origin_country_id
        $origin_country_id = isset($country_id) ? intval($country_id) : 0;
        $origin_country = null;
        if ($origin_country_id) {
            global $wpdb;
            $table = $wpdb->prefix . 'kit_operating_countries';
            $origin_country = $wpdb->get_var(
                $wpdb->prepare("SELECT `charge_group` FROM $table WHERE id = %d", $origin_country_id)
            );
        }

        return $origin_country;
    }


    public static function waybillQuoteStatus_update()
    {
        global $wpdb;

        // Check if user can invoice waybills
        if (!KIT_User_Roles::can_invoice()) {
            wp_die('You do not have permission to update waybill status.');
        }

        // Verify nonce for security
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update_waybill_approval_nonce')) {
            wp_die('Security check failed.');
        }

        $waybillid = intval($_POST['waybillid'] ?? 0);
        $waybillno = sanitize_text_field($_POST['waybillno'] ?? '');
        $status    = sanitize_text_field($_POST['status'] ?? '');
        $userId    = get_current_user_id();

        $table = $wpdb->prefix . 'kit_waybills';

        $updated = $wpdb->update(
            $table,
            ['status' => $status, 'status_userid' => $userId],
            ['id' => $waybillid, 'waybill_no' => $waybillno],
            ['%s', '%d'],
            ['%d', '%s']
        );


        if ($status === 'invoiced') {
            // Update waybill status to invoiced (quotations removed)
            $msg = (['message' => 'Waybill status updated to invoiced.']);
        }

        // Check if it's AJAX
        if (defined('DOING_AJAX') && DOING_AJAX) {
            if ($updated !== false) {
                wp_send_json_success(['message' => 'Waybill status updated successfully.']);
            } else {
                wp_send_json_error(['message' => 'Failed to update waybill status.']);
            }
        } else {
            // Non-AJAX fallback: redirect with success or error notice
            $redirect_url = admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $waybillid); // or wherever you want
            $redirect_url = add_query_arg('waybill_Status_update', ($updated !== false ? 'success' : 'error'), $redirect_url);
            wp_redirect($redirect_url);
            exit;
        }
    }


    public static function update_waybillApproval()
    {
        global $wpdb;

        // Check if user can approve waybills
        if (!KIT_User_Roles::can_approve()) {
            wp_die('You do not have permission to approve waybills.');
        }

        // Verify nonce for security
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update_waybill_approval_nonce')) {
            wp_die('Security check failed.');
        }

        $waybillid = intval($_POST['waybillid'] ?? 0);
        $waybillno = sanitize_text_field($_POST['waybillno'] ?? '');
        $status = sanitize_text_field($_POST['status'] ?? '');
        $userId = get_current_user_id();

        $table = $wpdb->prefix . 'kit_waybills';

        // Get current approval status to check if we need to update invoice status
        $current_waybill = $wpdb->get_row($wpdb->prepare(
            "SELECT approval, status FROM $table WHERE id = %d AND waybill_no = %s",
            $waybillid,
            $waybillno
        ));

        $update_data = [
            'approval' => $status,
            'approval_userid' => $userId,
        ];

        // If changing from "Approved" or "Completed" to something else, set invoice status to "pending"
        if ($current_waybill && 
            in_array($current_waybill->approval, ['approved', 'completed']) && 
            !in_array($status, ['approved', 'completed'])) {
            $update_data['status'] = 'pending';
            error_log("Approval status changed from '{$current_waybill->approval}' to '{$status}' - setting invoice status to 'pending' for waybill {$waybillno}");
        }

        $updated = $wpdb->update(
            $table,
            $update_data,
            [
                'id' => $waybillid,
                'waybill_no' => $waybillno,
            ],
            array_fill(0, count($update_data), '%s'),
            ['%d', '%s']
        );

        if ($updated !== false) {
            $redirect_args = ['approval_updated' => '1'];
            
            // If we also updated the invoice status, add a flag to show a message
            if (isset($update_data['status'])) {
                $redirect_args['invoice_status_updated'] = '1';
            }
            
            wp_redirect(add_query_arg($redirect_args, wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('approval_error', '1', wp_get_referer()));
        }
        exit;
    }

    public static function assign_waybill_to_delivery()
    {
        global $wpdb;

        // Check if user can assign deliveries (Admin or Manager only)
        if (!KIT_User_Roles::can_approve()) {
            wp_die('You do not have permission to assign waybills to deliveries.');
        }

        // Verify nonce for security
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'assign_waybill_delivery_nonce')) {
            wp_die('Security check failed.');
        }

        $waybill_id = intval($_POST['waybill_id'] ?? 0);
        $waybill_no = sanitize_text_field($_POST['waybill_no'] ?? '');
        $delivery_id = intval($_POST['delivery_id'] ?? 0);
        $assigned_by = get_current_user_id();

        if (!$waybill_id || !$delivery_id) {
            wp_redirect(add_query_arg('assignment_error', '1', wp_get_referer()));
            exit;
        }

        // Include warehouse functions
        require_once plugin_dir_path(__FILE__) . '../warehouse/warehouse-functions.php';

        // Check if waybill has warehouse items
        $warehouse_items = KIT_Warehouse::getWarehouseItems($waybill_id);
        
        if (empty($warehouse_items)) {
            wp_redirect(add_query_arg('assignment_error', '2', wp_get_referer()));
            exit;
        }

        // Check if delivery exists
        $delivery = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}kit_deliveries WHERE id = %d",
            $delivery_id
        ));

        if (!$delivery) {
            wp_redirect(add_query_arg('assignment_error', '3', wp_get_referer()));
            exit;
        }

        // Check if any warehouse items are already assigned
        $assigned_items = array_filter($warehouse_items, function($item) {
            return $item->status === 'assigned' || $item->status === 'shipped' || $item->status === 'delivered';
        });

        if (!empty($assigned_items)) {
            wp_redirect(add_query_arg('assignment_error', '4', wp_get_referer()));
            exit;
        }

        // Assign all warehouse items to the delivery
        $success_count = 0;
        $error_count = 0;
        
        foreach ($warehouse_items as $item) {
            if ($item->status === 'in_warehouse') {
                $result = KIT_Warehouse::assignToDelivery($item->id, $delivery_id, $assigned_by);
                if (is_wp_error($result)) {
                    $error_count++;
                    error_log("Failed to assign warehouse item {$item->id} to delivery {$delivery_id}: " . $result->get_error_message());
                } else {
                    $success_count++;
                }
            }
        }

        if ($success_count > 0) {
            error_log("Waybill {$waybill_no}: {$success_count} warehouse items assigned to delivery {$delivery_id} by user {$assigned_by}");
            wp_redirect(add_query_arg('assignment_success', '1', wp_get_referer()));
        } else {
            error_log("Failed to assign any warehouse items for waybill {$waybill_no} to delivery {$delivery_id}");
            wp_redirect(add_query_arg('assignment_error', '5', wp_get_referer()));
        }
        exit;
    }

    public static function myplugin_ajax_load_waybill_page()
    {
        check_ajax_referer('get_waybills_nonce', 'nonce');

        $paged = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;
        $items_per_page = isset($_POST['items_per_page']) ? intval($_POST['items_per_page']) : 5;

        $all_waybills = self::kit_get_all_waybills();

        // Return only the table (for AJAX)
        echo KIT_Waybills::render_table_with_pagination($all_waybills, [
            'current_page' => $paged,
            'items_per_page' => $items_per_page,
            'ajax' => true
        ]);

        wp_die();
    }

    public static function truckWaybills($deliveryid)
    {
        global $wpdb;
        $prefix = $wpdb->prefix;
        $deliveries_table = $prefix . 'kit_deliveries';
        $waybill_table = $prefix . 'kit_waybills';
        $customers_table = $prefix . 'kit_customers';
        $waybills = $wpdb->get_results($wpdb->prepare("SELECT wb.id as waybill_id, wb.waybill_no as waybill_no, wb.direction_id, wb.delivery_id, wb.customer_id, wb.approval, wb.approval_userid, wb.product_invoice_number, wb.product_invoice_amount, wb.mass_charge, wb.volume_charge, wb.charge_basis, wb.miscellaneous, wb.tracking_number, wb.created_by, wb.last_updated_by, wb.status, wb.status_userid, wb.created_at, wb.last_updated_at, c.name as customer_name, c.surname as customer_surname, c.cell as customer_cell, c.email as customer_email, c.address as customer_address
        FROM $deliveries_table, $waybill_table as wb, $customers_table as c WHERE $deliveries_table.id = wb.delivery_id AND wb.customer_id = c.cust_id AND $deliveries_table.id = %d", $deliveryid));

        return $waybills;
    }
    //Get all waybills along with their items from the waybills_items table
    /**
     * Displays the waybill form.
     * function waybillLists
     * @return string HTML content of the waybill form.
     */
    public static function waybillLists($deliveryid)
    {
        global $wpdb;

        $prefix = $wpdb->prefix;

        // TABLE NAMES
        $waybills_table   = $prefix . 'kit_waybills';
        $customers_table  = $prefix . 'kit_customers';
        $deliveries_table = $prefix . 'kit_deliveries';
        $directions_table = $prefix . 'kit_shipping_directions';
        $countries_table  = $prefix . 'kit_operating_countries';
        $items_table      = $prefix . 'kit_waybill_items';

        // STEP 1: Get Delivery Info
        $delivery_sql = $wpdb->prepare("
            SELECT 
                delivery.id,
                delivery.delivery_reference,
                delivery.direction_id,
                delivery.dispatch_date,
                delivery.truck_number,
                delivery.status,
                dir.description AS route_description,
                origin.country_name AS origin_country,
                dest.country_name AS destination_country
            FROM $deliveries_table delivery
            LEFT JOIN $directions_table dir ON delivery.direction_id = dir.id
            LEFT JOIN $countries_table origin ON dir.origin_country_id = origin.id
            LEFT JOIN $countries_table dest ON dir.destination_country_id = dest.id
            WHERE delivery.id = %d
            LIMIT 1
        ", $deliveryid);

        $delivery = $wpdb->get_row($delivery_sql, ARRAY_A);

        if (!$delivery) {
            return null; // No delivery found
        }

        // STEP 2: Get all waybills linked to this delivery
        $waybills_sql = $wpdb->prepare("
            SELECT 
                w.id AS waybill_id,
                w.*,
                c.name AS customer_name,
                c.surname AS customer_surname,
                c.cell AS customer_cell
            FROM $waybills_table w
            LEFT JOIN $customers_table c ON w.customer_id = c.cust_id
            WHERE w.delivery_id = %d
        ", $deliveryid);

        $waybills_data = $wpdb->get_results($waybills_sql, ARRAY_A);

        $waybills = [];

        // STEP 3: Loop through waybills and get items for each
        foreach ($waybills_data as $waybill) {
            $items_sql = $wpdb->prepare("SELECT * FROM $items_table WHERE waybillno = %d", $waybill['waybill_no']);
            $items = $wpdb->get_results($items_sql, ARRAY_A);

            $waybills[] = [
                'waybill' => $waybill,
                'items'   => $items,
            ];
        }

        // FINAL RETURN
        return [
            'delivery' => $delivery,
            'waybills' => $waybills,
        ];
    }

    public static function get_waybill_items($waybillNo)
    {
        $waybill_items = [];
        global $wpdb;
        $table = $wpdb->prefix . 'kit_waybill_items';

        $waybill_items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE waybillno = %s",
                $waybillNo
            )
        );

        if (empty($waybill_items)) {
            return [];
        }

        return is_array($waybill_items) ? $waybill_items : [];
    }

    public static function render_waybills_with_items($waybills)
    {
        // First group waybills by their ID
        $grouped_waybills = [];
        $current_user = wp_get_current_user();
        foreach ($waybills as $waybill) {
            $waybill_id = $waybill['id'];

            if (!isset($grouped_waybills[$waybill_id])) {
                // Get items for this waybill
                $items = KIT_Waybills::get_waybill_items($waybill['waybill_no']);

                // Prepare waybill data
                $main_waybill = clone $waybill;
                unset($main_waybill->item_name);
                unset($main_waybill->quantity);
                unset($main_waybill->unit_price);

                $grouped_waybills[$waybill_id] = [
                    'waybill' => $main_waybill,
                    'items' => $items
                ];
            }
        }

        // Output the table
        ob_start(); ?>
        <div class="overflow-x-auto">
            <table class="min-w-full bg-white border border-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Waybill #</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($grouped_waybills as $waybill_id => $data):

                        $waybill = $data['waybill'];
                        $items = $data['items'];
                        $item_count = count($items);
                    ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-medium text-blue-600"><?= esc_html($waybill['waybill_no']) ?></div>
                                <div class="text-xs text-gray-500"><?= date('M d, Y', strtotime($waybill['created_at'])) ?></div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div><?= esc_html($waybill['customer_id']) ?></div>
                                <div class="text-xs text-gray-500 truncate max-w-xs"><?= esc_html($waybill['customer_name'] ?? '') ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <?php if (KIT_User_Roles::can_see_prices()): ?>
                                    R<?= ($waybill['product_invoice_amount']) ?>
                                <?php else: ?>
                                    ***
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span
                                    class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= ($waybill['status'] === 'pending') ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                                    <?= ucfirst($waybill['status']) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <?php echo KIT_Commons::renderButton($item_count . ' item' . (($item_count !== 1) ? 's' : ''), 'ghost-primary', 'sm', [
                                    'classes' => 'toggle-items flex items-center',
                                    'data-waybill-id' => $waybill_id,
                                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>',
                                    'iconPosition' => 'right'
                                ]); ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex space-x-2">
                                    <a href="<?php echo admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $waybill['id'] . '&waybill_atts=view_waybill'); ?>"
                                        class="text-blue-600 hover:text-blue-900" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <?= KIT_Commons::deleteWaybillGlobal($waybill['id'], $waybill['waybill_no'], $waybill['delivery_id'], $current_user->ID); ?>
                                </div>
                            </td>
                        </tr>
                        <tr class="item-accordion hidden" id="items-<?= $waybill_id ?>">
                            <td colspan="6" class="bg-gray-50 px-4 py-3">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-xs border border-gray-200 rounded">
                                        <thead>
                                            <tr class="bg-gray-100">
                                                <th class="<?= KIT_Commons::thClasses() ?> text-left">Item</th>
                                                <th class="<?= KIT_Commons::thClasses() ?> text-right">Qty</th>
                                                <th class="<?= KIT_Commons::thClasses() ?> text-right">Price</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            // Use tRows function to render items table body only
                                            $item_columns = [
                                                'item' => 'Item',
                                                'quantity' => 'Qty',
                                                'unit_price' => 'Price'
                                            ];

                                            // Prepare items data for tRows
                                            $items_data = [];
                                            foreach ($items as $item) {
                                                $items_data[] = (object) [
                                                    'item' => $item->item ?? $item->item_name,
                                                    'quantity' => $item->quantity ?? '',
                                                    'unit_price' => $item->unit_price ?? 0
                                                ];
                                            }

                                            echo self::tRows($items_data, $item_columns, [
                                                'accordion' => true,
                                                'show_headers' => false,
                                                'table_class' => '',
                                                'row_class' => 'border-t border-gray-100',
                                                'cell_class' => KIT_Commons::tcolClasses(),
                                                'custom_cell_render' => function ($key, $item, $index) {
                                                    if ($key === 'unit_price') {
                                                        if (KIT_User_Roles::can_see_prices()) {
                                                            return '<span class="text-right">R' . number_format($item->unit_price, 2) . '</span>';
                                                        } else {
                                                            return '<span class="text-right">***</span>';
                                                        }
                                                    }
                                                    if ($key === 'quantity') {
                                                        return '<span class="text-right">' . esc_html($item->quantity) . '</span>';
                                                    }
                                                    return esc_html($item->item);
                                                }
                                            ]);
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.toggle-items').forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const waybillId = this.getAttribute('data-waybill-id');
                        const accordionRow = document.getElementById('items-' + waybillId);
                        const icon = this.querySelector('svg');

                        accordionRow.classList.toggle('hidden');
                        icon.classList.toggle('rotate-180');
                    });
                });
            });
        </script>
    <?php

    }


    public static function display_waybill_form()
    {
        wp_enqueue_style('kit-tailwind');
        // Quotations CSS removed
        // Ensure our city loader script is available on waybill page
        wp_enqueue_script('kitscript', plugin_dir_url(__FILE__) . '../../js/kitscript.js', ['jquery'], null, true);
        wp_enqueue_script('waybill-pagination', plugin_dir_url(__FILE__) . '../../js/waybill-pagination.js', ['jquery'], null, true);

        ob_start();
        include plugin_dir_path(__FILE__) . 'waybill-form.php';
        return ob_get_clean();
    }

    public static function process_form()
    {
        // First determine if this is an AJAX request
        $is_ajax = defined('DOING_AJAX') && DOING_AJAX;

        // Verify the appropriate nonce based on request type
        if ($is_ajax) {
            // For AJAX requests
            if (!check_ajax_referer('add_waybill_nonce', '_ajax_nonce', false)) {
                wp_send_json_error('Invalid nonce', 403);
                wp_die();
            }
        } else {
            // For regular form submissions
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'add_waybill_nonce')) {
                wp_die('Nonce verification failed');
            }
        }

        // Check permissions (allow plugin-specific capability)
        if (!current_user_can('kit_update_data') && !current_user_can('manage_options') && !current_user_can('edit_pages')) {
            if ($is_ajax) {
                wp_send_json_error('Unauthorized', 403);
            } else {
                wp_die('Unauthorized');
            }
        }

        // Process the form data
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Debug: Log the POST data
            error_log('Waybill form submission: ' . print_r($_POST, true));
            
            // Enhanced validation with field-specific errors
            $errors = [];
            
            // Check customer information
            if (empty($_POST['customer_name']) && empty($_POST['cust_id'])) {
                $errors[] = [
                    'field' => 'customer_name',
                    'message' => 'Customer information is required'
                ];
            }
            
            // Check destination information for non-warehoused items
            if (!isset($_POST['warehoused']) || $_POST['warehoused'] != 1) {
                if (empty($_POST['destination_country'])) {
                    $errors[] = [
                        'field' => 'destination_country',
                        'message' => 'Destination country is required for non-warehoused items'
                    ];
                }
                if (empty($_POST['destination_city'])) {
                    $errors[] = [
                        'field' => 'destination_city',
                        'message' => 'Destination city is required for non-warehoused items'
                    ];
                }
            }
            
            // If there are validation errors, return them
            if (!empty($errors)) {
                if ($is_ajax) {
                    wp_send_json_error([
                        'message' => 'Please fix the validation errors below.',
                        'errors' => $errors
                    ]);
                } else {
                    // Redirect back to the form with encoded errors for inline display
                    $referer = wp_get_referer();
                    if (!$referer) {
                        $referer = admin_url('admin.php?page=08600-Waybill');
                    }
                    $encoded_errors = base64_encode(wp_json_encode($errors));
                    $redirect_url = add_query_arg('form_errors', rawurlencode($encoded_errors), $referer);
                    wp_redirect($redirect_url);
                }
                return;
            }

            $result = self::save_waybill($_POST);

            if (is_wp_error($result)) {
                // Handle the error gracefully
                $error_message = $result->get_error_message();
                $error_code = $result->get_error_code();
                error_log('Waybill save error: ' . $error_message);
                
                if ($is_ajax) {
                    // Return field-specific error if available
                    if ($error_code === 'validation_error') {
                        wp_send_json_error([
                            'message' => 'Please fix the validation errors below.',
                            'errors' => [
                                [
                                    'field' => 'general',
                                    'message' => $error_message
                                ]
                            ]
                        ]);
                    } else {
                        wp_send_json_error(['message' => 'Error saving waybill: ' . $error_message]);
                    }
                } else {
                    wp_redirect(add_query_arg('error', urlencode($error_message), wp_get_referer()));
                }
                return;
            }

            // Success case
            if ($is_ajax) {
                wp_send_json_success([
                    'message' => 'Waybill saved successfully.',
                    'waybill_id' => $result['id'] ?? $result
                ]);
            } else {
                // Role-aware redirect after creation
                $waybill_id = isset($result['id']) ? intval($result['id']) : 0;
                $waybill_no = isset($result['waybill_no']) ? $result['waybill_no'] : '';

                if (class_exists('KIT_User_Roles') && (KIT_User_Roles::is_admin() || KIT_User_Roles::is_manager())) {
                    // Admins/Managers: go to view page
                    $redirect_url = admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $waybill_id . '&waybill_atts=view_waybill');
                    $redirect_url = add_query_arg('message', urlencode('Waybill created successfully'), $redirect_url);
                } else {
                    // Data Capturers/others: back to create page
                    $redirect_url = add_query_arg([
                        'page' => '08600-waybill-create',
                        'success' => '1',
                        'waybill_no' => $waybill_no,
                        'message' => 'Waybill created successfully!'
                    ], admin_url('admin.php'));
                }

                wp_redirect($redirect_url);
            }

            exit;
        }
    }

    //Get the destination country by waybill.delivery_id which referneces the delivery.id
    /**
     * Get the destination country by delivery_id.
     * If $delivery_id is provided, returns the country name as string.
     * If called as an AJAX action, echoes JSON and exits.
     *
     * @param int|null $delivery_id
     * @return string|void
     */
    public static function getDestinationCountry($delivery_id = null)
    {
        global $wpdb;

        // If called as an AJAX action (admin_post), get delivery_id from POST or GET
        if ($delivery_id === null) {
            $delivery_id = isset($_REQUEST['delivery_id']) ? intval($_REQUEST['delivery_id']) : 0;
            $is_ajax = defined('DOING_AJAX') && DOING_AJAX;

            if (! $delivery_id) {
                if ($is_ajax) {
                    wp_send_json_error('No delivery_id provided.');
                } else {
                    wp_die('No delivery_id provided.');
                }
            }
        }

        $table = $wpdb->prefix . 'kit_deliveries';
        $country = $wpdb->get_var($wpdb->prepare(
            "SELECT destination_country FROM $table WHERE id = %d",
            $delivery_id
        ));

        if ($country) {
            // If called as AJAX action, return JSON
            $action = (string)($_REQUEST['action'] ?? '');
            if (! is_null($delivery_id) && $action !== '' && strpos($action, 'getDestinationCountry') !== false) {
                wp_send_json_success(['destination_country' => $country]);
            }
            return esc_html($country);
        } else {
            if (! is_null($delivery_id) && isset($_REQUEST['action']) && $_REQUEST['action'] !== '' && strpos($_REQUEST['action'], 'getDestinationCountry') !== false) {
                wp_send_json_error('Destination country not found.');
            }
            return '<span class="text-red-500">Not set</span>';
        }
    }

    // Calculate total:
    // $mass_charge, $volume_charge, $misc_total may not be empty
    // $waybillItemsTotal is conidtional, its not a must to include it, if its set, then we can include it, get the 10% value of the total $waybillItemsTotal
    // 
    public static function calculate_total($mass_charge, $volume_charge, $misc_total, $waybillItemsTotal, $charge_basis = null, $ttt = null)
    {
        $itemsTotal = 0;
        $include_sad500 = 0;
        $include_sadc = 0;

        // Ensure all inputs are floats
        $include_sad500 = (isset($_POST['include_sad500']) && $_POST['include_sad500'] == 1 || (isset($ttt['include_sad500']) && $ttt['include_sad500'])) ? self::sadc_certificate() : 0;
        $include_sadc = (isset($_POST['include_sadc']) && $_POST['include_sadc'] == 1 || (isset($ttt['include_sadc']) && $ttt['include_sadc'])) ? self::sad() : 0;

        // Calculate VAT and international price
        $vatCharge = 0;
        $internationalPrice = 0;
        
        // Determine the better charge first to calculate VAT on the correct amount
        $better_charge = 0;
        if ($charge_basis == null) {
            $better_charge = max($mass_charge, $volume_charge);
        } elseif ($charge_basis == 'mass' || $charge_basis == 'weight') {
            $better_charge = $mass_charge;
        } else if ($charge_basis == 'volume') {
            $better_charge = $volume_charge;
        }
        
        if ((isset($_POST['vat_include']) && $_POST['vat_include'] == 1) || (is_array($ttt) && isset($ttt['vat_include']) && $ttt['vat_include'] == 1)) {
            // Calculate VAT on the waybill amount (better charge) plus waybill items
            $vatBase = $better_charge + $waybillItemsTotal;
            $vatCharge = self::vatCharge($vatBase);
        } else {
            // If VAT is not selected, add international price (converted to Rands)
            // Prefer snapshot if provided through $ttt
            if (is_array($ttt) && isset($ttt['international_price_rands']) && is_numeric($ttt['international_price_rands'])) {
                $internationalPrice = floatval($ttt['international_price_rands']);
            } else {
                $internationalPrice = self::international_price_in_rands();
            }
        }

        $additionalCharges = $misc_total + $include_sad500 + $include_sadc + $vatCharge + $internationalPrice;
        // Calculate total
        $total = $better_charge + $additionalCharges;
        return (float) $total;
    }

    /**
     * Processes miscellaneous items from form data and calculates total
     *
     * @param array $miscData The misc data array from form input
     * @return array Contains combined misc items and total amount
     */
    public static function manageMiscItems(array $miscData): array
    {
        // Use getMiscCharges function instead of manual handling
        $misc_result = self::getMiscCharges($miscData, []);

        return [
            'misc_items' => $misc_result->misc_items,
            'misc_total' => floatval($misc_result->misc_total)
        ];
    }

    public static function approve_waybill()
    {

        if (! current_user_can('administrator') && ! current_user_can('manager')) {
            wp_die('Unauthorized access');
        }

        // Validate and sanitize input
        $waybill_id  = isset($_POST['waybill_id']) ? intval($_POST['waybill_id']) : 0;
        $user_id     = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
        $delivery_id = isset($_POST['delivery_id']) ? intval($_POST['delivery_id']) : 0;

        if (! $waybill_id || ! $user_id) {
            wp_die('Invalid request');
        }

        // Check if delivery is selected
        if (! $delivery_id) {
            wp_redirect(add_query_arg([
                'waybill_approved' => '0',
                'error'            => 'no_delivery_selected',
            ], wp_get_referer()));
            exit;
        }

        global $wpdb;
        $waybills_table = $wpdb->prefix . 'kit_waybills';

        $updated = $wpdb->update(
            $waybills_table,
            [
                'approval_userid' => $user_id,
                'approval'        => 1,
                'delivery_id'     => $delivery_id, // Add delivery_id to the update
            ],
            ['id' => $waybill_id],
            ['%d', '%d', '%d'],
            ['%d']
        );

        if ($updated !== false) {
            wp_redirect(add_query_arg('waybill_approved', '1', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg([
                'waybill_approved' => '0',
                'error'            => 'db_error',
            ], wp_get_referer()));
        }
        exit;
    }

    public static function getMiscCharges($miscData, $serial)
    {
        $misc_combined = [];
        $misc_total = 0;

        if (isset($miscData['misc_item'], $miscData['misc_price'], $miscData['misc_quantity'])) {
            $misc_item = $miscData['misc_item'];
            $amounts = $miscData['misc_price'];
            $quantities = $miscData['misc_quantity'];

            $count = count($misc_item);

            for ($i = 0; $i < $count; $i++) {
                if (!empty($misc_item[$i])) {
                    $is_realized = is_array($serial) && in_array($i, $serial); // Example logic
                    $misc_combined[] = [
                        'misc_item' => $misc_item[$i],
                        'misc_price' => floatval($amounts[$i]),
                        'misc_quantity' => intval($quantities[$i]),
                        'realized' => $is_realized,
                    ];
                    $misc_total += floatval($amounts[$i]) * intval($quantities[$i]);
                }
            }
        }

        return (object)[
            'misc_items' => $misc_combined,
            'misc_total' => number_format($misc_total, 2, '.', '')
        ];
    }

    public static function waybillItemsTotal($items)
    {
        $total = 0;

        foreach ($items as $item) {
            // Ensure the values are numeric and safe to use
            $quantity = isset($item['quantity']) ? (float) $item['quantity'] : 0;
            $unit_price = isset($item['unit_price']) ? (float) $item['unit_price'] : 0;

            $total += $quantity * $unit_price;
        }

        return $total;
    }

    //others
    public static function vatRate()
    {
        // Consolidated VAT configuration: fixed at 10% across the system
        // Returns a percentage value for display (10 == 10%)
        return 10.0;
    }
    public static function vatCharge($itemsTotal)
    {
        // vatRate() returns a percentage value (e.g., 15 for 15%) for the settings UI
        // Convert to decimal for calculation, but guard if it's already in decimal form
        $rate = self::vatRate();
        $rate = is_numeric($rate) ? floatval($rate) : 0.0;
        if ($rate > 1) {
            $rate = $rate / 100.0; // convert 15 -> 0.15
        }
        return $itemsTotal * $rate;
    }

    /**
     * Check if VAT warning should be shown (VAT checked but no items)
     */
    public static function shouldShowVatWarning($vat_checked, $waybillItemsTotal)
    {
        return $vat_checked && $waybillItemsTotal == 0;
    }
    public static function sad()
    {
        global $wpdb;
        $wpdprefix = $wpdb->prefix;
        $company_table = $wpdprefix . 'kit_company_details';
        $sadc_charge = $wpdb->get_var("SELECT sadc_charge FROM $company_table LIMIT 1");
        if ($sadc_charge !== null && is_numeric($sadc_charge)) {
            return floatval($sadc_charge);
        }
    }
    public static function sadc_certificate()
    {
        global $wpdb;
        $wpdprefix = $wpdb->prefix;
        $company_table = $wpdprefix . 'kit_company_details';
        $sad500_charge = $wpdb->get_var("SELECT sad500_charge FROM $company_table LIMIT 1");
        if ($sad500_charge !== null && is_numeric($sad500_charge)) {
            return floatval($sad500_charge);
        }
    }
    
    public static function international_price()
    {
        global $wpdb;
        $wpdprefix = $wpdb->prefix;
        $company_table = $wpdprefix . 'kit_company_details';
        $international_price = $wpdb->get_var("SELECT international_price FROM $company_table LIMIT 1");
        if ($international_price !== null && is_numeric($international_price)) {
            return floatval($international_price);
        }
        // Default value if not set
        return 100.00;
    }
    
    public static function international_price_in_rands()
    {
        // Live USD->ZAR with transient caching (fallback to 18.50)
        $usd_price = self::international_price();
        $cache_key = 'kit_usd_zar_rate';
        $rate = get_transient($cache_key);
        if ($rate === false) {
            $rate = 18.50; // fallback
            $response = wp_remote_get('https://open.er-api.com/v6/latest/USD', [ 'timeout' => 6 ]);
            if (!is_wp_error($response) && (int) wp_remote_retrieve_response_code($response) === 200) {
                $data = json_decode(wp_remote_retrieve_body($response), true);
                if (isset($data['rates']['ZAR']) && is_numeric($data['rates']['ZAR'])) {
                    $rate = floatval($data['rates']['ZAR']);
                }
            }
            set_transient($cache_key, $rate, 10 * MINUTE_IN_SECONDS);
        }
        return $usd_price * floatval($rate);
    }
    public static function prepareMiscCharges($data)
    {
        $misc_combined = [];
        $misc_total = 0.0;

        // Assign variables first
        $has_vat = !empty($data['vat_include']);
        $has_sad500 = !empty($data['include_sad500']);
        $has_waybill_fee = !empty($data['include_sadc']);

        $manny = (isset($data['enable_price_manipulator'])) ? true : false;
        $manny_mass_rate = (isset($data['enable_price_manipulator'])) ? floatval(isset($data['mass_charge_manipulator'])) : null;
        $manny_volume_rate = (isset($data['enable_price_manipulator'])) ? floatval(isset($data['manny_volume_rate'])) : null;


        $others = [
            'waybill_description' => ($data['waybill_description'] ?? null),
            'mass_rate' => floatval($data['mass_rate'] ?? 0),
            'total_volume' => floatval($data['total_volume'] ?? 0),
            'manny' => $manny,
            'manny_mass_rate' => $manny_mass_rate,
            'manny_volume_rate' => $manny_volume_rate,
        ];

        /*  
       echo '<pre>';
        echo '<br>';
        print_r($_POST);
        echo '<br>';
        print_r($has_sad500);
        print_r($has_waybill_fee);
        echo '<br>';
        print_r($manny);
        echo '<br>';
        print_r($manny_mass_rate);
        echo '<br>';
        print_r($manny_volume_rate);
        echo '</pre>';
        exit(); 
        */

        // Remove/add keys based on rules
        if (isset($data['misc']) && is_array($data['misc'])) {
            $misc_result = self::getMiscCharges($data['misc'], []);
            $misc_combined = $misc_result->misc_items;
            $misc_total = floatval($misc_result->misc_total);
        }

        if ($has_vat) {
            // 🔒 FIX: Calculate items total without inserting to database (waybill not created yet)
            $waybillItemsTotal = 0;
            if (!empty($_POST['custom_items']) && is_array($_POST['custom_items'])) {
                foreach ($_POST['custom_items'] as $item) {
                    if (!empty($item['item_name']) && isset($item['quantity']) && isset($item['unit_price'])) {
                        $quantity = intval($item['quantity']);
                        $unit_price = floatval($item['unit_price']);
                        $waybillItemsTotal += $quantity * $unit_price;
                    }
                }
            }

            // Only VAT, remove SAD500 and waybill fee if present
            unset($data['include_sad500'], $data['include_sadc']);
            $others = array_filter($others, function ($k) {
                return !in_array($k, ['include_sad500', 'include_sadc']);
            }, ARRAY_FILTER_USE_KEY);
            $others['vat_total'] = self::vatCharge($waybillItemsTotal);
        } elseif ($has_sad500 || $has_waybill_fee) {
            // Only SAD500/waybill fee, remove VAT if present
            $others = array_filter($others, function ($k) {
                return $k !== 'vat';
            }, ARRAY_FILTER_USE_KEY);
            if ($has_sad500) $others['include_sad500'] = self::sad();
            if ($has_waybill_fee) $others['include_sadc'] = self::sadc_certificate();
        } else {
            // If no VAT is selected, snapshot live exchange conversion
            $rate_used = self::international_price_in_rands() / max(1.0, self::international_price());
            $others['usd_to_zar_rate_used'] = $rate_used;
            $others['international_price_rands'] = self::international_price_in_rands();
        }

        return [
            'misc_items' => $misc_combined,
            'misc_total' => $misc_total,
            'others'     => $others,
        ];
    }

    public static function save_waybill($data)
    {
        global $wpdb;

        $waybills_table = $wpdb->prefix . 'kit_waybills';

        if (isset($_POST['warehoused']) && $_POST['warehoused'] == 1) {
            // Override delivery_id using delivery_reference = 'warehoused'
            $warehoused_delivery_id = $wpdb->get_var(
                "SELECT id FROM {$wpdb->prefix}kit_deliveries WHERE delivery_reference = 'warehoused' LIMIT 1"
            );
            // Override direction_id where origin and destination country = 1
            $warehoused_direction_id = $wpdb->get_var(
                "SELECT id FROM {$wpdb->prefix}kit_shipping_directions WHERE origin_country_id = 1 AND destination_country_id = 1 LIMIT 1"
            );
            if ($warehoused_delivery_id) {
                $_POST['delivery_id'] = $warehoused_delivery_id;
            }
            if ($warehoused_direction_id) {
                $_POST['direction_id'] = $warehoused_direction_id;
            }
            // Override status
            $_POST['status'] = 'warehoused';

            // Don't add departure date for warehoused items
            $_POST['dispatch_date'] = null;
        }

        // 👥 Customer 1 Details
        $cust_id = isset($_POST['cust_id']) ? $_POST['cust_id'] : null;
        $customer_select = isset($_POST['customer_select']) ? $_POST['customer_select'] : null;
        $customer_name = isset($_POST['customer_name']) ? $_POST['customer_name'] : null;
        $customer_surname = isset($_POST['customer_surname']) ? $_POST['customer_surname'] : null;
        $company_name = isset($_POST['company_name']) ? $_POST['company_name'] : null;
        $email_address = isset($_POST['email_address']) ? $_POST['email_address'] : null;
        $cell = isset($_POST['cell']) ? $_POST['cell'] : null;
        $address = isset($_POST['address']) ? $_POST['address'] : null;
        $destination_city = isset($_POST['destination_city']) ? $_POST['destination_city'] : null;

        // Validate required fields for waybill creation
        $is_warehoused = isset($_POST['warehoused']) && $_POST['warehoused'] == 1;
        
        if ($is_warehoused) {
            // For warehoused items, we don't need destination city/country
            if (empty($cust_id) && empty($customer_name)) {
                return new WP_Error('validation_error', 'Customer information is required even for warehoused items.');
            }
        } else {
            // For non-warehoused items, destination country and city are required
            $destination_country = isset($_POST['destination_country']) ? $_POST['destination_country'] : null;

            if (empty($destination_country)) {
                return new WP_Error('validation_error', 'Destination country is required for non-warehoused items.');
            }
            if (empty($destination_city)) {
                return new WP_Error('validation_error', 'Destination city is required for non-warehoused items.');
            }
            if (empty($cust_id) && empty($customer_name)) {
                return new WP_Error('validation_error', 'Customer information is required.');
            }
        }
        $SADC_charge = self::sad();
        $vat = isset($_POST['vat_include']) ? $_POST['vat_include'] : 0;
        $country_id = isset($_POST['origin_country']) ? $_POST['origin_country'] : 1;
        $city_id =  isset($_POST['origin_city']) ? $_POST['origin_city'] : 1;
        $waybill_description =  isset($_POST['waybill_description']) ? $_POST['waybill_description'] : 1;

        $own_certificate = isset($_POST['own_certificate']) ? $_POST['own_certificate'] : 0;

        if ($own_certificate == 1) {
            $SADC_charge = $SADC_charge - 500;
        }

        // 💰 Charge Details
        $mass_charge = isset($_POST['mass_charge']) ? floatval(str_replace(',', '.', $_POST['mass_charge'] ?: '')) : 0;

        if (isset($_POST['enable_price_manipulator'], $_POST['new_mass_rate']) && floatval($_POST['new_mass_rate']) > 0) {
            $mass_charge = floatval($_POST['new_mass_rate']);
        }

        $volume_charge = isset($_POST['volume_charge']) ? $_POST['volume_charge'] : null;

        // Use posted charge_basis if available, otherwise determine automatically
        $charge_basis = $_POST['charge_basis'] ?? '';
        if (empty($charge_basis) && isset($_POST['mass_charge']) && isset($_POST['volume_charge'])) {
            if ($_POST['mass_charge'] > $_POST['volume_charge']) {
                $charge_basis = 'mass';
            } else {
                $charge_basis = 'volume';
            }
        }

        // 🧾 Custom Items
        $customer_id = isset($_POST['cust_id']) ? intval($_POST['cust_id']) : 0;
        // 🧮 Misc Charges
        $final_misc_data = self::prepareMiscCharges($_POST);

        // Persist pricing snapshot to ensure PDF/Invoice match UI exactly
        $posted_total_volume = isset($_POST['total_volume']) ? floatval(str_replace(',', '.', $_POST['total_volume'])) : 0.0;
        $posted_total_mass   = isset($_POST['total_mass_kg']) ? floatval(str_replace(',', '.', $_POST['total_mass_kg'])) : 0.0;
        $posted_volume_charge = isset($_POST['volume_charge']) ? floatval(str_replace(',', '.', $_POST['volume_charge'])) : 0.0;
        $posted_mass_charge   = isset($_POST['mass_charge']) ? floatval(str_replace(',', '.', $_POST['mass_charge'])) : 0.0;

        // Custom volume rate support from UI (checkbox/value names may vary slightly across forms)
        $use_custom_volume_rate = isset($_POST['use_custom_volume_rate']) && intval($_POST['use_custom_volume_rate']) === 1;
        $custom_volume_rate     = isset($_POST['custom_volume_rate_per_m3']) ? floatval(str_replace(',', '.', $_POST['custom_volume_rate_per_m3'])) : 0.0;

        // Derive snapshot rates that were actually used at save time
        $snapshot_volume_rate = 0.0;
        if ($use_custom_volume_rate && $custom_volume_rate > 0) {
            $snapshot_volume_rate = $custom_volume_rate;
        } elseif ($posted_total_volume > 0 && $posted_volume_charge > 0) {
            $snapshot_volume_rate = $posted_volume_charge / max(0.0001, $posted_total_volume);
        }
        $snapshot_mass_rate = 0.0;
        if ($posted_total_mass > 0 && $posted_mass_charge > 0) {
            $snapshot_mass_rate = $posted_mass_charge / max(0.0001, $posted_total_mass);
        }

        // Store snapshot in miscellaneous->others for downstream consumers (PDF, invoice, UI)
        if (!isset($final_misc_data['others']) || !is_array($final_misc_data['others'])) {
            $final_misc_data['others'] = [];
        }
        $final_misc_data['others']['used_charge_basis'] = $charge_basis;
        $final_misc_data['others']['use_custom_volume_rate'] = $use_custom_volume_rate ? 1 : 0;
        $final_misc_data['others']['custom_volume_rate_per_m3'] = $custom_volume_rate;
        $final_misc_data['others']['volume_rate_used'] = $snapshot_volume_rate;
        $final_misc_data['others']['mass_rate'] = $snapshot_mass_rate > 0 ? $snapshot_mass_rate : ($final_misc_data['others']['mass_rate'] ?? 0);
        $final_misc_data['others']['product_invoice_amount_snapshot'] = isset($waybillTotal) ? floatval($waybillTotal) : 0.0;

        $misc_serialized = serialize($final_misc_data);

        //if $cust_id = 0, meaning no customer is selected, then create a new customer
        if (empty($cust_id) || $customer_select === 'new') {
            // Create a new customer
            $customer_id = KIT_Customers::save_customer([
                'customer_select' => $customer_select,
                'customer_name' => $customer_name,
                'customer_surname' => $customer_surname,
                'cell' => $cell,
                'address' => $address,
                'company_name' => $company_name,
                'email_address' => $email_address,
                'country_id' => $country_id,
                'city_id' => $city_id,
            ]);
            
            // Check if customer creation failed
            if (!$customer_id || is_wp_error($customer_id)) {
                return new WP_Error('customer_error', 'Failed to create customer: ' . (is_wp_error($customer_id) ? $customer_id->get_error_message() : 'Unknown error'));
            }
        }


        if (isset($_POST['misc'])) {
            $misc = self::manageMiscItems($_POST['misc']);

            $misc_total = $misc['misc_total'];
        } else {
            $misc_total = 0;
        }
        // Generate waybill number if not provided
        $waybill_no = self::generate_waybill_number();

        // 🔒 FIX: Calculate items total without inserting to database (waybill not created yet)
        $waybillItemsTotal = 0;
        if (!empty($_POST['custom_items']) && is_array($_POST['custom_items'])) {
            foreach ($_POST['custom_items'] as $item) {
                if (!empty($item['item_name']) && isset($item['quantity']) && isset($item['unit_price'])) {
                    $quantity = intval($item['quantity']);
                    $unit_price = floatval($item['unit_price']);
                    $waybillItemsTotal += $quantity * $unit_price;
                }
            }
        }
        // Use bulletproof calculator
        require_once plugin_dir_path(__FILE__) . 'bulletproof-calculator.php';
        
        $calculation_params = [
            'mass_charge' => $mass_charge,
            'volume_charge' => $volume_charge,
            'misc_total' => $misc_total,
            'waybill_items_total' => $waybillItemsTotal,
            'charge_basis' => $charge_basis,
            'include_sad500' => isset($_POST['include_sad500']) ? 1 : 0,
            'include_sadc' => isset($_POST['include_sadc']) ? 1 : 0,
            'include_vat' => isset($_POST['vat_include']) ? 1 : 0
        ];
        
        $calculation_breakdown = KIT_Bulletproof_Calculator::calculate_waybill_total($calculation_params);
        $waybillTotal = $calculation_breakdown['totals']['final_total'];

        $include_sad500 = isset($_POST['include_sad500']) ? 1 : 0;
        $include_sadc = isset($_POST['include_sadc']) ? 1 : 0;
        $vat = isset($_POST['vat_include']) ? 1 : 0;


        /* echo '<pre>';
        print_r($mass_charge);
        echo '<br>';
        print_r($volume_charge);
        echo '<br>';
        print_r($misc_total);
        echo '<br>';
        print_r($waybillItemsTotal);
        echo '<br>';
        print_r($charge_basis);
        echo '</br>';
        print_r($waybillTotal); */


        // Bulletproof calculator handles all calculations - no need for double-checking

        // Prepare waybill data
        $direction_id_input = isset($_POST['direction_id']) ? (int)$_POST['direction_id'] : 0;
        $delivery_id_input = isset($_POST['delivery_id']) ? (int)$_POST['delivery_id'] : 0;

        if ($direction_id_input <= 0 && $delivery_id_input > 0) {
            // Resolve direction_id from deliveries table
            $dir_table = $wpdb->prefix . 'kit_shipping_directions';
            $deliv_table = $wpdb->prefix . 'kit_deliveries';
            $resolved = (int) $wpdb->get_var($wpdb->prepare("SELECT direction_id FROM $deliv_table WHERE id = %d", $delivery_id_input));
            if ($resolved > 0) {
                $direction_id_input = $resolved;
            }
        }

        // As a final fallback, use SA->SA direction if exists
        if ($direction_id_input <= 0) {
            $fallback_dir = (int) $wpdb->get_var("SELECT id FROM {$wpdb->prefix}kit_shipping_directions WHERE origin_country_id = 1 AND destination_country_id = 1 LIMIT 1");
            if ($fallback_dir > 0) {
                $direction_id_input = $fallback_dir;
            }
        }

        $waybill_data = [
            'direction_id' => (int)$direction_id_input,
            'delivery_id' => (int)($delivery_id_input ?: 1),
            'customer_id' => $customer_id,
            'city_id' => $destination_city ? (int)$destination_city : 1,
            'waybill_no' => $waybill_no,
            'product_invoice_number' => 'INV-' . date('Ymd-His'),
            'product_invoice_amount' => (float)$waybillTotal,
            'waybill_items_total' => (float)$waybillItemsTotal,
            'item_length' => (float)($_POST['item_length'] ?? 0),
            'item_width' => (float)($_POST['item_width'] ?? 0),
            'item_height' => (float)($_POST['item_height'] ?? 0),
            'total_mass_kg' => (float)($_POST['total_mass_kg'] ?? 0),
            'total_volume' => (float)($_POST['total_volume'] ?? 0),
            'mass_charge' => (float)($_POST['mass_charge'] ?? 0),
            'volume_charge' => (float)($_POST['volume_charge'] ?? 0),
            'charge_basis' => $charge_basis,
            'miscellaneous' => !empty($misc_serialized) ? $misc_serialized : '',
            'include_sad500' => $include_sad500,
            'include_sadc' => $include_sadc,
            'vat_include' => $vat,
            'tracking_number' => 'TRK-' . strtoupper(wp_generate_password(8, false)),
            'created_by' => get_current_user_id(),
            'last_updated_by' => get_current_user_id(),
            'status' => $is_warehoused ? 'warehoused' : 'pending',
        ];

        // Insert waybill
        $inserted = $wpdb->insert($waybills_table, $waybill_data);

        if (!$inserted) {
            error_log('Waybill insert failed: ' . $wpdb->last_error);
            error_log('Waybill data: ' . print_r($waybill_data, true));
            return new WP_Error('db_error', 'Could not save waybill: ' . $wpdb->last_error);
        }

        $waybill_id = $wpdb->insert_id;

        // Track warehouse action if waybill is being warehoused
        if (isset($_POST['warehoused']) && $_POST['warehoused'] == 1) {
            self::track_warehouse_action($waybill_no, $waybill_id, $customer_id, 'warehoused', null, 'warehoused');
        }

        // Save items if provided
        if (!empty($data['custom_items'])) {
            self::save_waybill_items($data['custom_items'], $waybill_no, $waybill_id, $vat);
        }

        // Add to warehouse if waybill is warehoused
        if (isset($_POST['warehoused']) && $_POST['warehoused'] == 1) {
            // Include warehouse functions
            require_once plugin_dir_path(__FILE__) . '../warehouse/warehouse-functions.php';
            
            $warehouse_data = [
                'waybill_id' => $waybill_id,
                'waybill_no' => $waybill_no,
                'customer_id' => $customer_id
            ];
            
            $warehouse_result = KIT_Warehouse::addToWarehouse($waybill_id, $warehouse_data);
            
            if (is_wp_error($warehouse_result)) {
                error_log('Failed to add waybill to warehouse: ' . $warehouse_result->get_error_message());
                // Don't fail the waybill creation, just log the error
            }
        }


        if ($waybill_id && $waybill_no) {
            // Check for VAT warning
            $vat_warning = '';
            if (self::shouldShowVatWarning($vat, $waybillItemsTotal)) {
                $vat_warning = 'VAT was checked but no waybill items were found. No VAT was added to the total.';
            }

            // Return success data
            return [
                'id' => $waybill_id,
                'waybill_no' => $waybill_no,
                'success' => true,
                'message' => 'Waybill created successfully'
            ];
        } else {
            return new WP_Error('db_error', 'Failed to create waybill - missing required data');
        }
    }
    /**
     * Fetches waybills from the database with optional filters and sorting.
     *
     * @param array $args Optional. Arguments for filtering and sorting. Default empty array.
     * @return array Array of waybill objects.
     */
    public static function get_waybills($args = [])
    {
        global $wpdb;

        $waybills_table   = $wpdb->prefix . 'kit_waybills';
        $customers_table  = $wpdb->prefix . 'kit_customers';
        $deliveries_table = $wpdb->prefix . 'kit_deliveries';
        $users_table      = $wpdb->users;

        $defaults = [
            'number'        => 20,
            'offset'        => 0,
            'orderby'       => 'w.created_at',
            'order'         => 'DESC',
            'fields'        => 'all',
            'status'        => '',
            'approval'      => '',
            'custom_format' => false,
        ];
        $args = wp_parse_args($args, $defaults);

        // Validate orderby and order
        $allowed_orderby = ['w.created_at', 'w.id', 'w.status'];
        $allowed_order   = ['ASC', 'DESC'];

        if (! in_array($args['orderby'], $allowed_orderby)) {
            $args['orderby'] = 'w.created_at';
        }

        if (! in_array(strtoupper($args['order']), $allowed_order)) {
            $args['order'] = 'DESC';
        }

        // Build select fields
        if ($args['custom_format']) {

            $select_fields = "
                w.id AS waybill_id,
                w.waybill_no,
                w.approval,
                w.approval_userid,
                w.customer_id,
                w.status,
                w.product_invoice_number,
                c.name AS customer_name,
                c.surname AS customer_surname,
                d.destination_city,
                d.origin_country,
                d.destination_country,
                d.delivery_reference,
                u.display_name AS created_by,
                a.user_login AS approved_by_username
                ";
        } else {
            $select_fields = "w.id AS waybill_id,
            w.*,
            d.delivery_reference,
            c.name AS customer_name, 
            c.surname AS customer_surname,
            u.display_name AS created_by,
            a.user_login AS approved_by_username,
            w.status AS approval_status";
        }



        // Base query
        $query = "SELECT $select_fields
        FROM $waybills_table AS w
        LEFT JOIN $customers_table AS c ON w.customer_id = c.cust_id
        LEFT JOIN $deliveries_table AS d ON w.delivery_id = d.id
        LEFT JOIN $users_table AS u ON w.created_by = u.ID
        LEFT JOIN $users_table AS a ON w.approval_userid = a.ID";

        // WHERE clause
        $where  = [];
        $params = [];

        if (! empty($args['status'])) {
            $where[]  = "w.status = %s";
            $params[] = $args['status'];
        }

        if (! empty($args['approval'])) {
            $where[]  = "w.approval = %s";
            $params[] = $args['approval'];
        }

        if ($where) {
            $query .= " WHERE " . implode(" AND ", $where);
        }

        // Append ORDER BY (safe because it's whitelisted above)
        $query .= " ORDER BY {$args['orderby']} {$args['order']}";

        // Add LIMIT/OFFSET placeholders
        $query .= " LIMIT %d OFFSET %d";
        $params[] = $args['number'];
        $params[] = $args['offset'];

        // Prepare full query once, with all params
        $prepared_query = $wpdb->prepare($query, ...$params);

        // Run query
        return $wpdb->get_results($prepared_query);
    }

    /**
     * Get all waybills with status 'pending'.
     *
     * @param array $args Optional. Additional query arguments.
     * @return array Array of pending waybills.
     */
    public static function getWaybillsStatusPending($args = [])
    {
        $args['status'] = 'pending';
        global $wpdb;
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $customers_table = $wpdb->prefix . 'kit_customers';

        $query = $wpdb->prepare(
            "SELECT w.id AS waybill_id, w.*, c.name AS customer_name, c.surname AS customer_surname
            FROM $waybills_table AS w
            LEFT JOIN $customers_table AS c ON w.customer_id = c.cust_id
            WHERE w.approval = %s AND w.status = %s",
            'pending',
            'pending'
        );

        return $wpdb->get_results($query);
    }

    public static function add_waybill_dash()
    {
        echo "iugiuh";
    }
    public static function get_waybill_count()
    {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_waybills");
        return $count ? (int) $count : 0;
    }

    public static function get_recent_waybill_count()
    {
        global $wpdb;
        $date  = date('Y-m-d', strtotime('-7 days'));
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}kit_waybills WHERE created_at >= %s",
            $date
        ));
        return $count ? (int) $count : 0;
    }

    public static function get_pending_waybill_count()
    {
        global $wpdb;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}kit_waybills WHERE status = %s",
            'pending'
        ));
        return $count ? (int) $count : 0;
    }

    /**
     * Generate a unique waybill number starting from 4000
     */
    public static function generate_waybill_number()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_waybills';

        // Get the highest waybill number
        $max_waybill_no = $wpdb->get_var("SELECT MAX(CAST(waybill_no AS UNSIGNED)) FROM $table_name");

        // If no waybills exist, start from 4000, otherwise increment from the highest
        $next_waybill_no = $max_waybill_no ? max(4000, intval($max_waybill_no) + 1) : 4000;

        // Check if the generated number already exists, if so, increment until unique
        do {
            $exists = $wpdb->get_var(
                $wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE waybill_no = %d", $next_waybill_no)
            );
            if ($exists) {
                $next_waybill_no++;
            }
        } while ($exists);

        return $next_waybill_no;
    }

    public static function get_latest_waybill_date()
    {
        global $wpdb;
        $date = $wpdb->get_var(
            "SELECT created_at FROM {$wpdb->prefix}kit_waybills ORDER BY created_at DESC LIMIT 1"
        );

        if (empty($date)) {
            return '';
        }

        return date('M j, Y', strtotime($date));
    }

    public static function render_table_with_pagination($data, $args = [])
    {


        $ajax = $args['ajax'] ?? false;
        $defaults = [
            'fields' => ['waybill_no', 'customer_id', 'status'],
            'table_class' => 'min-w-full divide-y divide-gray-200 text-xs',
            'actions' => true,
            'show_create_quotation' => false,
            'status_labels' => [
                'pending' => 'Pending',
                'completed' => 'Completed',
                'cancelled' => 'Cancelled'
            ],
            'status_colors' => [
                'pending' => 'bg-yellow-100 text-yellow-800',
                'completed' => 'bg-green-100 text-green-800',
                'cancelled' => 'bg-red-100 text-red-800'
            ],
            'items_per_page' => isset($_GET['items_per_page']) ? intval($_GET['items_per_page']) : 5,
            'current_page' => isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1
        ];

        $args = wp_parse_args($args, $defaults);

        //if $args['items_per_page'] is bigger than 5, then set current_page to 1, so that the pagination is not affected
        if ($args['items_per_page'] > 5) {
            $args['current_page'] = 1;
        }

        // Pagination Setup
        $items_per_page = $args['items_per_page'];
        $total_items = count($data);
        $total_pages = ceil($total_items / $items_per_page);
        $current_page = $args['current_page'];
        $offset = ($current_page - 1) * $items_per_page;
        $paginated_data = array_slice($data, $offset, $items_per_page);

        // Get current URL without pagination parameters
        $current_url = remove_query_arg(['paged'], $_SERVER['REQUEST_URI']);
        $current_url = add_query_arg(['items_per_page' => $items_per_page], $current_url);

        ob_start();
        // Only include wrapper for non-AJAX calls
        if (!$ajax) {
            echo '<div id="waybill-table-parent">';
            echo '<div id="waybill-table-container">';
        }
    ?>
        <!-- Single pagination (top or bottom, not both) -->
        <div class="tablenav top flex flex-wrap items-center justify-between gap-4 mb-4">
            <!-- Items per page dropdown - LEFT SIDE -->
            <div class="flex items-center space-x-2">
                <label for="items-per-page" class="text-xs text-gray-600 whitespace-nowrap">Items per page:</label>
                <form method="get" id="items-per-page-form" style="display:inline;">
                    <?php
                    // Preserve other query params
                    foreach ($_GET as $key => $val) {
                        if ($key !== 'items_per_page') {
                            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($val) . '">';
                        }
                    }
                    ?>
                    <select id="items-per-page" name="items_per_page"
                        class="text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm py-1 pl-2 pr-8"
                        onchange="this.form.submit()">
                        <option value="5" <?php selected($items_per_page, 5); ?>>5</option>
                        <option value="10" <?php selected($items_per_page, 10); ?>>10</option>
                        <option value="20" <?php selected($items_per_page, 20); ?>>20</option>
                        <option value="50" <?php selected($items_per_page, 50); ?>>50</option>
                        <option value="100" <?php selected($items_per_page, 100); ?>>100</option>
                    </select>
                </form>

                <!-- Item count -->
                <span class="text-xs text-gray-600 whitespace-nowrap">
                    <?php echo number_format($total_items); ?> item<?php echo $total_items !== 1 ? 's' : ''; ?>
                </span>
            </div>

            <!-- Pagination links - RIGHT SIDE -->
            <div class="flex items-center space-x-1">
                <?php
                // Fixed pagination links
                $pagination_args = [
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $current_page,
                    'total' => $total_pages,
                    'prev_next' => true,
                    'prev_text' => '<span class="px-4 py-2 rounded border-gray-300 bg-white text-gray-700 hover:bg-gray-50">&laquo; Previous</span>',
                    'next_text' => '<span class="px-4 py-2 rounded border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Next &raquo;</span>',
                    'add_args' => ['items_per_page' => $items_per_page],
                    'type' => 'array'
                ];

                $pagination_links = paginate_links($pagination_args);

                if ($pagination_links) {
                    foreach ($pagination_links as $link) {
                        // Add styling to current page
                                // Ensure link is not null before using string functions
        $link = $link ?? '';
        if ($link && strpos($link, 'current') !== false) {
            $link = str_replace('page-numbers current', 'page-numbers current px-4 py-2 rounded border bg-blue-50 border-blue-500 text-blue-600', $link);
        } else if ($link) {
            $link = str_replace('page-numbers', 'page-numbers px-4 py-2 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50', $link);
        }
                        echo $link;
                    }
                }
                ?>
            </div>
        </div>


        <!-- Table -->
        <table id="ajaxtable" class="<?php echo esc_attr($args['table_class']); ?>">
            <thead class="bg-gray-50">
                <tr>
                    <?php foreach ($args['fields'] as $field): ?>
                        <?php if ($field !== 'customer_surname'): ?>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $field ?: ''))); ?>
                            </th>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if ($args['actions']): ?>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php foreach ($paginated_data as $item):


                    if (!is_object($item) && isset($item['waybill'])) {
                        $quotation = $item['waybill'];
                        $quotation = (object) $quotation;
                    } else {
                        $quotation = (object) $item;
                    }
                ?>
                    <tr class="bg-white" data-waybill-id="<?php echo esc_attr($quotation->id); ?>">
                        <?php foreach ($args['fields'] as $field): ?>
                            <?php if ($field !== 'customer_surname'): ?>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-xs">
                                        <?php if ($field === 'approval'): ?>
                                            <!-- waybillApprovalStatus($waybillno, $waybillid, $status) -->
                                            <?php if (current_user_can('administrator')): ?>
                                                <?= KIT_Commons::waybillApprovalStatus($quotation->waybill_no, $quotation->id, 'approved', $quotation->approval, 'select'); ?>
                                            <?php else:
                                                KIT_Commons::statusBadge($quotation->approval);
                                            endif; ?>
                                        <?php elseif ($field === 'waybill_no'): ?>
                                            <a href="<?php echo admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $quotation->id . '&waybill_atts=view_waybill'); ?>"
                                                target="_blank" style="color:inherit; text-decoration:none;">
                                                <span class="font-medium text-blue-600"
                                                    style="color:inherit;"><?php echo esc_html($quotation->$field); ?></span>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <?php echo date('M d', strtotime($quotation->created_at)); ?></div>
                                            </a>
                                        <?php elseif ($field === 'customer_name'): ?>
                                            <a href="?page=all-customer-waybills&amp;cust_id=<?php echo esc_attr($quotation->customer_id); ?>"
                                                target="_blank" style="color:inherit; text-decoration:none;">
                                                <span
                                                    class="font-medium text-blue-600"><?php echo esc_html($quotation->customer_name); ?></span>
                                                <div class="text-xs text-gray-500 truncate max-w-xs"
                                                    title="<?php echo esc_attr($quotation->customer_name); ?>">
                                                    <?php echo esc_html($quotation->customer_surname); ?>
                                                </div>
                                            </a>
                                        <?php else: ?>
                                            <?php
                                            if ($field === 'total') {
                                                echo '<span class="text-bold text-blue-600">' . KIT_Commons::currency() . '</span><span class="text-xs text-gray-500">' . KIT_Waybills::get_total_cost_of_waybill($quotation->waybill_no) . '</span>';
                                            } else {
                                                echo esc_html($quotation->$field ?? '');
                                            }
                                            ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($args['actions']): ?>
                            <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                <div class="flex space-x-2">
                                    <a href="<?php echo admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $quotation->id . '&waybill_atts=view_waybill'); ?>"
                                        class="text-blue-600 hover:text-blue-900" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <a href="<?php echo plugin_dir_url(__FILE__) . '../pdf-generator.php?waybill_no=' . $quotation->id . '&pdf_nonce=' . wp_create_nonce('pdf_nonce'); ?>"
                                        target="_blank" class="text-indigo-600 hover:text-indigo-900" title="Print" target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                    </a>
                                    <?php if (current_user_can('administrator') || current_user_can('manager')): ?>
                                        <?php if ($args['show_create_quotation']): ?>
                                            <?php echo KIT_Commons::renderButton('Quote', 'success', 'sm', [
                                                'title' => 'Create Quotation',
                                                'data-waybill-id' => $quotation->id,
                                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
                                                'iconPosition' => 'left',
                                                'gradient' => true
                                            ]); ?>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- gay -->
                                    <?php $current_user = wp_get_current_user(); ?>
                                    <?= KIT_Commons::deleteWaybillGlobal($quotation->id, $quotation->waybill_no, $quotation->delivery_id, $current_user->ID); ?>
                                </div>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        if (!$ajax) {
            echo '</div>';
            echo '</div>';
        }

        return ob_get_clean();
    }

    // ✅ Shortcode to display all waybills
    public static function kit_get_all_waybills()
    {
        // Get specific fields
        return self::get_waybills(['fields' => 'w.waybill_no, w.product_invoice_number, w.created_at, c.name, c.surname',]);
    }


    public static function get_waybill_details($filters = [])
    {
        global $wpdb;

        $shipDirectionTable = "{$wpdb->prefix}kit_shipping_directions";

        $where_clauses = [];
        $params = [];

        // Apply filters
        if (isset($filters['waybill_id'])) {
            $where_clauses[] = 'w.id = %d';
            $params[] = $filters['waybill_id'];
        }

        if (isset($filters['status'])) {
            $where_clauses[] = 'w.status = %s';
            $params[] = $filters['status'];
        }

        if (empty($where_clauses)) {
            return new WP_Error('no_filters', 'No filters were provided for waybill query.');
        }

        $where_sql = implode(' AND ', $where_clauses);

        $sql = "
        SELECT
            w.id AS waybill_id,
            w.*,
            dt.delivery_reference,
            dt.direction_id,
            dt.dispatch_date,
            dt.truck_number,
            dt.status as delivery_status,
            c.name as customer_name,
            c.surname as customer_surname,
            c.cell,
            c.email_address,
            c.address,
            c.company_name,
            c.country_id,
            q.waybill_id,
            q.subtotal,
            q.vat_amount,
            q.total,
            q.quotation_notes,
            q.status as quote_status,
            q.created_by,
            q.created_at,
            u.user_login AS approved_by_username,
            oc1.country_name AS origin_country,
            oc1.country_code AS origin_code,
            oc2.country_name AS destination_country, 
            oc2.country_code AS destination_code
        FROM {$wpdb->prefix}kit_waybills AS w
        LEFT JOIN {$wpdb->prefix}kit_deliveries AS dt ON w.delivery_id = dt.id
        LEFT JOIN {$wpdb->prefix}kit_customers AS c ON w.customer_id = c.cust_id
        LEFT JOIN {$wpdb->prefix}kit_quotations AS q ON w.id = q.waybill_id
        LEFT JOIN {$wpdb->prefix}users AS u ON u.id = w.approval_userid
        LEFT JOIN {$shipDirectionTable} sd ON dt.direction_id = sd.id 
        LEFT JOIN {$wpdb->prefix}kit_operating_countries oc1 ON sd.origin_country_id = oc1.id 
        LEFT JOIN {$wpdb->prefix}kit_operating_countries oc2 ON sd.destination_country_id = oc2.id 
        WHERE $where_sql
    ";

        return $wpdb->get_results($wpdb->prepare($sql, ...$params));
    }

    public static function warehouseWaybills()
    {
        return self::get_waybill_details([
            'status' => 'warehoused'
        ]);
    }

    public static function RenderTable($data, $args = [])
    {
        $defaults = [
            'fields' => ['waybill_no', 'delivery_id', 'customer_id', 'status'],
            'table_class' => 'min-w-full divide-y divide-gray-200',
            'responsive' => true,
            'actions' => true,
            'show_create_quotation' => false, // New option to show create quotation button
            'status_labels' => [
                'pending' => 'Pending',
                'shipped' => 'Shipped',
                'delivered' => 'Delivered',
                'cancelled' => 'Cancelled'
            ],
            'status_colors' => [
                'pending' => 'bg-yellow-100 text-yellow-800',
                'shipped' => 'bg-blue-100 text-blue-800',
                'delivered' => 'bg-green-100 text-green-800',
                'cancelled' => 'bg-red-100 text-red-800'
            ]
        ];

        $args = wp_parse_args($args, $defaults);

        ob_start();
        ?>
        <div class="overflow-x-auto shadow-sm rounded-lg border border-gray-200">
            <table class="<?php echo esc_attr($args['table_class']); ?>">
                <thead class="bg-gray-50">
                    <tr>
                        <?php foreach ($args['fields'] as $field): ?>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $field ?: ''))); ?>
                            </th>
                        <?php endforeach; ?>
                        <?php if ($args['actions']): ?>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($data as $item):

                        $quotation = (object)$item;

                    ?>
                        <tr class="hover:bg-gray-50" data-waybill-id="<?php echo esc_attr($quotation->id); ?>">
                            <?php foreach ($args['fields'] as $field): ?>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="text-xs">
                                        <?php if ($field === 'status'): ?>
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo esc_attr($args['status_colors'][strtolower($quotation->$field)] ?? 'bg-gray-100 text-gray-800'); ?>">
                                                <?php echo esc_html($args['status_labels'][strtolower($quotation->$field)] ?? ucfirst($quotation->$field)); ?>
                                            </span>
                                        <?php elseif ($field === 'waybill_no'): ?>
                                            <span class="font-medium text-blue-600"><?php echo esc_html($quotation->$field); ?></span>
                                            <div class="text-xs text-gray-500 mt-1">
                                                <?php echo date('M d', strtotime($quotation->created_at)); ?></div>
                                        <?php elseif ($field === 'customer_id'): ?>
                                            <span class="font-medium"><?php echo esc_html($quotation->customer_id); ?></span>
                                            <div class="text-xs text-gray-500 truncate max-w-xs"
                                                title="<?php echo esc_attr($quotation->customer_name); ?>">
                                                <?php echo esc_html($quotation->customer_name) . ' ' . esc_html($quotation->surname); ?>
                                            </div>
                                        <?php else: ?>
                                            <?php echo esc_html($quotation->$field); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                            <?php if ($args['actions']): ?>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500">
                                    <div class="flex space-x-2">
                                        <?php if (!$args['quoted_already']): ?>
                                            <a href="<?php echo admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $quotation->id . '&waybill_atts=view_waybill'); ?>"
                                                class="text-blue-600 hover:text-blue-900" title="View">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            </a>

                                        <?php else: ?>
                                            <a href="<?php echo plugin_dir_url(__FILE__) . '../pdf-generator.php?waybill_no=' . $quotation->id . '&pdf_nonce=' . wp_create_nonce('pdf_nonce'); ?>"
                                                target="_blank"
                                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                                </svg>
                                                Download PASDDF
                                            </a>
                                        <?php endif; ?>
                                        <!--  <a href="<?php echo admin_url('admin.php?page=08600-Waybill-print&waybill_id=' . $quotation->id); ?>"
                                            class="text-indigo-600 hover:text-indigo-900" title="Print" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                            </svg>
                                        </a> -->
                                        <?php if ($args['show_create_quotation']): ?>
                                            <?php echo KIT_Commons::renderButton('Quote', 'success', 'sm', [
                                                'title' => 'Create Quotation',
                                                'data-waybill-id' => $quotation->id,
                                                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />',
                                                'iconPosition' => 'left',
                                                'gradient' => true
                                            ]); ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <script>
            jQuery('.create-quotation').on('click', function(e) {
                e.preventDefault();
                const waybillId = jQuery(this).data('waybill-id');
                const $button = jQuery(this);
                const $row = $button.closest('tr');

                if (!confirm('Are you sure you want to create a quotation for this waybill?')) {
                    return;
                }

                $button.html('<span class="spinner is-active" style="margin: 0;"></span>');
                $button.prop('disabled', true);

                jQuery.ajax({
                    url: myPluginAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'create_quotation_from_waybill',
                        waybill_id: waybillId,
                        _ajax_nonce: myPluginAjax.nonces.kit_waybill_nonce // Now using the correct nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            // Redirect to the new quotation if URL provided
                            if (response.data.redirect_url) {
                                window.location.href = response.data.redirect_url;
                            } else {
                                window.location.reload();
                            }
                        } else {
                            alert('Error: ' + (response.data ? .message || 'Failed to create quotation'));
                        }
                    },
                    error: function(xhr, status, error) {
                        alert('Error: ' + error);
                    },
                    complete: function() {
                        // Only reset if not redirecting
                        if (!response || !response.success) {
                            $button.html(
                                '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>'
                            );
                            $button.prop('disabled', false);
                        }
                    }
                });
            });
        </script>
    <?php

        return ob_get_clean();
    }

    public static function bonaWaybill($waybill_id)
    {
        global $wpdb;

        $shipDirectionTable = $wpdb->prefix . 'kit_shipping_directions';
        // 1. Get the waybill details, delivery, customer, and quotation info
        $waybill_data = $wpdb->get_row(
            $wpdb->prepare("
                SELECT
                    w.id AS waybill_id,
                    w.*,
                    dt.delivery_reference,
                    dt.direction_id,
                    dt.dispatch_date,
                    dt.truck_number,
                    dt.status as delivery_status,
                    c.name as customer_name,
                    c.surname as customer_surname,
                    c.cell,
                    c.email_address,
                    c.address,
                    c.company_name,
                    c.country_id,
                    q.waybill_id,
                    q.subtotal,
                    q.vat_amount,
                    q.total,
                    q.quotation_notes,
                    q.status as quote_status,
                    q.created_by,
                    q.created_at,
                    u.user_login AS approved_by_username,
                    oc1.country_name AS origin_country,
                    oc1.country_code AS origin_code,
                    oc2.country_name AS destination_country, 
                    oc2.country_code AS destination_code
                FROM {$wpdb->prefix}kit_waybills AS w
                LEFT JOIN {$wpdb->prefix}kit_deliveries AS dt ON w.delivery_id = dt.id
                LEFT JOIN {$wpdb->prefix}kit_customers AS c ON w.customer_id = c.cust_id
                LEFT JOIN {$wpdb->prefix}kit_quotations AS q ON w.id = q.waybill_id
                LEFT JOIN {$wpdb->prefix}users AS u ON u.id = w.approval_userid
                LEFT JOIN $shipDirectionTable sd ON dt.direction_id = sd.id 
                LEFT JOIN {$wpdb->prefix}kit_operating_countries oc1 ON sd.origin_country_id = oc1.id 
                LEFT JOIN {$wpdb->prefix}kit_operating_countries oc2 ON sd.destination_country_id = oc2.id 
                WHERE
                    w.id = %s LIMIT 1", $waybill_id),
            ARRAY_A
        );

        if (!$waybill_data) {
            return null; // Waybill not found
        }

        // Ensure all string fields have default values to prevent null deprecation warnings
        // Convert object to array first, then handle null values
        $waybill_data = (array) $waybill_data;
        $waybill_data = array_map(function ($value) {
            return $value === null ? '' : $value;
        }, $waybill_data);

        $waybill_no = $waybill_data['waybill_no'];
        // 2. Get the waybill items
        $waybill_items = $wpdb->get_results(
            $wpdb->prepare("
                            SELECT *
                            FROM {$wpdb->prefix}kit_waybill_items
                            WHERE waybillno = %s
                        ", $waybill_no),
            ARRAY_A
        );
        // Attach items to the result and ensure no null values
        if ($waybill_items) {
            $waybill_items = array_map(function ($item) {
                return array_map(function ($value) {
                    return $value === null ? '' : $value;
                }, $item);
            }, $waybill_items);
        }
        $waybill_data['items'] = $waybill_items;

        // Unserialize miscellaneous items before returning
        if (!empty($waybill_data['miscellaneous'])) {
            $misc_data = maybe_unserialize($waybill_data['miscellaneous']);
            // Ensure nested arrays have safe defaults
            if (is_array($misc_data)) {
                if (isset($misc_data['others']) && is_array($misc_data['others'])) {
                    $misc_data['others'] = array_map(function($value) {
                        return $value === null ? '' : $value;
                    }, $misc_data['others']);
                }
                if (isset($misc_data['misc_items']) && is_array($misc_data['misc_items'])) {
                    $misc_data['misc_items'] = array_map(function($item) {
                        if (is_array($item)) {
                            return array_map(function($value) {
                                return $value === null ? '' : $value;
                            }, $item);
                        }
                        return $item;
                    }, $misc_data['misc_items']);
                }
                // Ensure misc_total has a safe default
                if (!isset($misc_data['misc_total']) || $misc_data['misc_total'] === null) {
                    $misc_data['misc_total'] = 0.0;
                }
            }
            $waybill_data['miscellaneous'] = $misc_data;
        }


        return $waybill_data;
    }


    public static function changeWarehouses($waybill_id)
    {
        global $wpdb;
        //find the waybill that 

    }

    public static function waybillView()
    {
        $waybill_id = isset($_GET['waybill_id']) ? intval($_GET['waybill_id']) : 0;
        $waybill = KIT_Waybills::bonaWaybill($waybill_id);

        // Initialize variables with safe defaults
        $waybillFromStats = [];
        $waybillToStats = [];
        $mass_rate = '';
        $checkManny = '';
        $prefferedCharge = '';

        // Only process waybill data if it exists
        if ($waybill && is_array($waybill)) {
            // Get transit stats only if direction_id exists
            if (isset($waybill['direction_id']) && !empty($waybill['direction_id'])) {
                $waybillFromStats = KIT_Deliveries::getWaybillTransitStats($waybill['direction_id']);
                $waybillToStats = KIT_Deliveries::getWaybillTransitStats($waybill['direction_id'], 'destination');
            }

            // Determine preferred charge
            if (isset($waybill['mass_charge']) && isset($waybill['volume_charge'])) {
                if ($waybill['mass_charge'] > $waybill['volume_charge']) {
                    $prefferedCharge = 'mass';
                } else {
                    $prefferedCharge = 'volume';
                }
            }

            // Process miscellaneous data safely
            if (isset($waybill['miscellaneous']) && is_array($waybill['miscellaneous']) && isset($waybill['miscellaneous']['others'])) {
                $mass_rate = ($waybill['miscellaneous']['others']['mass_rate'] ?? 1);
                $checkManny = (isset($waybill['miscellaneous']['others']['manny']) && $waybill['miscellaneous']['others']['manny']) ? 'checked' : '';
            }
        }

        $is_editing = isset($_GET['edit']) && $_GET['edit'] === 'true';
        // Normalize waybill to array for downstream templates
        if (isset($waybill) && is_object($waybill)) {
            $waybill = (array) $waybill;
        }
    ?>
        <div class="wrap">
            <?php
            if (!empty($waybill) && is_array($waybill)) {
                if (!$is_editing) { ?>
                    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/viewWaybill.php'); ?>
                <?php
                } else {
                ?>
                    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/editWaybill.php'); ?>
            <?php
                }
            } else {
                echo '<div class="notice notice-error"><p>Waybill not found or invalid data.</p></div>';
            }
            ?>
        </div>
        <?php
    }

    public static function plugin_Waybill_view_page()
    {
        global $wpdb;
        $table_name   = $wpdb->prefix . 'kit_waybills';
        $waybill_id   = isset($_GET['waybill_id']) ? intval($_GET['waybill_id']) : 0;
        $is_view_mode = isset($_GET['waybill_atts']) && $_GET['waybill_atts'] === 'view_waybill';
        $set_cust_id = isset($_GET['cust_id']) ? intval($_GET['cust_id']) : 0;

        if (! empty($set_cust_id)):
            $breadlinks = [
                ["name" => "Home", "slug" => "?page=home"],
                ["name" => "Customer", "slug" => "?page=all-customer-waybills&cust_id=" . ($set_cust_id) ?? ""],
                ["name" => "Waybill", "slug" => "?page="],
            ];
        elseif (empty($set_cust_id)):
            $breadlinks = [
                ["name" => "Home", "slug" => "?page=home"],
                ["name" => "Waybills", "slug" => "?page=08600-Waybill-list"],
                ["name" => "New Waybill", "slug" => "?page="],
            ];
        endif;
        echo '<div class="wrap">';
        $breadlinks_json = urlencode(json_encode($breadlinks));
        echo do_shortcode('[showheader title="View Waybill" desc=""]');
        $current_user = wp_get_current_user();
        $roles = (array) $current_user->roles;
        echo '<div class="mt-6">';

        if ($waybill_id) {
            $waybill = $wpdb->get_row($wpdb->prepare(
                "SELECT w.id AS waybill_id, w.*, u.user_login AS approved_by_username
                             FROM $table_name AS w
                             LEFT JOIN {$wpdb->users} AS u ON w.approval_userid = u.ID
                             WHERE w.id = %d",
                $waybill_id
            ), ARRAY_A);

            // Convert null values to empty strings to prevent deprecation warnings
            if ($waybill) {
                $waybill = array_map(function($value) {
                    return $value === null ? '' : $value;
                }, $waybill);
            }


            if ($is_view_mode) {
                // View mode - Modern design
                echo '<div id="waybill-display" class="bg-white rounded-lg shadow-md p-6 max-w-7xl mx-auto">';

                // Header section
                echo '<div class="flex justify-between items-center mb-6 border-b pb-4">';
                echo '<h2 class="text-2xl font-bold text-gray-800">Waybil33l #' . esc_html($waybill['waybill_no']) . '</h2>';
                echo '<div class="flex space-x-3">';
                // If already approved, show the approver
                if (! empty($waybill['approved_by_username'])) {
                    echo '<div class="flex flex-col">';
                    echo '<p class="text-gray-600">Approved by:</p>';
                    echo '<div class="bg-slate-300 hover:bg-slate-700 rounded block text-gray text-center align-middle px-6 py-3">' . esc_html($waybill['approved_by_username']) . '</div>';
                    echo '</div>';
                }
                // If not approved, and user is Admin or Manager, show button
                elseif (in_array('administrator', $roles) || in_array('manager', $roles)) {
                    echo '<form method="POST" id="approvalBtn" action="' . esc_url(admin_url('admin-post.php')) . '">';
                    echo '<input type="hidden" name="action" value="approve_waybill">';
                    echo '<input type="hidden" name="waybill_id" value="' . esc_attr($waybill['id']) . '">';
                    echo '<input type="hidden" name="delivery_id" value="' . esc_attr($waybill['delivery_id']) . '">';
                    echo '<input type="hidden" name="user_id" value="' . esc_attr($current_user->ID) . '">';
                    echo KIT_Commons::renderButton('Approve Now', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]);
                    echo '</form>';
                }
                // Else show 'Not Approved'
                else {
                    echo 'Not Approved';
                }
                echo KIT_Commons::renderButton('Edit', 'primary', 'md', [
                    'id' => 'editWaybillBtn',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>',
                    'iconPosition' => 'left',
                    'gradient' => true
                ]);
                echo '<a href="' . admin_url('admin.php?page=08600-Waybill') . '" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">Back to List</a>';
                echo '</div></div>';

                // Main content sections
                echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';

                // Basic Information Section
                echo '<div class="bg-gray-50 p-4 rounded-lg">';
                echo '<h3 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Basic Information</h3>';
                echo '<div class="space-y-3">';
                echo self::render_detail_row('Waybill Number', $waybill['waybill_no']);
                echo self::render_detail_row('Invoice Number', $waybill['product_invoice_number']);
                echo self::render_detail_row('Customer ID', $waybill['customer_id']);
                echo '</div>';
                echo '</div>';

                // Dimensions Section
                echo '<div class="bg-gray-50 p-4 rounded-lg">';
                echo '<h3 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Dimensions</h3>';
                echo '<div class="grid grid-cols-2 gap-3">';
                echo self::render_detail_row('Length (cm)', $waybill['item_length']);
                echo self::render_detail_row('Width (cm)', $waybill['item_width']);
                echo self::render_detail_row('Height (cm)', $waybill['item_height']);
                //echo self::render_detail_row('Total Volume', $waybill['total_volume']);
                echo self::render_detail_row('Total Mass (kg)', $waybill['total_mass_kg']);
                echo '</div>';
                echo '</div>';

                // Charges Section
                echo '<div class="bg-gray-50 p-4 rounded-lg">';
                echo '<h3 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Charges</h3>';
                echo '<div class="grid grid-cols-1 gap-3">';
                echo self::render_detail_row('Charge Basis', $waybill['charge_basis']);
                echo (isset($waybill['mass_rate'])) ? self::render_detail_row('Mass Rate', KIT_Commons::currency() . number_format($waybill['mass_rate'], 2)) : '';
                echo (isset($waybill['mass_charge'])) ? self::render_detail_row('Mass Charge', KIT_Commons::currency() . number_format($waybill['mass_charge'], 2)) : '';
                echo (isset($waybill['volume_charge'])) ? self::render_detail_row('Volume Charge', KIT_Commons::currency() . number_format($waybill['volume_charge'], 2)) : '';
                echo '</div>';
                echo '</div>';

                // Miscellaneous Section
                echo '<div class="bg-gray-50 p-4 rounded-lg">';
                echo '<h3 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Miscellaneous Charges</h3>';
                echo '<div class="grid grid-cols-2 gap-3">';
                //$waybill['miscellaneous'] unserialize and display name + Price
                $miscData = null;
                if (!empty($waybill['miscellaneous'])) {
                    $miscData = maybe_unserialize($waybill['miscellaneous']);
                }

                if ($miscData && isset($miscData['misc_item'], $miscData['misc_price'])) {
                    echo '<ul class="list-disc pl-5">';
                    foreach ($miscData['misc_item'] as $index => $item) {
                        $price = $miscData['misc_price'][$index] ?? 0;
                        echo '<li>' . esc_html($item) . ' - R' . esc_html($price) . '</li>';
                    }
                    echo '</ul>';
                    echo '<p class="mt-2 font-semibold">Total: R' . esc_html($miscData['misc_total']) . '</p>';
                } else {
                    echo '<p>No miscellaneous items found.</p>';
                }
                echo '</div>';
                echo '</div>';

                echo '</div>'; // Close grid
                echo '</div>'; // Close waybill-display

                // Edit form (outside display)
                echo '<div id="waybill-edit-form" class="hidden mt-6 max-w-7xl mx-auto">';
                include plugin_dir_path(__FILE__) . 'waybill-form.php';
                echo '<div class="mt-4 flex justify-end space-x-3">';
                echo '<a
                            href="' . admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $waybill['id'] . '&waybill_atts=view_waybill') . '"
                            class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">Cancel</a>';
                echo '</div>';
                echo '</div>';

                // JavaScript
                echo '
                        <script>
                            document.addEventListener("DOMContentLoaded", function () {
                                document.getElementById("editWaybillBtn").addEventListener("click", function () {
                                    document.getElementById("waybill-display").style.display = "none";
                                    const form = document.getElementById("waybill-edit-form");
                                    form.classList.remove("hidden");
                                    form.style.display = "block";
                                });
                            });
                        </script>';
            } else {
                // Directly in edit mode
                echo '<div class="bg-white rounded-lg shadow-md p-6 max-w-7xl mx-auto">';
                include plugin_dir_path(__FILE__) . 'waybill-form.php';
                echo '<div class="mt-4 flex justify-end space-x-3">';
                echo '<a
                        href="' . admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $waybill['id'] . '&waybill_atts=view_waybill') . '"
                        class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">Cancel</a>';
                echo '</div>';
                echo '</div>';
            }
        } else {
            echo '<p>' . __('Invalid waybill ID.', 'kit') . '</p>';
        }
        echo '</div>'; // Close wrapper
        echo '</div>'; // Close wrapper
    }

    public static function render_detail_row($label, $value)
    {
        return '
                        <div class="flex gap-4 px-4 py-2">
                            <span class="text-black-600 font-bold">' . esc_html($label) . '</span>
                            <span class="text-gray-800">' . (! empty($value) ? esc_html($value) : '—') . '</span>
                        </div>';
    }

    public static function handle_delete_waybill()
    {
        global $wpdb;

        $is_ajax = defined('DOING_AJAX') && DOING_AJAX;

        if (!isset($_POST['waybill_no'])) {
            if ($is_ajax) {
                wp_send_json_error(['message' => 'Missing waybill_no']);
            } else {
                wp_die('Invalid request: waybill_no not provided.');
            }
        }

        $waybill_no = (int) $_POST['waybill_no'];

        if (!check_ajax_referer('delete_waybill_nonce', '_ajax_nonce', false)) {
            if ($is_ajax) {
                wp_send_json_error(['message' => 'Invalid nonce'], 403);
            } else {
                wp_die('Invalid nonce');
            }
        }

        $table_name = $wpdb->prefix . 'kit_waybills';

        $result = $wpdb->delete($table_name, ['waybill_no' => $waybill_no]);

        if ($is_ajax) {
            if ($result !== false) {
                wp_send_json_success(['message' => 'Waybill deleted successfully']);
            } else {
                wp_send_json_error(['message' => 'Failed to delete waybill']);
            }
        } else {
            if ($result !== false) {
                wp_redirect(add_query_arg('deleted', '1', wp_get_referer()));
            } else {
                wp_redirect(add_query_arg('error', '1', wp_get_referer()));
            }
            exit;
        }
    }

    public static function get_waybill_by_id($waybill_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_waybills';
        return $wpdb->get_row($wpdb->prepare("
        SELECT * FROM $table_name as w
        LEFT JOIN {$wpdb->prefix}kit_customers as c ON w.customer_id = c.id
        LEFT JOIN {$wpdb->prefix}kit_deliveries as d ON w.delivery_id = d.id
        LEFT JOIN {$wpdb->users} as u ON w.created_by = u.ID
        -- Quotations table join removed
        WHERE w.id = %d", $waybill_id));
    }

    public static function findWaybillNo($waybill_no)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_waybills';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE waybill_no = %s", $waybill_no));
    }

    public static function getFullWaybillWithItems($waybill_no)
    {
        global $wpdb;

        $prefix = $wpdb->prefix;

        // TABLE NAMES
        $waybills_table   = $prefix . 'kit_waybills';
        $customers_table  = $prefix . 'kit_customers';
        $deliveries_table = $prefix . 'kit_deliveries';
        $directions_table = $prefix . 'kit_shipping_directions';
        $countries_table  = $prefix . 'kit_operating_countries';
        $cities_table  = $prefix . 'kit_operating_cities';
        $items_table      = $prefix . 'kit_waybill_items';

        // PHASE 1: Waybill + related joins
        $waybill_sql = $wpdb->prepare(" SELECT 
                b.id AS waybill_id,
                c.id AS customer_id,
                d.id AS delivery_id,
                dir.id AS direction_id,
                b.direction_id,
                b.customer_id,
                b.approval,
                b.approval_userid,
                b.waybill_no,
                b.product_invoice_number,
                b.product_invoice_amount,
                b.waybill_items_total,
                b.item_length,
                b.item_width,
                b.item_height,
                b.total_mass_kg,
                b.total_volume,
                b.mass_charge,
                b.volume_charge,
                b.charge_basis,
                b.vat_include,
                b.miscellaneous,
                b.include_sad500,
                b.include_sadc,
                b.created_at,
                b.tracking_number,
                c.name AS customer_name,
                c.surname AS customer_surname,
                c.address,
                c.email_address,
                c.city_id,
                city.city_name AS customer_city,
                c.company_name,
                c.cell AS customer_cell,
                d.delivery_reference,
                d.direction_id,
                d.dispatch_date,
                d.truck_number,
                d.status,
                d.status AS delivery_status,
                dir.description AS route_description,
                origin.country_name AS origin_country,
                dest.country_name AS destination_country
                FROM $waybills_table b
                LEFT JOIN $customers_table c ON b.customer_id = c.cust_id
                LEFT JOIN $deliveries_table d ON b.delivery_id = d.id
                LEFT JOIN $directions_table dir ON b.direction_id = dir.id
                LEFT JOIN $countries_table origin ON dir.origin_country_id = origin.id
                LEFT JOIN $countries_table dest ON dir.destination_country_id = dest.id
                LEFT JOIN $cities_table city ON c.city_id = city.id
                WHERE b.waybill_no = %d
                LIMIT 1", $waybill_no);

        $waybill = $wpdb->get_row($waybill_sql, ARRAY_A);

        if (!$waybill) {
            return null;
        }

        // PHASE 2: Waybill Items
        $items_sql = $wpdb->prepare("SELECT * FROM $items_table WHERE waybillno = %d", $waybill_no);

        $items = $wpdb->get_results($items_sql, ARRAY_A);


        return (object)[
            'waybill' => $waybill,
            'items'   => $items,
        ];
    }

    public static function get_waybill_items_total($data)
    {
        $finalTotal = 0;

        // Loop through each item (assuming all arrays are aligned by index)
        for ($i = 0; $i < count($data['misc_item']); $i++) {
            $price = floatval($data['misc_price'][$i]);
            $qty = intval($data['misc_quantity'][$i]);
            $itemTotal = $price * $qty;
            $finalTotal += $itemTotal;
        }

        return $finalTotal;
    }


    public static function vatValidate($vat, $sad, $sad500)
    {
        $validate = [
            'vat' => $vat,
            'sad' => $sad,
            'sad500' => $sad500
        ];

        // If vat includes (assumed when $vat == 1), then SAD and SAD500 must be 0
        if ($vat == 1) {
            if ($sad != 0) {
                $validate['sad'] = false;
            }
            if ($sad500 != 0) {
                $validate['sad500'] = false;
            }
        }

        if ($sad == 1) {
            $validate['vat'] = false;
        }
        return $validate;
    }

    /**
     * Track warehouse actions for waybills
     * 
     * @param int $waybill_no Waybill number
     * @param int $waybill_id Waybill ID
     * @param int $customer_id Customer ID
     * @param string $action Action type (warehoused, assigned, removed)
     * @param string|null $previous_status Previous status
     * @param string $new_status New status
     * @param int|null $assigned_delivery_id Delivery ID if assigned
     * @param string $notes Additional notes
     */
    public static function track_warehouse_action($waybill_no, $waybill_id, $customer_id, $action, $previous_status = null, $new_status = null, $assigned_delivery_id = null, $notes = '')
    {
        global $wpdb;

        $tracking_table = $wpdb->prefix . 'kit_warehouse_tracking';

        $tracking_data = [
            'waybill_no' => $waybill_no,
            'waybill_id' => $waybill_id,
            'customer_id' => $customer_id,
            'action' => $action,
            'previous_status' => $previous_status,
            'new_status' => $new_status,
            'assigned_delivery_id' => $assigned_delivery_id,
            'notes' => $notes,
            'created_by' => get_current_user_id()
        ];

        $wpdb->insert($tracking_table, $tracking_data);
    }

    public static function deleteWaybillItems($waybill_no)
    {
        global $wpdb;
        $items_table = $wpdb->prefix . 'kit_waybill_items';
        if ($waybill_no != null) {
            // ✅ Delete previous waybill items
            $deleted = $wpdb->delete($items_table, ['waybillno' => $waybill_no]);

            if ($deleted) {
                return $deleted;
            }
        }
    }

    /**
     * Verify and optionally correct a waybill's total by recalculating it with the
     * bulletproof calculator and comparing with the DB value.
     *
     * Args:
     * - waybill_id?: int
     * - waybill_no?: int
     * - update_if_mismatch?: bool (default false)
     * - tolerance?: float (default 0.01)
     *
     * Returns array with:
     * - waybill_id, waybill_no
     * - db_total, calc_total, difference, matches, updated
     * - breakdown (calculator arrays)
     */
    public static function doubleCalcWaybillTotal($args = [])
    {
        global $wpdb;

        $update_if_mismatch = !empty($args['update_if_mismatch']);
        $tolerance = isset($args['tolerance']) ? floatval($args['tolerance']) : 0.01;

        $waybill_id = isset($args['waybill_id']) ? intval($args['waybill_id']) : 0;
        $waybill_no = isset($args['waybill_no']) ? intval($args['waybill_no']) : 0;

        // Resolve using whichever identifier we have
        if ($waybill_no <= 0 && $waybill_id > 0) {
            $waybill_no = (int) $wpdb->get_var($wpdb->prepare("SELECT waybill_no FROM {$wpdb->prefix}kit_waybills WHERE id = %d", $waybill_id));
        }

        if ($waybill_no <= 0) {
            return [ 'error' => 'Waybill identifier not provided' ];
        }

        $full = self::getFullWaybillWithItems($waybill_no);
        if (!$full || empty($full->waybill)) {
            return [ 'error' => 'Waybill not found', 'waybill_no' => $waybill_no ];
        }

        $wb = $full->waybill;
        $items = is_array($full->items) ? $full->items : [];

        // Compute items total defensively from items if present
        $computed_items_total = 0.0;
        foreach ($items as $it) {
            $qty = isset($it['quantity']) ? floatval($it['quantity']) : 0.0;
            $price = isset($it['unit_price']) ? floatval($it['unit_price']) : 0.0;
            $line_total = $price * $qty;
            // Prefer explicit total_price if present
            if (isset($it['total_price']) && is_numeric($it['total_price'])) {
                $line_total = floatval($it['total_price']);
            }
            $computed_items_total += $line_total;
        }

        // Parse miscellaneous safely
        $misc_total = 0.0;
        if (!empty($wb['miscellaneous'])) {
            $misc = maybe_unserialize($wb['miscellaneous']);
            if (is_array($misc) && isset($misc['misc_total'])) {
                $misc_total = floatval($misc['misc_total']);
            }
        }

        // Calculator params
        require_once plugin_dir_path(__FILE__) . 'bulletproof-calculator.php';
        $params = [
            'mass_charge' => floatval($wb['mass_charge'] ?? 0),
            'volume_charge' => floatval($wb['volume_charge'] ?? 0),
            'misc_total' => $misc_total,
            'waybill_items_total' => $computed_items_total > 0 ? $computed_items_total : floatval($wb['waybill_items_total'] ?? 0),
            'charge_basis' => $wb['charge_basis'] ?? 'auto',
            'include_sad500' => intval($wb['include_sad500'] ?? 0) === 1,
            'include_sadc' => intval($wb['include_sadc'] ?? 0) === 1,
            'include_vat' => intval($wb['vat_include'] ?? 0) === 1,
        ];

        $breakdown = KIT_Bulletproof_Calculator::calculate_waybill_total($params);
        $calc_total = floatval($breakdown['totals']['final_total'] ?? 0);
        $db_total = floatval($wb['product_invoice_amount'] ?? 0);
        $difference = round($db_total - $calc_total, 2);
        $matches = abs($difference) < $tolerance;

        $updated = false;
        if (!$matches && $update_if_mismatch) {
            // Update DB with authoritative calculated total
            $wpdb->update(
                $wpdb->prefix . 'kit_waybills',
                [
                    'product_invoice_amount' => $calc_total,
                    'waybill_items_total' => ($computed_items_total > 0 ? $computed_items_total : $wb['waybill_items_total'])
                ],
                [ 'waybill_no' => $waybill_no ],
                [ '%f', '%f' ],
                [ '%d' ]
            );
            $updated = ($wpdb->rows_affected > 0);
        }

        return [
            'waybill_id' => intval($wb['waybill_id'] ?? 0),
            'waybill_no' => $waybill_no,
            'db_total' => $db_total,
            'calc_total' => $calc_total,
            'difference' => $difference,
            'matches' => $matches,
            'updated' => $updated,
            'breakdown' => $breakdown,
        ];
    }
    public static function updateWaybillItems($waybill_items, $waybill_no = null)
    {
        global $wpdb;
        $items_table = $wpdb->prefix . 'kit_waybill_items';
        $total = 0;

        // 🔒 CRITICAL FIX: Prevent NULL waybillno database errors
        if ($waybill_no === null || empty($waybill_no)) {
            error_log("updateWaybillItems: waybill_no is null or empty - skipping item insertion");
            return 0;
        }

        if ($waybill_no != null) {
            // ✅ Delete previous waybill items
            $wpdb->delete($items_table, ['waybillno' => $waybill_no]);
        }

        foreach ($waybill_items as $item) {
            // Skip if required fields are missing
            if (empty($item['item_name']) || !isset($item['quantity']) || !isset($item['unit_price'])) {
                continue;
            }
            
            $quantity = intval($item['quantity']);
            $unit_price = floatval($item['unit_price']);
            $subtotal = $quantity * $unit_price;
            $total += $subtotal;
            
            $wpdb->insert($items_table, [
                'waybillno'   => $waybill_no,
                'item_name'   => sanitize_text_field($item['item_name']),
                'quantity'    => $quantity,
                'unit_price'  => $unit_price,
                'unit_mass'   => floatval($item['unit_mass'] ?? 0),
                'unit_volume' => floatval($item['unit_volume'] ?? 0),
                'total_price' => $subtotal,
                'created_at'  => current_time('mysql'),
            ]);
        }
        return $total;
    }

    public static function update_waybill_action()
    {

        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update_waybill_nonce')) {
            wp_die('Security check failed.');
        }

        // Allow Data Capturers/Managers via plugin cap (avoid WP core caps)
        if (!current_user_can('kit_update_data')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        global $wpdb;

        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $waybill_no     = intval($_POST['waybill_no'] ?? 0);
        $posted_waybill_id = intval($_POST['waybill_id'] ?? 0);

        // 🔄 Load current waybill (ensures waybill_id is correct from the DB)
        $existing = $waybill_no ? self::getFullWaybillWithItems($waybill_no) : null;


        if (!$existing && $posted_waybill_id) {
            // Fallback: resolve waybill number by ID
            $resolved_no = (int) $wpdb->get_var($wpdb->prepare("SELECT waybill_no FROM {$waybills_table} WHERE id = %d", $posted_waybill_id));
            if ($resolved_no) {
                $existing = self::getFullWaybillWithItems($resolved_no);
                if ($existing) {
                    $waybill_no = $resolved_no;
                }
            }
        }

        if (!$existing) {
            wp_die('Waybill not found.');
        }

        $waybill_id = $existing->waybill['waybill_id'];

        $final_misc_data = KIT_Waybills::prepareMiscCharges($_POST);
        $misc_serialized = serialize($final_misc_data);

        $addOptions = self::vatValidate(isset($_POST['vat_include']), isset($_POST['include_sadc']), isset($_POST['include_sad500']));

        if (empty($_POST['vat_include'])) {
            //Do not add 10% to the t
        }

        // Use null coalescing for consistent pattern
        $direction_id = $_POST['direction_id'] ?? $existing->waybill['direction_id'];
        $delivery_id = $_POST['delivery_id'] ?? $existing->waybill['delivery_id'];
        $approval = $_POST['approval'] ?? $existing->waybill['approval'];
        $approval_userid = $_POST['approval_userid'] ?? $existing->waybill['approval_userid'];
        $product_invoice_amount = $_POST['product_invoice_amount'] ?? $existing->waybill['product_invoice_amount'];
        $item_length = $_POST['item_length'] ?? $existing->waybill['item_length'];
        $item_width = $_POST['item_width'] ?? $existing->waybill['item_width'];
        $item_height = $_POST['item_height'] ?? $existing->waybill['item_height'];
        $total_mass_kg = $_POST['total_mass_kg'] ?? $existing->waybill['total_mass_kg'];
        $total_volume = $_POST['total_volume'] ?? $existing->waybill['total_volume'];
        $mass_charge = $_POST['mass_charge'] ?? $existing->waybill['mass_charge'];
        $volume_charge = $_POST['volume_charge'] ?? $existing->waybill['volume_charge'];
        $charge_basis = $_POST['charge_basis'] ?? $existing->waybill['charge_basis'];
        $vat_include = $addOptions['vat'] ?? $existing->waybill['vat_include'];
        // Warehouse status is now managed by warehouse_items table
        $include_sad500 = $_POST['include_sad500'] ?? 0;
        $include_sadc = $_POST['include_sadc'] ?? 0;

        //Not waybill
        $dispatch_date = $_POST['dispatch_date'] ?? $existing->waybill['dispatch_date'];
        $truck_number = $_POST['truck_number'] ?? $existing->waybill['truck_number'];

        // Fix misc total calculation logic
        $misc_total = $final_misc_data['misc_total'];
        if (isset($_POST['misc']) && is_array($_POST['misc'])) {
            $misc_total = self::get_waybill_items_total($_POST['misc']);
        }

        // Always update waybill items - this will delete existing items and insert new ones
        $waybillItemsTotal = self::updateWaybillItems($_POST['custom_items'] ?? [], $waybill_no);

        $vat_include = $_POST['vat_include'] ?? 0;

        // Use bulletproof calculator for edit flow
        require_once plugin_dir_path(__FILE__) . 'bulletproof-calculator.php';
        
        $calculation_params = [
            'mass_charge' => $mass_charge,
            'volume_charge' => $volume_charge,
            'misc_total' => $misc_total,
            'waybill_items_total' => $waybillItemsTotal,
            'charge_basis' => $charge_basis,
            'include_sad500' => $include_sad500,
            'include_sadc' => $include_sadc,
            'include_vat' => $vat_include
        ];
        
        $calculation_breakdown = KIT_Bulletproof_Calculator::calculate_waybill_total($calculation_params);
        $waybillTotal = $calculation_breakdown['totals']['final_total'];

        //$_POST['vat_include']
        $waybill_data = [
            'direction_id' => $direction_id,
            'delivery_id' => $delivery_id,
            'approval' => $approval,
            'approval_userid' => $approval_userid,
            'product_invoice_amount' => $waybillTotal,
            'waybill_items_total' => $waybillItemsTotal,
            'item_length' => $item_length,
            'item_width' => $item_width,
            'item_height' => $item_height,
            'total_mass_kg' => $total_mass_kg,
            'total_volume' => $total_volume,
            'mass_charge' => $mass_charge,
            'volume_charge' => $volume_charge,
            'charge_basis' => $charge_basis,
            'vat_include' => $vat_include,
            // Warehouse status now managed by warehouse_items table
            'miscellaneous' => $misc_serialized,
            'include_sad500' => $include_sad500,
            'include_sadc' => $include_sadc,
            'last_updated_by'   => get_current_user_id(),
            'last_updated_at'   => current_time('mysql'),
        ];

        $update_result = $wpdb->update(
            $waybills_table,
            $waybill_data,
            ['id' => $waybill_id],
            null,
            ['%d']
        );

        // Check if update was successful
        if ($update_result === false) {
            wp_die('Failed to update waybill. Database error: ' . $wpdb->last_error);
        }


        /* if (isset($_POST['misc'])) {
            self::miscItemsUpdateWaybill($_POST['misc'], $waybill_id, $waybill_no, $final_misc_data);
        } */

        // Check for VAT warning and add to redirect URL
        $redirect_url = admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $waybill_id . '&updated=1');
        if (self::shouldShowVatWarning($vat_include, $waybillItemsTotal)) {
            $redirect_url = add_query_arg('vat_warning', '1', $redirect_url);
        }

        // ✅ Redirect to success page
        wp_redirect($redirect_url);
        exit;
    }

    // Save waybill items uusing custom_items
    public static function save_waybill_items($items, $waybill_no, $waybill_id, $vatOption)
    {
        return (!empty($items) && is_array($items)) ? self::updateWaybillItems($items, $waybill_no) : 0;
    }

    public static function get_total_cost_of_waybill($waybill_no)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_waybills';

        // Get all waybills with their charges and misc
        $waybills = $wpdb->get_results($wpdb->prepare("SELECT mass_charge, volume_charge, miscellaneous FROM $table WHERE waybill_no = %d", $waybill_no));

        $grand_total = 0;
        foreach ($waybills as $waybill) {
            $mass = floatval($waybill['mass_charge']);
            $volume = floatval($waybill['volume_charge']);

            // Unserialize misc and get misc_total
            $misc_total = 0;
            if (!empty($waybill['miscellaneous'])) {
                $misc = maybe_unserialize($waybill['miscellaneous']);
                if (is_array($misc) && isset($misc['misc_total'])) {
                    $misc_total = floatval($misc['misc_total']);
                }
            }

            $grand_total += max($mass, $volume) + $misc_total;
        }

        return number_format($grand_total, 2, '.', '');
    }

    public static function  miscItemsUpdateWaybill($misc, $waybill_id, $waybill_no, $final_misc_data)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'kit_waybills';

        $grouped_misc = [
            'misc_item' => [],
            'misc_price' => [],
            'misc_quantity' => []
        ];


        if (!empty($final_misc_data['misc_items']) && is_array($final_misc_data['misc_items'])) {
            foreach ($final_misc_data['misc_items'] as $item) {
                $grouped_misc['misc_item'][] = $item['misc_item'] ?? '';
                $grouped_misc['misc_price'][] = $item['misc_price'] ?? 0;
                $grouped_misc['misc_quantity'][] = $item['misc_quantity'] ?? 0;
            }
        }


        // Use getMiscCharges function instead of manual handling
        $misc_result = self::getMiscCharges($grouped_misc, []);
        $misc_items = $misc_result->misc_items;
        $misc_total = floatval($misc_result->misc_total);


        // Serialize the misc items array
        $serialized_misc = maybe_serialize([
            'misc_items' => $misc_items,
            'misc_total' => $misc_total,
            'others' => $final_misc_data['others']
        ]);

        // Update the waybill
        $updated = $wpdb->update(
            $table,
            ['miscellaneous' => $serialized_misc],
            ['id' => $waybill_id, 'waybill_no' => $waybill_no],
            ['%s'],
            ['%d', '%d']
        );

        return $updated !== false;
    }


    /**
     * Helper function to render cell content based on key
     * 
     * @param string $key Column key
     * @param object $item Row data object
     * @param string $label Column label
     * @return string HTML content
     */
    private static function renderCellContent($key, $item, $label)
    {
        $value = $item->$key ?? '';

        switch ($key) {
            case 'waybill_no':
                return '<div class="font-medium text-blue-600">' . esc_html($value) . '</div>' .
                    '<div class="text-xs text-gray-500">' . date('M d, Y', strtotime($item->created_at ?? '')) . '</div>';

            case 'customer_id':
                return '<div>' . esc_html($value) . '</div>' .
                    '<div class="text-xs text-gray-500 truncate max-w-xs">' . esc_html($item->customer_name ?? '') . '</div>';

            case 'status':
                $status_class = ($value === 'pending') ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800';
                return '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $status_class . '">' .
                    ucfirst($value) . '</span>';

            case 'approval':
                // Check if current user can approve (administrator or manager)
                $can_approve = KIT_User_Roles::can_approve();

                // If user can approve, show waybillApprovalStatus
                if ($can_approve) {
                    return  KIT_Commons::waybillApprovalStatus($item->waybill_no, $item->id, 'quoted', $item->approval, 'select');
                } else {
                    // For users who cannot approve, show approval status badge only
                    $approval = strtolower($item->approval ?? 'pending');
                    switch ($approval) {
                        case 'approved':
                            $badgeColor = 'bg-green-400 text-green-800';
                            break;
                        case 'pending':
                            $badgeColor = 'bg-yellow-400 text-yellow-800';
                            break;
                        case 'rejected':
                            $badgeColor = 'bg-red-400 text-red-800';
                            break;
                        default:
                            $badgeColor = 'bg-gray-400 text-gray-800';
                    }
                    return '<span class="esmall selection:inline-block mt-1 px-2 py-1 rounded-full font-semibold ' . $badgeColor . '">' .
                        ucfirst($approval) . '</span>';
                }

            case 'amount':
            case 'product_invoice_amount':
                return 'R' . ($value ? number_format($value, 2) : '0');

            case 'items':
                $item_count = is_array($value) ? count($value) : (int)$value;
                return KIT_Commons::renderButton($item_count . ' item' . ($item_count !== 1 ? 's' : ''), 'ghost-primary', 'sm', [
                    'classes' => 'toggle-items flex items-center',
                    'data-item-id' => $item->id ?? '',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>',
                    'iconPosition' => 'right'
                ]);

            case 'actions':
                return self::renderDefaultActions($item);

            case 'total':
                return self::get_total_cost_of_waybill($item->waybill_no);

            default:
                return esc_html($value);
        }
    }

    /**
     * Render default actions for a row
     * 
     * @param object $item Row data object
     * @return string HTML content
     */
    private static function renderDefaultActions($item)
    {
        $current_user = wp_get_current_user();

        return '<div class="flex space-x-2">' .
            '<a href="' . admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $item->id . '&waybill_atts=view_waybill') . '" ' .
            'class="text-blue-600 hover:text-blue-900" title="View">' .
            '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">' .
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />' .
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />' .
            '</svg></a>' .
            KIT_Commons::deleteWaybillGlobal($item->id, $item->waybill_no, $item->delivery_id, $current_user->ID) .
            '</div>';
    }

    /**
     * Render default accordion content
     * 
     * @param array $accordion_data Accordion data structure
     * @param object $item Row data object
     * @return string HTML content
     */
    private static function renderDefaultAccordionContent($accordion_data, $item)
    {
        if (!isset($accordion_data['items']) || !is_array($accordion_data['items'])) {
            return '<p class="text-gray-500">No items available</p>';
        }

        $items = $accordion_data['items'];

        return '<div class="overflow-x-auto">' .
            '<table class="w-full text-xs border border-gray-200 rounded">' .
            '<thead><tr class="bg-gray-100">' .
            '<th class="' . KIT_Commons::thClasses() . ' text-left">Item</th>' .
            '<th class="' . KIT_Commons::thClasses() . ' text-right">Qty</th>' .
            '<th class="' . KIT_Commons::thClasses() . ' text-right">Price</th>' .
            '</tr></thead>' .
            '<tbody>' .
            implode('', array_map(function ($item) {
                return '<tr class="border-t border-gray-100">' .
                    '<td class="' . KIT_Commons::tcolClasses() . '">' . esc_html($item->item ?? $item->item_name) . '</td>' .
                    '<td class="' . KIT_Commons::tcolClasses() . ' text-right">' . esc_html($item->quantity ?? '') . '</td>' .
                    '<td class="' . KIT_Commons::tcolClasses() . ' text-right">R' . number_format($item->unit_price ?? 0, 2) . '</td>' .
                    '</tr>';
            }, $items)) .
            '</tbody></table></div>';
    }

    public static function pdfVerifier($waybill_no, $waybill_id = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_waybills';
        $where = 'waybill_no = %d';
        $params = [$waybill_no];
        if ($waybill_id !== null) {
            $where .= ' AND id = %d';
            $params[] = $waybill_id;
        }



        $sql = $wpdb->prepare("SELECT approval, approval_userid, status, status_userid FROM $table_name WHERE $where LIMIT 1", ...$params);
        $row = $wpdb->get_row($sql, ARRAY_A);
        if (!$row) return false;
        // soWhat: true only if both approval and status conditions are met
        $approval_ok = ($row['approval'] === 'approved' && !empty($row['approval_userid']));
        $status_ok = (in_array($row['status'], ['invoiced', 'quoted', 'paid', 'completed']) && !empty($row['status_userid'])) ?? false;

        $row['soWhat'] = ($approval_ok && $status_ok) ? true : false;
        return $row;
    }

    //if the waybill has status warehoused and 

    public static function delete_waybill($waybill_no, $waybill_id = null)
    {
        global $wpdb;

        $table = $wpdb->prefix . 'kit_waybills';

        // Sanitize inputs
        $waybill_no = sanitize_text_field($waybill_no);
        $waybill_id = $waybill_id !== null ? (int) $waybill_id : null;

        // Build where clause
        $where = ['waybill_no' => $waybill_no];
        if ($waybill_id !== null) {
            $where['id'] = $waybill_id;
        }

        // Delete from DB
        $deleted = $wpdb->delete($table, $where);

        wp_redirect(admin_url('admin.php?page=08600-Waybill'));
    }

    /**
     * Static function to dynamically render table rows with customizable data
     * 
     * @param array $data Array of objects/arrays containing row data
     * @param array $columns Array defining column structure ['key' => 'label']
     * @param array $options Additional options for customization
     * @return string HTML output of table rows
     */
    public static function tRows($data, $columns, $options = [])
    {
        $defaults = [
            'row_class' => 'hover:bg-gray-50',
            'cell_class' => 'px-4 py-3 whitespace-nowrap',
            'header_class' => 'px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase',
            'actions' => true,
            'actions_column' => 'actions',
            'custom_cell_render' => null,
            'custom_actions_render' => null,
            'row_attributes' => null,
            'accordion' => false,
            'accordion_content' => null,
            'table_class' => 'min-w-full divide-y divide-gray-200',
            'show_headers' => true
        ];

        $options = wp_parse_args($options, $defaults);

        ob_start();

        if (!$options['accordion']) {
            // Full table structure
        ?>
            <table class="<?= esc_attr($options['table_class']) ?>">
                <?php if ($options['show_headers']): ?>
                    <thead class="bg-gray-50">
                        <tr>
                            <?php foreach ($columns as $key => $label): ?>
                                <th class="<?= esc_attr($options['header_class']) ?>"><?= esc_html($label) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                <?php endif; ?>
                <tbody class="divide-y divide-gray-200">
                <?php } else {
                // Accordion mode - only table body content
                ?>
                <tbody class="divide-y divide-gray-200">
                <?php }

            foreach ($data as $index => $item):
                $row_attrs = '';
                if ($options['row_attributes'] && is_callable($options['row_attributes'])) {
                    $row_attrs = $options['row_attributes']($item, $index);
                }
                ?>
                    <tr class="<?= esc_attr($options['row_class']) ?>" <?= $row_attrs ?>>
                        <?php foreach ($columns as $key => $label):
                            $cell_attrs = '';
                            if ($options['custom_cell_render'] && is_callable($options['custom_cell_render'])) {
                                $custom_content = $options['custom_cell_render']($key, $item, $index);
                                if ($custom_content !== null) {
                                    echo '<td class="' . esc_attr($options['cell_class']) . '" ' . $cell_attrs . '>' . $custom_content . '</td>';
                                    continue;
                                }
                            }
                        ?>
                            <td class="<?= esc_attr($options['cell_class']) ?>" <?= $cell_attrs ?>>
                                <?php if ($options['custom_cell_render'] && is_callable($options['custom_cell_render'])): ?>
                                    <?= $options['custom_cell_render']($key, $item, $index) ?>
                                <?php else: ?>
                                    <?= self::renderCellContent($key, $item, $label) ?>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <?php if ($options['accordion'] && $options['accordion_content'] && is_callable($options['accordion_content'])): ?>
                        <tr class="accordion-content hidden">
                            <td colspan="<?= count($columns) ?>" class="bg-gray-50 px-4 py-3">
                                <?= $options['accordion_content']($item, $index) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>

                </tbody>
                <?php if (!$options['accordion']): ?>
            </table>
        <?php endif; ?>

<?php
        return ob_get_clean(true);
    }

    public static function DoubleCheckTotal($mass_charge, $volume_charge, $misc_total, $include_sad500, $include_sadc, $vatCharge, $internationalPrice, $charge_basis = null) {
        // Determine the better charge
        $better_charge = 0;
        if ($charge_basis == null) {
            $better_charge = max($mass_charge, $volume_charge);
        } elseif ($charge_basis == 'mass' || $charge_basis == 'weight') {
            $better_charge = $mass_charge;
        } else if ($charge_basis == 'volume') {
            $better_charge = $volume_charge;
        }

        // Calculate additional charges
        $additionalCharges = $misc_total + $include_sad500 + $include_sadc + $vatCharge + $internationalPrice;

        // Calculate total
        $total = $better_charge + $additionalCharges;

        // Return float total
        return (float) $total;
    }
}

// Initialize
KIT_Waybills::init();

// Include necessary files
function wpd_breadcrumbs($atts)
{
    // Set default attributes
    $atts = shortcode_atts(
        [
            'data' => '[]', // Default is an empty array
        ],
        $atts,
        'custom_breadcrumbs'
    );
    $output = "";

    // Decode the JSON data passed to the shortcode
    $breadlinks = json_decode(urldecode($atts['data']), true);

    // Check if the decoded data is valid
    if (! is_array($breadlinks)) {
        return "Invalid breadcrumb data.";
    }

    // Start the breadcrumb container
    $output .= '<div class="shadow rounded bg-slate-300 space-y-6 mb-6">';
    $output .= '<div class="max-w-7xl mx-auto px-4 py-3">
        <nav aria-label="breadcrumb">
            <ol class="flex items-center text-xs text-gray-500">';

    // Loop through each breadcrumb and generate the list items
    $total_links = count($breadlinks);
    foreach ($breadlinks as $index => $link) {
        if ($index !== 0) {
            $output .= '<li class="mx-1 text-black">/</li>'; // Adjusted spacing for separator
        }

        // If it's the last item, make it active
        if ($index === $total_links - 1) {
            $output .= '<li class="text-black font-medium">
                    <p class="m-0 text-black">' . $link['name'] . '</p>
                </li>';
        } else {
            $output .= '<li><a href="' . $link['slug'] . '"
                        class="shutup text-black font-medium hover:underline m-0">
                        <p class="m-0 text-black">' . $link['name'] . '</p>
                    </a></li>';
        }
    }

    // Close the breadcrumb container
    $output .= '</ol>
        </nav>
        </div>
    </div>';

    return $output;
}
add_shortcode('cust_breadcrumb', 'wpd_breadcrumbs');

function render_waybill_row($waybill, $columns, $current_user = null)
{
    if (!$current_user) {
        $current_user = wp_get_current_user();
    }
    $roles = (array) $current_user->roles;

    echo '<tr>';
    foreach ($columns as $col) {
        switch ($col) {
            case 'waybill_no':
                echo '<td>' . esc_html($waybill['waybill_no']) . '</td>';
                break;
            case 'customer':
                echo '<td>' . esc_html($waybill['customer_name']) . '</td>';
                break;
            case 'dispatch_date':
                echo '<td>' . (!empty($waybill['dispatch_date']) ? date('M j, Y', strtotime($waybill['dispatch_date'])) : '-') . '</td>';
                break;
            // ... add more columns as needed ...
            case 'actions':
                echo '<td>';
                // View action (everyone)
                echo KIT_Commons::renderButton('View', 'primary', 'sm', ['href' => admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $waybill['id']), 'gradient' => true]) . ' ';
                // Generate Quotation (admin only)
                if (in_array('administrator', $roles)) {
                    echo KIT_Commons::renderButton('Generate Quotation', 'success', 'sm', ['href' => admin_url('admin.php?page=generate-quotation&waybill_id=' . $waybill['id']), 'gradient' => true]) . ' ';
                }
                // Delete (admin only)
                if (in_array('administrator', $roles)) {
                    echo KIT_Commons::renderButton('Delete', 'danger', 'sm', ['href' => admin_url('admin-post.php?action=delete_waybill&waybill_id=' . $waybill['id']), 'onclick' => 'return confirm(\'Are you sure?\')', 'gradient' => true]);
                }
                echo '</td>';
                break;
            default:
                // fallback for any other column
                echo '<td>' . (isset($waybill[$col]) ? esc_html($waybill[$col]) : '-') . '</td>';
        }
    }
    echo '</tr>';
}
