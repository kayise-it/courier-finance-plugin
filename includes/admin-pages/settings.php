<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include user roles for strict access control
require_once plugin_dir_path(__FILE__) . '../user-roles.php';
// Seed helpers (JSON generation and seeding) - optional
$seed_json_path = plugin_dir_path(__FILE__) . '../seed/seed-json.php';
if (file_exists($seed_json_path)) {
    require_once $seed_json_path;
}
// SQL seed generator helper - optional
$seed_sql_path = plugin_dir_path(__FILE__) . '../seed/seed-sql.php';
if (file_exists($seed_sql_path)) {
    require_once $seed_sql_path;
}

// Check if current user has admin capabilities
$wpdb_global_was_set = true; // marker
global $wpdb;

// STRICT ACCESS CONTROL: Only specific administrators can access settings
if (!KIT_User_Roles::can_access_settings()) {
    wp_die('Access denied. This page is only available to authorized administrators (Thando, Mel, Patricia).');
}

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    // Handle wipe tables: clear all plugin data tables
    if ($_POST['action'] === 'wipe_tables' && isset($_POST['wipe_tables_nonce']) && wp_verify_nonce($_POST['wipe_tables_nonce'], 'wipe_tables')) {
        $wipe_tables_result = handle_wipe_tables();
    }
    
    // Handle setup seed: execute SQL from latestSQL.sql
    if ($_POST['action'] === 'seed_setup' && isset($_POST['setup_seed_nonce']) && wp_verify_nonce($_POST['setup_seed_nonce'], 'seed_setup')) {
        $setup_seed_result = handle_setup_seed_sql();
    }

    // Handle banking, company, and charges forms (all use same nonce)
    if (isset($_POST['settings_nonce']) && wp_verify_nonce($_POST['settings_nonce'], 'save_settings') && 
        isset($_POST['action']) && in_array($_POST['action'], ['save_banking', 'save_company', 'save_charges'])) {
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

    // Save Terms & Conditions
    if (isset($_POST['action']) && $_POST['action'] === 'save_terms' && wp_verify_nonce($_POST['settings_nonce'], 'save_settings')) {
        // Prefer list items if provided
        $built_html = '';
        if (!empty($_POST['terms_items']) && is_array($_POST['terms_items'])) {
            $items = array_map('sanitize_text_field', $_POST['terms_items']);
            $items = array_values(array_filter($items, function($v){ return strlen(trim((string)$v)) > 0; }));
            if (!empty($items)) {
                $html = '<ul class="terms-conditions">';
                foreach ($items as $it) {
                    $html .= '<li>' . esc_html($it) . '</li>';
                }
                $html .= '</ul>';
                $built_html = $html;
            }
        }

        // Fallback: accept pasted HTML (sanitized) if no list inputs were used
        if ($built_html === '') {
            $allowed_html_terms = wp_kses_allowed_html('post');
            $terms_raw = $_POST['terms_content'] ?? '';
            $built_html = wp_kses((string)$terms_raw, $allowed_html_terms);
        }

        update_option('kit_terms_conditions', $built_html);
        $message = 'Terms & Conditions saved successfully.';
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

// Auto-generate latestSQL.sql from Excel if missing (non-destructive)
try {
    $plugin_root_autogen = plugin_dir_path(__FILE__) . '../../';
    $latest_sql_path = $plugin_root_autogen . 'latestSQL.sql';
    $excel_path_autogen = plugin_dir_path(__FILE__) . '../../waybill_excel/Waybills_31-10-2025.xlsx';
    if (!file_exists($latest_sql_path)) {
        // Prefer DB export to avoid shell_exec dependency
        $export = kit_export_seed_sql_from_db();
        if (!$export['success'] && file_exists($excel_path_autogen) && function_exists('shell_exec')) {
            kit_generate_seed_sql_from_excel();
        }
    }
} catch (Exception $e) {
    // ignore
}

// Manual exporter: create newSQL.sql from current DB (drivers, customers, deliveries, waybills)
function kit_export_seed_sql_from_db(): array
{
    global $wpdb;
    $pluginRoot = plugin_dir_path(__FILE__) . '../../';
    $target = $pluginRoot . 'newSQL.sql';

    $drivers = $wpdb->get_results("SELECT name, is_active FROM {$wpdb->prefix}kit_drivers ORDER BY id ASC", ARRAY_A) ?: [];
    $customers = $wpdb->get_results("SELECT cust_id, name, surname, company_name, country_id FROM {$wpdb->prefix}kit_customers ORDER BY id ASC", ARRAY_A) ?: [];
    $deliveries = $wpdb->get_results("SELECT id, delivery_reference, direction_id, destination_city_id, dispatch_date, driver_id, status FROM {$wpdb->prefix}kit_deliveries ORDER BY id ASC", ARRAY_A) ?: [];
    $waybills = $wpdb->get_results("SELECT description, direction_id, city_id, delivery_id, customer_id, waybill_no, warehouse, product_invoice_number, product_invoice_amount, waybill_items_total, total_mass_kg, total_volume, mass_charge, volume_charge, charge_basis, miscellaneous, include_sad500, include_sadc, vat_include, tracking_number, status, created_at, last_updated_at FROM {$wpdb->prefix}kit_waybills ORDER BY id ASC", ARRAY_A) ?: [];

    $lines = [];
    $lines[] = 'START TRANSACTION';
    foreach ($drivers as $r) {
        $name = addslashes($r['name']);
        $active = (int)($r['is_active'] ?? 1);
        $lines[] = "INSERT INTO wp_kit_drivers (name, is_active) SELECT '{$name}', {$active} FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM wp_kit_drivers WHERE name = '{$name}')";
    }
    foreach ($customers as $r) {
        $cust_id = (int)$r['cust_id'];
        $name = addslashes($r['name']);
        $surname = addslashes($r['surname']);
        $company = addslashes($r['company_name']);
        $country = (int)$r['country_id'];
        $lines[] = "INSERT INTO wp_kit_customers (cust_id, name, surname, company_name, country_id) SELECT {$cust_id}, '{$name}', '{$surname}', '{$company}', {$country} FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM wp_kit_customers WHERE cust_id = {$cust_id})";
    }
    foreach ($deliveries as $r) {
        $ref = addslashes($r['delivery_reference']);
        $dir = (int)$r['direction_id'];
        $city = (int)$r['destination_city_id'];
        $date = addslashes($r['dispatch_date']);
        $driver_id = (int)$r['driver_id'];
        $status = addslashes($r['status']);
        $lines[] = "INSERT INTO wp_kit_deliveries (delivery_reference, direction_id, destination_city_id, dispatch_date, driver_id, status) SELECT '{$ref}', {$dir}, {$city}, '{$date}', (SELECT id FROM wp_kit_drivers d WHERE d.id = {$driver_id} LIMIT 1), '{$status}' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM wp_kit_deliveries WHERE delivery_reference = '{$ref}')";
    }
    foreach ($waybills as $r) {
        $cols = ['description','direction_id','city_id','delivery_id','customer_id','waybill_no','warehouse','product_invoice_number','product_invoice_amount','waybill_items_total','total_mass_kg','total_volume','mass_charge','volume_charge','charge_basis','miscellaneous','include_sad500','include_sadc','vat_include','tracking_number','status','created_at','last_updated_at'];
        $vals = [];
        $delivery_id = (int)($r['delivery_id'] ?? 0);
        foreach ($cols as $c) {
            if ($c === 'city_id') {
                // Handle city_id: get from waybill's city_id if set, otherwise from delivery's destination_city_id
                $city_id = isset($r['city_id']) && (int)$r['city_id'] > 0 ? (int)$r['city_id'] : 0;
                if ($city_id === 0 && $delivery_id > 0) {
                    // Get destination_city_id from delivery
                    $city_id = (int)$wpdb->get_var($wpdb->prepare("SELECT destination_city_id FROM {$wpdb->prefix}kit_deliveries WHERE id = %d", $delivery_id));
                }
                // Use hardcoded value (fallback to 9 if not found)
                $vals[] = $city_id > 0 ? (string)$city_id : '9';
                continue;
            }
            $v = $r[$c];
            if (is_null($v)) { $vals[] = 'NULL'; continue; }
            if (is_numeric($v) && !in_array($c, ['description','product_invoice_number','tracking_number','status','charge_basis','miscellaneous','created_at','last_updated_at'])) {
                $vals[] = (string)$v;
            } else {
                $vals[] = "'" . addslashes((string)$v) . "'";
            }
        }
        $lines[] = 'INSERT INTO wp_kit_waybills (' . implode(',', $cols) . ') SELECT ' . implode(',', $vals) . ' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM wp_kit_waybills WHERE waybill_no = ' . (int)$r['waybill_no'] . ')';
    }
    $lines[] = 'COMMIT';

    $ok = @file_put_contents($target, implode(";\n\n", $lines) . ";\n");
    if ($ok === false) {
        return ['success' => false, 'message' => 'Failed to write newSQL.sql'];
    }
    return ['success' => true, 'message' => 'newSQL.sql exported from DB', 'path' => $target];
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
        $warehouse_waybills_deleted = 0;
        if (!empty($customer_ids_str)) {
            $warehouse_waybills_deleted = $wpdb->query("DELETE FROM $waybills_table WHERE customer_id IN ($customer_ids_str) AND status IN ('pending', 'assigned', 'shipped', 'delivered')");
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
                'message' => "Successfully unseeded customers! Deleted: $customers_deleted customers, $waybills_deleted waybills, $waybill_items_deleted waybill items, $warehouse_waybills_deleted warehouse waybills."
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

// Function to handle waybill import
function handle_waybill_import()
{
    // Include the import script
    $import_file = plugin_dir_path(__FILE__) . '../../import_excel_waybills.php';
    error_log("Attempting to load import script from: $import_file");
    
    if (!file_exists($import_file)) {
        error_log("Import script file not found: $import_file");
        return [
            'success' => false,
            'message' => 'Import script file not found.'
        ];
    }
    
    require_once $import_file;
    error_log("Import script loaded successfully");
    
    // Check if file exists
    $excel_file = plugin_dir_path(__FILE__) . '../../waybill_excel/Waybills_31-10-2025.xlsx';
    error_log("Checking for Excel file at: $excel_file");
    
    if (!file_exists($excel_file)) {
        error_log("Excel file not found: $excel_file");
        return [
            'success' => false,
            'message' => 'Excel file not found. Please ensure the file exists in the waybill_excel folder.'
        ];
    }
    
    error_log("Excel file found, starting import...");
    
    try {
        // Run the import
        $importer = new Excel_Waybill_Importer($excel_file);
        $result = $importer->import();
        
        error_log("Import completed with success: " . ($result['success'] ? 'true' : 'false'));
        
        if ($result['success']) {
            return [
                'success' => true,
                'message' => 'Waybill import completed successfully!',
                'stats' => $result['stats']
            ];
        } else {
            return [
                'success' => false,
                'message' => $result['message'],
                'stats' => $result['stats']
            ];
        }
    } catch (Exception $e) {
        error_log("Import exception: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Import failed: ' . $e->getMessage()
        ];
    } catch (Error $e) {
        error_log("Import fatal error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Import fatal error: ' . $e->getMessage()
        ];
    }
}

// Wipe all plugin data tables (but preserve settings and reference data)
function handle_wipe_tables()
{
    global $wpdb;
    
    try {
        // Drop foreign keys first to avoid TRUNCATE/DELETE issues
        require_once(__DIR__ . '/../class-database.php');
        Database::drop_legacy_foreign_keys();
        
        // Disable foreign key checks temporarily
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 0');
        
        // Tables to wipe (data tables that are seeded)
        // Order matters: wipe child tables first, then parent tables
        $tables_to_wipe = [
            'kit_waybill_items',  // Child table (references waybills)
            'kit_quotations',      // May reference waybills
            'kit_invoices',        // May reference waybills
            'kit_deliveries',      // May reference waybills
            'kit_waybills',        // Parent table
            'kit_customers',       // Parent table (referenced by waybills)
            //'kit_drivers',
        ];
        
        $wiped_count = 0;
        $errors = [];
        
        foreach ($tables_to_wipe as $table_name) {
            $full_table_name = $wpdb->prefix . $table_name;
            
            // Check if table exists
            $table_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
                DB_NAME,
                $full_table_name
            ));
            
            if ($table_exists > 0) {
                // Use DELETE instead of TRUNCATE (works better with foreign keys disabled)
                // DELETE works even when foreign keys exist (with checks disabled)
                $result = $wpdb->query("DELETE FROM `{$full_table_name}`");
                
                if ($result === false) {
                    $errors[] = "Failed to wipe {$table_name}: " . ($wpdb->last_error ?: 'Unknown error');
                } else {
                    // Reset auto-increment counter
                    $wpdb->query("ALTER TABLE `{$full_table_name}` AUTO_INCREMENT = 1");
                    $wiped_count++;
                }
            }
        }
        
        // Re-enable foreign key checks
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
        
        // Reset seeding flags
        delete_option('kit_customers_seeded');
        
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Wiped ' . $wiped_count . ' tables, but encountered errors: ' . implode('; ', $errors),
                'stats' => ['wiped' => $wiped_count, 'errors' => $errors]
            ];
        }
        
        return [
            'success' => true,
            'message' => 'Successfully wiped ' . $wiped_count . ' data tables. You can now run Setup Seed.',
            'stats' => ['wiped' => $wiped_count]
        ];
    } catch (Exception $e) {
        // Re-enable foreign key checks even on error
        $wpdb->query('SET FOREIGN_KEY_CHECKS = 1');
        return [
            'success' => false,
            'message' => 'Wipe tables failed: ' . $e->getMessage()
        ];
    }
}

// Execute SQL from latestSQL.sql with dynamic table prefix replacement
function handle_setup_seed_sql()
{
    global $wpdb;

    $pluginRoot = plugin_dir_path(__FILE__) . '../../';
    $latest_sql_file = $pluginRoot . 'latestSQL.sql';

    try {
        // Check for files that actually exist
        $sql_file = null;
        if (file_exists($latest_sql_file)) {
            $sql_file = $latest_sql_file;
        } else {
            // Try to generate from Excel automatically as last resort
            $generated = kit_generate_seed_sql_from_excel();
            if ($generated['success'] && file_exists($generated['path'])) {
                $sql_file = $generated['path'];
            } else {
                return [
                    'success' => false,
                    'message' => 'Seed SQL not found. Please ensure latestSQL.sql exists in the plugin root.'
                ];
            }
        }

        // Log which SQL file is being used
        if (function_exists('error_log')) {
            @error_log('[SetupSeed] Using SQL file: ' . $sql_file);
        }
        $sql_content = file_get_contents($sql_file);
        if ($sql_content === false) {
            return [
                'success' => false,
                'message' => 'Failed to read seed SQL file.'
            ];
        }

        // Helper closure to normalise SQL content (BOM, newlines, prefixes, placeholders)
        $normalise_sql = function (string $sql) use ($wpdb) {
        // Strip UTF-8 BOM if present to avoid MySQL "﻿ START" syntax errors
            if (strncmp($sql, "\xEF\xBB\xBF", 3) === 0) {
                $sql = substr($sql, 3);
        }

        // Normalise newlines to LF to keep statement parsing consistent
            $sql = str_replace(["\r\n", "\r"], "\n", $sql);

        // Replace hardcoded wp_ prefixes with dynamic prefix if needed
        // First replace {PREFIX} placeholder (new format)
            $sql = str_replace('{PREFIX}', $wpdb->prefix, $sql);
        // Then handle legacy wp_ prefixes (backward compatibility)
            $sql = preg_replace('/`wp_([a-zA-Z_]+)`/', '`' . $wpdb->prefix . '$1`', $sql);
            $sql = preg_replace('/(?<![a-zA-Z0-9_])wp_([a-zA-Z_]+)/', $wpdb->prefix . '$1', $sql);
        
        // Determine created_by user_id based on prefix
        // If prefix is NOT 'wp_', we're in production/live -> use user_id 1593
        // If prefix IS 'wp_', we're in development -> use Thando@kayiseit.com user_id
        $created_by_user_id = 1593; // Default for production/live (non-wp_ prefix)
        
        if ($wpdb->prefix === 'wp_') {
            // Development environment - get user_id for Thando@kayiseit.com
            $thando_user = get_user_by('email', 'Thando@kayiseit.com');
            if ($thando_user) {
                $created_by_user_id = $thando_user->ID;
            } else {
                // Fallback to 1 if user not found
                $created_by_user_id = 1;
            }
        }
        
        // Replace {CREATED_BY} placeholder with the determined user_id
            $sql = str_replace('{CREATED_BY}', $created_by_user_id, $sql);

            return $sql;
        };

        // First pass normalisation on the chosen SQL file
        $sql_content = $normalise_sql($sql_content);

        // Fix triple-escaped apostrophes: \\\' should be '' (MySQL's apostrophe escape)
        // In the SQL file, \\\' is literal: backslash-backslash-backslash-apostrophe
        // When MySQL parses this: \\ = backslash, \' = apostrophe, which ends string early
        // We need to convert \\\' to '' (two single quotes = SQL's way to escape apostrophe)
        // Pattern: match backslash-backslash-backslash-apostrophe, replace with two apostrophes
        $sql_content = str_replace("\\\\\\'", "''", $sql_content);

        // Some legacy exports store the "miscellaneous" field as a PHP-serialised
        // array starting with a:3:{s:10:"misc_items"; ...}. These blobs contain
        // many semicolons and nested metadata that are not required for seeding,
        // and they tend to confuse statement splitting and MySQL parsing.
        // For import purposes we can safely collapse them to a simple JSON stub.
        $sql_content = preg_replace(
            "/'a:3:\\{s:10:\\\\\"misc_items\\\\\";.*?}'/s",
            '\'{"misc_items":[],"misc_total":"0.00","others":{}}\'',
            $sql_content
        );

        // If the chosen seed file doesn't actually contain any data-modifying
        // statements (e.g. it's just START TRANSACTION/COMMIT), fall back to
        // generating a richer seed file and use that instead.
        if (!preg_match('/\b(INSERT|CREATE|UPDATE|DELETE)\b/i', $sql_content)) {
            $regen_errors = [];

            // 1) Prefer exporting from the current database if possible
            if (function_exists('kit_export_seed_sql_from_db')) {
                $export = kit_export_seed_sql_from_db();
                if (!empty($export['success']) && !empty($export['path']) && file_exists($export['path'])) {
                    $sql_file = $export['path'];
                    $sql_content = file_get_contents($sql_file);
                    if ($sql_content === false) {
                        $regen_errors[] = 'DB export file could not be read';
                    } else {
                        $sql_content = $normalise_sql($sql_content);
                    }
                } else {
                    $regen_errors[] = isset($export['message']) ? $export['message'] : 'DB export failed';
                }
            }

            // 2) If DB export didn't yield usable SQL, try Excel-based generator
            if (!preg_match('/\b(INSERT|CREATE|UPDATE|DELETE)\b/i', $sql_content ?? '') && function_exists('kit_generate_seed_sql_from_excel')) {
                $generated = kit_generate_seed_sql_from_excel();
                if (!empty($generated['success']) && !empty($generated['path']) && file_exists($generated['path'])) {
                    $sql_file = $generated['path'];
                    $sql_content = file_get_contents($sql_file);
                    if ($sql_content === false) {
                        $regen_errors[] = 'Excel-generated seed SQL file could not be read';
                    } else {
                        $sql_content = $normalise_sql($sql_content);
                    }
                } else {
                    $regen_errors[] = isset($generated['message']) ? $generated['message'] : 'Excel generator failed';
                }
            }

            // 3) After regeneration attempts, if we STILL don't have data SQL, abort with a clear message
            if (!preg_match('/\b(INSERT|CREATE|UPDATE|DELETE)\b/i', $sql_content ?? '')) {
                $reason = !empty($regen_errors) ? implode('; ', $regen_errors) : 'no regeneration strategies available';
                return [
                    'success' => false,
                    'message' => 'Seed SQL file contains no data rows and automatic regeneration failed: ' . $reason,
                ];
            }
        }

        // Split and execute statements - handle quoted strings properly
        // CRITICAL: Split on semicolons, but respect string literals
        $statements = [];
        $current = '';
        $in_string = false;
        $string_char = '';
        $len = strlen($sql_content);
        
        for ($i = 0; $i < $len; $i++) {
            $char = $sql_content[$i];
            $next_char = ($i < $len - 1) ? $sql_content[$i + 1] : '';
            $current .= $char;
            
            // Handle SQL string literals with proper escaping
            if ($in_string) {
                // CRITICAL: Handle escape sequences FIRST, before checking for end of string
                if ($char === '\\' && $i + 1 < $len) {
                    // Escaped character (backslash + any char) - consume both
                    $i++;
                    $current .= $sql_content[$i];
                    continue;
                }
                
                // Check for SQL-style escape: '' (two single quotes) for single-quoted strings
                if ($char === "'" && $string_char === "'" && $next_char === "'") {
                    // Double single quote escape in SQL - skip next char
                    $i++;
                    $current .= $next_char;
                    continue;
                }
                
                // Check if we're at the end of the string
                if ($char === $string_char) {
                    // End of string
                    $in_string = false;
                    $string_char = '';
                }
            } elseif ($char === "'" || $char === '"') {
                // Start of string
                $in_string = true;
                $string_char = $char;
            } elseif ($char === ';') {
                $stmt = trim($current);
                $current = ''; // Reset BEFORE processing to avoid accumulation
                
                // Only add non-empty statements that aren't just a semicolon
                if ($stmt !== '' && $stmt !== ';' && strlen($stmt) > 1) {
                    // Skip comment-only statements - remove all comments first
                    $stmt_clean = preg_replace('/--.*$/m', '', $stmt);
                    $stmt_clean = preg_replace('/\/\*.*?\*\//s', '', $stmt_clean);
                    $stmt_clean = trim($stmt_clean);
                    
                    // Only add if there's actual SQL content (not just comments/whitespace)
                    if ($stmt_clean !== '' && !preg_match('/^\s*--/', $stmt_clean) && !preg_match('/^[\s\n\r]*$/', $stmt_clean)) {
                        // Additional check: ensure it contains actual SQL keywords
                        $has_sql_keywords = preg_match('/\b(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|SET|USE|SHOW|DESCRIBE|EXPLAIN)\b/i', $stmt_clean);
                        if ($has_sql_keywords) {
                            // Single statement - ensure it ends with semicolon.
                            // IMPORTANT: Do not attempt to split on semicolons here:
                            // values like the serialized "miscellaneous" field legitimately
                            // contain semicolons inside quoted strings.
                            $final_stmt = rtrim($stmt, ';') . ';';
                            $statements[] = $final_stmt;
                            // #region agent log
                            $log_file = plugin_dir_path(__FILE__) . '../../.cursor/debug.log';
                            $log_entry = json_encode([
                                'timestamp' => microtime(true),
                                'location' => 'settings.php:split_statements',
                                'message' => 'Statement split',
                                'data' => [
                                    'stmt_length' => strlen($final_stmt),
                                    'stmt_preview' => substr($final_stmt, 0, 150),
                                    'starts_with' => substr(trim($final_stmt), 0, 30),
                                    'in_string_at_split' => $in_string,
                                    'string_char' => $string_char
                                ],
                                'sessionId' => 'debug-session',
                                'runId' => 'run1'
                            ]) . "\n";
                            @file_put_contents($log_file, $log_entry, FILE_APPEND);
                            // #endregion
                        }
                    }
                }
            }
        }
        
        // Add remaining statement if any (after trimming comments)
        if (trim($current) !== '') {
            $stmt = trim($current);
            $stmt_clean = preg_replace('/--.*$/m', '', $stmt);
            $stmt_clean = preg_replace('/\/\*.*?\*\//s', '', $stmt_clean);
            $stmt_clean = trim($stmt_clean);
            // Only add if there's actual SQL content with keywords
            if ($stmt_clean !== '' && !preg_match('/^\s*--/', $stmt_clean) && !preg_match('/^[\s\n\r]*$/', $stmt_clean)) {
                $has_sql_keywords = preg_match('/\b(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|SET|USE|SHOW|DESCRIBE|EXPLAIN)\b/i', $stmt_clean);
                if ($has_sql_keywords) {
                    $statements[] = rtrim($stmt, ';') . ';';
                }
            }
        }
        
        $executed = 0; $errors = [];
        // #region agent log
        $log_file = plugin_dir_path(__FILE__) . '../../.cursor/debug.log';
        $log_entry = function($msg, $data = []) use ($log_file) {
            $entry = json_encode([
                'timestamp' => microtime(true),
                'location' => 'settings.php:handle_setup_seed_sql',
                'message' => $msg,
                'data' => $data,
                'sessionId' => 'debug-session',
                'runId' => 'run1'
            ]) . "\n";
            @file_put_contents($log_file, $entry, FILE_APPEND);
        };
        $log_entry('Starting statement execution', ['total_statements' => count($statements)]);
        // #endregion
        foreach ($statements as $idx => $statement) {
            if ($statement === '') { continue; }
            
            // #region agent log
            $original_statement = $statement;
            // #endregion
            
            // Remove SQL comments (both -- style and /* */ style) before executing
            // This prevents statements with leading comments from being skipped
            $statement = preg_replace('/--.*$/m', '', $statement); // Remove -- comments
            $statement = preg_replace('/\/\*.*?\*\//s', '', $statement); // Remove /* */ comments
            $statement = trim($statement);
            
            if ($statement === '' || strpos($statement, '--') === 0) { continue; }
            
            // Safety: Occasionally, parsing errors can leave us with a fragment that
            // starts with trailing junk (e.g. "}}',0,0,1,...") followed by a valid
            // INSERT statement. If the first token isn't a recognised SQL verb,
            // but we can find a later "INSERT INTO", trim everything before it.
            $was_trimmed = false;
            if (!preg_match('/^(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|SET|USE)\b/i', $statement)) {
                $insert_pos = stripos($statement, 'INSERT INTO ');
                if ($insert_pos !== false) {
                    // #region agent log
                    $log_entry('Trimming statement fragment', [
                        'idx' => $idx,
                        'before_length' => strlen($statement),
                        'fragment' => substr($statement, 0, min(100, $insert_pos)),
                        'insert_pos' => $insert_pos
                    ]);
                    // #endregion
                    $statement = substr($statement, $insert_pos);
                    $statement = ltrim($statement);
                    $was_trimmed = true;
                }
            }

            // If after cleaning the statement still doesn't start with a known SQL
            // verb, skip it to avoid feeding garbage to MySQL.
            if (!preg_match('/^(SELECT|INSERT|UPDATE|DELETE|CREATE|ALTER|DROP|SET|USE)\b/i', $statement)) {
                // #region agent log
                $log_entry('Skipping invalid statement', [
                    'idx' => $idx,
                    'preview' => substr($statement, 0, 150),
                    'was_trimmed' => $was_trimmed
                ]);
                // #endregion
                continue;
            }
            
            // Additional safety for INSERTs: ensure we actually have a VALUES or
            // SELECT clause following the column list. If the statement was
            // truncated (e.g., we only have "INSERT INTO ... (cols)" with no body)
            // then executing it will always be a syntax error, so we skip.
            if (preg_match('/^INSERT\s+INTO\b/i', $statement)) {
                if (!preg_match('/^INSERT\s+INTO\b.+\)\s+(VALUES|SELECT)\b/si', $statement)) {
                    // #region agent log
                    $log_entry('Skipping incomplete INSERT', [
                        'idx' => $idx,
                        'preview' => substr($statement, 0, 200),
                        'has_values' => (bool)preg_match('/\)\s+VALUES\b/si', $statement),
                        'has_select' => (bool)preg_match('/\)\s+SELECT\b/si', $statement)
                    ]);
                    // #endregion
                    continue;
                }
            }
            
            // Single statement - ensure it ends with semicolon
            $statement = rtrim($statement, ';') . ';';
            
            // Remove any newlines that might cause MySQL to see multiple statements
            // Replace newlines with spaces to ensure it's treated as a single line
            $statement = str_replace(["\r\n", "\r", "\n"], ' ', $statement);
            // Clean up multiple spaces
            $statement = preg_replace('/\s+/', ' ', $statement);
            $statement = trim($statement);
            
            // Execute statement - $wpdb->query() can only handle ONE statement
            $result = $wpdb->query($statement);
            if ($result === false) {
                $err = $wpdb->last_error ?: 'Unknown DB error';
                $errors[] = $err;
                // #region agent log
                $log_entry('SQL execution failed', [
                    'idx' => $idx,
                    'error' => $err,
                    'statement_preview' => substr($statement, 0, 500),
                    'statement_length' => strlen($statement),
                    'was_trimmed' => $was_trimmed,
                    'original_preview' => substr($original_statement, 0, 200)
                ]);
                // #endregion
                if (function_exists('error_log')) {
                    @error_log('[SetupSeed] DB Error: ' . $err . ' | SQL: ' . substr($statement, 0, 300));
                }
            } else {
                $executed++;
            }
        }
        // #region agent log
        $log_entry('Statement execution completed', [
            'executed' => $executed,
            'errors_count' => count($errors)
        ]);
        // #endregion

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Seeding completed with errors. See first error below.',
                'stats' => [ 'executed' => $executed, 'errors' => $errors ]
            ];
        }

        $used_file = basename($sql_file);
        return [
            'success' => true,
            'message' => 'Setup seed executed successfully using ' . $used_file . '.',
            'stats' => [ 'executed' => $executed ]
        ];
    } catch (Exception $e) {
        return [ 'success' => false, 'message' => 'Setup seed failed: ' . $e->getMessage() ];
    }
}
// Generate newSQL.sql from Excel file (waybill_excel/*.xlsx)
function kit_generate_seed_sql_from_excel(): array
{
    $excel_file = plugin_dir_path(__FILE__) . '../../waybill_excel/Waybills_31-10-2025.xlsx';
    $pluginRoot = plugin_dir_path(__FILE__) . '../../';
    $target_sql = $pluginRoot . 'newSQL.sql';

    if (!file_exists($excel_file)) {
        return ['success' => false, 'message' => 'Excel file not found', 'path' => null];
    }

    $python = "\nimport pandas as pd\nimport json\nimport sys\n\ntry:\n    df = pd.read_excel('" . $excel_file . "', sheet_name='waybills')\n    for col in df.columns:\n        if str(df[col].dtype).startswith('datetime'):\n            df[col] = df[col].astype(str)\n    df = df.fillna('')\n    print(json.dumps(df.to_dict('records')))\nexcept Exception as e:\n    print(json.dumps({'error': str(e)}))\n    sys.exit(0)\n";

    if (!function_exists('shell_exec')) {
        return ['success' => false, 'message' => 'shell_exec disabled; cannot read Excel', 'path' => null];
    }
    $tmp = tempnam(sys_get_temp_dir(), 'seedgen_');
    @file_put_contents($tmp, $python);
    $json = @shell_exec('python3 ' . escapeshellarg($tmp) . ' 2>/dev/null');
    @unlink($tmp);
    if (!$json) {
        return ['success' => false, 'message' => 'Python failed to read Excel', 'path' => null];
    }
    $rows = json_decode($json, true);
    if (!is_array($rows) || isset($rows['error'])) {
        return ['success' => false, 'message' => 'Excel parse error', 'path' => null];
    }

    $drivers = [];
    $deliveries = [];
    $customers = [];
    $waybillRows = [];
    foreach ($rows as $r) {
        $driver = trim((string)($r['Driver'] ?? ''));
        $date = trim((string)($r['Truck Dispatch Date'] ?? ''));
        if ($date === '' || strtolower($date) === 'nat' || strtolower($date) === 'nan' || $date === '0000-00-00') {
            $date = date('Y-m-d');
        }
        $customer = trim((string)($r['Customer'] ?? ''));
        if ($driver === '' || $date === '' || $customer === '') { continue; }
        $drivers[$driver] = true;
        $deliveries[$driver . '|' . $date] = ['driver' => $driver, 'date' => $date];
        $customers[$customer] = true;
        $waybillRows[] = $r;
    }

    $sql = [];
    $sql[] = 'START TRANSACTION';
    foreach (array_keys($drivers) as $name) {
        $esc = addslashes($name);
        $sql[] = "INSERT INTO wp_kit_drivers (name, is_active) \nSELECT '{$esc}', 1 FROM DUAL \nWHERE NOT EXISTS (SELECT 1 FROM wp_kit_drivers WHERE name = '{$esc}')";
    }
    foreach ($deliveries as $d) {
        $escName = addslashes($d['driver']);
        $escDate = addslashes($d['date']);
        $sql[] = "INSERT INTO wp_kit_deliveries (delivery_reference, direction_id, destination_city_id, dispatch_date, driver_id, status)\nSELECT CONCAT('IMPORT-', LEFT(UUID(),8)), 1, 1, '{$escDate}', d.id, 'scheduled'\nFROM wp_kit_drivers d\nWHERE d.name = '{$escName}'\nAND NOT EXISTS (SELECT 1 FROM wp_kit_deliveries WHERE driver_id = d.id AND dispatch_date = '{$escDate}')";
    }
    foreach (array_keys($customers) as $fullname) {
        $parts = explode(' ', trim($fullname), 2);
        $first = addslashes($parts[0] ?? '');
        $last = addslashes($parts[1] ?? 'LastName');
        $custId = rand(1000, 9999);
        $sql[] = "INSERT INTO wp_kit_customers (cust_id, name, surname, company_name, country_id) \nSELECT {$custId}, '{$first}', '{$last}', 'Individual', 0 FROM DUAL \nWHERE NOT EXISTS (SELECT 1 FROM wp_kit_customers WHERE name = '{$first}' AND surname = '{$last}')";
    }
    foreach ($waybillRows as $row) {
        // --- Extract fields with defensive fallbacks ---
        $driver = addslashes(trim((string)($row['Driver'] ?? '')));
        $dateVal = trim((string)($row['Truck Dispatch Date'] ?? ''));
        if ($dateVal === '' || strtolower($dateVal) === 'nat' || strtolower($dateVal) === 'nan' || $dateVal === '0000-00-00') {
            $dateVal = date('Y-m-d');
        }
        $date = addslashes($dateVal);

        $customer = trim((string)($row['Customer'] ?? ''));
        $parts = explode(' ', $customer, 2);
        $first = addslashes($parts[0] ?? '');
        $last  = addslashes($parts[1] ?? 'LastName');

        // Quantities / totals
        $qty       = (int)($row['QUANTITY'] ?? 0);
        $mass      = (float)($row['T MASS'] ?? 0);
        $vol       = (float)($row['T VOLUME'] ?? 0);
        $length    = (float)($row['LENGTH'] ?? 0);
        $width     = (float)($row['WIDTH'] ?? 0);
        $height    = (float)($row['HEIGHT'] ?? 0);
        $basis     = trim((string)($row['BASIS'] ?? ''));

        $massCost  = addslashes(preg_replace('/[^0-9\.]/','', (string)($row['MASS COST'] ?? '0')));
        $volCost   = addslashes(preg_replace('/[^0-9\.]/','', (string)($row['VOL COST'] ?? '0')));
        $totalCost = (float)max((float)$massCost, (float)$volCost);

        // Waybill description from Excel column exactly as provided
        $waybill_description = addslashes(trim((string)($row['Waybill  description'] ?? $row['Waybill description'] ?? $row['Waybill_description'] ?? '')));

        // Get city_id from CITY column - look up city name in operating_cities table
        $city_name = trim((string)($row['CITY'] ?? ''));
        $city_id = 9; // Default fallback
        if ($city_name !== '') {
            global $wpdb;
            $city_id_result = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}kit_operating_cities WHERE city_name = %s LIMIT 1",
                $city_name
            ));
            if ($city_id_result) {
                $city_id = (int)$city_id_result;
            }
        }

        // VAT / SADC / SAD500 mapping from Excel (tolerant to headers and values)
        $vatRaw = strtoupper(trim((string)($row['VAT'] ?? $row['Vat'] ?? $row['vat_include'] ?? '')));
        $vatFlag = in_array($vatRaw, ['TRUE','1','YES','SAD500','SADC'], true) ? 1 : 0;
        $include_sad500 = ($vatRaw === 'SAD500') ? 1 : 0;
        $include_sadc   = ($vatRaw === 'SADC')   ? 1 : 0;

        // Item fields (small boxes) — must use "Item description" (NOT waybill description)
        $itemDesc = trim((string)($row['Item description'] ?? $row['ITEM DESCRIPTION'] ?? $row['Item_Description'] ?? ''));
        $itemDescSql = $itemDesc !== '' ? ("'" . addslashes($itemDesc) . "'") : "NULL";
        $itemQty  = (int)($row['QUANTITY'] ?? 0);

        // Client invoice (W) and total price (Y) — tolerant to header variants
        $clientInv = trim((string)($row['CL INV #'] ?? $row['CLIENT INVOICE'] ?? $row['Client invoice'] ?? $row['Client_Invoice'] ?? ''));
        $clientInvSql = $clientInv !== '' ? ("'" . addslashes($clientInv) . "'") : "NULL";
        $totalPriceRaw = trim((string)($row['TOTAL PRICE'] ?? $row['Total price'] ?? $row['Total_Price'] ?? $row['TOTAL'] ?? ''));
        $totalPrice = preg_replace('/[^0-9\.]/','', $totalPriceRaw);
        $totalPriceSql = ($totalPrice === '' ? 'NULL' : $totalPrice);

        // 1) Insert WAYBILL (no product_invoice_number; PHP generates it later if you use save_waybill route)
        // Use hardcoded city_id from CSV CITY column lookup
        $sql[] = "INSERT INTO wp_kit_waybills (description, direction_id, city_id, delivery_id, customer_id, waybill_no, warehouse, product_invoice_number, product_invoice_amount, waybill_items_total, total_mass_kg, total_volume, item_length, item_width, item_height, mass_charge, volume_charge, charge_basis, miscellaneous, include_sad500, include_sadc, vat_include, tracking_number, status, created_at, last_updated_at)\n"
               . "SELECT " . ($waybill_description ? "'{$waybill_description}'" : "NULL") . ", 1, {$city_id}, del.id, cust.cust_id, FLOOR(RAND()*900000)+100000, 0, NULL, {$totalCost}, {$totalCost}, {$mass}, {$vol}, {$length}, {$width}, {$height}, {$massCost}, {$volCost}, " . ($basis !== '' ? "'" . addslashes($basis) . "'" : "'mass'") . ", '', {$include_sad500}, {$include_sadc}, {$vatFlag}, CONCAT('TRK-', LEFT(UUID(),8)), 'pending', NOW(), NOW()\n"
               . "FROM wp_kit_deliveries del\n"
               . "JOIN wp_kit_drivers d ON d.id = del.driver_id AND d.name = '{$driver}' AND del.dispatch_date = '{$date}'\n"
               . "JOIN wp_kit_customers cust ON cust.name = '{$first}' AND cust.surname = '{$last}'\n"
               . "WHERE NOT EXISTS (SELECT 1 FROM wp_kit_waybills w JOIN wp_kit_customers c2 ON w.customer_id = c2.cust_id WHERE c2.name = '{$first}' AND c2.surname = '{$last}' AND w.delivery_id = del.id)";

        // 2) Insert WAYBILL ITEM using Item description (NOT the waybill description)
        //    We reference the waybill just inserted via the same join keys (customer+delivery) to get its waybill_no
        $sql[] = "INSERT INTO wp_kit_waybill_items (waybillno, item_name, quantity, unit_price, unit_mass, unit_volume, total_price, client_invoice)\n"
               . "SELECT w.waybill_no, {$itemDescSql}, " . ($itemQty > 0 ? $itemQty : 1) . ", 0.00, 0.00, 0.00, {$totalPriceSql}, {$clientInvSql}\n"
               . "FROM wp_kit_waybills w\n"
               . "JOIN wp_kit_deliveries del ON del.id = w.delivery_id\n"
               . "JOIN wp_kit_drivers d ON d.id = del.driver_id AND d.name = '{$driver}' AND del.dispatch_date = '{$date}'\n"
               . "JOIN wp_kit_customers cust ON cust.cust_id = w.customer_id AND cust.name = '{$first}' AND cust.surname = '{$last}'\n"
               . "WHERE NOT EXISTS (SELECT 1 FROM wp_kit_waybill_items i WHERE i.waybillno = w.waybill_no AND i.item_name = {$itemDescSql})";
    }
    $sql[] = 'COMMIT';
    $ok = @file_put_contents($target_sql, implode(";\n\n", $sql) . ";\n");
    if ($ok === false) {
        return ['success' => false, 'message' => 'Failed to write newSQL.sql', 'path' => null];
    }
    return ['success' => true, 'message' => 'Generated from Excel', 'path' => $target_sql];
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
            <?php echo KIT_Commons::renderButton('Setup Seed', 'ghost', 'sm', ['id' => 'tab-setup-seed', 'classes' => 'tab-button', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />', 'iconPosition' => 'left']); ?>
            <?php echo KIT_Commons::renderButton('Terms & Conditions', 'ghost', 'sm', ['id' => 'tab-terms', 'classes' => 'tab-button', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>', 'iconPosition' => 'left']); ?>
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
                            <?= KIT_Commons::renderButton('Save Company Details', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]) ?>
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
                                <p class="text-xs text-yellow-700 mb-3">Use these tools to add missing fields/tables safely without wiping data.</p>
                                <div class="flex flex-wrap gap-3">
                                    <?php echo KIT_Commons::renderButton('Add International Price Field', 'warning', 'lg', ['id' => 'migrate-db', 'type' => 'button', 'gradient' => true]); ?>
                                    <?php echo KIT_Commons::renderButton('Run Full DB Migration (Add missing columns/tables)', 'secondary', 'lg', ['id' => 'kit-run-migration', 'type' => 'button']); ?>
                                </div>
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

        <!-- Setup Seed Tab -->
        <div id="content-setup-seed" class="tab-panel hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Setup Seed</h2>
                    <p class="text-sm text-gray-600 mt-1">Seed drivers, customers, deliveries and waybills programmatically.</p>
                </div>

                <div class="p-6">
                    <?php
                    // Show seed SQL status - match the priority order in handle_setup_seed_sql()
                    $pluginRoot = plugin_dir_path(__FILE__) . '../../';
                    $latest_sql_file = $pluginRoot . 'latestSQL.sql';
                    
                    // Determine if the seed file exists (matching handle_setup_seed_sql priority)
                    $sql_file = null;
                    $file_display_name = '';
                    if (file_exists($latest_sql_file)) {
                        $sql_file = $latest_sql_file;
                        $file_display_name = 'latestSQL.sql';
                    }
                    
                    $file_exists = ($sql_file !== null);
                    ?>

                    <div class="mb-6 p-4 rounded-lg border <?php echo $file_exists ? 'bg-blue-50 border-blue-200' : 'bg-yellow-50 border-yellow-200'; ?>">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <?php if ($file_exists): ?>
                                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <?php else: ?>
                                    <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                                <?php endif; ?>
                            </div>
                            <div class="ml-3 text-sm">
                                <p class="font-medium <?php echo $file_exists ? 'text-blue-800' : 'text-yellow-800'; ?>"><?php echo $file_exists ? 'Seed SQL Found' : 'Seed SQL Not Found'; ?></p>
                                <?php if ($file_exists): ?>
                                    <p class="mt-1 text-blue-700">
                                        Seed file located at: <code class="px-2 py-1 rounded bg-gray-100"><?php echo esc_html($file_display_name); ?></code>
                                    </p>
                                <?php else: ?>
                                    <p class="mt-1 text-yellow-700">
                                        Please ensure <code class="px-2 py-1 rounded bg-gray-100">latestSQL.sql</code> exists in the plugin root directory
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="flex gap-3 items-center">
                        <form method="post" action="" class="inline">
                            <?php wp_nonce_field('seed_setup', 'setup_seed_nonce'); ?>
                            <input type="hidden" name="action" value="seed_setup">
                            <?php echo KIT_Commons::renderButton('Run Setup Seed', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]); ?>
                        </form>
                        
                        <form method="post" action="" id="wipe-tables-form" class="inline">
                            <?php wp_nonce_field('wipe_tables', 'wipe_tables_nonce'); ?>
                            <input type="hidden" name="action" value="wipe_tables">
                            <?php echo KIT_Commons::renderButton('Wipe Tables', 'danger', 'lg', ['type' => 'submit', 'id' => 'wipe-tables-button']); ?>
                        </form>
                    </div>

                    <?php if (isset($wipe_tables_result)): ?>
                        <div class="mt-6 p-4 rounded-lg border <?php echo $wipe_tables_result['success'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?>">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <?php if ($wipe_tables_result['success']): ?>
                                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <?php else: ?>
                                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-3 text-sm">
                                    <p class="font-medium <?php echo $wipe_tables_result['success'] ? 'text-green-800' : 'text-red-800'; ?>"><?php echo $wipe_tables_result['success'] ? 'Tables Wiped Successfully' : 'Wipe Tables Failed'; ?></p>
                                    <div class="mt-2 <?php echo $wipe_tables_result['success'] ? 'text-green-700' : 'text-red-700'; ?>">
                                        <p><?php echo esc_html($wipe_tables_result['message'] ?? ''); ?></p>
                                        <?php if (!empty($wipe_tables_result['stats'])): ?>
                                            <div class="mt-3 space-y-1">
                                                <p><strong>Statistics:</strong></p>
                                                <p>• Tables wiped: <?php echo intval($wipe_tables_result['stats']['wiped'] ?? 0); ?></p>
                                                <?php if (!empty($wipe_tables_result['stats']['errors'])): ?>
                                                    <p>• Errors: <?php echo count($wipe_tables_result['stats']['errors']); ?></p>
                                                    <div class="mt-2 text-xs">
                                                        <p><strong>Error details:</strong></p>
                                                        <?php foreach (array_slice($wipe_tables_result['stats']['errors'], 0, 5) as $error): ?>
                                                            <p class="text-red-600">• <?php echo esc_html($error); ?></p>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($setup_seed_result)): ?>
                        <div class="mt-6 p-4 rounded-lg border <?php echo $setup_seed_result['success'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'; ?>">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <?php if ($setup_seed_result['success']): ?>
                                        <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <?php else: ?>
                                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-3 text-sm">
                                    <p class="font-medium <?php echo $setup_seed_result['success'] ? 'text-green-800' : 'text-red-800'; ?>"><?php echo $setup_seed_result['success'] ? 'Setup Seed Successful' : 'Setup Seed Failed'; ?></p>
                                    <div class="mt-2 <?php echo $setup_seed_result['success'] ? 'text-green-700' : 'text-red-700'; ?>">
                                        <p><?php echo esc_html($setup_seed_result['message'] ?? ''); ?></p>
                                        <?php if (!empty($setup_seed_result['stats'])): ?>
                                            <div class="mt-3 space-y-1">
                                                <p><strong>Statistics:</strong></p>
                                                <p>• Statements executed: <?php echo intval($setup_seed_result['stats']['executed'] ?? 0); ?></p>
                                                <?php if (!empty($setup_seed_result['stats']['errors'])): ?>
                                                    <p>• Errors: <?php echo count($setup_seed_result['stats']['errors']); ?></p>
                                                    <div class="mt-2 text-xs">
                                                        <p><strong>Error details:</strong></p>
                                                        <?php foreach (array_slice($setup_seed_result['stats']['errors'], 0, 5) as $error): ?>
                                                            <p class="text-red-600">• <?php echo esc_html($error); ?></p>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Import Tab removed -->

        <!-- Terms & Conditions Tab -->
        <div id="content-terms" class="tab-panel hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Terms & Conditions</h2>
                    <p class="text-sm text-gray-600 mt-1">Manage the Terms & Conditions shown to customers.</p>
                </div>

                <div class="p-6">
                    <form method="post" action="">
                        <?php wp_nonce_field('save_settings', 'settings_nonce'); ?>
                        <input type="hidden" name="action" value="save_terms">

                        <?php
                        $existing_terms_html = get_option('kit_terms_conditions', '');
                        $existing_items = [];
                        if (is_string($existing_terms_html) && preg_match_all('/<li[^>]*>(.*?)<\/li>/si', $existing_terms_html, $m)) {
                            foreach ($m[1] as $seg) {
                                $existing_items[] = wp_strip_all_tags($seg);
                            }
                        }
                        if (empty($existing_items)) {
                            $existing_items = [''];
                        }
                        ?>

                                    <div class="form-group">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Terms & Conditions Items</label>
                            <div id="terms-list" class="space-y-2">
                                <?php foreach ($existing_items as $txt): ?>
                                    <div class="flex items-center gap-2">
                                        <input type="text" name="terms_items[]" value="<?php echo esc_attr($txt); ?>" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter a term item">
                                        <?php echo KIT_Commons::renderButton('Remove', 'secondary', 'lg', ['type' => 'button', 'classes' => 'remove-term-item px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100']); ?>
                                    </div>
                                <?php endforeach; ?>
                                    </div>
                            <div class="mt-3">
                                <?php echo KIT_Commons::renderButton('Add Item', 'secondary', 'lg', ['type' => 'button', 'id' => 'add-term-item', 'classes' => 'px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100']); ?>
                                    </div>
                            <p class="text-xs text-gray-500 mt-2">Items will be saved as a bullet list in PDFs. You can still paste full HTML below if needed.</p>
                                    </div>

                        <div class="form-group mt-4">
                            <label for="terms_content" class="block text-sm font-medium text-gray-700 mb-2">Or paste Terms HTML (optional)</label>
                            <textarea id="terms_content" name="terms_content" rows="6" class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="<ul><li>Example term</li></ul>"></textarea>
                            <p class="text-xs text-gray-500 mt-1">If provided, pasted HTML will be used when list items are empty.</p>
                                    </div>

                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <?php echo KIT_Commons::renderButton('Save Terms & Conditions', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]); ?>
                        </div>
                    </form>
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
        const runMigrationButton = document.getElementById('kit-run-migration');
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

        if (runMigrationButton) {
            runMigrationButton.addEventListener('click', async function() {
                runMigrationButton.disabled = true;
                runMigrationButton.textContent = 'Running Migration...';
                migrationResult.innerHTML = '<span class="text-blue-600">⏳ Running database migration (adding missing tables/columns)...</span>';

                try {
                    const formData = new FormData();
                    formData.append('action', 'kit_migrate_schema');
                    formData.append('nonce', '<?php echo wp_create_nonce("kit_migrate_schema"); ?>');

                    const response = await fetch(ajaxurl || '/wp-admin/admin-ajax.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        migrationResult.innerHTML = '<span class="text-green-600">✅ ' + result.data.message + '</span>';
                        setTimeout(() => { location.reload(); }, 2000);
                    } else {
                        migrationResult.innerHTML = '<span class="text-red-600">❌ ' + (result.data.message || 'Migration failed') + '</span>';
                    }
                } catch (error) {
                    migrationResult.innerHTML = '<span class="text-red-600">❌ Error: ' + error.message + '</span>';
                } finally {
                    runMigrationButton.disabled = false;
                    runMigrationButton.textContent = 'Run Full DB Migration (Add missing columns/tables)';
                }
            });
        }

        // Removed: Server Connection Test Functionality (deprecated)

        // Terms items add/remove
        const addBtn = document.getElementById('add-term-item');
        const list = document.getElementById('terms-list');
        if (addBtn && list) {
            addBtn.addEventListener('click', () => {
                const row = document.createElement('div');
                row.className = 'flex items-center gap-2';
                row.innerHTML = '<input type="text" name="terms_items[]" value="" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter a term item">\n<button type="button" class="remove-term-item px-3 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">Remove</button>';
                list.appendChild(row);
            });
            list.addEventListener('click', (e) => {
                if (e.target && e.target.classList.contains('remove-term-item')) {
                    const row = e.target.closest('.flex');
                    if (row && list.children.length > 1) {
                        row.remove();
                    } else if (row) {
                        // Clear the last remaining input instead of removing
                        const input = row.querySelector('input[name="terms_items[]"]');
                        if (input) input.value = '';
                    }
                }
            });
        }

        // Wipe tables form confirmation
        const wipeTablesForm = document.getElementById('wipe-tables-form');
        const wipeTablesButton = document.getElementById('wipe-tables-button');

        if (wipeTablesForm && wipeTablesButton) {
            wipeTablesButton.addEventListener('click', function(e) {
                e.preventDefault();

                const confirmed = confirm(
                    '⚠️ WARNING: This will DELETE ALL DATA from the following tables:\n\n' +
                    '• Waybill Items\n' +
                    '• Quotations\n' +
                    '• Invoices\n' +
                    '• Waybills\n' +
                    '• Deliveries\n' +
                    '• Customers\n\n' +
                    'This action:\n' +
                    '• CANNOT be undone\n' +
                    '• Will permanently delete all data\n' +
                    '• Settings and reference data will be preserved\n\n' +
                    'Are you absolutely sure you want to continue?\n\n' +
                    'Click OK to wipe all tables or Cancel to abort.'
                );

                if (confirmed) {
                    // Double confirmation for safety
                    const doubleConfirmed = confirm(
                        'FINAL CONFIRMATION:\n\n' +
                        'You are about to PERMANENTLY DELETE all plugin data.\n\n' +
                        'This is your last chance to cancel.\n\n' +
                        'Click OK to proceed with wiping all tables.'
                    );

                    if (doubleConfirmed) {
                        // Show loading state
                        wipeTablesButton.disabled = true;
                        wipeTablesButton.textContent = 'Wiping Tables...';

                        // Submit the form programmatically
                        wipeTablesForm.submit();
                    }
                }
            });
        }

        // Waybill import form confirmation
        const importForm = document.getElementById('import-form');
        const importButton = document.getElementById('import-button');

        if (importForm && importButton) {
            importForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const confirmed = confirm(
                    'Are you sure you want to import waybills from Excel?\n\n' +
                    'This action:\n' +
                    '• Will create drivers, customers, deliveries, and waybills\n' +
                    '• Will skip duplicate waybill numbers\n' +
                    '• May take several minutes depending on file size\n\n' +
                    'Click OK to continue or Cancel to abort.'
                );

                if (confirmed) {
                    // Show loading state
                    importButton.disabled = true;
                    importButton.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Importing...';

                    // Submit the form
                    this.submit();
                }
            });
        }
    });
</script>