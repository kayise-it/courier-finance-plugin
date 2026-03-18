<?php
/**
 * Warehouse Management Functions
 * Handles warehouse operations using kit_waybills table
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIT_Warehouse
{
    /**
     * Add waybill to warehouse
     */
    public static function addToWarehouse($waybill_id, $warehouse_location = 'Johannesburg')
    {
        global $wpdb;
        
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        
        $result = $wpdb->update(
            $waybills_table,
            [
                'status' => 'pending',
                'warehouse' => $warehouse_location
            ],
            ['id' => $waybill_id],
            ['%s', '%s'],
            ['%d']
        );
            
            if ($result === false) {
            error_log('Failed to add waybill to warehouse: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Failed to add waybill to warehouse');
        }
        
        return true;
    }
    
    /**
     * Get warehouse items from waybills table
     */
    public static function getWarehouseItems($warehouse_location = null)
    {
        global $wpdb;
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $customers_table = $wpdb->prefix . 'kit_customers';
        
        // Get waybills in warehouse (warehouse = 1)
        $where_conditions = ["w.warehouse = 1"];
        
        if ($warehouse_location) {
            $where_conditions[] = $wpdb->prepare("w.warehouse = %s", $warehouse_location);
        }
        
        $where_clause = " WHERE " . implode(" AND ", $where_conditions);
        
        // Build waybills query
        $query = "
            SELECT 
                w.id,
                w.id as waybill_id,
                w.waybill_no,
                w.product_invoice_amount,
                w.total_mass_kg,
                w.item_length,
                w.item_width,
                w.item_height,
                w.status,
                w.created_at,
                w.last_updated_at,
                c.name as customer_name,
                c.surname as customer_surname,
                c.company_name,
                c.cell as customer_cell,
                c.email_address as customer_email
            FROM {$waybills_table} w
            LEFT JOIN {$customers_table} c ON w.customer_id = c.cust_id
            {$where_clause}
            ORDER BY w.created_at DESC
        ";
        
        if ($warehouse_location) {
            return $wpdb->get_results($wpdb->prepare($query, $warehouse_location));
        } else {
            return $wpdb->get_results($query);
        }
    }
    
    /**
     * Get all warehouse items with status from waybills table
     */
    public static function getAllWarehouseItems($status = null, $warehouse_location = null)
    {
        global $wpdb;
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $customers_table = $wpdb->prefix . 'kit_customers';
        
        $where_conditions = [];
        
        // Only show waybills where warehouse = 1 (actual warehouse waybills)
        // warehouse is BOOLEAN (TINYINT(1)): 1 = in warehouse, 0/NULL = not in warehouse
        if ($status) {
            $where_conditions[] = $wpdb->prepare("w.status = %s", $status);
            $where_conditions[] = "w.warehouse = 1";
        } else {
            $where_conditions[] = "w.warehouse = 1";
        }
        
        if ($warehouse_location) {
            $where_conditions[] = $wpdb->prepare("w.warehouse = %s", $warehouse_location);
        }
        
        $where_clause = " WHERE " . implode(" AND ", $where_conditions);
        
        // Get individual waybills
        $individual_query = "
            SELECT 
                w.*,
                w.id,
                w.id as waybill_id,
                w.product_invoice_amount,
                w.total_mass_kg,
                w.status,
                w.created_at,
                w.last_updated_at,
                c.name as customer_name, 
                c.surname as customer_surname, 
                c.company_name,
                c.cell as customer_cell,
                c.email_address as customer_email,
                d.delivery_reference, 
                d.dispatch_date
             FROM {$waybills_table} w
             LEFT JOIN {$customers_table} c ON w.customer_id = c.cust_id
             LEFT JOIN {$wpdb->prefix}kit_deliveries d ON w.delivery_id = d.id
             $where_clause
             ORDER BY w.created_at DESC
        ";
        
        return $wpdb->get_results($individual_query);
    }
    
    
    /**
     * Assign waybill to delivery
     */
    public static function assignToDelivery($waybill_id, $delivery_id, $assigned_by = null)
    {
        global $wpdb;
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        
        if (!$assigned_by) {
            $assigned_by = get_current_user_id();
        }
        
        // Update waybill status to assigned and set warehouse to 0 (not in warehouse)
        // warehouse is BOOLEAN (TINYINT(1)): 1 = in warehouse, 0 = not in warehouse
        $result = $wpdb->update(
            $waybills_table,
            [
                'status' => 'assigned',
                'delivery_id' => $delivery_id,
                'warehouse' => 0, // Set to 0 to indicate it's no longer in warehouse
                'last_updated_by' => $assigned_by,
                'last_updated_at' => current_time('mysql')
            ],
            ['id' => $waybill_id],
            ['%s', '%d', '%d', '%d', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            error_log('Failed to assign waybill to delivery: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Failed to assign waybill to delivery');
        }
        
        return true;
    }
    
    /**
     * Mark waybill as shipped
     */
    public static function markAsShipped($waybill_id)
    {
        global $wpdb;
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        
        $result = $wpdb->update(
            $waybills_table,
            [
                'status' => 'shipped',
                'last_updated_at' => current_time('mysql')
            ],
            ['id' => $waybill_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            error_log('Failed to mark waybill as shipped: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Failed to mark waybill as shipped');
        }
        
        return true;
    }
    
    /**
     * Mark waybill as delivered
     */
    public static function markAsDelivered($waybill_id)
    {
        global $wpdb;
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        
        $result = $wpdb->update(
            $waybills_table,
            [
                'status' => 'delivered',
                'last_updated_at' => current_time('mysql')
            ],
            ['id' => $waybill_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            error_log('Failed to mark waybill as delivered: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Failed to mark waybill as delivered');
        }
        
        return true;
    }
    
    /**
     * Get available deliveries for warehouse assignment
     */
    public static function getAvailableDeliveries($destination_country, $destination_city = '')
    {
        global $wpdb;
        $deliveries_table = $wpdb->prefix . 'kit_deliveries';
        
        if (empty($destination_city)) {
            // Match by country only
            $deliveries = $wpdb->get_results($wpdb->prepare(
                "SELECT d.id as delivery_id, d.delivery_reference as delivery_name, 
                        oc2.country_name as destination_country, 
                        '' as destination_city, 
                        d.dispatch_date 
                 FROM $deliveries_table d
                 LEFT JOIN {$wpdb->prefix}kit_shipping_directions sd ON d.direction_id = sd.id
                 LEFT JOIN {$wpdb->prefix}kit_operating_countries oc2 ON sd.destination_country_id = oc2.id
                 WHERE oc2.country_name = %s 
                 AND oc2.is_active = 1
                 AND d.dispatch_date >= CURDATE()
                 AND d.status = 'scheduled'
                 AND d.delivery_reference != 'pending'
                 ORDER BY d.dispatch_date ASC",
                $destination_country
            ));
        } else {
            // Match by country and city (city matching not available in current schema)
            $deliveries = $wpdb->get_results($wpdb->prepare(
                "SELECT d.id as delivery_id, d.delivery_reference as delivery_name, 
                        oc2.country_name as destination_country, 
                        '' as destination_city, 
                        d.dispatch_date 
                 FROM $deliveries_table d
                 LEFT JOIN {$wpdb->prefix}kit_shipping_directions sd ON d.direction_id = sd.id
                 LEFT JOIN {$wpdb->prefix}kit_operating_countries oc2 ON sd.destination_country_id = oc2.id
                 WHERE oc2.country_name = %s 
                 AND oc2.is_active = 1
                 AND d.dispatch_date >= CURDATE()
                 AND d.status = 'scheduled'
                 AND d.delivery_reference != 'pending'
                 ORDER BY d.dispatch_date ASC",
                $destination_country
            ));
        }
        
        return $deliveries;
    }
    
    /**
     * Get warehouse statistics from waybills table
     */
    public static function getWarehouseStats()
    {
        global $wpdb;
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        
        // Only count waybills where warehouse = 1 as "in_warehouse"
        // warehouse is BOOLEAN (TINYINT(1)): 1 = in warehouse, 0/NULL = not in warehouse
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_items,
                SUM(CASE WHEN warehouse = 1 THEN 1 ELSE 0 END) as in_warehouse,
                SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered
             FROM {$waybills_table}"
        );
        
        return $stats;
    }
    
    /**
     * Get warehouse items by waybill number
     */
    public static function getWarehouseItemByWaybillNo($waybill_no)
    {
        global $wpdb;
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $customers_table = $wpdb->prefix . 'kit_customers';
        
        // Only return waybills where warehouse = 1 (actual warehouse waybills)
        return $wpdb->get_row($wpdb->prepare(
            "SELECT w.*, c.name as customer_name, c.surname as customer_surname
             FROM $waybills_table w
             LEFT JOIN $customers_table c ON w.customer_id = c.cust_id
             WHERE w.waybill_no = %d
             AND w.warehouse = 1",
            $waybill_no
        ));
    }
    
    /**
     * Remove waybill from warehouse
     */
    public static function removeFromWarehouse($waybill_id)
    {
        global $wpdb;
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        
        $result = $wpdb->update(
            $waybills_table,
            [
                'status' => 'pending',
                'warehouse' => '',
                'last_updated_at' => current_time('mysql')
            ],
            ['id' => $waybill_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            error_log('Failed to remove waybill from warehouse: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Failed to remove waybill from warehouse');
        }
        
        return true;
    }
    
    /**
     * Create realistic warehouse waybills like a human would
     */
    public static function createRealisticWarehousedWaybills($count = 10)
    {
        global $wpdb;
        
        // Get real customers from database
        $customers = $wpdb->get_results("
            SELECT cust_id, name, surname, email 
            FROM {$wpdb->prefix}kit_customers 
            WHERE cust_id > 0 
            ORDER BY RAND() 
            LIMIT 20
        ");
        
        if (empty($customers)) {
            return new WP_Error('no_customers', 'No customers found. Please create customers first.');
        }
        
        // Get delivery options (warehouse delivery)
        $warehouse_delivery = $wpdb->get_row("
            SELECT id FROM {$wpdb->prefix}kit_deliveries 
            WHERE delivery_reference = 'pending' 
            LIMIT 1
        ");
        
        if (!$warehouse_delivery) {
            return new WP_Error('no_warehouse_delivery', 'Warehouse delivery not found.');
        }
        
        // Get shipping directions for realistic data
        $directions = $wpdb->get_results("
            SELECT id FROM {$wpdb->prefix}kit_shipping_directions 
            ORDER BY RAND() 
            LIMIT 5
        ");
        
        $created_count = 0;
        $errors = [];
        
        // Realistic product descriptions
        $product_descriptions = [
            'Electronics - Laptops and Accessories',
            'Clothing - Winter Collection',
            'Books - Educational Materials',
            'Home Appliances - Kitchen Items',
            'Sports Equipment - Fitness Gear',
            'Furniture - Office Chairs',
            'Automotive Parts - Engine Components',
            'Medical Supplies - First Aid Kits',
            'Tools - Construction Equipment',
            'Toys - Educational Games'
        ];
        
        // Realistic warehouse locations
        $warehouse_locations = [
            'Johannesburg Warehouse',
            'Cape Town Distribution Center',
            'Durban Storage Facility',
            'Pretoria Logistics Hub',
            'Port Elizabeth Warehouse'
        ];
        
        for ($i = 1; $i <= $count; $i++) {
            $customer = $customers[array_rand($customers)];
            $direction = $directions[array_rand($directions)];
            $product_desc = $product_descriptions[array_rand($product_descriptions)];
            $warehouse_location = $warehouse_locations[array_rand($warehouse_locations)];
            
            // Generate realistic waybill number
            $waybill_no = 'WB-' . date('Y') . '-' . str_pad($i + rand(1000, 9999), 6, '0', STR_PAD_LEFT);
            
            // Realistic dimensions and weights
            $length = rand(20, 120); // cm
            $width = rand(15, 80);   // cm  
            $height = rand(10, 60);  // cm
            $weight = rand(5, 150);   // kg
            $volume = ($length * $width * $height) / 1000000; // m³
            
            // Realistic invoice amounts
            $invoice_amount = rand(250, 8500);
            
            $waybill_data = [
                'direction_id' => $direction->id,
                'delivery_id' => $warehouse_delivery->id,
                'customer_id' => $customer->cust_id,
                'approval' => 'approved',
                'waybill_no' => $waybill_no,
                'product_invoice_number' => class_exists('KIT_Waybills') ? KIT_Waybills::generate_product_invoice_number() : 'INV-' . date('Ymd') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'product_invoice_amount' => $invoice_amount,
                'waybill_items_total' => $invoice_amount,
                'item_length' => $length,
                'item_width' => $width,
                'item_height' => $height,
                'total_mass_kg' => $weight,
                'total_volume' => $volume,
                'mass_charge' => $weight * 2.5, // Realistic charge per kg
                'volume_charge' => $volume * 150, // Realistic charge per m³
                'charge_basis' => $weight > ($volume * 200) ? 'mass' : 'volume',
                'vat_include' => 'Yes',
                'warehouse' => $warehouse_location,
                'miscellaneous' => "Product: {$product_desc}\nCustomer: {$customer->name} {$customer->surname}\nEmail: {$customer->email}",
                'include_sad500' => rand(0, 1),
                'include_sadc' => rand(0, 1),
                'return_load' => rand(0, 1),
                'tracking_number' => 'TRK-' . strtoupper(wp_generate_password(8, false, false)),
                'status' => 'pending',
                'created_by' => get_current_user_id() ?: 1,
                'last_updated_by' => get_current_user_id() ?: 1,
                'created_at' => current_time('mysql'),
                'last_updated_at' => current_time('mysql')
            ];
            
            $result = $wpdb->insert(
                $wpdb->prefix . 'kit_waybills',
                $waybill_data
            );
            
            if ($result) {
                $created_count++;
                
                // Also create waybill items for more realism
                $item_count = rand(1, 5);
                for ($j = 1; $j <= $item_count; $j++) {
                    $item_data = [
                        'waybillno' => $waybill_no,
                        'item_description' => "Item {$j}: " . $product_descriptions[array_rand($product_descriptions)],
                        'quantity' => rand(1, 10),
                        'weight_kg' => $weight / $item_count,
                        'length_cm' => $length,
                        'width_cm' => $width,
                        'height_cm' => $height,
                        'volume_cm3' => $volume * 1000000 / $item_count,
                        'unit_price' => $invoice_amount / $item_count,
                        'total_price' => $invoice_amount / $item_count
                    ];
                    
                    $wpdb->insert($wpdb->prefix . 'kit_waybill_items', $item_data);
                }
            } else {
                $errors[] = "Failed to create waybill {$waybill_no}: " . $wpdb->last_error;
            }
        }
        
        return [
            'success' => true,
            'created_count' => $created_count,
            'total_requested' => $count,
            'errors' => $errors
        ];
    }
}