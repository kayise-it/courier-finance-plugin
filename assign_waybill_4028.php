<?php
require_once __DIR__ . '/bootstrap.php';

global $wpdb;

$delivery = $wpdb->get_row("SELECT id FROM {$wpdb->prefix}kit_deliveries WHERE status='scheduled' ORDER BY id DESC LIMIT 1");
if (!$delivery) { die("No scheduled delivery found\n"); }
$delivery_id = (int)$delivery->id;

$waybill = $wpdb->get_row($wpdb->prepare("SELECT id, customer_id FROM {$wpdb->prefix}kit_waybills WHERE waybill_no=%d LIMIT 1", 4028));
if (!$waybill) { die("Waybill 4028 not found\n"); }

$wi = $wpdb->get_row($wpdb->prepare("SELECT id FROM {$wpdb->prefix}kit_warehouse_items WHERE waybill_id=%d LIMIT 1", $waybill->id));
if (!$wi) {
    $wpdb->insert($wpdb->prefix.'kit_warehouse_items', [
        'waybill_id' => (int)$waybill->id,
        'customer_id' => (int)$waybill->customer_id,
        'status' => 'in_warehouse',
        'created_at' => current_time('mysql')
    ]);
    $wi_id = (int)$wpdb->insert_id;
} else {
    $wi_id = (int)$wi->id;
}

$wpdb->update($wpdb->prefix.'kit_warehouse_items', [
    'status' => 'assigned',
    'assigned_delivery_id' => $delivery_id,
    'assigned_at' => current_time('mysql')
], [ 'id' => $wi_id ]);

echo "Assigned waybill 4028 via warehouse_item #$wi_id to delivery #$delivery_id\n";
