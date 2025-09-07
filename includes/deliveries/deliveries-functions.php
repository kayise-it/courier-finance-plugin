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
        add_action('wp_ajax_handle_get_cities_for_country', [self::class, 'handle_get_cities_for_country_callback']);
        add_action('wp_ajax_nopriv_handle_get_cities_for_country', [self::class, 'handle_get_cities_for_country_callback']);
        add_action('wp_ajax_handle_get_countryDeliveries', [self::class, 'handle_get_countryDeliveries_callback']);
        add_action('wp_ajax_handle_get_price_per_kg', [self::class, 'handle_get_price_per_kg']);
        add_action('wp_ajax_nopriv_handle_get_price_per_kg', [self::class, 'handle_get_price_per_kg']);
        add_action('wp_ajax_handle_get_price_per_m3', [self::class, 'handle_get_price_per_m3']);
        add_action('wp_ajax_nopriv_handle_get_price_per_m3', [self::class, 'handle_get_price_per_m3']);
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
        $chargeGroup = KIT_Waybills::chargeGroup($_POST['origin_country_id'] ?? 0);

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

        // ✅ BULLETPROOF: Comprehensive input validation and sanitization
        try {
            // Validate nonce for security
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'get_waybills_nonce')) {
                wp_send_json_error(['message' => 'Invalid security token.']);
                return;
            }

            // Sanitize and validate inputs
            $direction_id = isset($_POST['direction_id']) ? intval($_POST['direction_id']) : 0;
            $total_mass_kg = isset($_POST['total_mass_kg']) ? floatval($_POST['total_mass_kg']) : 0;
            $origin_country_id = isset($_POST['origin_country_id']) ? intval($_POST['origin_country_id']) : 0;

            // ✅ BULLETPROOF: Comprehensive validation
            if ($direction_id <= 0) {
                wp_send_json_error(['message' => 'Invalid direction ID.']);
                return;
            }

            if ($total_mass_kg <= 0) {
                wp_send_json_error(['message' => 'Mass must be greater than 0.']);
                return;
            }

            if ($total_mass_kg > 10000) { // Reasonable upper limit
                wp_send_json_error(['message' => 'Mass exceeds maximum limit of 10,000 kg.']);
                return;
            }

            // Get charge group with fallback
            $chargeGroup = KIT_Waybills::chargeGroup($origin_country_id);
            if (!$chargeGroup) {
                wp_send_json_error(['message' => 'Unable to determine charge group.']);
                return;
            }

            $table = $wpdb->prefix . 'kit_shipping_rates_mass';

            // ✅ BULLETPROOF: Fixed boundary conditions with proper weight range logic
            $rate_per_kg = $wpdb->get_var($wpdb->prepare("
            SELECT rate_per_kg
            FROM $table
            WHERE direction_id = %d
              AND min_weight <= %f
              AND (max_weight > %f OR max_weight = %f)
            ORDER BY effective_date DESC, min_weight DESC
            LIMIT 1", $chargeGroup, $total_mass_kg, $total_mass_kg, $total_mass_kg));

            // ✅ BULLETPROOF: Comprehensive error handling and fallbacks
            if ($rate_per_kg !== null && $rate_per_kg > 0) {
                $total_charge = round($rate_per_kg * $total_mass_kg, 2);
                
                // Validate calculated charge
                if ($total_charge <= 0) {
                    wp_send_json_error(['message' => 'Invalid calculated charge.']);
                    return;
                }

                wp_send_json_success([
                    'rate_per_kg' => floatval($rate_per_kg),
                    'total_charge' => $total_charge,
                    'direction_id' => $direction_id,
                    'mass_kg' => $total_mass_kg,
                    'charge_group' => $chargeGroup
                ]);
            } else {
                // ✅ BULLETPROOF: Try fallback rate lookup
                $fallback_rate = $wpdb->get_var($wpdb->prepare("
                SELECT rate_per_kg
                FROM $table
                WHERE direction_id = %d
                ORDER BY effective_date DESC, min_weight ASC
                LIMIT 1", $chargeGroup));
                
                if ($fallback_rate !== null && $fallback_rate > 0) {
                    $total_charge = round($fallback_rate * $total_mass_kg, 2);
                    wp_send_json_success([
                        'rate_per_kg' => floatval($fallback_rate),
                        'total_charge' => $total_charge,
                        'direction_id' => $direction_id,
                        'mass_kg' => $total_mass_kg,
                        'charge_group' => $chargeGroup,
                        'fallback' => true
                    ]);
                } else {
                    wp_send_json_error([
                        'message' => 'No matching rate found for the specified criteria.',
                        'debug_info' => [
                            'direction_id' => $direction_id,
                            'mass_kg' => $total_mass_kg,
                            'charge_group' => $chargeGroup
                        ]
                    ]);
                }
            }
            
        } catch (Exception $e) {
            // ✅ BULLETPROOF: Catch any unexpected errors
            error_log('Rate fetch error: ' . $e->getMessage());
            wp_send_json_error([
                'message' => 'An unexpected error occurred while fetching rates.',
                'error_code' => 'RATE_FETCH_ERROR'
            ]);
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

        $country_id = intval($_POST['country_id'] ?? 0);

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
        if (! isset($_POST['nonce']) || ! (wp_verify_nonce($_POST['nonce'], 'deliveries_nonce') || wp_verify_nonce($_POST['nonce'], 'get_waybills_nonce'))) {
            wp_send_json_error('Invalid nonce', 403);
        }

        $country_code = sanitize_text_field($_POST['country']);

        if (empty($country_code)) {
            wp_send_json_error(['message' => 'Country code is required']);
        }

        $deliveries = KIT_Deliveries::getScheduledDeliveries($country_code);

        ob_start();
        foreach ($deliveries as $delivery): ?>
            <label class="delivery-card bg-white w-[100px] h-[100px] rounded-[5px] border-2 border-gray-300 cursor-pointer relative flex items-center justify-center text-center text-[11px] font-medium leading-tight hover:shadow-md transition-all duration-200" data-delivery-id="<?= esc_attr($delivery->id) ?>">
                <input type="radio" name="delivery_id" value="<?= esc_attr($delivery->id) ?>" class="sr-only" <?= $delivery->id == 1 ? 'checked' : '' ?>>
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
        if (! isset($_POST['nonce']) || ! (wp_verify_nonce($_POST['nonce'], 'deliveries_nonce') || wp_verify_nonce($_POST['nonce'], 'get_waybills_nonce'))) {
            wp_send_json_error('Invalid nonce', 403);
        }

        $country_id = intval($_POST['country']);

        if (empty($country_id)) {
            wp_send_json_error(['message' => 'Country ID is required']);
        }

        // Validate country ID exists
        global $wpdb;
        $country_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}kit_operating_countries WHERE id = %d",
            $country_id
        ));

        if (!$country_exists) {
            wp_send_json_error(['message' => 'Country not found']);
        }

        $deliveries = self::getScheduledCountryDeliveries($country_id);

        ob_start();
        foreach ($deliveries as $delivery) {
            echo self::render_scheduled_delivery_card($delivery, [
                'input_type' => 'radio',
                'input_name' => 'delivery_id',
                'checked_id' => 1,
            ]);
        }
        $html = ob_get_clean();

        wp_send_json_success(['html' => $html]);
    }

    /**
     * Render scheduled delivery card for AJAX responses
     * Uses our reusable deliveryCard component
     */
    public static function render_scheduled_delivery_card($delivery, $options = [])
    {
        // Include our reusable component
        require_once plugin_dir_path(__FILE__) . '../components/deliveryCard.php';
        
        // Convert delivery data to match our component format
        $component_delivery = (object)[
            'direction_id' => $delivery->delivery_id,
            'id' => $delivery->delivery_id,
            'origin_country' => $delivery->origin_country, // Use country names directly
            'destination_country' => $delivery->destination_country,
            'dispatch_date' => $delivery->dispatch_date,
            'truck_number' => $delivery->truck_number ?? '',
            'description' => $delivery->description ?? ''
        ];
        
        // Debug: Log what we're getting from the database
        error_log('Delivery data: ' . print_r($delivery, true));
        error_log('Component delivery: ' . print_r($component_delivery, true));
        
        // Use our reusable component with radio button options
        $radio_options = [
            'type' => $options['input_type'] ?? 'radio',
            'name' => $options['input_name'] ?? 'delivery_id',
            'checked_id' => $options['checked_id'] ?? null
        ];
        
        ob_start();
        renderDeliveryCard($component_delivery, 'scheduled', true, 'handleDeliveryClick', $radio_options);
        
        return ob_get_clean();
    }

    // Wrapper to satisfy registered AJAX hook name
    public static function handle_get_deliveries_by_country_id()
    {
        // Reuse core implementation
        self::get_deliveries_by_country_id();
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
                    <?php 
                // Ensure status is not null before using string functions
                $status = $status ?? '';
                ?>
                <span class="mr-2"><?php echo esc_html(ucfirst(str_replace('_', ' ', (string)($status ?? '')))); ?></span>
                    <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </span>
            </button>

            <div id="delivery-status-dropdown-<?php echo $delivery_id; ?>" class="hidden absolute right-0 mt-2 w-56 bg-white shadow-lg rounded-md z-10">
                <div class="py-1">
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Scheduled</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">In Transit</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Delivered</a>
                    <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Cancelled</a>
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
                    $table_name.id,
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
            // Filter by the joined destination country name column
            $query .= $wpdb->prepare(" AND oc2.country_name = %s", $country_code);
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
                                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', (string)($delivery->status ?? '')))); ?>
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
                                        return KIT_Commons::currency() . ' ' . ((int) $row->product_invoice_amount + (int) $row->miscellaneous);
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
        $query = $wpdb->prepare("SELECT * FROM $table_name WHERE country_id = %d ORDER BY city_name ASC", $country_id);
        $results = $wpdb->get_results($query);

        // Debug logging
        error_log("Getting cities for country ID: $country_id, found: " . count($results));

        return $results;
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
        <select onchange="handleCountryChange(this.value, '<?php echo esc_attr($name); ?>')" name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($id); ?>" <?php echo $required_attr; ?>
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
        // Get delivery data for editing
        $delivery = null;
        if ($delivery_id) {
            $delivery = self::get_delivery($delivery_id);
        }
        
        ob_start();
        ?>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">
                            <?= $delivery ? 'Edit Delivery' : 'Create New Delivery' ?>
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            <?= $delivery ? 'Update delivery information and settings' : 'Create a new delivery entry' ?>
                        </p>
                    </div>
                    <?php if ($delivery): ?>
                    <div class="flex items-center space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            <?= $delivery->status === 'delivered' ? 'bg-green-100 text-green-800' : 
                                ($delivery->status === 'in_transit' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') ?>">
                            <?= ucfirst(str_replace('_', ' ', (string)($delivery->status ?? ''))) ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <form id="edit-delivery-form" class="space-y-6">
                <input type="hidden" name="action" value="kit_deliveries_crud">
                <input type="hidden" name="delivery_id" id="delivery_id" value="<?= $delivery ? $delivery->id : 0 ?>">
                <?php wp_nonce_field('get_waybills_nonce', 'nonce'); ?>

                <!-- Reference Number -->
                <div class="form-field">
                    <label for="delivery_reference" class="block text-sm font-medium text-gray-700 mb-2">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Reference Number
                        </span>
                    </label>
                    <div class="relative">
                        <input type="text" name="delivery_reference" id="delivery_reference"
                            readonly value="<?= $delivery ? esc_attr($delivery->delivery_reference) : KIT_Deliveries::generateDeliveryRef() ?>"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 text-gray-700 font-mono text-sm">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Auto-generated reference number</p>
                </div>

                <!-- Route Information -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-4 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Route Information
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Origin -->
                        <div class="form-field">
                            <label for="origin_country_select" class="block text-sm font-medium text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Origin Country
                                </span>
                            </label>
                            <div class="relative">
                                <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsOrigin.php'); ?>
                            </div>
                        </div>

                        <!-- Destination -->
                        <div class="form-field">
                            <label for="destination_country_select" class="block text-sm font-medium text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Destination Country
                                </span>
                            </label>
                            <div class="relative">
                                <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsDestination.php'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delivery Details -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-gray-700 mb-4 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Delivery Details
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Dispatch Date -->
                        <div class="form-field">
                            <label for="dispatch_date" class="block text-sm font-medium text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Dispatch Date
                                </span>
                            </label>
                            <input type="date" name="dispatch_date" id="dispatch_date" required
                                value="<?= $delivery ? esc_attr($delivery->dispatch_date) : '' ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        </div>

                        <!-- Truck Number -->
                        <div class="form-field">
                            <label for="truck_number" class="block text-sm font-medium text-gray-700 mb-2">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Truck Number
                                </span>
                            </label>
                            <input type="text" name="truck_number" id="truck_number" required
                                value="<?= $delivery ? esc_attr($delivery->truck_number) : '' ?>"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                                placeholder="Enter truck number">
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-field mt-4">
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Delivery Status
                            </span>
                        </label>
                        <div class="relative">
                            <?php
                            $deliveries_status = [
                                'scheduled' => 'Scheduled',
                                'in_transit' => 'In Transit',
                                'delivered' => 'Delivered'
                            ];
                            $current_status = $delivery ? $delivery->status : 'scheduled';
                            echo KIT_Commons::simpleSelect(
                                '',
                                'status',
                                'status',
                                $deliveries_status,
                                $current_status
                            );
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex gap-3 pt-6 border-t border-gray-200">
                    <button type="submit" id="save-delivery-btn"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center space-x-2 disabled:opacity-50 disabled:cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span><?= $delivery ? 'Update Delivery' : 'Save Delivery' ?></span>
                    </button>
                    <a href="?page=view-deliveries&delivery_id=<?= $delivery_id ?>" 
                       class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-3 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <span>Cancel</span>
                    </a>
                </div>
            </form>
        </div>

        <style>
        .form-field input:focus,
        .form-field select:focus {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
        }
        
        .form-field input.error,
        .form-field select.error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }
        
        .success-toast {
            animation: slideInRight 0.3s ease-out;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Enhanced form validation with visual feedback
            function validateField(field) {
                const $field = $(field);
                const value = $field.val().trim();
                const isRequired = $field.prop('required');
                
                if (isRequired && !value) {
                    $field.addClass('error').removeClass('border-gray-300').addClass('border-red-500');
                    $field.closest('.form-field').find('label').addClass('text-red-600');
                    return false;
                } else {
                    $field.removeClass('error').removeClass('border-red-500').addClass('border-gray-300');
                    $field.closest('.form-field').find('label').removeClass('text-red-600');
                    return true;
                }
            }

            // Real-time validation
            $('#edit-delivery-form input, #edit-delivery-form select').on('blur', function() {
                validateField(this);
            });

            $('#edit-delivery-form').on('submit', function(e) {
                e.preventDefault();
                
                // Validate all fields
                let isValid = true;
                $(this).find('input[required], select[required]').each(function() {
                    if (!validateField(this)) {
                        isValid = false;
                    }
                });

                if (!isValid) {
                    // Show error message with better styling
                    const errorMsg = $('<div class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2">' +
                        '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>' +
                        '</svg>' +
                        '<span>Please fill in all required fields</span>' +
                        '</div>');
                    $('body').append(errorMsg);
                    
                    // Auto-remove error message
                    setTimeout(function() {
                        errorMsg.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 4000);
                    
                    // Scroll to first error
                    $('html, body').animate({
                        scrollTop: $('.error').first().offset().top - 100
                    }, 500);
                    
                    return;
                }

                const formData = $(this).serializeArray();
                formData.push({
                    name: 'task',
                    value: $('#delivery_id').val() === '0' ? 'create_delivery' : 'update_delivery'
                });

                // Show loading state with better UX
                const $btn = $('#save-delivery-btn');
                const originalText = $btn.find('span').text();
                $btn.prop('disabled', true)
                    .find('span').text('Saving...');
                
                // Add loading spinner
                $btn.prepend('<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">' +
                    '<circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>' +
                    '<path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>' +
                    '</svg>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: $.param(formData),
                    success: function(response) {
                        if (response.success) {
                            // Enhanced success message
                            const successMsg = $('<div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2 success-toast">' +
                                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>' +
                                '</svg>' +
                                '<span>Delivery saved successfully!</span>' +
                                '</div>');
                            $('body').append(successMsg);
                            
                            setTimeout(function() {
                                successMsg.fadeOut(function() {
                                    $(this).remove();
                                    location.reload();
                                });
                            }, 2000);
                        } else {
                            // Enhanced error message
                            const errorMsg = $('<div class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2">' +
                                '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>' +
                                '</svg>' +
                                '<span>Error: ' + (response.data || 'Unknown error occurred') + '</span>' +
                                '</div>');
                            $('body').append(errorMsg);
                            
                            setTimeout(function() {
                                errorMsg.fadeOut(function() {
                                    $(this).remove();
                                });
                            }, 5000);
                        }
                    },
                    error: function() {
                        // Network error message
                        const errorMsg = $('<div class="fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2">' +
                            '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>' +
                            '</svg>' +
                            '<span>Network error occurred. Please try again.</span>' +
                            '</div>');
                        $('body').append(errorMsg);
                        
                        setTimeout(function() {
                            errorMsg.fadeOut(function() {
                                $(this).remove();
                            });
                        }, 5000);
                    },
                    complete: function() {
                        // Reset button state
                        $btn.prop('disabled', false)
                            .find('span').text(originalText);
                        $btn.find('svg.animate-spin').remove();
                    }
                });
            });

            // Add visual feedback for form interactions
            $('#edit-delivery-form input, #edit-delivery-form select').on('focus', function() {
                $(this).closest('.form-field').addClass('ring-2 ring-blue-500 ring-opacity-50');
            }).on('blur', function() {
                $(this).closest('.form-field').removeClass('ring-2 ring-blue-500 ring-opacity-50');
            });
        });
        </script>
        <?php
        return ob_get_clean();
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
        // Check if we're viewing a specific delivery
        if (isset($_GET['view_delivery']) && is_numeric($_GET['view_delivery'])) {
            // Set the delivery_id parameter for the view_deliveries_page function
            $_GET['delivery_id'] = $_GET['view_delivery'];
            self::view_deliveries_page();
            return;
        }

        // Enqueue necessary scripts for AJAX functionality
        wp_enqueue_script('kitscript', plugin_dir_url(__FILE__) . '../js/kitscript.js', ['jquery'], null, true);
        wp_enqueue_script('waybill-pagination', plugin_dir_url(__FILE__) . '../../js/waybill-pagination.js', ['jquery'], null, true);

        $deliveries = self::get_all_deliveries();

        // Get delivery statistics
        $total_deliveries = count($deliveries);
        $scheduled_deliveries = array_filter($deliveries, function ($d) {
            return $d->status === 'scheduled';
        });
        $scheduled_count = count($scheduled_deliveries);
        $in_transit_deliveries = array_filter($deliveries, function ($d) {
            return $d->status === 'in_transit';
        });
        $in_transit_count = count($in_transit_deliveries);
        $delivered_deliveries = array_filter($deliveries, function ($d) {
            return $d->status === 'delivered';
        });
        $delivered_count = count($delivered_deliveries);
        $delivered_countries = array_unique(array_column($deliveries, 'destination_country_name'));
        $countries_count = count($delivered_countries);
    ?>

        <div class="wrap deliveries-page">
            <?php
            echo KIT_Commons::showingHeader([
                'title' => 'Deliveries Management',
                'desc' => 'Manage and track all delivery operations',
            ]);
            ?>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Total Deliveries</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($total_deliveries); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Scheduled</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($scheduled_count); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">In Transit</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($in_transit_count); ?></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600">Countries Served</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo number_format($countries_count); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabbed Interface -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8 px-6" aria-label="Tabs">
                        <button id="tab-all-deliveries" class="tab-button active py-4 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            Show All Deliveries
                        </button>
                        <button id="tab-add-delivery" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300">
                            <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Delivery
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <!-- Tab 1: Show All Deliveries -->
                    <div id="tab-content-all-deliveries" class="tab-content active">
                        <!-- View Toggle Buttons -->
                        <div class="flex justify-between items-center mb-6">
                            <div class="flex space-x-2">
                                <button id="block-view-btn" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium transition-colors">
                                    Bloc2k View
                                </button>
                                <button id="table-view-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium transition-colors hover:bg-gray-300">
                                    Table View
                                </button>
                            </div>
                            <div class="text-sm text-gray-600">
                                <?php echo count($deliveries); ?> deliveries found
                            </div>
                        </div>

                        <?php if (!empty($deliveries)): ?>
                            <!-- Block View -->
                            <?php
                            // Include the DeliveryCard component
                            require_once plugin_dir_path(__FILE__) . '../components/deliveryCard.php';

                            // Use the component to render the delivery grid
                            echo KIT_DeliveryCard::renderGrid($deliveries, [
                                'show_actions' => true,
                                'show_truck' => true,
                                'show_waybill_count' => false,
                                'grid_class' => 'grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6',
                                'empty_message' => 'No deliveries found'
                            ]);
                            ?>

                        <?php else: ?>
                            <div class="text-center py-12">
                                <div class="mx-auto w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No deliveries yet</h3>
                                <p class="text-gray-600 mb-6">Get started by creating your first delivery</p>
                                <button id="create-first-delivery" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                                    Create First Delivery
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab 2: Add Delivery -->
                <div id="tab-content-add-delivery" class="tab-content hidden">
                    <div class="max-w-2xl mx-auto">
                        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                            <div class="mb-6">
                                <h2 class="text-xl font-semibold text-gray-900">Add New Delivery</h2>
                                <p class="text-sm text-gray-600 mt-1">Create a new delivery entry</p>
                            </div>

                            <form id="delivery-form" class="space-y-6">
                                <input type="hidden" name="action" value="kit_deliveries_crud">
                                <input type="hidden" name="delivery_id" id="delivery_id" value="0">
                                <?php wp_nonce_field('get_waybills_nonce', 'nonce'); ?>

                                <!-- Reference Number -->
                                <div class="form-field">
                                    <label for="delivery_reference" class="block text-sm font-medium text-gray-700 mb-2">
                                        Reference Number
                                    </label>
                                    <input type="text" name="delivery_reference" id="delivery_reference"
                                        readonly value="<?= KIT_Deliveries::generateDeliveryRef() ?>"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50">
                                </div>

                                <!-- Origin -->
                                <div class="form-field">
                                    <label for="origin_country_select" class="block text-sm font-medium text-gray-700 mb-2">
                                        Origin Country
                                    </label>
                                    <?php echo KIT_Deliveries::CountrySelect('origin_country', 'origin_country_select', null, true); ?>
                                </div>

                                <div class="form-field">
                                    <label for="origin_city_select" class="block text-sm font-medium text-gray-700 mb-2">
                                        Origin City
                                    </label>
                                    <select name="origin_city" id="origin_city_select" required class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select City</option>
                                    </select>
                                </div>

                                <!-- Destination -->
                                <div class="form-field">
                                    <label for="destination_country_select" class="block text-sm font-medium text-gray-700 mb-2">
                                        Destination Country
                                    </label>
                                    <?php echo KIT_Deliveries::CountrySelect('destination_country', 'destination_country_select', null, true); ?>
                                </div>

                                <div class="form-field">
                                    <label for="destination_city_select" class="block text-sm font-medium text-gray-700 mb-2">
                                        Destination City
                                    </label>
                                    <select name="destination_city" id="destination_city_select" required class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select City</option>
                                    </select>
                                </div>

                                <!-- Dispatch Date -->
                                <div class="form-field">
                                    <label for="dispatch_date" class="block text-sm font-medium text-gray-700 mb-2">
                                        Dispatch Date
                                    </label>
                                    <input type="date" name="dispatch_date" id="dispatch_date" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- Truck Number -->
                                <div class="form-field">
                                    <label for="truck_number" class="block text-sm font-medium text-gray-700 mb-2">
                                        Truck Number
                                    </label>
                                    <input type="text" name="truck_number" id="truck_number" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Enter truck number">
                                </div>

                                <!-- Status -->
                                <div class="form-field">
                                    <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                                        Delivery Status
                                    </label>
                                    <?php
                                    $deliveries_status = [
                                        'scheduled' => 'Scheduled',
                                        'in_transit' => 'In Transit',
                                        'delivered' => 'Delivered'
                                    ];
                                    echo KIT_Commons::simpleSelect(
                                        '',
                                        'status',
                                        'status',
                                        $deliveries_status,
                                        'scheduled'
                                    );
                                    ?>
                                </div>

                                <!-- Form Actions -->
                                <div class="flex gap-3 pt-4">
                                    <button type="submit" id="save-delivery-btn"
                                        class="btn-primary flex-1">
                                        Save Delivery
                                    </button>
                                    <button type="button" id="cancel-edit"
                                        class="btn-secondary hidden">
                                        Cancel
                                    </button>

                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Quick Actions Section - Bottom of Page -->
            <div class="mt-8">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="?page=08600-waybill-create" class="flex items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg border border-blue-200 transition-colors group">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-blue-200">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Create Waybill</h4>
                                <p class="text-sm text-gray-600">Generate new waybill</p>
                            </div>
                        </a>

                        <a href="?page=08600-customers" class="flex items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg border border-green-200 transition-colors group">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-green-200">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Manage Customers</h4>
                                <p class="text-sm text-gray-600">View and edit customers</p>
                            </div>
                        </a>

                        <a href="?page=route-management" class="flex items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg border border-purple-200 transition-colors group">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-purple-200">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 4m0 13V4m-6 3l6-3"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Manage Routes</h4>
                                <p class="text-sm text-gray-600">Configure shipping routes</p>
                            </div>
                        </a>

                        <a href="?page=warehouse-waybills" class="flex items-center p-4 bg-orange-50 hover:bg-orange-100 rounded-lg border border-orange-200 transition-colors group">
                            <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3 group-hover:bg-orange-200">
                                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-medium text-gray-900">Warehouse</h4>
                                <p class="text-sm text-gray-600">Manage warehouse waybills</p>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .tab-content {
                display: none;
            }

            .tab-content.active {
                display: block !important;
            }

            .tab-button {
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .tab-button.active {
                border-bottom-color: #3b82f6 !important;
                color: #2563eb !important;
            }
        </style>

        <!-- Edit Delivery Modal -->
        <div id="edit-delivery-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden flex items-center justify-center p-4" style="display: none;">
            <div class="relative mx-auto w-11/12 md:w-3/4 lg:w-1/2 shadow-xl rounded-xl bg-white max-h-[90vh] overflow-y-auto">
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900" id="modal-title">Edit Delivery</h3>
                    <button id="close-modal" class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-lg hover:bg-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <div id="modal-content" class="overflow-y-auto">
                        <!-- Modal content will be loaded here -->
                        <div class="flex items-center justify-center py-12">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Define ajaxurl for WordPress admin
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            
            // Tab functionality
            document.addEventListener('DOMContentLoaded', function() {
                const tabButtons = document.querySelectorAll('.tab-button');
                const tabContents = document.querySelectorAll('.tab-content');

                function switchTab(tabId) {
                    // Hide all tab contents
                    tabContents.forEach(content => {
                        content.style.display = 'none';
                        content.classList.add('hidden');
                        content.classList.remove('active');
                    });

                    // Remove active class from all tab buttons
                    tabButtons.forEach(button => {
                        button.classList.remove('active', 'border-blue-500', 'text-blue-600');
                        button.classList.add('border-transparent', 'text-gray-500');
                    });

                    // Show selected tab content
                    const selectedContent = document.getElementById('tab-content-' + tabId);
                    if (selectedContent) {
                        selectedContent.style.display = 'block';
                        selectedContent.classList.remove('hidden');
                        selectedContent.classList.add('active');
                    }

                    // Activate selected tab button
                    const selectedButton = document.getElementById('tab-' + tabId);
                    if (selectedButton) {
                        selectedButton.classList.add('active', 'border-blue-500', 'text-blue-600');
                        selectedButton.classList.remove('border-transparent', 'text-gray-500');
                    }
                }

                // Add click event listeners to tab buttons
                tabButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const tabId = this.id.replace('tab-', '');
                        switchTab(tabId);
                    });
                });

                // Initialize with first tab active
                switchTab('all-deliveries');

                // Debug: Log tab elements to console
                console.log('Tab buttons found:', tabButtons.length);
                console.log('Tab contents found:', tabContents.length);
            });

            

            jQuery(document).ready(function($) {
                // Set minimum date for dispatch date
                const dispatchDateInput = document.getElementById('dispatch_date');
                const today = new Date().toISOString().split('T')[0];
                dispatchDateInput.min = today;

                // Handle form submission
                $('#delivery-form').on('submit', function(e) {
                    e.preventDefault();

                    // Basic form validation
                    const requiredFields = ['origin_country', 'destination_country', 'dispatch_date', 'truck_number'];
                    let isValid = true;

                    requiredFields.forEach(function(fieldName) {
                        const field = document.querySelector(`[name="${fieldName}"]`);
                        if (!field.value.trim()) {
                            field.classList.add('border-red-500');
                            isValid = false;
                        } else {
                            field.classList.remove('border-red-500');
                        }
                    });

                    if (!isValid) {
                        alert('Please fill in all required fields.');
                        return;
                    }

                    const formData = $(this).serializeArray();
                    formData.push({
                        name: 'task',
                        value: $('#delivery_id').val() === '0' ? 'create_delivery' : 'update_delivery'
                    });

                    // Show loading state
                    $('#save-delivery-btn').prop('disabled', true).text('Saving...');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: $.param(formData),
                        success: function(response) {
                            if (response.success) {
                                // Show success message
                                const successMsg = $('<div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">Delivery saved successfully!</div>');
                                $('body').append(successMsg);
                                setTimeout(function() {
                                    successMsg.fadeOut(function() {
                                        $(this).remove();
                                    });
                                    location.reload();
                                }, 2000);
                            } else {
                                alert('Error: ' + (response.data || 'Unknown error occurred'));
                            }
                        },
                        error: function() {
                            alert('Network error occurred. Please try again.');
                        },
                        complete: function() {
                            $('#save-delivery-btn').prop('disabled', false).text('Save Delivery');
                        }
                    });
                });

                // Handle "Add Delivery" button
                $('#add-delivery-btn, #create-first-delivery').on('click', function() {
                    // Switch to add delivery tab
                    switchTab('add-delivery');
                    $('#delivery-form input:first').focus();
                });

                // Test country change functionality
                $('#test-country-change').on('click', function() {
                    console.log('Testing country change...');
                    const originCountrySelect = document.getElementById('origin_country_select');
                    if (originCountrySelect && originCountrySelect.value) {
                        handleCountryChange(originCountrySelect.value, 'origin');
                    } else {
                        alert('Please select a country first');
                    }
                });

                // Handle edit delivery
                window.editDelivery = function(deliveryId) {
                    console.log('Opening modal for delivery ID:', deliveryId);
                    
                    // Show modal
                    $('#edit-delivery-modal').removeClass('hidden').show();
                    
                    // Get the AJAX URL - try multiple sources
                    const ajaxUrl = window.ajaxurl || (window.myPluginAjax && window.myPluginAjax.ajax_url) || '/wp-admin/admin-ajax.php';
                    console.log('Using AJAX URL:', ajaxUrl);
                    
                    // Load delivery data via AJAX
                    $.ajax({
                        url: ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'kit_deliveries_crud',
                            task: 'get_delivery',
                            id: deliveryId,
                            nonce: $('#delivery-form input[name="nonce"]').val()
                        },
                        success: function(response) {
                            console.log('AJAX response:', response);
                            if (response.success) {
                                // Load the delivery form in the modal
                                loadDeliveryFormInModal(response.data, deliveryId);
                            } else {
                                alert('Error loading delivery data: ' + (response.data || 'Unknown error'));
                                $('#edit-delivery-modal').addClass('hidden').hide();
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('AJAX error:', error);
                            console.error('Status:', status);
                            console.error('Response:', xhr.responseText);
                            console.error('XHR:', xhr);
                            alert('Network error occurred while loading delivery data. Please check the console for details.');
                            $('#edit-delivery-modal').addClass('hidden').hide();
                        }
                    });
                };

                // Function to load delivery form in modal
                function loadDeliveryFormInModal(delivery, deliveryId) {
                    // Create form HTML
                    const formHtml = `
                        <form id="modal-delivery-form" class="space-y-8">
                            <input type="hidden" name="action" value="kit_deliveries_crud">
                            <input type="hidden" name="delivery_id" value="${deliveryId}">
                            <input type="hidden" name="nonce" value="${$('#delivery-form input[name="nonce"]').val()}">

                            <!-- Reference Number -->
                            <div class="form-field">
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Reference Number
                                    </span>
                                </label>
                                <input type="text" name="delivery_reference" value="${delivery.delivery_reference || ''}" 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 text-gray-700 font-mono text-sm" readonly>
                                <p class="mt-2 text-xs text-gray-500">Auto-generated reference number</p>
                            </div>

                            <!-- Route Information -->
                            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                                <h3 class="text-base font-semibold text-gray-800 mb-6 flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    Route Information
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Origin -->
                                    <div class="form-field">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                Origin Country
                                            </span>
                                        </label>
                                        <select name="origin_country" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                            <option value="">Select Origin Country</option>
                                            <option value="1" ${delivery.origin_country_id == 1 ? 'selected' : ''}>South Africa</option>
                                            <option value="2" ${delivery.origin_country_id == 2 ? 'selected' : ''}>Zimbabwe</option>
                                            <option value="3" ${delivery.origin_country_id == 3 ? 'selected' : ''}>Zambia</option>
                                        </select>
                                    </div>

                                    <!-- Destination -->
                                    <div class="form-field">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                Destination Country
                                            </span>
                                        </label>
                                        <select name="destination_country" onchange="handleCountryChange(this.value, 'destination')" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                            <option value="">Select Destination Country</option>
                                            <option value="1" ${delivery.destination_country_id == 1 ? 'selected' : ''}>South Africa</option>
                                            <option value="2" ${delivery.destination_country_id == 2 ? 'selected' : ''}>Zimbabwe</option>
                                            <option value="3" ${delivery.destination_country_id == 3 ? 'selected' : ''}>Zambia</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Destination City -->
                                <div class="form-field mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Destination City
                                        </span>
                                    </label>
                                    <select name="destination_city" id="destination_city_select" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                        <option value="">Select Destination City</option>
                                        <!-- Cities will be loaded dynamically based on selected country -->
                                    </select>
                                </div>
                            </div>

                            <!-- Delivery Details -->
                            <div class="bg-gray-50 rounded-xl p-6 border border-gray-200">
                                <h3 class="text-base font-semibold text-gray-800 mb-6 flex items-center">
                                    <svg class="w-5 h-5 mr-3 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Delivery Details
                                </h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <!-- Dispatch Date -->
                                    <div class="form-field">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                Dispatch Date
                                            </span>
                                        </label>
                                        <input type="date" name="dispatch_date" value="${delivery.dispatch_date || ''}" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white">
                                    </div>

                                    <!-- Truck Number -->
                                    <div class="form-field">
                                        <label class="block text-sm font-medium text-gray-700 mb-3">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                Truck Number
                                            </span>
                                        </label>
                                        <input type="text" name="truck_number" value="${delivery.truck_number || ''}" required
                                               class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors bg-white"
                                               placeholder="Enter truck number">
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="form-field mt-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-3">
                                        <span class="flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Delivery Status
                                        </span>
                                    </label>
                                    <select name="status" class="w-full px-4 py-3 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                                        <option value="scheduled" ${delivery.status === 'scheduled' ? 'selected' : ''}>Scheduled</option>
                                        <option value="in_transit" ${delivery.status === 'in_transit' ? 'selected' : ''}>In Transit</option>
                                        <option value="delivered" ${delivery.status === 'delivered' ? 'selected' : ''}>Delivered</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex gap-4 pt-8 border-t border-gray-200">
                                <button type="submit" id="modal-save-btn"
                                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-6 py-4 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center space-x-3 disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span>Update Delivery</span>
                                </button>
                                <button type="button" id="modal-cancel-btn"
                                    class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 px-6 py-4 rounded-lg font-medium transition-colors duration-200 flex items-center justify-center space-x-3">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    <span>Cancel</span>
                                </button>
                            </div>
                        </form>
                    `;
                    
                    $('#modal-content').html(formHtml);
                    
                    // Handle modal form submission
                    $('#modal-delivery-form').on('submit', function(e) {
                        e.preventDefault();
                        
                        const formData = $(this).serializeArray();
                        formData.push({
                            name: 'task',
                            value: 'update_delivery'
                        });

                        // Show loading state
                        const $btn = $('#modal-save-btn');
                        const originalText = $btn.find('span').text();
                        $btn.prop('disabled', true).find('span').text('Saving...');
                        
                        // Get the AJAX URL - try multiple sources
                        const ajaxUrl = window.ajaxurl || (window.myPluginAjax && window.myPluginAjax.ajax_url) || '/wp-admin/admin-ajax.php';
                        console.log('Using AJAX URL for form submission:', ajaxUrl);
                        
                        $.ajax({
                            url: ajaxUrl,
                            type: 'POST',
                            data: $.param(formData),
                            success: function(response) {
                                console.log('Form submission response:', response);
                                if (response.success) {
                                    // Show success message
                                    const successMsg = $('<div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center space-x-2">' +
                                        '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                                        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>' +
                                        '</svg>' +
                                        '<span>Delivery updated successfully!</span>' +
                                        '</div>');
                                    $('body').append(successMsg);
                                    
                                    setTimeout(function() {
                                        successMsg.fadeOut(function() {
                                            $(this).remove();
                                            $('#edit-delivery-modal').addClass('hidden').hide();
                                            location.reload();
                                        });
                                    }, 2000);
                                } else {
                                    alert('Error: ' + (response.data || 'Unknown error occurred'));
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Form submission AJAX error:', error);
                                console.error('Status:', status);
                                console.error('Response:', xhr.responseText);
                                alert('Network error occurred. Please try again. Check console for details.');
                            },
                            complete: function() {
                                $btn.prop('disabled', false).find('span').text(originalText);
                            }
                        });
                    });
                    
                    // Handle cancel button
                    $('#modal-cancel-btn').on('click', function() {
                        $('#edit-delivery-modal').addClass('hidden').hide();
                    });
                }

                // Handle modal close
                $('#close-modal').on('click', function() {
                    $('#edit-delivery-modal').addClass('hidden').hide();
                });

                // Close modal when clicking outside
                $('#edit-delivery-modal').on('click', function(e) {
                    if (e.target === this) {
                        $(this).addClass('hidden').hide();
                    }
                });

                // Handle delete delivery
                window.deleteDelivery = function(deliveryId) {
                    if (confirm('Are you sure you want to delete this delivery?')) {
                        // TODO: Implement delete functionality
                        alert('Delete functionality will be implemented');
                    }
                };

                // Add visual feedback for form interactions
                $('input, select').on('focus', function() {
                    $(this).parent().addClass('ring-2 ring-blue-500 ring-opacity-50');
                }).on('blur', function() {
                    $(this).parent().removeClass('ring-2 ring-blue-500 ring-opacity-50');
                });

                // View toggle functionality
                $('#block-view-btn').on('click', function() {
                    $('#block-view').removeClass('hidden');
                    $('#table-view').addClass('hidden');
                    $('#block-view-btn').removeClass('bg-gray-200 text-gray-700').addClass('bg-blue-600 text-white');
                    $('#table-view-btn').removeClass('bg-blue-600 text-white').addClass('bg-gray-200 text-gray-700');
                });

                $('#table-view-btn').on('click', function() {
                    $('#table-view').removeClass('hidden');
                    $('#block-view').addClass('hidden');
                    $('#table-view-btn').removeClass('bg-gray-200 text-gray-700').addClass('bg-blue-600 text-white');
                    $('#block-view-btn').removeClass('bg-blue-600 text-white').addClass('bg-gray-200 text-gray-700');
                });
            });
        </script>
    <?php
    }

    public static function handle_ajax()
    {



        check_ajax_referer('get_waybills_nonce', 'nonce');

        if (!current_user_can('kit_view_waybills') && !current_user_can('edit_pages')) {
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

        return $updated !== false ? true : false;
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

        exit(var_dump($wpdb->last_query));
        return $updated !== false ? true : false;
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

        return $updated !== false ? true : false;
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
            'destination_city_id' => isset($data['destination_city']) ? sanitize_text_field($data['destination_city']) : 1, // Default to first city if not provided
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
        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            name="<?php echo esc_attr($name); ?>"
            id="<?php echo esc_attr($id); ?>"
            <?php echo $required; ?>
            onchange="handleCountryChange(this.value, '<?php echo $type; ?>')">
            <option value="">Select Country</option>
            <?php foreach ($countries as $country): ?>
                <option value="<?php echo esc_attr($country->id); ?>" <?php echo ($country_id == $country->id) ? 'selected' : ''; ?>>
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

        // If no country is selected, show empty dropdown
        if (!$country_id) {
            $cities = [];
        } else {
            $cities = KIT_Deliveries::get_Cities_forCountry($country_id);
        }
    ?>
        <select class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            name="<?php echo esc_attr($name); ?>"
            id="<?php echo esc_attr($id); ?>"
            <?php echo $required; ?>>
            <option value="">Select City</option>
            <?php if ($cities && is_array($cities)): ?>
                <?php foreach ($cities as $city): ?>
                    <option value="<?php echo esc_attr($city->id); ?>" <?php echo ($city_id == $city->id) ? 'selected' : ''; ?>>
                        <?php echo esc_html($city->city_name); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>
<?php
        return ob_get_clean();
    }

    public static function handle_get_cities_for_country_callback()
    {
        // Debug: Log the POST data
        error_log('AJAX request received: ' . print_r($_POST, true));

        // Verify nonce
        if (!isset($_POST['nonce'])) {
            error_log('Nonce not found in POST data');
            wp_send_json_error('Nonce not found');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'get_waybills_nonce')) {
            error_log('Nonce verification failed. Received: ' . $_POST['nonce']);
            wp_send_json_error('Invalid security token');
        }

        if (!isset($_POST['country_id'])) {
            wp_send_json_error('Country ID is required');
        }

        $country_id = intval($_POST['country_id']);

        if (!$country_id) {
            wp_send_json_error('Invalid country ID');
        }

        // Get cities for the country
        $cities = KIT_Deliveries::get_Cities_forCountry($country_id);

        if ($cities && is_array($cities) && count($cities) > 0) {
            wp_send_json_success($cities);
        } else {
            wp_send_json_error('No cities found for this country');
        }
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
        echo '<th>' . ucfirst(str_replace('_', ' ', (string)($col ?? ''))) . '</th>';
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
