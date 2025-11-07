<?php

/**
 * Delivery Truck PDF Generator
 *
 * Generates a consolidated PDF summarising a delivery and its associated waybills.
 */

// Bootstrap WordPress when accessed directly
if (! defined('ABSPATH')) {
    $possible_paths = [
        dirname(__FILE__, 4) . '/wp-load.php',
        dirname(__FILE__, 3) . '/wp-load.php',
        dirname(__FILE__, 2) . '/wp-load.php',
    ];

    $wp_loaded = false;
    foreach ($possible_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }

    if (! $wp_loaded) {
        die('WordPress bootstrap failed for delivery truck PDF.');
    }
}

// Validate nonce & permissions
$nonce = isset($_GET['delivery_nonce']) ? sanitize_text_field($_GET['delivery_nonce']) : '';
if (! $nonce || ! wp_verify_nonce($nonce, 'delivery_truck_pdf')) {
    wp_die('Invalid delivery PDF request', 403);
}

if (! class_exists('KIT_User_Roles') || ! KIT_User_Roles::can_see_prices()) {
    wp_die('Access denied. Delivery PDF restricted to authorised administrators.', 403);
}

$delivery_id = isset($_GET['delivery_id']) ? intval($_GET['delivery_id']) : 0;
if ($delivery_id <= 0) {
    wp_die('Missing delivery ID');
}

// Ensure required classes are available
if (! class_exists('KIT_Deliveries')) {
    require_once __DIR__ . '/includes/deliveries/deliveries-functions.php';
}
if (! class_exists('KIT_Waybills')) {
    require_once __DIR__ . '/includes/waybill/waybill-functions.php';
}
if (! class_exists('KIT_Routes')) {
    require_once __DIR__ . '/includes/routes/routes-functions.php';
}

$delivery = KIT_Deliveries::get_delivery($delivery_id);
if (! $delivery) {
    wp_die('Delivery not found');
}

$waybills = KIT_Waybills::truckWaybills($delivery_id);

// Enrich waybill data with city name and totals
$total_amount = 0.0;
$waybill_rows = [];
foreach ($waybills as $row) {
    $city_name = '';
    $city_id   = isset($row['city_id']) ? intval($row['city_id']) : 0;
    if ($city_id > 0) {
        $city_name = KIT_Routes::get_city_name_by_id($city_id) ?: '';
    }

    $mass   = isset($row['total_mass_kg']) ? floatval($row['total_mass_kg']) : 0.0;
    $volume = isset($row['total_volume']) ? floatval($row['total_volume']) : 0.0;
    $amount = isset($row['product_invoice_amount']) ? floatval($row['product_invoice_amount']) : 0.0;

    $miscellaneous_total = 0.0;
    if (! empty($row['miscellaneous'])) {
        $misc_data = maybe_unserialize($row['miscellaneous']);
        if (is_array($misc_data)) {
            if (isset($misc_data['misc_total'])) {
                $miscellaneous_total = floatval($misc_data['misc_total']);
            } elseif (isset($misc_data['misc_items']) && is_array($misc_data['misc_items'])) {
                foreach ($misc_data['misc_items'] as $item) {
                    $price = isset($item['misc_price']) ? floatval($item['misc_price']) : 0.0;
                    $qty   = isset($item['misc_quantity']) ? intval($item['misc_quantity']) : 0;
                    $miscellaneous_total += $price * $qty;
                }
            }
        }
    }

    $total_amount += ($amount + $miscellaneous_total);

    $waybill_rows[] = [
        'number'   => $row['waybill_no'] ?? '',
        'customer' => trim(($row['customer_name'] ?? '') . ' ' . ($row['customer_surname'] ?? '')),
        'city'     => $city_name,
        'status'   => isset($row['status']) ? ucfirst(str_replace('_', ' ', (string) $row['status'])) : '',
        'mass'     => $mass,
        'volume'   => $volume,
        'amount'   => $amount + $miscellaneous_total,
    ];
}

$totals = [
    'waybills' => KIT_Waybills::calculate_total_waybills($delivery_id),
    'mass'     => KIT_Waybills::calculate_total_mass($delivery_id),
    'volume'   => KIT_Waybills::calculate_total_volume($delivery_id),
    'amount'   => $total_amount,
];

// Company colours (with safe fallbacks)
$primary_color   = '#2563eb';
$secondary_color = '#111827';
$accent_color    = '#10b981';

if (class_exists('KIT_Commons')) {
    try {
        if (method_exists('KIT_Commons', 'getPrimaryColor')) {
            $primary_color = KIT_Commons::getPrimaryColor();
        }
        if (method_exists('KIT_Commons', 'getSecondaryColor')) {
            $secondary_color = KIT_Commons::getSecondaryColor();
        } elseif (method_exists('KIT_Commons', 'getSecondaryColorCanonical')) {
            $secondary_color = KIT_Commons::getSecondaryColorCanonical();
        }
        if (method_exists('KIT_Commons', 'getAccentColor')) {
            $accent_color = KIT_Commons::getAccentColor();
        } elseif (method_exists('KIT_Commons', 'getAccentColorCanonical')) {
            $accent_color = KIT_Commons::getAccentColorCanonical();
        }
    } catch (Throwable $e) {
        // keep defaults
    }
}

// Prepare HTML
ob_start();
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Delivery <?= esc_html($delivery->delivery_reference ?? ('#' . $delivery_id)); ?> Summary</title>
  <style>
    :root {
      --primary: <?= esc_html($primary_color); ?>;
      --secondary: <?= esc_html($secondary_color); ?>;
      --accent: <?= esc_html($accent_color); ?>;
    }

    body {
      font-family: Arial, sans-serif;
      font-size: 11px;
      color: #1f2937;
      margin: 0;
      padding: 0;
    }

    h1, h2, h3 {
      margin: 0;
      color: var(--primary);
    }

    table {
      border-collapse: collapse;
      width: 100%;
      margin-bottom: 14px;
    }

    th, td {
      border: 1px solid #e5e7eb;
      padding: 6px 8px;
      text-align: left;
    }

    th {
      background: #f3f4f6;
      font-weight: 600;
      color: #111827;
      font-size: 10px;
      letter-spacing: 0.2px;
    }

    .meta-table th {
      width: 24%;
    }

    .summary {
      display: flex;
      gap: 10px;
      margin: 12px 0 16px;
    }

    .summary-card {
      flex: 1;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      padding: 10px;
      background: #f9fafb;
    }

    .summary-card h3 {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.4px;
      color: var(--secondary);
      margin-bottom: 6px;
    }

    .summary-card .value {
      font-size: 16px;
      font-weight: 700;
      color: var(--primary);
    }

    .text-muted {
      color: #6b7280;
      font-size: 10px;
    }
  </style>
</head>
<body>
  <h1>Delivery Summary</h1>
  <p class="text-muted">Generated <?= esc_html(date_i18n('Y-m-d H:i')); ?></p>

  <table class="meta-table">
    <tr>
      <th>Delivery Reference</th>
      <td><?= esc_html($delivery->delivery_reference ?? ''); ?></td>
      <th>Dispatch Date</th>
      <td><?= esc_html($delivery->dispatch_date ? date('Y-m-d', strtotime($delivery->dispatch_date)) : ''); ?></td>
    </tr>
    <tr>
      <th>Route</th>
      <td><?= esc_html($delivery->direction_description ?? ''); ?></td>
      <th>Status</th>
      <td><?= esc_html(ucfirst(str_replace('_', ' ', $delivery->status ?? ''))); ?></td>
    </tr>
    <tr>
      <th>Origin</th>
      <td><?= esc_html($delivery->origin_country ?? ''); ?></td>
      <th>Destination</th>
      <td><?= esc_html($delivery->destination_country ?? ''); ?></td>
    </tr>
    <tr>
      <th>Driver</th>
      <td><?= esc_html($delivery->driver_name ?? ''); ?></td>
      <th>Driver Contact</th>
      <td><?= esc_html($delivery->driver_phone ?? ''); ?></td>
    </tr>
  </table>

  <div class="summary">
    <table style="width:100%; border-collapse:collapse; font-size:12px;">
      <tr>
        <td style="padding:8px 12px; border:1px solid #d1d5db; width:25%;">
          <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.4px; color:var(--secondary);">Total Waybills</div>
          <div style="font-size:16px; font-weight:700; color:var(--primary); margin-top:4px;">
            <?= number_format_i18n($totals['waybills']); ?>
          </div>
        </td>
        <td style="padding:8px 12px; border:1px solid #d1d5db; width:25%;">
          <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.4px; color:var(--secondary);">Total Mass (kg)</div>
          <div style="font-size:16px; font-weight:700; color:var(--primary); margin-top:4px;">
            <?= number_format_i18n($totals['mass'], 1); ?>
          </div>
        </td>
        <td style="padding:8px 12px; border:1px solid #d1d5db; width:25%;">
          <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.4px; color:var(--secondary);">Total Volume (m³)</div>
          <div style="font-size:16px; font-weight:700; color:var(--primary); margin-top:4px;">
            <?= number_format_i18n($totals['volume'], 2); ?>
          </div>
        </td>
        <td style="padding:8px 12px; border:1px solid #d1d5db; width:25%;">
          <div style="font-size:10px; text-transform:uppercase; letter-spacing:0.4px; color:var(--secondary);">Invoice Total (ZAR)</div>
          <div style="font-size:16px; font-weight:700; color:var(--primary); margin-top:4px;">
            <?= number_format_i18n($totals['amount'], 2); ?>
          </div>
        </td>
      </tr>
    </table>
  </div>

  <h2>Waybills on Truck</h2>
  <?php if (! empty($waybill_rows)) : ?>
  <?php
  $sorted_waybill_rows = $waybill_rows;
  usort($sorted_waybill_rows, function ($a, $b) {
      return strcmp(strtolower($a['city']), strtolower($b['city']));
  });
  ?>
  <table>
    <thead>
      <tr>
        <th style="width: 12%;">Waybill #</th>
        <th>Customer</th>
        <th style="width: 18%;">City</th>
        <th style="width: 14%;">Status</th>
        <th style="width: 12%; text-align:right;">Mass (kg)</th>
        <th style="width: 12%; text-align:right;">Volume (m³)</th>
        <th style="width: 14%; text-align:right;">Amount (ZAR)</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($sorted_waybill_rows as $item) : ?>
      <tr>
        <td><?= esc_html($item['number']); ?></td>
        <td><?= esc_html($item['customer']); ?></td>
        <td><?= esc_html($item['city']); ?></td>
        <td><?= esc_html($item['status']); ?></td>
        <td style="text-align:right;"><?= number_format_i18n($item['mass'], 1); ?></td>
        <td style="text-align:right;"><?= number_format_i18n($item['volume'], 2); ?></td>
        <td style="text-align:right;"><?= number_format_i18n($item['amount'], 2); ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php else : ?>
    <p class="text-muted">No waybills have been assigned to this delivery.</p>
  <?php endif; ?>

  <p class="text-muted">Generated by Courier Finance Plugin</p>
</body>
</html>
<?php
$html = ob_get_clean();

// Render PDF via Dompdf
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('isFontSubsettingEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = sprintf('delivery-%s.pdf', $delivery->delivery_reference ?: $delivery_id);
$dompdf->stream($filename, ['Attachment' => false]);
exit;

