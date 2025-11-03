<?php
$testing = false;

/**
 * PDF Generator for Waybills/Quotations
 */

// Bootstrap WordPress if accessed directly
if (! defined('ABSPATH')) {
  // Try multiple possible paths to find WordPress root
  $possible_paths = [
    dirname(__FILE__, 4) . '/wp-load.php',  // Plugin -> wp-content -> plugins -> root
    dirname(__FILE__, 3) . '/wp-load.php',  // Plugin -> wp-content -> root
    dirname(__FILE__, 2) . '/wp-load.php',  // Plugin -> root
  ];

  $wp_load_found = false;

  foreach ($possible_paths as $path) {
    if (file_exists($path)) {
      // Suppress errors during WordPress loading
      $old_error_reporting = error_reporting();
      error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR);

      require_once $path;

      // Restore error reporting
      error_reporting($old_error_reporting);
      $wp_load_found = true;
      break;
    }
  }

  if (!$wp_load_found) {
    die('WordPress not found. Please ensure the plugin is installed in the correct directory.');
  }
}

// Color scheme (Settings & Configuration 60/30/10)
$primary_color = '#2563eb';
$secondary_color = '#111827';
$accent_color = '#10b981';
$darkBadge = '#043c7d';
$lightBadge = '#e0e7ef';

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
$pTextColor = $primary_color;
$stroke_color = $primary_color;

// Verify nonce and inputs (temporarily disabled for testing)
$nonce = isset($_GET['pdf_nonce']) ? sanitize_text_field($_GET['pdf_nonce']) : '';
// if (! wp_verify_nonce($nonce, 'pdf_nonce')) {
//   wp_die('Invalid request', 403);
// }

$waybill_no = isset($_GET['waybill_no']) ? intval($_GET['waybill_no']) : 0;
if (! $waybill_no) {
  wp_die('Missing waybill_no');
}

// 🔒 SECURITY: Only authorized administrators (Thando, Mel, Patricia) can access PDFs
if (!class_exists('KIT_User_Roles') || !KIT_User_Roles::can_see_prices()) {
  wp_die('Access denied. PDF access is restricted to authorized administrators only.', 403);
}

// Autoload dompdf
require_once __DIR__ . '/vendor/autoload.php';

// Load waybill with items
$quotation = KIT_Waybills::getFullWaybillWithItems($waybill_no);

if (!$quotation || !isset($quotation->waybill)) {
  wp_die('Waybill not found');
}

// Generate QR code for waybill
$qr_code_data = !empty($quotation->waybill['qr_code_data']) 
    ? $quotation->waybill['qr_code_data'] 
    : KIT_Waybills::generate_qr_code_data($waybill_no);
$qr_code_image = '';
if (!empty($qr_code_data)) {
    $qr_code_image = KIT_Waybills::generate_qr_code_image($qr_code_data, 200);
}


// Convert waybill to array and handle null values
$waybill = (array) $quotation->waybill;
$waybill = array_map(function ($value) {
  return $value === null ? '' : $value;
}, $waybill);

// Convert back to object for compatibility
$waybill = (object) $waybill;

$waybillItems = isset($quotation->items) && is_array($quotation->items) ? $quotation->items : false;


// Company details (name, address, VAT, banking) from DB
global $wpdb;
$company = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}kit_company_details LIMIT 1", ARRAY_A);
// Use stored snapshot where possible to ensure PDF matches UI exactly
$mass_charge = floatval($waybill->mass_charge ?? 0);
$volume_charge = floatval($waybill->volume_charge ?? 0);

$stored_basis = '';
$stored_volume_rate = 0.0;
$stored_mass_rate = 0.0;
$stored_invoice_amount = 0.0;
if (!empty($waybill->miscellaneous)) {
  $md = maybe_unserialize($waybill->miscellaneous);
  if (is_array($md) && isset($md['others'])) {
    $stored_basis = isset($md['others']['used_charge_basis']) ? $md['others']['used_charge_basis'] : '';
    $stored_volume_rate = isset($md['others']['volume_rate_used']) ? floatval($md['others']['volume_rate_used']) : 0.0;
    if (isset($md['others']['mass_rate'])) {
      $stored_mass_rate = floatval($md['others']['mass_rate']);
    }
    if (isset($md['others']['product_invoice_amount_snapshot'])) {
      $stored_invoice_amount = floatval($md['others']['product_invoice_amount_snapshot']);
    }
  }
}

// Priority: Manual override from waybill > Stored snapshot > Automatic selection
$charge_basis = '';
if (!empty($waybill->charge_basis)) {
  // Use manual override from waybill if set
  $charge_basis = $waybill->charge_basis;
} elseif (!empty($stored_basis)) {
  // Use stored snapshot if available
  $charge_basis = $stored_basis;
} else {
  // Automatic selection: use the higher charge
  $charge_basis = ($mass_charge > $volume_charge) ? 'mass' : 'volume';
}

if ($charge_basis == 'mass' || $charge_basis == 'weight') {
  $charge = $mass_charge;
} else {
  $charge = $volume_charge;
}
// Calculate misc_total from waybill miscellaneous data
$misc_total = 0;
$misc_data = '';
$misc_items = [];

if (!empty($waybill->miscellaneous)) {
  $misc_data = maybe_unserialize($waybill->miscellaneous);
  if ($misc_data && isset($misc_data['misc_total'])) {
    $misc_total = floatval($misc_data['misc_total']);
  } elseif ($misc_data && isset($misc_data['misc_items']) && is_array($misc_data['misc_items'])) {
    // Calculate total from misc items if misc_total is not directly available
    foreach ($misc_data['misc_items'] as $item) {
      if (isset($item['misc_price']) && isset($item['misc_quantity'])) {
        $misc_total += floatval($item['misc_price']) * intval($item['misc_quantity']);
      }
    }
  }

  // Safely get misc_items
  if ($misc_data && isset($misc_data['misc_items']) && is_array($misc_data['misc_items'])) {
    $misc_items = $misc_data['misc_items'];
  }
}

// Compute a resilient fallback total in case no snapshot/field exists
$sad500_total = (!empty($waybill->include_sad500) && intval($waybill->include_sad500) === 1)
  ? floatval(KIT_Waybills::sadc_certificate())
  : 0.0;
$sadc_total = (!empty($waybill->include_sadc) && intval($waybill->include_sadc) === 1)
  ? floatval(KIT_Waybills::sad())
  : 0.0;

// Prefer stored international amount if VAT is not included
$intl_amount_for_total = 0.0;
if (empty($waybill->vat_include) || intval($waybill->vat_include ?? 0) === 0) {
  $stored_intl_calc = 0.0;
  if (!empty($waybill->miscellaneous)) {
    $misc_tmp_calc = maybe_unserialize($waybill->miscellaneous);
    if (is_array($misc_tmp_calc) && isset($misc_tmp_calc['others']['international_price_rands'])) {
      $stored_intl_calc = floatval($misc_tmp_calc['others']['international_price_rands']);
    }
  }
  $intl_amount_for_total = $stored_intl_calc > 0
    ? $stored_intl_calc
    : floatval(KIT_Waybills::international_price_in_rands());
}

$items_total = 0.0;
if (!empty($waybillItems)) {
  foreach ($waybillItems as $wi) {
    $qty = isset($wi['quantity']) ? intval($wi['quantity']) : 0;
    $unit = isset($wi['unit_price']) ? floatval($wi['unit_price']) : 0.0;
    $items_total += ($qty * $unit);
  }
}

$transport_total = floatval($charge ?? 0);
$computed_fallback_total = $transport_total + $misc_total + $sad500_total + $sadc_total + $intl_amount_for_total + $items_total;

// Choose the best value to render
$final_total = $stored_invoice_amount > 0
  ? $stored_invoice_amount
  : ((isset($waybill->product_invoice_amount) && floatval($waybill->product_invoice_amount) > 0)
    ? floatval($waybill->product_invoice_amount)
    : $computed_fallback_total);

// Get the image file content and convert it to Base64
$imagePath = plugin_dir_path(__FILE__) . '/icons/pin.png';
$pin = '';
if (file_exists($imagePath)) {
  $imageData = file_get_contents($imagePath);
  if ($imageData !== false) {
    $pin = 'data:image/png;base64,' . base64_encode($imageData);
  }
}

$emailPath = plugin_dir_path(__FILE__) . '/icons/email.png';
$email = '';
if (file_exists($emailPath)) {
  $imageData = file_get_contents($emailPath);
  if ($imageData !== false) {
    $email = 'data:image/png;base64,' . base64_encode($imageData);
  }
}

$webPath = plugin_dir_path(__FILE__) . '/icons/web.png';
$web = '';
if (file_exists($webPath)) {
  $imageData = file_get_contents($webPath);
  if ($imageData !== false) {
    $web = 'data:image/png;base64,' . base64_encode($imageData);
  }
}

$contactPath = plugin_dir_path(__FILE__) . '/icons/contact.png';
$contact = '';
if (file_exists($contactPath)) {
  $imageData = file_get_contents($contactPath);
  if ($imageData !== false) {
    $contact = 'data:image/png;base64,' . base64_encode($imageData);
  }
}

if (!$quotation) {
  wp_die('Quotation not found');
}

// Terms & Conditions: load from WP options with safe fallback
$kit_terms = function_exists('get_option') ? get_option('kit_terms_conditions', '') : '';
if (!is_string($kit_terms)) {
  $kit_terms = '';
}
$default_terms_html = '<ul class="terms-conditions">'
  . '<li>Payment terms: <strong>50% upfront required before dispatch</strong>, the rest on delivery.</li>'
  . '<li>Late payment: <strong>10% penalty</strong> if not paid within 7 days.</li>'
  . '<li>All prices are in <strong>South African Rand (ZAR)</strong>.</li>'
  . '<li>Delivery time: Approximately <strong>14 Days</strong> after dispatch and TRA release.</li>'
  . '<li>Insurance: Basic coverage included, additional coverage available.</li>'
  . '<li>Extra insurance available on request at additional cost.</li>'
  . '</ul>';
$terms_html = trim($kit_terms) !== '' ? $kit_terms : $default_terms_html;

// Create PDF content
ob_start();
?>

<style>
  :root {
    --primary: <?php echo $primary_color; ?>;
    --secondary: <?php echo $secondary_color; ?>;
    --accent: <?php echo $accent_color; ?>;
  }

  @page {
    margin: 8mm 8mm 15mm 8mm;
  }

  /* Standardized typography for PDF */
  body {
    margin: 0;
    padding: 0;
    font-family: Arial, sans-serif;
    font-size: 11px;
    line-height: 1.35;
    color: #333;
  }

  h1,
  h2,
  h3,
  h4 {
    margin: 0;
    color: var(--primary);
    font-weight: 700;
  }

  h4 {
    font-size: 12px;
  }

  table {
    border-collapse: collapse;
    font-size: 10.5px;
  }

  th {
    font-size: 10px;
    font-weight: 700;
  }

  td {
    font-size: 10.5px;
  }

  .smTableStyle {
    font-size: 10px;
  }

  .smTableStyle tr {
    border-right: 1px solid #e0e7ef;
  }

  .smCellStyle {
    font-size: 10px;
  }

  td.cellStyle {
    text-align: left;
    padding: 5px 6px;
    border-bottom: 1px solid #e0e7ef;
  }

  td.cellStyle.alignleft {
    text-align: left;
  }

  td.cellStyle.aligncenter {
    text-align: center;
  }

  td.cellStyle.alignright {
    text-align: right;
  }

  .fstCol {
    width: 70px !important;
  }

  th.thr {
    font-size: 12px;
    padding: 8px 6px;
    width: 130px;
  }

  th.thr:nth-child(2),
  td.cellStyle:nth-child(2) {
    width: 180px !important;
  }

  tr.tr {
    font-size: 10px;
  }

  .pcolor {
    background-color: <?php echo $primary_color; ?>;
  }

  .pTextColor {
    color: <?php echo $primary_color; ?>;
  }

  .wText {
    color: #fff;
  }

  .scolor {
    background-color: <?php echo $secondary_color; ?>;
  }

  .rowTotal>td {
    color: #fff;
    font-weight: 700;
    font-size: 18px;
  }

  /* Ensure the TOTAL row is never split across pages */
  .rowTotal {
    page-break-inside: avoid;
  }

  .small-text {
    font-size: 9px;
  }

  .muted {
    color: #666;
  }

  .total-row td {
    font-size: 14px;
    font-weight: 700;
  }

  .thank-you-text {
    font-size: 10px;
    margin-top: 10px;
    margin-bottom: 10px;
    color: var(--primary);
    font-weight: 600;
    text-align: center;
  }

  /* Tidy Terms & Conditions list */
  .terms-conditions,
  .terms-conditions ul {
    font-size: 10px;
    margin: 0 0 6px 0;
    padding-left: 14px;
    list-style-type: disc;
    list-style-position: outside;
    color: #444;
  }

  .terms-conditions li {
    margin: 2px 0 4px;
    line-height: 1.4;
  }

  .terms-conditions li::marker {
    color: <?php echo $primary_color; ?>;
  }

  /* Keep two-card section compact and on same page */
  .banking-details {
    page-break-inside: avoid;
  }

  .banking-details tr td:nth-child(1) {
    vertical-align: top;
    padding: 0;
    width: 50%;
  }

  .banking-details tr td:nth-child(2) {
    vertical-align: top;
    padding: 0 0 0 12px;
    width: 50%;
    background-color: transparent;
  }

  /* Tighten inner Banking Details table spacing */
  .banking-details table {
    border-collapse: collapse;
  }

  .banking-details table td {
    padding: 2px 4px !important;
  }

  .banking-details table td.dCells {
    padding: 2px 6px 2px 0 !important;
  }

  /* Generic helpers to avoid dompdf overflow/overlap */
  .no-break {
    page-break-inside: avoid;
  }

  [style*='box-shadow'] {
    box-shadow: none !important;
  }

  .pdf-footer {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    height: 12mm;
    text-align: center;
    font-size: 10px;
    color: #6b7280;
    border-top: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
  }
</style>
<!-- Global Footer (repeats on every page) -->
<div class="pdf-footer">Generate using KIT Accounting</div>
<!-- PAGE CONTAINER -->
<table width="100%" cellpadding="3" cellspacing="0" style="max-width:700px; margin:0 auto; font-family:Arial,sans-serif;font-size:12px;color:#333;">
  <tr>
    <td colspan="3">
      <!-- HEADER: LOGO + INVOICE INFO -->
      <table width="100%" cellpadding="3" cellspacing="0" style="margin-bottom:8px; border-bottom:1px solid #e9ecef;">
        <tr>
          <td style="width:40%;vertical-align:middle;">
            <img src="<?php echo plugin_dir_url(__FILE__); ?>/img/logo.jpeg" alt="Company Logo" style="display:block; width:100%; height:auto;">
          </td>
          <td style="width:35%;vertical-align:middle;text-align:right;">
            <div style="font-size:18px;font-weight:bold;color:<?= $pTextColor ?> ;text-transform:uppercase; margin-bottom:4px;">Waybill Invoice</div>
            <div style="font-size:10px;color:#666; padding:6px;">
              <div><strong>Invoice #:</strong> <?= $waybill->product_invoice_number ?></div>
              <div><strong>Date:</strong> <?= date('F j, Y', strtotime($waybill->created_at)) ?></div>
              <div><strong>Tracking #:</strong> <?= $waybill->tracking_number ?></div>
            </div>
          </td>
          <?php if (!empty($qr_code_image)): ?>
          <td style="width:25%;vertical-align:middle;text-align:right;">
            <div style="text-align:center;">
              <img src="<?= $qr_code_image ?>" alt="QR Code" style="width:150px; height:150px; display:block; margin:0 auto;">
              <div style="font-size:9px; color:#666; margin-top:4px; text-align:center;">Scan for details</div>
            </div>
          </td>
          <?php endif; ?>
        </tr>
      </table>

      <!-- ADDRESS ROW: FROM & BILL TO -->
      <table width="100%" cellpadding="0" cellspacing="3" style="margin-bottom:12px;">
        <tr>
          <td style="width:50%; background:#f8f9fa; border:1px solid #e9ecef; border-radius:4px; padding:8px;">
            <div style="font-size:13px;font-weight:bold;color:<?= $pTextColor ?> ;text-transform:uppercase; margin-bottom:4px;">From</div>
            <div style="font-size:10px; line-height:1.3;">
              <?= esc_html($company['company_name'] ?? '') ?><br>
              <?= nl2br(esc_html($company['company_address'] ?? '')) ?><br>
              <?= esc_html($company['company_phone'] ?? '') ?><br>
              <?= esc_html($company['company_email'] ?? '') ?><br>
              VAT: <?= esc_html($company['company_vat_number'] ?? '') ?>
            </div>
          </td>
          <td style="width:50%;background:#f8f9fa;border:1px solid #e9ecef;border-radius:4px;padding:8px;">
            <div style="font-size:13px;font-weight:bold;color:<?= $pTextColor ?> ;text-transform:uppercase; margin-bottom:4px;">Bill To</div>
            <div style="font-size:10px; line-height:1.3;">
              <strong><?= $waybill->company_name ?></strong><br>
              <?= $waybill->customer_name . " " . $waybill->customer_surname ?><br>
              <?= ($waybill->address) ?? 'No Address' ?><br>
              <?= $waybill->origin_country ?><br>
              Email: <?= ($waybill->email_address) ?? 'customer@customer.com' ?><br>
              Cell: <?= $waybill->customer_cell ?? 'N/A' ?><br>
              Destination: <?= $waybill->route_description . ', ' . $waybill->customer_city ?>
            </div>
          </td>
        </tr>
      </table>

      <!-- DESCRIPTION -->
      <table width="100%" cellpadding="3" cellspacing="0" style="margin-bottom:12px;">
        <tr>
          <td style="padding:4px 0; border-bottom:1px solid #e9ecef;">
            <h4 style="color: <?= $pTextColor ?> ; margin: 0 0 3px 0; font-size:12px;">Description</h4>
            <p style="margin: 0; font-size:10px;"><?= $misc_data['others']['waybill_description'] ?></p>
          </td>
        </tr>
      </table>

      <!-- SHIPPING DETAILS TABLE -->
      <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom:12px;border-collapse:collapse;">
        <tr>
          <td colspan="5" style="font-size:12px;font-weight:bold;color:<?= $pTextColor ?> ;padding-bottom:4px;">Shipping Details</td>
        </tr>
        <tr class="scolor" style="color:#fff;font-size:10px;">
          <th style="text-align:left; padding:4px;">Waybill #</th>
          <th style="text-align:left; padding:4px;">Dispatch Date</th>
          <th style="text-align:center; padding:4px;">Dimensions (cm)</th>
          <th style="text-align:center; padding:4px;"><?= ($charge_basis == 'mass' || $charge_basis == 'weight') ? 'Mass (kg)' : 'Volume (m³)' ?></th>
          <th style="text-align:center; padding:4px;">Charge Basis</th>
        </tr>
        <tr style="font-size:10px; border-bottom:1px solid #e9ecef;">
          <td style="padding:4px;"><?= isset($waybill->waybill_no) ? esc_html($waybill->waybill_no) : 'N/A' ?></td>
          <td style="padding:4px;"><?= (isset($waybill->dispatch_date) && $waybill->dispatch_date) ? date('M j, Y', strtotime($waybill->dispatch_date)) : 'TBD' ?></td>
          <td style="text-align:center; padding:4px;">
            <?= isset($waybill->item_length) ? $waybill->item_length : '0' ?> ×
            <?= isset($waybill->item_width) ? $waybill->item_width : '0' ?> ×
            <?= isset($waybill->item_height) ? $waybill->item_height : '0' ?>
          </td>
          <td style="text-align:center; padding:4px;">
            <?php if ($charge_basis == 'mass' || $charge_basis == 'weight'): ?>
              <?= isset($waybill->total_mass_kg) ? number_format($waybill->total_mass_kg, 2) : '0.00' ?>
            <?php else: ?>
              <?php
              $length = isset($waybill->item_length) ? floatval($waybill->item_length) : 0;
              $width = isset($waybill->item_width) ? floatval($waybill->item_width) : 0;
              $height = isset($waybill->item_height) ? floatval($waybill->item_height) : 0;
              echo number_format($length * $width * $height / 1000000, 3);
              ?>
            <?php endif; ?>
          </td>
          <td style="text-align:center; padding:4px;"><?= ucfirst($charge_basis) ?></td>
        </tr>
      </table>

      <!-- Charges Breakdown - Enhanced UI/UX -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:5px;">
        <tr>
          <td colspan="5" style="font-size:12px;font-weight:bold;color:<?= $pTextColor ?> ;padding-bottom:4px;">Charges Breakdown</td>
        </tr>
        <tr class="scolor" style="color:#fff;font-size:11px;">
          <th align="left" class="thr">Description</th>
          <th class="thr" align="left">Item</th>
          <th <?= (!empty($waybillItems)) ? 'colspan="2"' : '' ?> class="thr" align="center" style="width: 50px;">Qty</th>
          <th class="thr" align="right" style="width: 100px;">Price (R)</th>
          <th class="thr" align="right" style="width: 100px;">Subtotal (R)</th>
        </tr>
        <?php
        $charge_type = ($charge_basis == 'mass' || $charge_basis == 'weight') ? 'Mass' : 'Volume';
        $charge_amount = ($charge_basis == 'mass' || $charge_basis == 'weight') ? $waybill->mass_charge : $waybill->volume_charge;
        ?>
        <tr style="font-size:13px; background:#f9fafb;">
          <td class="cellStyle fstCol">
            <span style="display:inline-block;background:<?= $lightBadge ?>; color:<?= $pTextColor ?>; font-size:11px;padding:2px 8px;border-radius:6px;font-weight:600;">Transport</span>
          </td>
          <td class="cellStyle">
            <span style="font-weight:600;"><?= $charge_type ?> Charge</span>
          </td>
          <td class="cellStyle aligncenter" <?= (!empty($waybillItems)) ? 'colspan="2"' : '' ?>>
            <?php if ($charge_basis == 'mass' || $charge_basis == 'weight'): ?>
              <?= isset($waybill->total_mass_kg) ? number_format($waybill->total_mass_kg, 2) . ' kg' : '0.00 kg' ?>
            <?php else: ?>
              <?php
              $length = isset($waybill->item_length) ? floatval($waybill->item_length) : 0;
              $width = isset($waybill->item_width) ? floatval($waybill->item_width) : 0;
              $height = isset($waybill->item_height) ? floatval($waybill->item_height) : 0;
              echo number_format($length * $width * $height / 1000000, 3) . ' m³';
              ?>
            <?php endif; ?>
          </td>
          <td class="cellStyle" style="text-align:right;">
            <?php
            // Display the rate (either mass or volume rate) from stored snapshot first
            if ($charge_basis == 'mass' || $charge_basis == 'weight') {
              $rate = $stored_mass_rate > 0 ? $stored_mass_rate : ((!empty($misc_data) && isset($misc_data['others']['mass_rate'])) ? floatval($misc_data['others']['mass_rate']) : 0);
              if (!$rate && !empty($waybill->mass_charge) && !empty($waybill->total_mass_kg) && $waybill->total_mass_kg > 0) {
                $rate = $waybill->mass_charge / $waybill->total_mass_kg;
              }
              echo 'R ' . number_format($rate, 2) . '/kg';
            } else {
              $rate = $stored_volume_rate > 0 ? $stored_volume_rate : 0;
              if (!$rate && !empty($waybill->volume_charge) && !empty($waybill->total_volume) && $waybill->total_volume > 0) {
                $rate = $waybill->volume_charge / $waybill->total_volume;
              }
              echo 'R ' . number_format($rate, 2) . '/m³';
            }
            ?>
          </td>
          <td class="cellStyle" style="font-weight:600; text-align:right;"><?= number_format($charge_amount, 2) ?></td>
        </tr>
        <?php if (!empty($misc_items)): ?>
          <?php foreach ($misc_items as $item): ?>
            <tr style="font-size:12px; background:#f9fafb; border-bottom:1px solid #e0e7ef;">
              <td class="cellStyle fstCol">
                <span style="display:inline-block;background:<?= $lightBadge ?>;color:#64748b;font-size:11px;padding:2px 8px;border-radius:6px;">Miscellaneous</span>
              </td>
              <td class="cellStyle"><?= htmlspecialchars($item['misc_item']) ?></td>
              <td class="cellStyle" <?= (!empty($waybillItems)) ? 'colspan="2"' : '' ?> style="text-align:center;"><?= intval($item['misc_quantity']) ?></td>
              <td class="cellStyle" style="text-align:right;"><?= number_format($item['misc_price'], 2) ?></td>
              <td class="cellStyle" style="text-align:right; font-weight: 700;"><?= number_format($item['misc_quantity'] * $item['misc_price'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!empty($waybill->include_sad500) && $waybill->include_sad500 == 1): ?>
          <tr style="font-size:13px; background:#f9fafb; border-bottom:1px solid #e0e7ef;">
            <td class="cellStyle fstCol">
              <span style="display:inline-block;background:<?= $lightBadge ?>;color:#64748b;font-size:11px;padding:2px 8px;border-radius:6px;">Processing</span>
            </td>
            <td class="cellStyle">SAD500</td>
            <td class="cellStyle aligncenter" <?= (!empty($waybillItems)) ? 'colspan="2"' : '' ?>>1</td>
            <td class="cellStyle alignright "><?= KIT_Waybills::sadc_certificate() ?></td>
            <td class="cellStyle" style="text-align:right; font-weight: 700;"><?= KIT_Waybills::sadc_certificate() ?></td>
          </tr>
        <?php endif ?>
        <?php if (!empty($waybill->include_sadc) && $waybill->include_sadc == 1): ?>
          <tr style="font-size:13px; background:#f9fafb; border-bottom:1px solid #e0e7ef;">
            <td class="cellStyle fstCol">
              <span style="display:inline-block;background:<?= $lightBadge ?>;color:#64748b;font-size:11px;padding:2px 8px;border-radius:6px;">Processing</span>
            </td>
            <td class="cellStyle">SADC Certificate</td>
            <td class="cellStyle aligncenter" <?= (!empty($waybillItems)) ? 'colspan="2"' : '' ?>>1</td>
            <td class="cellStyle alignright"><?= KIT_Waybills::sad() ?></td>
            <td class="cellStyle" style="text-align:right; font-weight: 700;"><?= KIT_Waybills::sad() ?></td>
          </tr>
        <?php endif; ?>
        <?php // Show Agent Clearing & Documentation when VAT is NOT selected, preferring stored snapshot amount 
        ?>
        <?php if (empty($waybill->vat_include) || intval($waybill->vat_include ?? 0) === 0): ?>
          <?php
          $stored_intl = 0.0;
          if (!empty($waybill->miscellaneous)) {
            $misc_tmp = maybe_unserialize($waybill->miscellaneous);
            if (is_array($misc_tmp) && isset($misc_tmp['others']['international_price_rands'])) {
              $stored_intl = floatval($misc_tmp['others']['international_price_rands']);
            }
          }
          $intl_amount = $stored_intl > 0 ? $stored_intl : KIT_Waybills::international_price_in_rands();
          ?>
          <tr style="font-size:13px; background:#f9fafb; border-bottom:1px solid #e0e7ef;">
            <td class="cellStyle fstCol">
              <span style="display:inline-block;background:<?= $lightBadge ?>;color:#64748b;font-size:11px;padding:2px 8px;border-radius:6px;">Customs Clearing</span>
            </td>
            <td class="cellStyle">Agent Clearing & Documentation</td>
            <td class="cellStyle aligncenter" <?= (!empty($waybillItems)) ? 'colspan="2"' : '' ?>>1</td>
            <td class="cellStyle alignright"><?= KIT_Waybills::international_price_in_rands() ?></td>
            <td class="cellStyle" style="text-align:right; font-weight: 700;"><?= number_format($intl_amount, 2) ?></td>
          </tr>
        <?php endif; ?>
        <?php if (!empty($waybillItems)): ?>
          <tr style="font-size:12px;" class="scolor">
            <td <?= (!empty($waybillItems)) ? 'colspan="2"' : '' ?> style="text-align:left; padding:9px 6px; border-bottom:1px solid #e0e7ef; width: 120px;">
              <span style="display:inline-block; background: <?= $darkBadge ?>; color:#fff; font-weight: 700; font-size:11px;padding:2px 8px;border-radius:6px;">Border Clearing</span>
            </td>
            <td <?= (!empty($waybillItems)) ? 'colspan="4"' : 'colspan="3"' ?> align="right" style="font-weight: 700; padding:9px 6px; border-bottom:1px solid #e0e7ef;">
              <!-- <?= KIT_Waybills::vatCharge($waybill->waybill_items_total) ?> -->
            </td>
          </tr>
          <?php $show_vat_column = (!empty($waybill->vat_include) && intval($waybill->vat_include) === 1); ?>
          <?php foreach ($waybillItems as $item): ?>
            <tr style="background:#f9fafb; border-bottom:1px solid #e0e7ef;">
              <td class="CellStyle">
                <span style="display:inline-block;background:<?= $lightBadge ?>; color:<?= $pTextColor ?>;font-size:11px;padding:2px 8px;border-radius:6px;">Border Clearing</span>
              </td>
              <td class="CellStyle"><?= htmlspecialchars($item['item_name']) ?></td>
              <td class="CellStyle" style="text-align:center;"><?= intval($item['quantity']) ?></td>
              <td class="CellStyle" style="text-align:right;"><?= number_format($item['unit_price'], 2) ?></td>
              <td class="CellStyle" style="text-align:right;"><?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
              <?php if ($show_vat_column): ?>
                <td class="CellStyle" style="text-align:right; font-weight: 700;"><?= number_format(($item['quantity'] * $item['unit_price']) * (KIT_Waybills::vatRate() / 100), 2) ?></td>
              <?php else: ?>
                <td class="CellStyle" style="text-align:right; font-weight: 700;"></td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        <!-- TOTAL ROW -->
        <tr class="rowTotal pcolor">
          <td class="cellStyle" style="font-size:18px; font-weight:700;">TOTAL</td>
          <td class="cellStyle" <?= (!empty($waybillItems)) ? 'colspan="5"' : 'colspan="4"' ?> style="text-align:right;">R <?= number_format($final_total, 2) ?></td>
        </tr>
      </table>

      <!-- Modern Banking & Terms Section -->
      <table class="banking-details" width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <!-- Banking Details Card -->
          <td>
            <div style="background:#f8fafc; border-radius:8px; box-shadow:0 2px 8px rgba(37,99,235,0.07); padding:18px 20px 14px 20px; border:1.5px solid #e0e7ef;">
              <div style="font-size:13px; font-weight:700; color:<?= $pTextColor ?> ; margin-bottom:10px; letter-spacing:0.5px; display:flex; align-items:center;">
                <svg width="18" height="18" style="margin-right:7px;" fill="none" stroke="<?= $stroke_color ?>" stroke-width="2" viewBox="0 0 24 24">
                  <rect x="3" y="7" width="18" height="10" rx="2" />
                  <path d="M3 10h18" />
                </svg>
                Banking Details
              </div>
              <table width="100%">
                <?php if (!empty($company['bank_name'])): ?>
                  <tr>
                    <td class="dCells"><span style="color:#666;">Bank</span></td>
                    <td style="text-align:right; font-weight:600;"><?= esc_html($company['bank_name']); ?></td>
                  </tr>
                <?php endif; ?>
                <?php if (!empty($company['account_holder'])): ?>
                  <tr>
                    <td class="dCells"><span style="color:#666;">Account Holder</span></td>
                    <td style="text-align:right;"><?= esc_html($company['account_holder']); ?></td>
                  </tr>
                <?php endif; ?>
                <?php if (!empty($company['account_number'])): ?>
                  <tr>
                    <td class="dCells"><span style="color:#666;">Account #</span></td>
                    <td style="text-align:right; letter-spacing:1px;"><?= esc_html($company['account_number']); ?></td>
                  </tr>
                <?php endif; ?>
                <?php if (!empty($company['branch_code'])): ?>
                  <tr>
                    <td class="dCells"><span style="color:#666;">Branch Code</span></td>
                    <td style="text-align:right;"><?= esc_html($company['branch_code']); ?></td>
                  </tr>
                <?php endif; ?>
                <?php if (!empty($company['account_type'])): ?>
                  <tr>
                    <td class="dCells"><span style="color:#666;">Account Type</span></td>
                    <td style="text-align:right;"><?= esc_html($company['account_type']); ?></td>
                  </tr>
                <?php endif; ?>
                <?php if (!empty($company['swift_code'])): ?>
                  <tr>
                    <td class="dCells"><span style="color:#666;">SWIFT</span></td>
                    <td style="text-align:right;"><?= esc_html($company['swift_code']); ?></td>
                  </tr>
                <?php endif; ?>
                <?php if (!empty($company['iban'])): ?>
                  <tr>
                    <td class="dCells"><span style="color:#666;">IBAN</span></td>
                    <td style="text-align:right;"><?= esc_html($company['iban']); ?></td>
                  </tr>
                <?php endif; ?>
              </table>
            </div>
          </td>
          <!-- Terms & Conditions Card -->
          <td>
            <div style="background:#f8fafc; border-radius:8px; box-shadow:0 2px 8px rgba(37,99,235,0.07); padding:18px 20px 14px 20px; border:1.5px solid #e0e7ef;">
              <div style="font-size:13px; font-weight:700; color:<?= $pTextColor ?> ; margin-bottom:10px; letter-spacing:0.5px; display:flex; align-items:center;">
                <svg width="18" height="18" style="margin-right:7px;" fill="none" stroke="<?= $stroke_color ?>" stroke-width="2" viewBox="0 0 24 24">
                  <rect x="4" y="4" width="16" height="16" rx="2" />
                  <path d="M8 8h8M8 12h8M8 16h4" />
                </svg>
                Terms &amp; Conditions
              </div>
              <div class="terms-conditions" style="padding-left:0; list-style-position:inside;">
                <?= $terms_html ?>
              </div>

            </div>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>
<?php
$html = ob_get_clean();
// In testing mode, output raw HTML for visual debugging instead of generating a PDF
if (isset($testing) && $testing) {
  header('Content-Type: text/html; charset=UTF-8');
  echo $html;
  exit;
}
// Create PDF
use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('isFontSubsettingEnabled', true); // Reduce file size
$options->set('defaultFont', 'CustomFont'); // Fallback font

$dompdf = new Dompdf($options); // Initialize Dompdf FIRST
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF
$dompdf->stream("waybill-{$waybill_no}.pdf", [
  "Attachment" => false
]);
exit;
