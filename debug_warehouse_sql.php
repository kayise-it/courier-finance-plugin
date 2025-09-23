<?php
// Debug script to print warehouse table summaries
// Usage: php debug_warehouse_sql.php

require_once __DIR__ . '/bootstrap.php';

global $wpdb;
$p = $wpdb->prefix;

function print_rows($title, $rows) {
  echo "\n== $title ==\n";
  if (empty($rows)) { echo "(none)\n"; return; }
  foreach ($rows as $r) {
    echo json_encode($r, JSON_UNESCAPED_SLASHES) . "\n";
  }
}

// Counts by status
$counts = $wpdb->get_results("SELECT status, COUNT(*) AS cnt FROM {$p}kit_warehouse_items GROUP BY status ORDER BY status");
print_rows('kit_warehouse_items counts', $counts);

// Latest items with joins
$items = $wpdb->get_results(
  "SELECT wi.id, wi.status, wi.created_at, wi.assigned_delivery_id,
          w.id AS waybill_id, w.waybill_no, w.warehouse,
          c.cust_id, c.name, c.surname
     FROM {$p}kit_warehouse_items wi
     LEFT JOIN {$p}kit_waybills w ON wi.waybill_id = w.id
     LEFT JOIN {$p}kit_customers c ON wi.customer_id = c.cust_id
     ORDER BY wi.id DESC LIMIT 20"
);
print_rows('latest warehouse_items (joined)', $items);

// Latest tracking
$tracking = $wpdb->get_results(
  "SELECT id, waybill_id, waybill_no, customer_id, action, previous_status, new_status, assigned_delivery_id, created_by, created_at
     FROM {$p}kit_warehouse_tracking
     ORDER BY id DESC LIMIT 20"
);
print_rows('latest warehouse_tracking', $tracking);

// Any waybills flagged as warehouse=1
$waybills_flagged = $wpdb->get_results(
  "SELECT id, waybill_no, customer_id, warehouse, status, created_at
     FROM {$p}kit_waybills
     WHERE warehouse = 1
     ORDER BY id DESC LIMIT 20"
);
print_rows('waybills flagged warehouse=1', $waybills_flagged);

echo "\nDone.\n";


