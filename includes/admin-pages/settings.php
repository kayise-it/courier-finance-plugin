<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include user roles for strict access control
require_once plugin_dir_path(__FILE__) . '../user-roles.php';

// Check if current user has admin capabilities
$wpdb_global_was_set = true; // marker
global $wpdb;

// STRICT ACCESS CONTROL: Only specific administrators can access settings
if (!KIT_User_Roles::can_access_settings()) {
    wp_die('Access denied. This page is only available to authorized administrators (Thando, Mel, Patricia).');
}

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    // Handle seeding action
    if ($_POST['action'] === 'seed_customers' && wp_verify_nonce($_POST['seeding_nonce'], 'seed_customers')) {
        $seeding_result = handle_customer_seeding();
    }

    // Handle unseeding action
    if ($_POST['action'] === 'unseed_customers' && wp_verify_nonce($_POST['unseeding_nonce'], 'unseed_customers')) {
        $unseeding_result = handle_customer_unseeding();
    }

    if (wp_verify_nonce($_POST['settings_nonce'], 'save_settings')) {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_company_details';
        // Only update columns that were posted to avoid wiping existing values
        $sanitizers = [
            'company_name' => function ($v) {
                return sanitize_text_field($v);
            },
            'company_address' => function ($v) {
                return sanitize_textarea_field($v);
            },
            'company_email' => function ($v) {
                return sanitize_email($v);
            },
            'company_phone' => function ($v) {
                return sanitize_text_field($v);
            },
            'company_website' => function ($v) {
                return esc_url_raw($v);
            },
            'company_registration' => function ($v) {
                return sanitize_text_field($v);
            },
            'company_vat_number' => function ($v) {
                return sanitize_text_field($v);
            },
            'bank_name' => function ($v) {
                return sanitize_text_field($v);
            },
            'account_number' => function ($v) {
                return sanitize_text_field($v);
            },
            'branch_code' => function ($v) {
                return sanitize_text_field($v);
            },
            'account_type' => function ($v) {
                return sanitize_text_field($v);
            },
            'account_holder' => function ($v) {
                return sanitize_text_field($v);
            },
            'swift_code' => function ($v) {
                return sanitize_text_field($v);
            },
            'iban' => function ($v) {
                return sanitize_text_field($v);
            },
            'vat_percentage' => function ($v) {
                return (float)$v;
            },
            'sadc_charge' => function ($v) {
                return (float)$v;
            },
            'sad500_charge' => function ($v) {
                return (float)$v;
            },
            'international_price' => function ($v) {
                return (float)$v;
            },
        ];
        $fields = [];
        foreach ($sanitizers as $key => $fn) {
            if (array_key_exists($key, $_POST)) {
                $fields[$key] = $fn($_POST[$key]);
            }
        }
        // If nothing to update, do nothing
        if (!empty($fields)) {
            // Maintain a single-row table
            $exists = $wpdb->get_var("SELECT id FROM $table ORDER BY id ASC LIMIT 1");
            if ($exists) {
                $wpdb->update($table, $fields, ['id' => intval($exists)]);
                $message = 'Settings updated successfully.';
            } else {
                $wpdb->insert($table, $fields);
                $message = 'Settings saved successfully.';
            }
        }
    }

    // Save server configuration
    if (isset($_POST['action']) && $_POST['action'] === 'save_server_config' && wp_verify_nonce($_POST['settings_nonce'], 'save_settings')) {
        $server_config = [
            'kit_server_name' => sanitize_text_field($_POST['server_name'] ?? ''),
            'kit_server_type' => sanitize_text_field($_POST['server_type'] ?? ''),
            'kit_server_host' => sanitize_text_field($_POST['server_host'] ?? ''),
            'kit_server_port' => intval($_POST['server_port'] ?? 3306),
            'kit_server_username' => sanitize_text_field($_POST['server_username'] ?? ''),
            'kit_server_password' => sanitize_text_field($_POST['server_password'] ?? ''),
            'kit_server_database' => sanitize_text_field($_POST['server_database'] ?? ''),
            'kit_server_ssl' => intval($_POST['server_ssl'] ?? 0),
            'kit_api_endpoint' => esc_url_raw($_POST['api_endpoint'] ?? ''),
            'kit_api_timeout' => intval($_POST['api_timeout'] ?? 30),
            'kit_api_headers' => sanitize_textarea_field($_POST['api_headers'] ?? ''),
            'kit_api_retry_attempts' => intval($_POST['api_retry_attempts'] ?? 3),
            'kit_webhook_url' => esc_url_raw($_POST['webhook_url'] ?? ''),
            'kit_webhook_secret' => sanitize_text_field($_POST['webhook_secret'] ?? ''),
            'kit_webhook_events' => array_map('sanitize_text_field', $_POST['webhook_events'] ?? [])
        ];

        foreach ($server_config as $key => $value) {
            update_option($key, $value);
        }

        $message = 'Server configuration saved successfully.';
    }

    // Save brand colors (60/30/10 rule)
    if (isset($_POST['action']) && $_POST['action'] === 'save_colors' && wp_verify_nonce($_POST['settings_nonce'], 'save_settings')) {
        $primary   = isset($_POST['primary_color']) ? sanitize_hex_color($_POST['primary_color']) : '';
        $secondary = isset($_POST['secondary_color']) ? sanitize_hex_color($_POST['secondary_color']) : '';
        $accent    = isset($_POST['accent_color']) ? sanitize_hex_color($_POST['accent_color']) : '';

        // Fallbacks if invalid
        if (!$primary) {
            $primary   = '#2563eb';
        }
        if (!$secondary) {
            $secondary = '#111827';
        }
        if (!$accent) {
            $accent    = '#10b981';
        }

        $schema = [
            'primary'   => $primary,
            'secondary' => $secondary,
            'accent'    => $accent,
            'rule'      => '60/30/10',
            'updated_at' => current_time('mysql'),
        ];

        $json_path = plugin_dir_path(__FILE__) . '../../colorSchema.json';
        // Ensure we can write the file
        $written = @file_put_contents($json_path, wp_json_encode($schema, JSON_PRETTY_PRINT));
        if ($written !== false) {
            $message = 'Colors saved successfully.';
        } else {
            $message = 'Failed to save colors. Please check file permissions.';
        }
    }
}

/**
 * Ensure a prefix-adjusted copy of assets/customers.sql exists as assets/customers_dynamic.sql
 * Recreates the file if it is missing or older than the source file.
 * Returns an array [success => bool, message => string, path => string|null]
 */
function kit_ensure_dynamic_customers_sql(): array
{
    global $wpdb;
    $assetsDir = plugin_dir_path(__FILE__) . '../../assets/';
    $src = $assetsDir . 'customers.sql';
    $dest = $assetsDir . 'customers_dynamic.sql';

    if (!file_exists($src)) {
        return ['success' => false, 'message' => 'Source SQL not found: customers.sql', 'path' => null];
    }

    $needsRegen = !file_exists($dest) || (filemtime($dest) < filemtime($src));
    if (!$needsRegen) {
        return ['success' => true, 'message' => 'Dynamic SQL up to date', 'path' => $dest];
    }

    $sql = file_get_contents($src);
    if ($sql === false) {
        return ['success' => false, 'message' => 'Failed to read customers.sql', 'path' => null];
    }

    // Replace hardcoded wp_ with current WordPress prefix
    $sql = preg_replace('/`wp_([a-zA-Z_]+)`/', '`' . $wpdb->prefix . '$1`', $sql);
    $sql = preg_replace('/(?<![a-zA-Z0-9_])wp_([a-zA-Z_]+)/', $wpdb->prefix . '$1', $sql);

    $written = @file_put_contents($dest, $sql);
    if ($written === false) {
        return ['success' => false, 'message' => 'Failed to write customers_dynamic.sql (check permissions)', 'path' => null];
    }

    return ['success' => true, 'message' => 'customers_dynamic.sql regenerated', 'path' => $dest];
}

// Function to handle customer seeding
function handle_customer_seeding()
{
    global $wpdb;

    // Check if already seeded
    $already_seeded = get_option('kit_customers_seeded', false);
    if ($already_seeded) {
        return [
            'success' => false,
            'message' => 'Customers have already been seeded. This can only be done once per plugin installation.'
        ];
    }

    // Check if customers table has data
    $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_customers WHERE cust_id >= 100001");
    if ($existing_count > 0) {
        return [
            'success' => false,
            'message' => 'Customer data already exists in the database. Seeding is not allowed.'
        ];
    }

    try {
        // Try to use dynamic SQL file first, fall back to original with prefix replacement
        $dynamic_sql_file = plugin_dir_path(__FILE__) . '../../assets/customers_dynamic.sql';
        $original_sql_file = plugin_dir_path(__FILE__) . '../../assets/customers.sql';

        // Ensure customers_dynamic.sql exists and is up to date
        $regen = kit_ensure_dynamic_customers_sql();
        if ($regen['success']) {
            $dynamic_sql_file = $regen['path'];
        }

        if ($dynamic_sql_file && file_exists($dynamic_sql_file)) {
            // Use pre-generated dynamic SQL file
            $sql_file = $dynamic_sql_file;
            $sql_content = file_get_contents($sql_file);
            error_log("Customer seeding using dynamic SQL file with prefix: " . $wpdb->prefix);
        } elseif (file_exists($original_sql_file)) {
            // Use original SQL file with dynamic prefix replacement
            $sql_file = $original_sql_file;
            $sql_content = file_get_contents($sql_file);

            // Replace hardcoded wp_ prefix with dynamic WordPress prefix
            // Use regex to replace table names more precisely
            $sql_content = preg_replace('/`wp_([a-zA-Z_]+)`/', '`' . $wpdb->prefix . '$1`', $sql_content);
            $sql_content = preg_replace('/wp_([a-zA-Z_]+)/', $wpdb->prefix . '$1', $sql_content);

            error_log("Customer seeding using original SQL file with prefix replacement: " . $wpdb->prefix);
        } else {
            return [
                'success' => false,
                'message' => 'Customer SQL file not found. Checked: ' . $dynamic_sql_file . ' and ' . $original_sql_file
            ];
        }

        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql_content)));

        $executed_count = 0;
        $error_count = 0;
        $errors = [];

        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue; // Skip empty statements and comments
            }

            $result = $wpdb->query($statement);
            if ($result === false) {
                $error_count++;
                $errors[] = $wpdb->last_error;
            } else {
                $executed_count++;
            }
        }

        if ($error_count > 0) {
            return [
                'success' => false,
                'message' => "Seeding completed with errors. Executed: $executed_count statements, Errors: $error_count. First error: " . ($errors[0] ?? 'Unknown error')
            ];
        }

        // Mark as seeded
        update_option('kit_customers_seeded', true);

        return [
            'success' => true,
            'message' => "Successfully seeded customers! Executed $executed_count SQL statements."
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Seeding failed: ' . $e->getMessage()
        ];
    }
}

function handle_customer_unseeding()
{
    global $wpdb;

    // Get table names
    $customers_table = $wpdb->prefix . 'kit_customers';
    $waybills_table = $wpdb->prefix . 'kit_waybills';
    $waybill_items_table = $wpdb->prefix . 'kit_waybill_items';
    $warehouse_tracking_table = $wpdb->prefix . 'kit_warehouse_tracking';

    try {
        // Start transaction
        $wpdb->query('START TRANSACTION');

        // Count customers before deletion
        $customer_count = $wpdb->get_var("SELECT COUNT(*) FROM $customers_table");

        if ($customer_count == 0) {
            $wpdb->query('COMMIT');
            return [
                'success' => true,
                'message' => 'No customers found. Database is already clean.'
            ];
        }

        // Get all customer IDs for related data cleanup
        $customer_ids = $wpdb->get_col("SELECT cust_id FROM $customers_table");
        $customer_ids_str = implode(',', array_map('intval', $customer_ids));

        // Delete waybill items for these customers
        $waybill_items_deleted = 0;
        if (!empty($customer_ids_str)) {
            $waybill_ids = $wpdb->get_col("SELECT id FROM $waybills_table WHERE customer_id IN ($customer_ids_str)");
            if (!empty($waybill_ids)) {
                $waybill_ids_str = implode(',', array_map('intval', $waybill_ids));
                $waybill_items_deleted = $wpdb->query("DELETE FROM $waybill_items_table WHERE waybillno IN ($waybill_ids_str)");
            }
        }

        // Delete waybills for these customers
        $waybills_deleted = 0;
        if (!empty($customer_ids_str)) {
            $waybills_deleted = $wpdb->query("DELETE FROM $waybills_table WHERE customer_id IN ($customer_ids_str)");
        }

        // Delete warehouse tracking for these customers
        $warehouse_tracking_deleted = 0;
        if (!empty($customer_ids_str)) {
            $warehouse_tracking_deleted = $wpdb->query("DELETE FROM $warehouse_tracking_table WHERE customer_id IN ($customer_ids_str)");
        }

        // Delete all customers
        $customers_deleted = $wpdb->query("DELETE FROM $customers_table");

        // Reset auto-increment counter
        $wpdb->query("ALTER TABLE $customers_table AUTO_INCREMENT = 1");

        // Reset seeding flag
        delete_option('kit_customers_seeded');

        // Verify deletion
        $remaining_customers = $wpdb->get_var("SELECT COUNT(*) FROM $customers_table");

        if ($remaining_customers == 0) {
            $wpdb->query('COMMIT');
            return [
                'success' => true,
                'message' => "Successfully unseeded customers! Deleted: $customers_deleted customers, $waybills_deleted waybills, $waybill_items_deleted waybill items, $warehouse_tracking_deleted warehouse tracking records."
            ];
        } else {
            $wpdb->query('ROLLBACK');
            return [
                'success' => false,
                'message' => "Error: Some customers remain. Transaction rolled back. Remaining: $remaining_customers"
            ];
        }
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        return [
            'success' => false,
            'message' => 'Unseeding failed: ' . $e->getMessage()
        ];
    }
}
?>

<div class="wrap">
    <?php
    echo KIT_Commons::showingHeader([
        'title' => 'Settings & Configuration',
        'desc' => 'Manage your company settings, banking details, and system charges',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />',
    ]);
    ?>

    <?php if (isset($message)) {
        require_once plugin_dir_path(__FILE__) . '../components/toast.php';
        echo KIT_Toast::success($message);
    } ?>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 mb-8">
        <nav class="flex space-x-8" aria-label="Tabs">
            <?php echo KIT_Commons::renderButton('Banking Details', 'ghost', 'sm', ['id' => 'tab-banking', 'classes' => 'tab-button active', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>', 'iconPosition' => 'left']); ?>
            <?php echo KIT_Commons::renderButton('Company Details', 'ghost', 'sm', ['id' => 'tab-company', 'classes' => 'tab-button', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>', 'iconPosition' => 'left']); ?>
            <?php echo KIT_Commons::renderButton('VAT & Charges', 'ghost', 'sm', ['id' => 'tab-charges', 'classes' => 'tab-button', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>', 'iconPosition' => 'left']); ?>
            <?php echo KIT_Commons::renderButton('Color Scheme', 'ghost', 'sm', ['id' => 'tab-colors', 'classes' => 'tab-button', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7a4 4 0 018 0v10a4 4 0 11-8 0V7zm8 0a4 4 0 018 0v4a4 4 0 01-4 4h-4" />', 'iconPosition' => 'left']); ?>
            <?php echo KIT_Commons::renderButton('Seeding', 'ghost', 'sm', ['id' => 'tab-seeding', 'classes' => 'tab-button', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>', 'iconPosition' => 'left']); ?>
            <?php echo KIT_Commons::renderButton('Server Connections', 'ghost', 'sm', ['id' => 'tab-servers', 'classes' => 'tab-button', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>', 'iconPosition' => 'left']); ?>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="tab-contents">
        <!-- Banking Details Tab -->
        <div id="content-banking" class="tab-panel active">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Banking Details</h2>
                    <p class="text-sm text-gray-600 mt-1">Configure your company's banking information for payments and invoices</p>
                </div>

                <div class="p-6">
                    <form method="post" action="">
                        <?php wp_nonce_field('save_settings', 'settings_nonce'); ?>
                        <input type="hidden" name="action" value="save_banking">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                                <input type="text" id="bank_name" name="bank_name"
                                    value="<?php echo esc_attr($wpdb->get_var('SELECT bank_name FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="form-group">
                                <label for="account_number" class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                                <input type="text" id="account_number" name="account_number"
                                    value="<?php echo esc_attr($wpdb->get_var('SELECT account_number FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="form-group">
                                <label for="branch_code" class="block text-sm font-medium text-gray-700 mb-2">Branch Code</label>
                                <input type="text" id="branch_code" name="branch_code"
                                    value="<?php echo esc_attr($wpdb->get_var('SELECT branch_code FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="form-group">
                                <label for="account_type" class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                                <select id="account_type" name="account_type"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <?php $acct = $wpdb->get_var('SELECT account_type FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1'); ?>
                                    <option value="savings" <?php selected($acct, 'savings'); ?>>Savings Account</option>
                                    <option value="current" <?php selected($acct, 'current'); ?>>Current Account</option>
                                    <option value="business" <?php selected($acct, 'business'); ?>>Business Account</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="account_holder" class="block text-sm font-medium text-gray-700 mb-2">Account Holder Name</label>
                                <input type="text" id="account_holder" name="account_holder"
                                    value="<?php echo esc_attr($wpdb->get_var('SELECT account_holder FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="form-group">
                                <label for="swift_code" class="block text-sm font-medium text-gray-700 mb-2">Swift Code</label>
                                <input type="text" id="swift_code" name="swift_code"
                                    value="<?php echo esc_attr($wpdb->get_var('SELECT swift_code FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="form-group md:col-span-2">
                                <label for="iban" class="block text-sm font-medium text-gray-700 mb-2">IBAN (International Bank Account Number)</label>
                                <input type="text" id="iban" name="iban"
                                    value="<?php echo esc_attr($wpdb->get_var('SELECT iban FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <?php echo KIT_Commons::renderButton('Save Banking Details', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]); ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Company Details Tab -->
        <div id="content-company" class="tab-panel hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Company Details</h2>
                    <p class="text-sm text-gray-600 mt-1">Update your company information and contact details</p>
                </div>

                <div class="p-6">
                    <form method="post" action="">
                        <?php wp_nonce_field('save_settings', 'settings_nonce'); ?>
                        <input type="hidden" name="action" value="save_company">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group md:col-span-2">
                                <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                                <input type="text" id="company_name" name="company_name"
                                    value="<?php echo esc_attr($wpdb->get_var('SELECT company_name FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="form-group md:col-span-2">
                                <label for="company_address" class="block text-sm font-medium text-gray-700 mb-2">Company Address</label>
                                <textarea id="company_address" name="company_address" rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo esc_textarea($wpdb->get_var('SELECT company_address FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="company_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" id="company_email" name="company_email"
                                    value="<?php echo esc_attr($wpdb->get_var('SELECT company_email FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="form-group">
                                <label for="company_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" id="company_phone" name="company_phone"
                                    value="<?php echo esc_attr($wpdb->get_var('SELECT company_phone FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="form-group">
                                <label for="company_website" class="block text-sm font-medium text-gray-700 mb-2">Website</label>
                                <input type="url" id="company_website" name="company_website"
                                    value="<?php echo esc_attr($wpdb->get_var('SELECT company_website FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="form-group">
                                <label for="company_registration" class="block text-sm font-medium text-gray-700 mb-2">Company Registration Number</label>
                                <input type="text" id="company_registration" name="company_registration"
                                    value="<?php echo esc_attr($wpdb->get_var('SELECT company_registration FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="form-group">
                                <label for="company_vat_number" class="block text-sm font-medium text-gray-700 mb-2">VAT Registration Number</label>
                                <input type="text" id="company_vat_number" name="company_vat_number"
                                    value="<?php echo esc_attr($wpdb->get_var('SELECT company_vat_number FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <?php echo KIT_Commons::renderButton('Save Company Details', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]); ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- VAT & Charges Tab -->
        <div id="content-charges" class="tab-panel hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">VAT & Charges</h2>
                    <p class="text-sm text-gray-600 mt-1">Configure VAT percentage and SADC/SAD500 charges</p>
                </div>

                <div class="p-6">
                    <form method="post" action="">
                        <?php wp_nonce_field('save_settings', 'settings_nonce'); ?>
                        <input type="hidden" name="action" value="save_charges">

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="form-group">
                                <label for="vat_percentage" class="block text-sm font-medium text-gray-700 mb-2">
                                    VAT Percentage (%)
                                </label>
                                <div class="relative">
                                    <input type="number" id="vat_percentage" name="vat_percentage"
                                        value="<?php echo esc_attr(KIT_Waybills::vatRate()); ?>"
                                        step="0.01" min="0" max="100" disabled
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 text-sm">%</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">VAT rate is fixed at 10% (managed in code)</p>
                            </div>

                            <div class="form-group">
                                <label for="sadc_charge" class="block text-sm font-medium text-gray-700 mb-2">
                                    SADC Charge (R)
                                </label>
                                <div class="relative">

                                    <input type="number" id="sadc_charge" name="sadc_charge"
                                        value="<?php echo esc_attr(KIT_Waybills::sad()); ?>"
                                        step="0.01" min="0"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 text-sm">R</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">SADC documentation charge</p>
                            </div>

                            <div class="form-group">
                                <label for="sad500_charge" class="block text-sm font-medium text-gray-700 mb-2">
                                    SAD500 Charge (R)
                                </label>
                                <div class="relative">
                                    <input type="number" id="sad500_charge" name="sad500_charge"
                                        value="<?php echo esc_attr(KIT_Waybills::sadc_certificate()); ?>"
                                        step="0.01" min="0"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 text-sm">R</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">SAD500 customs declaration charge</p>
                            </div>

                            <div class="form-group">
                                <label for="international_price" class="block text-sm font-medium text-gray-700 mb-2">
                                    International Price (USD)
                                </label>
                                <div class="relative">
                                    <input type="number" id="international_price" name="international_price"
                                        value="<?php echo esc_attr(KIT_Waybills::international_price()); ?>"
                                        step="0.01" min="0"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 text-sm">$</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">International shipping fee in USD (converted to Rands)</p>

                                <!-- Live Exchange Rate Display -->
                                <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-blue-700 font-medium">Live Exchange Rate:</span>
                                        <span class="text-blue-800 font-semibold" id="exchange-rate">Loading...</span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between text-sm">
                                        <span class="text-blue-600">Converted to ZAR:</span>
                                        <span class="text-blue-900 font-bold text-lg" id="zar-amount">R 0.00</span>
                                    </div>
                                    <div class="mt-1 text-xs text-blue-500">
                                        <span id="last-updated">Last updated: Never</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Preview Section -->
                        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Charge Preview</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">VAT Rate:</span>
                                    <span class="font-medium" id="vat-preview">15%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">SADC Charge:</span>
                                    <span class="font-medium" id="sadc-preview">R 0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">SAD500 Charge:</span>
                                    <span class="font-medium" id="sad500-preview">R 0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">International Price:</span>
                                    <span class="font-medium" id="international-preview">$0.00</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <?php echo KIT_Commons::renderButton('Save VAT & Charges', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]); ?>

                            <!-- Database Migration Button -->
                            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <h4 class="text-sm font-medium text-yellow-800 mb-2">Database Migration</h4>
                                <p class="text-xs text-yellow-700 mb-3">If the International Price field is not working, click the button below to add it to the database.</p>
                                <?php echo KIT_Commons::renderButton('Add International Price Field to Database', 'warning', 'md', ['id' => 'migrate-db', 'type' => 'button', 'gradient' => true]); ?>
                                <div id="migration-result" class="mt-2 text-sm"></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Brand Colors (60/30/10) Tab -->
        <div id="content-colors" class="tab-panel hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Brand Colors (60 / 30 / 10)</h2>
                    <p class="text-sm text-gray-600 mt-1">Set your primary (60%), secondary (30%), and accent (10%) brand colors.</p>
                </div>
                <div class="p-6">
                    <?php
                    $schema_path = plugin_dir_path(__FILE__) . '../../colorSchema.json';
                    $schema = ['primary' => '#2563eb', 'secondary' => '#111827', 'accent' => '#10b981'];
                    if (file_exists($schema_path)) {
                        $loaded = json_decode(file_get_contents($schema_path), true);
                        if (is_array($loaded)) {
                            $schema = array_merge($schema, $loaded);
                        }
                    }
                    ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('save_settings', 'settings_nonce'); ?>
                        <input type="hidden" name="action" value="save_colors">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Primary (60%)</label>
                                <input type="color" name="primary_color" value="<?= esc_attr($schema['primary']); ?>" class="w-20 h-10 p-0 border rounded">
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Secondary (30%)</label>
                                <input type="color" name="secondary_color" value="<?= esc_attr($schema['secondary']); ?>" class="w-20 h-10 p-0 border rounded">
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Accent (10%)</label>
                                <input type="color" name="accent_color" value="<?= esc_attr($schema['accent']); ?>" class="w-20 h-10 p-0 border rounded">
                            </div>
                        </div>
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <?php echo KIT_Commons::renderButton('Save Colors', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]); ?>
                        </div>
                    </form>
                    <div class="mt-6 grid grid-cols-3 gap-4">
                        <div class="rounded-lg border p-3" style="background: <?= esc_attr($schema['primary']); ?>; color: #fff;">Primary</div>
                        <div class="rounded-lg border p-3" style="background: <?= esc_attr($schema['secondary']); ?>; color: #fff;">Secondary</div>
                        <div class="rounded-lg border p-3" style="background: <?= esc_attr($schema['accent']); ?>; color: #fff;">Accent</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Seeding Tab -->
        <div id="content-seeding" class="tab-panel hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Customer Data Seeding</h2>
                    <p class="text-sm text-gray-600 mt-1">Seed customer data from CSV file to the database. This can only be done once per plugin installation.</p>
                </div>

                <div class="p-6">
                    <?php
                    // Check seeding status
                    $already_seeded = get_option('kit_customers_seeded', false);
                    $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}kit_customers WHERE cust_id >= 100001");
                    $can_seed = !$already_seeded && $existing_count == 0;
                    ?>

                    <!-- Seeding Status -->
                    <div class="mb-6 p-4 rounded-lg border <?php echo $already_seeded ? 'bg-green-50 border-green-200' : ($existing_count > 0 ? 'bg-yellow-50 border-yellow-200' : 'bg-blue-50 border-blue-200'); ?>">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <?php if ($already_seeded): ?>
                                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                <?php elseif ($existing_count > 0): ?>
                                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                <?php else: ?>
                                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                <?php endif; ?>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium <?php echo $already_seeded ? 'text-green-800' : ($existing_count > 0 ? 'text-yellow-800' : 'text-blue-800'); ?>">
                                    <?php if ($already_seeded): ?>
                                        ✅ Customers Already Seeded
                                    <?php elseif ($existing_count > 0): ?>
                                        ⚠️ Customer Data Exists
                                    <?php else: ?>
                                        ℹ️ Ready to Seed
                                    <?php endif; ?>
                                </h3>
                                <div class="mt-2 text-sm <?php echo $already_seeded ? 'text-green-700' : ($existing_count > 0 ? 'text-yellow-700' : 'text-blue-700'); ?>">
                                    <?php if ($already_seeded): ?>
                                        <p>Customer data has been successfully seeded and is protected from re-seeding.</p>
                                    <?php elseif ($existing_count > 0): ?>
                                        <p>Customer data already exists in the database (<?php echo $existing_count; ?> records). Seeding is not allowed to prevent data duplication.</p>
                                    <?php else: ?>
                                        <p>No customer data found. You can safely seed the customer data from the CSV file.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seeding Form -->
                    <?php if ($can_seed): ?>
                        <form method="post" action="" id="seeding-form">
                            <?php wp_nonce_field('seed_customers', 'seeding_nonce'); ?>
                            <input type="hidden" name="action" value="seed_customers">

                            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">What will be seeded:</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li>• Customer data from <code>assets/customers.sql</code></li>
                                    <li>• All foreign key relationships will be respected</li>
                                    <li>• Data will be validated against existing countries and cities</li>
                                    <li>• This operation can only be performed once per plugin installation</li>
                                </ul>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-600">
                                    <p><strong>Warning:</strong> This action cannot be undone and can only be performed once.</p>
                                </div>
                                <div>
                                    <?php echo KIT_Commons::renderButton('Seed Customer Data', 'primary', 'lg', ['type' => 'submit', 'gradient' => true, 'id' => 'seed-button']); ?>
                                </div>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="text-gray-500">
                                <?php if ($already_seeded): ?>
                                    <p class="text-lg font-medium">Seeding is not available</p>
                                    <p class="text-sm mt-2">Customer data has already been seeded and is protected from re-seeding.</p>
                                <?php else: ?>
                                    <p class="text-lg font-medium">Seeding is not available</p>
                                    <p class="text-sm mt-2">Customer data already exists in the database. Please clear the data first if you need to re-seed.</p>
                                <?php endif; ?>
                            </div>

                            <?php
                            // Offer to regenerate customers_dynamic.sql if missing
                            $ensure = kit_ensure_dynamic_customers_sql();
                            if (!$ensure['success']) {
                                echo '<p class="mt-4 text-sm text-yellow-700">' . esc_html($ensure['message']) . '</p>';
                            }
                            ?>

                            <!-- Unseed Button -->
                            <?php if ($already_seeded || $existing_count > 0): ?>
                                <div class="mt-6">
                                    <form method="post" action="" id="unseeding-form" onsubmit="return confirm('⚠️ WARNING: This will permanently delete ALL customers and their related data (waybills, waybill items, warehouse tracking). This action cannot be undone. Are you sure you want to continue?');">
                                        <?php wp_nonce_field('unseed_customers', 'unseeding_nonce'); ?>
                                        <input type="hidden" name="action" value="unseed_customers">
                                        <?php echo KIT_Commons::renderButton('🗑️ Unseed All Customers', 'danger', 'lg', ['type' => 'submit', 'gradient' => true]); ?>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Seeding Result -->
                    <?php if (isset($seeding_result)): ?>
                        <div class="mt-6 p-4 rounded-lg border <?php echo $seeding_result['success'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?>">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <?php if ($seeding_result['success']): ?>
                                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium <?php echo $seeding_result['success'] ? 'text-green-800' : 'text-red-800'; ?>">
                                        <?php echo $seeding_result['success'] ? 'Seeding Successful' : 'Seeding Failed'; ?>
                                    </h3>
                                    <div class="mt-2 text-sm <?php echo $seeding_result['success'] ? 'text-green-700' : 'text-red-700'; ?>">
                                        <p><?php echo esc_html($seeding_result['message']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Unseeding Result -->
                    <?php if (isset($unseeding_result)): ?>
                        <div class="mt-6 p-4 rounded-lg border <?php echo $unseeding_result['success'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?>">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <?php if ($unseeding_result['success']): ?>
                                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    <?php else: ?>
                                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium <?php echo $unseeding_result['success'] ? 'text-green-800' : 'text-red-800'; ?>">
                                        <?php echo $unseeding_result['success'] ? 'Unseeding Successful' : 'Unseeding Failed'; ?>
                                    </h3>
                                    <div class="mt-2 text-sm <?php echo $unseeding_result['success'] ? 'text-green-700' : 'text-red-700'; ?>">
                                        <p><?php echo esc_html($unseeding_result['message']); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Server Connections Tab -->
        <div id="content-servers" class="tab-panel hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Server Connections</h2>
                    <p class="text-sm text-gray-600 mt-1">Configure external server connections for data synchronization and API integrations</p>
                </div>

                <div class="p-6">
                    <!-- Connection Status Overview -->
                    <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Connection Status</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-green-800">Database</p>
                                        <p class="text-sm text-green-600">Connected</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-yellow-800">External API</p>
                                        <p class="text-sm text-yellow-600">Not Configured</p>
                                    </div>
                                </div>
                            </div>
                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-800">Webhook</p>
                                        <p class="text-sm text-gray-600">Not Configured</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Server Configuration Form -->
                    <form method="post" action="" id="server-config-form">
                        <?php wp_nonce_field('save_settings', 'settings_nonce'); ?>
                        <input type="hidden" name="action" value="save_server_config">

                        <div class="space-y-6">
                            <!-- Primary Server Configuration -->
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Primary Server Configuration</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="form-group">
                                        <label for="server_name" class="block text-sm font-medium text-gray-700 mb-2">Server Name</label>
                                        <input type="text" id="server_name" name="server_name" 
                                            value="<?php echo esc_attr(get_option('kit_server_name', '')); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="e.g., Main Database Server">
                                    </div>

                                    <div class="form-group">
                                        <label for="server_type" class="block text-sm font-medium text-gray-700 mb-2">Server Type</label>
                                        <select id="server_type" name="server_type" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="mysql" <?php selected(get_option('kit_server_type', ''), 'mysql'); ?>>MySQL Database</option>
                                            <option value="api" <?php selected(get_option('kit_server_type', ''), 'api'); ?>>REST API</option>
                                            <option value="webhook" <?php selected(get_option('kit_server_type', ''), 'webhook'); ?>>Webhook</option>
                                            <option value="ftp" <?php selected(get_option('kit_server_type', ''), 'ftp'); ?>>FTP Server</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="server_host" class="block text-sm font-medium text-gray-700 mb-2">Host/URL</label>
                                        <input type="text" id="server_host" name="server_host" 
                                            value="<?php echo esc_attr(get_option('kit_server_host', '')); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="e.g., api.example.com or 192.168.1.100">
                                    </div>

                                    <div class="form-group">
                                        <label for="server_port" class="block text-sm font-medium text-gray-700 mb-2">Port</label>
                                        <input type="number" id="server_port" name="server_port" 
                                            value="<?php echo esc_attr(get_option('kit_server_port', '3306')); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="3306">
                                    </div>

                                    <div class="form-group">
                                        <label for="server_username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                        <input type="text" id="server_username" name="server_username" 
                                            value="<?php echo esc_attr(get_option('kit_server_username', '')); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Database or API username">
                                    </div>

                                    <div class="form-group">
                                        <label for="server_password" class="block text-sm font-medium text-gray-700 mb-2">Password/API Key</label>
                                        <input type="password" id="server_password" name="server_password" 
                                            value="<?php echo esc_attr(get_option('kit_server_password', '')); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Database password or API key">
                                    </div>

                                    <div class="form-group">
                                        <label for="server_database" class="block text-sm font-medium text-gray-700 mb-2">Database Name</label>
                                        <input type="text" id="server_database" name="server_database" 
                                            value="<?php echo esc_attr(get_option('kit_server_database', '')); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Database name (if applicable)">
                                    </div>

                                    <div class="form-group">
                                        <label for="server_ssl" class="block text-sm font-medium text-gray-700 mb-2">SSL/TLS</label>
                                        <select id="server_ssl" name="server_ssl" 
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="0" <?php selected(get_option('kit_server_ssl', '0'), '0'); ?>>Disabled</option>
                                            <option value="1" <?php selected(get_option('kit_server_ssl', '0'), '1'); ?>>Enabled</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- API Configuration -->
                            <div class="bg-blue-50 rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">API Configuration</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="form-group">
                                        <label for="api_endpoint" class="block text-sm font-medium text-gray-700 mb-2">API Endpoint</label>
                                        <input type="url" id="api_endpoint" name="api_endpoint" 
                                            value="<?php echo esc_attr(get_option('kit_api_endpoint', '')); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="https://api.example.com/v1/">
                                    </div>

                                    <div class="form-group">
                                        <label for="api_timeout" class="block text-sm font-medium text-gray-700 mb-2">Timeout (seconds)</label>
                                        <input type="number" id="api_timeout" name="api_timeout" 
                                            value="<?php echo esc_attr(get_option('kit_api_timeout', '30')); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="30">
                                    </div>

                                    <div class="form-group">
                                        <label for="api_headers" class="block text-sm font-medium text-gray-700 mb-2">Custom Headers (JSON)</label>
                                        <textarea id="api_headers" name="api_headers" rows="3"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder='{"Content-Type": "application/json", "Authorization": "Bearer token"}'><?php echo esc_textarea(get_option('kit_api_headers', '')); ?></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="api_retry_attempts" class="block text-sm font-medium text-gray-700 mb-2">Retry Attempts</label>
                                        <input type="number" id="api_retry_attempts" name="api_retry_attempts" 
                                            value="<?php echo esc_attr(get_option('kit_api_retry_attempts', '3')); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="3">
                                    </div>
                                </div>
                            </div>

                            <!-- Webhook Configuration -->
                            <div class="bg-purple-50 rounded-lg p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Webhook Configuration</h3>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="form-group">
                                        <label for="webhook_url" class="block text-sm font-medium text-gray-700 mb-2">Webhook URL</label>
                                        <input type="url" id="webhook_url" name="webhook_url" 
                                            value="<?php echo esc_attr(get_option('kit_webhook_url', '')); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="https://webhook.example.com/endpoint">
                                    </div>

                                    <div class="form-group">
                                        <label for="webhook_secret" class="block text-sm font-medium text-gray-700 mb-2">Webhook Secret</label>
                                        <input type="password" id="webhook_secret" name="webhook_secret" 
                                            value="<?php echo esc_attr(get_option('kit_webhook_secret', '')); ?>"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="Secret key for webhook verification">
                                    </div>

                                    <div class="form-group">
                                        <label for="webhook_events" class="block text-sm font-medium text-gray-700 mb-2">Events to Send</label>
                                        <div class="space-y-2">
                                            <label class="flex items-center">
                                                <input type="checkbox" name="webhook_events[]" value="waybill_created" 
                                                    <?php checked(in_array('waybill_created', get_option('kit_webhook_events', []))); ?>
                                                    class="form-checkbox">
                                                <span class="ml-2 text-sm text-gray-700">Waybill Created</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" name="webhook_events[]" value="waybill_updated" 
                                                    <?php checked(in_array('waybill_updated', get_option('kit_webhook_events', []))); ?>
                                                    class="form-checkbox">
                                                <span class="ml-2 text-sm text-gray-700">Waybill Updated</span>
                                            </label>
                                            <label class="flex items-center">
                                                <input type="checkbox" name="webhook_events[]" value="delivery_created" 
                                                    <?php checked(in_array('delivery_created', get_option('kit_webhook_events', []))); ?>
                                                    class="form-checkbox">
                                                <span class="ml-2 text-sm text-gray-700">Delivery Created</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 pt-6 border-t border-gray-200 flex justify-between">
                            <div>
                                <?php echo KIT_Commons::renderButton('Test Connection', 'secondary', 'lg', ['type' => 'button', 'id' => 'test-connection', 'gradient' => true]); ?>
                            </div>
                            <div>
                                <?php echo KIT_Commons::renderButton('Save Server Configuration', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]); ?>
                            </div>
                        </div>
                    </form>

                    <!-- Connection Test Results -->
                    <div id="connection-test-results" class="mt-6 hidden">
                        <div class="bg-white border rounded-lg p-4">
                            <h4 class="text-lg font-medium text-gray-900 mb-2">Connection Test Results</h4>
                            <div id="test-results-content"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Notice -->
    <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Security Notice</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>This settings page is restricted to authorized administrators only. All changes are logged for security purposes.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .tab-button {
        @apply text-gray-500 hover:text-gray-700 hover:bg-gray-100;
    }

    .tab-button.active {
        @apply text-blue-600 bg-blue-50 border-b-2 border-blue-600;
    }

    .tab-panel {
        @apply transition-all duration-300 ease-in-out;
    }

    .form-group {
        @apply space-y-1;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab functionality
        const tabs = document.querySelectorAll('.tab-button');
        const panels = document.querySelectorAll('.tab-panel');
        const storageKey = 'kit_settings_active_tab';

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.id.replace('tab-', 'content-');

                // Remove active class from all tabs and panels
                tabs.forEach(t => t.classList.remove('active'));
                panels.forEach(p => p.classList.add('hidden'));

                // Add active class to clicked tab and show target panel
                tab.classList.add('active');
                document.getElementById(target).classList.remove('hidden');

                // Persist active tab id
                try {
                    localStorage.setItem(storageKey, tab.id);
                } catch (e) {}
            });
        });

        // Restore previously active tab on load
        try {
            const savedTabId = localStorage.getItem(storageKey);
            const savedTab = savedTabId ? document.getElementById(savedTabId) : null;
            if (savedTab && savedTab.classList.contains('tab-button')) {
                // Simulate click to apply classes/panels logic
                savedTab.click();
            }
        } catch (e) {}

        // Live preview for charges
        const vatInput = document.getElementById('vat_percentage');
        const sadcInput = document.getElementById('sadc_charge');
        const sad500Input = document.getElementById('sad500_charge');
        const internationalInput = document.getElementById('international_price');

        function updatePreview() {
            document.getElementById('vat-preview').textContent = (vatInput.value || 0) + '%';
            document.getElementById('sadc-preview').textContent = 'R ' + parseFloat(sadcInput.value || 0).toFixed(2);
            document.getElementById('sad500-preview').textContent = 'R ' + parseFloat(sad500Input.value || 0).toFixed(2);
            document.getElementById('international-preview').textContent = '$' + parseFloat(internationalInput.value || 0).toFixed(2);
        }

        vatInput.addEventListener('input', updatePreview);
        sadcInput.addEventListener('input', updatePreview);
        sad500Input.addEventListener('input', updatePreview);
        internationalInput.addEventListener('input', updatePreview);

        // Initialize preview
        updatePreview();

        // Live Exchange Rate Functionality
        let currentExchangeRate = 18.50; // Default fallback rate

        async function fetchExchangeRate() {
            try {
                // Try to fetch from a free exchange rate API
                const response = await fetch('https://api.exchangerate-api.com/v4/latest/USD');
                if (response.ok) {
                    const data = await response.json();
                    currentExchangeRate = data.rates.ZAR;
                    updateExchangeRateDisplay();
                    updateLastUpdated();
                } else {
                    throw new Error('Failed to fetch exchange rate');
                }
            } catch (error) {
                console.log('Using fallback exchange rate:', currentExchangeRate);
                updateExchangeRateDisplay();
                updateLastUpdated();
            }
        }

        function updateExchangeRateDisplay() {
            const usdAmount = parseFloat(internationalInput.value) || 0;
            const zarAmount = usdAmount * currentExchangeRate;

            document.getElementById('exchange-rate').textContent = `1 USD = R ${currentExchangeRate.toFixed(2)}`;
            document.getElementById('zar-amount').textContent = `R ${zarAmount.toFixed(2)}`;
        }

        function updateLastUpdated() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', {
                hour12: false,
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            document.getElementById('last-updated').textContent = `Last updated: ${timeString}`;
        }

        // Update exchange rate display when international price changes
        internationalInput.addEventListener('input', updateExchangeRateDisplay);

        // Fetch exchange rate on page load and every 5 minutes
        fetchExchangeRate();
        setInterval(fetchExchangeRate, 5 * 60 * 1000); // Update every 5 minutes

        // Seeding form confirmation
        const seedingForm = document.getElementById('seeding-form');
        const seedButton = document.getElementById('seed-button');

        if (seedingForm && seedButton) {
            seedingForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const confirmed = confirm(
                    'Are you sure you want to seed customer data?\n\n' +
                    'This action:\n' +
                    '• Cannot be undone\n' +
                    '• Can only be performed once per plugin installation\n' +
                    '• Will insert customer data from the CSV file\n\n' +
                    'Click OK to continue or Cancel to abort.'
                );

                if (confirmed) {
                    // Show loading state
                    seedButton.disabled = true;
                    seedButton.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Seeding...';

                    // Submit the form
                    this.submit();
                }
            });
        }

        // Database Migration Functionality
        const migrateButton = document.getElementById('migrate-db');
        const migrationResult = document.getElementById('migration-result');

        if (migrateButton) {
            migrateButton.addEventListener('click', async function() {
                migrateButton.disabled = true;
                migrateButton.textContent = 'Adding Field...';
                migrationResult.innerHTML = '<span class="text-blue-600">⏳ Adding international_price field to database...</span>';

                try {
                    // Create a simple AJAX request to run the migration
                    const formData = new FormData();
                    formData.append('action', 'migrate_international_price');
                    formData.append('nonce', '<?php echo wp_create_nonce("migrate_international_price"); ?>');

                    const response = await fetch(ajaxurl || '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        migrationResult.innerHTML = '<span class="text-green-600">✅ ' + result.data.message + '</span>';
                        // Refresh the page after successful migration
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                    } else {
                        migrationResult.innerHTML = '<span class="text-red-600">❌ ' + (result.data.message || 'Migration failed') + '</span>';
                    }
                } catch (error) {
                    migrationResult.innerHTML = '<span class="text-red-600">❌ Error: ' + error.message + '</span>';
                } finally {
                    migrateButton.disabled = false;
                    migrateButton.textContent = 'Add International Price Field to Database';
                }
            });
        }

        // Server Connection Test Functionality
        const testConnectionButton = document.getElementById('test-connection');
        const connectionTestResults = document.getElementById('connection-test-results');
        const testResultsContent = document.getElementById('test-results-content');

        if (testConnectionButton) {
            testConnectionButton.addEventListener('click', async function() {
                testConnectionButton.disabled = true;
                testConnectionButton.textContent = 'Testing...';
                connectionTestResults.classList.remove('hidden');
                testResultsContent.innerHTML = '<div class="flex items-center"><svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Testing connection...</div>';

                try {
                    const formData = new FormData();
                    formData.append('action', 'test_server_connection');
                    formData.append('nonce', '<?php echo wp_create_nonce("test_server_connection"); ?>');
                    
                    // Get form data
                    const serverForm = document.getElementById('server-config-form');
                    const formDataObj = new FormData(serverForm);
                    
                    // Append all form fields
                    for (let [key, value] of formDataObj.entries()) {
                        formData.append(key, value);
                    }

                    const response = await fetch(ajaxurl || '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        testResultsContent.innerHTML = `
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-green-800">Connection Successful!</h3>
                                        <div class="mt-2 text-sm text-green-700">
                                            <p>${result.data.message}</p>
                                            ${result.data.details ? '<pre class="mt-2 text-xs bg-green-100 p-2 rounded">' + JSON.stringify(result.data.details, null, 2) + '</pre>' : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    } else {
                        testResultsContent.innerHTML = `
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-red-800">Connection Failed</h3>
                                        <div class="mt-2 text-sm text-red-700">
                                            <p>${result.data.message || 'Unknown error occurred'}</p>
                                            ${result.data.details ? '<pre class="mt-2 text-xs bg-red-100 p-2 rounded">' + JSON.stringify(result.data.details, null, 2) + '</pre>' : ''}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                } catch (error) {
                    testResultsContent.innerHTML = `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">Connection Test Error</h3>
                                    <div class="mt-2 text-sm text-red-700">
                                        <p>Error: ${error.message}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                } finally {
                    testConnectionButton.disabled = false;
                    testConnectionButton.textContent = 'Test Connection';
                }
            });
        }
    });
</script>