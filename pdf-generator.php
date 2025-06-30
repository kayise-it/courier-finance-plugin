<?php

/**
 * PDF Generator for Quotations
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
  define('ABSPATH', dirname(__FILE__, 4) . '/'); // Adjust the path as needed
}
if (!isset($quotation_id_global)) {
  echo 'Missing quotation data';
  exit;
}

require_once __DIR__ . '/vendor/autoload.php';
require_once(ABSPATH . 'wp-load.php');

if (!isset($quotation_id_global) || !current_user_can('manage_options')) {
  wp_die('Invalid request');
}

global $wpdb;
$table_name = $wpdb->prefix . 'kit_quotations';

$quotation = KIT_Waybills::getFullWaybillWithItems($waybill_no);

$waybill = (object) $quotation->waybill;

if ($waybill->approval !== 'approved') {
  wp_die('Waybill not approved, Manager needs to approve the waybill first');
}
if ($waybill->status !== 'quoted') {
  wp_die('Waybill not quoted, Manager needs to quote the waybill first');
}

// Calculate misc_total from waybill miscellaneous data
$misc_total = 0;
if (!empty($waybill->miscellaneous)) {
  $misc_data = maybe_unserialize($waybill->miscellaneous);
  if ($misc_data && isset($misc_data['misc_total'])) {
    $misc_total = floatval($misc_data['misc_total']);
  } elseif ($misc_data && isset($misc_data['misc_items'])) {
    // Calculate total from misc items if misc_total is not directly available
    foreach ($misc_data['misc_items'] as $item) {
      $misc_total += floatval($item['misc_price']) * intval($item['misc_quantity']);
    }
  }
}

// Get the image file content and convert it to Base64
$imagePath = plugin_dir_path(__FILE__) . 'icons/pin.png';
$imageData = file_get_contents($imagePath);
$pin = 'data:image/png;base64,' . base64_encode($imageData);

$emailPath = plugin_dir_path(__FILE__) . '/icons/email.png';
$imageData = file_get_contents($emailPath);
$email = 'data:image/png;base64,' . base64_encode($imageData);

$webPath = plugin_dir_path(__FILE__) . '/icons/web.png';
$imageData = file_get_contents($webPath);
$web = 'data:image/png;base64,' . base64_encode($imageData);

$contactPath = plugin_dir_path(__FILE__) . '/icons/contact.png';
$imageData = file_get_contents($contactPath);
$contact = 'data:image/png;base64,' . base64_encode($imageData);

if (!$quotation) {
  wp_die('Quotation not found');
}

$our_details = (object)[
  "name" => "Standard Bank",
  "contact" => 1244253464576,
  "email" => "info@08600africa.co.za",
  "Addess" => "00000",
  "VAT" => "00000",
];

$payment_details = (object)[
  "bank_name" => "Standard Bank",
  "account" => 1244253464576,
  "branch" => "00000"
];

// Create PDF content
ob_start();
?>
<!-- PAGE CONTAINER -->
<table width="100%" cellpadding="0" cellspacing="0" style="max-width:700px;margin:0 auto;font-family:Arial,sans-serif;font-size:12px;color:#333;">
  <tr>
    <td>

      <!-- HEADER: LOGO + QUOTATION INFO -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
        <tr>
          <td style="width:50%;vertical-align:top;">
            <div style="width:120px;height:60px;border:2px dashed #dee2e6;background:#f8f9fa;color:#6c757d;font-size:10px;display:flex;align-items:center;justify-content:center;text-align:center;">
              [LOGO PLACEHOLDER]<br>120x60px
            </div>
            <div style="font-size:18px;font-weight:bold;color:#4f46e5;margin-top:8px;">08600 Logistics</div>
            <div style="font-size:10px;color:#666;">
              123 Business Street<br>Johannesburg, 2000<br>South Africa<br>
              Phone: +27 11 123 4567<br>Email: info@08600.co.za<br>VAT: 123456789
            </div>
          </td>
          <td style="width:50%;vertical-align:top;text-align:right;">
            <div style="font-size:24px;font-weight:bold;color:#4f46e5;text-transform:uppercase;">Waybill Quotation</div>
            <div style="font-size:11px;color:#666;">
              <b>Quotation #:</b> <?= $waybill->product_invoice_number ?><br>
              <b>Date:</b> <?= date('F j, Y', strtotime($waybill->created_at)) ?><br>
              <b>Valid Until:</b> <?= date('F j, Y', strtotime('+30 days')) ?><br>
              <b>Tracking #:</b> <?= $waybill->tracking_number ?>
            </div>
          </td>
        </tr>
      </table>

      <!-- ADDRESS ROW: FROM & BILL TO -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;">
        <tr>
          <td style="width:50%;background:#f8f9fa;border:1px solid #e9ecef;border-radius:5px;padding:10px 12px;">
            <div style="font-size:12px;font-weight:bold;color:#4f46e5;text-transform:uppercase;">From</div>
            <div style="font-size:10px;">
              08600 Logistics<br>123 Business Street<br>Johannesburg, 2000<br>South Africa<br>
              Phone: +27 11 123 4567<br>Email: info@08600.co.za<br>VAT: 123456789
            </div>
          </td>
          <td style="width:50%;background:#f8f9fa;border:1px solid #e9ecef;border-radius:5px;padding:10px 12px;">
            <div style="font-size:12px;font-weight:bold;color:#4f46e5;text-transform:uppercase;">Bill To</div>
            <div style="font-size:10px;">
              <?= $waybill->customer_name . " " . $waybill->customer_surname ?><br>
              <?= ($waybill->customer_address) ?? 'No Address' ?><br>
              <?= $waybill->origin_country ?><br>
              Email: <?= ($waybill->customer_email) ?? 'customer@customer.com' ?><br>
              Cell: <?= $waybill->customer_cell ?? 'N/A' ?><br>
              Destination: <?= $waybill->destination_country ?>
            </div>
          </td>
        </tr>
      </table>

      <!-- SHIPPING DETAILS TABLE -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;border-collapse:collapse;">
        <tr>
          <td colspan="6" style="font-size:14px;font-weight:bold;color:#4f46e5;border-bottom:1px solid #4f46e5;padding-bottom:4px;">Shipping Details</td>
        </tr>
        <tr style="background:#4f46e5;color:#fff;font-size:10px;">
          <th>Waybill #</th>
          <th>Invoice #</th>
          <th>Dispatch Date</th>
          <th>Dimensions (cm)</th>
          <th>Mass (kg)</th>
          <th>Charge Basis</th>
        </tr>
        <tr style="font-size:10px;">
          <td><?= $waybill->waybill_no ?></td>
          <td><?= $waybill->product_invoice_number ?></td>
          <td><?= ($waybill->dispatch_date) ? date('M j, Y', strtotime($waybill->dispatch_date)) : 'TBD' ?></td>
          <td><?= $waybill->item_length ?> × <?= $waybill->item_width ?> × <?= $waybill->item_height ?></td>
          <td><?= number_format($waybill->total_mass_kg, 2) ?></td>
          <td><?= ucfirst($waybill->charge_basis) ?></td>
        </tr>
      </table>

      <!-- CHARGES BREAKDOWN TABLE -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:12px;border-collapse:collapse;">
        <tr>
          <td colspan="2" style="font-size:14px;font-weight:bold;color:#4f46e5;border-bottom:1px solid #4f46e5;padding-bottom:4px;">Charges Breakdown</td>
        </tr>
        <tr style="background:#4f46e5;color:#fff;font-size:10px;">
          <th>Description</th>
          <th style="text-align:right;">Amount (R)</th>
        </tr>
        <tr style="font-size:10px;">
          <td>Mass Charge</td>
          <td style="text-align:right;"><?= number_format($waybill->mass_charge, 2) ?></td>
        </tr>
        <tr style="font-size:10px;">
          <td>Volume Charge</td>
          <td style="text-align:right;"><?= number_format($waybill->volume_charge, 2) ?></td>
        </tr>
        <?php if ($misc_total > 0): ?>
          <tr style="font-size:10px;">
            <td>Miscellaneous Charges</td>
            <td style="text-align:right;"><?= number_format($misc_total, 2) ?></td>
          </tr>
        <?php endif; ?>
        <tr style="background:#4f46e5;color:#fff;font-weight:bold;font-size:12px;">
          <td>TOTAL</td>
          <td style="text-align:right;"><?= number_format(KIT_Waybills::calculate_total($waybill->mass_charge, $waybill->volume_charge, $misc_total), 2) ?></td>
        </tr>
      </table>

      <!-- FOOTER: TERMS & CONTACT -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
        <tr>
          <td style="font-size:9px;color:#666;">
            <div style="font-weight:bold;color:#4f46e5;">Terms & Conditions</div>
            • This quotation is valid for 30 days from the date of issue<br>
            • Payment terms: 50% deposit required before dispatch<br>
            • Delivery time: Subject to route availability and customs clearance<br>
            • Insurance: Basic coverage included, additional coverage available<br>
            • All prices are in South African Rand (ZAR) and include VAT
            <br><br>
            <div style="margin-top:10px;">
              <strong>Thank you for choosing 08600 Logistics!</strong><br>
              For any queries, please contact us at info@08600.co.za or +27 11 123 4567
            </div>
          </td>
        </tr>
      </table>

    </td>
  </tr>
</table>
<?php $html = ob_get_clean(); ?>
<?php

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
$dompdf->stream("quotation-{$quotation_id_global}.pdf", [
  "Attachment" => true
]);
exit;
