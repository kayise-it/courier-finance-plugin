<?php
$testing = 0;

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

// Color scheme (Settings & Configuration 60/30/10) - Load from dashboard settings
$primary_color = '#2563eb';
$secondary_color = '#111827';
$accent_color = '#10b981';
$darkBadge = '#043c7d';
$lightBadge = '#e0e7ef';

// Always try to load from colorSchema.json first
$schema_path = plugin_dir_path(__FILE__) . 'colorSchema.json';
if (file_exists($schema_path)) {
  $schema_data = json_decode(file_get_contents($schema_path), true);
  if (is_array($schema_data)) {
    if (!empty($schema_data['primary'])) {
      $primary_color = $schema_data['primary'];
    }
    if (!empty($schema_data['secondary'])) {
      $secondary_color = $schema_data['secondary'];
    }
    if (!empty($schema_data['accent'])) {
      $accent_color = $schema_data['accent'];
    }
  }
}

// Fallback to KIT_Commons methods if available
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

// Ensure user is logged in and has proper session
if (!function_exists('is_user_logged_in') || !is_user_logged_in()) {
  wp_die('You must be logged in to access PDFs. Please log in and try again.', 403);
}

// 🔒 SECURITY: Only authorized administrators (Thando, Mel, Patricia) can access PDFs
if (!class_exists('KIT_User_Roles') || !KIT_User_Roles::can_see_prices()) {
  $current_user = wp_get_current_user();
  $username = isset($current_user->user_login) ? strtolower($current_user->user_login) : 'not logged in';
  wp_die('Access denied. PDF access is restricted to authorized administrators only. Current user: ' . $username, 403);
}

// Autoload dompdf
$vendor_autoload = __DIR__ . '/vendor/autoload.php';
if (!file_exists($vendor_autoload)) {
  wp_die('Error: Vendor autoload file not found. Please run composer install.', 500);
}
require_once $vendor_autoload;

// Load waybill with items
if (!class_exists('KIT_Waybills')) {
  wp_die('Error: KIT_Waybills class not found.', 500);
}

$quotation = KIT_Waybills::getFullWaybillWithItems($waybill_no);

if (!$quotation || !isset($quotation->waybill)) {
  wp_die('Waybill ' . $waybill_no . ' not found in database.', 404);
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

// NEW: Read calculated totals from DB columns first (faster, more reliable)
$misc_total_from_db = isset($waybill->misc_total) ? floatval($waybill->misc_total) : null;
$border_clearing_total_from_db = isset($waybill->border_clearing_total) ? floatval($waybill->border_clearing_total) : null;
$sad500_total_from_db = isset($waybill->sad500_amount) ? floatval($waybill->sad500_amount) : null;
$sadc_total_from_db = isset($waybill->sadc_amount) ? floatval($waybill->sadc_amount) : null;
$international_price_from_db = isset($waybill->international_price_rands) ? floatval($waybill->international_price_rands) : null;

$stored_basis = '';
$stored_volume_rate = 0.0;
$stored_mass_rate = 0.0;
if (!empty($waybill->miscellaneous)) {
  $md = maybe_unserialize($waybill->miscellaneous);
  if (is_array($md) && isset($md['others'])) {
    $stored_basis = isset($md['others']['used_charge_basis']) ? $md['others']['used_charge_basis'] : '';
    $stored_volume_rate = isset($md['others']['volume_rate_used']) ? floatval($md['others']['volume_rate_used']) : 0.0;
    if (isset($md['others']['mass_rate'])) {
      $stored_mass_rate = floatval($md['others']['mass_rate']);
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
// Use DB column only (no calculations)
$misc_total = ($misc_total_from_db !== null) ? floatval($misc_total_from_db) : 0.0;
$misc_data = [];
$misc_items = [];

// Only unserialize miscellaneous for display purposes (misc_items array)
if (!empty($waybill->miscellaneous)) {
  $misc_data = maybe_unserialize($waybill->miscellaneous);
  if (is_array($misc_data) && isset($misc_data['misc_items']) && is_array($misc_data['misc_items'])) {
    $misc_items = $misc_data['misc_items'];
  }
}

// Use DB columns only (no calculations)
$sad500_total = 0.0;
if (!empty($waybill->include_sad500)) {
  if ($sad500_total_from_db !== null && $sad500_total_from_db > 0) {
    $sad500_total = floatval($sad500_total_from_db);
  } elseif (class_exists('KIT_Waybills')) {
    // Fallback to configured/default SAD500 charge when DB snapshot is missing/zero
    $sad500_total = floatval(KIT_Waybills::sadc_certificate());
  }
}

$sadc_total = 0.0;
if (!empty($waybill->include_sadc)) {
  if ($sadc_total_from_db !== null && $sadc_total_from_db > 0) {
    $sadc_total = floatval($sadc_total_from_db);
  } elseif (class_exists('KIT_Waybills')) {
    // Fallback to configured/default SADC charge when DB snapshot is missing/zero
    $sadc_total = floatval(KIT_Waybills::sad());
  }
}

// International price / Agent Clearing: prefer DB snapshot, but if it's zero/missing
// while the final_total includes an extra component beyond primary + misc + SAD/SADC,
// derive it as the remainder so the PDF matches the DB total.
$intl_amount_for_total = 0.0;
if (empty($waybill->vat_include) && $international_price_from_db !== null && $international_price_from_db > 0) {
  $intl_amount_for_total = floatval($international_price_from_db);
}

// Border clearing header total (black row): prefer stored DB snapshot, but if it's
// missing or zero, fall back to sum of the 10% border clearing amounts.
$border_clearing_10_percent_total = ($border_clearing_total_from_db !== null) ? floatval($border_clearing_total_from_db) : 0.0;
if ($border_clearing_10_percent_total <= 0 && !empty($waybillItems)) {
  $recalc_border_total = 0.0;
  foreach ($waybillItems as $it) {
    if (is_object($it)) {
      $it = (array)$it;
    }
    $qty  = isset($it['quantity']) ? intval($it['quantity']) : 0;
    $unit = isset($it['unit_price']) ? floatval($it['unit_price']) : 0.0;
    if ($qty > 0 && $unit > 0) {
      $recalc_border_total += ($qty * $unit) * 0.10; // 10% of each border clearing line
    }
  }
  $border_clearing_10_percent_total = $recalc_border_total;
}
$items_total = floatval($waybill->waybill_items_total ?? 0.0);

// Get items for display only (calculate ten_percent for display)
if (!empty($waybillItems)) {
  foreach ($waybillItems as $key => $wi) {
    if (is_object($wi)) {
      $waybillItems[$key] = (array) $wi;
      $wi = $waybillItems[$key];
    }
    $qty = isset($wi['quantity']) ? intval($wi['quantity']) : 0;
    $unit = isset($wi['unit_price']) ? floatval($wi['unit_price']) : 0.0;
    $subtotal = $qty * $unit;
    $waybillItems[$key]['ten_percent'] = $subtotal * 0.10; // For display only
  }
}

// Use product_invoice_amount from DB as baseline
$final_total = isset($waybill->product_invoice_amount)
  ? KIT_Waybills::normalize_amount($waybill->product_invoice_amount)
  : 0.0;

// If VAT is not included and there is no explicit international price stored,
// but the DB total includes more than primary + misc + SAD/SADC, treat the
// remainder as the international price so the "Agent Clearing" line matches
// the DB-backed final_total.
if (empty($waybill->vat_include) && $intl_amount_for_total <= 0) {
  $base_components = $charge + $misc_total + $sad500_total + $sadc_total;
  $remainder = $final_total - $base_components;
  if ($remainder > 0.01) {
    $intl_amount_for_total = $remainder;
  }
}

// Border Clearing (VAT) and Agent Clearing are mutually exclusive in the PDF.
$show_border_clearing_vat = !empty($waybill->vat_include) && intval($waybill->vat_include) === 1;
$show_agent_clearing = !$show_border_clearing_vat;
$display_total = $charge + $misc_total + $sad500_total + $sadc_total + $intl_amount_for_total + ($show_border_clearing_vat ? $border_clearing_10_percent_total : 0.0);

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
    margin: 8mm 8mm 14mm 8mm;
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

  .invoice-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 12px;
  }

  .charges-table {
    table-layout: fixed;
    width: 100%;
  }

  .invoice-table th {
    background-color: <?php echo $secondary_color; ?>;
    color: #fff;
    border: 1px solid #d1d5db;
    padding: 6px 4px;
  }

  .invoice-table td {
    background: #fff;
    border: 1px solid #d1d5db;
    padding: 6px 4px;
  }

  /* Explicit column sizing for the 4-column charges table */
  .charges-table col.item-col {
    width: 59%;
  }

  .charges-table col.qty-col {
    width: 9%;
  }

  .charges-table col.price-col,
  .charges-table col.subtotal-col {
    width: 16%;
  }

  .charges-table th:nth-child(1),
  .charges-table td:nth-child(1) {
    width: 59% !important;
  }

  .charges-table th:nth-child(2),
  .charges-table td:nth-child(2) {
    width: 9% !important;
  }

  .charges-table th:nth-child(3),
  .charges-table td:nth-child(3),
  .charges-table th:nth-child(4),
  .charges-table td:nth-child(4) {
    width: 16% !important;
  }

  .charges-table th:nth-child(2),
  .charges-table td:nth-child(2) {
    white-space: nowrap;
    text-align: center;
  }

  .charges-table th:nth-child(3),
  .charges-table td:nth-child(3),
  .charges-table th:nth-child(4),
  .charges-table td:nth-child(4) {
    white-space: nowrap;
    text-align: right;
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
    padding: 6px 6px;
    border: 1px solid #d1d5db;
    background: #fff;
    white-space: normal;
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
    white-space: nowrap;
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
    background-color: <?php echo $primary_color; ?> !important;
    border-color: <?php echo $primary_color; ?> !important;
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

  .fine-print {
    font-size: 8px;
    color: #6b7280;
    line-height: 1.35;
    margin-top: 8px;
    border-top: 1px solid #e5e7eb;
    padding-top: 5px;
  }
</style>
<!-- PAGE CONTAINER -->
<table width="100%" cellpadding="3" cellspacing="0" style="max-width:700px; margin:0 auto; font-family:Arial,sans-serif;font-size:12px;color:#333;">
  <tr>
    <td colspan="3">
      <!-- HEADER: LOGO + INVOICE INFO -->
      <table width="100%" cellpadding="3" cellspacing="0" style="margin-bottom:8px; border-bottom:1px solid #e9ecef;">
        <tr>
          <td style="width:32%;vertical-align:top; padding-top:2px;">
            <img src="<?php echo plugin_dir_url(__FILE__); ?>/img/logo.png" alt="Company Logo" style="display:block; width:230px; max-width:100%; height:auto;">
          </td>
          <td style="width:43%;vertical-align:top;text-align:right;">
            <div style="font-size:18px;font-weight:bold;color:<?= $pTextColor ?> ;text-transform:uppercase; margin-bottom:4px;">Waybill Invoice</div>
            <div style="font-size:10px;color:#666; padding:6px;">
              <div><strong>Invoice #:</strong> <?= $waybill->product_invoice_number ?></div>
              <div><strong>Date:</strong> <?= date('F j, Y', strtotime($waybill->created_at)) ?></div>
              <div><strong>Tracking #:</strong> <?= $waybill->tracking_number ?></div>
              <?php
              $invoice_status = isset($waybill->status) ? trim((string)$waybill->status) : '';
              $approval_status = isset($waybill->approval) ? trim((string)$waybill->approval) : '';
              $invoice_labels = ['pending' => 'Pending', 'invoiced' => 'Invoiced', 'rejected' => 'Rejected', 'completed' => 'Completed'];
              $approval_labels = ['pending' => 'Not Approved', 'approved' => 'Approved', 'rejected' => 'Rejected', 'completed' => 'Completed'];
              $inv_label = $invoice_labels[$invoice_status] ?? $invoice_status ?: 'Pending';
              $app_label = $approval_labels[$approval_status] ?? $approval_status ?: 'Not Approved';
              ?>
              <div style="margin-top:6px; padding-top:4px; border-top:1px solid #e9ecef;">
                <span style="color:#333;"><strong>Invoice Status:</strong> <?= esc_html($inv_label) ?></span>
                <span style="margin-left:10px; color:#333;"><strong>Approval Status:</strong> <?= esc_html($app_label) ?></span>
              </div>
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
          <td style="width:50%; background:transparent; border:0; border-radius:0; padding:0;">
            <div style="font-size:13px;font-weight:bold;color:<?= $pTextColor ?> ;text-transform:uppercase; margin-bottom:4px;">From</div>
            <div style="font-size:10px; line-height:1.3;">
              <?= esc_html($company['company_name'] ?? '') ?><br>
              <?= nl2br(esc_html($company['company_address'] ?? '')) ?><br>
              <?= esc_html($company['company_phone'] ?? '') ?><br>
              <?= esc_html($company['company_email'] ?? '') ?><br>
              VAT: <?= esc_html($company['company_vat_number'] ?? '') ?>
            </div>
          </td>
          <td style="width:50%;background:transparent;border:0;border-radius:0;padding:0;">
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

      <!-- CHARGES BREAKDOWN - TABLE 1: Transport, Processing, Customs Clearing -->
      <div style="font-size:12px;font-weight:bold;color:<?= $pTextColor ?>;padding-bottom:4px;">Charges Breakdown</div>
      <table class="invoice-table charges-table" cellpadding="0" cellspacing="0" style="margin-bottom:0;">
        <colgroup>
          <col class="item-col">
          <col class="qty-col">
          <col class="price-col">
          <col class="subtotal-col">
        </colgroup>
        <tr class="scolor" style="color:#fff;font-size:11px;">
          <th align="left">Item</th>
          <th align="center">Qty</th>
          <th align="right">Price (R)</th>
          <th align="right">Subtotal (R)</th>
        </tr>
        
        <?php
        $charge_type = ($charge_basis == 'mass' || $charge_basis == 'weight') ? 'Mass' : 'Volume';
        $charge_amount = ($charge_basis == 'mass' || $charge_basis == 'weight') ? $waybill->mass_charge : $waybill->volume_charge;
        $charge_type_text = $charge_type . ' Charge';
        ?>
        <!-- Transport -->
        <tr style="font-size:13px;">
          <td class="cellStyle" style="white-space: nowrap;"><span style="font-weight:600;">Transport - <?= $charge_type_text ?></span></td>
          <td class="cellStyle" style="text-align:center;">
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
          <td class="cellStyle" style="font-weight:700; text-align:right;">R <?= number_format($charge_amount, 2) ?></td>
        </tr>
        
        <!-- Miscellaneous Items -->
        <?php if (!empty($misc_items)): ?>
          <?php foreach ($misc_items as $item): ?>
            <?php 
            $misc_item_name = htmlspecialchars($item['misc_item'] ?? '');
            ?>
            <tr style="font-size:12px;">
              <td class="cellStyle" style="white-space: nowrap;">Miscellaneous - <?= $misc_item_name ?></td>
              <td class="cellStyle" style="text-align:center;"><?= intval($item['misc_quantity']) ?></td>
              <td class="cellStyle" style="text-align:right;"><?= number_format($item['misc_price'], 2) ?></td>
              <td class="cellStyle" style="text-align:right; font-weight: 700;">R <?= number_format($item['misc_quantity'] * $item['misc_price'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- SAD500 -->
        <?php if (!empty($waybill->include_sad500) && $waybill->include_sad500 == 1): ?>
          <tr style="font-size:13px;">
            <td class="cellStyle">Processing - SAD500</td>
            <td class="cellStyle" style="text-align:center;">1</td>
            <td class="cellStyle" style="text-align:right;"><?= number_format($sad500_total, 2) ?></td>
            <td class="cellStyle" style="text-align:right; font-weight: 700;">R <?= number_format($sad500_total, 2) ?></td>
          </tr>
        <?php endif; ?>
        
        <!-- SADC Certificate -->
        <?php if (!empty($waybill->include_sadc) && $waybill->include_sadc == 1): ?>
          <tr style="font-size:13px;">
            <td class="cellStyle">Processing - SADC Certificate</td>
            <td class="cellStyle" style="text-align:center;">1</td>
            <td class="cellStyle" style="text-align:right;"><?= number_format($sadc_total, 2) ?></td>
            <td class="cellStyle" style="text-align:right; font-weight: 700;">R <?= number_format($sadc_total, 2) ?></td>
          </tr>
        <?php endif; ?>
        
        <!-- Agent Clearing & Documentation -->
        <?php if ($show_agent_clearing && $intl_amount_for_total > 0): ?>
          <?php
          // Use already calculated intl_amount_for_total (from DB or fallback)
          $intl_amount = $intl_amount_for_total;
          // Display rate for reference (calculate from stored value or use default)
          $intl_display_rate = $intl_amount > 0 ? $intl_amount : KIT_Waybills::international_price_in_rands();
          $agent_clearing_text = 'Agent Clearing & Documentation';
          ?>
          <tr style="font-size:13px;">
            <td class="cellStyle" style="white-space: nowrap;">Customs Clearing - <?= $agent_clearing_text ?></td>
            <td class="cellStyle" style="text-align:center;">1</td>
            <td class="cellStyle" style="text-align:right;"><?= number_format($intl_display_rate, 2) ?></td>
            <td class="cellStyle" style="text-align:right; font-weight: 700;">R <?= number_format($intl_amount, 2) ?></td>
          </tr>
        <?php endif; ?>
      </table>
      
      <!-- CHARGES BREAKDOWN - TABLE 2: Border Clearing Items -->
      <?php if ($show_border_clearing_vat && !empty($waybillItems)): ?>
      <table class="invoice-table" cellpadding="0" cellspacing="0" style="margin-bottom:5px;">
        <tr class="scolor" style="font-size:11px;">
          <th align="left" class="thr" style="width:18%; white-space: nowrap;">Description</th>
          <th class="thr" align="left" style="width:32%; white-space: nowrap;">Item</th>
          <th class="thr" align="center" style="width:12%; white-space: nowrap;">Qty</th>
          <th class="thr" align="right" style="width:12%; white-space: nowrap;">Price (R)</th>
          <th class="thr" align="right" style="width:13%; white-space: nowrap;">Subtotal (R)</th>
          <th class="thr" align="right" style="width:13%; white-space: nowrap;">10% (R)</th>
        </tr>
        <?php foreach ($waybillItems as $key => $item): ?>
          <?php 
          // Ensure item is an array
          if (is_object($item)) {
            $item = (array) $item;
            $waybillItems[$key] = $item;
          }
          $item_subtotal = intval($item['quantity']) * floatval($item['unit_price']);
          // Use stored value from calculation loop, or recalculate if missing
          $item_ten_percent = isset($waybillItems[$key]['ten_percent']) 
            ? floatval($waybillItems[$key]['ten_percent']) 
            : ($item_subtotal * 0.10);
          $item_name_clean = htmlspecialchars($item['item_name'] ?? '');
          ?>
          <tr>
            <td class="cellStyle" style="width:18%;">
              Border Clearing
            </td>
            <td class="cellStyle" style="width:32%; white-space: nowrap;"><?= $item_name_clean ?></td>
            <td class="cellStyle" style="text-align:center; width:12%;"><?= intval($item['quantity']) ?></td>
            <td class="cellStyle" style="text-align:right; width:12%;"><?= number_format($item['unit_price'], 2) ?></td>
            <td class="cellStyle" style="text-align:right; font-weight: 700; width:13%;">R <?= number_format($item_subtotal, 2) ?></td>
            <td class="cellStyle" style="text-align:right; font-weight: 700; width:13%;">R <?= number_format($item_ten_percent, 2) ?></td>
          </tr>
        <?php endforeach; ?>
        <tr style="font-size:12px; font-weight:700;">
          <td colspan="5" class="cellStyle" style="text-align:right;">Border Clearing Total</td>
          <td class="cellStyle" style="text-align:right;">R <?= number_format($border_clearing_10_percent_total, 2) ?></td>
        </tr>
      </table>
      <?php endif; ?>
      
      <!-- TOTAL ROW (border clearing included only when VAT is enabled) -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:5px;">
        <tr class="rowTotal pcolor">
          <td class="cellStyle" style="font-size:18px; font-weight:700;">TOTAL</td>
          <td class="cellStyle" style="text-align:right;">R <?= number_format($final_total, 2) ?></td>
        </tr>
      </table>

      <!-- Modern Banking & Terms Section -->
      <table class="banking-details" width="100%" cellpadding="0" cellspacing="0">
        <tr>
          <!-- Banking Details Card -->
          <td style="width:100%; padding-right:0;">
            <div style="padding:0; border:0; background:transparent; box-shadow:none; border-radius:0;">
              <div style="font-size:13px; font-weight:700; color:<?= $pTextColor ?>; margin:0 0 8px 0; letter-spacing:0;">
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
        </tr>
      </table>
      <div class="fine-print">
        <strong>Terms &amp; Conditions:</strong>
        <?= trim(preg_replace('/\s+/', ' ', strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $terms_html)))); ?>
      </div>
    </td>
  </tr>
</table>

<script type="text/php">
    if (isset($pdf)) {
        // Add page number and footer text at bottom center
        // A4 page: width ~595 points, height ~842 points
        $font = $fontMetrics->getFont("Arial", "normal");
        $size = 9;
        $color = array(0.4, 0.4, 0.4); // Gray color
        $page_text = "Page {PAGE_NUM} of {PAGE_COUNT} | Generated by KAYISE IT";
        // Use approximate center position (A4 width is 595 points)
        // Estimate text width for "Page 999 of 999 | Generated by KAYISE IT" (~200 points)
        $text_width = 200;
        $x = (595 - $text_width) / 2; // Center on A4 width
        $y = 820; // Position near bottom (842 is full height)
        $pdf->page_text($x, $y, $page_text, $font, $size, $color);
    }
</script>
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
$options->set('isPhpEnabled', true); // Enable PHP for footer scripts

$dompdf = new Dompdf($options); // Initialize Dompdf FIRST
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Set security headers before streaming PDF
if (!headers_sent()) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="waybill-' . esc_attr($waybill_no) . '.pdf"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    header('X-Content-Type-Options: nosniff');
    // If site is HTTPS, add additional security headers
    if (is_ssl() || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

// Output PDF
$dompdf->stream("waybill-{$waybill_no}.pdf", [
  "Attachment" => false
]);
exit;
