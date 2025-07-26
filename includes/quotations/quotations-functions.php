<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

global $wpdb;
$table_quotations = $wpdb->prefix . "kit_quotations";
$table_waybills = $wpdb->prefix . "kit_waybills";

class KIT_Quotations
{
    public static function init()
    {
        // In your main plugin file (e.g., my-plugin/my-plugin.php)
        add_action('wp_ajax_generate_pdf', [self::class, 'handle_pdf_generation']);
        add_action('wp_ajax_nopriv_generate_pdf', [self::class, 'handle_pdf_generation']); // For public access
        add_action('wp_ajax_create_quotation_from_waybill', [self::class, 'createQuotation_from_waybill_ajax']);
        add_action('wp_ajax_nopriv_create_quotation_from_waybill', [self::class, 'createQuotation_from_waybill_ajax']);
    }

    public static function calculate_waybill_estimate_by_id($waybill_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_waybills';

        $waybill = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $waybill_id)
        );

        if (! $waybill) return 0;

        // Extract fields
        $mass = floatval($waybill->total_mass_kg);
        $length = floatval($waybill->item_length);
        $width = floatval($waybill->item_width);
        $height = floatval($waybill->item_height);
        $charge_basis = strtoupper($waybill->charge_basis);
        $misc_raw = maybe_unserialize($waybill->miscellaneous); // Important!

        // Calculate volume in m³
        $volume = ($length * $width * $height) / 1000000;

        // MASS rates
        if ($mass >= 10 && $mass <= 500) $mass_rate = 40;
        elseif ($mass <= 1000) $mass_rate = 35;
        elseif ($mass <= 2500) $mass_rate = 30;
        elseif ($mass <= 5000) $mass_rate = 25;
        elseif ($mass <= 7500) $mass_rate = 20;
        elseif ($mass <= 10000) $mass_rate = 17.5;
        else $mass_rate = 15;

        $mass_charge = $mass * $mass_rate;

        // VOLUME rates
        if ($volume <= 1) $volume_rate = 7500;
        elseif ($volume <= 2) $volume_rate = 7000;
        elseif ($volume <= 5) $volume_rate = 6500;
        elseif ($volume <= 10) $volume_rate = 5500;
        else $volume_rate = 5000;

        $volume_charge = $volume * $volume_rate;

        // Determine base charge
        if ($charge_basis === 'MASS') {
            $main_charge = $mass_charge;
        } elseif ($charge_basis === 'VOLUME') {
            $main_charge = $volume_charge;
        } else {
            $main_charge = max($mass_charge, $volume_charge);
        }

        // Handle miscellaneous
        $misc_total = 0;
        if (is_array($misc_raw)) {
            foreach ($misc_raw as $item) {
                $misc_total += floatval($item['price'] ?? 0);
            }
        }

        $final_total = $main_charge + $misc_total;

        return round($final_total, 2);
    }

    public static function get_AllQuotedWaybill()
    {
        global $wpdb, $table_quotations;

        $query = "SELECT COUNT(*) as total_quotations FROM $table_quotations";
        $result = $wpdb->get_var($query);
        return intval($result);
    }

    public static function get_AllPendingWaybill()
    {
        global $wpdb, $table_waybills;

        $query = "SELECT COUNT(*) as total_pending_quotations FROM $table_waybills WHERE status = 'pending'";
        $result = $wpdb->get_var($query);
        return intval($result);
    }

    public static function convertToQuotation($waybillno)
    {
        global $wpdb;

        // Step 1: Get the waybill
        $waybill = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kit_waybills WHERE `waybill_no` = %d",
            $waybillno
        ));

        if (!$waybill) {
            return new WP_Error('invalid_waybill', 'Waybill not found');
        }

        // Step 2: If already quoted, block
        if ($waybill->approval === 'approved') {
            //return new WP_Error('already_quoted', 'This waybill has already been quoted');
        }

        // Step 3: Auto-approve pending waybills
        if ($waybill->approval === 'pending') {
            $wpdb->update(
                "{$wpdb->prefix}kit_waybills",
                [
                    'approval' => 'approved',
                    'last_updated_by' => get_current_user_id(),
                    'last_updated_at' => current_time('mysql')
                ],
                ['id' => $waybill_id],
                ['%s', '%d', '%s'],
                ['%d']
            );
            // Refresh updated status
            $waybill->approval = 'approved';
        }

        // Update waybill status to quoted only id approval is approved
        if ($waybill->approval === 'approved') {
            $waybill_updated = $wpdb->update(
                "{$wpdb->prefix}kit_waybills",
                [
                    'status' => 'invoiced',
                    'last_updated_by' => get_current_user_id(),
                    'last_updated_at' => current_time('mysql')
                ],
                ['waybill_no' => $waybillno],
                ['%s', '%d', '%s'],
                ['%d']
            );

            return $waybill_updated;
        }

    }

    public static function createQuotation_from_waybill_ajax()
    {

        // Verify nonce first
        if (!check_ajax_referer('kit_waybill_nonce', '_ajax_nonce', false)) {
            wp_send_json_error('Invalid nonce', 403);
            wp_die();
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
        }

        $waybill_id = isset($_POST['waybill_id']) ? intval($_POST['waybill_id']) : 0;

        if (!$waybill_id) {
            wp_send_json_error('Waybill ID required', 400);
        }

        // Call your quotation conversion function
        $result = self::convertToQuotation($waybill_id);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success([
            'message' => 'Quotation created successfully',
            'quotation_id' => $result
        ]);
    }

    // ✅ Get all quotations
    public static function kit_get_all_quotations()
    {
        global $wpdb, $table_quotations;

        $query = "
            SELECT q.*, c.name AS customer_name, c.surname AS customer_surname, w.id AS waybill_id, w.waybill_no, w.product_invoice_amount, w.miscellaneous, w.charge_basis, w.mass_charge, w.volume_charge
            FROM $table_quotations q
            LEFT JOIN {$wpdb->prefix}kit_customers c ON q.customer_id = c.cust_id
            LEFT JOIN {$wpdb->prefix}kit_waybills w ON q.waybill_id = w.id
        ";
        return $wpdb->get_results($query, ARRAY_A);
    }

    public static function generate_quotation($waybill_no)
    {
        global $wpdb;

        // Get waybill data
        $waybill = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}kit_waybills WHERE waybill_no = %d", $waybill_no)
        );

        if (!$waybill) {
            return false;
        }

        // if $waybill->miscellaneous is not empty, then unserialize it
        if (!empty($waybill->miscellaneous)) {
            $misc_combined = unserialize($waybill->miscellaneous);
            $misc_total = $misc_combined['total'] ?? 0;
        } else {
            $misc_total = 0;
        }

        $subtotal = $waybill->product_invoice_amount;

        // Prepare quotation data

        $quotation_data = [
            'delivery_id'      => $waybill->delivery_id,
            'waybill_id'       => $waybill->id,
            'waybillno'      => $waybill->waybill_no,
            'customer_id'      => $waybill->customer_id,
            'subtotal'         => 0,
            'vat_amount'       => 0,
            'total'            => $waybill->product_invoice_amount || 0,
            'quotation_notes'  => sprintf('Generated from waybill #%s', $waybill->waybill_no),
            'status'           => 'pending',
            'created_by'       => get_current_user_id(),
            'created_at'       => current_time('mysql'),
            'last_updated_by'  => get_current_user_id(),
            'last_updated_at'  => current_time('mysql')
        ];

        $wpdb->query('START TRANSACTION');

        // Insert into quotations table
        $quotation_inserted = $wpdb->insert(
            "{$wpdb->prefix}kit_quotations",
            $quotation_data,
            [
                '%d', //delivery_id
                '%d', //waybill_id
                '%d', //waybillno
                '%d', //customer_id
                '%d', //subtotal
                '%d', //vat_amount
                '%d', //total
                '%s', //quotation_notes
                '%s', //status
                '%d', //created_by
                '%s', //created_at
                '%d', //last_updated_by
                '%s', //last_updated_at
            ]
        );

        if ($quotation_inserted) {

            // Update waybill status to 'quoted'
            $wpdb->update(
                $wpdb->prefix . "kit_waybills",
                ['status' => 'quoted'],
                ['waybill_no' => $waybill->waybill_no],
                ['%s'],
                ['%d']
            );
            $wpdb->query('COMMIT');

            return $wpdb->insert_id;
        }

        return false;
    }

    // Function to check if the waybill.status is 'quoted'
    // Return True if 'quoted' else false
    public static function checkWaybillStatus($waybill_no)
    {
        global $wpdb;

        // Get the waybill status
        $status = $wpdb->get_var(
            $wpdb->prepare("SELECT status FROM {$wpdb->prefix}kit_waybills WHERE waybill_no = %d", $waybill_no)
        );

        // Check if the status is 'quoted'
        if ($status === 'quoted') {
            return true;
        }

        return false;
    }


    public static function handle_pdf_generation()
    {

        check_ajax_referer('pdf_nonce', 'pdf_nonce');

        $quotation_id = null;
        // Get quotation ID
        $waybill_no = isset($_GET['waybill_no']) ? intval($_GET['waybill_no']) : 0;
        if (self::checkWaybillStatus($waybill_no)) {
            global $wpdb;
            $quotation_id = $wpdb->get_var(
                $wpdb->prepare("SELECT id FROM {$wpdb->prefix}kit_quotations WHERE waybillNo = %d", $waybill_no)
            );
        } else {
            // Generate quotation from waybill
            $quotation_id = self::generate_quotation($waybill_no);

            if (!$quotation_id) {
                echo 'Error generating quotation';
                exit;
            }
        }

        $quotation_id_global = $quotation_id; // Define a new variable
        // Include your PDF generator
        include COURIER_FINANCE_PLUGIN_PATH . 'pdf-generator.php';
        wp_die(); // Terminate
    }
}
KIT_Quotations::init();



/**
 * Generate quotation from waybill
 */
function kit_generate_quotation_from_waybill($waybill_id)
{
    global $wpdb;

    // Get waybill data
    $waybill = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}kit_waybills WHERE id = %d", $waybill_id)
    );

    if (!$waybill) {
        return false;
    }

    // Calculate charges based on waybill data
    $subtotal = 0;
    $total = 0;

    if ($waybill->charge_basis === 'MASS') {
        $subtotal = $waybill->mass_charge;
    } elseif ($waybill->charge_basis === 'VOLUME') {
        $subtotal = $waybill->volume_charge;
    } elseif ($waybill->charge_basis === 'BOTH') {
        $subtotal = max($waybill->mass_charge, $waybill->volume_charge);
    }

    // Apply any additional calculations here (taxes, discounts, etc.)
    $total = $subtotal;
    // Prepare quotation data
    $quotation_data = [
        'delivery_id' => $waybill->delivery_id,
        'waybill_id' => $waybill->id,
        'customer_id' => $waybill->customer_id,
        'subtotal' => $subtotal,
        'miscellaneous' => $waybill->miscellaneous,
        'total' => $total,
        'created_by' => wp_get_current_user()->user_login,
        'status' => 'pending',
        'created_at' => current_time('mysql')
    ];



    return $quotation_data;
}

/**
 * Updated quotation handler
 */
add_action('admin_post_kit_add_quotation', 'kit_add_quotation');

function kit_add_quotation()
{
    if (!isset($_POST['kit_add_quotation_nonce']) || !wp_verify_nonce($_POST['kit_add_quotation_nonce'], 'kit_add_quotation_action')) {
        wp_die('Security check failed');
    }

    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    global $wpdb;
    $table_quotations = $wpdb->prefix . 'kit_quotations';

    // Check if we're generating from a waybill
    if (isset($_POST['waybill_id']) && !empty($_POST['waybill_id'])) {
        $waybill_id = intval($_POST['waybill_id']);
        $quotation_data = kit_generate_quotation_from_waybill($waybill_id);

        if (!$quotation_data) {
            wp_die('Invalid waybill ID');
        }
    } else {
        // Handle manual quotation creation (your existing code)
        $quotation_data = kit_handle_quotation_data('POST');



        if (empty($quotation_data['customer_name'])) {
            wp_die('Required fields are missing.');
        }
    }

    // Insert the quotation
    $inserted = $wpdb->insert(
        $table_quotations,
        $quotation_data,
        [
            '%s', // delivery_id
            '%s', // waybill_id 
            '%s', // customer_id 
            '%s', // subtotal 
            '%s', // miscellaneous
            '%s', // total
            '%s', // created_by
            '%s', // status
            '%s'  // created_at
        ]
    );
    if (!$inserted) {
        wp_die('Error creating quotation');
    }

    $last_inserted_id = $wpdb->insert_id;
    wp_redirect(admin_url('admin.php?page=kit-quotation-edit&quotation_id=' . $last_inserted_id . '&message=success'));
    exit;
}

/**
 * Add a button to waybill view to generate quotation
 */
add_action('admin_init', function () {
    if (isset($_GET['generate_quotation']) && isset($_GET['waybill_id'])) {
        $waybill_id = intval($_GET['waybill_id']);
        $quotation_data = kit_generate_quotation_from_waybill($waybill_id);

        if ($quotation_data) {
            global $wpdb;
            $table_quotations = $wpdb->prefix . 'kit_quotations';

            $inserted = $wpdb->insert(
                $table_quotations,
                $quotation_data,
                [
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s',
                    '%s'
                ]
            );

            if ($inserted) {
                $last_inserted_id = $wpdb->insert_id;
                wp_redirect(admin_url('admin.php?page=kit-quotation-edit&quotation_id=' . $last_inserted_id));
                exit;
            }
        }

        wp_die('Error generating quotation from waybill');
    }
});

/**
 * Add generate quotation button to waybill view
 */
add_filter('kit_waybill_actions', function ($actions, $waybill_id) {
    $actions['generate_quotation'] = [
        'url' => admin_url('admin.php?page=kit-waybill-list&generate_quotation=1&waybill_id=' . $waybill_id),
        'label' => 'Generate Quotation',
        'class' => 'button-primary'
    ];
    return $actions;
}, 10, 2);


// ✅ Get Quotation by ID
function kit_get_quotation_by_id($id)
{
    global $wpdb, $table_quotations;
    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $table_quotations WHERE id = %d", intval($id)),
        ARRAY_A
    );
}

// ✅ Update Quotation
add_action('admin_post_kit_update_quotation', 'kit_update_quotation');



function kit_update_quotation()
{
    // Security checks
    if (!isset($_POST['kit_quotation_nonce']) || !wp_verify_nonce($_POST['kit_quotation_nonce'], 'kit_edit_quotation_action')) {
        wp_die('Security check failed');
    }
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    global $wpdb, $table_quotations;
    $quotation_id = intval($_POST['quotation_id']);
    $data = kit_handle_quotation_data('POST');

    if (empty($quotation_id)) {
        wp_die('Invalid quotation ID.');
    }

    // Corrected update statement
    $result = $wpdb->update(
        $table_quotations,
        $data,
        ['id' => $quotation_id],
        [
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%f',
            '%f',
            '%f',
            '%f',
            '%s',
            '%s',
            '%f',
            '%f',
            '%f',
            '%d',
            '%d',
            '%d'
        ],
        ['%d']
    );

    if ($result === false) {
        wp_die('Error updating quotation');
    }

    wp_redirect(admin_url('admin.php?page=kit-quotation-edit&quotation_id=' . $quotation_id . '&message=updated'));
    exit;
}

// ✅ Shortcode to display all quotations
function kit_get_all_quotations_table()
{
    $quotations = KIT_Waybills::get_waybills(array(
        'approval' => 'approved',
        'custom_format' => true
    ));

    $approvedWaybills = KIT_Waybills::get_waybills(array(
        'approval' => 'approved',
        'custom_format' => true
    ));


    ob_start();
?>
    <div class="">
        <!-- Dashboard Header -->
        <div class="bg-white p-6 rounded border-2 border-1 flex flex-col md:flex-row justify-between items-start md:items-center gap-6 mb-6">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 w-full md:w-auto">
                <div class="bg-slate-200 rounded border-2 p-4 flex items-center gap-4">
                    <div>
                        <h3 class="text-xs font-medium text-gray-500">Pending</h3>
                        <p class="text-2xl font-bold text-gray-900 text-center">
                            <?php echo KIT_Quotations::get_AllPendingWaybill(); ?></p>
                    </div>
                </div>

                <div class="bg-slate-200 rounded border-2 p-4 flex items-center gap-4">

                    <div>
                        <h3 class="text-xs font-medium text-gray-500">QuotedD</h3>
                        <p class="text-2xl font-bold text-gray-900 text-center">
                            <?php echo KIT_Quotations::get_AllQuotedWaybill(); ?></p>
                    </div>
                </div>

                <div class="bg-slate-200 rounded border-2 p-4 flex items-center gap-4">

                    <div>
                        <h3 class="text-xs font-medium text-gray-500">Invoiced</h3>
                        <p class="text-2xl font-bold text-gray-900 text-center">
                            <?php echo 0; ?></p>
                    </div>
                </div>
            </div>
        </div>


        <div class="grid grid-cols-2 gap-4">
            <div class="bg-white p-6 rounded border-2">
                <h4 class="text-lg font-bold text-gray-800 mb-1">Pending Waybills</h4>
                <p class="text-gray-500 mb-4">
                    Below is a list of all waybills that are currently <span class="font-semibold text-yellow-600">pending</span> and have not yet been quoted. Review and generate quotations as needed.
                </p>
                <!-- All waybills with no quotations, wp_kit_waybills`.`status = pending -->
                <?php
                $pendingWaybills = KIT_Waybills::getWaybillsStatusPending();

                $options = [
                    'itemsPerPage' => 5,
                    'currentPage' => $_GET['paged'] ?? 1,
                    'tableClass' => 'min-w-full text-left text-xs text-gray-700',
                    'emptyMessage' => 'No customers records found',
                    'id' => 'customerTable',

                ];


                $columns = [
                    'waybill_no' => ['label' => 'Waybill #', 'align' => 'text-left'],
                    'customer_name' => ['label' => 'Name', 'align' => 'text-left'],
                    'charge_basis' => ['label' => 'Charge Basis', 'align' => 'text-left'],
                    'total' => ['label' => 'Total', 'align' => 'text-right'],
                    'actions' => ['label' => 'Actions', 'align' => 'text-center'],
                ];

                $waybill_actions = function ($key, $row) {
                    if ($key === 'waybill_no') {
                        return  '<a href="?page=08600-Waybill-view&waybill_id=' . $row->waybill_id . '&waybill_atts=view_waybill" target="_blank" class="text-blue-600 hover:underline">' . $row->waybill_no . '</a>';
                    }
                    if ($key === 'status') {
                        return  KIT_Commons::statusBadge($row->status);
                    }
                    
                    if ($key === 'customer_name') {
                        return $row->customer_name . ' ' . $row->customer_surname;
                    }
                    if ($key === 'total') {
                        return KIT_Commons::currency() . ' ' . ((int) $row->product_invoice_amount + (int) $row->miscellaneous);
                    }
                    if ($key === 'approval') {
                        return KIT_Commons::statusBadge($row->approval);
                    }
                    if ($key === 'actions') {
                        $html = '<a href="?page=08600-Waybill-view&waybill_id=' . $row->waybill_id . '&waybill_atts=view_waybill" class="text-blue-600 hover:underline">View</a> ';
                        $html .= '<a href="?page=08600-Waybill&delete_waybill=' . $row->waybill_no . '" class="text-red-600 hover:underline" onclick="return confirm(\'Are you sure you want to delete this waybill?\');">Delete</a>';
                        return $html;
                    }
                    return htmlspecialchars(($row->$key ?? '') ?: '');
                };

                echo KIT_Commons::render_versatile_table($pendingWaybills, $columns, $waybill_actions, $options);

                ?>
            </div>
            <div class="bg-white p-6 rounded border-2">
                <h4 class="text-lg font-bold text-gray-800 mb-1">Quoted Waybills</h4>
                <p class="text-gray-500 mb-4">
                    Below is a list of all quotations for waybills.
                </p>
                <!-- All waybills with no quotations, wp_kit_waybills`.`status = quoted and quotation -->
                <?php
                $quotations = KIT_Quotations::kit_get_all_quotations();
    
                $options = [
                    'itemsPerPage' => 5,
                    'currentPage' => $_GET['paged'] ?? 1,
                    'tableClass' => 'min-w-full text-left text-xs text-gray-700',
                    'emptyMessage' => 'No customers records found',
                    'id' => 'customerTable',

                ];

                $columns = [
                    'waybill_no' => ['label' => 'Waybill #', 'align' => 'text-left'],
                    'customer_name' => ['label' => 'Name', 'align' => 'text-left'],
                    'charge_basis' => ['label' => 'Charge Basis', 'align' => 'text-left'],
                    'total' => ['label' => 'Total', 'align' => 'text-right'],
                    'actions' => ['label' => 'Actions', 'align' => 'text-center'],
                ];


                $waybill_actions = function ($key, $row) {
                    if ($key === 'charge_basis') {
                        return $row['charge_basis'];
                    }   
                    if ($key === 'customer_name') {
                        return $row['customer_name'] . ' ' . $row['customer_surname'];
                    }   
                    if ($key === 'waybill_no') {
                        // link to waybill view example: ?href="?page=08600-Waybill-view&waybill_id=7&waybill_atts=view_waybill"
                        return '<a href="?page=08600-Waybill-view&waybill_id=' . $row['waybill_id'] . '&waybill_atts=view_waybill" target="_blank" class="text-blue-600 hover:underline">' . $row['waybillNo'] . '</a>';
                    }

                    if ($key === 'total') {
                        return KIT_Commons::currency() . ' ' . ((int) $row['product_invoice_amount'] + (int) ($row['miscellaneous'] ?? 0));
                    }
                    if ($key === 'actions') {
                        //$html download PDF button
                        $html = '';
                        $html .= '<div class="flex">';
                        $html .= '<a href="?page=08600-Waybill-view&waybill_id=' . $row['waybill_id'] . '" class="text-blue-600 hover:underline">View</a> ';
                        $html .= '<a href="?page=waybill-dashboard&delete_waybill=' . $row['waybillNo'] . '" class="text-red-600 hover:underline" onclick="return confirm(\'Are you sure you want to delete this waybill?\');">Delete</a>';
                        $html .= '</div>';

                        return $html;
                    }
   
                };

                echo KIT_Commons::render_versatile_table($quotations, $columns, $waybill_actions, $options);
                ?>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
}

// ✅ Delete Quotation
add_action('admin_post_kit_delete_quotation', 'kit_delete_quotation');
function kit_delete_quotation()
{
    if (!isset($_POST['kit_delete_quotation_nonce']) || !wp_verify_nonce($_POST['kit_delete_quotation_nonce'], 'kit_delete_quotation_action')) {
        wp_die('Security check failed');
    }
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }

    global $wpdb, $table_quotations;
    $quotation_id = isset($_POST['quotation_id']) ? intval($_POST['quotation_id']) : 0;

    if (empty($quotation_id)) {
        wp_die('Invalid quotation ID.');
    }

    $wpdb->delete($table_quotations, ['id' => $quotation_id], ['%d']);

    // Redirect back to the appropriate page
    $redirect_url = admin_url('admin.php?page=Quotations');
    if (isset($_POST['redirect_page'])) {
        $redirect_url = admin_url('admin.php?page=' . sanitize_text_field($_POST['redirect_page']));
    }

    wp_redirect($redirect_url);
    exit;
}
