<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
class KIT_Customers
{
    public static function init()
    {
        add_action('admin_post_update_customer', [self::class, 'handle_update_customer']);
        add_action('wp_ajax_save_customer_ajax', [self::class, 'handle_save_customer_ajax']);
        add_action('wp_ajax_nopriv_save_customer_ajax', [self::class, 'handle_save_customer_ajax']);
        add_action('wp_ajax_test_customer_ajax', [self::class, 'test_customer_ajax']);
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

        // First check if customer already exists
        $existing_customer = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE name = %s AND surname = %s",
                sanitize_text_field($cust['customer_name']),
                sanitize_text_field($cust['customer_surname'])
            )
        );

        if ($existing_customer) {
            // Customer already exists, return their ID or some indicator
            return $existing_customer->cust_id;
        }


        // Sanitize inputs
        $cust_data = [
            'cust_id'  => rand(1000, 9999),
            'name'     => sanitize_text_field($cust['customer_name']),
            'surname'  => sanitize_text_field($cust['customer_surname']),
            'cell'     => sanitize_text_field($cust['cell']),
            'email_address'  => sanitize_text_field($cust['email_address']),
            'address'  => sanitize_text_field($cust['address']),
            'country_id'  => $cust['country_id'],
            'city_id'  => $cust['city_id'],
            'company_name'  => sanitize_text_field($cust['company_name']),

        ];

        // Insert into DB
        $inserted = $wpdb->insert($table_name, $cust_data);


        if ($inserted === false) {
            return false; // Insert failed
        }


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
            wp_redirect(admin_url('admin.php?page=all-customer-waybills&cust_id=' . $cust_id . '&msg=' . $msg));
            exit;
        }


        exit;
    }

    public static function handle_save_customer_ajax()
    {
        // Debug: Log the request
        error_log('Customer AJAX request received: ' . print_r($_POST, true));

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'save_customer_nonce')) {
            error_log('Customer AJAX nonce failed');
            wp_send_json_error(['message' => 'Security check failed']);
        }

        // Check if required fields are present
        if (empty($_POST['name']) || empty($_POST['surname']) || empty($_POST['cell'])) {
            error_log('Customer AJAX missing required fields');
            wp_send_json_error(['message' => 'Please fill in all required fields (Name, Surname, Cell)']);
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
        $output .= '<button type="submit" class="button button-primary">Upload</button>';
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
    if (isset($_GET['delete_customer'])) {
        delete_customer($_GET['delete_customer']);
    }

    $customers = tholaMaCustomer();

    // Get customer statistics
    $total_customers = count($customers);
    $active_customers = array_filter($customers, function ($c) {
        return !empty($c->company_name);
    });
    $active_customers_count = count($active_customers);
    $inactive_customers = $total_customers - $active_customers_count;
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Customer Dashboard</h1>
        <hr class="wp-header-end">

        <!-- Statistics Cards -->
        <div class="dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #2563eb;">
                <h3 style="margin: 0 0 10px 0; color: #2563eb;">Total Customers</h3>
                <div style="font-size: 2em; font-weight: bold; color: #1e293b;"><?php echo number_format($total_customers); ?></div>
                <p style="margin: 5px 0 0 0; color: #64748b;">All registered customers</p>
            </div>

            <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #059669;">
                <h3 style="margin: 0 0 10px 0; color: #059669;">Active Customers</h3>
                <div style="font-size: 2em; font-weight: bold; color: #1e293b;"><?php echo number_format($active_customers_count); ?></div>
                <p style="margin: 5px 0 0 0; color: #64748b;">With company details</p>
            </div>

            <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #dc2626;">
                <h3 style="margin: 0 0 10px 0; color: #dc2626;">Inactive Customers</h3>
                <div style="font-size: 2em; font-weight: bold; color: #1e293b;"><?php echo number_format($inactive_customers); ?></div>
                <p style="margin: 5px 0 0 0; color: #64748b;">Without company details</p>
            </div>

            <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #7c3aed;">
                <h3 style="margin: 0 0 10px 0; color: #7c3aed;">Quick Actions</h3>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button id="customerModalButton" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl shadow">
                        + Add New Customer
                    </button>
                    <a href="?page=08600-waybill-create" class="button" style="text-decoration: none;">Create Waybill</a>
                </div>
            </div>
        </div>

        <!-- Customer Management Section -->
        <div class="customer-management" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h2 style="margin: 0 0 20px 0;">Customer Management</h2>

            <?php
            $options = [
                'itemsPerPage' => 10,
                'currentPage' => $_GET['paged'] ?? 1,
                'tableClass' => 'min-w-full text-left text-xs text-gray-700',
                'emptyMessage' => 'No customers found. <a href="#" onclick="document.getElementById(\'createCustomerBtn\').click(); return false;">Add your first customer</a>',
                'id' => 'customerTable',
            ];

            $columns = [
                'cust_id' => ['label' => 'ID', 'align' => 'text-left'],
                'company_name' => ['label' => 'Company', 'align' => 'text-left'],
                'customer_name' => ['label' => 'Name', 'align' => 'text-left'],
                'country_name' => ['label' => 'Country', 'align' => 'text-left'],
                'waybills' => ['label' => 'Waybills', 'align' => 'text-left'],
                'actions' => ['label' => 'Actions', 'align' => 'text-center'],
            ];

            $cell_callback = function ($key, $row) {
                if ($key === 'company_name') {
                    return $row->company_name;
                }
                if ($key === 'customer_name') {
                    return $row->customer_name . ' ' . $row->customer_surname;
                }
                if ($key === 'waybills') {
                    return '<a href="?page=all-customer-waybills&cust_id=' . $row->cust_id . '" class="text-blue-600 hover:underline">Waybills</a>';
                }
                if ($key === 'actions') {
                    $html = '<a href="?page=all-customer-waybills&cust_id=' . $row->cust_id . '" class="text-blue-600 hover:underline">View</a> ';
                    $html .= '<a href="?page=customers-dashboard&delete_customer=' . $row->cust_id . '" class="text-red-600 hover:underline" onclick="return confirm(\'Are you sure you want to delete this customer?\');">Delete</a>';
                    return $html;
                }
                return htmlspecialchars(($row->$key ?? '') ?: '');
            };

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bulk_action'])) {
                $selectedRows = $_POST['selected_rows'];
                $action = $_POST['bulk_action'];
                if ($action === 'delete') {
                    foreach ($selectedRows as $row) {
                        delete_customer($row, false);
                    }
                    wp_redirect(admin_url('admin.php?page=customers-dashboard'));
                    exit();
                } elseif ($action === 'export') {
                    echo '<pre>';
                    print_r($selectedRows);
                    echo '</pre>';
                    exit();
                }
            }

            echo KIT_Commons::render_versatile_table($customers, $columns, $cell_callback, $options);
            ?>
        </div>

        <!-- Customer Modal -->
        <?php echo customer_form(); ?>

        <!-- Quick Links -->
        <div class="quick-links" style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <a href="#" onclick="document.getElementById('createCustomerBtn').click(); return false;" style="display: block; padding: 15px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
                <strong>Add New Customer</strong>
            </a>
            <a href="?page=08600-waybill-create" style="display: block; padding: 15px; background: #059669; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
                <strong>Create Waybill</strong>
            </a>
            <a href="?page=route-management" style="display: block; padding: 15px; background: #dc2626; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
                <strong>Manage Routes</strong>
            </a>
            <a href="?page=kit-deliveries" style="display: block; padding: 15px; background: #7c3aed; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
                <strong>Manage Deliveries</strong>
            </a>
        </div>

    </div>

        <!-- Customer Modal -->
        <?php echo customer_form(); ?>
    </div>

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
        .wp-admin .wrap > *:last-child:not(.customer-management):not(.quick-links) {
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
        <button onclick="document.getElementById('thaboModal').classList.remove('hidden')"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl shadow">
            Open Modal
        </button>

        <!-- Modal Overlay -->
        <div id="thaboModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <!-- Modal Content -->
            <div class="bg-white p-6 rounded-xl shadow-xl w-96 text-center">
                <h2 class="text-xl font-semibold mb-4">Hey Thabo 👋</h2>
                <p class="mb-6">Welcome to the modal!</p>
                <button onclick="document.getElementById('thaboModal').classList.add('hidden')"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg">
                    Close
                </button>
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
        <input type="hidden" name="cust_id" id="cust_id" value="<?= esc_attr($customer->cust_id ?? '') ?>">
        <div>
            <?= KIT_Commons::Linput([
                'label' => 'Company Name',
                'name'  => 'company_name',
                'id'    => 'company_name',
                'type'  => 'text',
                'value' => $customer->company_name ?? '',
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
                'value' => $customer->customer_name ?? '',
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
                'value' => $customer->customer_surname ?? '',
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
                'value' => $customer->cell ?? '',
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
                'value' => $customer->address ?? '',
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
                'value' => $customer->email_address ?? '',
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
                <button id="customerModalClose" class="absolute top-3 right-4 text-gray-600 hover:text-black text-xl">
                    &times;
                </button>

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
                            <button type="button" id="customerModalCloseBtn"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded">
                                Cancel
                            </button>
                            <button type="submit" name="customer_submit" id="customerSubmitBtn"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                                <?= $is_edit ? 'Update' : 'Save' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('customerModal');
            const openBtn = document.getElementById('customerModalButton');
            const closeBtn = document.getElementById('customerModalClose');
            const closeBtn2 = document.getElementById('customerModalCloseBtn');
            const form = document.getElementById('customerForm');
            const submitBtn = document.getElementById('customerSubmitBtn');

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
                    const formData = new FormData(form);
                    formData.append('action', 'save_customer_ajax');
                    formData.append('nonce', '<?php echo wp_create_nonce("save_customer_nonce"); ?>');

                    // Debug: Log what we're sending
                    console.log('Sending form data:');
                    for (let [key, value] of formData.entries()) {
                        console.log(key + ': ' + value);
                    }

                    fetch(ajaxurl || '/wp-admin/admin-ajax.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            console.log('Response status:', response.status);
                            console.log('Response headers:', response.headers);
                            return response.json();
                        })
                        .then(data => {
                            console.log('Response data:', data);
                            if (data.success) {
                                // Show success message
                                const successDiv = document.createElement('div');
                                successDiv.className = 'bg-green-100 text-green-800 p-4 rounded mb-4';
                                successDiv.textContent = data.data.message;

                                // Insert before form
                                form.parentNode.insertBefore(successDiv, form);

                                // Close modal after 2 seconds
                                setTimeout(() => {
                                    closeModal();
                                    location.reload(); // Reload page to show new customer
                                }, 2000);
                            } else {
                                throw new Error(data.data.message || 'Unknown error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            submitBtn.disabled = false;
                            submitBtn.textContent = '<?= $is_edit ? 'Update' : 'Save' ?>';

                            // Show error message
                            const errorDiv = document.createElement('div');
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

function delete_customer($id, $redirect = false)
{
    global $wpdb;
    $customer_id = intval($id);

    // First, delete all waybills for this customer
    $waybills_table = $wpdb->prefix . 'kit_waybills';
    $wpdb->delete($waybills_table, ['customer_id' => $customer_id]);

    // Now delete the customer
    $customers_table = $wpdb->prefix . 'kit_customers';
    $deleted = $wpdb->delete($customers_table, ['cust_id' => $customer_id]);

    if ($deleted && $redirect) {
        wp_redirect(esc_url($_SERVER['REQUEST_URI']));
        exit;
    } elseif (!$redirect) {
        echo '<div class="bg-yellow-100 text-yellow-800 p-4 rounded mb-4">Customer not found or already deleted.</div>';
    }
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

    $customer = $wpdb->get_row($query);
    // Return false if no customer found
    if (empty($customer)) {
        return false;
    }
    return $customer;
}

function edit_customer()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_customers';
    $id = isset($_GET['edit_customer']) ? intval($_GET['edit_customer']) : 0;
    $customer = $wpdb->get_row("SELECT cust_id, name as customer_name, surname as customer_surname, cell, address FROM $table_name WHERE cust_id = $id");

    ob_start(); ?>
    <div class="wrap">
        <?php
        echo KIT_Commons::showingHeader([
            'title' => 'Update Customer',
            'desc'  => KIT_Commons::kitButton([
                'color' => 'green',
                'href'  => admin_url('admin.php?page=customers-dashboard')
            ], 'Back'),
        ]);
        ?>
        <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden p-6 mt-7">
            <h1 class="text-xl font-bold mb-4">Our Client</h1>
            <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" class="space-y-4">
                <?php wp_nonce_field('update_customer_nonce', 'cust_update_nonce'); ?>
                <input type="hidden" name="action" value="update_customer" />
                <?php theForm($customer); ?>
                <div class="flex justify-end">
                    <button type="submit" name="customer_submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        Upd78ate
                    </button>
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
                                'Company'  => $customer->company_name,
                                'Name'     => $customer->customer_name,
                                'Surname'  => $customer->customer_surname,
                                'Cell'     => $customer->cell,
                                'Address'  => $customer->address,
                                'Email'  => $customer->email_address,
                                'Country'  => $customer->country_name,
                                'City'     => $customer->city_name,
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
                                'href' => admin_url('admin.php?page=customers-dashboard&delete_customer=' . $customer->cust_id)
                            ], 'Delete Customer');

                            //We will add a edit button here to edit the customer details.
                            echo KIT_Commons::kitButton([
                                'color' => 'blue',
                                'href' => admin_url('admin.php?page=all-customer-waybills&cust_id=' . $customer->cust_id . '&edit_customer=' . $customer->cust_id)
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
            if (KIT_Commons::isAdmin()) {
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
