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
$waybillItems = isset($quotation->items) ? (object) $quotation->items : false;

//use charge basis to determine the charge to use
$charge_basis = $waybill->charge_basis;
if ($charge_basis == 'mass' || $charge_basis == 'weight') {
  $charge = $waybill->mass_charge;
} else {
  $charge = $waybill->volume_charge;
}

// Calculate misc_total from waybill miscellaneous data
$misc_total = 0;
$misc_data = '';
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
<table width="100%" cellpadding="5" cellspacing="0" style="max-width:700px;margin:0 auto;font-family:Arial,sans-serif;font-size:16px;color:#333;">
  <tr>
    <td>

      <!-- HEADER: LOGO + QUOTATION INFO -->
      <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom:12px;">
        <tr>
          <td style="width:50%;vertical-align:top;">
            <img src="<?php echo plugin_dir_url(__FILE__); ?>/img/logo.png" width="160" height="auto" alt="Logo" style="display:block;">
            <div style="font-size:18px;font-weight:bold;color:rgb(37, 99, 235);margin-top:8px;">08600 Logistics</div>
            <div style="font-size:13px;color:#666;">
              123 Business Street<br>Johannesburg, 2000<br>South Africa<br>
              Phone: +27 11 123 4567<br>Email: info@08600.co.za<br>VAT: 123456789
            </div>
          </td>
          <td style="width:50%;vertical-align:top;text-align:right;">
            <div style="font-size:24px;font-weight:bold;color:rgb(37, 99, 235);text-transform:uppercase;">Waybill Invoice</div>
            <div style="font-size:11px;color:#666;">
              <b>Invoice #:</b> <?= $waybill->product_invoice_number ?><br>
              <b>Date:</b> <?= date('F j, Y', strtotime($waybill->created_at)) ?><br>
              <b>Valid Until:</b> <?= date('F j, Y', strtotime('+30 days')) ?><br>
              <b>Tracking #:</b> <?= $waybill->tracking_number ?>
            </div>
          </td>
        </tr>
      </table>

      <!-- ADDRESS ROW: FROM & BILL TO -->
      <table width="100%" cellpadding="0" cellspacing="5" style="margin-bottom:12px;">
        <tr>
          <td style="width:50%; background:#f8f9fa; border:1px solid #e9ecef; border-radius:5px; padding:10px 12px;">
            <div style="font-size:16px;font-weight:bold;color:rgb(37, 99, 235);text-transform:uppercase;">From</div>
            <div style="font-size:13px;">
              08600 Logistics<br>123 Business Street<br>Johannesburg, 2000<br>South Africa<br>
              Phone: +27 11 123 4567<br>Email: info@08600.co.za<br>VAT: 123456789
            </div>
          </td>
          <td style="width:50%;background:#f8f9fa;border:1px solid #e9ecef;border-radius:5px;padding:10px 12px;">
            <div style="font-size:16px;font-weight:bold;color:rgb(37, 99, 235);text-transform:uppercase;">Bill To</div>

            <div style="font-size:13px;">
              <h4 class="m-0 p-0 font-bold" style="padding: 0; margin: 0"><?= $waybill->company_name ?></h4>
              <?= $waybill->customer_name . " " . $waybill->customer_surname ?><br>
              <?= ($waybill->address) ?? 'No Address' ?><br>
              <?= $waybill->origin_country ?><br>
              Email: <?= ($waybill->email_address) ?? 'customer@customer.com' ?><br>
              Cell: <?= $waybill->customer_cell ?? 'N/A' ?><br>
              Orgin Location: <?= $waybill->origin_country . ', ' . $waybill->customer_city ?>
            </div>
          </td>
        </tr>
      </table>
      <table>
        <tr>
          <td>
            <h4 style="text-align:left; color: rgb(37, 99, 235); margin: 0; padding: 0">Description</h4>
            <p style="margin: 0; padding: 0;"><?= $misc_data['others']['waybill_description'] ?></p>
          </td>
        </tr>
      </table>

      <!-- SHIPPING DETAILS TABLE -->
      <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom:12px;border-collapse:collapse;">
        <tr>
          <td colspan="6" style="font-size:14px;font-weight:bold;color:rgb(37, 99, 235);border-bottom:1px solid rgb(37, 99, 235);padding-bottom:4px;">Shipping Details</td>
        </tr>
        <tr style="background:rgb(37, 99, 235);color:#fff;font-size:13px; border: 0.5px solid black;">
          <th align="left">Waybill #</th>
          <th align="left">Dispatch Date</th>
          <th align="center">Dimensions (cm)</th>
          <th align="right"><?= ($charge_basis == 'mass' || $charge_basis == 'weight') ? 'Mass (kg)' : 'Volume (m³)' ?></th>
          <th>Charge Basis</th>
        </tr>
        <tr style="font-size:13px; padding-top: 10px;">
          <td style="padding-top: 10px; padding-left: 10px;"><?= $waybill->waybill_no ?></td>
          <td style="padding-top: 10px;"><?= ($waybill->dispatch_date) ? date('M j, Y', strtotime($waybill->dispatch_date)) : 'TBD' ?></td>
          <td style="padding-top: 10px;"><?= $waybill->item_length ?> × <?= $waybill->item_width ?> × <?= $waybill->item_height ?></td>
          <td style="padding-top: 10px;">
            <?php if ($charge_basis == 'mass' || $charge_basis == 'weight'): ?>
              <?= number_format($waybill->total_mass_kg, 2) ?>
            <?php else: ?>
              <?= number_format($waybill->item_length * $waybill->item_width * $waybill->item_height / 1000000, 3) ?>
            <?php endif; ?>
          </td>
          <td style="padding-top: 10px;"><?= ucfirst($waybill->charge_basis) ?></td>
        </tr>
      </table>

      <!-- CHARGES BREAKDOWN TABLE -->
      <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom:12px;border-collapse:collapse;">
        <tr>
          <td colspan="2" style="font-size:14px;font-weight:bold;color:rgb(37, 99, 235);border-bottom:1px solid rgb(37, 99, 235);padding-bottom:4px;">Charges Breakdown</td>
        </tr>
        <tr style="background:rgb(37, 99, 235);color:#fff;font-size:13px;">
          <th style="text-align:left;">Description</th>
          <th style="text-align:right;">Amount (R)</th>
        </tr>
        <?php if ($charge_basis == 'mass' || $charge_basis == 'weight'): ?>
          <tr style="font-size:13px;">
            <td>Mass Charge</td>
            <td style="text-align:right;"><?= number_format($waybill->mass_charge, 2) ?></td>
          </tr>
        <?php else: ?>
          <tr style="font-size:13px;">
            <td>Volume Charge</td>
            <td style="text-align:right;"><?= number_format($waybill->volume_charge, 2) ?></td>
          </tr>
        <?php endif; ?>
        <?php if ($misc_total > 0): ?>
          <tr style="font-size:13px;">
            <td>Miscellaneous Charges
              <?php
              //Display the misc items and their prices
              if (!empty($waybill->miscellaneous)) {
                $misc_data = maybe_unserialize($waybill->miscellaneous);

                foreach ($misc_data['misc_items'] as $item) {
                  echo $item['misc_item'] . ' - ' . number_format($item['misc_price'], 2) . '<br>';
                }
              }
              ?>
            </td>
            <td style="text-align:right;"><?= number_format($misc_total, 2) ?></td>
          </tr>
          <?php if (isset($misc_data['others']['include_sad500']) && !empty($misc_data['others']['include_sad500'])): ?>
            <tr style="font-size:13px;">
              <td>
                <p>include_sad500</p>
              </td>
              <td style="text-align:right;"><?= number_format($misc_data['others']['include_sad500'], 2) ?></td>
            </tr>
          <?php endif; ?>
        <?php endif; ?>

        <?php if (isset($waybillItems)): ?>
          <tr style="font-size:13px;">
            <td style="text-align:left;">
              Customs and Clearing
            </td>
            <td style="text-align:right;">
              <?=
              KIT_Waybills::vatCharge("null", KIT_Waybills::waybillItemsTotal($waybillItems));
              ?>
            </td>
          </tr>
        <?php endif; ?>
        <tr style="background:rgb(37, 99, 235);color:#fff;font-weight:bold;font-size:16px;padding: 10px">
          <td>TOTAL</td>
          <td style="text-align:right;"><?= number_format($waybill->product_invoice_amount, 2) ?></td>
        </tr>
      </table>

      <!-- FOOTER: TERMS & CONTACT -->
      <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
        <tr>
          <td style="font-size:9px;color:#666;">
            <div style="font-weight:bold;color:rgb(37, 99, 235);">Terms & Conditions</div>
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
<?php
$html = ob_get_clean(); ?>
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
$dompdf->stream("invoice-{$quotation_id_global}.pdf", [
  "Attachment" => true
]);
exit;
