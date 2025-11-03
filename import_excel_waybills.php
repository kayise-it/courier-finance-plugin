<?php
/**
 * Waybill Excel Import Script
 * 
 * This script imports waybill data from an Excel file into the WordPress database.
 * It handles driver creation, customer creation, delivery creation, and waybill creation.
 * 
 * Usage: Run this file from the command line or via WordPress admin
 * 
 * Requirements:
 * - pandas, openpyxl Python libraries
 * - WordPress database must be properly configured
 * - Waybill Excel file in the waybill_excel folder
 */

// Load WordPress only if not already loaded
if (!defined('ABSPATH')) {
    if (file_exists(__DIR__ . '/../../../wp-load.php')) {
        require_once __DIR__ . '/../../../wp-load.php';
    } else {
        die('WordPress not found. Please ensure this script is in the plugin directory.');
    }
}

// Check if user is authorized (if running via web)
if (!defined('WP_CLI') && !current_user_can('manage_options')) {
    wp_die('Unauthorized');
}

// Include required plugin files
require_once __DIR__ . '/includes/class-database.php';
require_once __DIR__ . '/includes/customers/customers-functions.php';
require_once __DIR__ . '/includes/deliveries/deliveries-functions.php';
require_once __DIR__ . '/includes/routes/routes-functions.php';
require_once __DIR__ . '/includes/waybill/waybill-functions.php';

class Excel_Waybill_Importer {
    
    private $excel_file;
    private $wpdb;
    private $stats;
    
    public function __construct($excel_file) {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->excel_file = $excel_file;
        $this->stats = [
            'total_rows' => 0,
            'drivers_created' => 0,
            'customers_created' => 0,
            'deliveries_created' => 0,
            'waybills_created' => 0,
            'errors' => []
        ];
    }
    
    /**
     * Main import function
     */
    public function import() {
        // Read Excel file using Python
        $data = $this->read_excel_file();
        
        if (!$data) {
            return [
                'success' => false,
                'message' => 'Failed to read Excel file',
                'stats' => $this->stats
            ];
        }
        
        // Group rows by waybill number (driver + truck dispatch date combination)
        $grouped = $this->group_by_waybill($data);
        
        foreach ($grouped as $waybill_key => $rows) {
            try {
                // Process each waybill group
                $result = $this->process_waybill_group($rows);
                
                if ($result['success']) {
                    $this->stats['waybills_created']++;
                } else {
                    $error_msg = "Waybill group {$waybill_key}: " . $result['message'];
                    $this->stats['errors'][] = [
                        'waybill_key' => $waybill_key,
                        'error' => $result['message']
                    ];
                    error_log("Import error: $error_msg");
                }
            } catch (Exception $e) {
                $error_msg = "Waybill group {$waybill_key}: " . $e->getMessage();
                $this->stats['errors'][] = [
                    'waybill_key' => $waybill_key,
                    'error' => $e->getMessage()
                ];
                error_log("Import exception: $error_msg");
            }
        }
        
        return [
            'success' => true,
            'stats' => $this->stats
        ];
    }
    
    /**
     * Read Excel file using Python
     */
    private function read_excel_file() {
        $python_script = "
import pandas as pd
import json
import sys
from datetime import datetime

try:
    df = pd.read_excel('" . $this->excel_file . "', sheet_name='waybills')
    
    # Convert datetime objects to strings BEFORE fillna to preserve dtype info
    for col in df.columns:
        if df[col].dtype == 'datetime64[ns]':
            df[col] = df[col].astype(str)
    
    # Convert time objects to strings (found in Supplier column)
    for col in df.columns:
        if df[col].dtype == 'object':
            # Check if any values are time or datetime objects
            time_mask = df[col].apply(lambda x: hasattr(x, '__class__') and ('time' in str(type(x)) or 'datetime' in str(type(x))))
            if time_mask.any():
                df[col] = df[col].astype(str)
    
    df = df.fillna('')  # Replace NaN with empty strings
    
    data = df.to_dict('records')
    print(json.dumps(data))
except Exception as e:
    print(json.dumps({'error': str(e)}), file=sys.stderr)
    sys.exit(1)
";
        
        $temp_file = tempnam(sys_get_temp_dir(), 'excel_import_');
        file_put_contents($temp_file, $python_script);
        
        $command = "python3 " . escapeshellarg($temp_file) . " 2>&1";
        $output = shell_exec($command);
        
        unlink($temp_file);
        
        if (!$output) {
            return false;
        }
        
        $data = json_decode($output, true);
        
        if (isset($data['error'])) {
            error_log("Excel import error: " . $data['error']);
            return false;
        }
        
        $this->stats['total_rows'] = count($data);
        return $data;
    }
    
    /**
     * Group rows by waybill identifier (Driver + Truck Dispatch Date + unique identifier)
     */
    private function group_by_waybill($data) {
        $grouped = [];
        $skipped_invalid = 0;
        
        foreach ($data as $row) {
            // Skip rows with missing driver or dispatch date
            $driver = $row['Driver'] ?? '';
            $dispatch_date = $row['Truck Dispatch Date'] ?? '';
            
            if (empty($driver) || empty($dispatch_date)) {
                $skipped_invalid++;
                error_log("Skipping row with missing driver or dispatch date. Driver: '{$driver}', Date: '{$dispatch_date}'");
                continue;
            }
            
            // Group by driver and dispatch date
            $waybill_key = $driver . '_' . $dispatch_date;
            
            if (!isset($grouped[$waybill_key])) {
                $grouped[$waybill_key] = [];
            }
            
            $grouped[$waybill_key][] = $row;
        }
        
        if ($skipped_invalid > 0) {
            error_log("Skipped {$skipped_invalid} rows with invalid driver/dispatch date");
        }
        
        return $grouped;
    }
    
    /**
     * Process a group of rows that belong to the same waybill
     */
    private function process_waybill_group($rows) {
        // Get the first row for waybill-level data
        $first_row = $rows[0];
        
        // Step 1: Get or create driver
        $driver_id = $this->get_or_create_driver($first_row);
        if (!$driver_id) {
            return ['success' => false, 'message' => 'Failed to create driver'];
        }
        
        // Step 2: Get or create delivery
        $delivery_id = $this->get_or_create_delivery($first_row, $driver_id);
        if (!$delivery_id) {
            return ['success' => false, 'message' => 'Failed to create delivery'];
        }
        
        // Step 3: Process each row in the group (each row is an item)
        foreach ($rows as $row) {
            // Get or create customer for this row
            $customer_id = $this->get_or_create_customer($row);
            if (!$customer_id) {
                continue; // Skip this row if customer creation fails
            }
            
            // Extract client_invoice from row (needed for waybill_items)
            $client_invoice = sanitize_text_field($row['CL INV #'] ?? '');
            
            // Create waybill
            $waybill_result = $this->create_waybill($row, $delivery_id, $customer_id);
            
            if ($waybill_result['success']) {
                // Create waybill items
                $this->create_waybill_items($row, $waybill_result['waybill_no'], $client_invoice);
            }
        }
        
        return ['success' => true];
    }
    
    /**
     * Get or create a driver
     */
    private function get_or_create_driver($row) {
        $driver_name = sanitize_text_field($row['Driver'] ?? '');
        
        if (empty($driver_name)) {
            return false;
        }
        
        $drivers_table = $this->wpdb->prefix . 'kit_drivers';
        
        // Check if driver exists
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT id FROM $drivers_table WHERE name = %s",
            $driver_name
        ));
        
        if ($existing) {
            return $existing->id;
        }
        
        // Create new driver
        $result = $this->wpdb->insert($drivers_table, [
            'name' => $driver_name,
            'is_active' => 1
        ]);
        
        if ($result) {
            $this->stats['drivers_created']++;
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get or create a delivery
     */
    private function get_or_create_delivery($row, $driver_id) {
        $dispatch_date = $row['Truck Dispatch Date'] ?? '';
        $driver_name = sanitize_text_field($row['Driver'] ?? '');
        
        $deliveries_table = $this->wpdb->prefix . 'kit_deliveries';
        
        // Check if delivery exists for this driver and date
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT id FROM $deliveries_table WHERE driver_id = %d AND dispatch_date = %s",
            $driver_id,
            $dispatch_date
        ));
        
        if ($existing) {
            return $existing->id;
        }
        
        // Determine direction_id and destination_city_id based on Excel data
        // Default to warehouse delivery (direction_id = 1) if we can't determine
        $direction_id = 1;
        $destination_city_id = 1;
        
        // Try to get direction from countries if available in data
        // For now, using default warehouse delivery
        
        // Create new delivery
        $result = $this->wpdb->insert($deliveries_table, [
            'delivery_reference' => 'IMPORT-' . strtoupper(wp_generate_password(6, false, false)),
            'direction_id' => $direction_id,
            'destination_city_id' => $destination_city_id,
            'dispatch_date' => $dispatch_date,
            'driver_id' => $driver_id,
            'status' => 'scheduled',
            'created_by' => get_current_user_id() ?: 1
        ]);
        
        if ($result) {
            $this->stats['deliveries_created']++;
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get or create a customer
     */
    private function get_or_create_customer($row) {
        $customer_name = sanitize_text_field($row['Customer'] ?? '');
        $cell = sanitize_text_field($row['Cell'] ?? '');
        $company = sanitize_text_field($row['Company'] ?? '');
        $address = sanitize_text_field($row['Address'] ?? '');
        $city_name = sanitize_text_field($row['City'] ?? '');
        
        if (empty($customer_name)) {
            return false;
        }
        
        // Parse name into first name and surname
        $name_parts = explode(' ', trim($customer_name), 2);
        $first_name = $name_parts[0] ?? '';
        $surname = $name_parts[1] ?? 'LastName';
        
        $customers_table = $this->wpdb->prefix . 'kit_customers';
        
        // Check if customer exists
        $existing = $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT cust_id FROM $customers_table WHERE name = %s AND surname = %s",
            $first_name,
            $surname
        ));
        
        if ($existing) {
            return $existing->cust_id;
        }
        
        // Get city_id if city name is provided
        $city_id = null;
        $country_id = null;
        
        if (!empty($city_name)) {
            $city_result = $this->get_city_by_name($city_name);
            if ($city_result) {
                $city_id = $city_result->id;
                $country_id = $city_result->country_id;
            }
        }
        
        // Generate unique customer ID
        do {
            $cust_id = rand(1000, 9999);
            $exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT cust_id FROM $customers_table WHERE cust_id = %d",
                $cust_id
            ));
        } while ($exists);
        
        // Prepare company name
        if (empty($company)) {
            $company = 'Individual';
        }
        
        // Create new customer
        $cust_data = [
            'cust_id' => $cust_id,
            'name' => $first_name,
            'surname' => $surname,
            'cell' => $cell,
            'company_name' => $company,
            'address' => $address,
            'country_id' => $country_id ?? 0,
            'city_id' => $city_id,
        ];
        
        $result = $this->wpdb->insert($customers_table, $cust_data, [
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%d',
            ($city_id === null ? null : '%d')
        ]);
        
        if ($result) {
            $this->stats['customers_created']++;
            return $cust_id;
        }
        
        return false;
    }
    
    /**
     * Get city by name
     */
    private function get_city_by_name($city_name) {
        $cities_table = $this->wpdb->prefix . 'kit_operating_cities';
        
        return $this->wpdb->get_row($this->wpdb->prepare(
            "SELECT id, country_id FROM $cities_table WHERE city_name = %s LIMIT 1",
            $city_name
        ));
    }
    
    /**
     * Clean currency value - handles both numeric and formatted strings like "82 000.00"
     */
    private function clean_currency_value($value) {
        if (is_numeric($value)) {
            return floatval($value);
        }
        
        if (is_string($value)) {
            // Remove non-numeric characters except decimal point
            $cleaned = preg_replace('/[^0-9\.]/', '', $value);
            return floatval($cleaned);
        }
        
        return 0.0;
    }
    
    /**
     * Create a waybill
     */
    private function create_waybill($row, $delivery_id, $customer_id) {
        $waybills_table = $this->wpdb->prefix . 'kit_waybills';
        
        // Get existing waybill number if it exists in Excel, or generate one
        $existing_waybill_no = $this->extract_waybill_number($row);
        
        $waybill_no = null;
        
        // If we have an existing waybill number, check if it already exists
        if ($existing_waybill_no) {
            $exists = $this->wpdb->get_var($this->wpdb->prepare(
                "SELECT waybill_no FROM $waybills_table WHERE waybill_no = %d",
                $existing_waybill_no
            ));
            
            if (!$exists) {
                $waybill_no = $existing_waybill_no;
            } else {
                // Skip if waybill already exists
                return [
                    'success' => false,
                    'message' => "Waybill number {$existing_waybill_no} already exists, skipping"
                ];
            }
        }
        
        // Generate new waybill number if we don't have one
        if (!$waybill_no) {
            $waybill_no = $this->generate_waybill_number();
        }
        
        // Extract waybill data from row
        $waybill_description = sanitize_text_field($row['Waybill  description'] ?? '');
        $item_description = sanitize_text_field($row['Item description'] ?? '');
        $quantity = intval($row['QUANTITY'] ?? 0);
        $basis = sanitize_text_field($row['BASIS'] ?? 'MASS');
        $total_mass = floatval($row['T MASS'] ?? 0);
        $length = floatval($row['LENGTH'] ?? 0);
        $width = floatval($row['WIDTH'] ?? 0);
        $height = floatval($row['HEIGHT'] ?? 0);
        $total_volume = floatval($row['T VOLUME'] ?? 0);
        $mass_cost = $this->clean_currency_value($row['MASS COST'] ?? 0);
        $vol_cost = $this->clean_currency_value($row['VOL COST'] ?? 0);
        
        // Map Excel TRUE values to boolean (handles TRUE, true, "TRUE", 1, etc.)
        $sad500_raw = $row['SAD500'] ?? '';
        $sadc_raw = $row['SADC'] ?? '';
        $vat_raw = $row['VAT'] ?? '';
        
        $sad500 = $this->convert_to_boolean($sad500_raw);
        $sadc = $this->convert_to_boolean($sadc_raw);
        $vat = $this->convert_to_boolean($vat_raw);
        
        $supplier = sanitize_text_field($row['Supplier'] ?? '');
        $invoice_number = sanitize_text_field($row['CL INV #'] ?? ''); // This is the CLIENT invoice number from Excel
        $client_invoice = sanitize_text_field($row['CL INV #'] ?? ''); // CL INV # is the client invoice number
        $client_invoice_amount = $this->clean_currency_value($row['CLIENT INVOICE ( R)'] ?? 0);
        
        // Get the charge basis and total charge
        $charge_basis = ($basis === 'VOLUME' || ($total_volume > 0 && $vol_cost > $mass_cost)) ? 'volume' : 'mass';
        $waybillTotal = max($mass_cost, $vol_cost);
        $waybillItemsTotal = $waybillTotal; // We'll update this when we create items
        
        // Get direction_id from delivery
        $direction_id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT direction_id FROM {$this->wpdb->prefix}kit_deliveries WHERE id = %d",
            $delivery_id
        )) ?: 1;
        
        // Get city_id from customer
        $city_id = $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT city_id FROM {$this->wpdb->prefix}kit_customers WHERE cust_id = %d",
            $customer_id
        )) ?: 1;
        
        // Prepare miscellaneous field
        $misc_data = [
            'supplier' => $supplier,
            'client_invoice' => $client_invoice,
            'invoice_number' => $invoice_number,
        ];
        $misc_serialized = maybe_serialize($misc_data);
        
        // ALWAYS use the uniform generate_product_invoice_number() function
        // The Excel CL INV # goes into client_invoice (waybill_items table), not product_invoice_number
        // CRITICAL: Never use $invoice_number or $client_invoice for product_invoice_number
        if (!class_exists('KIT_Waybills') || !method_exists('KIT_Waybills', 'generate_product_invoice_number')) {
            error_log("Excel Import Error: KIT_Waybills::generate_product_invoice_number() not available");
            return [
                'success' => false,
                'message' => 'Product invoice number generation function not available'
            ];
        }
        
        $product_invoice_number = KIT_Waybills::generate_product_invoice_number();
        
        // Validate that product_invoice_number was generated correctly (should be in format INV-YYYYMMDD-XXXXX)
        if (empty($product_invoice_number) || !preg_match('/^INV-\d{8}-\d{5}$/', $product_invoice_number)) {
            error_log("Excel Import Error: Invalid product_invoice_number generated: " . ($product_invoice_number ?: 'NULL'));
            error_log("Excel Import Error: CL INV # value (should NOT be used): " . ($invoice_number ?: 'NULL'));
            return [
                'success' => false,
                'message' => 'Failed to generate valid product invoice number'
            ];
        }
        
        // Double-check: ensure we never accidentally use CL INV # for product_invoice_number
        if ($product_invoice_number === $invoice_number || $product_invoice_number === $client_invoice) {
            error_log("Excel Import Error: CRITICAL - product_invoice_number matches CL INV #! This should never happen!");
            return [
                'success' => false,
                'message' => 'Product invoice number conflict detected'
            ];
        }
        
        // Create waybill data
        $waybill_data = [
            'description' => $waybill_description,
            'direction_id' => $direction_id,
            'delivery_id' => $delivery_id,
            'customer_id' => $customer_id,
            'city_id' => $city_id,
            'waybill_no' => $waybill_no,
            'warehouse' => 0,
            'product_invoice_number' => $product_invoice_number,
            'product_invoice_amount' => $client_invoice_amount,
            'waybill_items_total' => $waybillItemsTotal,
            'item_length' => $length,
            'item_width' => $width,
            'item_height' => $height,
            'total_mass_kg' => $total_mass,
            'total_volume' => $total_volume,
            'mass_charge' => $mass_cost,
            'volume_charge' => $vol_cost,
            'charge_basis' => $charge_basis,
            'miscellaneous' => $misc_serialized,
            'include_sad500' => $sad500 ? 1 : 0,
            'include_sadc' => $sadc ? 1 : 0,
            'vat_include' => $vat ? 1 : 0,
            'tracking_number' => 'TRK-' . strtoupper(wp_generate_password(8, false)),
            'created_by' => get_current_user_id() ?: 1,
            'last_updated_by' => get_current_user_id() ?: 1,
            'status' => 'pending',
            'created_at' => $row['Waybill Creation Date'] ?? current_time('mysql'),
            'last_updated_at' => current_time('mysql'),
        ];
        
        $result = $this->wpdb->insert($waybills_table, $waybill_data);
        
        if ($result) {
            return [
                'success' => true,
                'waybill_no' => $waybill_no,
                'waybill_id' => $this->wpdb->insert_id
            ];
        }
        
        return [
            'success' => false,
            'message' => 'Failed to insert waybill: ' . $this->wpdb->last_error
        ];
    }
    
    /**
     * Extract waybill number from row data
     */
    private function extract_waybill_number($row) {
        $description = $row['Waybill  description'] ?? '';
        
        // Try to find WB-XXXX pattern
        if (preg_match('/WB-?\s*(\d+)/i', $description, $matches)) {
            return intval($matches[1]);
        }
        
        return null;
    }
    
    /**
     * Generate unique waybill number starting from 4000
     * Uses the KIT_Waybills function to ensure consistency
     */
    private function generate_waybill_number() {
        // Use the centralized function that starts from 4000
        return KIT_Waybills::generate_waybill_number();
    }
    
    /**
     * Convert Excel boolean values (TRUE, true, "TRUE", 1, etc.) to boolean
     */
    private function convert_to_boolean($value) {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_numeric($value)) {
            return intval($value) > 0;
        }
        
        $value_str = strtoupper(trim((string)$value));
        return in_array($value_str, ['TRUE', '1', 'YES', 'Y', 'ON']);
    }
    
    /**
     * Create waybill items
     */
    private function create_waybill_items($row, $waybill_no, $client_invoice = '') {
        $items_table = $this->wpdb->prefix . 'kit_waybill_items';
        
        $item_description = sanitize_text_field($row['Item description'] ?? '');
        $quantity = intval($row['QUANTITY'] ?? 0);
        $total_mass = floatval($row['T MASS'] ?? 0);
        $total_volume = floatval($row['T VOLUME'] ?? 0);
        $mass_cost = $this->clean_currency_value($row['MASS COST'] ?? 0);
        $vol_cost = $this->clean_currency_value($row['VOL COST'] ?? 0);
        
        // Calculate unit price based on quantity
        $total_cost = max($mass_cost, $vol_cost);
        $unit_price = $quantity > 0 ? ($total_cost / $quantity) : 0;
        
        // Calculate unit mass and volume
        $unit_mass = $quantity > 0 ? ($total_mass / $quantity) : 0;
        $unit_volume = $quantity > 0 ? ($total_volume / $quantity) : 0;
        
        // Skip if no item data
        if (empty($item_description) || $quantity <= 0) {
            return;
        }
        
        $this->wpdb->insert($items_table, [
            'waybillno' => $waybill_no,
            'item_name' => $item_description,
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'unit_mass' => $unit_mass,
            'unit_volume' => $unit_volume,
            'total_price' => $total_cost,
            'client_invoice' => !empty($client_invoice) ? sanitize_text_field($client_invoice) : null,
            'created_at' => current_time('mysql'),
        ], [
            '%d',
            '%s',
            '%d',
            '%f',
            '%f',
            '%f',
            '%f',
            '%s',
            '%s'
        ]);
    }
    
    /**
     * Print import statistics
     */
    public function print_stats() {
        echo "\n=== Import Statistics ===\n";
        echo "Total rows processed: " . $this->stats['total_rows'] . "\n";
        echo "Drivers created: " . $this->stats['drivers_created'] . "\n";
        echo "Customers created: " . $this->stats['customers_created'] . "\n";
        echo "Deliveries created: " . $this->stats['deliveries_created'] . "\n";
        echo "Waybills created: " . $this->stats['waybills_created'] . "\n";
        
        if (!empty($this->stats['errors'])) {
            echo "\nErrors:\n";
            foreach ($this->stats['errors'] as $error) {
                echo "  - " . $error['waybill_key'] . ": " . $error['error'] . "\n";
            }
        }
        
        echo "\n";
    }
}

// Main execution
if (defined('WP_CLI') || (isset($_GET['run_import']) && current_user_can('manage_options'))) {
    $excel_file = __DIR__ . '/waybill_excel/Waybills_31-10-2025.xlsx';
    
    if (!file_exists($excel_file)) {
        die("Excel file not found: $excel_file\n");
    }
    
    echo "Starting waybill import from: $excel_file\n";
    
    $importer = new Excel_Waybill_Importer($excel_file);
    $result = $importer->import();
    
    if ($result['success']) {
        echo "Import completed successfully!\n";
        $importer->print_stats();
    } else {
        echo "Import failed: " . ($result['message'] ?? 'Unknown error') . "\n";
        $importer->print_stats();
    }
} else {
    echo "This script requires administrative privileges.\n";
}

