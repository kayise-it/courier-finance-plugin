<?php
if (!defined('ABSPATH')) {
    exit;
}

class KIT_Deliveries
{
    public static function init()
    {
        add_action('admin_menu', [self::class, 'add_admin_menu']);
        add_action('wp_ajax_kit_deliveries_crud', [self::class, 'handle_ajax']);
        add_action('admin_post_kit_deliveries_crud', [self::class, 'handle_ajax']);
        add_action('wp_ajax_kit_deliveries_crud', [self::class, 'handle_ajax']);
        add_action('wp_ajax_delivery_changeTo_Intransit', [self::class, 'delivery_changeTo_Intransit']);
        add_action('wp_ajax_delivery_changeTo_Delivered', [self::class, 'delivery_changeTo_Delivered']);
        add_action('wp_ajax_delivery_changeTo_Scheduled', [self::class, 'delivery_changeTo_Scheduled']);
        add_action('wp_ajax_get_scheduled_deliveries', [self::class, 'getScheduledDeliveries']);
        add_action('wp_ajax_get_customers', [self::class, 'get_customers']);
        add_action('wp_ajax_get_deliveries_by_country', [self::class, 'handle_get_deliveries_by_country']);
        add_action('wp_ajax_nopriv_get_deliveries_by_country', [self::class, 'handle_get_deliveries_by_country']);
        add_action('wp_ajax_get_deliveries_by_country_id', [self::class, 'handle_get_deliveries_by_country_id']);
        add_action('wp_ajax_nopriv_get_deliveries_by_country_id', [self::class, 'handle_get_deliveries_by_country_id']);
        add_shortcode('country_select', [self::class, 'CountrySelect']);
        add_action('wp_ajax_handle_get_cities_for_country', [self::class, 'handle_get_cities_for_country']);
        add_action('wp_ajax_handle_get_countryDeliveries', [self::class, 'handle_get_countryDeliveries_callback']);
        add_action('wp_ajax_handle_get_price_per_kg', [self::class, 'handle_get_price_per_kg']);
        add_action('wp_ajax_nopriv_handle_get_price_per_kg', [self::class, 'handle_get_price_per_kg']);
        add_action('wp_ajax_handle_get_price_per_m3', [self::class, 'handle_get_price_per_m3']);
        add_action('wp_ajax_nopriv_handle_get_price_per_m3', [self::class, 'handle_get_price_per_m3']);
        add_action('wp_ajax_handle_get_cities_for_country', [self::class, 'handle_get_cities_for_country_callback']);
        add_action('wp_ajax_nopriv_handle_get_cities_for_country', [self::class, 'handle_get_cities_for_country_callback']);
    }
    public static function shippingDirections()
    {
        //Use table wp_kit_shipping_directions
        global $wpdb;
        $table = $wpdb->prefix . 'kit_shipping_directions';
        $query = "SELECT id, origin_country_id, destination_country_id, description, is_active, created_at FROM $table";
        return $wpdb->get_results($query);
    }

    public static function getDirectionId($delivery_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_deliveries';
        $query = $wpdb->prepare("SELECT direction_id FROM $table WHERE id = %d", $delivery_id);
        return $wpdb->get_var($query);
    }

    public static function handle_get_price_per_m3()
    {
        global $wpdb;

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'get_waybills_nonce')) {
            wp_send_json_error(['message' => 'Invalid security token.']);
        }

        $direction_id = isset($_POST['direction_id']) ? intval($_POST['direction_id']) : 0;
        $total_volume_m3 = isset($_POST['total_volume_m3']) ? floatval($_POST['total_volume_m3']) : 0;
        $chargeGroup = KIT_Waybills::chargeGroup($_POST['origin_country_id']);

        if (!$chargeGroup || !$total_volume_m3) {
            wp_send_json_error(['message' => 'Missing chargeGroup or volume.']);
        }
        

        $table = $wpdb->prefix . 'kit_shipping_rates_volume';

        $rate_per_m3 = $wpdb->get_var($wpdb->prepare("
        SELECT rate_per_m3
        FROM $table
        WHERE direction_id = %d
          AND %f BETWEEN min_volume AND max_volume
        LIMIT 1", $chargeGroup, $total_volume_m3));

        if ($rate_per_m3 !== null) {
            wp_send_json_success(['rate_per_m3' => $rate_per_m3]);
        } else {
            wp_send_json_error(['message' => 'No matching volumetric rate found.']);
        }
    }

    public static function handle_get_price_per_kg()
    {
        global $wpdb;

        /*   
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'get_waybills_nonce')) {
            wp_send_json_error(['message' => 'Invalid security token.']);
        } 
            */


        $chargeGroup = KIT_Waybills::chargeGroup($_POST['origin_country_id']);

        $direction_id = isset($_POST['direction_id']) ? intval($_POST['direction_id']) : 0;
        $total_mass_kg = isset($_POST['total_mass_kg']) ? floatval($_POST['total_mass_kg']) : 0;


        if (!$total_mass_kg) {
            wp_send_json_error(['message' => 'Missing direction_id or total_mass_kg.']);
        }

        $table = $wpdb->prefix . 'kit_shipping_rates_mass';

        // safer than BETWEEN
        $rate_per_kg = $wpdb->get_var($wpdb->prepare("
        SELECT rate_per_kg
        FROM $table
        WHERE direction_id = %d
          AND min_weight <= %f
          AND max_weight >= %f
        ORDER BY effective_date DESC
        LIMIT 1", $chargeGroup, $total_mass_kg, $total_mass_kg));

        if ($rate_per_kg !== null) {

            wp_send_json_success([
                'rate_per_kg' => $rate_per_kg,
                'total_charge' => round($rate_per_kg * $total_mass_kg, 2)
            ]);
        } else {
            wp_send_json_error(['message' => 'No matching rate found.']);
        }
    }

    public static function handle_get_countryDeliveries_callback()
    {

        check_ajax_referer('get_waybills_nonce', 'nonce');

        $country_id = isset($_POST['country_id']) ? ($_POST['country_id']) : 0;


        if (!$country_id) {
            wp_send_json_error(['message' => 'Missing country country_id']);
        }

        $deliveries = KIT_Deliveries::getScheduledCountryDeliveries($country_id);

        wp_send_json_success($deliveries);
    }
    public static function kit_get_Cities_forCountry()
    {
        if (! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'get_waybills_nonce')) {
            wp_send_json_error('Invalid nonce');
        }

        $country_id = intval($_POST['country_id']);

        if (! $country_id) {
            wp_send_json_error('Invalid country ID');
        }

        $cities = KIT_Deliveries::get_Cities_forCountry($country_id);

        if (! is_array($cities)) {
            wp_send_json_error('No cities found');
        }

        wp_send_json_success($cities);
    }
    public static function handle_get_cities_for_country()
    {
        // Verify nonce
        check_ajax_referer('get_waybills_nonce', 'nonce');

        if (!isset($_POST['country_id'])) {
            wp_send_json_error('Country ID is required');
        }

        $country_id = sanitize_text_field($_POST['country_id']);

        // Replace this with your actual database query
        global $wpdb;
        $cities = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kit_operating_cities WHERE country_id = %s",
            $country_id
        ));

        if ($wpdb->last_error) {
            wp_send_json_error($wpdb->last_error);
        }

        wp_send_json_success($cities);
    }
    public static function generateDeliveryRef()
    {
        //example of ref DEL-20250601-001
        //Check if the reference in the delivery table exists before
        global $wpdb;
        $table = $wpdb->prefix . 'kit_deliveries';

        $date = date('Ymd');
        $counter = 1;

        do {
            $ref = sprintf('DEL-%s-%03d', $date, $counter);
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE delivery_reference = %s",
                $ref
            ));
            $counter++;
        } while ($exists);

        return $ref;
    }
    public static function add_admin_menu()
    {
        // Menu registration moved to unified 08600 Waybills menu
        // Individual menu registration commented out to avoid conflicts
        
        /*
        add_menu_page(
            'Deliveries Management',
            'Deliveries',
            'edit_pages',
            'kit-deliveries',
            [__CLASS__, 'render_admin_page'],
            'dashicons-car',
            6
        );
        add_submenu_page(
            'Deliveries',       // Parent slug (e.g., under Pages)
            'View Delivery',                // Page title
            '',                // Menu title
            'edit_pages',                // Capability
            'view-deliveries',         // Menu slug
            [__CLASS__, 'view_deliveries_page']           // Callback function to display the page
        );
        */

        //Create a packing list for the deliver. So it must show waybills for the current delivery truck and then show the destinations
        //Group the packing list based on the destination, infuture this will allow us to create a route for delivery


    }
    public static function handle_get_deliveries_by_country()
    {
        check_ajax_referer('deliveries_nonce', 'nonce');

        $country_code = sanitize_text_field($_POST['country']);

        if (empty($country_code)) {
            wp_send_json_error(['message' => 'Country code is required']);
        }

        $deliveries = KIT_Deliveries::getScheduledDeliveries($country_code);

        ob_start();
        foreach ($deliveries as $delivery): ?>
            <label class="bg-white w-[100px] h-[100px] rounded-[5px] border-2 border-gray-300 cursor-pointer relative flex items-center justify-center text-center text-[11px] font-medium leading-tight hover:shadow-md transition-all duration-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-100 has-[:checked]:shadow-lg active:border-blue-500 active:bg-blue-100 active:shadow-lg">
                <input type="radio" name="delivery_id" value="<?= esc_attr($delivery->id) ?>" class="sr-only peer" <?= $delivery->id == 1 ? 'checked' : '' ?>>
                <div>
                    <div class="font-bold text-[12px]"><?= esc_html(date('d M Y', strtotime($delivery->dispatch_date))) ?></div>
                    <div class="text-gray-500"><?= esc_html(ucfirst($delivery->status)) ?></div>
                    <div class="text-gray-600 mt-1"><?= esc_html($delivery->origin_country) ?> → <?= esc_html($delivery->destination_country) ?></div>
                </div>
            </label>
        <?php endforeach;
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }
    public static function get_deliveries_by_country_id()
    {
        check_ajax_referer('deliveries_nonce', 'nonce');

        $country_code = sanitize_text_field($_POST['country']);

        if (empty($country_code)) {
            wp_send_json_error(['message' => 'Country code is required']);
        }

        $deliveries = KIT_Deliveries::getScheduledDeliveries($country_code);

        ob_start();
        foreach ($deliveries as $delivery): ?>
            <label class="bg-white w-[100px] h-[100px] rounded-[5px] border-2 border-gray-300 cursor-pointer relative flex items-center justify-center text-center text-[11px] font-medium leading-tight hover:shadow-md transition-all duration-200 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-100 has-[:checked]:shadow-lg active:border-blue-500 active:bg-blue-100 active:shadow-lg">
                <input type="radio" name="delivery_id" value="<?= esc_attr($delivery->id) ?>" class="sr-only peer" <?= $delivery->id == 1 ? 'checked' : '' ?>>
                <div>
                    <div class="font-bold text-[12px]"><?= esc_html(date('d M Y', strtotime($delivery->dispatch_date))) ?></div>
                    <div class="text-gray-500"><?= esc_html(ucfirst($delivery->status)) ?></div>
                    <div class="text-gray-600 mt-1"><?= esc_html($delivery->origin_country) ?> → <?= esc_html($delivery->destination_country) ?></div>
                </div>
            </label>
        <?php endforeach;
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }
    public static function getScheduledCountryDeliveries($country_id)
    {
        global $wpdb;

        $deliveryTable = $wpdb->prefix . 'kit_deliveries';
        $shipDirectionTable = $wpdb->prefix . 'kit_shipping_directions';

        // Validate country ID
        $destinationCountry_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}kit_operating_countries WHERE id = %d",
            $country_id
        ));

        if (!$destinationCountry_id) {
            return false;
        }

        $query = "
        SELECT 
            d.id as delivery_id, 
            d.delivery_reference, 
            d.direction_id, 
            d.dispatch_date, 
            d.truck_number, 
            d.status, 
            sd.description, 

            -- Origin
            oc1.country_name AS origin_country, 
            oc1.country_code AS origin_code,

            -- Destination
            oc2.country_name AS destination_country, 
            oc2.country_code AS destination_code

        FROM $deliveryTable d

        LEFT JOIN $shipDirectionTable sd ON d.direction_id = sd.id 

        LEFT JOIN {$wpdb->prefix}kit_operating_countries oc1 ON sd.origin_country_id = oc1.id 
        LEFT JOIN {$wpdb->prefix}kit_operating_countries oc2 ON sd.destination_country_id = oc2.id 

        WHERE sd.destination_country_id = %d AND d.status = 'scheduled'
    ";

        $sql = $wpdb->prepare($query, $destinationCountry_id);

        return $wpdb->get_results($sql);
    }

    public static function tailSelect($atts)
    {
        // Properly output the HTML using PHP, not short tags inside a string
        ob_start();

        $delivery_id = isset($atts['delivery_id']) ? esc_attr($atts['delivery_id']) : '';
        $status = isset($atts['status']) ? $atts['status'] : '';
        ?>
        <div class="relative">
            <button type="button"
                class="inline-flex justify-center w-full rounded-md border shadow-sm px-4 py-2 bg-white text-sm font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                id="delivery-status-button-<?php echo $delivery_id; ?>"
                onclick="toggleDropdownDeliveryStatus('<?php echo $delivery_id; ?>')">
                <span class="flex items-center">
                    <span class="mr-2"><?php echo esc_html(ucfirst(str_replace('_', ' ', $status))); ?></span>
                    <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </span>
            </button>

            <div id="delivery-status-dropdown-<?php echo $delivery_id; ?>" class="hidden absolute right-0 mt-2 w-56 bg-white shadow-lg rounded-md z-10">
                <div class="py-1">
                    <?php if ($status !== 'scheduled'): ?>
                        <a href="#" onclick="changeDeliveryStatus(<?php echo $delivery_id; ?>, 'scheduled')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Scheduled</a>
                    <?php endif; ?>
                    <?php if ($status !== 'in_transit'): ?>
                        <a href="#" onclick="changeDeliveryStatus(<?php echo $delivery_id; ?>, 'in_transit')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">In Transit</a>
                    <?php endif; ?>
                    <?php if ($status !== 'delivered'): ?>
                        <a href="#" onclick="changeDeliveryStatus(<?php echo $delivery_id; ?>, 'delivered')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Delivered</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function deliveryStatus($atts)
    {
        if ($atts['insideForm'] == 'false') {
            echo "adffda";
            //just get the delivery status from the database
            global $wpdb;
            $table_name = $wpdb->prefix . 'kit_deliveries';
            $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM $table_name WHERE id = %d", $atts['delivery_id']));

            //just get the delivery status from the database
            $status = $wpdb->get_var($wpdb->prepare("SELECT status FROM $table_name WHERE id = %d", $atts['delivery_id']));

            ob_start();
        ?>
            <form method="POST" action="<?= esc_url(admin_url('admin-post.php')) ?>" id="delivery-status-form">
                <input type="hidden" name="action" value="update_delivery_status">
                <input type="hidden" name="delivery_id" value="<?= esc_attr($atts['delivery_id'] ?? '') ?>">
                <?php wp_nonce_field('update_delivery_status_nonce'); ?>
                <div class="relative inline-block text-left">
                    <?= self::tailSelect($atts) ?>
                </div>
            </form>

        <?php
        } else {
            return  self::tailSelect($atts);
        }
    }

    public static function getScheduledDeliveries($country_code = '')
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_deliveries';

        $query = "
                SELECT 
                    $table_name.delivery_reference, 
                    $table_name.direction_id, 
                    $table_name.dispatch_date, 
                    $table_name.truck_number, 
                    $table_name.status, 
                    sd.description,
                    sd.destination_country_id,
                    sd.origin_country_id,
                    oc1.country_name AS origin_country, 
                    oc1.country_code AS origin_code,
                    oc2.country_name AS destination_country, 
                    oc2.country_code AS destination_code

                FROM $table_name 

                LEFT JOIN {$wpdb->prefix}kit_shipping_directions sd 
                    ON $table_name.direction_id = sd.id 

                LEFT JOIN {$wpdb->prefix}kit_operating_countries oc1 
                    ON sd.origin_country_id = oc1.id 

                LEFT JOIN {$wpdb->prefix}kit_operating_countries oc2 
                    ON sd.destination_country_id = oc2.id 
                WHERE $table_name.status = 'scheduled'
                AND  $table_name.delivery_reference != 'warehoused'";

        if (!empty($country_code)) {
            $query .= $wpdb->prepare(" AND destination_country = %s", $country_code);
        }

        $query .= " ORDER BY dispatch_date ASC";
        return $wpdb->get_results($query);
    }


    public static function getCityData($city_id)
    {
        global $wpdb;

        $cities_table = $wpdb->prefix . 'kit_operating_cities';
        $query = $wpdb->prepare(
            "SELECT id, city_name FROM $cities_table WHERE id = %d",
            $city_id
        );
        return $wpdb->get_row($query, OBJECT);
    }

    public static function view_deliveries_page()
    {
        if (!isset($_GET['delivery_id']) || !is_numeric($_GET['delivery_id'])) {
            echo '<div class="notice notice-error"><p>Invalid delivery ID.</p></div>';
            return;
        }

        $delivery_id = intval($_GET['delivery_id']);
        $delivery = self::get_delivery($delivery_id);

        if (!$delivery) {
            echo '<div class="notice notice-error"><p>Delivery not found.</p></div>';
            return;
        }

        // Fetch waybills for this delivery
        global $wpdb;
        $waybill_table = $wpdb->prefix . 'kit_waybills';
        $waybills = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM $waybill_table WHERE delivery_id = %d", $delivery_id)
        );
        ?>

        <div class="wrap">
            <?php
            echo KIT_Commons::showingHeader([
                'title' => 'Delivery Details',
                'desc' => "View and manage delivery details and associated waybills",
            ]);
            ?>
            <div class="<?= KIT_Commons::container() ?>">
                <div class="grid md:grid-cols-8 gap-4">
                    <div class="md:col-span-2">
                        <div class=" bg-white rounded-lg shadow p-6 space-y-6">
                            <h2 class="text-xl font-semibold text-gray-700">Truck Details</h2>
                            <hr>
                            <?php
                            if (isset($_GET['edit_delivery']) && $_GET['edit_delivery'] == 1) {
                                echo self::deliveryForm($delivery_id);
                            } else {
                            ?>
                                <table class="min-w-full divide-y divide-gray-200">
                                    <tbody class="bg-white divide-y divide-gray-100">
                                        <tr>
                                            <th class="text-left py-2 pr-4 text-black font-medium">Reference Number</th>
                                            <td class="py-2 text-gray-900"><?php echo esc_html($delivery->delivery_reference); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-left py-2 pr-4 text-black font-medium">Origin Country</th>
                                            <td class="py-2 text-gray-900"><?php echo esc_html($delivery->origin_country); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-left py-2 pr-4 text-black font-medium">Destination Country</th>
                                            <td class="py-2 text-gray-900"><?php echo esc_html($delivery->destination_country); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-left py-2 pr-4 text-black font-medium">Dispatch Date</th>
                                            <td class="py-2 text-gray-900">
                                                <?php echo esc_html(date('Y-m-d', strtotime($delivery->dispatch_date))); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-left py-2 pr-4 text-black font-medium">Truck Number</th>
                                            <td class="py-2 text-gray-900"><?php echo esc_html($delivery->truck_number); ?></td>
                                        </tr>
                                        <tr>
                                            <th class="text-left py-2 pr-4 text-black font-medium">Status</th>
                                            <td class="py-2">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?php
                                    echo $delivery->status === 'delivered'
                                        ? 'bg-green-100 text-green-800'
                                        : ($delivery->status === 'in_transit'
                                            ? 'bg-yellow-100 text-yellow-800'
                                            : 'bg-blue-100 text-blue-800');
                                    ?>">
                                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $delivery->status))); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="text-left py-2 pr-4 text-black font-medium">Created By</th>
                                            <td class="py-2 text-gray-900">
                                                <?php echo esc_html(self::get_customer_name($delivery->created_by)); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <!-- Edit delivery button kitButton -->
                                <?php echo KIT_Commons::kitButton([
                                    'color' => 'blue',
                                    'href' => admin_url('admin.php?page=view-deliveries&delivery_id=' . $delivery_id . '&edit_delivery=1')
                                ], 'Edit Delivery'); ?>
                            <?php
                            } ?>
                        </div>
                    </div>
                    <div class="md:col-span-6 bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold text-gray-700">Waybills on Truck</h2>
                            <?php if (isset($delivery_id)) :
                                $customers   = KIT_Customers::tholaMaCustomer();
                                $form_action = admin_url('admin-post.php?action=add_waybill_action');

                                $modal_path = realpath(plugin_dir_path(__FILE__) . '../components/modal.php');

                                if (file_exists($modal_path)) {
                                    require_once $modal_path;
                                } else {
                                    error_log("Modal.php not found at: " . $modal_path);
                                    // Optional: Show a safe error or fallback content
                                }
                                echo KIT_Modal::render(
                                    'create-waybill-modal',
                                    'Create New W33aybill',
                                    kit_render_waybill_multiform([
                                        'form_action'          => $form_action,
                                        'waybill_id'           => '',
                                        'is_edit_mode'         => '0',
                                        'waybill'              => '{}',
                                        'customer_id'          => '0',
                                        'delivery_id'          => $delivery_id,
                                        'is_existing_customer' => '0',
                                        'customer'             => $customers
                                    ]),
                                    '3xl'
                                );
                            endif; ?>
                        </div>
                        <?php if (!empty($waybills)): ?>
                            <div class="overflow-x-auto">
                                <?php
                                $waybillsandItems = KIT_Waybills::truckWaybills($delivery->id);

                                $options = [
                                    'itemsPerPage' => 5,
                                    'currentPage' => $_GET['paged'] ?? 1,
                                    'tableClass' => 'min-w-full text-left text-xs text-gray-700',
                                    'emptyMessage' => 'No customers records found',
                                    'id' => 'customerTable',
                                    'role' => 'waybills'
                                ];

                                $columns = [
                                    'waybill_no' => ['label' => 'Waybill #', 'align' => 'text-left'],
                                    'customer_name' => ['label' => 'Name', 'align' => 'text-left'],
                                    'approval' => ['label' => 'Approval', 'align' => 'text-left'],
                                    'total' => ['label' => 'Total', 'align' => 'text-right'],
                                    'actions' => ['label' => 'Actions', 'align' => 'text-center'],
                                ];

                                $waybill_actions = function ($key, $row) {
                                    if ($key === 'waybill_no') {

                                        return '<a target="_blank" href="?page=08600-Waybill-view&waybill_id=' . $row->waybill_id . '&waybill_atts=view_waybill" class="text-blue-600 hover:underline">' . $row->waybill_no . '</a>';
                                    }
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
                                    if ($key === 'approval') {

                                        return KIT_Commons::waybillApprovalStatus($row->waybill_no, $row->waybill_id, $row->approval, 'select');
                                    }
                                    if ($key === 'actions') {
                                        $html = '<a target="_blank" href="?page=08600-Waybill-view&waybill_id=' . $row->waybill_id . '&waybill_atts=view_waybill" class="text-blue-600 hover:underline">View</a>';
                                        $html .= ' | <a href="?page=08600-Waybill-view&waybill_id=' . $row->waybill_id . '&edit=true" class="text-blue-600 hover:underline">Edit</a> ';
                                        $html .= ' | <a href="?page=waybill-dashboard&delete_waybill=' . $row->waybill_no . '" class="text-red-600 hover:underline" onclick="return confirm(\'Are you sure you want to delete this waybill?\');">Delete</a>';
                                        return $html;
                                    }
                                    return htmlspecialchars(($row->$key ?? '') ?: '');
                                };


                                echo KIT_Commons::render_versatile_table($waybillsandItems, $columns, $waybill_actions, $options);

                                ?>
                            </div>
                        <?php else: ?>
                            <div class="text-gray-500">No waybills found for this delivery.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    public static function getAllCountries()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_operating_countries';
        return $wpdb->get_results("SELECT * FROM $table_name");
    }
    public static function getCountriesObject()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_operating_countries';
        return $wpdb->get_results("SELECT * FROM $table_name WHERE is_active = 1");
    }
    public static function get_Cities_forCountry($country_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_operating_cities';
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE country_id = %d", $country_id);
        return $wpdb->get_results($query);
    }
    public static function CountrySelect($name = '', $id = '', $delivery_id = null, $required = true)
    {
        $required_attr = $required ? 'required' : '';
        //get all the countires with the is_active = 1
        $countries = self::getCountriesObject();
        if (empty($countries)) {
            return '<p class="text-red-500">No active countries found.</p>';
        }
        if ($delivery_id): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const select = document.getElementById('<?php echo esc_js($id); ?>');
                    if (select) {
                        handleCountryChange(select.value);
                    }
                });
            </script>
        <?php endif;
        ob_start();
        ?>
        <select onchange="handleCountryChange(this.value)" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($id); ?>" <?php echo $required_attr; ?>
            class="<?= KIT_Commons::selectClass(); ?>">
            <option value="">Select Country</option>
            <?php foreach ($countries as $country): ?>
                <option value="<?php echo esc_attr($country->id); ?>"
                    <?php echo ($delivery_id == $country->id) ? 'selected' : ''; ?>>
                    <?php echo esc_html($country->country_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php
        return ob_get_clean();
    }

    public static function deliveryForm($delivery_id = null)
    {
        $delivery_id = $_POST['delivery_id'] ?? null;
        if ($delivery_id) {
            $delivery = self::get_delivery($delivery_id);
        }



    ?>
        <form id="delivery-form" class="space-y-4 max-w-3xl" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
            <input type="hidden" name="action" value="kit_deliveries_crud">
            <input type="hidden" name="delivery_id" id="delivery_id" value="0">
            <?php wp_nonce_field('kit_deliveries_nonce', 'nonce'); ?>

            <div class="space-y-2">
                <label for="delivery_reference" class="block text-xs font-medium text-gray-700">Reference
                    Number</label>
                <input type="text" name="delivery_reference" id="delivery_reference" readonly value="<?= KIT_Deliveries::generateDeliveryRef() ?>"
                    class="text-xs w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="grid grid-cols-1 gap-2">
                <label for="origin_country" class="block text-xs font-medium text-gray-700">Origin</label>
                <div class="sm:grid sm:grid-cols-1 gap-2">
                    <div class="">
                        <?php echo KIT_Deliveries::selectAllCountries('origin_country', 'origin_country_select', 1, $required = "required", 'origin'); ?>
                    </div>
                    <div class="">
                        <?php echo KIT_Deliveries::selectAllCitiesByCountry('origin_city', 'origin_city_select', 1, 1, $required = 'required', ''); ?>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 gap-2">
                <label for="destination_country" class="block text-xs font-medium text-gray-700">Destination</label>
                <div class="sm:grid sm:grid-cols-1 gap-2">
                    <div class="">
                        <?php echo KIT_Deliveries::selectAllCountries('destination_country', 'destination_country_select', 2, $required = "required", 'destination'); ?>
                    </div>
                    <div class="">
                        <?php echo KIT_Deliveries::selectAllCitiesByCountry('destination_city', 'destination_city_select', 2, 6); ?>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div class="space-y-2">
                    <?= KIT_Commons::Linput([
                        'label' => 'Dispatc12h Date',
                        'name'  => 'dispatch_date',
                        'id'  => 'dispatch_date',
                        'type'  => 'date',
                        'value' => '',
                        'class' => 'additional-class'
                    ]); ?>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const dispatchDateInput = document.getElementById('dispatch_date');
                            const today = new Date().toISOString().split('T')[0];
                            // Set min date attribute
                            dispatchDateInput.min = today;
                            // Additional validation on form submission
                            document.querySelector('form').addEventListener('submit', function(e) {
                                const selectedDate = dispatchDateInput.value;
                                if (selectedDate < today) {
                                    e.preventDefault();
                                    dispatchDateInput.focus();
                                }
                            });
                        });
                    </script>
                </div>

                <div class="space-y-2">
                    <label for="truck_number" class="block text-xs font-medium text-gray-700">Truck Number</label>
                    <input type="text" name="truck_number" id="truck_number"
                        class="text-xs w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="space-y-2">
                <?php
                $deliveries_status = [
                    'scheduled' => 'Scheduled',
                    'in_transit' => 'In Transit',
                    'delivered' => 'Delivered'
                ];
                echo KIT_Commons::simpleSelect(
                    'Delivery Status',
                    'status',
                    'status',
                    $deliveries_status,
                    null
                );
                ?>
            </div>

            <div class="flex space-x-3">
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Save Delivery
                </button>
                <button type="button" id="cancel-edit"
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 hidden">
                    Cancel
                </button>
            </div>
        </form>
    <?php
    }

    /**
     * Get country_id, country_name, and country_code from a direction_id.
     *
     * @param int $direction_id
     * @param string $type 'origin' or 'destination'
     * @return array|null
     */
    public static function getWaybillTransitStats($direction_id, $type = 'origin')
    {
        global $wpdb;

        // Validate $type
        $type = strtolower($type);
        if (!in_array($type, ['origin', 'destination'])) {
            $type = 'origin';
        }

        // Set column names based on type
        $country_id_col = $type === 'origin' ? 'origin_country_id' : 'destination_country_id';
        $country_table_alias = $type === 'origin' ? 'oc1' : 'oc2';

        // Get the country_id from the direction
        $direction = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT sd.{$country_id_col} as country_id
                 FROM {$wpdb->prefix}kit_shipping_directions sd
                 WHERE sd.id = %d
                 LIMIT 1",
                $direction_id
            ),
            ARRAY_A
        );

        if (!$direction || empty($direction['country_id'])) {
            return null;
        }

        $country_id = intval($direction['country_id']);

        // Get the country details
        $country = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id as country_id, country_name, country_code
                 FROM {$wpdb->prefix}kit_operating_countries
                 WHERE id = %d
                 LIMIT 1",
                $country_id
            ),
            ARRAY_A
        );

        return $country ?: null;
    }



    public static function render_admin_page()
    {
        $deliveries = self::get_all_deliveries();
        
        // Get delivery statistics
        $total_deliveries = count($deliveries);
        $scheduled_deliveries = array_filter($deliveries, function($d) { return $d->status === 'scheduled'; });
        $scheduled_count = count($scheduled_deliveries);
        $in_transit_deliveries = array_filter($deliveries, function($d) { return $d->status === 'in_transit'; });
        $in_transit_count = count($in_transit_deliveries);
        $delivered_countries = array_unique(array_column($deliveries, 'destination_country_name'));
        $countries_count = count($delivered_countries);
        
        // Get recent deliveries for overview
        $recent_deliveries = array_slice($deliveries, 0, 5);
    ?>

        <div class="wrap">
            <h1 class="wp-heading-inline">Deliveries Management</h1>
            <hr class="wp-header-end">

            <!-- Tab Navigation -->
            <div class="delivery-tabs" style="margin-bottom: 30px;">
                <div style="display: flex; background: #f3f4f6; padding: 4px; border-radius: 8px; width: fit-content;">
                    <button id="overview-tab" class="tab-btn active" style="padding: 12px 24px; border-radius: 6px; font-weight: 500; font-size: 14px; transition: all 0.2s ease; background: white; color: #374151; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: none; cursor: pointer;">
                        Overview
                    </button>
                    <button id="create-tab" class="tab-btn" style="padding: 12px 24px; border-radius: 6px; font-weight: 500; font-size: 14px; transition: all 0.2s ease; background: transparent; color: #6b7280; border: none; cursor: pointer;">
                        Create Delivery
                    </button>
                    <button id="manage-tab" class="tab-btn" style="padding: 12px 24px; border-radius: 6px; font-weight: 500; font-size: 14px; transition: all 0.2s ease; background: transparent; color: #6b7280; border: none; cursor: pointer;">
                        Manage Deliveries
                    </button>
                </div>
            </div>

            <!-- Overview Tab -->
            <div id="overview-content" class="tab-content">
                <!-- Statistics Cards -->
                <div class="dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                    <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #2563eb;">
                        <h3 style="margin: 0 0 10px 0; color: #2563eb;">Total Deliveries</h3>
                        <div style="font-size: 2em; font-weight: bold; color: #1e293b;"><?php echo number_format($total_deliveries); ?></div>
                        <p style="margin: 5px 0 0 0; color: #64748b;">All deliveries created</p>
                    </div>

                    <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #059669;">
                        <h3 style="margin: 0 0 10px 0; color: #059669;">Scheduled</h3>
                        <div style="font-size: 2em; font-weight: bold; color: #1e293b;"><?php echo number_format($scheduled_count); ?></div>
                        <p style="margin: 5px 0 0 0; color: #64748b;">Ready for pickup</p>
                    </div>

                    <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #dc2626;">
                        <h3 style="margin: 0 0 10px 0; color: #dc2626;">In Transit</h3>
                        <div style="font-size: 2em; font-weight: bold; color: #1e293b;"><?php echo number_format($in_transit_count); ?></div>
                        <p style="margin: 5px 0 0 0; color: #64748b;">Currently shipping</p>
                    </div>

                    <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #7c3aed;">
                        <h3 style="margin: 0 0 10px 0; color: #7c3aed;">Countries</h3>
                        <div style="font-size: 2em; font-weight: bold; color: #1e293b;"><?php echo number_format($countries_count); ?></div>
                        <p style="margin: 5px 0 0 0; color: #64748b;">Destinations served</p>
                    </div>
                </div>

                <!-- Recent Deliveries & Quick Actions -->
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                    <!-- Recent Deliveries -->
                    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                        <h2 style="margin: 0 0 20px 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">Recent Deliveries</h2>
                        <?php if (!empty($recent_deliveries)): ?>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach ($recent_deliveries as $delivery): ?>
                                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f9fafb; border-radius: 8px; border-left: 4px solid #2563eb;">
                                        <div>
                                            <div style="font-weight: 600; color: #1f2937;"><?php echo esc_html($delivery->delivery_reference); ?></div>
                                            <div style="font-size: 14px; color: #6b7280;"><?php echo esc_html($delivery->origin_country_name); ?> → <?php echo esc_html($delivery->destination_country_name); ?></div>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <?php echo KIT_Commons::statusBadge($delivery->status); ?>
                                            <?php echo self::deliveryStatus(['delivery_id' => $delivery->id, 'status' => $delivery->status, 'insideForm' => 'true']); ?>
                                            <a href="?page=view-deliveries&delivery_id=<?php echo $delivery->id; ?>" style="color: #2563eb; font-size: 14px; text-decoration: none;">View</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div style="text-align: center; padding: 40px; color: #6b7280;">
                                <p>No deliveries yet. Create your first delivery to get started.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                        <h2 style="margin: 0 0 20px 0; font-size: 1.25rem; font-weight: 600; color: #1f2937;">Quick Actions</h2>
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <button onclick="switchTab('create')" style="background: #2563eb; color: white; padding: 12px 16px; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;">
                                Create New Delivery
                            </button>
                            <button onclick="switchTab('manage')" style="background: #059669; color: white; padding: 12px 16px; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;">
                                View All Deliveries
                            </button>
                            <a href="?page=warehouse-waybills" style="background: #7c3aed; color: white; padding: 12px 16px; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s ease; text-decoration: none; text-align: center;">
                                Assign Waybills
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Delivery Tab -->
            <div id="create-content" class="tab-content" style="display: none;">
                <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <h2 style="margin: 0 0 24px 0; font-size: 1.5rem; font-weight: 600; color: #1f2937;">Create New Delivery</h2>
                    <?= self::deliveryForm(); ?>
                </div>
            </div>

            <!-- Manage Deliveries Tab -->
            <div id="manage-content" class="tab-content" style="display: none;">
                <div style="background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
                    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 24px;">
                        <h2 style="margin: 0; font-size: 1.5rem; font-weight: 600; color: #1f2937;">Manage Deliveries</h2>
                        <div style="display: flex; gap: 12px;">
                            <input type="text" id="delivery-search" placeholder="Search deliveries..." style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                            <select id="status-filter" style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 14px;">
                                <option value="">All Status</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="in_transit">In Transit</option>
                                <option value="delivered">Delivered</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <?php
                        $options = [
                            'itemsPerPage' => 15,
                            'currentPage' => $_GET['paged'] ?? 1,
                            'tableClass' => 'w-full bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden',
                            'emptyMessage' => '<div class="text-center py-16"><h3 class="text-xl font-medium text-gray-900 mb-3">No deliveries found</h3><p class="text-gray-500 mb-6 text-lg">Get started by creating your first delivery</p><button onclick="switchTab(\'create\')" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-lg">Create Delivery</button></div>',
                            'id' => 'deliveriesTable',
                            'headerClass' => 'bg-gray-50 border-b border-gray-200',
                            'rowClass' => 'hover:bg-gray-50 transition-colors duration-150 border-b border-gray-100',
                            'cellClass' => 'px-6 py-4 text-sm text-gray-900',
                            'headerCellClass' => 'px-6 py-4 text-xs font-semibold text-gray-700 uppercase tracking-wider',
                        ];

                        $columns = [
                            'delivery_reference' => ['label' => 'Reference', 'align' => 'text-left'],
                            'route' => ['label' => 'Route', 'align' => 'text-left'],
                            'status' => ['label' => 'Status', 'align' => 'text-center'],
                            'dispatch_date' => ['label' => 'Dispatch Date', 'align' => 'text-center'],
                            'actions' => ['label' => 'Actions', 'align' => 'text-center'],
                        ];

                        $delivery_actions = function ($key, $row) {
                            if ($key === 'delivery_reference') {
                                return '<span class="font-semibold text-gray-900">' . esc_html($row->delivery_reference) . '</span>';
                            }
                            if ($key === 'route') {
                                return '<span class="text-gray-700">' . esc_html($row->origin_country_name) . ' → ' . esc_html($row->destination_country_name) . '</span>';
                            }
                            if ($key === 'status') {
                                return KIT_Commons::statusBadge($row->status);
                            }
                            if ($key === 'dispatch_date') {
                                return '<span class="text-gray-500">' . date('M j, Y', strtotime($row->dispatch_date)) . '</span>';
                            }
                            if ($key === 'actions') {
                                $html = '<div class="flex space-x-2">';
                                $html .= '<a href="?page=view-deliveries&delivery_id=' . $row->id . '" class="inline-flex items-center px-3 py-2 text-xs font-medium text-blue-700 bg-blue-100 rounded-lg hover:bg-blue-200 transition-colors">View</a>';
                                $html .= '<a href="?page=deliveries-dashboard&delete_delivery=' . $row->id . '" class="inline-flex items-center px-3 py-2 text-xs font-medium text-red-700 bg-red-100 rounded-lg hover:bg-red-200 transition-colors" onclick="return confirm(\'Are you sure you want to delete this delivery?\');">Delete</a>';
                                $html .= '</div>';
                                return $html;
                            }
                            return htmlspecialchars(($row->$key ?? '') ?: '');
                        };
                                                 echo KIT_Commons::render_versatile_table($deliveries, $columns, $delivery_actions, $options);
                         ?>
                     </div>
                 </div>
             </div>
        </div>

        <style>
        /* Hide overlapping WordPress footer text */
        #wpfooter,
        .wp-footer,
        .wp-version,
        .update-nag,
        .notice,
        .updated,
        .error,
        .warning {
            display: none !important;
        }
        
        /* Ensure proper spacing at bottom */
        .wrap {
            margin-bottom: 40px !important;
        }
        </style>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionality
            function switchTab(tabName) {
                // Hide all tab contents
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.style.display = 'none';
                });
                
                // Remove active class from all tab buttons
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                    btn.style.background = 'transparent';
                    btn.style.color = '#6b7280';
                    btn.style.boxShadow = 'none';
                });
                
                // Show selected tab content
                const selectedContent = document.getElementById(tabName + '-content');
                if (selectedContent) {
                    selectedContent.style.display = 'block';
                }
                
                // Add active class to selected tab button
                const selectedBtn = document.getElementById(tabName + '-tab');
                if (selectedBtn) {
                    selectedBtn.classList.add('active');
                    selectedBtn.style.background = 'white';
                    selectedBtn.style.color = '#374151';
                    selectedBtn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
                }
            }
            
            // Make switchTab function globally available
            window.switchTab = switchTab;
            
            // Add click event listeners to tab buttons
            document.getElementById('overview-tab').addEventListener('click', () => switchTab('overview'));
            document.getElementById('create-tab').addEventListener('click', () => switchTab('create'));
            document.getElementById('manage-tab').addEventListener('click', () => switchTab('manage'));
            
            // Search and filter functionality
            const searchInput = document.getElementById('delivery-search');
            const statusFilter = document.getElementById('status-filter');
            const tableRows = document.querySelectorAll('#deliveriesTable tbody tr');
            
            function filterDeliveries() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusFilterValue = statusFilter.value.toLowerCase();
                
                tableRows.forEach(row => {
                    const reference = row.querySelector('td:nth-child(1)')?.textContent.toLowerCase() || '';
                    const route = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                    const status = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
                    const date = row.querySelector('td:nth-child(4)')?.textContent.toLowerCase() || '';
                    
                    const matchesSearch = reference.includes(searchTerm) || 
                                        route.includes(searchTerm) || 
                                        status.includes(searchTerm) || 
                                        date.includes(searchTerm);
                    
                    // Handle status filtering more precisely
                    let matchesStatus = true;
                    if (statusFilterValue) {
                        if (statusFilterValue === 'scheduled') {
                            matchesStatus = status.includes('scheduled');
                        } else if (statusFilterValue === 'in_transit') {
                            matchesStatus = status.includes('in transit') || status.includes('in_transit');
                        } else if (statusFilterValue === 'delivered') {
                            matchesStatus = status.includes('delivered');
                        }
                    }
                    
                    if (matchesSearch && matchesStatus) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Update table info
                updateTableInfo();
            }
            
            function updateTableInfo() {
                const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
                const totalRows = tableRows.length;
                const showingRows = visibleRows.length;
                
                // Find and update the table info text
                const tableInfo = document.querySelector('.table-info');
                if (tableInfo) {
                    tableInfo.textContent = `Showing ${showingRows} of ${totalRows} deliveries`;
                }
            }
            
            if (searchInput) {
                searchInput.addEventListener('input', filterDeliveries);
            }
            
            if (statusFilter) {
                statusFilter.addEventListener('change', filterDeliveries);
            }
            
            // Initialize table info
            updateTableInfo();
            
            // Note: Select all functionality removed since checkbox column was removed
            
            // Delivery status dropdown functionality
            window.toggleDropdownDeliveryStatus = function(deliveryId) {
                const dropdown = document.getElementById('delivery-status-dropdown-' + deliveryId);
                if (dropdown) {
                    dropdown.classList.toggle('hidden');
                }
            }
            
            window.changeDeliveryStatus = function(deliveryId, newStatus) {
                if (!confirm(`Are you sure you want to change this delivery status to "${newStatus.replace('_', ' ')}"?`)) {
                    return;
                }
                
                // Determine which AJAX action to call based on the new status
                let action = '';
                switch(newStatus) {
                    case 'in_transit':
                        action = 'delivery_changeTo_Intransit';
                        break;
                    case 'delivered':
                        action = 'delivery_changeTo_Delivered';
                        break;
                    case 'scheduled':
                        action = 'delivery_changeTo_Scheduled';
                        break;
                }
                
                if (action) {
                    const formData = new FormData();
                    formData.append('action', action);
                    formData.append('id', deliveryId);
                    
                    fetch(ajaxurl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Status updated successfully!');
                            location.reload(); // Refresh to show updated status
                        } else {
                            alert('Failed to update status: ' + (data.data || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error updating status. Please try again.');
                    });
                }
                
                // Hide the dropdown
                const dropdown = document.getElementById('delivery-status-dropdown-' + deliveryId);
                if (dropdown) {
                    dropdown.classList.add('hidden');
                }
            }
            

        });
        </script>

        <style>
        /* Tab button hover effects */
        .tab-btn:hover {
            background: #e5e7eb !important;
            color: #374151 !important;
        }
        
        /* Stat card hover effects */
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        /* Recent delivery card hover effects */
        .recent-delivery-card:hover {
            background: #f3f4f6 !important;
            transform: translateX(4px);
            transition: all 0.2s ease;
        }
        
        /* Quick action button hover effects */
        .quick-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        </style>

            <!-- Quick Links -->
            <div class="quick-links" style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                <a href="#" onclick="document.querySelector('#delivery-form324343434344343434pew9kwwsdkfpdsfodskpfosdkpfosdkfposdkfsdpofkdsapofk').scrollIntoView(); return false;" style="display: block; padding: 15px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
                    <strong>Add New Delivery</strong>
                </a>
                <a href="?page=08600-waybill-create" style="display: block; padding: 15px; background: #059669; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
                    <strong>Create Waybill</strong>
                </a>
                <a href="?page=route-management" style="display: block; padding: 15px; background: #dc2626; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
                    <strong>Manage Routes</strong>
                </a>
                <a href="?page=08600-customers" style="display: block; padding: 15px; background: #7c3aed; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
                    <strong>Manage Customers</strong>
                </a>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                // Handle form submission
                $('#delivery-form324343434344343434pew9kwwsdkfpdsfodskpfosdkpfosdkfposdkfsdpofksdpofkdsapofk').on('submit', function(e) {
                    e.preventDefault();
                    // Get form data
                    const formData = $(this).serializeArray();

                    // Add the task parameter
                    formData.push({
                        name: 'task',
                        value: $('#delivery_id').val() === '0' ? 'create_delivery' : 'update_delivery'
                    });

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: $.param(formData), // Convert array to query string
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                console.log("response.data");
                            }
                        }
                    });
                });


            });
        </script>
    <?php
    }

    public static function handle_ajax()
    {



        check_ajax_referer('kit_deliveries_nonce', 'nonce');


        if (!current_user_can('edit_pages')) {
            wp_send_json_error('Unauthorized');
        }


        $task = $_POST['task'] ?? 'create_delivery';
        $data = $_POST;

        if (empty($task)) {
            wp_send_json_error('Task parameter missing');
        }

        switch ($task) {
            case 'create_delivery':
            case 'update_delivery':
                $result = self::save_delivery($data);
                break;

            case 'get_delivery':
                $result = self::get_delivery($data['id']);
                break;

            case 'delete_delivery':
                $result = self::delete_delivery($data['id']);
                break;
            case 'get_scheduled_deliveries':
                if (empty($_POST['country'])) {
                    wp_send_json_error('Country parameter missing');
                }
                $result = self::deliveries_by_CountStat(
                    sanitize_text_field($_POST['country'])
                );
                break;

            default:
                wp_send_json_error('Invalid action');
        }

        // Handle both AJAX and POST requests
        if (defined('DOING_AJAX') && DOING_AJAX) {
            if ($result === false) {
                wp_send_json_error('Operation failed');
            } else {
                wp_send_json_success($result);
            }
        } else {
            // For POST (admin-post.php) requests
            if ($result === false) {
                wp_redirect(wp_get_referer() ?: admin_url());
                exit;
            } else {
                // Optionally, you can add a success notice or redirect to a specific page
                wp_redirect(wp_get_referer() ?: admin_url());
                exit;
            }
        }
    }



    //Change delivery status from schediled to intransit
    public static function delivery_changeTo_Intransit()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_deliveries';
        $id = intval($_POST['id']);
        $updated = $wpdb->update(
            $table,
            ['status' => 'in_transit'],
            ['id' => intval($id)]
        );

        if ($updated !== false) {
            wp_send_json_success('Status updated successfully');
        } else {
            wp_send_json_error('Failed to update status');
        }
    }
    //Change delivery status from intransit to delivered
    public static function delivery_changeTo_Delivered()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_deliveries';
        $id = intval($_POST['id']);

        $updated = $wpdb->update(
            $table,
            ['status' => 'delivered'],
            ['id' => intval($id)]
        );

        if ($updated !== false) {
            wp_send_json_success('Status updated successfully');
        } else {
            wp_send_json_error('Failed to update status');
        }
    }
    //Change delivery status scheduled
    public static function delivery_changeTo_Scheduled()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_deliveries';
        $id = intval($_POST['id']);
        $updated = $wpdb->update(
            $table,
            ['status' => 'scheduled'],
            ['id' => intval($id)]
        );

        if ($updated !== false) {
            wp_send_json_success('Status updated successfully');
        } else {
            wp_send_json_error('Failed to update status');
        }
    }
    public static function deliveries_by_CountStat($country)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_deliveries';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table 
        WHERE destination_country = %s 
        AND status = 'scheduled'
        ORDER BY dispatch_date ASC",
            $country
        ));
    }
    public static function get_customers()
    {
        return get_users(['role__in' => ['customer', 'subscriber']]);
    }
    public static function get_customer_name($customer_id)
    {
        $customer = get_user_by('ID', $customer_id);
        return $customer ? $customer->display_name : 'Unknown';
    }
    public static function get_all_deliveries()
    {
        //get all deliveries and fk link with operating countries where the delivery.origin_country
        global $wpdb;
        $table = $wpdb->prefix . 'kit_deliveries';

        // Fetch all deliveries ordered by created_at in descending order
        // Join with kit_shipping_directions on direction_id
        // Also join kit_operating_countries twice to get origin and destination country names

        $countries_table = $wpdb->prefix . 'kit_operating_countries';
        $directions_table = $wpdb->prefix . 'kit_shipping_directions';

        // Join: deliveries -> directions -> countries (origin & destination)
        $query = "
            SELECT 
            d.*, 
            sd.origin_country_id, 
            sd.destination_country_id, 
            sd.description AS direction_description,
            oc1.country_name AS origin_country_name,
            oc2.country_name AS destination_country_name
            FROM {$table} d
            LEFT JOIN {$directions_table} sd ON d.direction_id = sd.id
            LEFT JOIN {$countries_table} oc1 ON sd.origin_country_id = oc1.id
            LEFT JOIN {$countries_table} oc2 ON sd.destination_country_id = oc2.id
            WHERE d.delivery_reference != 'warehoused'
            ORDER BY d.created_at DESC
        ";
        return $wpdb->get_results($query);
    }
    public static function get_delivery($id)
    {
        global $wpdb;

        $deliveryTable       = $wpdb->prefix . 'kit_deliveries';
        $shipDirectionTable  = $wpdb->prefix . 'kit_shipping_directions';
        $countryTable        = $wpdb->prefix . 'kit_operating_countries';

        $query = "
        SELECT 
            d.*, 
            sd.*,

            -- Origin
            oc1.country_name AS origin_country, 
            oc1.country_code AS origin_code,

            -- Destination
            oc2.country_name AS destination_country, 
            oc2.country_code AS destination_code

        FROM $deliveryTable d

        LEFT JOIN $shipDirectionTable sd ON d.direction_id = sd.id 
        LEFT JOIN $countryTable oc1 ON sd.origin_country_id = oc1.id 
        LEFT JOIN $countryTable oc2 ON sd.destination_country_id = oc2.id 

        WHERE d.id = %d
    ";

        return $wpdb->get_row($wpdb->prepare($query, $id));
    }

    public static function get_direction_id($origin_country_id, $destination_country_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_shipping_directions';
        return $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE origin_country_id = %d AND destination_country_id = %d", $origin_country_id, $destination_country_id));
    }

    public static function create_direction($origin_country_id, $destination_country_id)
    {
        global $wpdb;
    
        $table = $wpdb->prefix . 'kit_shipping_directions';
        $countries_table = $wpdb->prefix . 'kit_operating_countries';
    
        // Fetch country names
        $origin_country_name = KIT_Routes::get_country_name_by_id($origin_country_id);
        $destination_country_name = KIT_Routes::get_country_name_by_id($destination_country_id);
    
        // 🔁 Activate origin country if inactive
        $origin_active = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM $countries_table WHERE id = %d",
            $origin_country_id
        ));
    
        if ($origin_active !== null && intval($origin_active) === 0) {
            $wpdb->update(
                $countries_table,
                ['is_active' => 1],
                ['id' => $origin_country_id],
                ['%d'],
                ['%d']
            );
        }
    
        // 🔁 Activate destination country if inactive
        $destination_active = $wpdb->get_var($wpdb->prepare(
            "SELECT is_active FROM $countries_table WHERE id = %d",
            $destination_country_id
        ));
    
        if ($destination_active !== null && intval($destination_active) === 0) {
            $wpdb->update(
                $countries_table,
                ['is_active' => 1],
                ['id' => $destination_country_id],
                ['%d'],
                ['%d']
            );
        }
    
        // Insert route/direction
        $wpdb->insert($table, [
            'origin_country_id' => $origin_country_id,
            'destination_country_id' => $destination_country_id,
            'description' => $origin_country_name . ' to ' . $destination_country_name
        ]);
    
        return $wpdb->insert_id;
    }

    public static function save_delivery($data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_deliveries';
        $chargeGroups_table = $wpdb->prefix . 'kit_charge_groups';

        // Get or create shipping direction
        $direction_id = self::get_direction_id($data['origin_country'], $data['destination_country']);
        if (!$direction_id) {
            $direction_id = self::create_direction($data['origin_country'], $data['destination_country']);
        }

        $delivery_data = [
            'delivery_reference' => sanitize_text_field($data['delivery_reference']),
            'direction_id' => (int) $direction_id,
            'destination_city_id' => sanitize_text_field($data['destination_city']),
            'dispatch_date' => sanitize_text_field($data['dispatch_date']),
            'truck_number' => sanitize_text_field($data['truck_number']),
            'status' => in_array($data['status'], ['scheduled', 'in_transit', 'delivered'])
                ? $data['status']
                : 'scheduled',
            'created_by' => get_current_user_id()
        ];

        if (isset($data['delivery_id']) && $data['delivery_id'] > 0) {
            // Update existing delivery

            $wpdb->update(
                $table,
                $delivery_data,
                ['id' => intval($data['delivery_id'])]
            );
            return $data['delivery_id'];
        } else {

            $wpdb->insert($table, $delivery_data);

            // The delivery has been created successfully, return the id of the new delivery
            return $wpdb->insert_id;
        }
    }
    public static function delete_delivery($id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_deliveries';
        return $wpdb->delete($table, ['id' => intval($id)]);
    }

    public static function selectAllCountries($name, $id, $country_id, $required = true, $type = 'origin')
    {
        $countries = self::getAllCountries();
        ob_start();

    ?>
        <select class="<?= KIT_Commons::selectClass() ?>" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($id); ?>" <?php echo $required; ?> " onchange=" gaybitch(this.value, '<?php echo $type; ?>' )">
            <option value="">Select Country</option>
            <?php foreach ($countries as $country): ?>
                <option value=" <?php echo esc_attr($country->id); ?>" <?php echo ($country_id == $country->id) ? 'selected' : ''; ?>>
                    <?php echo esc_html($country->country_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
    <?php
        return ob_get_clean();
    }

    public static function selectAllCitiesByCountry($name, $id, $country_id, $city_id, $required = true)
    {
        ob_start();
        $cities = KIT_Deliveries::get_Cities_forCountry($country_id);
    ?>
        <select class="<?= KIT_Commons::selectClass() ?>" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($id); ?>" <?php echo $required; ?>>
            <option value="">Select City</option>
            <?php foreach ($cities as $city): ?>
                <option value="<?php echo esc_attr($city->id); ?>" <?php echo ($city_id == $city->id) ? 'selected' : ''; ?>>
                    <?php echo esc_html($city->city_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
<?php
        return ob_get_clean();
    }

    function handle_get_cities_for_country_callback()
    {
        check_ajax_referer('kit_deliveries_nonce', 'nonce');

        $country_id = intval($_POST['country_id']);
        $cities = KIT_Deliveries::get_Cities_forCountry($country_id); // You need to implement this

        $cities_dropdown = KIT_Deliveries::selectAllCitiesByCountry('origin_city', 'origin_city_select', $cities, null, true);

        wp_send_json_success($cities_dropdown);
    }
}

KIT_Deliveries::init();

function render_waybills_with_items($waybillsandItems)
{
    // Define the columns you want to show
    $columns = ['waybill_no', 'customer', 'dispatch_date', 'actions'];

    echo '<table class="table-class">';
    // Table header
    echo '<thead><tr>';
    foreach ($columns as $col) {
        echo '<th>' . ucfirst(str_replace('_', ' ', $col)) . '</th>';
    }
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ($waybillsandItems as $row) {
        $waybill = (object)$row['waybill'];
        render_waybill_row($waybill, $columns);
        // Optionally, render items as sub-rows or in a details row
        // foreach ($row['items'] as $item) { ... }
    }

    echo '</tbody></table>';
}
