<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include user roles for permission checking
require_once plugin_dir_path(__FILE__) . '../user-roles.php';

class KIT_Customers
{
    public static function init()
    {
        add_action('admin_post_update_customer', [self::class, 'handle_update_customer']);
        add_action('wp_ajax_save_customer_ajax', [self::class, 'handle_save_customer_ajax']);
        add_action('wp_ajax_nopriv_save_customer_ajax', [self::class, 'handle_save_customer_ajax']);
        add_action('wp_ajax_test_customer_ajax', [self::class, 'test_customer_ajax']);
        add_action('wp_ajax_get_cities_by_country', [self::class, 'handle_get_cities_by_country']);
        add_action('wp_ajax_nopriv_get_cities_by_country', [self::class, 'handle_get_cities_by_country']);
    }
    public static function gamaCustomer($id)
    {

        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';
        return $wpdb->get_var("SELECT name FROM $table_name WHERE cust_id=" . $id);
    }
    public static function idCustomer($id)
    {

        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';
        return $wpdb->get_var("SELECT id FROM $table_name WHERE cust_id=" . $id);
    }

    public static function save_customer($cust)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';

        // Optional debug (disabled in production)
        // error_log('save_customer called with data: ' . print_r($cust, true));

        // First check if customer already exists
        $existing_customer = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE name = %s AND surname = %s",
                sanitize_text_field($cust['name'] ?? $cust['customer_name'] ?? ''),
                sanitize_text_field($cust['surname'] ?? $cust['customer_surname'] ?? '')
            )
        );

        if ($existing_customer) {
            // Customer already exists, return their ID or some indicator
            return $existing_customer->cust_id;
        }


        // Sanitize inputs
        $city_id = isset($cust['city_id']) && $cust['city_id'] !== '' ? intval($cust['city_id']) : null; // NULL for FK when empty
        // Default empty company name to 'Individual'
        $company_name = sanitize_text_field($cust['company_name'] ?? '');
        if ($company_name === '') {
            $company_name = 'Individual';
        }

        // Handle email - convert empty string to null for database
        $email_address = isset($cust['email_address']) && trim($cust['email_address']) !== ''
            ? sanitize_email(trim($cust['email_address']))
            : null;

        $cust_data = [
            'cust_id'  => rand(1000, 9999),
            'name'     => sanitize_text_field($cust['name'] ?? $cust['customer_name'] ?? ''),
            'surname'  => sanitize_text_field($cust['surname'] ?? $cust['customer_surname'] ?? ''),
            'cell'     => sanitize_text_field($cust['cell'] ?? ''),
            'email_address'  => $email_address,
            'address'  => sanitize_text_field($cust['address'] ?? ''),
            'country_id'  => intval($cust['country_id'] ?? 0),
            'city_id'  => $city_id,
            'company_name'  => $company_name,
            'vat_number'  => sanitize_text_field($cust['vat_number'] ?? ''),
        ];

        // Insert into DB
        // Specify data types to allow NULL for city_id and email_address
        $inserted = $wpdb->insert($table_name, $cust_data, [
            '%d',
            '%s',
            '%s',
            '%s',
            ($email_address === null ? null : '%s'),
            '%s',
            '%d',
            ($city_id === null ? null : '%d'),
            '%s',
            '%s'
        ]);

        // Optional debug
        // if ($inserted === false) {
        //     error_log('Database error: ' . $wpdb->last_error);
        // }

        if ($inserted === false) {
            return false; // Insert failed
        }

        // error_log('Customer saved successfully with ID: ' . $cust_data['cust_id']);
        return $cust_data['cust_id']; // Return the new customer ID
    }

    public static function update_customer($cust_id, $data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';

        // Sanitize input data
        $update_data = [];
        if (isset($data['company_name'])) {
            $update_data['company_name'] = sanitize_text_field($data['company_name']);
        }
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['surname'])) {
            $update_data['surname'] = sanitize_text_field($data['surname']);
        }
        if (isset($data['cell'])) {
            $update_data['cell'] = sanitize_text_field($data['cell']);
        }
        if (isset($data['address'])) {
            $update_data['address'] = sanitize_text_field($data['address']);
        }
        if (isset($data['email_address'])) {
            // Handle email - convert empty string to null for database
            $email_value = trim($data['email_address']);
            $update_data['email_address'] = $email_value !== '' ? sanitize_email($email_value) : null;
        }
        // Only update country_id if a valid value (> 0) is provided
        if (isset($data['country_id']) && intval($data['country_id']) > 0) {
            $update_data['country_id'] = intval($data['country_id']);
        }
        // Only update city_id if a valid value (> 0) is provided
        if (isset($data['city_id']) && intval($data['city_id']) > 0) {
            $update_data['city_id'] = intval($data['city_id']);
        }

        // Only update if there is data
        if (!empty($update_data)) {
            $updated = $wpdb->update(
                $table_name,
                $update_data,
                ['cust_id' => intval($cust_id)]
            );
            return $updated !== false;
        }
        return false;
    }

    public static function handle_update_customer()
    {
        if (!isset($_POST['cust_update_nonce']) || !wp_verify_nonce($_POST['cust_update_nonce'], 'update_customer_nonce')) {
            wp_die('Nonce verification failed');
        }

        $cust_id = intval($_POST['cust_id'] ?? $_POST['customer_id'] ?? 0);

        $data = [
            'company_name' => sanitize_text_field($_POST['company_name']),
            'name'    => sanitize_text_field($_POST['name']),
            'surname' => sanitize_text_field($_POST['surname']),
            'cell'    => sanitize_text_field($_POST['cell']),
            'address' => sanitize_textarea_field($_POST['address']),
            'email_address' => sanitize_text_field($_POST['email_address']),
        ];

        // Handle field name mismatch: form submits 'origin_country' and 'origin_city' 
        // Only include if valid values are provided (don't overwrite with 0)
        $country_id_value = null;
        if (isset($_POST['origin_country']) && $_POST['origin_country'] !== '' && $_POST['origin_country'] !== '0') {
            $country_id_value = intval($_POST['origin_country']);
        } elseif (isset($_POST['country_id']) && $_POST['country_id'] !== '' && $_POST['country_id'] !== '0') {
            $country_id_value = intval($_POST['country_id']);
        }
        
        if ($country_id_value !== null && $country_id_value > 0) {
            $data['country_id'] = $country_id_value;
        }
        
        $city_id_value = null;
        if (isset($_POST['origin_city']) && $_POST['origin_city'] !== '' && $_POST['origin_city'] !== '0') {
            $city_id_value = intval($_POST['origin_city']);
        } elseif (isset($_POST['city_id']) && $_POST['city_id'] !== '' && $_POST['city_id'] !== '0') {
            $city_id_value = intval($_POST['city_id']);
        }
        
        if ($city_id_value !== null && $city_id_value > 0) {
            $data['city_id'] = $city_id_value;
        }

        // 🔥 Call your method here
        $updated = KIT_Customers::update_customer($cust_id, $data);

        //get the customer id
        $id = KIT_Customers::idCustomer($cust_id);

        if ($updated) {
            // Redirect with success parameter (toast will be shown on redirected page)
            wp_redirect(admin_url('admin.php?page=08600-customers&view_customer=' . $cust_id . '&updated=1'));
            exit;
        }


        exit;
    }

    public static function handle_save_customer_ajax()
    {
        // Debug: Log the request
        error_log('Customer AJAX request received: ' . print_r($_POST, true));

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'customer_nonce')) {
            error_log('Customer AJAX nonce failed');
            wp_send_json_error(['message' => 'Security check failed']);
        }

        // Check if required fields are present
        if (empty($_POST['name']) || empty($_POST['surname']) || empty($_POST['cell']) || empty($_POST['company_name']) || empty($_POST['address'])) {
            error_log('Customer AJAX missing required fields');
            wp_send_json_error(['message' => 'Please fill in all required fields (Company Name, First Name, Last Name, Cell, Address)']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';

        // Generate a unique customer ID
        do {
            $cust_id = rand(1000, 9999);
            $exists = $wpdb->get_var($wpdb->prepare("SELECT cust_id FROM $table_name WHERE cust_id = %d", $cust_id));
        } while ($exists);

        // Sanitize inputs
        $company_name = sanitize_text_field($_POST['company_name'] ?? '');
        if ($company_name === '') {
            $company_name = 'Individual';
        }

        // Handle email - convert empty string to null for database
        $email_address = isset($_POST['email_address']) && trim($_POST['email_address']) !== ''
            ? sanitize_email(trim($_POST['email_address']))
            : null;

        $cust_data = [
            'cust_id'  => $cust_id,
            'name'     => sanitize_text_field($_POST['name'] ?? ''),
            'surname'  => sanitize_text_field($_POST['surname'] ?? ''),
            'cell'     => sanitize_text_field($_POST['cell'] ?? ''),
            'address'  => sanitize_text_field($_POST['address'] ?? ''),
            'email_address' => $email_address,
            'company_name' => $company_name,
            'country_id' => intval($_POST['country_id'] ?? 0),
            'city_id' => intval($_POST['city_id'] ?? 0),
            'vat_number' => sanitize_text_field($_POST['vat_number'] ?? ''),
        ];

        // Insert into DB
        $inserted = $wpdb->insert($table_name, $cust_data);

        if ($inserted) {
            wp_send_json_success(['message' => 'Customer saved successfully! 🎉', 'customer_id' => $cust_id]);
        } else {
            $error_message = 'Failed to save customer.';
            if ($wpdb->last_error) {
                $error_message .= ' Database Error: ' . $wpdb->last_error;
            }
            wp_send_json_error(['message' => $error_message]);
        }
    }

    public static function test_customer_ajax()
    {
        wp_send_json_success(['message' => 'AJAX is working!', 'post_data' => $_POST]);
    }

    public static function handle_get_cities_by_country()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'customer_nonce')) {
            wp_send_json_error(['message' => 'Security check failed']);
        }

        $country_id = intval($_POST['country_id'] ?? 0);

        if (!$country_id) {
            wp_send_json_error(['message' => 'Country ID is required']);
        }

        global $wpdb;
        $cities_table = $wpdb->prefix . 'kit_cities';

        $cities = $wpdb->get_results($wpdb->prepare(
            "SELECT id, city_name FROM $cities_table WHERE country_id = %d ORDER BY city_name ASC",
            $country_id
        ));

        if ($cities) {
            wp_send_json_success($cities);
        } else {
            wp_send_json_success([]); // Return empty array if no cities found
        }
    }

    /**
     * Render customer form for modal or inline use
     * 
     * @param array $atts Array of attributes:
     *   - form_action: Form action URL
     *   - customer: Customer data (null for new)
     *   - is_modal: Whether rendered in modal (boolean)
     * @return string HTML form content
     */
    public static function render_customer_form($atts = [])
    {
        $atts = shortcode_atts([
            'form_action' => admin_url('admin-post.php'),
            'customer' => null,
            'is_modal' => false,
        ], $atts);

        $form_action = esc_url($atts['form_action']);
        $customer = $atts['customer'];
        $is_modal = $atts['is_modal'];

        // Get countries
        require_once plugin_dir_path(__FILE__) . '../deliveries/deliveries-functions.php';
        $countries = KIT_Deliveries::getCountriesObject();

        ob_start();
?>
        <form id="add-customer-form" method="post" action="<?php echo $form_action; ?>" class="space-y-6">
            <input type="hidden" name="action" value="add_customer">
            <?php wp_nonce_field('add_customer_nonce', 'customer_nonce'); ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
                    <input type="text" name="company_name" id="company_name" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                        value="<?php echo esc_attr($customer['company_name'] ?? $_POST['company_name'] ?? ''); ?>">
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                    <input type="text" name="name" id="name" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                        value="<?php echo esc_attr($customer['name'] ?? $_POST['name'] ?? ''); ?>">
                </div>

                <div>
                    <label for="surname" class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                    <input type="text" name="surname" id="surname" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                        value="<?php echo esc_attr($customer['surname'] ?? $_POST['surname'] ?? ''); ?>">
                </div>

                <div>
                    <label for="cell" class="block text-sm font-medium text-gray-700 mb-2">Cell Phone *</label>
                    <input type="tel" name="cell" id="cell" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                        value="<?php echo esc_attr($customer['cell'] ?? $_POST['cell'] ?? ''); ?>">
                </div>

                <div>
                    <label for="email_address" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                    <input type="email" name="email_address" id="email_address" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                        value="<?php echo esc_attr($customer['email_address'] ?? $_POST['email_address'] ?? ''); ?>">
                </div>

                <div>
                    <label for="country_id" class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                    <select name="country_id" id="country_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Select Country</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?php echo esc_attr($country->id); ?>"
                                <?php selected($customer['country_id'] ?? $_POST['country_id'] ?? '', $country->id); ?>>
                                <?php echo esc_html($country->country_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="city_id" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                    <select name="city_id" id="city_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Select City</option>
                    </select>
                </div>

                <div>
                    <label for="vat_number" class="block text-sm font-medium text-gray-700 mb-2">VAT Number</label>
                    <input type="text" name="vat_number" id="vat_number"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                        placeholder="VAT registration number"
                        value="<?php echo esc_attr($customer['vat_number'] ?? $_POST['vat_number'] ?? ''); ?>">
                </div>
            </div>

            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
                <textarea name="address" id="address" rows="3" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"><?php echo esc_textarea($customer['address'] ?? $_POST['address'] ?? ''); ?></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                <?php if (!$is_modal): ?>
                    <a href="<?php echo admin_url('admin.php?page=08600-customers'); ?>"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        Cancel
                    </a>
                <?php endif; ?>
                <?php echo KIT_Commons::renderButton('Save Customer', 'primary', 'lg', ['type' => 'submit']); ?>
            </div>
        </form>

        <script>
            jQuery(document).ready(function($) {
                // Handle country change to load cities
                $('#country_id').on('change', function() {
                    var countryId = $(this).val();
                    var citySelect = $('#city_id');

                    citySelect.html('<option value="">Loading cities...</option>');

                    if (countryId) {
                        $.ajax({
                            url: ajaxurl || '/wp-admin/admin-ajax.php',
                            type: 'POST',
                            data: {
                                action: 'get_cities_by_country',
                                country_id: countryId,
                                nonce: '<?php echo wp_create_nonce('customer_nonce'); ?>'
                            },
                            success: function(response) {
                                citySelect.html('<option value="">Select City</option>');
                                if (response.success && response.data) {
                                    $.each(response.data, function(index, city) {
                                        citySelect.append($('<option>', {
                                            value: city.id,
                                            text: city.city_name
                                        }));
                                    });
                                }
                            },
                            error: function() {
                                citySelect.html('<option value="">Error loading cities</option>');
                            }
                        });
                    } else {
                        citySelect.html('<option value="">Select City</option>');
                    }
                });

                // Form will submit normally - server handles redirect
            });
        </script>
    <?php
        return ob_get_clean();
    }

    //After the new customer is saved, go back to the customer table and update the cust_id to the new customer id
    public static function update_customer_id($customer_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';

        // Update the customer ID
        $updated = $wpdb->update($table_name, ['cust_id' => $customer_id, 'id' => $customer_id]);

        return $updated;
    }

    public static function delete_customer($id)
    {
        global $wpdb;
        $customer_id = intval($id);

        // First, delete all waybills for this customer
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $wpdb->delete($waybills_table, ['customer_id' => $customer_id]);

        // Now delete the customer
        $customers_table = $wpdb->prefix . 'kit_customers';
        $deleted = $wpdb->delete($customers_table, ['cust_id' => $customer_id]);

        if ($deleted) {
            if (!class_exists('KIT_Toast')) {
                require_once plugin_dir_path(__FILE__) . '../components/toast.php';
            }
            KIT_Toast::ensure_toast_loads();
            echo KIT_Toast::success('Customer deleted successfully.', 'Customer Deleted');
            wp_redirect(admin_url('admin.php?page=customers-dashboard'));
            exit;
        } else {
            if (!class_exists('KIT_Toast')) {
                require_once plugin_dir_path(__FILE__) . '../components/toast.php';
            }
            KIT_Toast::ensure_toast_loads();
            echo KIT_Toast::warning('Customer not found or already deleted.', 'Warning');
        }
    }

    public static function tholaMaCustomer()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        return $wpdb->get_results("
        SELECT 
            c.id, 
            c.cust_id, 
            c.name as customer_name, 
            c.surname as customer_surname, 
            c.email_address, 
            c.cell, 
            c.address, 
            c.country_id, 
            c.city_id,
            country.country_name,
            city.city_name,
            c.company_name,
            COUNT(w.id) as total_waybills
        FROM $table_name c
        LEFT JOIN {$wpdb->prefix}kit_operating_countries country ON c.country_id = country.id
        LEFT JOIN {$wpdb->prefix}kit_operating_cities city ON c.city_id = city.id
        LEFT JOIN $waybills_table w ON w.customer_id = c.cust_id
        GROUP BY c.id, c.cust_id, c.name, c.surname, c.email_address, c.cell, c.address, c.country_id, c.city_id, country.country_name, city.city_name, c.company_name
        ");
    }

    /**
     * Upload a CSV or Excel file and create customers for each row.
     * Accepts a file input named 'customers_file'.
     * Returns an array with 'created', 'errors'.
     */
    public static function upload_customers_csv_excel()
    {
        if (!isset($_FILES['customers_file']) || $_FILES['customers_file']['error'] !== UPLOAD_ERR_OK) {
            return ['created' => 0, 'errors' => ['No file uploaded or upload error']];
        }

        $file = $_FILES['customers_file']['tmp_name'];
        $filename = $_FILES['customers_file']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $rows = [];
        $errors = [];

        // Parse CSV
        if ($ext === 'csv') {
            if (($handle = fopen($file, 'r')) !== false) {
                // Skip first empty line
                fgetcsv($handle, 0, ';');

                // Get headers from second line
                $header = fgetcsv($handle, 0, ';');
                if ($header === false || count($header) < 6) {
                    fclose($handle);
                    return ['created' => 0, 'errors' => ['Invalid CSV format or headers']];
                }

                // Remove empty first column from headers
                $header = array_slice($header, 1);
                $header = array_map('trim', $header);

                // Process data rows
                $lineNumber = 2; // Start after header
                while (($data = fgetcsv($handle, 0, ';')) !== false) {
                    $lineNumber++;

                    // Skip empty rows
                    if (count($data) <= 1) continue;

                    // Remove empty first column
                    $data = array_slice($data, 1);
                    $data = array_map('trim', $data);

                    // Validate row
                    if (count($data) !== count($header)) {
                        $errors[] = "Line $lineNumber: Skipped - column count mismatch";
                        continue;
                    }

                    try {
                        $rowData = array_combine($header, $data);

                        // Map CSV fields to database fields
                        $mappedData = [
                            'cust_id' => $rowData['cust_id'] ?? '',
                            'customer_name' => $rowData['name'] ?? '',
                            'customer_surname' => $rowData['surname'] ?? '',
                            'cell' => $rowData['cell'] ?? '',
                            'email_address' => $rowData['email_address'] ?? '',
                            'address' => $rowData['address'] ?? '',
                            'country_id' => $rowData['country_id'] ?? '',
                            'city_id' => $rowData['city_id'] ?? '',
                            'company_name' => $rowData['company_name'] ?? '',


                        ];

                        // Validate required fields
                        if (empty($mappedData['customer_name']) || empty($mappedData['customer_surname'])) {
                            $errors[] = "Line $lineNumber: Skipped - missing name or surname";
                            continue;
                        }

                        $rows[] = $mappedData;
                    } catch (ValueError $e) {
                        $errors[] = "Line $lineNumber: Skipped - " . $e->getMessage();
                    }
                }
                fclose($handle);
            } else {
                return ['created' => 0, 'errors' => ['Failed to open CSV file']];
            }
        } elseif (in_array($ext, ['xlsx', 'xls'])) {
            if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                require_once ABSPATH . 'vendor/autoload.php';
            }
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
                $sheet = $spreadsheet->getActiveSheet();

                $header = [];
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $cellIterator = $row->getCellIterator();
                    $cellIterator->setIterateOnlyExistingCells(false);
                    $rowData = [];
                    foreach ($cellIterator as $cell) {
                        $rowData[] = $cell->getValue();
                    }

                    if ($rowIndex === 1) {
                        $header = $rowData;
                    } else {
                        if (count($rowData) === count($header)) {
                            try {
                                $rowData = array_combine($header, $rowData);
                                $rows[] = [
                                    'cust_id' => $rowData['cust_id'] ?? '',
                                    'customer_name' => $rowData['name'] ?? '',
                                    'customer_surname' => $rowData['surname'] ?? '',
                                    'cell' => $rowData['cell'] ?? '',
                                    'email_address' => $rowData['email_address'] ?? '',
                                    'address' => $rowData['address'] ?? '',
                                    'country_id'  => $rowData['country_id'] ?? '',
                                    'city_id'  => $rowData['city_id'] ?? '',
                                    'company_name' => $rowData['company_name'] ?? '',
                                ];
                            } catch (ValueError $e) {
                                $errors[] = "Excel row $rowIndex: " . $e->getMessage();
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                return ['created' => 0, 'errors' => ['Excel parse error: ' . $e->getMessage()]];
            }
        } else {
            return ['created' => 0, 'errors' => ['Unsupported file type']];
        }

        // Create customers
        $created = 0;
        foreach ($rows as $row) {
            $result = self::save_customer($row);
            if (is_wp_error($result)) {
                $errors[] = $result->get_error_message();
            } else {
                $created++;
            }
        }

        return [
            'created' => $created,
            'errors' => $errors,
            'total_rows' => count($rows)
        ];
    }

    /**
     * Render the customer CSV/Excel upload form and handle upload in the admin UI.
     */
    public static function render_upload_customers_form()
    {
        $output = '';
        // Handle form submission
        if (!empty($_POST['upload_customers_csv_excel_nonce']) && isset($_FILES['customers_file'])) {
            // Check nonce if in WordPress
            if (function_exists('wp_verify_nonce')) {
                if (!wp_verify_nonce($_POST['upload_customers_csv_excel_nonce'], 'upload_customers_csv_excel')) {
                    $output .= '<div class="notice notice-error"><p>Security check failed.</p></div>';
                } else {
                    $result = self::upload_customers_csv_excel();
                    if ($result['created'] > 0) {
                        $output .= '<div class="notice notice-success"><p>' . $result['created'] . ' customers created successfully.</p></div>';
                    }
                    if (!empty($result['errors'])) {
                        $output .= '<div class="notice notice-error"><ul>';
                        foreach ($result['errors'] as $err) {
                            $output .= '<li>' . htmlspecialchars($err) . '</li>';
                        }
                        $output .= '</ul></div>';
                    }
                }
            } else {
                // If not in WordPress, skip nonce check
                $result = self::upload_customers_csv_excel();
                if ($result['created'] > 0) {
                    $output .= '<div class="notice notice-success"><p>' . $result['created'] . ' customers created successfully.</p></div>';
                }
                if (!empty($result['errors'])) {
                    $output .= '<div class="notice notice-error"><ul>';
                    foreach ($result['errors'] as $err) {
                        $output .= '<li>' . htmlspecialchars($err) . '</li>';
                    }
                    $output .= '</ul></div>';
                }
            }
        }
        // Render the form
        $output .= '<form method="post" enctype="multipart/form-data">';
        if (function_exists('wp_nonce_field')) {
            $output .= wp_nonce_field('upload_customers_csv_excel', 'upload_customers_csv_excel_nonce', true, false);
        }
        $output .= '<h3>Bulk Upload Customers (CSV or Excel)</h3>';
        $output .= '<input type="file" name="customers_file" accept=".csv,.xlsx,.xls" required> ';
        $output .= KIT_Commons::renderButton('Upload', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]);
        $output .= '</form>';
        return $output;
    }

    /**
     * Example: Integrate the upload form into the customer dashboard page.
     * Call this in your customer dashboard rendering logic.
     */
    public static function customer_dashboard_page()
    {
        // Enqueue required scripts
        wp_enqueue_script('kitscript', plugin_dir_url(__FILE__) . '../js/kitscript.js', ['jquery'], null, true);

        // Localize script with AJAX data
        wp_localize_script('kitscript', 'customerAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('customer_nonce')
        ));

        // Handle delete customer action
        if (isset($_GET['delete_customer']) && !empty($_GET['delete_customer'])) {
            $customer_id = intval($_GET['delete_customer']);

            // Check if user has permission to delete customers
            if (current_user_can('manage_options') || current_user_can('administrator')) {
                delete_customer($customer_id, true);
                wp_redirect(admin_url('admin.php?page=08600-customers&deleted=1&tab=manage-customers'));
                exit;
            } else {
                wp_die('Sorry, you are not allowed to delete customers.');
            }
        }

        // Handle view customer action
        if (isset($_GET['view_customer']) && !empty($_GET['view_customer'])) {
            customer_detail_view($_GET['view_customer']);
            return;
        }

        // Handle edit customer action
        if (isset($_GET['edit_customer']) && !empty($_GET['edit_customer'])) {
            edit_customer_form($_GET['edit_customer']);
            return;
        }

        // Handle download PDF customer summary action
        if (isset($_GET['download_pdf_customer_summary']) && !empty($_GET['download_pdf_customer_summary'])) {
            $customer_id = intval($_GET['download_pdf_customer_summary']);

            // Get all waybill numbers for this customer
            global $wpdb;
            $waybills_table = $wpdb->prefix . 'kit_waybills';
            $waybill_nos = $wpdb->get_col($wpdb->prepare(
                "SELECT waybill_no FROM $waybills_table WHERE customer_id = %d ORDER BY waybill_no ASC",
                $customer_id
            ));

            if (!empty($waybill_nos)) {
                // Use pdf-customer-bulk.php to generate customer summary with all waybills
                // Go up 2 levels from includes/customers/ to plugin root
                $plugin_url = dirname(dirname(plugin_dir_url(__FILE__)));
                $pdf_url = add_query_arg([
                    'selected_ids' => implode(',', $waybill_nos),
                    'customer_id' => $customer_id
                ], $plugin_url . '/pdf-customer-bulk.php');

                wp_redirect($pdf_url);
                exit;
            } else {
                // No waybills found for this customer
                if (class_exists('KIT_Toast')) {
                    KIT_Toast::ensure_toast_loads();
                    echo KIT_Toast::error('No waybills found for this customer.', 'No Waybills');
                }
                wp_redirect(admin_url('admin.php?page=08600-customers&view_customer=' . $customer_id));
                exit;
            }
        }

        // Handle customer update form submission
        if (isset($_POST['action']) && $_POST['action'] === 'update_customer') {
            if (wp_verify_nonce($_POST['cust_update_nonce'], 'update_customer_nonce')) {
                $customer_id = intval($_POST['customer_id']);

                // Update customer data - build base array
                $update_data = array(
                    'name' => sanitize_text_field($_POST['name']),
                    'surname' => sanitize_text_field($_POST['surname']),
                    'cell' => sanitize_text_field($_POST['cell']),
                    'email_address' => sanitize_email($_POST['email_address']),
                    'address' => sanitize_textarea_field($_POST['address']),
                    'company_name' => sanitize_text_field($_POST['company_name']),
                    'vat_number' => sanitize_text_field($_POST['vat_number'])
                );

                // Handle field name mismatch: form submits 'origin_country' and 'origin_city' 
                // Only update if a non-empty value is provided (don't overwrite with 0)
                // Check both field name formats and only update if we have a valid value
                $country_id_value = null;
                if (isset($_POST['origin_country']) && $_POST['origin_country'] !== '' && $_POST['origin_country'] !== '0') {
                    $country_id_value = intval($_POST['origin_country']);
                } elseif (isset($_POST['country_id']) && $_POST['country_id'] !== '' && $_POST['country_id'] !== '0') {
                    $country_id_value = intval($_POST['country_id']);
                }
                
                if ($country_id_value !== null && $country_id_value > 0) {
                    $update_data['country_id'] = $country_id_value;
                }
                
                $city_id_value = null;
                if (isset($_POST['origin_city']) && $_POST['origin_city'] !== '' && $_POST['origin_city'] !== '0') {
                    $city_id_value = intval($_POST['origin_city']);
                } elseif (isset($_POST['city_id']) && $_POST['city_id'] !== '' && $_POST['city_id'] !== '0') {
                    $city_id_value = intval($_POST['city_id']);
                }
                
                if ($city_id_value !== null && $city_id_value > 0) {
                    $update_data['city_id'] = $city_id_value;
                }

                $updated = $wpdb->update(
                    $wpdb->prefix . 'kit_customers',
                    $update_data,
                    array('cust_id' => $customer_id)
                );

                if ($updated !== false) {
                    wp_redirect(admin_url('admin.php?page=08600-customers&view_customer=' . $customer_id . '&updated=1'));
                    exit;
                } else {
                    wp_redirect(admin_url('admin.php?page=08600-customers&view_customer=' . $customer_id . '&error=1'));
                    exit;
                }
            }
        }

        // Data
        $customers = tholaMaCustomer();
        $total_customers = is_array($customers) ? count($customers) : 0;
        $active_customers_count = is_array($customers)
            ? count(array_filter($customers, function ($c) {
                return !empty($c->company_name);
            }))
            : 0;
        $inactive_customers = max(0, $total_customers - $active_customers_count);

        // Toast after delete success
        if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
            $deleted_waybills = isset($_GET['waybills_deleted']) ? intval($_GET['waybills_deleted']) : 0;
            $msg = 'Customer deleted successfully';
            if ($deleted_waybills > 0) {
                $msg .= ' • Deleted ' . $deleted_waybills . ' waybill(s)';
            }
            require_once plugin_dir_path(__FILE__) . '../components/toast.php';
            echo KIT_Toast::success($msg);
        }

        // Show success message if customer was just added
        if (isset($_GET['customer_added']) && $_GET['customer_added'] == '1') {
            if (!class_exists('KIT_Toast')) {
                require_once plugin_dir_path(__FILE__) . '../components/toast.php';
            }
            KIT_Toast::ensure_toast_loads();
            echo KIT_Toast::success('Customer added successfully!', 'Customer Added');
        }

        // Overview
        require_once plugin_dir_path(__FILE__) . '../components/quickStats.php';
        require_once plugin_dir_path(__FILE__) . '../components/dashboardQuickies.php';
        require_once plugin_dir_path(__FILE__) . '../components/iconButton.php';

        // Get customer statistics
        $total_customers = count($customers);
        $active_customers = array_filter($customers, function ($c) {
            return !empty($c->company_name);
        });
        $active_customers_count = count($active_customers);

        // UI Shell


    ?>
        <div class="wrap" style="max-width: 100vw; overflow-x: hidden;">
            <?php
            // Include modal component
            require_once plugin_dir_path(__FILE__) . '../components/modal.php';

            // Render customer form for modal
            $customer_form_content = self::render_customer_form();

            // Render Add Customer Modal
            $add_customer_modal = KIT_Modal::render(
                'add-customer-modal',
                'Add New Customer',
                $customer_form_content,
                '3xl',
                true,
                'Add Customer'
            );

            echo KIT_Commons::showingHeader([
                'title' => 'Customers Dashboard',
                'desc'  => '',
                'content' => $add_customer_modal,
                'icon' => KIT_Commons::icon('user-group'),
            ]);

            // Sort customers by name A-Z by default (only if no sort parameter is set)
            if (!isset($_GET['orderby']) || empty($_GET['orderby'])) {
                usort($customers, function ($a, $b) {
                    // Handle both customer_name/customer_surname and name/surname formats
                    $name_a = trim(($a->customer_name ?? $a->name ?? '') . ' ' . ($a->customer_surname ?? $a->surname ?? ''));
                    $name_b = trim(($b->customer_name ?? $b->name ?? '') . ' ' . ($b->customer_surname ?? $b->surname ?? ''));
                    return strcasecmp($name_a, $name_b);
                });
            }

            // Get current page and items per page for pagination
            $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
            $items_per_page = isset($_GET['items_per_page']) ? max(5, min(100, intval($_GET['items_per_page']))) : 10;

            // Define columns - Name first, then Company, then Country, then Total Waybills (styled like waybills table)
            $columns = [
                'customer_name' => [
                    'label' => 'Name',
                    'sortable' => true,
                    'searchable' => true,
                    'header_class' => 'w-48 text-left max-w-48',
                    'cell_class' => 'text-left w-48 max-w-48 text-xs',
                    'callback' => function ($value, $row, $rowIndex) {
                        // Handle both object and array formats, and both property naming conventions
                        $name = '';
                        $surname = '';
                        if (is_object($row)) {
                            $name = $row->customer_name ?? $row->name ?? '';
                            $surname = $row->customer_surname ?? $row->surname ?? '';
                        } elseif (is_array($row)) {
                            $name = $row['customer_name'] ?? $row['name'] ?? '';
                            $surname = $row['customer_surname'] ?? $row['surname'] ?? '';
                        }
                        $full_name = trim($name . ' ' . $surname);
                        return esc_html($full_name ?: '—');
                    }
                ],
                'company_name' => [
                    'label' => 'Company',
                    'sortable' => true,
                    'searchable' => true,
                    'header_class' => 'w-52 text-left max-w-52',
                    'cell_class' => 'text-left w-52 max-w-52 text-xs truncate',
                ],
                'country_name' => [
                    'label' => 'Country',
                    'sortable' => true,
                    'searchable' => true,
                    'header_class' => 'w-32 text-left max-w-32',
                    'cell_class' => 'text-left w-32 max-w-32 text-xs',
                ],
                'total_waybills' => [
                    'label' => 'Total Waybills',
                    'sortable' => true,
                    'searchable' => false,
                    'header_class' => 'w-24 text-center max-w-24',
                    'cell_class' => 'text-center w-24 max-w-24 text-xs',
                    'callback' => function ($value, $row, $rowIndex) {
                        $count = 0;
                        if (is_object($row)) {
                            $count = intval($row->total_waybills ?? 0);
                        } elseif (is_array($row)) {
                            $count = intval($row['total_waybills'] ?? 0);
                        }
                        return '<span class="font-semibold">' . esc_html($count) . '</span>';
                    }
                ],
            ];

            // Render table with fallback if unified table class is not available
            if (class_exists('KIT_Unified_Table')) {

                echo KIT_Unified_Table::infinite($customers, $columns, [
                    'actions' => [
                        [
                            'label' => '<span class="sr-only">Edit</span>' . KIT_Icon::svg('edit', 16),
                            'is_html' => true,
                            'title' => 'Edit customer',
                            'href' => '?page=edit-customer&edit_customer={cust_id}',
                            'class' => KIT_Icon::buttonClasses('blue', 'sm')
                        ],
                        'view' => [
                            'label' => '<span class="sr-only">View</span>' . KIT_Icon::svg('eye', 16),
                            'is_html' => true,
                            'title' => 'View customer',
                            'href' => '?page=08600-customers&view_customer={cust_id}',
                            'class' => KIT_Icon::buttonClasses('green', 'sm')
                        ],
                        [
                            'label' => '<span class="sr-only">Delete</span>' . KIT_Icon::svg('trash', 16),
                            'is_html' => true,
                            'title' => 'Delete customer',
                            'href' => '?page=08600-customers&delete_customer={cust_id}',
                            'class' => KIT_Icon::buttonClasses('red', 'sm'),
                            'onclick' => 'return confirm("Are you sure you want to delete this customer? This will also delete all associated waybills.")'
                        ]
                    ],
                    'searchable' => true,
                    'sortable' => true,
                    'selectable' => true,
                    'bulk_actions' => true,
                    'items_per_page' => $items_per_page,
                    'current_page' => $current_page,
                    'show_items_per_page' => true,
                    'exportable' => true,
                    'empty_message' => 'No customers found',
                    'pagination' => true // Enable pagination
                ]);
            } else {
                echo '<div class="overflow-x-auto">';
                echo KIT_Unified_Table::infinite($customers, $columns, [
                    'title' => 'Customers',
                    'actions' => $actions,
                ]);
                echo '</div>';
                echo '</div>';
            }

            ?>
        </div>
    <?php
    }
}
// Initialize
KIT_Customers::init();

function customer_button_with_modal()
{
    ?>
    <div class="p-6">
        <!-- Trigger Button -->
        <?php echo KIT_Commons::renderButton('Open Modal', 'success', 'lg', ['onclick' => 'document.getElementById(\'thaboModal\').classList.remove(\'hidden\')', 'gradient' => true]); ?>

        <!-- Modal Overlay -->
        <div id="thaboModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <!-- Modal Content -->
            <div class="bg-white p-6 rounded-xl shadow-xl w-96 text-center">
                <h2 class="text-xl font-semibold mb-4">Hey Thabo 👋</h2>
                <p class="mb-6">Welcome to the modal!</p>
                <?php echo KIT_Commons::renderButton('Close', 'secondary', 'lg', ['onclick' => 'document.getElementById(\'thaboModal\').classList.add(\'hidden\')']); ?>
            </div>
        </div>
    </div>
<?php
}

function zamazama($customer = null)
{
    ob_start();
?>
    <div class="bg-yellow-100 text-yellow-800 p-4 rounded mb-4">Zamazama function called. Nothing to do here yet.</div>
    <input type="text" name="cust_id" value="232332">
<?php
    return ob_get_clean();
}

function theForm($customer = null)
{
    // Icon SVG definitions - Following brand guidelines: stroke-width="2", text-gray-500 for input icons
    // Brand colors: Primary #2563eb, Secondary #111827, Accent #10b981
    // Icons use currentColor to inherit text color class
    $icon_company = '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-12 18h12" /></svg>';
    $icon_globe = '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.944 11.944 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m-2.284 0A17.919 17.919 0 0112 15.75c-3.314 0-6.288-.815-8.432-2.497M15.432 14.25A17.919 17.919 0 0112 15.75" /></svg>';
    $icon_user = '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>';
    $icon_phone = '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>';
    $icon_email = '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>';
    $icon_map = '<svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>';

?>
    <input type="hidden" name="cust_id" id="cust_id" value="<?= esc_attr($customer['cust_id'] ?? '') ?>">
    
    <div class="space-y-6">
        <!-- Company Information Section -->
        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-gray-300">
            <div class="flex items-center gap-2 mb-5 pb-3 border-b border-gray-200">
                <div class="text-blue-600"><?php echo $icon_company; ?></div>
                <h2 class="text-lg font-semibold text-gray-800">Company Information</h2>
            </div>
            <div class="space-y-4">
                <?= KIT_Commons::Linput([
                    'label' => 'Company Name',
                    'name'  => 'company_name',
                    'id'    => 'company_name',
                    'type'  => 'text',
                    'value' => $customer['company_name'] ?? '',
                    'class' => 'w-full pr-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:border-gray-400',
                    'icon' => $icon_company,
                    'special' => ''
                ]); ?>
            </div>
        </div>

        <!-- Location Section -->
        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-gray-300">
            <div class="flex items-center gap-2 mb-5 pb-3 border-b border-gray-200">
                <div class="text-blue-600"><?php echo $icon_globe; ?></div>
                <h2 class="text-lg font-semibold text-gray-800">Location</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php
                // Get customer location data
                $defaultCountryId = isset($customer['country_id']) && !empty($customer['country_id']) ? intval($customer['country_id']) : 1;
                $defaultCityId = isset($customer['city_id']) && !empty($customer['city_id']) ? intval($customer['city_id']) : 1;
                
                // Country Select
                ?>
                <div class="relative">
                    <label for="origin_country_select" class="<?= KIT_Commons::labelClass() ?>">Origin Country</label>
                    <?php
                    $country_select = KIT_Deliveries::selectAllCountries('origin_country', 'origin_country_select', $defaultCountryId, "required", 'origin', []);
                    // Enhance the select styling
                    $country_select = str_replace(
                        'class="' . KIT_Commons::selectClass() . '"',
                        'class="' . KIT_Commons::selectClass() . ' pl-12 pr-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:border-gray-400"',
                        $country_select
                    );
                    echo $country_select;
                    ?>
                    <div class="absolute left-3 flex items-center pointer-events-none z-10" style="top: calc(1.5rem + 0.25rem); height: 3rem;">
                        <?php echo $icon_globe; ?>
                    </div>
                </div>
                
                <?php
                // City Select
                ?>
                <div class="relative">
                    <label for="origin_city_select" class="<?= KIT_Commons::labelClass() ?>">Origin City</label>
                    <?php
                    $city_select = KIT_Deliveries::selectAllCitiesByCountry('origin_city', 'origin_city_select', $defaultCountryId, $defaultCityId);
                    // Enhance the select styling
                    $city_select = str_replace(
                        'class="' . KIT_Commons::selectClass() . '"',
                        'class="' . KIT_Commons::selectClass() . ' pl-12 pr-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:border-gray-400"',
                        $city_select
                    );
                    echo $city_select;
                    ?>
                    <div class="absolute left-3 flex items-center pointer-events-none z-10" style="top: calc(1.5rem + 0.25rem); height: 3rem;">
                        <?php echo $icon_globe; ?>
                    </div>
                </div>
                
                <input type="hidden" id="origin_country_initial" value="<?= esc_attr($defaultCountryId); ?>">
                <input type="hidden" id="origin_city_initial" value="<?= esc_attr($defaultCityId); ?>">
            </div>
        </div>

        <!-- Personal Information Section -->
        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-gray-300">
            <div class="flex items-center gap-2 mb-5 pb-3 border-b border-gray-200">
                <div class="text-blue-600"><?php echo $icon_user; ?></div>
                <h2 class="text-lg font-semibold text-gray-800">Personal Information</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?= KIT_Commons::Linput([
                    'label' => 'Customer Name',
                    'name'  => 'name',
                    'id'    => 'customer_name',
                    'type'  => 'text',
                    'value' => $customer['name'] ?? '',
                    'class' => 'w-full pr-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:border-gray-400',
                    'icon' => $icon_user,
                    'special' => ''
                ]); ?>
                <?= KIT_Commons::Linput([
                    'label' => 'Customer Surname',
                    'name'  => 'surname',
                    'id'    => 'customer_surname',
                    'type'  => 'text',
                    'value' => $customer['surname'] ?? '',
                    'class' => 'w-full pr-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:border-gray-400',
                    'icon' => $icon_user,
                    'special' => ''
                ]); ?>
            </div>
        </div>

        <!-- Contact Information Section -->
        <div class="bg-gray-50 rounded-xl p-6 border border-gray-200 transition-all duration-200 hover:shadow-md hover:border-gray-300">
            <div class="flex items-center gap-2 mb-5 pb-3 border-b border-gray-200">
                <div class="text-blue-600"><?php echo $icon_phone; ?></div>
                <h2 class="text-lg font-semibold text-gray-800">Contact Information</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?= KIT_Commons::Linput([
                    'label' => 'Cell',
                    'name'  => 'cell',
                    'id'    => 'cell',
                    'type'  => 'text',
                    'value' => $customer['cell'] ?? '',
                    'class' => 'w-full pr-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:border-gray-400',
                    'icon' => $icon_phone,
                    'special' => ''
                ]); ?>
                <?= KIT_Commons::Linput([
                    'label' => 'Email',
                    'name'  => 'email_address',
                    'id'    => 'email_address',
                    'type'  => 'email',
                    'value' => $customer['email_address'] ?? '',
                    'class' => 'w-full pr-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:border-gray-400',
                    'icon' => $icon_email,
                    'special' => ''
                ]); ?>
                <div class="md:col-span-2">
                    <?= KIT_Commons::Linput([
                        'label' => 'Address',
                        'name'  => 'address',
                        'id'    => 'address',
                        'type'  => 'text',
                        'value' => $customer['address'] ?? '',
                        'class' => 'w-full pr-4 py-3 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 hover:border-gray-400',
                        'icon' => $icon_map,
                        'special' => ''
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
<?php
}

function customer_form()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_customers';

    // Edit mode
    $is_edit = false;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $customer = null;

    if ($id) {
        $is_edit = true;
        $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }

    // Handle form submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_submit'])) {
        save_customer();
    }

    ob_start();
?>
    <div class="customer-form-container">
        <!-- Trigger Button -->


        <!-- Modal -->
        <div id="customerModal" class="fixed hidden inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-xl w-full max-w-xl relative">
                <!-- Close Button -->
                <?php echo KIT_Commons::renderButton('×', 'ghost', 'sm', ['id' => 'customerModalClose', 'classes' => 'absolute top-3 right-4 text-gray-600 hover:text-black text-xl']); ?>

                <h2 class="text-xl font-bold mb-4"><?= $is_edit ? 'Edit Customer' : 'Add Customer' ?></h2>
                <div class="">
                    <form method="post" class="space-y-4" id="customerForm" action="">
                        <?php if ($is_edit): ?>
                            <input type="hidden" name="customer_id" value="<?= esc_attr($id) ?>">
                        <?php endif; ?>

                        <div class="">
                            <?php echo theForm(null); ?>
                        </div>

                        <div class="flex justify-end space-x-2">
                            <?php echo KIT_Commons::renderButton('Cancel', 'secondary', 'lg', ['type' => 'button', 'id' => 'customerModalCloseBtn']); ?>
                            <?php echo KIT_Commons::renderButton($is_edit ? 'Update' : 'Save', 'success', 'lg', ['type' => 'submit', 'name' => 'customer_submit', 'id' => 'customerSubmitBtn', 'gradient' => true]); ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = document.getElementById('customerModal');
            var openBtn = document.getElementById('customerModalButton');
            var closeBtn = document.getElementById('customerModalClose');
            var closeBtn2 = document.getElementById('customerModalCloseBtn');
            var form = document.getElementById('customerForm');
            var submitBtn = document.getElementById('customerSubmitBtn');

            // Open modal
            if (openBtn) {
                openBtn.addEventListener('click', function() {
                    modal.classList.remove('hidden');
                });
            }

            // Close modal functions
            function closeModal() {
                modal.classList.add('hidden');
            }

            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (closeBtn2) closeBtn2.addEventListener('click', closeModal);

            // Close on outside click
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeModal();
                }
            });

            // Form submission
            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    // Show loading state
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Saving...';

                    // Submit form via AJAX
                    var formData = new FormData(form);
                    // Ensure company_name defaults to 'Individual' if empty
                    var cn = (formData.get('company_name') || '').trim();
                    if (!cn) {
                        formData.set('company_name', 'Individual');
                    }
                    formData.append('action', 'save_customer_ajax');
                    formData.append('nonce', '<?php echo wp_create_nonce("save_customer_nonce"); ?>');

                    // Debug: Log what we're sending
                    console.log('Sending form data:');
                    for (var pair of formData.entries()) {
                        console.log(pair[0] + ': ' + pair[1]);
                    }

                    fetch(ajaxurl || '/wp-admin/admin-ajax.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(function(response) {
                            console.log('Response status:', response.status);
                            console.log('Response headers:', response.headers);
                            return response.json();
                        })
                        .then(function(data) {
                            console.log('Response data:', data);
                            if (data.success) {
                                // Show success message
                                var successDiv = document.createElement('div');
                                successDiv.className = 'bg-green-100 text-green-800 p-4 rounded mb-4';
                                successDiv.textContent = data.data.message;

                                // Insert before form
                                form.parentNode.insertBefore(successDiv, form);

                                // Close modal after 2 seconds
                                setTimeout(function() {
                                    closeModal();
                                    location.reload(); // Reload page to show new customer
                                }, 2000);
                            } else {
                                throw new Error(data.data.message || 'Unknown error');
                            }
                        })
                        .catch(function(error) {
                            console.error('Error:', error);
                            submitBtn.disabled = false;
                            submitBtn.textContent = '<?= $is_edit ? 'Update' : 'Save' ?>';

                            // Show error message
                            var errorDiv = document.createElement('div');
                            errorDiv.className = 'bg-red-100 text-red-800 p-4 rounded mb-4';
                            errorDiv.textContent = error.message || 'Failed to save customer. Please try again.';
                            form.parentNode.insertBefore(errorDiv, form);
                        });
                });
            }
        });
    </script>
<?php
    return ob_get_clean();
}

function save_customer()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_customers';

    // Generate a unique customer ID
    do {
        $cust_id = rand(1000, 9999);
        $exists = $wpdb->get_var($wpdb->prepare("SELECT cust_id FROM $table_name WHERE cust_id = %d", $cust_id));
    } while ($exists);

    // Sanitize inputs
    // Default empty company name to 'Individual'
    $company_name = sanitize_text_field($_POST['company_name'] ?? '');
    if ($company_name === '') {
        $company_name = 'Individual';
    }

    $cust_data = [
        'cust_id'  => $cust_id,
        'name'     => sanitize_text_field($_POST['name']),
        'surname'  => sanitize_text_field($_POST['surname']),
        'cell'     => sanitize_text_field($_POST['cell']),
        'address'  => sanitize_text_field($_POST['address']),
        'email_address' => sanitize_text_field($_POST['email_address'] ?? ''),
        'company_name' => $company_name,
        'country_id' => intval($_POST['country_id'] ?? 0),
        'city_id' => intval($_POST['city_id'] ?? 0),
    ];

    // Insert into DB
    $inserted = $wpdb->insert($table_name, $cust_data);

    if ($inserted) {
        if (!class_exists('KIT_Toast')) {
            require_once plugin_dir_path(__FILE__) . '../components/toast.php';
        }
        KIT_Toast::ensure_toast_loads();
        echo KIT_Toast::success('Customer saved successfully.', 'Customer Saved');
        wp_redirect(admin_url('admin.php?page=customers-dashboard'));
        exit;
    } else {
        if (!class_exists('KIT_Toast')) {
            require_once plugin_dir_path(__FILE__) . '../components/toast.php';
        }
        KIT_Toast::ensure_toast_loads();
        $error_msg = $wpdb->last_error ? 'Database Error: ' . esc_html($wpdb->last_error) : 'Failed to save customer.';
        echo KIT_Toast::error($error_msg, 'Error');
    }
}

function customer_detail_view($customer_id)
{
    global $wpdb;
    $customer_id = intval($customer_id);

    // Handle bulk actions
    if (isset($_POST['bulk_action']) && isset($_POST['bulk_ids']) && !empty($_POST['bulk_ids'])) {
        if (!current_user_can('kit_view_waybills')) {
            wp_die('Unauthorized');
        }

        $bulk_action = sanitize_text_field($_POST['bulk_action']);
        $bulk_ids = sanitize_text_field($_POST['bulk_ids']);
        $waybill_nos = array_map('trim', explode(',', $bulk_ids));
        $waybill_nos = array_filter($waybill_nos);

        if (!empty($waybill_nos)) {
            $deleted_count = 0;
            $export_count = 0;

            if ($bulk_action === 'delete') {
                // Verify nonce if provided
                if (isset($_POST['bulk_nonce'])) {
                    if (!wp_verify_nonce($_POST['bulk_nonce'], 'bulk_waybill_nonce')) {
                        wp_die('Security check failed');
                    }
                }

                // Delete selected waybills
                if (class_exists('KIT_Waybills')) {
                    foreach ($waybill_nos as $waybill_no) {
                        if (KIT_Waybills::delete_waybill($waybill_no)) {
                            $deleted_count++;
                        }
                    }
                }

                if ($deleted_count > 0) {
                    if (class_exists('KIT_Toast')) {
                        KIT_Toast::ensure_toast_loads();
                        echo KIT_Toast::success("Successfully deleted {$deleted_count} waybill(s).", 'Bulk Delete');
                    }
                    // Redirect to avoid resubmission
                    wp_safe_redirect(admin_url('admin.php?page=08600-customers&view_customer=' . $customer_id . '&bulk_deleted=' . $deleted_count));
                    exit;
                }
            } elseif ($bulk_action === 'export') {
                // Handle bulk export - generate concatenated invoice PDF using pdf-customer-bulk.php
                // Go up 2 levels from includes/customers/ to plugin root
                $plugin_url = dirname(dirname(plugin_dir_url(__FILE__)));
                // Use pdf-customer-bulk.php for bulk customer invoices
                $pdf_url = add_query_arg([
                    'selected_ids' => implode(',', $waybill_nos),
                    'customer_id' => $customer_id
                ], $plugin_url . '/pdf-customer-bulk.php');

                // Redirect to PDF generator which will stream the PDF
                wp_redirect($pdf_url);
                exit;
            }
        }
    }

    // Handle success/error messages
    if (isset($_GET['updated']) && $_GET['updated'] == '1') {
        if (class_exists('KIT_Toast')) {
            KIT_Toast::ensure_toast_loads();
            echo KIT_Toast::success('Customer updated successfully!', 'Customer Update');
        }
    }

    if (isset($_GET['error']) && $_GET['error'] == '1') {
        if (class_exists('KIT_Toast')) {
            KIT_Toast::ensure_toast_loads();
            echo KIT_Toast::error('Failed to update customer. Please try again.', 'Customer Update');
        }
    }

    if (isset($_GET['bulk_deleted'])) {
        if (class_exists('KIT_Toast')) {
            KIT_Toast::ensure_toast_loads();
            echo KIT_Toast::success('Waybill(s) deleted successfully!', 'Bulk Delete');
        }
    }

    // Get customer details
    $customer = get_customer_details($customer_id);

    if (!$customer) {
        if (class_exists('KIT_Toast')) {
            KIT_Toast::ensure_toast_loads();
            echo KIT_Toast::error('Customer not found.', 'Customer Details');
        }
        return;
    }

    // Get waybills for this customer, enriched with delivery and city info
    $waybills = [];
    if (class_exists('KIT_Waybills')) {
        $all_waybills = KIT_Waybills::getAllWaybills();
        foreach ($all_waybills as $wb) {
            if ((int)($wb->customer_id ?? 0) === (int)$customer_id) {
                $waybills[] = $wb;
            }
        }
    }

?>
    <div class="wrap">
        <?php
        echo KIT_Commons::showingHeader([
            'title' => 'Customer Details',
            'desc'  => KIT_Commons::kitButton([
                'color' => 'green',
                'href'  => admin_url('admin.php?page=08600-customers')
            ], 'Back'),
        ]);
        ?>


        <div class="grid grid-cols-5 gap-4">
            <!-- Customer Information Card -->
            <div class="col-span-2 bg-white shadow rounded-lg p-6 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Customer Information</h2>

                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-700 mb-3">Personal Details</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Full Name:</span>
                                <p class="text-gray-900"><?php echo esc_html(($customer['customer_name'] ?? '') . ' ' . ($customer['customer_surname'] ?? '')); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Cell Phone:</span>
                                <p class="text-gray-900"><?php echo esc_html($customer['cell'] ?? 'Not provided'); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Email:</span>
                                <p class="text-gray-900"><?php echo esc_html($customer['email_address'] ?: 'Not provided'); ?></p>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-700 mb-3">Company & Location</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm font-medium text-gray-500">Company:</span>
                                <p class="text-gray-900"><?php echo esc_html($customer['company_name'] ?: 'Not provided'); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Country:</span>
                                <p class="text-gray-900"><?php echo esc_html($customer['country_name'] ?: 'Not specified'); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">City:</span>
                                <p class="text-gray-900"><?php echo esc_html($customer['city_name'] ?: 'Not specified'); ?></p>
                            </div>
                            <div>
                                <span class="text-sm font-medium text-gray-500">Address:</span>
                                <p class="text-gray-900"><?php echo esc_html($customer['address'] ?: 'Not provided'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="flex justify-end gap-2">
                    <!-- Download PDF customer summary, like waybill summary pdf -->
                    <?php
                    // Get all waybill numbers for this customer to generate PDF URL directly
                    global $wpdb;
                    $waybills_table = $wpdb->prefix . 'kit_waybills';
                    $waybill_nos = $wpdb->get_col($wpdb->prepare(
                        "SELECT waybill_no FROM $waybills_table WHERE customer_id = %d ORDER BY waybill_no ASC",
                        $customer_id
                    ));

                    $pdf_url = '';
                    if (!empty($waybill_nos)) {
                        // Generate PDF URL directly (go up 2 levels from includes/customers/ to plugin root)
                        $plugin_url = dirname(dirname(plugin_dir_url(__FILE__)));
                        $pdf_url = add_query_arg([
                            'selected_ids' => implode(',', $waybill_nos),
                            'customer_id' => $customer_id
                        ], $plugin_url . '/pdf-customer-bulk.php');
                    }

                    $pdf_icon = '<svg class="inline-block ml-1 -mt-0.5 w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 20 20"><path d="M12 16v-4m0 4l-2-2m2 2l2-2M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V6.828a2 2 0 00-.586-1.414l-3.828-3.828A2 2 0 0012.172 2H6z"></path></svg>';

                    if (!empty($pdf_url)) {
                        echo KIT_Commons::renderButton('PDF', 'primary', 'lg', [
                            'href' => $pdf_url,
                            'gradient' => true,
                            'icon' => $pdf_icon,
                            'target' => '_blank',
                            'rel' => 'noopener'
                        ]);
                    } else {
                        echo KIT_Commons::renderButton('PDF', 'primary', 'lg', [
                            'href' => '#',
                            'gradient' => true,
                            'icon' => $pdf_icon,
                            'disabled' => true,
                            'title' => 'No waybills available for this customer'
                        ]);
                    }
                    ?>
                    <?php echo KIT_Commons::renderButton('Edit Cusstomer', 'primary', 'lg', ['href' => '?page=08600-customers&edit_customer=' . $customer_id, 'gradient' => true]); ?>
                </div>
            </div>
            <div class="col-span-3">
                <?php
                // Define actions for the unified table
                $summary_url = plugins_url('pdf-summary.php', dirname(dirname(__FILE__)));
                $actions = [
                    [
                        'label' => 'Download',
                        'title' => 'Download PDF invoice',
                        'target' => '_blank',
                        'href' => $summary_url . '?waybill_no={waybill_no}',
                        'class' => 'text-xs font-medium text-green-600 hover:text-green-800 hover:underline',
                        'condition' => function ($row) {
                            $product_invoice_number = is_object($row) 
                                ? (isset($row->product_invoice_number) ? trim((string) $row->product_invoice_number) : '')
                                : (isset($row['product_invoice_number']) ? trim((string) $row['product_invoice_number']) : '');
                            return !empty($product_invoice_number);
                        }
                    ],
                    [
                        'label' => 'Delete',
                        'title' => 'Delete waybill',
                        'href' => '?page=08600-waybill-manage&delete_waybill={waybill_no}',
                        'class' => 'text-xs font-medium text-red-600 hover:text-red-800 hover:underline',
                        'onclick' => 'return confirm("Are you sure you want to delete this waybill?")'
                    ]
                ];

                // Use standardized column definitions from KIT_Commons for consistency
                // #region agent log
                $log_data = [
                    'sessionId' => 'debug-session',
                    'runId' => 'post-fix',
                    'hypothesisId' => 'FIXED',
                    'location' => 'customers-functions.php:' . __LINE__,
                    'message' => 'Using standardized KIT_Commons::getColumns for waybill_no',
                    'data' => [
                        'using_standardized_columns' => true,
                        'waybill_no_source' => 'KIT_Commons::getColumns'
                    ],
                    'timestamp' => time() * 1000
                ];
                file_put_contents('/Applications/MAMP/htdocs/08600/wp-content/plugins/courier-finance-plugin/.cursor/debug.log', json_encode($log_data) . "\n", FILE_APPEND);
                // #endregion
                
                $columns = KIT_Commons::getColumns([
                    'waybill_no',
                    'customer_city' => [
                        'label' => 'City',
                        'callback' => function ($value, $row, $rowIndex) {
                            return esc_html($value ?: '—');
                        }
                    ],
                    'truck_details' => [
                        'label' => 'Truck Details',
                        'callback' => function ($value, $row, $rowIndex) {
                            $row = is_object($row) ? (array) $row : $row;
                            $truck_number = $row['truck_number'] ?? '';
                            $delivery_reference = $row['delivery_reference'] ?? '';
                            $dispatch_date = $row['dispatch_date'] ?? '';

                            if ($truck_number === '' && $delivery_reference === '' && $dispatch_date === '') {
                                return '<span class="text-gray-400">No truck info</span>';
                            }

                            $html = '<div class="space-y-0.5">';

                            if ($truck_number !== '') {
                                $truck_display = mb_strlen($truck_number) > 10 ? mb_substr($truck_number, 0, 8) . '..' : $truck_number;
                                $html .= '<span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 font-medium" title="Truck: ' . esc_attr($truck_number) . '">';
                                $html .= '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"/><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1V8a1 1 0 00-1-1h-3z"/></svg>';
                                $html .= esc_html($truck_display) . '</span>';
                            }

                            if ($delivery_reference !== '') {
                                $ref_display = mb_strlen($delivery_reference) > 12 ? mb_substr($delivery_reference, 0, 10) . '..' : $delivery_reference;
                                $html .= ' <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded bg-green-50 text-green-700 font-medium" title="Ref: ' . esc_attr($delivery_reference) . '">';
                                $html .= '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>';
                                $html .= esc_html($ref_display) . '</span>';
                            }

                            if ($dispatch_date !== '') {
                                $formatted = function_exists('date_i18n') ? date_i18n('M j, Y', strtotime($dispatch_date)) : date('M j, Y', strtotime($dispatch_date));
                                $html .= '<div class="text-[10px] text-gray-500 truncate">' . esc_html($formatted) . '</div>';
                            }

                            $html .= '</div>';
                            return $html;
                        }
                    ],
                    'created_at' => [
                        'label' => 'Created',
                        'callback' => function ($value, $row, $rowIndex) {
                            if (empty($value)) {
                                return '—';
                            }
                            $timestamp = strtotime($value);
                            if ($timestamp) {
                                return esc_html(function_exists('date_i18n') ? date_i18n('M j, Y', $timestamp) : date('M j, Y', $timestamp));
                            }
                            return esc_html($value);
                        }
                    ]
                ]);

                // #region agent log
                $table_options = [
                    'title' => 'Waybills (' . count($waybills) . ')',
                    'primary_action' => [
                        'label' => 'View All Waybills',
                        'href' => '?page=08600-waybill-manage&customer_id=' . $customer_id,
                        'class' => 'px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-md hover:from-blue-700 hover:to-indigo-700 transition'
                    ],
                    'actions' => $actions,
                    'searchable' => true,
                    'sortable' => true,
                    'bulk_management' => true,
                    'bulk_actions_list' => ['export', 'delete'],
                    'empty_message' => 'No waybills found for this customer',
                    'preserve_order' => false
                ];
                $log_data = [
                    'sessionId' => 'debug-session',
                    'runId' => 'run1',
                    'hypothesisId' => 'B',
                    'location' => 'customers-functions.php:' . __LINE__,
                    'message' => 'Customer details table options before render',
                    'data' => [
                        'options' => $table_options,
                        'columns_count' => count($columns),
                        'has_custom_header_classes' => array_reduce($columns, function($carry, $col) {
                            return $carry || (is_array($col) && !empty($col['header_class']));
                        }, false)
                    ],
                    'timestamp' => time() * 1000
                ];
                file_put_contents('/Applications/MAMP/htdocs/08600/wp-content/plugins/courier-finance-plugin/.cursor/debug.log', json_encode($log_data) . "\n", FILE_APPEND);
                // #endregion
                
                // Render the unified table with standard styling to match main waybill table
                echo KIT_Unified_Table::infinite($waybills, $columns, $table_options);
                ?>
            </div>
        </div>

        <script>
            function testCustomerToast() {
                if (window.KITToast) {
                    // Test different toast types
                    window.KITToast.show('Customer data loaded successfully!', 'success', 'Customer Details');
                    setTimeout(() => {
                        window.KITToast.show('This is a test error message', 'error', 'Test Error');
                    }, 1000);
                    setTimeout(() => {
                        window.KITToast.show('Customer information updated', 'info', 'Information');
                    }, 2000);
                } else {
                    alert('Toast system not loaded. Please refresh the page.');
                }
            }

            // Initialize bulk management handlers for unified table
            (function() {
                document.addEventListener('DOMContentLoaded', function() {
                    <?php if (!empty($waybills)): ?>
                        const customerId = <?php echo intval($customer_id); ?>;
                        const pluginUrl = '<?php echo esc_js(dirname(dirname(plugin_dir_url(__FILE__)))); ?>';

                        // Find all unified tables on the page and attach export handlers
                        document.querySelectorAll('[id^="kit-infinite-table-"]').forEach(function(table) {
                            const tableId = table.id;
                            const containerId = tableId.replace('kit-infinite-table-', 'kit-infinite-wrap-');
                            const container = document.querySelector('[id^="kit-infinite-wrap-"]');
                            
                            if (!container) return;

                            // Find the export button within this table's bulk actions bar
                            const exportBtn = container.querySelector('[data-bulk-action="export"]');
                            if (exportBtn) {
                                exportBtn.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    e.stopPropagation();

                                    const checkboxes = table.querySelectorAll('.bulk-row-checkbox:checked');
                                    const waybillNos = Array.from(checkboxes).map(cb => cb.value).filter(v => v);

                                    if (waybillNos.length === 0) {
                                        alert('Please select at least one waybill to generate invoice.');
                                        return;
                                    }

                                    // Generate concatenated invoice PDF using pdf-customer-bulk.php
                                    const pdfUrl = pluginUrl + '/pdf-customer-bulk.php?selected_ids=' + encodeURIComponent(waybillNos.join(',')) + '&customer_id=' + customerId;

                                    // Open PDF in new window/tab
                                    window.open(pdfUrl, '_blank');
                                });
                            }

                            // Find the delete button within this table's bulk actions bar
                            const deleteBtn = container.querySelector('[data-bulk-action="delete"]');
                                if (deleteBtn) {
                                    deleteBtn.addEventListener('click', function(e) {
                                        e.preventDefault();
                                        e.stopPropagation();

                                        const checkboxes = table.querySelectorAll('.bulk-row-checkbox:checked');
                                        const waybillNos = Array.from(checkboxes).map(cb => cb.value).filter(v => v);

                                        if (waybillNos.length === 0) {
                                            alert('Please select at least one waybill.');
                                            return;
                                        }

                                        if (!confirm('Are you sure you want to delete ' + waybillNos.length + ' selected waybill(s)? This action cannot be undone.')) {
                                            return;
                                        }

                                        // Create and submit form
                                        const form = document.createElement('form');
                                        form.method = 'POST';
                                        form.action = window.location.href;

                                        const actionInput = document.createElement('input');
                                        actionInput.type = 'hidden';
                                        actionInput.name = 'bulk_action';
                                        actionInput.value = 'delete';
                                        form.appendChild(actionInput);

                                        const idsInput = document.createElement('input');
                                        idsInput.type = 'hidden';
                                        idsInput.name = 'bulk_ids';
                                        idsInput.value = waybillNos.join(',');
                                        form.appendChild(idsInput);

                                        document.body.appendChild(form);
                                        form.submit();
                                    });
                                }
                        });
                    <?php endif; ?>
                });
            })();
        </script>
    <?php
}

function delete_customer($id, $redirect = false)
{
    global $wpdb;
    $customer_id = intval($id);

    // First, get all waybill IDs for this customer
    $waybills_table = $wpdb->prefix . 'kit_waybills';
    $waybill_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM $waybills_table WHERE customer_id = %d",
        $customer_id
    ));

    if (!empty($waybill_ids)) {
        // Delete waybill items for all waybills of this customer
        $waybill_items_table = $wpdb->prefix . 'kit_waybill_items';
        foreach ($waybill_ids as $waybill_id) {
            $wpdb->delete($waybill_items_table, ['waybillno' => $waybill_id]);
        }

        // Delete all waybills for this customer
        $wpdb->delete($waybills_table, ['customer_id' => $customer_id]);
    }

    // Also remove warehouse tracking linked to this customer (FK lacks ON DELETE CASCADE)
    $waybills_table = $wpdb->prefix . 'kit_waybills';
    // Delete warehouse waybills for this customer
    $wpdb->delete($waybills_table, [
        'customer_id' => $customer_id,
        'status' => ['pending', 'assigned', 'shipped', 'delivered']
    ]);

    // Now delete the customer
    $customers_table = $wpdb->prefix . 'kit_customers';
    $deleted = $wpdb->delete($customers_table, ['cust_id' => $customer_id]);

    if ($deleted && $redirect) {
        $deleted_count = count($waybill_ids);
        $message = "Customer deleted successfully!";
        if ($deleted_count > 0) {
            $message .= " Also deleted $deleted_count waybill(s) and their associated items.";
        }
        $target_url = admin_url('admin.php?page=08600-customers&deleted=1&waybills_deleted=' . $deleted_count . '&tab=manage-customers');
        if (!headers_sent()) {
            wp_safe_redirect($target_url);
            exit;
        } else {
            echo '<script>window.location.replace(' . json_encode($target_url) . ');</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url($target_url) . '"></noscript>';
            exit;
        }
    }

    // Return true/false for bulk operations instead of echoing HTML
    return (bool) $deleted;
}

function tholaMaCustomer()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_customers';
    $waybills_table = $wpdb->prefix . 'kit_waybills';
    return $wpdb->get_results("
    SELECT 
        c.id, 
        c.cust_id, 
        c.name as customer_name, 
        c.surname as customer_surname, 
        c.email_address, 
        c.cell, 
        c.address, 
        c.country_id, 
        c.city_id,
        country.country_name,
        city.city_name,
        c.company_name,
        COUNT(w.id) as total_waybills
    FROM $table_name c
    LEFT JOIN {$wpdb->prefix}kit_operating_countries country ON c.country_id = country.id
    LEFT JOIN {$wpdb->prefix}kit_operating_cities city ON c.city_id = city.id
    LEFT JOIN $waybills_table w ON w.customer_id = c.cust_id
    GROUP BY c.id, c.cust_id, c.name, c.surname, c.email_address, c.cell, c.address, c.country_id, c.city_id, country.country_name, city.city_name, c.company_name
");
}

function gamaCustomer()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_customers';
    $results = $wpdb->get_results("SELECT * FROM $table_name");
    $customers = array_map(fn($row) => $row->name, $results);
    return $customers;
}

function get_customer_details($customer_id)
{
    global $wpdb;

    // Sanitize the input
    $customer_id = absint($customer_id);
    if (!$customer_id) {
        return false;
    }

    $table_name = $wpdb->prefix . 'kit_customers';

    // Prepare and execute a parameterized query
    $query = $wpdb->prepare(
        "SELECT 
        c.id, 
        c.cust_id, 
        c.name as customer_name, 
        c.surname as customer_surname, 
        c.email_address, 
        c.cell, 
        c.address, 
        c.country_id, 
        c.city_id,
        country.country_name,
        city.city_name,
        c.company_name
        FROM $table_name c
        LEFT JOIN {$wpdb->prefix}kit_operating_countries country ON c.country_id = country.id
        LEFT JOIN {$wpdb->prefix}kit_operating_cities city ON c.city_id = city.id
        WHERE cust_id = %d",
        $customer_id
    );

    $customer = $wpdb->get_row($query, ARRAY_A);

    // Convert null values to empty strings to prevent deprecation warnings
    if ($customer) {
        $customer = array_map(function ($value) {
            return $value === null ? '' : $value;
        }, $customer);
    }
    // Return false if no customer found
    if (empty($customer)) {
        return false;
    }
    return $customer;
}

function edit_customer_form($customer_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_customers';
    $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE cust_id = %d", $customer_id), ARRAY_A);

    // Enqueue CSS for the edit customer page
    wp_enqueue_style('autsincss', plugin_dir_url(__FILE__) . '../assets/css/austin.css', array(), '1.0');
    wp_enqueue_style('kit-tailwindcss', plugin_dir_url(__FILE__) . '../assets/css/frontend.css', array(), '1.0');

    // Add CSS class wrapper to admin body for scoping
    add_filter('admin_body_class', function ($classes) {
        return $classes . ' courier-finance-plugin';
    });

    // Enqueue JavaScript for country/city selection
    wp_enqueue_script('kitscript', plugin_dir_url(__FILE__) . '../js/kitscript.js', ['jquery'], null, true);

    // Convert null values to empty strings to prevent deprecation warnings
    if ($customer) {
        $customer = array_map(function ($value) {
            return $value === null ? '' : $value;
        }, $customer);
    }

    if (!$customer) {
        wp_die('Customer not found');
    }

    ?>
        <div class="wrap flex flex-col h-screen">
            <?php
            echo KIT_Commons::showingHeader([
                'title' => 'Edit Customer',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />',
                'desc'  => KIT_Commons::kitButton([
                    'color' => 'green',
                    'href'  => admin_url('admin.php?page=08600-customers&view_customer=' . $customer_id)
                ], 'Back'),
            ]);
            ?>
            <div class="flex-1 flex flex-col max-w-7xl mx-auto w-full bg-white rounded-2xl shadow-xl border border-gray-100 mt-7 mb-7 overflow-hidden">
                <div class="flex-shrink-0 p-6 border-b border-gray-200">
                    <h1 class="text-2xl font-bold text-gray-900">Edit Customer</h1>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <div class="p-6">
                        <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" class="space-y-6">
                    <?php wp_nonce_field('update_customer_nonce', 'cust_update_nonce'); ?>
                    <input type="hidden" name="action" value="update_customer" />
                    <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>" />
                    <?php
                    theForm($customer); ?>
                            <div class="flex flex-col sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200">
                                <?php 
                                $back_icon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>';
                                echo KIT_Commons::renderButton('Back', 'secondary', 'lg', [
                                    'href' => admin_url('admin.php?page=08600-customers&view_customer=' . $customer_id),
                                    'icon' => $back_icon
                                ]); 
                                $save_icon = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>';
                                echo KIT_Commons::renderButton('Update Customer', 'primary', 'lg', [
                                    'type' => 'submit', 
                                    'name' => 'customer_submit', 
                                    'gradient' => true,
                                    'icon' => $save_icon
                                ]); 
                                ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script>
        (function() {
            function alignInputIcons() {
                document.querySelectorAll('.input-with-icon-container').forEach(function(container) {
                    const input = container.querySelector('input, select');
                    const icon = container.querySelector('.input-icon');
                    
                    if (!input || !icon) return;
                    
                    const label = document.querySelector('label[for="' + input.id + '"]');
                    if (!label) return;
                    
                    // Get actual positions
                    const containerRect = container.getBoundingClientRect();
                    const labelRect = label.getBoundingClientRect();
                    const inputRect = input.getBoundingClientRect();
                    
                    // Calculate where input starts relative to container
                    const inputTop = inputRect.top - containerRect.top;
                    const inputHeight = inputRect.height;
                    
                    // Position icon to match input top and height, center vertically
                    icon.style.top = inputTop + 'px';
                    icon.style.height = inputHeight + 'px';
                });
            }
            
            // Run on load and after any dynamic changes
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', alignInputIcons);
            } else {
                alignInputIcons();
            }
            
            // Re-align on window resize
            window.addEventListener('resize', alignInputIcons);
        })();
        </script>
        <?php
    }

    function customer_waybills($customer_id)
    {
        global $wpdb;

        // Sanitize and validate the customer ID
        $customer_id = intval($customer_id);  // Use intval to ensure it's a valid integer

        // Query to get all waybills for the given customer ID
        $table_name = $wpdb->prefix . 'kit_waybills';
        // TABLE NAMES
        $waybills_table   = $wpdb->prefix . 'kit_waybills';
        $customers_table  = $wpdb->prefix . 'kit_customers';
        $deliveries_table = $wpdb->prefix . 'kit_deliveries';
        $directions_table = $wpdb->prefix . 'kit_shipping_directions';
        $countries_table  = $wpdb->prefix . 'kit_operating_countries';
        $items_table      = $wpdb->prefix . 'kit_waybill_items';

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
        b.item_length,
        b.item_width,
        b.item_height,
        b.total_mass_kg,
        b.total_volume,
        b.mass_charge,
        b.volume_charge,
        b.charge_basis,
        b.warehouse,
        b.miscellaneous,
        b.include_sad500,
        b.include_sadc,
        c.name AS customer_name,
        c.surname AS customer_surname,
        c.cell AS customer_cell,
        d.delivery_reference,
        d.direction_id,
        d.dispatch_date,
        d.truck_number,
        d.status AS delivery_status,
        dir.description AS route_description,
        origin.country_name AS origin_country,
        dest.country_name AS destination_country
        FROM $waybills_table b
        LEFT JOIN $customers_table c ON b.customer_id = c.cust_id
        LEFT JOIN $deliveries_table d ON b.direction_id = d.id
        LEFT JOIN $directions_table dir ON b.direction_id = dir.id
        LEFT JOIN $countries_table origin ON dir.origin_country_id = origin.id
        LEFT JOIN $countries_table dest ON dir.destination_country_id = dest.id
        WHERE b.customer_id = %d", $customer_id);


        $waybill = $wpdb->get_results($waybill_sql);

        return $waybill;
    }



    function view_customer_waybills()
    {
        if (isset($_GET['cust_id'])) {

            $customer_id = intval($_GET['cust_id']);
            $customer = get_customer_details($customer_id); // You'll need to implement this
            $waybills = customer_waybills($customer_id);

            echo KIT_Commons::showingHeader([
                'title' => 'Customers Waybills',
                'desc' => "342234",
            ]);

            $customers   = KIT_Customers::tholaMaCustomer();
            $form_action = admin_url('admin-post.php?action=add_waybill_action');

            $modal_path = realpath(plugin_dir_path(__FILE__) . '../components/modal.php');

            if (file_exists($modal_path)) {
                require_once $modal_path;
            } else {
                error_log("Modal.php not found at: " . $modal_path);
                // Optional: Show a safe error or fallback content
            }

            if (isset($_GET['selected_ids'])) {

                $selected_ids_string = $_GET['selected_ids']; // "4000,4003,4004"
                $selected_ids_array = explode(',', $selected_ids_string);

                if (!empty($selected_ids_array)) {
                    // Generate PDF and return it as download
                    include plugin_dir_path(__FILE__) . 'pdf-bulkinvoicing.php';
                    exit;
                }
            }
        ?>
            <div class="<?= KIT_Commons::container() ?> flex gap-4 min-h-screen bg-gray-100">
                <!-- Left Sidebar - Customer Details -->
                <div class="w-1/3">
                    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                        <h2 class="text-2xl font-bold text-gray-800 mb-4">Custome23r Details</h2>

                        <?php if ($customer) :

                            /* We will add a edit button here to edit the customer details.
                        if $edit_customer is set, we will show the edit form.
                        if $edit_customer is not set, we will show the customer details. */
                            if (!isset($_GET['edit_customer'])) {

                                /* We must display theForm but not as a form, do not use theForm function, but cusrtomer details */
                                echo '<div class="divide-y divide-gray-200">';
                                // Only add Company if it's not empty
                                $fields = array_filter([
                                    'Company'  => $customer['company_name'] ?? '',
                                    'Name'     => $customer['customer_name'] ?? '',
                                    'Surname'  => $customer['customer_surname'] ?? '',
                                    'Cell'     => $customer['cell'] ?? '',
                                    'Address'  => $customer['address'] ?? '',
                                    'Email'  => $customer['email_address'] ?? '',
                                    'Country'  => $customer['country_name'] ?? '',
                                    'City'     => $customer['city_name'] ?? '',
                                ], function ($value) {
                                    return $value !== null && $value !== '';
                                });

                                foreach ($fields as $key => $value) {
                                    echo '<div class="flex items-center py-2">';
                                    echo '<span class="w-[70px] font-semibold text-gray-700">' . esc_html($key) . ':</span>';
                                    echo '<span class="flex-1 text-gray-900">' . esc_html($value) . '</span>';
                                    echo '</div>';
                                }
                                echo '</div>';
                                //We will add a delete button here to delete the customer.
                                echo KIT_Commons::kitButton([
                                    'color' => 'red',
                                    'href' => admin_url('admin.php?page=customers-dashboard&delete_customer=' . $customer['cust_id'])
                                ], 'Delete Customer');

                                //We will add a edit button here to edit the customer details.
                                echo KIT_Commons::kitButton([
                                    'color' => 'blue',
                                    'href' => admin_url('admin.php?page=08600-customers&edit_customer=' . $customer['cust_id'])
                                ], 'Edit Customer');
                            } else {
                                /* We will display the edit form here */
                                $formHtml = '';
                                $formHtml .= '<form method="POST" action="' . esc_url(admin_url('admin-post.php')) . '" class="space-y-4">';
                                ob_start();
                                wp_nonce_field('update_customer_nonce', 'cust_update_nonce');
                                $formHtml .= ob_get_clean();
                                $formHtml .= '<input type="hidden" name="action" value="update_customer" />';
                                ob_start();
                                theForm($customer);
                                $formHtml .= ob_get_clean();
                                $formHtml .= '<div class="flex justify-end">';
                                $formHtml .= KIT_Commons::kitButton([
                                    'color' => 'blue',
                                    'type' => 'submit',
                                    'name' => 'customer_update_btn',
                                ], 'Update Customer');
                                $formHtml .= '</div>';
                                $formHtml .= '</form>';
                                echo $formHtml;
                            }

                        ?>

                        <?php else : ?>
                            <p class="text-red-500">Customer not found</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Content - Waybills Table -->
                <div class="w-2/3">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-800">Waybill History</h2>
                            <?php
                            echo KIT_Modal::render(
                                'create-waybill-modal',
                                'Create New Waybill',
                                kit_render_waybill_multiform([
                                    'form_action'          => $form_action,
                                    'waybill_id'           => '',
                                    'is_edit_mode'         => '0',
                                    'waybill'              => '{}',
                                    'customer_id'          => $customer_id,
                                    'is_existing_customer' => '',
                                    'customer'             => $customers
                                ]),
                                '6xl'
                            );
                            ?>

                        </div>

                        <?php if (!empty($waybills)) :

                            $options = [
                                'itemsPerPage' => 20,
                                'currentPage' => $_GET['paged'] ?? 1,
                                'tableClass' => 'min-w-full text-left text-sm text-gray-700',
                                'emptyMessage' => 'No customers records found',
                                'id' => 'customerTable',
                                'role' => 'waybills'
                            ];

                            $columns = [
                                'waybill_no'     => ['label' => 'Waybill #', 'align' => 'text-left'],
                                'customer_name'  => ['label' => 'Name', 'align' => 'text-left'],
                                'approval'       => ['label' => 'Approval', 'align' => 'text-left'],
                                'total'          => ['label' => 'Total', 'align' => 'text-right'],
                            ];
                            $cell_callback = function ($key, $row) {
                                if ($key === 'waybill_no') {
                                    // Return a link to the waybill view page
                                    return '<a target="_blank" href="?page=08600-Waybill-view&waybill_id=' . $row->waybill_id . '&waybill_atts=view_waybill" class="text-blue-600 hover:underline">#' . $row->waybill_no . '</a>';
                                }
                                if ($key === 'total') {
                                    //total is the sum of the product_invoice_amount and the miscellaneous
                                    if (KIT_User_Roles::can_see_prices()) {
                                        return KIT_Commons::currency() . ' ' . ((int)$row->product_invoice_amount + ((int)$row->miscellaneous ?? 0));
                                    } else {
                                        return '***';
                                    }
                                }
                                if ($key === 'approval') {
                                    return $row->approval;
                                }
                                return htmlspecialchars(($row->$key ?? '') ?: '');
                            };

                            echo KIT_Unified_Table::infinite($waybills, $columns, [
                                'title' => 'Customer Waybills',
                            ]);
                        ?>
                        <?php else : ?>
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No waybills found</h3>
                                <p class="mt-1 text-sm text-gray-500">This customer doesn't have any waybills yet.</p>
                                <div class="mt-6">
                                    <?php
                                    echo KIT_Commons::kitButton([
                                        'color' => 'blue',
                                        'modal' => 'create-waybill-modal',
                                        'icon' => 'plus',
                                    ], 'Create New Waybill'); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

    <?php
        } else {
            return '<div class="p-6 text-red-500">No customer selected.</div>';
        }
    }

    // Fallback for sanitize_text_field if not in WordPress
    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($str)
        {
            return is_string($str) ? trim(strip_tags($str)) : $str;
        }
    }

    // Fallback for is_wp_error if not in WordPress
    if (!function_exists('is_wp_error')) {
        function is_wp_error($thing)
        {
            return false;
        }
    }
