<?php
// Simulation script: create a waybill, warehouse it, then assign to a delivery
// Usage: php simulate_warehouse.php

// Ensure we do not trigger wp_send_json_* early exits
if (!defined('DOING_AJAX')) {
  define('DOING_AJAX', false);
}

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/08600-services-quotations.php';

// Helpers
function out($label, $value) { echo $label . ': ' . (is_scalar($value) ? $value : json_encode($value)) . PHP_EOL; }

global $wpdb;

// 1) Ensure there is at least one scheduled delivery
$delivery = $wpdb->get_row("SELECT id, delivery_reference FROM {$wpdb->prefix}kit_deliveries WHERE status='scheduled' ORDER BY id DESC LIMIT 1");
if (!$delivery) {
  $wpdb->insert($wpdb->prefix.'kit_deliveries', [
    'delivery_reference' => 'SIM-'.wp_generate_password(6,false,false),
    'direction_id' => 1,
    'destination_city_id' => 1,
    'status' => 'scheduled',
    'created_by' => 0,
    'created_at' => current_time('mysql')
  ]);
  $delivery_id = $wpdb->insert_id;
  $delivery = $wpdb->get_row($wpdb->prepare("SELECT id, delivery_reference FROM {$wpdb->prefix}kit_deliveries WHERE id=%d", $delivery_id));
}
out('Using delivery', $delivery->delivery_reference . ' (#'.$delivery->id.')');

// 2) Pick a customer
$customer = $wpdb->get_row("SELECT cust_id, name, surname FROM {$wpdb->prefix}kit_customers ORDER BY cust_id DESC LIMIT 1");
if (!$customer) { die("No customers found. Seed customers first.\n"); }
out('Using customer', $customer->cust_id . ' ' . $customer->name . ' ' . $customer->surname);

// 3) Create a waybill directly
$waybill_no = KIT_Waybills::generate_waybill_number();
$_POST = [
  '_wpnonce' => wp_create_nonce('save_waybill_nonce'),
  'waybill_no' => $waybill_no,
  'cust_id' => $customer->cust_id,
  'customer_select' => $customer->cust_id,
  'origin_country' => 2,
  'destination_country' => 1,
  'direction_id' => 1,
  'current_rate' => '30.00',
  'base_rate' => '30.00',
  'total_mass_kg' => '100',
  'item_length' => '50',
  'item_width' => '40',
  'item_height' => '30',
  'warehoused' => 1
];

$result = KIT_Waybills::save_waybill($_POST);
if (is_wp_error($result)) {
  die('Save error: ' . $result->get_error_message() . "\n");
}

$waybill = $wpdb->get_row($wpdb->prepare("SELECT id, waybill_no, warehouse FROM {$wpdb->prefix}kit_waybills WHERE waybill_no=%d", $waybill_no));
if (!$waybill) { die("Waybill not created.\n"); }
out('Waybill created', $waybill->waybill_no.' (DB id '.$waybill->id.')');
out('Warehouse flag', $waybill->warehouse);

// 4) Ensure warehouse items were created; if not, try adding
require_once __DIR__ . '/includes/warehouse/warehouse-functions.php';
$items = KIT_Warehouse::getWarehouseItems($waybill->id);
if (empty($items)) {
  // Directly insert a warehouse item matching current schema
  $wpdb->insert($wpdb->prefix.'kit_warehouse_items', [
    'waybill_id' => $waybill->id,
    'customer_id' => $customer->cust_id,
    'item_description' => 'Simulated Box',
    'weight_kg' => 10,
    'length_cm' => 50,
    'width_cm' => 40,
    'height_cm' => 30,
    'volume_cm3' => 60000,
    'status' => 'in_warehouse',
    'created_at' => current_time('mysql')
  ]);
  $items = KIT_Warehouse::getWarehouseItems($waybill->id);
}
out('Warehouse items', count($items));

// 5) Assign first warehouse item to the delivery
if (!empty($items)) {
  $first = $items[0];
  $ok = KIT_Warehouse::assignToDelivery($first->id, $delivery->id);
  out('Assigned item id', $first->id);
  out('Assign result', $ok ? 'OK' : 'FAILED');
}

echo "Done.\n";

