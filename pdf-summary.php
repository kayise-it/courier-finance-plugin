<?php
// Lightweight PDF/HTML summary for waybill accessible to Manager & Data Capturer

if (! defined('ABSPATH')) {
    require_once dirname(__FILE__, 4) . '/wp-load.php';
}

// Permission: Managers, Data Capturers, and Admins
if (! class_exists('KIT_User_Roles')) {
    wp_die('Access denied', 403);
}
if (! (KIT_User_Roles::is_admin() || KIT_User_Roles::is_manager() || KIT_User_Roles::is_data_capturer())) {
    wp_die('Access denied', 403);
}

$waybill_no = isset($_GET['waybill_no']) ? (int) $_GET['waybill_no'] : 0;
if (! $waybill_no) {
    wp_die('Missing waybill_no');
}

$data = KIT_Waybills::getFullWaybillWithItems($waybill_no);
if (! $data || empty($data->waybill)) {
    wp_die('Waybill not found');
}

$w = (object) $data->waybill;
$items = is_array($data->items) ? $data->items : [];

// Extract description and miscellaneous items from serialized misc field
$description = '';
$misc_items = [];
if (!empty($w->miscellaneous)) {
    $misc_data = maybe_unserialize($w->miscellaneous);
    if (is_array($misc_data)) {
        if (isset($misc_data['others']['waybill_description'])) {
            $description = (string) $misc_data['others']['waybill_description'];
        }
        if (isset($misc_data['misc_items']) && is_array($misc_data['misc_items'])) {
            $misc_items = $misc_data['misc_items'];
        }
    }
}

// Render minimal HTML summary (can be printed to PDF via browser)
header('Content-Type: text/html; charset=UTF-8');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Waybill <?= htmlspecialchars($w->waybill_no ?? '') ?> Summary</title>
  <style>
    body{font-family:Arial,sans-serif;font-size:12px;color:#111;margin:24px}
    h1{font-size:18px;margin:0 0 8px}
    table{border-collapse:collapse;width:100%}
    th,td{border:1px solid #e5e7eb;padding:6px;text-align:left}
    th{background:#f8fafc}
    .meta{margin:4px 0 12px}
  </style>
  <script>window.print && setTimeout(()=>window.print(), 200);</script>
  </head>
<body>
  <h1>Waybill Summary</h1>
  <div class="meta">
    <strong>Waybill #:</strong> <?= htmlspecialchars($w->waybill_no ?? '') ?>
    &nbsp;&nbsp;|&nbsp;&nbsp; <strong>Status:</strong> <?= htmlspecialchars($w->status ?? 'pending') ?>
    &nbsp;&nbsp;|&nbsp;&nbsp; <strong>Route:</strong> <?= htmlspecialchars(($w->route_description ?? '')) ?>
  </div>

  <?php if ($description !== ''): ?>
    <div class="meta"><strong>Description:</strong> <?= nl2br(htmlspecialchars($description)) ?></div>
  <?php endif; ?>

  <table>
    <tr>
      <th>Customer</th>
      <td><?= htmlspecialchars(trim(($w->customer_name ?? '') . ' ' . ($w->customer_surname ?? ''))) ?></td>
      <th>Company</th>
      <td><?= htmlspecialchars($w->company_name ?? '') ?></td>
    </tr>
    <tr>
      <th>Email</th>
      <td><?= htmlspecialchars($w->email_address ?? '') ?></td>
      <th>Cell</th>
      <td><?= htmlspecialchars($w->customer_cell ?? '') ?></td>
    </tr>
    <tr>
      <th>Origin</th>
      <td><?= htmlspecialchars($w->origin_country ?? '') ?></td>
      <th>Destination</th>
      <td><?= htmlspecialchars($w->destination_country ?? '') ?></td>
    </tr>
    <tr>
      <th>Dimensions (cm)</th>
      <td><?= htmlspecialchars(($w->item_length ?? 0) . ' × ' . ($w->item_width ?? 0) . ' × ' . ($w->item_height ?? 0)) ?></td>
      <th>Mass (kg)</th>
      <td><?= htmlspecialchars(number_format((float)($w->total_mass_kg ?? 0), 2)) ?></td>
    </tr>
  </table>

  <h3>Items</h3>
  <?php if (!empty($items)): ?>
    <table>
      <thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
      <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= htmlspecialchars($it['item_name'] ?? '') ?></td>
          <td><?= (int)($it['quantity'] ?? 0) ?></td>
          <td><?= number_format((float)($it['unit_price'] ?? 0), 2) ?></td>
          <td><?= number_format(((float)($it['unit_price'] ?? 0)) * ((int)($it['quantity'] ?? 0)), 2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="color:#6b7280; margin:6px 0 14px;">No items captured for this waybill.</p>
  <?php endif; ?>

  <h3>Miscellaneous</h3>
  <?php if (!empty($misc_items)): ?>
    <table>
      <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
      <tbody>
      <?php foreach ($misc_items as $mi): ?>
        <tr>
          <td><?= htmlspecialchars($mi['misc_item'] ?? '') ?></td>
          <td><?= (int)($mi['misc_quantity'] ?? 0) ?></td>
          <td><?= number_format((float)($mi['misc_price'] ?? 0), 2) ?></td>
          <td><?= number_format(((float)($mi['misc_price'] ?? 0)) * ((int)($mi['misc_quantity'] ?? 0)), 2) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p style="color:#6b7280; margin:6px 0 14px;">No miscellaneous charges captured for this waybill.</p>
  <?php endif; ?>

  <p style="margin-top:12px;color:#555;">This is a customer summary, not a tax invoice.</p>
</body>
</html>

