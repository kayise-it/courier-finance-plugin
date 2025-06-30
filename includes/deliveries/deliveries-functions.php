<?php
if (!defined('ABSPATH')) {
    exit;
}

class KIT_Deliveries
{
    public static function init()
    {
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        add_action('wp_ajax_kit_deliveries_crud', [__CLASS__, 'handle_ajax']);
        add_action('wp_ajax_delivery_changeTo_Intransit', [__CLASS__, 'delivery_changeTo_Intransit']);
        add_action('wp_ajax_delivery_changeTo_Delivered', [__CLASS__, 'delivery_changeTo_Delivered']);
        add_action('wp_ajax_delivery_changeTo_Scheduled', [__CLASS__, 'delivery_changeTo_Scheduled']);
        add_action('wp_ajax_get_scheduled_deliveries', [__CLASS__, 'getScheduledDeliveries']);
        add_action('wp_ajax_get_customers', [__CLASS__, 'get_customers']);
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

        if (!$direction_id || !$total_volume_m3) {
            wp_send_json_error(['message' => 'Missing direction_id or volume.']);
        }

        $table = $wpdb->prefix . 'kit_shipping_rates_volume';

        $rate_per_m3 = $wpdb->get_var($wpdb->prepare("
        SELECT rate_per_m3
        FROM $table
        WHERE direction_id = %d
          AND %f BETWEEN min_volume AND max_volume
        LIMIT 1", $direction_id, $total_volume_m3));

        if ($rate_per_m3 !== null) {
            wp_send_json_success(['rate_per_m3' => $rate_per_m3]);
        } else {
            wp_send_json_error(['message' => 'No matching volumetric rate found.']);
        }
    }

    public static function handle_get_price_per_kg()
    {
        global $wpdb;

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'get_waybills_nonce')) {
            wp_send_json_error(['message' => 'Invalid security token.']);
        }

        $direction_id = isset($_POST['direction_id']) ? intval($_POST['direction_id']) : 0;
        $total_mass_kg = isset($_POST['total_mass_kg']) ? floatval($_POST['total_mass_kg']) : 0;

        if (!$direction_id || !$total_mass_kg) {
            wp_send_json_error(['message' => 'Missing direction_id or total_mass_kg.']);
        }

        $table = $wpdb->prefix . 'kit_shipping_rates_mass';

        $rate_per_kg = $wpdb->get_var($wpdb->prepare("
        SELECT rate_per_kg
        FROM $table
        WHERE direction_id = %d
          AND %f BETWEEN min_weight AND max_weight
        LIMIT 1", $direction_id, $total_mass_kg));

        if ($rate_per_kg !== null) {
            wp_send_json_success(['rate_per_kg' => $rate_per_kg]);
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
        echo '<pre>';
        print_r($_POST);
        echo '</pre>';
        exit();
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
                ";

        if (!empty($country_code)) {
            $query .= $wpdb->prepare(" AND destination_country = %s", $country_code);
        }

        $query .= " ORDER BY dispatch_date ASC";
        return $wpdb->get_results($query);
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
                    <div class="md:col-span-3">
                    <div class=" bg-white rounded-lg shadow p-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr>
                                    <th class="text-left py-2 pr-4 text-gray-600 font-medium">Reference Number</th>
                                    <td class="py-2 text-gray-900"><?php echo esc_html($delivery->delivery_reference); ?></td>
                                </tr>
                                <tr>
                                    <th class="text-left py-2 pr-4 text-gray-600 font-medium">Origin Country</th>
                                    <td class="py-2 text-gray-900"><?php echo esc_html($delivery->origin_country); ?></td>
                                </tr>
                                <tr>
                                    <th class="text-left py-2 pr-4 text-gray-600 font-medium">Destination Country</th>
                                    <td class="py-2 text-gray-900"><?php echo esc_html($delivery->destination_country); ?></td>
                                </tr>
                                <tr>
                                    <th class="text-left py-2 pr-4 text-gray-600 font-medium">Dispatch Date</th>
                                    <td class="py-2 text-gray-900">
                                        <?php echo esc_html(date('Y-m-d', strtotime($delivery->dispatch_date))); ?></td>
                                </tr>
                                <tr>
                                    <th class="text-left py-2 pr-4 text-gray-600 font-medium">Truck Number</th>
                                    <td class="py-2 text-gray-900"><?php echo esc_html($delivery->truck_number); ?></td>
                                </tr>
                                <tr>
                                    <th class="text-left py-2 pr-4 text-gray-600 font-medium">Status</th>
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
                                    <th class="text-left py-2 pr-4 text-gray-600 font-medium">Created By</th>
                                    <td class="py-2 text-gray-900">
                                        <?php echo esc_html(self::get_customer_name($delivery->created_by)); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>
                    <div class="md:col-span-5 bg-white rounded-lg shadow p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-xl font-semibold text-gray-700">Waybills</h2>
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
                                $waybillsandItems = KIT_Waybills::waybillLists($delivery->id);
                                KIT_Waybills::render_waybills_with_items($waybillsandItems);
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
        $wpdb->get_results($query);
        exit(var_dump($wpdb->last_query));
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

    public static function render_admin_page()
    {
    ?>

        <div class="wrap">
            <?php
            echo do_shortcode('[showheader title="Deliveries Management" desc="Manage all deliveries here."]');
            ?>
            <div class="">
                <div class="grid grid-cols-12 gap-4">
                    <div class="col-span-3 bg-white rounded-lg shadow-md p-6 mb-8">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4">Add New Delivery</h2>
                        <form id="delivery-form" class="space-y-4 max-w-3xl">
                            <input type="hidden" name="action" value="kit_deliveries_crud">
                            <input type="hidden" name="delivery_id" id="delivery_id" value="0">
                            <?php wp_nonce_field('kit_deliveries_nonce', 'nonce'); ?>

                            <div class="space-y-2">
                                <label for="delivery_reference" class="block text-xs font-medium text-gray-700">Reference
                                    Number</label>
                                <input type="text" name="delivery_reference" id="delivery_reference" readonly value="<?= KIT_Deliveries::generateDeliveryRef() ?>"
                                    class="text-xs w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="grid grid-cols-1 gap-4">
                                <div class="space-y-2">
                                    <?php
                                    /* echo KIT_Deliveries::CountrySelect('origin_country', 'origin_country');  */
                                    $shippingDirections = KIT_Deliveries::shippingDirections();
                                    echo KIT_Commons::SelectInput('Direction', 'direction_id', 'direction_id', $shippingDirections);
                                    ?>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                                    $deliveries_status
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
                    </div>

                    <!-- Deliveries List Table -->
                    <div class="col-span-9 bg-white rounded-lg shadow-md p-6">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4">Deliveries List</h2>
                        <div class="overflow-x-auto">
                            <table id="deliveries-table" class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="<?= KIT_Commons::thClasses() ?>">
                                            Reference</th>
                                        <th scope="col"
                                            class="<?= KIT_Commons::thClasses() ?>">
                                            Origin</th>
                                        <th scope="col"
                                            class="<?= KIT_Commons::thClasses() ?>">
                                            Dispatch 321Date</th>
                                        <th scope="col"
                                            class="<?= KIT_Commons::thClasses() ?>">
                                            Status</th>
                                        <th scope="col"
                                            class="<?= KIT_Commons::thClasses() ?>">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach (self::get_all_deliveries() as $delivery): ?>
                                        <tr data-id="<?php echo $delivery->id; ?>" class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-900"><?php echo $delivery->delivery_reference; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-900"><?php echo $delivery->origin_country_name; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-900"><?php echo date('Y-m-d', strtotime($delivery->dispatch_date)); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-900">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $delivery->status === 'delivered' ? 'bg-green-100 text-green-800' : ($delivery->status === 'in_transit' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800'); ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $delivery->status)); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs font-medium">
                                                <?php if ($delivery->status === 'scheduled'): ?>
                                                    <button class="change-delivery-status mr-2 text-blue-600 hover:text-blue-900"
                                                        data-id="<?php echo $delivery->id; ?>">Ready to Leave</button>
                                                <?php endif; ?>
                                                <?php //Else if equal to in_transit
                                                if ($delivery->status === 'in_transit'): ?>
                                                    <button class="change-delivery-arrived mr-2 text-blue-600 hover:text-blue-900"
                                                        data-id="<?php echo $delivery->id; ?>">Arrived</button>
                                                <?php endif; ?>
                                                <!-- If the status is equal to in_transit this show this -->

                                                <a href="?page=view-deliveries&delivery_id=<?php echo $delivery->id; ?>"
                                                    class="mr-2 text-blue-600 hover:text-blue-900"
                                                    data-id="<?php echo $delivery->id; ?>">View</a>
                                                <button class="delete-delivery text-red-600 hover:text-red-900"
                                                    data-id="<?php echo $delivery->id; ?>">Del23ete</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                // Handle form submission
                $('#delivery-form').on('submit', function(e) {
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

                // Edit delivery
                $('.edit-delivery').on('click', function() {
                    const id = $(this).data('id');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'kit_deliveries_crud',
                            nonce: $('#nonce').val(),
                            task: 'get_delivery',
                            id: id
                        },
                        success: function(response) {
                            if (response.success) {
                                const delivery = response.data;
                                $('#delivery_id').val(delivery.id);
                                $('#delivery_reference').val(delivery.delivery_reference);
                                $('#origin_country').val(delivery.origin_country);
                                $('#destination_country').val(delivery.destination_country);
                                $('#destination_city').val(delivery.destination_city);
                                $('#dispatch_date').val(delivery.dispatch_date);
                                $('#truck_number').val(delivery.truck_number);
                                $('#status').val(delivery.status);

                                $('#cancel-edit').show();
                            } else {
                                console.log("78yh" + response.data);
                            }
                        }
                    });
                });

                //Change delivery status from scheduled to In-transit, action: 'delivery_changeTo_Intransit',
                $('.change-delivery-status').on('click', function() {
                    const id = $(this).data('id');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'delivery_changeTo_Intransit',
                            nonce: $('#nonce').val(),
                            id: id
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                console.log("io" + response.data);
                            }
                        }
                    });
                });

                //Change delivery status from in_transit to delivered
                $('.change-delivery-arrived').on('click', function() {
                    const id = $(this).data('id');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'delivery_changeTo_Delivered',
                            nonce: $('#nonce').val(),
                            id: id
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                console.log("oji" + response.data);
                            }
                        }
                    });
                });

                // Delete delivery
                $('.delete-delivery').on('click', function() {
                    if (confirm('Are you sure you want to delete this delivery?')) {
                        const id = $(this).data('id');

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'kit_deliveries_crud',
                                nonce: $('#nonce').val(),
                                task: 'delete_delivery',
                                id: id
                            },
                            success: function(response) {
                                if (response.success) {
                                    location.reload();
                                } else {
                                    console.log("78g8" + response.data);
                                }
                            }
                        });
                    }
                });

                // Cancel edit
                $('#cancel-edit').on('click', function() {
                    $('#delivery-form')[0].reset();
                    $('#delivery_id').val(0);
                    $(this).hide();
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

        $task = $_POST['task'] ?? '';
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

        if ($result === false) {
            wp_send_json_error('Operation failed');
        } else {
            wp_send_json_success($result);
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
            sd.description AS direction_description,

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
    public static function save_delivery($data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_deliveries';

        $delivery_data = [
            'delivery_reference' => sanitize_text_field($data['delivery_reference']),
            'direction_id' => sanitize_text_field($data['direction_id']),
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
            // Create new delivery
            $wpdb->insert($table, $delivery_data);
            return $wpdb->insert_id;
        }
    }
    public static function delete_delivery($id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_deliveries';
        return $wpdb->delete($table, ['id' => intval($id)]);
    }
}

KIT_Deliveries::init();

function render_waybills_with_items($waybillsandItems) {
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
