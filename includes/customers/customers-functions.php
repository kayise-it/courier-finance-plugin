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
        $cust_data = [
            'cust_id'  => rand(1000, 9999),
            'name'     => sanitize_text_field($cust['name'] ?? $cust['customer_name'] ?? ''),
            'surname'  => sanitize_text_field($cust['surname'] ?? $cust['customer_surname'] ?? ''),
            'cell'     => sanitize_text_field($cust['cell'] ?? ''),
            'email_address'  => sanitize_text_field($cust['email_address'] ?? ''),
            'address'  => sanitize_text_field($cust['address'] ?? ''),
            'country_id'  => intval($cust['country_id'] ?? 0),
            'city_id'  => $city_id,
            'company_name'  => sanitize_text_field($cust['company_name'] ?? ''),
            'vat_number'  => sanitize_text_field($cust['vat_number'] ?? ''),
        ];

        // Insert into DB
        // Specify data types to allow NULL for city_id
        $inserted = $wpdb->insert($table_name, $cust_data, [
            '%d', '%s', '%s', '%s', '%s', '%s', '%d', ($city_id === null ? null : '%d'), '%s', '%s'
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
            $update_data['email_address'] = sanitize_text_field($data['email_address']);
        }
        if (isset($data['country_id'])) {
            $update_data['country_id'] = sanitize_text_field($data['country_id']);
        }
        if (isset($data['city_id'])) {
            $update_data['city_id'] = sanitize_text_field($data['city_id']);
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

        $cust_id = intval($_POST['cust_id']);

        $data = [
            'company_name' => sanitize_text_field($_POST['company_name']),
            'name'    => sanitize_text_field($_POST['name']),
            'surname' => sanitize_text_field($_POST['surname']),
            'cell'    => sanitize_text_field($_POST['cell']),
            'address' => sanitize_textarea_field($_POST['address']),
            'email_address' => sanitize_textarea_field($_POST['email_address']),
            'country_id' => sanitize_textarea_field($_POST['country_id']),
            'city_id' => sanitize_textarea_field($_POST['city_id']),
        ];

        // 🔥 Call your method here
        $updated = KIT_Customers::update_customer($cust_id, $data);

        //get the customer id
        $id = KIT_Customers::idCustomer($cust_id);

        if ($updated) {
            //Send a message to the user that the customer was updated successfully
            $msg = '<div class="bg-green-100 text-green-800 p-4 rounded mb-4">Customer updated successfully. 🗑️</div>';
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
        if (empty($_POST['name']) || empty($_POST['surname']) || empty($_POST['cell']) || empty($_POST['company_name']) || empty($_POST['email_address']) || empty($_POST['address'])) {
            error_log('Customer AJAX missing required fields');
            wp_send_json_error(['message' => 'Please fill in all required fields (Company Name, First Name, Last Name, Cell, Email, Address)']);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';

        // Generate a unique customer ID
        do {
            $cust_id = rand(1000, 9999);
            $exists = $wpdb->get_var($wpdb->prepare("SELECT cust_id FROM $table_name WHERE cust_id = %d", $cust_id));
        } while ($exists);

        // Sanitize inputs
        $cust_data = [
            'cust_id'  => $cust_id,
            'name'     => sanitize_text_field($_POST['name'] ?? ''),
            'surname'  => sanitize_text_field($_POST['surname'] ?? ''),
            'cell'     => sanitize_text_field($_POST['cell'] ?? ''),
            'address'  => sanitize_text_field($_POST['address'] ?? ''),
            'email_address' => sanitize_text_field($_POST['email_address'] ?? ''),
            'company_name' => sanitize_text_field($_POST['company_name'] ?? ''),
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

        echo '<pre>';
        print_r($customer_id);
        echo '</pre>';
        exit();
        // First, delete all waybills for this customer
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $wpdb->delete($waybills_table, ['customer_id' => $customer_id]);

        // Now delete the customer
        $customers_table = $wpdb->prefix . 'kit_customers';
        $deleted = $wpdb->delete($customers_table, ['cust_id' => $customer_id]);

        if ($deleted) {
            echo '<div class="bg-red-100 text-red-800 p-4 rounded mb-4">Customer deleted successfully. 🗑️</div>';
            wp_redirect(admin_url('admin.php?page=customers-dashboard'));
            exit;
        } else {
            echo '<div class="bg-yellow-100 text-yellow-800 p-4 rounded mb-4">Customer not found or already deleted.</div>';
        }
    }

    public static function tholaMaCustomer()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';
        return $wpdb->get_results("SELECT * FROM $table_name");
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
        $output .= KIT_Commons::renderButton('Upload', 'primary', 'md', ['type' => 'submit', 'gradient' => true]);
        $output .= '</form>';
        return $output;
    }

    /**
     * Example: Integrate the upload form into the customer dashboard page.
     * Call this in your customer dashboard rendering logic.
     */
    public static function customer_dashboard_page()
    {
        echo '<div class="wrap"><h1>Customer Dashboard</h1>';
        // ... existing dashboard content ...
        echo self::render_upload_customers_form();
        echo '</div>';
    }
}
// Initialize
KIT_Customers::init();



function customer_dashboard()
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

    // Handle customer update form submission
    if (isset($_POST['action']) && $_POST['action'] === 'update_customer') {
        if (wp_verify_nonce($_POST['cust_update_nonce'], 'update_customer_nonce')) {
            $customer_id = intval($_POST['customer_id']);
            
            // Update customer data
            $update_data = array(
                'name' => sanitize_text_field($_POST['name']),
                'surname' => sanitize_text_field($_POST['surname']),
                'cell' => sanitize_text_field($_POST['cell']),
                'email_address' => sanitize_email($_POST['email_address']),
                'address' => sanitize_textarea_field($_POST['address']),
                'company_name' => sanitize_text_field($_POST['company_name']),
                'country_id' => intval($_POST['country_id']),
                'city_id' => intval($_POST['city_id']),
                'vat_number' => sanitize_text_field($_POST['vat_number'])
            );
            
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

    // UI Shell
    echo '<div class="wrap" style="max-width: 100%; margin-bottom: 80px;">';
    echo KIT_Commons::showingHeader([
        'title' => 'Customer Dashboard',
        'desc'  => KIT_Commons::renderButton('Add Customer', 'primary', 'md', ['href' => admin_url('admin.php?page=08600-add-customer'), 'gradient' => true]),
    ]);
    echo '<hr class="wp-header-end">';

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
        echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">Customer added successfully! 🎉</div>';
    }

    // Tabs
    echo '<div class="customer-tabs mb-6">';
    echo '  <div class="flex bg-gray-100 p-1 rounded-md w-fit">';
    echo '    ' . KIT_Commons::renderButton('Overview', 'ghost', 'sm', ['id' => 'overview-tab', 'classes' => 'tab-btn']) . '';
    echo '    ' . KIT_Commons::renderButton('Manage Customers', 'ghost', 'sm', ['id' => 'manage-customers-tab', 'classes' => 'tab-btn active']) . '';
    echo '  </div>';
    echo '</div>';

    // Overview
    require_once plugin_dir_path(__FILE__) . '../components/quickStats.php';
    require_once plugin_dir_path(__FILE__) . '../components/dashboardQuickies.php';

    echo '<div id="overview-content" class="tab-content" style="display:none">';
    // Quick stats row

    echo '</div>';

    // Add Customer (inline form)
    echo '<div id="add-customer-content" class="tab-content" style="display:none">';
    echo '<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">';
    echo '<h3 class="text-base font-semibold text-gray-900 mb-4">Add New Customer</h3>';
    echo '<form method="post" class="space-y-6" id="inlineCustomerForm" action="">';
    echo '  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">';
    echo '    <div><label class="block text-sm text-gray-700 mb-2">Company Name *</label><input type="text" name="company_name" id="company_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md"></div>';
    echo '    <div><label class="block text-sm text-gray-700 mb-2">First Name *</label><input type="text" name="name" id="name" required class="w-full px-3 py-2 border border-gray-300 rounded-md"></div>';
    echo '    <div><label class="block text-sm text-gray-700 mb-2">Last Name *</label><input type="text" name="surname" id="surname" required class="w-full px-3 py-2 border border-gray-300 rounded-md"></div>';
    echo '    <div><label class="block text-sm text-gray-700 mb-2">Cell Phone *</label><input type="tel" name="cell" id="cell" required class="w-full px-3 py-2 border border-gray-300 rounded-md"></div>';
    echo '    <div><label class="block text-sm text-gray-700 mb-2">Email Address *</label><input type="email" name="email_address" id="email_address" required class="w-full px-3 py-2 border border-gray-300 rounded-md"></div>';
    echo '    <div><label class="block text-sm text-gray-700 mb-2">Address *</label><textarea name="address" id="address" rows="3" required class="w-full px-3 py-2 border border-gray-300 rounded-md"></textarea></div>';
    echo '    <div><label class="block text-sm text-gray-700 mb-2">Country</label>';
    echo '      <select name="country_id" id="country_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">';
    echo '        <option value="">Select Country</option>';
    echo '        <option value="1">South Africa</option>';
    echo '        <option value="2">Zimbabwe</option>';
    echo '      </select>';
    echo '    </div>';
    echo '    <div><label class="block text-sm text-gray-700 mb-2">City</label>';
    echo '      <select name="city_id" id="city_id" class="w-full px-3 py-2 border border-gray-300 rounded-md">';
    echo '        <option value="">Select City</option>';
    echo '        <option value="1">Johannesburg</option>';
    echo '        <option value="2">Cape Town</option>';
    echo '        <option value="3">Durban</option>';
    echo '      </select>';
    echo '    </div>';
    echo '    <div><label class="block text-sm text-gray-700 mb-2">VAT Number</label><input type="text" name="vat_number" id="vat_number" class="w-full px-3 py-2 border border-gray-300 rounded-md" placeholder="VAT registration number"></div>';
    echo '  </div>';
    echo '  <div class="flex justify-end gap-2 pt-4">';
    echo '    ' . KIT_Commons::renderButton('Cancel', 'secondary', 'md', ['type' => 'button', 'onclick' => 'switchCustomerTab(\'manage-customers\')']) . '';
    echo '    ' . KIT_Commons::renderButton('Save Customer', 'primary', 'md', ['type' => 'submit', 'id' => 'saveCustomerBtn', 'gradient' => true]) . '';
    echo '  </div>';
    echo '</form>';
    echo '</div>';
    echo '</div>';

    // Manage Customers (table)
    echo '<div id="manage-customers-content" class="tab-content" style="display:block">';
    echo '<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">';
    echo '<h3 class="text-base font-semibold text-gray-900 mb-4">Customer Management</h3>';

    $columns = [
        'company_name'  => ['label' => 'Company', 'align' => 'text-left'],
        'customer_name'          => ['label' => 'Name',    'align' => 'text-left'],
        'country_name'  => ['label' => 'Country', 'align' => 'text-left'],
        'actions'       => ['label' => 'Actions', 'align' => 'text-center'],
    ];

    $cell_callback = function ($key, $row) {
        if ($key === 'name') {
            $html  = '<div class="flex flex-col">';
            $html .= '<span class="font-medium text-gray-900">' . esc_html(($row->name ?? '') . ' ' . ($row->surname ?? '')) . '</span>';
            $html .= '<span class="text-xs text-gray-500">' . esc_html($row->email_address ?? '') . '</span>';
            return $html . '</div>';
        }
        if ($key === 'actions') {
            $html  = '<div class="flex justify-center gap-2">';
            $html .= '<a class="inline-flex px-3 py-1 text-xs rounded-md bg-blue-100 text-blue-700" href="?page=08600-customers&view_customer=' . intval($row->cust_id) . '">View</a>';
            $html .= '<a class="inline-flex px-3 py-1 text-xs rounded-md bg-red-100 text-red-700" href="?page=08600-customers&delete_customer=' . intval($row->cust_id) . '" onclick="return confirm(\'Delete this customer and all their waybills?\');">Delete</a>';
            return $html . '</div>';
        }
        return htmlspecialchars(($row->$key ?? '') ?: '');
    };

    $options = [
        'itemsPerPage' => 10,
        'currentPage'  => isset($_GET['paged']) ? $_GET['paged'] : 1,
        'tableClass'   => 'w-full text-left text-xs text-gray-700',
        'emptyMessage' => 'No customers found. <a href="#" onclick="showAddCustomerForm(); return false;">Add your first customer</a>',
        'id'           => 'customerTable',
        'role'         => 'customers',
        'filterOverride' => 'country',
    ];

    echo '<div class="overflow-x-auto">';
    echo KIT_Commons::render_versatile_table($customers, $columns, $cell_callback, $options);
    echo '</div>';

    // Client-side country filtering for versatile table
    echo "<script>\n";
    echo "document.addEventListener(\"DOMContentLoaded\", function(){\n";
    echo "  var select = document.getElementById(\"customerCountryFilter\");\n";
    echo "  function countryColIndex(){\n";
    echo "    var ths = document.querySelectorAll(\"#customerTable thead th\");\n";
    echo "    for (var i=0;i<ths.length;i++){ if ((ths[i].textContent||\"\").trim().toLowerCase()===\"country\") return i; }\n";
    echo "    return -1;\n";
    echo "  }\n";
    echo "  var col = countryColIndex();\n";
    echo "  function applyFilter(){\n";
    echo "    var selectedText = select && select.value!==\"\" ? select.options[select.selectedIndex].text.toLowerCase() : \"\";\n";
    echo "    var rows = document.querySelectorAll(\"#customerTable tbody tr\");\n";
    echo "    Array.prototype.forEach.call(rows, function(row){\n";
    echo "      if (col < 0 || !select || select.value===\"\"){ row.style.display=\"\"; return; }\n";
    echo "      var cell = row.querySelector(\"td:nth-child(\"+(col+1)+\")\");\n";
    echo "      var txt = (cell && cell.textContent ? cell.textContent : \"\").trim().toLowerCase();\n";
    echo "      row.style.display = (txt === selectedText) ? \"\" : \"none\";\n";
    echo "    });\n";
    echo "  }\n";
    echo "  if (select){ select.addEventListener(\"change\", function(){ setTimeout(applyFilter, 0); }); }\n";
    echo "  var search = document.getElementById(\"customerTable-search\");\n";
    echo "  if (search){ search.addEventListener(\"input\", function(){ setTimeout(applyFilter, 50); }); }\n";
    echo "  setTimeout(applyFilter, 200);\n";
    echo "});\n";
    echo "</script>";
    echo '</div>';
    echo '</div>';

    // Tab behavior script to preserve active tab via URL parameter
    echo '<script>';
    echo 'document.addEventListener("DOMContentLoaded", function(){';
    echo '  function activateTab(name){';
    echo '    var tabs = ["overview","manage-customers"];';
    echo '    tabs.forEach(function(t){';
    echo '      var btn = document.getElementById(t+"-tab"); var panel = document.getElementById(t+"-content");';
    echo '      if(!btn||!panel) return;';
    echo '      var activeClasses = ["bg-white","text-gray-900","border-gray-200","shadow"];';
    echo '      var inactiveClasses = ["text-gray-600","border-transparent"];';
    echo '      if(t===name){';
    echo '        btn.classList.add("active"); panel.style.display="block";';
    echo '        activeClasses.forEach(function(c){ btn.classList.add(c); });';
    echo '        inactiveClasses.forEach(function(c){ btn.classList.remove(c); });';
    echo '      } else {';
    echo '        btn.classList.remove("active"); panel.style.display="none";';
    echo '        activeClasses.forEach(function(c){ btn.classList.remove(c); });';
    echo '        inactiveClasses.forEach(function(c){ btn.classList.add(c); });';
    echo '      }';
    echo '    });';
    echo '  }';
    echo '  function getInitialTab(){';
    echo '    var params = new URLSearchParams(window.location.search);';
    echo '    var tab = params.get("tab") || (window.location.hash ? window.location.hash.replace("#","") : "");';
    echo '    var allowed = {"overview":1,"manage-customers":1};';
    echo '    return allowed[tab]?tab:"manage-customers";';
    echo '  }';
    echo '  ["overview","manage-customers"].forEach(function(t){';
    echo '    var btn = document.getElementById(t+"-tab"); if(btn){ btn.addEventListener("click", function(){ activateTab(t); history.replaceState(null, "", window.location.pathname+"?page=08600-customers&tab="+t); }); }';
    echo '  });';
    echo '  activateTab(getInitialTab());';
    echo '});';
    echo '</script>';

    // Close wrap
    echo '</div>';
    return;

    // Get customer statistics
    $total_customers = count($customers);
    $active_customers = array_filter($customers, function ($c) {
        return !empty($c->company_name);
    });
    $active_customers_count = count($active_customers);
    $inactive_customers = $total_customers - $active_customers_count;
?>
    <div class="wrap" style="max-width: 100%; margin-bottom: 80px;">
        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    Customer deleted successfully!
                    <?php if (isset($_GET['waybills_deleted']) && $_GET['waybills_deleted'] > 0): ?>
                        Also deleted <?php echo intval($_GET['waybills_deleted']); ?> waybill(s) and their associated items.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
        <?= KIT_Commons::showingHeader([
            'title' => 'Customer Dashboard',
            'desc' => 'Manage customers and their waybills',
        ]);
        ?>
        <hr class="wp-header-end">

        <!-- Tab Navigation -->
        <div class="customer-tabs" style="margin-bottom: 30px;">
            <div class="flex bg-gray-100 p-1 rounded-lg w-fit">
                <?php echo KIT_Commons::renderButton('Overview', 'ghost', 'sm', ['id' => 'overview-tab', 'classes' => 'tab-btn']); ?>
                <?php echo KIT_Commons::renderButton('Add Customer', 'ghost', 'sm', ['id' => 'add-customer-tab', 'classes' => 'tab-btn']); ?>
                <?php echo KIT_Commons::renderButton('Manage Customers', 'ghost', 'sm', ['id' => 'manage-customers-tab', 'classes' => 'tab-btn active']); ?>
            </div>
        </div>

        <!-- Overview Tab Content -->
        <div id="overview-content" class="tab-content">
        </div>

        <!-- Add Customer Tab Content -->
        <div id="add-customer-content" class="tab-content">
            <div class="bg-white p-6 rounded-xl shadow-md border border-gray-200">
                <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 20px 0;">Add New Customer</h3>

                <form method="post" class="space-y-6" id="inlineCustomerForm" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Company Name -->
                        <div>
                            <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                            <input type="text" name="company_name" id="company_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Enter company name">
                        </div>

                        <!-- Customer Name -->
                        <div>
                            <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                            <input type="text" name="customer_name" id="customer_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Enter first name">
                        </div>

                        <!-- Customer Surname -->
                        <div>
                            <label for="customer_surname" class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                            <input type="text" name="customer_surname" id="customer_surname" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Enter last name">
                        </div>

                        <!-- Cell Phone -->
                        <div>
                            <label for="cell" class="block text-sm font-medium text-gray-700 mb-2">Cell Phone *</label>
                            <input type="tel" name="cell" id="cell" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Enter cell phone number">
                        </div>

                        <!-- Email Address -->
                        <div>
                            <label for="email_address" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                            <input type="email" name="email_address" id="email_address" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Enter email address">
                        </div>

                        <!-- Address -->
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                            <textarea name="address" id="address" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="Enter address"></textarea>
                        </div>

                        <!-- Country -->
                        <div>
                            <label for="country_id" class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                            <select name="country_id" id="country_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">Select Country</option>
                                <?php
                                global $wpdb;
                                $countries = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}kit_operating_countries ORDER BY country_name");
                                foreach ($countries as $country) {
                                    echo '<option value="' . esc_attr($country->id) . '">' . esc_html($country->country_name) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <!-- City -->
                        <div>
                            <label for="city_id" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                            <select name="city_id" id="city_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="">Select City</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                        <?php echo KIT_Commons::renderButton('Cancel', 'secondary', 'md', ['type' => 'button', 'onclick' => 'switchCustomerTab(\'manage-customers\')']); ?>
                        <?php echo KIT_Commons::renderButton('Save Customer', 'primary', 'md', ['type' => 'submit', 'id' => 'saveCustomerBtn', 'gradient' => true]); ?>
                    </div>
                </form>

                <!-- Success/Error Messages -->
                <div id="customerFormMessages" class="mt-4"></div>
            </div>
        </div>

        <!-- Manage Customers Tab Content -->
        <div id="manage-customers-content" class="tab-content active">
            <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; margin-bottom: 60px;">
                <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 20px 0;">Customer Management</h3>

                <?php
                $options = [
                    'itemsPerPage' => 10,
                    'currentPage' => $_GET['paged'] ?? 1,
                    'tableClass' => 'w-full text-left text-xs text-gray-700',
                    'emptyMessage' => 'No customers found. <a href="#" onclick="showAddCustomerForm(); return false;">Add your first customer</a>',
                    'id' => 'customerTable',
                    'role' => 'customers',
                    'bulk_actions' => [
                        'delete' => 'Delete Selected',
                        'export' => 'Export Selected'
                    ]
                ];

                $columns = [
                    'cust_id' => ['label' => 'ID', 'align' => 'text-left'],
                    'company_name' => ['label' => 'Company', 'align' => 'text-left'],
                    'customer_name' => ['label' => 'Name', 'align' => 'text-left'],
                    'country_name' => ['label' => 'Country', 'align' => 'text-left'],
                    'actions' => ['label' => 'Actions', 'align' => 'text-center'],
                ];

                $cell_callback = function ($key, $row) {
                    if ($key === 'company_name') {
                        return '<span class="font-medium text-gray-900">' . esc_html($row->company_name ?: 'N/A') . '</span>';
                    }
                    if ($key === 'customer_name') {
                        $html = '<div class="flex flex-col">';
                        $html .= '<span class="font-medium text-gray-900">' . esc_html($row->customer_name . ' ' . $row->customer_surname) . '</span>';
                        $html .= '<span class="text-sm text-gray-500">' . esc_html($row->email_address ?: '') . '</span>';
                        // Show the country as a badge using KIT_Commons::badge
                        if (!empty($row->country_name)) {
                            $html .= '<span style="display:inline-block; margin-top:4px;">' . KIT_Commons::badge(esc_html($row->country_name), 'info', ['size' => 'sm']) . '</span>';
                        }
                        $html .= '</div>';
                        return $html;
                    }
                    if ($key === 'country_name') {
                        return '<span class="text-gray-900">' . esc_html($row->country_name ?: 'N/A') . '</span>';
                    }

                    if ($key === 'actions') {
                        $html = '<div class="flex space-x-2 justify-center">';
                        $html .= '<a href="?page=08600-customers&view_customer=' . $row->cust_id . '" class="inline-flex items-center px-3 py-1 text-xs font-medium text-blue-700 bg-blue-100 rounded-md hover:bg-blue-200 transition-colors">View</a>';
                        $html .= '<a href="?page=08600-customers&delete_customer=' . $row->cust_id . '" class="inline-flex items-center px-3 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-md hover:bg-red-200 transition-colors" onclick="return confirm(\'⚠️ WARNING: This will permanently delete the customer AND all their waybills and waybill items. This action cannot be undone. Are you sure?\');">Delete</a>';
                        $html .= '</div>';
                        return $html;
                    }
                    return htmlspecialchars(($row->$key ?? '') ?: '');
                };

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bulk_action'])) {
                    $selectedRows = $_POST['selected_rows'];
                    $action = $_POST['bulk_action'];
                    if ($action === 'delete') {
                        $deletedCount = 0;
                        foreach ($selectedRows as $row) {
                            $result = delete_customer($row, false);
                            if ($result === true) { $deletedCount++; }
                        }
                        $target = admin_url('admin.php?page=08600-customers&deleted=1&bulk=1&waybills_deleted=' . intval($deletedCount) . '&tab=manage-customers');
                        if (!headers_sent()) {
                            wp_safe_redirect($target);
                            exit();
                        } else {
                            echo '<script>window.location.replace(' . json_encode($target) . ');</script>';
                            echo '<noscript><meta http-equiv="refresh" content="0;url=' . esc_url($target) . '"></noscript>';
                            exit();
                        }
                    } elseif ($action === 'export') {
                        echo '<pre>';
                        print_r($selectedRows);
                        echo '</pre>';
                        exit();
                    }
                }

                ?>
                <div style="overflow-x-auto;">
                    <?php echo KIT_Commons::render_versatile_table($customers, $columns, $cell_callback, $options); ?>
                </div>
            </div>
        </div>



    </div>

    <!-- Customer Modal -->
    <?php echo customer_form(); ?>
    </div>

    <script>
        // Function to show add customer form
        function showAddCustomerForm() {
            // Hide all tab contents
            Array.prototype.forEach.call(document.querySelectorAll('.tab-content'), function(panel) {
                panel.style.display = 'none';
            });
            // Show add customer form
            var addCustomerPanel = document.getElementById('add-customer-content');
            if (addCustomerPanel) {
                addCustomerPanel.style.display = 'block';
            }
            // Deactivate all tab buttons
            Array.prototype.forEach.call(document.querySelectorAll('.customer-tabs .tab-btn'), function(btn) {
                btn.classList.remove('active');
            });
        }

        // Robust tab handler (no inline styles required)
        document.addEventListener('DOMContentLoaded', function() {
            function switchCustomerTab(name) {
                // Hide all panels
                Array.prototype.forEach.call(document.querySelectorAll('.tab-content'), function(panel) {
                    panel.style.display = 'none';
                });
                // Deactivate all buttons
                Array.prototype.forEach.call(document.querySelectorAll('.customer-tabs .tab-btn'), function(btn) {
                    btn.classList.remove('active');
                });
                // Show requested panel
                var panel = document.getElementById(name + '-content');
                if (panel) panel.style.display = 'block';
                // Activate corresponding button
                var btn = document.getElementById(name + '-tab');
                if (btn) btn.classList.add('active');
            }

            // Delegate clicks from tab container
            var tabsContainer = document.querySelector('.customer-tabs');
            if (tabsContainer) {
                tabsContainer.addEventListener('click', function(e) {
                    var btn = e.target.closest('.tab-btn');
                    if (!btn) return;
                    e.preventDefault();
                    var name = btn.id.replace('-tab', '');
                    switchCustomerTab(name);
                });
            }

            // Initialize to whichever tab is marked active, else manage-customers
            // If customer_added=1 is in URL, switch to manage-customers tab
            var urlParams = new URLSearchParams(window.location.search);
            var customerAdded = urlParams.get('customer_added');
            
            var activeBtn = document.querySelector('.customer-tabs .tab-btn.active');
            var initial = (customerAdded === '1') ? 'manage-customers' : (activeBtn ? activeBtn.id.replace('-tab', '') : 'manage-customers');
            switchCustomerTab(initial);
        });
    </script>

    <style>
        /* Hide WordPress admin footer text that's overlapping */
        .wp-footer,
        .wp-admin .wp-footer,
        .wp-admin .wp-footer a,
        .wp-admin .wp-footer p {
            display: none !important;
        }

        /* Hide any overlapping WordPress notices */
        .notice,
        .updated,
        .error,
        .warning {
            position: relative !important;
            z-index: 1 !important;
        }

        /* Ensure table has proper z-index */
        .customer-management {
            position: relative;
            z-index: 10;
        }

        /* Tab styling */
        .tab-btn:hover {
            background: #e5e7eb !important;
            color: #374151 !important;
        }

        .tab-btn.active {
            background: white !important;
            color: #374151 !important;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1) !important;
        }

        .tab-content {
            display: none;
        }

        .tab-content:first-child {
            display: block;
        }

        /* Hide specific overlapping text */
        .wp-admin .wrap:after,
        .wp-admin .wrap:before {
            display: none !important;
        }

        /* Hide WordPress footer text specifically */
        .wp-admin .wrap p:contains("Thank you for creating with WordPress"),
        .wp-admin .wrap p:contains("Version"),
        .wp-admin .wrap:contains("Thank you for creating with WordPress"),
        .wp-admin .wrap:contains("Version 6.8.2") {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }

        /* Force hide any overlapping elements */
        .wp-admin .wrap>*:last-child:not(.customer-management):not(.quick-links) {
            display: none !important;
        }
    </style>
<?php
}

function customer_button_with_modal()
{
?>
    <div class="p-6">
        <!-- Trigger Button -->
        <?php echo KIT_Commons::renderButton('Open Modal', 'success', 'md', ['onclick' => 'document.getElementById(\'thaboModal\').classList.remove(\'hidden\')', 'gradient' => true]); ?>

        <!-- Modal Overlay -->
        <div id="thaboModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <!-- Modal Content -->
            <div class="bg-white p-6 rounded-xl shadow-xl w-96 text-center">
                <h2 class="text-xl font-semibold mb-4">Hey Thabo 👋</h2>
                <p class="mb-6">Welcome to the modal!</p>
                <?php echo KIT_Commons::renderButton('Close', 'secondary', 'md', ['onclick' => 'document.getElementById(\'thaboModal\').classList.add(\'hidden\')']); ?>
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

?>
    <div class="grid grid-cols-1 gap-4">
        <input type="hidden" name="cust_id" id="cust_id" value="<?= esc_attr($customer['cust_id'] ?? '') ?>">
        <div>
            <?= KIT_Commons::Linput([
                'label' => 'Company Name',
                'name'  => 'company_name',
                'id'    => 'company_name',
                'type'  => 'text',
                'value' => $customer['company_name'] ?? '',
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                'special' => ''
            ]); ?>
        </div>
        <div>
            <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsOrigin.php'); ?>
            <?= KIT_Commons::Linput([
                'label' => 'Customer Name',
                'name'  => 'name',
                'id'    => 'customer_name',
                'type'  => 'text',
                'value' => $customer['customer_name'] ?? '',
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                'special' => ''
            ]); ?>
        </div>
        <div>
            <?= KIT_Commons::Linput([
                'label' => 'Customer Surname',
                'name'  => 'surname',
                'id'    => 'customer_surname',
                'type'  => 'text',
                'value' => $customer['customer_surname'] ?? '',
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                'special' => ''
            ]); ?>
        </div>
        <div>
            <?= KIT_Commons::Linput([
                'label' => 'Cell',
                'name'  => 'cell',
                'id'    => 'cell',
                'type'  => 'text',
                'value' => $customer['cell'] ?? '',
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                'special' => ''
            ]); ?>
        </div>
        <div>
            <?= KIT_Commons::Linput([
                'label' => 'Address',
                'name'  => 'address',
                'id'    => 'address',
                'type'  => 'text',
                'value' => $customer['address'] ?? '',
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                'special' => ''
            ]); ?>
        </div>
        <div>
            <?= KIT_Commons::Linput([
                'label' => 'Email',
                'name'  => 'email_address',
                'id'    => 'email_address',
                'type'  => 'text',
                'value' => $customer['email_address'] ?? '',
                'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                'special' => ''
            ]); ?>

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
                            <?php echo KIT_Commons::renderButton('Cancel', 'secondary', 'md', ['type' => 'button', 'id' => 'customerModalCloseBtn']); ?>
                            <?php echo KIT_Commons::renderButton($is_edit ? 'Update' : 'Save', 'success', 'md', ['type' => 'submit', 'name' => 'customer_submit', 'id' => 'customerSubmitBtn', 'gradient' => true]); ?>
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
    $cust_data = [
        'cust_id'  => $cust_id,
        'name'     => sanitize_text_field($_POST['name']),
        'surname'  => sanitize_text_field($_POST['surname']),
        'cell'     => sanitize_text_field($_POST['cell']),
        'address'  => sanitize_text_field($_POST['address']),
        'email_address' => sanitize_text_field($_POST['email_address'] ?? ''),
        'company_name' => sanitize_text_field($_POST['company_name'] ?? ''),
        'country_id' => intval($_POST['country_id'] ?? 0),
        'city_id' => intval($_POST['city_id'] ?? 0),
    ];

    // Insert into DB
    $inserted = $wpdb->insert($table_name, $cust_data);

    if ($inserted) {
        echo '<div class="bg-green-100 text-green-800 p-4 rounded mb-4">Customer saved successfully. 🎉</div>';
        wp_redirect(admin_url('admin.php?page=customers-dashboard'));
        exit;
    } else {
        echo '<div class="bg-red-100 text-red-800 p-4 rounded mb-4">Failed to save customer. 😢</div>';
        // Debug information
        if ($wpdb->last_error) {
            echo '<div class="bg-red-100 text-red-800 p-4 rounded mb-4">Database Error: ' . esc_html($wpdb->last_error) . '</div>';
        }
    }
}

function customer_detail_view($customer_id)
{
    global $wpdb;
    $customer_id = intval($customer_id);

    // Handle success/error messages
    if (isset($_GET['updated']) && $_GET['updated'] == '1') {
        if (class_exists('KIT_Toast')) {
            echo KIT_Toast::success('Customer updated successfully!', 'Customer Update');
        } else {
            echo '<div class="notice notice-success"><p>Customer updated successfully.</p></div>';
        }
    }
    
    if (isset($_GET['error']) && $_GET['error'] == '1') {
        if (class_exists('KIT_Toast')) {
            echo KIT_Toast::error('Failed to update customer. Please try again.', 'Customer Update');
        } else {
            echo '<div class="notice notice-error"><p>Failed to update customer. Please try again.</p></div>';
        }
    }

    // Get customer details
    $customer = get_customer_details($customer_id);

    if (!$customer) {
        if (class_exists('KIT_Toast')) {
            echo KIT_Toast::error('Customer not found.', 'Customer Details');
        } else {
        echo '<div class="notice notice-error"><p>Customer not found.</p></div>';
        }
        return;
    }

    // Get waybills for this customer
    $waybills_table = $wpdb->prefix . 'kit_waybills';
    $waybills = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $waybills_table WHERE customer_id = %d ORDER BY created_at DESC",
        $customer_id
    ));

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
                    <div class="flex gap-2">
                        <?php echo KIT_Commons::renderButton('Edit Customer', 'primary', 'md', ['href' => '?page=08600-customers&edit_customer=' . $customer_id, 'gradient' => true]); ?>
                        <?php echo KIT_Commons::renderButton('Test Toast', 'secondary', 'md', ['onclick' => 'testCustomerToast()', 'gradient' => false]); ?>
                    </div>
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
            </div>

            <!-- Waybills Section -->
            
        </div>
        <div class="col-span-3 bg-white shadow rounded-lg p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">Waybills (<?php echo count($waybills); ?>)</h2>
                    <?php echo KIT_Commons::renderButton('View All Waybills', 'primary', 'md', ['href' => '?page=08600-waybills&customer_id=' . $customer_id, 'gradient' => true]); ?>
                </div>

                <?php if (!empty($waybills)): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waybill #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach (array_slice($waybills, 0, 5) as $waybill): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">#<?php echo esc_html($waybill->waybill_no); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $waybill->status === 'completed' ? 'bg-green-100 text-green-800' : ($waybill->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); ?>">
                                                <?php echo ucfirst(esc_html($waybill->status)); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            R<?php echo number_format($waybill->product_invoice_amount, 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y', strtotime($waybill->created_at)); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="?page=08600-Waybill-view&waybill_id=<?php echo $waybill->id; ?>" class="text-blue-600 hover:text-blue-900">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (count($waybills) > 5): ?>
                        <div class="mt-4 text-center">
                            <p class="text-sm text-gray-500">Showing 5 of <?php echo count($waybills); ?> waybills</p>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-8">
                        <p class="text-gray-500">No waybills found for this customer.</p>
                        <?php echo KIT_Commons::renderButton('Create First Waybill', 'primary', 'md', ['href' => '?page=08600-waybill-create&customer_id=' . $customer_id, 'classes' => 'mt-2', 'gradient' => true]); ?>
                    </div>
                <?php endif; ?>
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
    $warehouse_tracking_table = $wpdb->prefix . 'kit_warehouse_tracking';
    $wpdb->delete($warehouse_tracking_table, ['customer_id' => $customer_id]);

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
        c.company_name
    FROM $table_name c
    LEFT JOIN {$wpdb->prefix}kit_operating_countries country ON c.country_id = country.id
    LEFT JOIN {$wpdb->prefix}kit_operating_cities city ON c.city_id = city.id
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
        $customer = array_map(function($value) {
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

    // Convert null values to empty strings to prevent deprecation warnings
    if ($customer) {
        $customer = array_map(function($value) {
            return $value === null ? '' : $value;
        }, $customer);
    }

    if (!$customer) {
        wp_die('Customer not found');
    }

    ?>
    <div class="wrap">
        <?php
        echo KIT_Commons::showingHeader([
            'title' => 'Edit Customer',
            'desc'  => KIT_Commons::kitButton([
                'color' => 'green',
                'href'  => admin_url('admin.php?page=08600-customers&view_customer=' . $customer_id)
            ], 'Back'),
        ]);
        ?>
        <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden p-6 mt-7">
            <h1 class="text-xl font-bold mb-4">Edit Customer</h1>
            <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" class="space-y-4">
                <?php wp_nonce_field('update_customer_nonce', 'cust_update_nonce'); ?>
                <input type="hidden" name="action" value="update_customer" />
                <input type="hidden" name="customer_id" value="<?php echo $customer_id; ?>" />
                <?php theForm($customer); ?>
                <div class="flex justify-end gap-2">
                    <?php echo KIT_Commons::renderButton('Cancel', 'secondary', 'md', ['href' => admin_url('admin.php?page=08600-customers&view_customer=' . $customer_id)]); ?>
                    <?php echo KIT_Commons::renderButton('Update Customer', 'primary', 'md', ['type' => 'submit', 'name' => 'customer_submit', 'gradient' => true]); ?>
                </div>
            </form>
        </div>
    </div>
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
                            '3xl'
                        );
                        ?>

                    </div>

                    <?php if (!empty($waybills)) :

                        $options = [
                            'itemsPerPage' => 5,
                            'currentPage' => $_GET['paged'] ?? 1,
                            'tableClass' => 'min-w-full text-left text-sm text-gray-700',
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
                        $cell_callback = function ($key, $row) {
                            if ($key === 'waybill_no') {
                                //Return a link to the waybill view page example href="?page=08600-Waybill-view&waybill_id=7&waybill_atts=view_waybill"
                                return '<a target="_blank" href="?page=08600-Waybill-view&waybill_id=' . $row->waybill_id . '&waybill_atts=view_waybill" class="text-blue-600 hover:underline">' . $row->waybill_no . '</a>';
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
                            if ($key === 'actions') {

                                return '<a href="?page=08600-Waybill-view=' . $row->waybill_id . '&waybill_atts=view_waybill" class="text-red-600 hover:underline" onclick="return confirm(\'Are you sure you want to delete this customer?\');">Delete</a>';
                                return $html;
                            }
                            return htmlspecialchars(($row->$key ?? '') ?: '');
                        };

                        echo KIT_Commons::render_versatile_table($waybills, $columns, $cell_callback, $options);
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
