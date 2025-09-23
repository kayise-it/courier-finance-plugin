<?php
/**
 * Warehouse Management Functions
 * Handles warehouse items, assignments, and status tracking
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIT_Warehouse
{
    /**
     * Add waybill items to warehouse
     */
    public static function addToWarehouse($waybill_id, $waybill_data)
    {
        global $wpdb;
        
        $warehouse_table = $wpdb->prefix . 'kit_warehouse_items';
        $waybill_items_table = $wpdb->prefix . 'kit_waybill_items';
        
        // Get waybill number from waybill_data (not the database ID)
        $waybill_no = $waybill_data['waybill_no'] ?? null;
        if (!$waybill_no) {
            return new WP_Error('missing_waybill_no', 'Waybill number is required');
        }
        
        // Get waybill items using waybill number (not database ID)
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $waybill_items_table WHERE waybillno = %d",
            $waybill_no
        ));
        
        if (empty($items)) {
            return new WP_Error('no_items', 'No items found for waybill');
        }
        
        $warehouse_items = [];
        
        foreach ($items as $item) {
            $warehouse_item = [
                'waybill_id' => $waybill_id,
                'customer_id' => $waybill_data['customer_id'],
                'item_description' => $item->item_description,
                'weight_kg' => $item->weight_kg ?? 0.00,
                'length_cm' => $item->length_cm ?? 0.00,
                'width_cm' => $item->width_cm ?? 0.00,
                'height_cm' => $item->height_cm ?? 0.00,
                'volume_cm3' => $item->volume_cm3 ?? 0.00,
                'status' => 'in_warehouse',
                'notes' => 'Added to warehouse from waybill #' . $waybill_data['waybill_no'],
                'created_at' => current_time('mysql')
            ];
            
            $result = $wpdb->insert($warehouse_table, $warehouse_item);
            
            if ($result === false) {
                error_log('Failed to add item to warehouse: ' . $wpdb->last_error);
                return new WP_Error('db_error', 'Failed to add item to warehouse');
            }
            
            $warehouse_items[] = $wpdb->insert_id;
        }
        
        // Log warehouse action
        self::logWarehouseAction($waybill_id, $waybill_data['waybill_no'], 'warehoused', 'pending', 'warehoused');
        
        return $warehouse_items;
    }
    
    /**
     * Get warehouse items for a waybill
     */
    public static function getWarehouseItems($waybill_id)
    {
        global $wpdb;
        $warehouse_table = $wpdb->prefix . 'kit_warehouse_items';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT wi.*, d.delivery_reference, d.dispatch_date
             FROM $warehouse_table wi
             LEFT JOIN {$wpdb->prefix}kit_deliveries d ON wi.assigned_delivery_id = d.id
             WHERE wi.waybill_id = %d
             ORDER BY wi.created_at ASC",
            $waybill_id
        ));
    }
    
    /**
     * Get all warehouse items with status
     */
    public static function getAllWarehouseItems($status = null)
    {
        global $wpdb;
        $warehouse_table = $wpdb->prefix . 'kit_warehouse_items';
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $customers_table = $wpdb->prefix . 'kit_customers';
        
        $where_clause = '';
        if ($status) {
            $where_clause = $wpdb->prepare(" WHERE wi.status = %s", $status);
        }
        
        return $wpdb->get_results(
            "SELECT wi.*, w.waybill_no, c.name as customer_name, c.surname as customer_surname, d.delivery_reference, d.dispatch_date
             FROM $warehouse_table wi
             LEFT JOIN $waybills_table w ON wi.waybill_id = w.id
             LEFT JOIN $customers_table c ON wi.customer_id = c.cust_id
             LEFT JOIN {$wpdb->prefix}kit_deliveries d ON wi.assigned_delivery_id = d.id
             $where_clause
             ORDER BY wi.created_at DESC"
        );
    }
    
    /**
     * Assign warehouse item to delivery
     */
    public static function assignToDelivery($warehouse_item_id, $delivery_id, $assigned_by = null)
    {
        global $wpdb;
        $warehouse_table = $wpdb->prefix . 'kit_warehouse_items';
        
        if (!$assigned_by) {
            $assigned_by = get_current_user_id();
        }
        
        $result = $wpdb->update(
            $warehouse_table,
            [
                'status' => 'assigned',
                'assigned_delivery_id' => $delivery_id,
                'assigned_by' => $assigned_by,
                'assigned_at' => current_time('mysql')
            ],
            ['id' => $warehouse_item_id],
            ['%s', '%d', '%d', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            error_log('Failed to assign warehouse item to delivery: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Failed to assign item to delivery');
        }
        
        // Log warehouse action
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $warehouse_table WHERE id = %d", $warehouse_item_id));
        if ($item) {
            $waybill = $wpdb->get_row($wpdb->prepare("SELECT waybill_no FROM {$wpdb->prefix}kit_waybills WHERE id = %d", $item->waybill_id));
            self::logWarehouseAction($item->waybill_id, $waybill->waybill_no, 'assigned', 'warehoused', 'assigned', $delivery_id);
        }
        
        return true;
    }
    
    /**
     * Mark warehouse item as shipped
     */
    public static function markAsShipped($warehouse_item_id)
    {
        global $wpdb;
        $warehouse_table = $wpdb->prefix . 'kit_warehouse_items';
        
        $result = $wpdb->update(
            $warehouse_table,
            [
                'status' => 'shipped',
                'shipped_at' => current_time('mysql')
            ],
            ['id' => $warehouse_item_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            error_log('Failed to mark warehouse item as shipped: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Failed to mark item as shipped');
        }
        
        return true;
    }
    
    /**
     * Mark warehouse item as delivered
     */
    public static function markAsDelivered($warehouse_item_id)
    {
        global $wpdb;
        $warehouse_table = $wpdb->prefix . 'kit_warehouse_items';
        
        $result = $wpdb->update(
            $warehouse_table,
            [
                'status' => 'delivered',
                'delivered_at' => current_time('mysql')
            ],
            ['id' => $warehouse_item_id],
            ['%s', '%s'],
            ['%d']
        );
        
        if ($result === false) {
            error_log('Failed to mark warehouse item as delivered: ' . $wpdb->last_error);
            return new WP_Error('db_error', 'Failed to mark item as delivered');
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
        $warehouse_table = $wpdb->prefix . 'kit_warehouse_items';
        
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
                 AND d.delivery_reference != 'warehoused'
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
                 AND d.delivery_reference != 'warehoused'
                 ORDER BY d.dispatch_date ASC",
                $destination_country
            ));
        }
        
        return $deliveries;
    }
    
    /**
     * Log warehouse actions
     */
    private static function logWarehouseAction($waybill_id, $waybill_no, $action, $previous_status, $new_status, $delivery_id = null)
    {
        global $wpdb;
        $tracking_table = $wpdb->prefix . 'kit_warehouse_tracking';
        
        // Resolve customer id for tracking (avoid FK violations)
        $customer_id = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT customer_id FROM {$wpdb->prefix}kit_waybills WHERE id = %d",
            $waybill_id
        ));
        $wpdb->insert($tracking_table, [
            'waybill_id' => $waybill_id,
            'waybill_no' => $waybill_no,
            'customer_id' => $customer_id,
            'action' => $action,
            'previous_status' => $previous_status,
            'new_status' => $new_status,
            'assigned_delivery_id' => $delivery_id,
            'notes' => "Warehouse item $action",
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql')
        ]);
    }
    
    /**
     * Get warehouse statistics
     */
    public static function getWarehouseStats()
    {
        global $wpdb;
        $warehouse_table = $wpdb->prefix . 'kit_warehouse_items';
        
        $stats = $wpdb->get_row(
            "SELECT 
                COUNT(*) as total_items,
                SUM(CASE WHEN status = 'in_warehouse' THEN 1 ELSE 0 END) as in_warehouse,
                SUM(CASE WHEN status = 'assigned' THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered
             FROM $warehouse_table"
        );
        
        return $stats;
    }
}
