<?php
/**
 * Server Synchronization Example
 * 
 * This file demonstrates how to use the server connection class
 * for data synchronization with external services
 * 
 * @package CourierFinancePlugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIT_Server_Sync_Example
{
    private $server_connection;
    
    public function __construct()
    {
        $this->server_connection = KIT_Server_Connection::get_instance();
    }
    
    /**
     * Sync waybill data to external server
     */
    public function sync_waybill($waybill_id)
    {
        global $wpdb;
        
        // Get waybill data
        $waybill = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kit_waybills WHERE id = %d",
            $waybill_id
        ), ARRAY_A);
        
        if (!$waybill) {
            return false;
        }
        
        // Prepare data for external server
        $sync_data = [
            'waybill_id' => $waybill['id'],
            'waybill_no' => $waybill['waybill_no'],
            'customer_id' => $waybill['customer_id'],
            'status' => $waybill['status'],
            'created_at' => $waybill['created_at'],
            'updated_at' => $waybill['updated_at']
        ];
        
        // Send to external API
        $api_response = $this->server_connection->api_request(
            'waybills/sync',
            'POST',
            $sync_data
        );
        
        if ($api_response['success']) {
            // Send webhook notification
            $this->server_connection->send_webhook('waybill_synced', $sync_data);
            
            // Log successful sync
            error_log("Waybill {$waybill_id} synced successfully");
            return true;
        } else {
            // Log sync failure
            error_log("Waybill {$waybill_id} sync failed: " . $api_response['error']);
            return false;
        }
    }
    
    /**
     * Sync customer data to external server
     */
    public function sync_customer($customer_id)
    {
        global $wpdb;
        
        // Get customer data
        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kit_customers WHERE id = %d",
            $customer_id
        ), ARRAY_A);
        
        if (!$customer) {
            return false;
        }
        
        // Prepare data for external server
        $sync_data = [
            'customer_id' => $customer['id'],
            'cust_id' => $customer['cust_id'],
            'name' => $customer['name'],
            'surname' => $customer['surname'],
            'email' => $customer['email_address'],
            'phone' => $customer['cell'],
            'address' => $customer['address'],
            'company_name' => $customer['company_name'],
            'created_at' => $customer['created_at']
        ];
        
        // Send to external API
        $api_response = $this->server_connection->api_request(
            'customers/sync',
            'POST',
            $sync_data
        );
        
        if ($api_response['success']) {
            // Send webhook notification
            $this->server_connection->send_webhook('customer_synced', $sync_data);
            
            // Log successful sync
            error_log("Customer {$customer_id} synced successfully");
            return true;
        } else {
            // Log sync failure
            error_log("Customer {$customer_id} sync failed: " . $api_response['error']);
            return false;
        }
    }
    
    /**
     * Sync delivery data to external server
     */
    public function sync_delivery($delivery_id)
    {
        global $wpdb;
        
        // Get delivery data
        $delivery = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}kit_deliveries WHERE id = %d",
            $delivery_id
        ), ARRAY_A);
        
        if (!$delivery) {
            return false;
        }
        
        // Prepare data for external server
        $sync_data = [
            'delivery_id' => $delivery['id'],
            'delivery_reference' => $delivery['delivery_reference'],
            'direction_id' => $delivery['direction_id'],
            'destination_city_id' => $delivery['destination_city_id'],
            'dispatch_date' => $delivery['dispatch_date'],
            'truck_number' => $delivery['truck_number'],
            'status' => $delivery['status'],
            'created_at' => $delivery['created_at']
        ];
        
        // Send to external API
        $api_response = $this->server_connection->api_request(
            'deliveries/sync',
            'POST',
            $sync_data
        );
        
        if ($api_response['success']) {
            // Send webhook notification
            $this->server_connection->send_webhook('delivery_synced', $sync_data);
            
            // Log successful sync
            error_log("Delivery {$delivery_id} synced successfully");
            return true;
        } else {
            // Log sync failure
            error_log("Delivery {$delivery_id} sync failed: " . $api_response['error']);
            return false;
        }
    }
    
    /**
     * Bulk sync all data
     */
    public function bulk_sync_all()
    {
        global $wpdb;
        
        $results = [
            'waybills' => 0,
            'customers' => 0,
            'deliveries' => 0,
            'errors' => []
        ];
        
        // Sync waybills
        $waybills = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}kit_waybills ORDER BY id DESC LIMIT 100");
        foreach ($waybills as $waybill) {
            if ($this->sync_waybill($waybill->id)) {
                $results['waybills']++;
            } else {
                $results['errors'][] = "Waybill {$waybill->id} sync failed";
            }
        }
        
        // Sync customers
        $customers = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}kit_customers ORDER BY id DESC LIMIT 100");
        foreach ($customers as $customer) {
            if ($this->sync_customer($customer->id)) {
                $results['customers']++;
            } else {
                $results['errors'][] = "Customer {$customer->id} sync failed";
            }
        }
        
        // Sync deliveries
        $deliveries = $wpdb->get_results("SELECT id FROM {$wpdb->prefix}kit_deliveries ORDER BY id DESC LIMIT 100");
        foreach ($deliveries as $delivery) {
            if ($this->sync_delivery($delivery->id)) {
                $results['deliveries']++;
            } else {
                $results['errors'][] = "Delivery {$delivery->id} sync failed";
            }
        }
        
        return $results;
    }
    
    /**
     * Get sync status
     */
    public function get_sync_status()
    {
        $connection_status = $this->server_connection->get_connection_status();
        
        return [
            'connection_status' => $connection_status,
            'last_sync' => get_option('kit_last_sync', 'Never'),
            'sync_enabled' => get_option('kit_sync_enabled', false)
        ];
    }
}

// Initialize sync hooks
add_action('init', function() {
    $sync = new KIT_Server_Sync_Example();
    
    // Hook into waybill creation/update
    add_action('kit_waybill_created', [$sync, 'sync_waybill']);
    add_action('kit_waybill_updated', [$sync, 'sync_waybill']);
    
    // Hook into customer creation/update
    add_action('kit_customer_created', [$sync, 'sync_customer']);
    add_action('kit_customer_updated', [$sync, 'sync_customer']);
    
    // Hook into delivery creation/update
    add_action('kit_delivery_created', [$sync, 'sync_delivery']);
    add_action('kit_delivery_updated', [$sync, 'sync_delivery']);
});

// AJAX handler for bulk sync
add_action('wp_ajax_bulk_sync_data', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'bulk_sync_data')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    $sync = new KIT_Server_Sync_Example();
    $results = $sync->bulk_sync_all();
    
    // Update last sync time
    update_option('kit_last_sync', current_time('mysql'));
    
    wp_send_json_success($results);
});

// AJAX handler for sync status
add_action('wp_ajax_get_sync_status', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'get_sync_status')) {
        wp_send_json_error(['message' => 'Security check failed']);
        return;
    }
    
    $sync = new KIT_Server_Sync_Example();
    $status = $sync->get_sync_status();
    
    wp_send_json_success($status);
});
