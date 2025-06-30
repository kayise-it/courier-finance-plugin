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
        add_action('admin_post_update_waybillApproval', [self::class, 'update_waybillApproval']);
        add_action('admin_post_nopriv_update_waybillApproval', [self::class, 'update_waybillApproval']);
        add_shortcode('kit_waybill_form', [__CLASS__, 'display_waybill_form']);
        add_action('kit_waybills_list', [__CLASS__, 'kit_get_all_waybills_table']);
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
    }



    public static function update_waybillApproval()
    {
        global $wpdb;

        $waybillid = intval($_POST['waybillid']);
        $waybillno = sanitize_text_field($_POST['waybillno']);
        $status = sanitize_text_field($_POST['status']);
        $userId = get_current_user_id();

        $table = $wpdb->prefix . 'kit_waybills';

        $updated = $wpdb->update(
            $table,
            [
                'approval' => $status,
                'approval_userid' => $userId,
            ],
            [
                'id' => $waybillid,
                'waybill_no' => $waybillno,
            ],
            ['%s', '%d'],
            ['%d', '%s']
        );

        if ($updated !== false) {
            wp_redirect(add_query_arg('approval_updated', '1', wp_get_referer()));
        } else {
            wp_redirect(add_query_arg('approval_error', '1', wp_get_referer()));
        }
        exit;
    }

    public static function myplugin_ajax_load_waybill_page()
    {
        check_ajax_referer('get_waybills_nonce', 'nonce');

        $paged = isset($_POST['paged']) ? max(1, intval($_POST['paged'])) : 1;
        $items_per_page = isset($_POST['items_per_page']) ? intval($_POST['items_per_page']) : 5;

        $all_waybills = self::kit_get_all_waybills();

        // Just return the table with pagination - no extra wrapper
        echo KIT_Waybills::render_table_with_pagination($all_waybills, [
            'current_page' => $paged,
            'items_per_page' => $items_per_page,
            'ajax' => true
        ]);
        wp_die();
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

        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $items_table    = $wpdb->prefix . 'kit_waybill_items';

        // 1. Get all waybills for the delivery
        $waybills_query = "
        SELECT * FROM $waybills_table
        WHERE delivery_id = %d
        ORDER BY created_at DESC";

        $waybills = $wpdb->get_results($wpdb->prepare($waybills_query, $deliveryid));

        // 2. Get all items linked to those waybills
        $waybill_nos = wp_list_pluck($waybills, 'waybill_no');
        if (empty($waybill_nos)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($waybill_nos), '%s'));

        $items_query = "
        SELECT * FROM $items_table
        WHERE waybillno IN ($placeholders)";
        $items = $wpdb->get_results($wpdb->prepare($items_query, ...$waybill_nos));

        // 3. Group items by waybillno
        $grouped_items = [];
        foreach ($items as $item) {
            $grouped_items[$item->waybillno][] = $item;
        }

        // 4. Attach items to their respective waybill
        foreach ($waybills as &$waybill) {
            $waybill->items = $grouped_items[$waybill->waybill_no] ?? [];
        }

        return $waybills;
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
            $waybill_id = $waybill->id;

            if (!isset($grouped_waybills[$waybill_id])) {
                // Get items for this waybill
                $items = KIT_Waybills::get_waybill_items($waybill->waybill_no);

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
                                <div class="font-medium text-blue-600"><?= esc_html($waybill->waybill_no) ?></div>
                                <div class="text-xs text-gray-500"><?= date('M d, Y', strtotime($waybill->created_at)) ?></div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div><?= esc_html($waybill->customer_id) ?></div>
                                <div class="text-xs text-gray-500 truncate max-w-xs"><?= esc_html($waybill->customer_name ?? '') ?>
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                R<?= ($waybill->product_invoice_amount) ?>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= ($waybill->status === 'pending') ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                                    <?= ucfirst($waybill->status) ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <button class="toggle-items text-blue-600 hover:text-blue-800 flex items-center"
                                    data-waybill-id="<?= $waybill_id ?>">
                                    <?= $item_count ?> item<?= ($item_count !== 1) ? 's' : '' ?>
                                    <svg class="w-4 h-4 ml-1 transition-transform" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                                        </path>
                                    </svg>
                                </button>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex space-x-2">
                                    <a href="<?php echo admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $waybill->id . '&waybill_atts=view_waybill'); ?>"
                                        class="text-blue-600 hover:text-blue-900" title="View">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </a>
                                    <?= KIT_Commons::deleteWaybillGlobal($waybill->id, $waybill->waybill_no, $waybill->delivery_id, $current_user->ID); ?>
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
                                                        return '<span class="text-right">R' . number_format($item->unit_price, 2) . '</span>';
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
        wp_enqueue_style('kit-quotations');
        wp_enqueue_script('kit-scripts');

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

        // Check permissions
        if (!current_user_can('manage_options') && !current_user_can('edit_pages')) {
            if ($is_ajax) {
                wp_send_json_error('Unauthorized', 403);
            } else {
                wp_die('Unauthorized');
            }
        }

        // Process the form data
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['waybill_no'])) {
            $result = self::save_waybill($_POST);

            if (is_wp_error($result)) {
                // Handle the error gracefully
                $error_message = $result->get_error_message();
                echo '<div class="error">Failed to get result: ' . esc_html($error_message) . '</div>';
                return;
            }

            // Now it's safe to use $result['waybill_no']
            $newWayBill = self::getFullWaybillWithItems($result['waybill_no']);

            if (is_wp_error($result)) {
                if ($is_ajax) {
                    wp_send_json_error(['message' => 'Error saving waybill: ' . $result->get_error_message()]);
                } else {
                    wp_redirect(add_query_arg('error', '1', wp_get_referer()));
                }
            } else {
                if ($is_ajax) {
                    wp_send_json_success([
                        'message' => 'Waybill saved successfully.',
                        'waybill_id' => $result
                    ]);
                } else {
                    $redirect_url = add_query_arg([
                        'page'         => '08600-Waybill-view',
                        'waybill_id'   => $result['id'],
                        'waybill_atts' => $newWayBill,
                    ], admin_url('admin.php'));

                    wp_redirect($redirect_url);
                }
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
            if (! is_null($delivery_id) && isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'getDestinationCountry') !== false) {
                wp_send_json_success(['destination_country' => $country]);
            }
            return esc_html($country);
        } else {
            if (! is_null($delivery_id) && isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'getDestinationCountry') !== false) {
                wp_send_json_error('Destination country not found.');
            }
            return '<span class="text-red-500">Not set</span>';
        }
    }

    // Calculate total:
    // Check which amount is better: mass_charge and volume_charge
    // If both are set, use the one with the higher value
    // Then add the miscellaneous charges to the total
    // return the total amount
    public static function calculate_total($mass_charge, $volume_charge, $misc_total)
    {
        // Ensure all inputs are floats
        $mass_charge = floatval($mass_charge);
        $volume_charge = floatval($volume_charge);
        $misc_total = floatval($misc_total);

        // Determine the better charge
        $better_charge = max($mass_charge, $volume_charge);

        // Calculate total
        $total = $better_charge + $misc_total;

        return number_format($total, 2, '.', '');
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
        $misc_total = 0.00;

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
    public static function save_waybill($data)
    {
        global $wpdb;

        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $items_table = $wpdb->prefix . 'kit_waybill_items';
        $rates_table = $wpdb->prefix . 'kit_shipping_dedicated_truck_rates';
        $customerArray = [];
        // 👥 Customer 1 Details
        $cust_id = isset($data['cust_id']) ? $data['cust_id'] : null;
        $customer_select = isset($data['customer_select']) ? $data['customer_select'] : null;
        $customer_name = isset($data['customer_name']) ? $data['customer_name'] : null;
        $customer_surname = isset($data['customer_surname']) ? $data['customer_surname'] : null;
        $cell = isset($data['cell']) ? $data['cell'] : null;
        $address = isset($data['address']) ? $data['address'] : null;

        //PUT all customer details into an array
        $customerArray = [
            'cust_id' => $cust_id,
            'customer_select' => $customer_select,
            'customer_name' => $customer_name,
            'customer_surname' => $customer_surname,
            'cell' => $cell,
            'address' => $address
        ];

        // 🌍 Destination Info
        $destination_country = isset($data['destination_country']) ? $data['destination_country'] : null;
        $destination_city = isset($data['destination_city']) ? $data['destination_city'] : null;

        // 📦 Package Dimensions & Weights
        $total_mass_kg = isset($data['total_mass_kg']) ? $data['total_mass_kg'] : null;
        $item_length = isset($data['item_length']) ? $data['item_length'] : null;
        $item_width = isset($data['item_width']) ? $data['item_width'] : null;
        $item_height = isset($data['item_height']) ? $data['item_height'] : null;
        $total_volume = isset($data['total_volume']) ? $data['total_volume'] : null;

        // 💰 Charge Details
        $charge_basis = isset($data['charge_basis']) ? $data['charge_basis'] : null;
        $mass_charge = isset($data['mass_charge']) ? $data['mass_charge'] : null;
        $volume_charge = isset($data['volume_charge']) ? $data['volume_charge'] : null;

        // 🧾 Custom Items
        $custom_items = isset($data['custom_items']) ? $data['custom_items'] : [];
        $customer_id = isset($data['cust_id']) ? intval($data['cust_id']) : 0;
        // 🧮 Misc Charges
        $misc_combined = [];
        $misc_total = 0.00;

        // If misc items are provided, process them using getMiscCharges
        if (isset($data['misc']) && is_array($data['misc'])) {
            $misc_result = self::getMiscCharges($data['misc'], []);
            $misc_combined = $misc_result->misc_items;
            $misc_total = floatval($misc_result->misc_total);
        }
        // Serialize miscellaneous charges
        $misc_serialized = !empty($misc_combined) ? serialize($misc_combined) : '';

        //if $cust_id = 0, meaning no customer is selected, then create a new customer
        if (empty($cust_id) || $customer_select === 'new') {
            // Create a new customer
            $customer_id = KIT_Customers::save_customer([
                'customer_select' => $customer_select,
                'customer_name' => $customer_name,
                'customer_surname' => $customer_surname,
                'cell' => $cell,
                'address' => $address,
            ]);
        }

        $waybillTotal = self::calculate_total($mass_charge, $volume_charge, $misc_total);

        // Generate waybill number if not provided
        $waybill_no = !empty($data['waybill_no']) ? $data['waybill_no'] :
            'WB-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

        // Prepare waybill data
        $waybill_data = [
            'direction_id' => (int)$data['direction_id'],
            'delivery_id' => (int)$data['delivery_id'],
            'customer_id' => $customer_id,
            'waybill_no' => $waybill_no,
            'product_invoice_number' => 'INV-' . date('Ymd-His'),
            'product_invoice_amount' => $waybillTotal,
            'item_length' => (float)($data['item_length'] ?? 0),
            'item_width' => (float)($data['item_width'] ?? 0),
            'item_height' => (float)($data['item_height'] ?? 0),
            'total_mass_kg' => (float)($data['total_mass_kg'] ?? 0),
            'total_volume' => (float)($data['total_volume'] ?? 0),
            'mass_charge' => (float)($data['mass_charge'] ?? 0),
            'volume_charge' => (float)($data['volume_charge'] ?? 0),
            'charge_basis' => sanitize_text_field($data['charge_basis'] ?? 'mass'),
            'miscellaneous' => !empty($misc_serialized) ? $misc_serialized : '',
            'include_sad500' => isset($data['include_sad500']) ? 1 : 0,
            'tracking_number' => 'TRK-' . strtoupper(wp_generate_password(8, false)),
            'created_by' => get_current_user_id(),
            'last_updated_by' => get_current_user_id(),
            'status' => 'pending'
        ];

        // Insert waybill
        $inserted = $wpdb->insert($waybills_table, $waybill_data);

        if (!$inserted) return new WP_Error('db_error', __('Could not save waybill', 'kit'));

        $waybill_id = $wpdb->insert_id;

        self::miscItemsUpdateWaybill($data['misc'], $waybill_id, $waybill_no);


        // Save items if provided
        if (!empty($data['custom_items'])) {
            self::save_waybill_items($data['custom_items'], $waybill_no, $waybill_id);
        }

        return [
            'id' => $waybill_id,
            'waybill_no' => $waybill_no,
            'amount' => $waybillTotal
        ];
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
                w.id,
                w.waybill_no,
                w.approval,
                w.approval_userid,
                w.customer_id,
                w.status,
                w.product_invoice_number,
                c.name AS customer_name,
                d.destination_city,
                d.origin_country,
                d.destination_country,
                d.delivery_reference,
                u.display_name AS created_by,
                a.user_login AS approved_by_username
                ";
        } else {
            $select_fields = "w.*,
            d.delivery_reference,
            c.name AS customer_name, 
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
        return self::get_waybills($args);
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
            'items_per_page' => isset($_GET['items_per_page']) ? intval($_GET['items_per_page']) : 5, // Get from request if available
            'current_page' => isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1
        ];

        $args = wp_parse_args($args, $defaults);

        // Pagination Setup
        $items_per_page = $args['items_per_page'];
        $total_items = count($data);
        $current_page = $args['current_page'];
        $total_pages = ceil($total_items / $items_per_page);
        $offset = ($current_page - 1) * $items_per_page;
        $paginated_data = array_slice($data, $offset, $items_per_page);

        ob_start();
        // Only include wrapper for non-AJAX calls
        if (!$ajax) {
            echo '<div id="waybill-table-container">';
        }
    ?>
        <!-- Single pagination (top or bottom, not both) -->
        <div class="tablenav top flex flex-wrap items-center justify-between gap-4 mb-4">
            <!-- Items per page dropdown - LEFT SIDE -->
            <div class="flex items-center space-x-2">
                <label for="items-per-page" class="text-xs text-gray-600 whitespace-nowrap">Items per page:</label>
                <select id="items-per-page"
                    class="text-xs rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500 shadow-sm py-1 pl-2 pr-8">
                    <option value="5" <?php selected($items_per_page, 5); ?>>5</option>
                    <option value="10" <?php selected($items_per_page, 10); ?>>10</option>
                    <option value="20" <?php selected($items_per_page, 20); ?>>20</option>
                    <option value="50" <?php selected($items_per_page, 50); ?>>50</option>
                    <option value="100" <?php selected($items_per_page, 100); ?>>100</option>
                </select>

                <!-- Item count -->
                <span class="text-xs text-gray-600 whitespace-nowrap">
                    <?php echo number_format($total_items); ?> item<?php echo $total_items !== 1 ? 's' : ''; ?>
                </span>
            </div>

            <!-- Pagination links - RIGHT SIDE -->
            <div class="flex items-center space-x-1">
                <?php
                echo paginate_links([
                    'base'    => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                    'format'  => '?paged=%#%',
                    'current' => $current_page,
                    'total'   => $total_pages,
                    'prev_next' => true,
                    'prev_text' => __('<span class="px-3 py-1 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">&laquo; Previous</span>'),
                    'next_text' => __('<span class="px-3 py-1 rounded border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Next &raquo;</span>'),
                    'add_args' => ['items_per_page' => $items_per_page],
                    'before_page_number' => '<span class="pagination-links">', // Add this
                    'after_page_number' => '</span>', // Add this
                ]);

                ?>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Handle items per page change
                document.getElementById('items-per-page').addEventListener('change', function() {
                    const itemsPerPage = this.value;
                    const url = new URL(window.location.href);
                    url.searchParams.set('items_per_page', itemsPerPage);
                    url.searchParams.set('paged', 1); // Reset to first page
                    window.location.href = url.toString();
                });

                // Add current page styling
                document.querySelectorAll('.pagination-links a').forEach(link => {
                    if (link.textContent === '<?php echo $current_page; ?>') {
                        link.classList.add('bg-blue-50', 'border-blue-500', 'text-blue-600');
                        link.classList.remove('bg-white', 'border-gray-300', 'text-gray-700');
                    }
                });
            });
        </script>
        <!-- Table -->


        <table id="ajaxtable" class="<?php echo esc_attr($args['table_class']); ?>">
            <thead class="bg-gray-50">
                <tr>
                    <?php foreach ($args['fields'] as $field): ?>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <?php echo esc_html(ucfirst(str_replace('_', ' ', $field))); ?>
                        </th>
                    <?php endforeach; ?>
                    <?php if ($args['actions']): ?>
                        <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($paginated_data as $item):
                    $quotation = (object)$item;
                ?>
                    <tr class="hover:bg-gray-50" data-waybill-id="<?php echo esc_attr($quotation->id); ?>">
                        <?php foreach ($args['fields'] as $field): ?>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-xs">
                                    <?php if ($field === 'approval'): ?>
                                        <!-- QuotationStatus($waybillno, $waybillid, $status) -->
                                        <?php if (current_user_can('administrator')): ?>
                                            <?= KIT_Commons::QuotationStatus($quotation->waybill_no, $quotation->id, 'quoted', $quotation->approval, 'select'); ?>
                                        <?php else:
                                            KIT_Commons::statusBadge($quotation->approval);
                                        endif; ?>
                                    <?php elseif ($field === 'waybill_no'): ?>
                                        <span class="font-medium text-blue-600"><?php echo esc_html($quotation->$field); ?></span>
                                        <div class="text-xs text-gray-500 mt-1">
                                            <?php echo date('M d', strtotime($quotation->created_at)); ?></div>
                                    <?php elseif ($field === 'customer_id'): ?>
                                        <span class="font-medium"><?php echo esc_html($quotation->customer_id); ?></span>
                                        <div class="text-xs text-gray-500 truncate max-w-xs"
                                            title="<?php echo esc_attr($quotation->customer_name); ?>">
                                            <?php echo esc_html($quotation->customer_name); ?></div>
                                    <?php else: ?>
                                        <?php echo esc_html($quotation->$field); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
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
                                    <a href="<?php echo admin_url('admin.php?page=08600-Waybill-print&waybill_id=' . $quotation->id); ?>"
                                        class="text-indigo-600 hover:text-indigo-900" title="Print" target="_blank">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                    </a>
                                    <?php if (current_user_can('administrator') || current_user_can('manager')): ?>
                                        <?php if ($args['show_create_quotation']): ?>
                                            <button class="create-quotation text-green-600 hover:text-green-900" title="Create Quotation"
                                                data-waybill-id="<?php echo esc_attr($quotation->id); ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>Quote
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>


                                    <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>">
                                        <input type="hidden" name="action" value="delete_waybill">
                                        <?php wp_nonce_field('delete_waybill_nonce') ?>
                                        <input type="hidden" name="waybill_id" value="<?= esc_attr($quotation->id) ?>">
                                        <input type="hidden" name="delivery_id" value="<?= esc_attr($quotation->delivery_id) ?>">
                                        <input type="hidden" name="user_id" value="<?= esc_attr(get_current_user_id()) ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Delewete</button>
                                    </form>
                                </div>
                            </td>
                        <?php endif; ?>
                        </>
                    <?php endforeach; ?>
            </tbody>
        </table>

        <?php
        if (!$ajax) {
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

    // ✅ Shortcode to display all waybills
    public static function kit_get_all_waybills_table()
    {
        // Get waybills with proper fields
        $waybills = self::get_waybills(['custom_format' => true]);

        // Start output buffering
        ob_start(); ?>
        <div class="mx-auto p-4">
            <!-- Header with button -->
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-gray-800">Waybills</h2>
                <?php
                // Include the modal component
                $customers   = KIT_Customers::tholaMaCustomer();
                $form_action = admin_url('admin-post.php?action=add_waybill_action');

                $modal_path = realpath(plugin_dir_path(__FILE__) . '../components/modal.php');

                if (file_exists($modal_path)) {
                    require_once $modal_path;
                } else {
                    error_log("Modal.php not found at: " . $modal_path);
                }

                echo KIT_Modal::render(
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
                );
                ?>
            </div>

            <?php
            // Use tRows function to render the table
            $current_user = wp_get_current_user();
            $roles = (array) $current_user->roles;

            if (in_array('administrator', $roles)) {
                $columns = [
                    'waybill_no' => 'Waybill No',
                    'delivery_reference' => 'Delivery',
                    'approved_by_username' => 'Approved By',
                    'destination_country' => 'Destination',
                    'customer_name' => 'Customer',
                    'actions' => 'Actions'
                ];
            } else {
                $columns = [
                    'waybill_no' => 'Waybill No',
                    'approval' => 'Status',
                    'customer_name' => 'Customer',
                    'destination_city' => 'Destination City',
                    'destination_country' => 'Destination Country',
                    'actions' => 'Actions'
                ];
            }

            echo self::tRows($waybills, $columns, [
                'table_class' => 'min-w-full divide-y divide-gray-200',
                'row_class' => 'hover:bg-gray-50',
                'cell_class' => KIT_Commons::tcolClasses(),
                'header_class' => KIT_Commons::thClasses(),
                'custom_cell_render' => function ($key, $item, $index) use ($current_user, $roles) {
                    switch ($key) {
                        case 'delivery_reference':
                            if (!empty($item->delivery_id)) {
                                return '<a href="' . esc_url(add_query_arg(['page' => 'view-deliveries', 'delivery_id' => $item->delivery_id], admin_url('admin.php'))) . '" class="text-blue-600 hover:text-blue-900">' . esc_html($item->delivery_reference) . '</a>';
                            }
                            return '<span class="text-gray-400">Not set</span>';

                        case 'approved_by_username':
                            if (!empty($item->approved_by_username)) {
                                return '<p class="">' . esc_html($item->approved_by_username) . '</p>';
                            } elseif (in_array('administrator', $roles) || in_array('manager', $roles)) {
                                return '<form method="POST" id="approvalBtn" action="' . esc_url(admin_url('admin-post.php')) . '">' .
                                    '<input type="hidden" name="action" value="approve_waybill">' .
                                    '<input type="hidden" name="waybill_id" value="' . esc_attr($item->id) . '">' .
                                    '<input type="hidden" name="delivery_id" value="' . esc_attr($item->delivery_id) . '">' .
                                    '<input type="hidden" name="user_id" value="' . esc_attr($current_user->ID) . '">' .
                                    '<button type="submit" class="bg-blue-500 hover:bg-blue-700 rounded block text-white text-center align-middle px-6 py-3">Approve Now</button>' .
                                    '</form>';
                            } else {
                                return 'Not Approved';
                            }

                        case 'destination_country':
                            if (empty($item->delivery_id)) {
                                // Show modal button for setting destination
                                $modal_id = 'destination-modal-' . $item->id;
                                $modal_title = __('Add Destination Country', 'your-text-domain');

                                // Build modal content
                                ob_start();
                                $template_path = COURIER_FINANCE_PLUGIN_PATH . 'includes/components/scheduledDeliveries.php';
                                if (file_exists($template_path)) {
                                    include $template_path;
                                }
                                $deliveries_html = ob_get_clean();

                                $modal_content = '<form method="POST" action="' . esc_url(admin_url('admin-post.php')) . '" class="space-y-4">' .
                                    '<input type="hidden" name="action" value="update_destinationCountry">' .
                                    wp_nonce_field('update_destinationCountry_nonce', '_wpnonce', true, false) .
                                    '<input type="hidden" name="waybill_id" value="' . $item->id . '" />' .
                                    '<div class="p-4">' .
                                    '<div id="scheduled-deliveries-container" class="mt-4">' .
                                    '<h4 class="text-md font-medium text-gray-600 mb-2">' . $deliveries_html . '</h4>' .
                                    '</div>' .
                                    '</div>' .
                                    '<div class="px-4 py-3 border-t border-gray-200 flex justify-end">' .
                                    '<button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-xs">Update Destination</button>' .
                                    '</div>' .
                                    '</form>';

                                return '<button data-modal="' . esc_attr($modal_id) . '" class="text-indigo-600 hover:text-indigo-900">' . __('Add Destination', 'your-text-domain') . '</button>' .
                                    KIT_Modal::render($modal_id, $modal_title, $modal_content, 'md');
                            } else {
                                return KIT_Waybills::getDestinationCountry($item->delivery_id);
                            }

                        case 'actions':
                            $estimate = KIT_Quotations::calculate_waybill_estimate_by_id($item->id);
                            return '<div class="space-y-2">' .
                                '<a href="' . admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $item->id . '&waybill_atts=view_waybill') . '" ' .
                                'class="edit-waybill bg-blue-500 rounded block text-white text-center align-middle px-6 py-3" ' .
                                'id="waybill' . esc_attr($item->waybill_no) . '">View</a>' .
                                '<div class="text-xs text-gray-600">Est: R' . number_format($estimate, 2) . '</div>' .
                                '</div>';
                    }
                    return null; // Let default rendering handle other cases
                }
            ]);
            ?>
        </div>
    <?php
        return ob_get_clean();
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
                                <?php echo esc_html(ucfirst(str_replace('_', ' ', $field))); ?>
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
                                                <?php echo esc_html($quotation->customer_name); ?></div>
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
                                            <a href="<?php echo admin_url('admin-ajax.php?action=generate_pdf&quotation_id=' . $quotation->id . '&security_nonce=' . wp_create_nonce('pdf_nonce')); ?>"
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
                                            <button class="create-quotation text-green-600 hover:text-green-900" title="Create Quotation"
                                                data-waybill-id="<?php echo esc_attr($quotation->id); ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                asdfafsd
                                            </button>
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
                            alert('Error: ' + (response.data?.message || 'Failed to create quotation'));
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
                    w.*,
                    dt.delivery_reference,
                    dt.direction_id,
                    /* dt.origin_country,
                    dt.destination_country,
                    dt.destination_city, */
                    dt.dispatch_date,
                    dt.truck_number,
                    dt.status,
                    c.name,
                    c.surname,
                    c.cell,
                    c.email,
                    c.address,
                     q.waybill_id,
                    q.subtotal,
                    q.vat_amount,
                    q.total,
                    q.quotation_notes,
                    q.status,
                    q.created_by,
                    q.created_at,
                    u.user_login AS approved_by_username,
                                -- Origin
            oc1.country_name AS origin_country, 
            oc1.country_code AS origin_code,

            -- Destination
            oc2.country_name AS destination_country, 
            oc2.country_code AS destination_code
                FROM
                    wp_kit_waybills AS w
                LEFT JOIN wp_kit_deliveries AS dt ON w.delivery_id = dt.id
                LEFT JOIN wp_kit_customers AS c ON w.customer_id = c.cust_id
                LEFT JOIN wp_kit_quotations AS q ON q.waybill_id = w.id
                LEFT JOIN wp_users AS u ON u.id = w.approval_userid
                LEFT JOIN wp_kit_shipping_directions AS us ON u.id = w.approval_userid
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
        $waybill_no = $waybill_data['waybill_no'];
        // 2. Get the waybill items
        $waybill_items = $wpdb->get_results(
            $wpdb->prepare("
                            SELECT *
                            FROM wp_kit_waybill_items
                            WHERE waybillno = %s
                        ", $waybill_no),
            ARRAY_A
        );
        // Attach items to the result
        $waybill_data['items'] = $waybill_items;

        // Unserialize miscellaneous items before returning
        if (!empty($waybill_data['miscellaneous'])) {
            $waybill_data['miscellaneous'] = maybe_unserialize($waybill_data['miscellaneous']);
        }

        return $waybill_data;
    }

    public static function waybillView()
    {
        $waybill_id = isset($_GET['waybill_id']) ? intval($_GET['waybill_id']) : 0;
        $waybill = KIT_Waybills::bonaWaybill($waybill_id);
        $is_editing = isset($_GET['edit']) && $_GET['edit'] === 'true';

        ?><div class="wrap"><?php 
        if ($waybill) {
            if (!$is_editing) { ?>
            
                <div class="max-w-6xl mx-auto p-6 md:space-y-6 bg-white rounded-lg shadow-md">
                    <!-- Header Section -->
                    <div class="flex justify-between items-center border-b pb-4">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-800">Waybill #<?= htmlspecialchars($waybill['waybill_no']) ?></h1>
                            <div class="flex items-center mt-2">
                                <?= KIT_Commons::statusBadge($waybill['approval']) ?>
                                
                                <span class="ml-2 text-xs text-gray-500">
                                    <?php
                                    $status_text = ucfirst($waybill['approval']);
                                    $action_text = $waybill['approval'] === 'pending' ? 'Pending' : ($waybill['approval'] === 'rejected' ? 'Rejected' : ($waybill['approval'] === 'completed' ? 'Completed' : 'Approved'));

                                    echo $action_text . ' By: ' . $waybill['approved_by_username'];
                                    ?>
                                </span>
                                <span class="ml-2 text-xs text-gray-500">
                                    Last Updated: <?= date('M j, Y', strtotime($waybill['last_updated_at'])) ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex space-x-3">
                            <!-- Action Buttons -->

                            <a href="<?php echo admin_url('admin-ajax.php?action=generate_pdf&waybill_no=' . $waybill['waybill_no'] . '&pdf_nonce=' . wp_create_nonce("pdf_nonce")); ?>"

                                class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                                DownlQDWSoad PDF
                            </a>

                            <?= KIT_Commons::QuotationStatus($waybill['waybill_no'], $waybill['id'], 'quoted', $waybill['approval'], 'select'); ?>
                        </div>
                    </div>

                    <!-- Waybill Information Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                        <!-- Waybill Details -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Waybill Details</h2>
                            <div class="space-y-3">
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Waybill Number:</span>
                                    <span class="font-medium"><?= htmlspecialchars($waybill['waybill_no']) ?></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Tracking Number:</span>
                                    <span class="font-medium"><?= htmlspecialchars($waybill['tracking_number']) ?></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Invoice Number:</span>
                                    <span class="font-medium"><?= htmlspecialchars($waybill['product_invoice_number']) ?></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Waybill Amount:</span>
                                    <span class="font-medium"><?= KIT_Commons::currency() ?> <?= number_format($waybill['mass_charge'], 2) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Details -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Customer Details</h2>
                            <div class="space-y-3">
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Customer Name:</span>
                                    <span class="font-medium"><?= $waybill['name'] ?></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Customer Surname:</span>
                                    <span class="font-medium"><?= $waybill['surname'] ?></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Contact:</span>
                                    <span class="font-medium"><?= htmlspecialchars($waybill['cell']) ?></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Email:</span>
                                    <span class="font-medium"><?= $waybill['email'] ?></span>
                                </div>

                            </div>
                        </div>
                        <!-- Shipment Details -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Shipment Details</h2>
                            <div class="space-y-3">

                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Waybill Amount:</span>
                                    <span class="font-medium"><?= KIT_Commons::currency() ?> <?= number_format($waybill['mass_charge'], 2) ?></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Waybill misc total:</span>
                                    <span class="font-medium"><?= KIT_Commons::currency() ?> <?= number_format(($waybill['miscellaneous']['misc_total']) ?? 0, 2) ?></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Total:</span>
                                    <?php
                                    // Use getMiscCharges to process the misc data
                                    $misc_data = null;
                                    if (!empty($waybill['miscellaneous'])) {
                                        $misc_data = maybe_unserialize($waybill['miscellaneous']);
                                    }
                                    $misc_total = 0;

                                    if ($misc_data && isset($misc_data['misc_items'])) {
                                        // Convert the stored format to the format expected by getMiscCharges
                                        $misc_data_for_processing = [
                                            'misc_item' => [],
                                            'misc_price' => [],
                                            'misc_quantity' => []
                                        ];

                                        foreach ($misc_data['misc_items'] as $item) {
                                            $misc_data_for_processing['misc_item'][] = $item['misc_item'];
                                            $misc_data_for_processing['misc_price'][] = $item['misc_price'];
                                            $misc_data_for_processing['misc_quantity'][] = $item['misc_quantity'];
                                        }

                                        $misc_result = self::getMiscCharges($misc_data_for_processing, []);
                                        $misc_total = floatval($misc_result->misc_total);
                                    }

                                    $total_amount = floatval($waybill['mass_charge']) + $misc_total;
                                    ?>
                                    <span class="font-medium"><?= KIT_Commons::currency() ?> <?= number_format($total_amount, 2) ?></span>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-gray-600 font-bold"></span>
                                    <span class="text-xs text-gray-500 italic">
                                        Waybill Amount + Misc Total
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- Route Information -->
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Route Information</h2>
                            <div class="space-y-3">
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Origin:</span>
                                    <span class="font-medium"><?= htmlspecialchars($waybill['origin_country']) ?></span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Destination:</span>
                                    <span class="font-medium">
                                        <?= htmlspecialchars($waybill['destination_country']) ?>
                                    </span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Dimensions:</span>
                                    <span class="font-medium">
                                        <?= htmlspecialchars($waybill['item_length']) ?> ×
                                        <?= htmlspecialchars($waybill['item_width']) ?> ×
                                        <?= htmlspecialchars($waybill['item_height']) ?> cm
                                    </span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-gray-600 font-bold">Total Mass:</span>
                                    <span class="font-medium"><?= htmlspecialchars($waybill['total_mass_kg']) ?> kg</span>
                                </div>

                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="">
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Waybil32l Items</h2>
                                <?php
                                echo KIT_Commons::waybillTrackAndData($waybill['items']);
                                ?>
                            </div>
                        </div>
                        <!-- Miscellaneous Items Section -->
                        <div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Miscellaneous Items</h2>
                                <?php
                                $misc_data = null;
                                if (!empty($waybill['miscellaneous'])) {
                                    $misc_data = maybe_unserialize($waybill['miscellaneous']);
                                }

                                if (!empty($misc_data)):
                                    // Use getMiscCharges to process the misc data
                                    $misc_total = 0;

                                    if (isset($misc_data['misc_items'])) {
                                        // Convert the stored format to the format expected by getMiscCharges
                                        $misc_data_for_processing = [
                                            'misc_item' => [],
                                            'misc_price' => [],
                                            'misc_quantity' => []
                                        ];

                                        foreach ($misc_data['misc_items'] as $item) {
                                            $misc_data_for_processing['misc_item'][] = $item['misc_item'];
                                            $misc_data_for_processing['misc_price'][] = $item['misc_price'];
                                            $misc_data_for_processing['misc_quantity'][] = $item['misc_quantity'];
                                        }

                                        $misc_result = self::getMiscCharges($misc_data_for_processing, []);
                                        $misc_total = floatval($misc_result->misc_total);
                                    }
                                ?>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-100">
                                                <tr>
                                                    <th class="<?= KIT_Commons::thClasses(); ?>">Description</th>
                                                    <th class="<?= KIT_Commons::thClasses(); ?>">Price</th>
                                                    <th class="<?= KIT_Commons::thClasses(); ?>">Qty</th>
                                                    <th class="<?= KIT_Commons::thClasses(); ?>">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                <?php $temTotal = 0;

                                                foreach ($misc_data['misc_items'] as $key => $item): ?>
                                                    <tr>

                                                        <td class="<?= KIT_Commons::tcolClasses() ?>"><?= htmlspecialchars($item['misc_item']) ?></td>
                                                        <td class="<?= KIT_Commons::tcolClasses() ?>"><?= KIT_Commons::currency() ?> <?= number_format($item['misc_price'], 2) ?></td>
                                                        <td class="<?= KIT_Commons::tcolClasses() ?>"><?= intval($item['misc_quantity']) ?></td>
                                                        <td class="<?= KIT_Commons::tcolClasses() ?>">
                                                            <?= KIT_Commons::currency() ?> <?= number_format($item['misc_price'] * $item['misc_quantity'], 2) ?>
                                                        </td>
                                                    </tr>
                                                    <?php $temTotal += $item['misc_price'] * $item['misc_quantity']; ?>
                                                <?php endforeach; ?>
                                                <tr>
                                                    <td colspan="3" class="<?= KIT_Commons::tcolClasses() ?> text-right font-semibold">Total</td>
                                                    <td class="<?= KIT_Commons::tcolClasses() ?> font-bold">
                                                        <?= KIT_Commons::currency() ?> <?= number_format($misc_total, 2) ?>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500">No miscellaneous items added</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>


                    <!-- Notes Section -->
                    <?php if ($waybill['approval'] === 'pending') : ?>
                        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                            <div class="flex flex-col">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-xs font-medium text-yellow-800">Approval Required</h3>
                                    <div class="mt-2 text-xs text-yellow-700">
                                        <p>This waybill is pending manager approval before processing.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Footer Actions -->
                    <div class="flex justify-end space-x-3 border-t pt-4">
                        <a href="?page=08600-Waybill-view&waybill_id=<?= $waybill_id ?>&edit=true" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Edit Waybill
                        </a>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Save Cwhang3es
                        </button>
                    </div>
                </div>
            <?php
            } else { ?>
                <div class="max-w-6xl mx-auto p-6 bg-white rounded-lg shadow-md">
                    <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>">
                        <input type="hidden" name="action" value="update_waybill_action">
                        <input type="hidden" name="waybill_id" value="<?= $waybill_id ?>">
                        <input type="hidden" name="waybill_no" value="<?= $waybill['waybill_no'] ?>">
                        <input type="hidden" name="cust_id" value="<?= $waybill['customer_id'] ?>">
                        <?php wp_nonce_field('update_waybill_nonce'); ?>

                        <!-- Header Section -->
                        <div class="flex justify-between items-center mb-8 border-b pb-4">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-800">Editing Waybill #<?= htmlspecialchars($waybill['waybill_no']) ?></h1>
                                <div class="flex items-center mt-2">
                                    <span class="px-3 py-1 rounded-full text-xs font-medium <?= $waybill['approval'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800' ?>">
                                        <?= ucfirst($waybill['approval']) ?> Approval
                                    </span>
                                    <span class="ml-2 text-xs text-gray-500">
                                        <?php
                                        $status_text = ucfirst($waybill['approval']);
                                        $action_text = $waybill['approval'] === 'pending' ? 'Pending' : ($waybill['approval'] === 'rejected' ? 'Rejected' : ($waybill['approval'] === 'completed' ? 'Completed' : 'Approved'));
                                        echo $action_text . ' By: ' . $waybill['approved_by_username'];
                                        ?>
                                    </span>
                                    <span class="ml-2 text-xs text-gray-500">
                                        Last Updated: <?= date('M j, Y', strtotime($waybill['last_updated_at'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex space-x-3">
                                <a href="?page=08600-Waybill-view&waybill_id=<?= $waybill_id ?>" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Canc3el Editing
                                </a>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    Save Chaunges
                                </button>
                            </div>
                        </div>

                        <!-- Waybill Information Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                            <!-- Customer Details -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Customer Details</h2>
                                <div class="space-y-3">
                                    <div class="flex flex-col">
                                        <span class="text-gray-600 font-bold">Customer Name:</span>
                                        <span class="font-medium"><?= $waybill['name'] ?></span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-gray-600 font-bold">Customer Surname:</span>
                                        <span class="font-medium"><?= $waybill['surname'] ?></span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-gray-600 font-bold">Contact:</span>
                                        <span class="font-medium"><?= htmlspecialchars($waybill['cell']) ?></span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-gray-600 font-bold">Email:</span>
                                        <span class="font-medium"><?= $waybill['email'] ?></span>
                                    </div>

                                </div>
                            </div>
                            <!-- Shipment Details (Read-only) -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Shipment Details</h2>
                                <div class="space-y-3">
                                    <div class="flex flex-col">
                                        <span class="text-gray-600 font-bold">Waybill Number:</span>
                                        <span class="font-medium"><?= htmlspecialchars($waybill['waybill_no']) ?></span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-gray-600 font-bold">Tracking Number:</span>
                                        <span class="font-medium"><?= htmlspecialchars($waybill['tracking_number']) ?></span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-gray-600 font-bold">Invoice Number:</span>
                                        <span class="font-medium"><?= htmlspecialchars($waybill['product_invoice_number']) ?></span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-gray-600 font-bold">Invoice Amount:</span>
                                        <span class="font-medium"><?= KIT_Commons::currency() ?> <?= number_format($waybill['product_invoice_amount'], 2) ?></span>
                                    </div>
                                    <div class="flex items-center">
                                        <span class="text-gray-600 font-bold"></span>
                                        <span class="text-xs text-gray-500 italic">
                                            Waybill Amount + Misc Total
                                        </span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-gray-600 font-bold">Dispatch Date:</span>
                                        <span class="font-medium"><?= date('M j, Y', strtotime($waybill['dispatch_date'])) ?></span>
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-gray-600 font-bold">Truck Number:</span>
                                        <span class="font-medium"><?= htmlspecialchars($waybill['truck_number']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Route Information (Editable) -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Route Information</h2>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <label class="text-gray-600 font-bold">Origin:</label>
                                        <input type="text" name="origin_country" value="<?= htmlspecialchars($waybill['origin_country']) ?>" class="w-2/3 px-2 py-1 border rounded">
                                    </div>
                                    <div class="flex items-center">
                                        <label class="text-gray-600 font-bold">Destination Country:</label>
                                        <input type="text" name="destination_country" value="<?= htmlspecialchars($waybill['destination_country']) ?>" class="w-2/3 px-2 py-1 border rounded">
                                    </div>

                                    <div class="flex items-center">
                                        <label class="text-gray-600 font-bold">Dimensions (cm):</label>
                                        <div class="w-2/3 flex space-x-2">
                                            <input type="number" name="item_length" value="<?= htmlspecialchars($waybill['item_length']) ?>" class="w-1/3 px-2 py-1 border rounded" placeholder="Length">
                                            <input type="number" name="item_width" value="<?= htmlspecialchars($waybill['item_width']) ?>" class="w-1/3 px-2 py-1 border rounded" placeholder="Width">
                                            <input type="number" name="item_height" value="<?= htmlspecialchars($waybill['item_height']) ?>" class="w-1/3 px-2 py-1 border rounded" placeholder="Height">
                                        </div>
                                    </div>
                                    <div class="flex items-center">
                                        <label class="text-gray-600 font-bold">Total Mass (kg):</label>
                                        <input type="number" step="0.01" name="total_mass_kg" value="<?= htmlspecialchars($waybill['total_mass_kg']) ?>" class="w-2/3 px-2 py-1 border rounded">
                                    </div>
                                    <div class="flex items-center">
                                        <label class="text-gray-600 font-bold">Charge Basis:</label>
                                        <select name="charge_basis" class="w-2/3 px-2 py-1 border rounded">
                                            <option value="weight" <?= $waybill['charge_basis'] === 'weight' ? 'selected' : '' ?>>Weight</option>
                                            <option value="volume" <?= $waybill['charge_basis'] === 'volume' ? 'selected' : '' ?>>Volume</option>
                                            <option value="value" <?= $waybill['charge_basis'] === 'value' ? 'selected' : '' ?>>Value</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <?=
                            KIT_Commons::waybillItemsControl([
                                'container_id' => 'custom-waybill-items',
                                'button_id' => 'add-waybill-item',
                                'group_name' => 'custom_items',
                                'existing_items' => $waybill['items'],
                                'input_class' => 'border border-gray-300 rounded px-3 py-2 bg-white',
                                'remove_btn_class' => 'bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600',
                                'add_btn_class' => 'bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700',
                                'specialClass' => '!text-[10px]',
                            ]);
                            ?>


                            <!-- Miscellaneous Items Section (Editable) -->
                            <div class="bg-gray-50 p-4 rounded-lg mb-8">
                                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Miscellaneous89 Items</h2>
                                <?php
                                $misc = [];

                                if (!empty($waybill['miscellaneous'])) {
                                    $misc = $waybill['miscellaneous'] ?? [];
                                }
                                $misc_items = [];


                                // Check if misc data exists and has items
                                if (!empty($misc) && is_array($misc) && isset($misc['misc_items']) && is_array($misc['misc_items'])) {
                                    $misc_total = floatval($misc['misc_items']['misc_total'] ?? 0);

                                    foreach ($misc['misc_items'] as $item) {
                                        $name     = isset($item['misc_item'])  ? sanitize_text_field($item['misc_item'])  : '';
                                        $price    = isset($item['misc_price']) ? floatval($item['misc_price']) : 0;
                                        $quantity = isset($item['misc_quantity'])   ? intval($item['misc_quantity'])     : 1;
                                        $subtotal = $price * $quantity;

                                        $misc_items[] = [
                                            'misc_item'     => sanitize_text_field($name),
                                            'misc_price'    => $price,
                                            'misc_quantity' => $quantity,
                                            'misc_subtotal' => $subtotal,
                                        ];
                                    }
                                } else {
                                    $misc_total = 0;
                                }
                                ?>

                                <?php

                                echo KIT_Commons::miscItemsControl([
                                    'container_id' => 'misc-items',
                                    'button_id' => 'add-misc-item',
                                    'group_name' => 'misc',
                                    'input_class' => '',
                                    'existing_items' => $misc_items
                                ]);
                                ?>


                                <div class="mt-4 pt-4 border-t">
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium">Total Miscellaneous:</span>
                                        <span class="font-bold"><?= KIT_Commons::currency() ?> <?= number_format($misc['misc_total'] ?? 0, 2) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                // Add new misc item row
                                document.getElementById('add-misc-item').addEventListener('click', function() {
                                    const container = document.getElementById('misc-items-container');
                                    const newRow = document.createElement('div');
                                    newRow.className = 'flex items-center mb-2 misc-item-row';
                                    newRow.innerHTML = `
                                        <input type="text" name="misc_item[]" class="flex-1 px-3 py-2 border rounded-md mr-2" placeholder="Item description">
                                        <input type="number" step="0.01" name="misc_price[]" class="w-1/4 px-3 py-2 border rounded-md" placeholder="Amount">
                                        <button type="button" class="ml-2 px-3 py-2 bg-red-500 text-white rounded-md remove-misc-item">
                                            Remove
                                        </button>`;
                                    container.appendChild(newRow);
                                });

                                // Remove misc item row
                                document.addEventListener('click', function(e) {
                                    if (e.target.classList.contains('remove-misc-item')) {
                                        e.target.closest('.misc-item-row').remove();
                                    }
                                });
                            });
                        </script>
                    </form>
                </div><?php
                    }
                } else {
                    echo 'Waybill not found.';
                }
                ?></div><?php 
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
                        "SELECT w.*, u.user_login AS approved_by_username
                             FROM $table_name AS w
                             LEFT JOIN {$wpdb->users} AS u ON w.approval_userid = u.ID
                             WHERE w.id = %d",
                        $waybill_id
                    ));


                    if ($is_view_mode) {
                        // View mode - Modern design
                        echo '<div id="waybill-display" class="bg-white rounded-lg shadow-md p-6 max-w-7xl mx-auto">';

                        // Header section
                        echo '<div class="flex justify-between items-center mb-6 border-b pb-4">';
                        echo '<h2 class="text-2xl font-bold text-gray-800">Waybill #' . esc_html($waybill->waybill_no) . '</h2>';
                        echo '<div class="flex space-x-3">';
                        // If already approved, show the approver
                        if (! empty($waybill->approved_by_username)) {
                            echo '<div class="flex flex-col">';
                            echo '<p class="text-gray-600">Approved by:</p>';
                            echo '<div class="bg-slate-300 hover:bg-slate-700 rounded block text-gray text-center align-middle px-6 py-3">' . esc_html($waybill->approved_by_username) . '</div>';
                            echo '</div>';
                        }
                        // If not approved, and user is Admin or Manager, show button
                        elseif (in_array('administrator', $roles) || in_array('manager', $roles)) {
                            echo '<form method="POST" id="approvalBtn" action="' . esc_url(admin_url('admin-post.php')) . '">';
                            echo '<input type="hidden" name="action" value="approve_waybill">';
                            echo '<input type="hidden" name="waybill_id" value="' . esc_attr($waybill->id) . '">';
                            echo '<input type="hidden" name="delivery_id" value="' . esc_attr($waybill->delivery_id) . '">';
                            echo '<input type="hidden" name="user_id" value="' . esc_attr($current_user->ID) . '">';
                            echo '<button type="submit" class="bg-blue-500 hover:bg-blue-700 rounded block text-white text-center align-middle px-6 py-3">Approve Now</button>';
                            echo '</form>';
                        }
                        // Else show 'Not Approved'
                        else {
                            echo 'Not Approved';
                        }
                        echo '<button id="editWaybillBtn" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center">';
                        echo '<svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>';
                        echo 'Edit';
                        echo '</button>';
                        echo '<a href="' . admin_url('admin.php?page=08600-Waybill') . '" class="px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">Back to List</a>';
                        echo '</div></div>';

                        // Main content sections
                        echo '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">';

                        // Basic Information Section
                        echo '<div class="bg-gray-50 p-4 rounded-lg">';
                        echo '<h3 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Basic Information</h3>';
                        echo '<div class="space-y-3">';
                        echo self::render_detail_row('Waybill Number', $waybill->waybill_no);
                        echo self::render_detail_row('Invoice Number', $waybill->product_invoice_number);
                        echo self::render_detail_row('Customer ID', $waybill->customer_id);
                        echo '</div>';
                        echo '</div>';

                        // Dimensions Section
                        echo '<div class="bg-gray-50 p-4 rounded-lg">';
                        echo '<h3 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Dimensions</h3>';
                        echo '<div class="grid grid-cols-2 gap-3">';
                        echo self::render_detail_row('Length (cm)', $waybill->item_length);
                        echo self::render_detail_row('Width (cm)', $waybill->item_width);
                        echo self::render_detail_row('Height (cm)', $waybill->item_height);
                        //echo self::render_detail_row('Total Volume', $waybill->total_volume);
                        echo self::render_detail_row('Total Mass (kg)', $waybill->total_mass_kg);
                        echo '</div>';
                        echo '</div>';

                        // Charges Section
                        echo '<div class="bg-gray-50 p-4 rounded-lg">';
                        echo '<h3 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Charges</h3>';
                        echo '<div class="grid grid-cols-1 gap-3">';
                        echo self::render_detail_row('Charge Basis', $waybill->charge_basis);
                        echo (isset($waybill->mass_charge)) ? self::render_detail_row('Mass Charge', '$' . number_format($waybill->mass_charge, 2)) : '';
                        echo (isset($waybill->volume_charge)) ? self::render_detail_row('Volume Charge', '$' . number_format($waybill->volume_charge, 2)) : '';
                        echo '</div>';
                        echo '</div>';

                        // Miscellaneous Section
                        echo '<div class="bg-gray-50 p-4 rounded-lg">';
                        echo '<h3 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Miscellaneous Charges</h3>';
                        echo '<div class="grid grid-cols-2 gap-3">';
                        //$waybill->miscellaneous unserialize and display name + Price
                        $miscData = null;
                        if (!empty($waybill->miscellaneous)) {
                            $miscData = unserialize($waybill->miscellaneous);
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
                            href="' . admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $waybill->id . '&waybill_atts=view_waybill') . '"
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
                        href="' . admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $waybill->id . '&waybill_atts=view_waybill') . '"
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
        LEFT JOIN {$wpdb->prefix}kit_quotations as q ON w.id = q.waybill_id
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
                $items_table      = $prefix . 'kit_waybill_items';

                // PHASE 1: Waybill + related joins
                $waybill_sql = $wpdb->prepare(" SELECT 
                wb.id AS waybill_id,
                c.id AS customer_id,
                d.id AS delivery_id,
                dir.id AS direction_id,
                wb.direction_id,
                wb.delivery_id,
                wb.customer_id,
                wb.approval,
                wb.approval_userid,
                wb.waybill_no,
                wb.product_invoice_number,
                wb.product_invoice_amount,
                wb.item_length,
                wb.item_width,
                wb.item_height,
                wb.total_mass_kg,
                wb.total_volume,
                wb.mass_charge,
                wb.volume_charge,
                wb.charge_basis,
                wb.vat_number,
                wb.warehouse,
                wb.miscellaneous,
                wb.include_sad500,
                wb.include_sadc,
                c.name AS customer_name,
                c.surname AS customer_surname,
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
                FROM $waybills_table wb
                LEFT JOIN $customers_table c ON wb.customer_id = c.cust_id
                LEFT JOIN $deliveries_table d ON wb.delivery_id = d.id
                LEFT JOIN $directions_table dir ON wb.direction_id = dir.id
                LEFT JOIN $countries_table origin ON dir.origin_country_id = origin.id
                LEFT JOIN $countries_table dest ON dir.destination_country_id = dest.id
                WHERE wb.waybill_no = %d
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
            public static function update_waybill_action()
            {

                if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update_waybill_nonce')) {
                    wp_die('Security check failed.');
                }

                if (!current_user_can('edit_posts')) {
                    wp_die('You do not have sufficient permissions to access this page.');
                }

                global $wpdb;

                $waybills_table = $wpdb->prefix . 'kit_waybills';
                $items_table    = $wpdb->prefix . 'kit_waybill_items';

                $waybill_no     = intval($_POST['waybill_no']);

                // 🔄 Load current waybill (ensures waybill_id is correct from the DB)
                $existing = self::getFullWaybillWithItems($waybill_no);



                if (!$existing) {
                    wp_die('Waybill not found.');
                }

                $waybill_id = $existing->waybill['waybill_id'];

                // ✅ Update main waybill
                $waybill_data = [
                    'item_length'       => (($_POST['item_length'])) ?? $existing->waybill['item_length'],
                    'item_width'        => (($_POST['item_width'])) ?? $existing->waybill['item_width'],
                    'item_height'       => (($_POST['item_height'])) ?? $existing->waybill['item_height'],
                    'total_mass_kg'     => (($_POST['total_mass_kg'])) ?? $existing->waybill['total_mass_kg'],
                    'total_volume'      => (($_POST['total_volume'])) ?? $existing->waybill['total_volume'],
                    'charge_basis'      => (sanitize_text_field($_POST['charge_basis'])) ?? $existing->waybill['charge_basis'],
                    //'vat_number'        => (sanitize_text_field($_POST['vat_number']))?? $existing['waybill']['vat_number'],
                    //'warehouse'         => (sanitize_text_field($_POST['warehouse']))?? $existing['waybill']['warehouse'],
                    'last_updated_by'   => get_current_user_id(),
                    'last_updated_at'   => current_time('mysql'),
                ];

                $wpdb->update(
                    $waybills_table,
                    $waybill_data,
                    ['id' => $waybill_id],
                    null,
                    ['%d']
                );

                // ✅ Delete previous waybill items
                $wpdb->delete($items_table, ['waybillno' => $waybill_no]);

                // ✅ Insert new items (loop through submitted POST array)
                if (!empty($_POST['custom_items']) && is_array($_POST['custom_items'])) {
                    foreach ($_POST['custom_items'] as $item) {

                        if (!empty($item['item_name'])) {
                            $wpdb->insert($items_table, [
                                'waybillno'   => $waybill_no,
                                'item_name'   => sanitize_text_field($item['item_name']),
                                'quantity'    => intval($item['quantity']),
                                'unit_price'  => floatval($item['unit_price']),
                                'unit_mass'   => floatval($item['unit_mass'] ?? 0),
                                'unit_volume' => floatval($item['unit_volume'] ?? 0),
                                'total_price' => floatval($item['total_price'] ?? 0),
                                'created_at'  => current_time('mysql'),
                            ]);
                        }
                    }
                }

                self::miscItemsUpdateWaybill($_POST['misc'], $waybill_id, $waybill_no);

                // ✅ Redirect to success page
                wp_redirect(admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $waybill_id . '&updated=1'));
                exit;
            }

            // Save waybill items uusing custom_items
            public static function save_waybill_items($items, $waybill_no, $waybill_id)
            {
                global $wpdb;

                $table = $wpdb->prefix . 'kit_waybill_items';

                // Delete existing items for this waybill
                $wpdb->delete($table, ['waybillno' => $waybill_no]);

                // Insert new items
                foreach ($items as $item) {
                    if (!empty($item['item_name'])) {
                        $wpdb->insert($table, [
                            'waybillno'   => $waybill_no,
                            'item_name'   => sanitize_text_field($item['item_name']),
                            'quantity'    => intval($item['quantity']),
                            'unit_price'  => floatval($item['unit_price']),
                            'unit_mass'   => floatval($item['unit_mass'] ?? 0),
                            'unit_volume' => floatval($item['unit_volume'] ?? 0),
                            'total_price' => floatval($item['total_price'] ?? 0),
                            'created_at'  => current_time('mysql'),
                        ]);
                    }
                }
            }

            public static function  miscItemsUpdateWaybill($misc, $waybill_id, $waybill_no)
            {
                global $wpdb;

                $table = $wpdb->prefix . 'kit_waybills';

                // Use getMiscCharges function instead of manual handling
                $misc_result = self::getMiscCharges($misc, []);
                $misc_items = $misc_result->misc_items;
                $misc_total = floatval($misc_result->misc_total);

                // Serialize the misc items array
                $serialized_misc = maybe_serialize([
                    'misc_items' => $misc_items,
                    'misc_total' => $misc_total,
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
                return ob_get_clean();
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
                        $current_user = wp_get_current_user();
                        $roles = (array) $current_user->roles;

                        // If user is administrator, show QuotationStatus
                        if (in_array('administrator', $roles)) {
                            return KIT_Commons::QuotationStatus($item->waybill_no, $item->id, 'quoted', $item->approval, 'select');
                        } else {
                            // For non-administrators, show approval status
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
                        return 'R' . ($value ? number_format($value, 2) : '0.00');

                    case 'items':
                        $item_count = is_array($value) ? count($value) : (int)$value;
                        return '<button class="toggle-items text-blue-600 hover:text-blue-800 flex items-center" data-item-id="' . ($item->id ?? '') . '">' .
                            $item_count . ' item' . ($item_count !== 1 ? 's' : '') .
                            '<svg class="w-4 h-4 ml-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">' .
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>' .
                            '</svg></button>';

                    case 'actions':
                        return self::renderDefaultActions($item);

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
                        echo '<td>' . esc_html($waybill->waybill_no) . '</td>';
                        break;
                    case 'customer':
                        echo '<td>' . esc_html($waybill->customer_name) . '</td>';
                        break;
                    case 'dispatch_date':
                        echo '<td>' . (!empty($waybill->dispatch_date) ? date('M j, Y', strtotime($waybill->dispatch_date)) : '-') . '</td>';
                        break;
                    // ... add more columns as needed ...
                    case 'actions':
                        echo '<td>';
                        // View action (everyone)
                        echo '<a href="' . admin_url('admin.php?page=08600-Waybill-view&waybill_id=' . $waybill->id) . '" class="btn btn-sm btn-primary">View</a> ';
                        // Generate Quotation (admin only)
                        if (in_array('administrator', $roles)) {
                            echo '<a href="' . admin_url('admin.php?page=generate-quotation&waybill_id=' . $waybill->id) . '" class="btn btn-sm btn-success">Generate Quotation</a> ';
                        }
                        // Delete (admin only)
                        if (in_array('administrator', $roles)) {
                            echo '<a href="' . admin_url('admin-post.php?action=delete_waybill&waybill_id=' . $waybill->id) . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
                        }
                        echo '</td>';
                        break;
                    default:
                        // fallback for any other column
                        echo '<td>' . (isset($waybill->$col) ? esc_html($waybill->$col) : '-') . '</td>';
                }
            }
            echo '</tr>';
        }
