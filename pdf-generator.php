<?php
$testing = 1;
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

$misc = unserialize($waybill->miscellaneous);
$misc_items = $misc['misc_items'];
$misc_total = $misc['misc_total'];

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

<style>
  tr p {
    font-size: 13px;
  }

  .misci p {
    font-weight: 100;
    font-size: 10px;
  }

  table.subTable,
  .subHeadTable {
    width: 100%;
    /* Full width or a specific px/% */
    border-collapse: collapse;
    /* or 'separate' */
    border-spacing: 0;
    /* Space between cells (used if not collapsed) */
    table-layout: auto;
    /* or 'fixed' for faster rendering */
  }

  table.subTable tr:nth-child(even) {
    background-color: #e9ecef;
  }

  table.subTable tr:nth-child(odd) {
    background-color: #e9ece3;
  }

  table.subTable thead {
    background-color: #333;
    color: white;
  }
</style>
<!-- PAGE CONTAINER -->
<table width="100%" cellpadding="5" cellspacing="0" style="max-width:700px; margin:0 auto; font-family:Arial,sans-serif;font-size:16px;color:#333;">
  <tr>
    <td colspan="3">
      <!-- HEADER: LOGO + INVOICE INFO -->
      <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom:12px; border-bottom:1px solid #e9ecef;">
        <tr>
          <td style="width:50%;vertical-align:top;">
            <img src="<?php echo plugin_dir_url(__FILE__); ?>/img/logo.png" width="160" height="auto" alt="Company Logo" style="display:block;">
          </td>
          <td style="width:50%;vertical-align:top;text-align:right;">
            <div style="font-size:24px;font-weight:bold;color:rgb(37, 99, 235);text-transform:uppercase; margin-bottom:8px;">Waybill Invoice</div>
            <div style="font-size:12px;color:#666; background:#f8f9fa; padding:8px; border-radius:4px;">
              <div><strong>Invoice #:</strong> <?= $waybill->product_invoice_number ?></div>
              <div><strong>Date:</strong> <?= date('F j, Y', strtotime($waybill->created_at)) ?></div>
              <div><strong>Valid Until:</strong> <?= date('F j, Y', strtotime('+30 days')) ?></div>
              <div><strong>Tracking #:</strong> <?= $waybill->tracking_number ?></div>
            </div>
          </td>
        </tr>
      </table>

      <!-- ADDRESS ROW: FROM & BILL TO -->
      <table width="100%" cellpadding="0" cellspacing="5" style="margin-bottom:20px;">
        <tr>
          <td style="width:50%; background:#f8f9fa; border:1px solid #e9ecef; border-radius:5px; padding:12px;">
            <div style="font-size:16px;font-weight:bold;color:rgb(37, 99, 235);text-transform:uppercase; margin-bottom:8px;">From</div>
            <div style="font-size:13px; line-height:1.5;">
              08600 Logistics<br>
              123 Business Street<br>
              Johannesburg, 2000<br>
              South Africa<br>
              Phone: +27 11 123 4567<br>
              Email: info@08600.co.za<br>
              VAT: 123456789
            </div>
          </td>
          <td style="width:50%;background:#f8f9fa;border:1px solid #e9ecef;border-radius:5px;padding:12px;">
            <div style="font-size:16px;font-weight:bold;color:rgb(37, 99, 235);text-transform:uppercase; margin-bottom:8px;">Bill To</div>
            <div style="font-size:13px; line-height:1.5;">
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
      <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom:20px;">
        <tr>
          <td style="padding:8px 0; border-bottom:1px solid #e9ecef;">
            <h4 style="color: rgb(37, 99, 235); margin: 0 0 5px 0;">Description</h4>
            <p style="margin: 0; font-size:13px;"><?= $misc_data['others']['waybill_description'] ?></p>
          </td>
        </tr>
      </table>

      <!-- SHIPPING DETAILS TABLE -->
      <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom:20px;border-collapse:collapse;">
        <tr>
          <td colspan="5" style="font-size:14px;font-weight:bold;color:rgb(37, 99, 235);border-bottom:1px solid rgb(37, 99, 235);padding-bottom:6px;">Shipping Details</td>
        </tr>
        <tr style="background:rgb(37, 99, 235);color:#fff;font-size:13px;">
          <th style="text-align:left; padding:8px;">Waybill #</th>
          <th style="text-align:left; padding:8px;">Dispatch Date</th>
          <th style="text-align:center; padding:8px;">Dimensions (cm)</th>
          <th style="text-align:center; padding:8px;"><?= ($charge_basis == 'mass' || $charge_basis == 'weight') ? 'Mass (kg)' : 'Volume (m³)' ?></th>
          <th style="text-align:center; padding:8px;">Charge Basis</th>
        </tr>
        <tr style="font-size:13px; border-bottom:1px solid #e9ecef;">
          <td style="padding:8px;"><?= $waybill->waybill_no ?></td>
          <td style="padding:8px;"><?= ($waybill->dispatch_date) ? date('M j, Y', strtotime($waybill->dispatch_date)) : 'TBD' ?></td>
          <td style="text-align:center; padding:8px;"><?= $waybill->item_length ?> × <?= $waybill->item_width ?> × <?= $waybill->item_height ?></td>
          <td style="text-align:center; padding:8px;">
            <?php if ($charge_basis == 'mass' || $charge_basis == 'weight'): ?>
              <?= number_format($waybill->total_mass_kg, 2) ?>
            <?php else: ?>
              <?= number_format($waybill->item_length * $waybill->item_width * $waybill->item_height / 1000000, 3) ?>
            <?php endif; ?>
          </td>
          <td style="text-align:center; padding:8px;"><?= ucfirst($waybill->charge_basis) ?></td>
        </tr>
      </table>

      <!-- CHARGES BREAKDOWN TABLE -->
      <table width="100%" cellpadding="5" cellspacing="0" style="margin-bottom:20px;border-collapse:collapse;">
        <tr>
          <td colspan="2" style="font-size:14px;font-weight:bold;color:rgb(37, 99, 235);border-bottom:1px solid rgb(37, 99, 235);padding-bottom:6px;">Charges Breakdown</td>
        </tr>
        <tr style="background:rgb(37, 99, 235);color:#fff;font-size:13px;">
          <th style="text-align:left; padding:8px;">Description</th>
          <th style="text-align:right; padding:8px;">Amount (R)</th>
        </tr>

        <?php if ($charge_basis == 'mass' || $charge_basis == 'weight'): ?>
          <tr style="font-size:13px; border-bottom:1px solid #e9ecef;">
            <td style="padding:8px;">Mass Charge</td>
            <td style="text-align:right; padding:8px;"><?= number_format($waybill->mass_charge, 2) ?></td>
          </tr>
        <?php else: ?>
          <tr style="font-size:13px; border-bottom:1px solid #e9ecef;">
            <td style="padding:8px;">Volume Charge</td>
            <td style="text-align:right; padding:8px;"><?= number_format($waybill->volume_charge, 2) ?></td>
          </tr>
        <?php endif; ?>

        <?php if (isset($waybill->include_sad500) && $waybill->include_sad500 == 1): ?>
          <tr style="font-size:13px; border-bottom:1px solid #e9ecef;">
            <td style="padding:8px;">SAD500</td>
            <td style="text-align:right; padding:8px;"><?= KIT_Waybills::sadc_certificate() ?></td>
          </tr>
        <?php endif ?>
        <?php if (isset($waybill->include_sadc) && $waybill->include_sadc == 1): ?>
          <tr style="font-size:13px; border-bottom:1px solid #e9ecef;">
            <td style="padding:8px;">
              SADC Certificate
            </td>
            <td style="text-align:right; padding:8px;"><?= KIT_Waybills::sad() ?></td>
          </tr>
        <?php endif ?>
        <tr>
          <!-- WAYBILL ITEMS TABLE -->
          <?php if (isset($waybillItems) && is_object($waybillItems) && isset($waybillItems->{0})): ?>
            <td colspan="1" style="vertical-align: top; width:<?php if (!empty($waybill->miscellaneous)): ?> 50% <?php else: ?>100% <?php endif; ?>">
              <table class="subHeadTable">
                <tr style="font-size:13px; border-bottom:1px solid #e9ecef;">
                  <td style="padding:8px;">Customs and Clearing</td>
                  <td style="text-align:right; padding:8px;"><?= number_format($waybill->waybill_items_total * 0.10, 2) ?></td>
                </tr>
              </table>
              <table class="subTable">
                <tr style="background:#f8f9fa; font-size:12px;">
                  <th style="text-align:left; padding:8px; border:1px solid #e9ecef;">Item</th>
                  <th style="text-align:center; padding:8px; border:1px solid #e9ecef;">Qty</th>
                  <th style="text-align:right; padding:8px; border:1px solid #e9ecef;">Unit Price</th>
                  <th style="text-align:right; padding:8px; border:1px solid #e9ecef;">Total</th>
                </tr>
                <?php foreach ($waybillItems as $item): ?>
                  <tr style="font-size:12px; border-bottom:1px solid #e9ecef;">
                    <td style="padding:8px; border:1px solid #e9ecef;"><?= $item['item_name'] ?></td>
                    <td style="text-align:center; padding:8px; border:1px solid #e9ecef;"><?= $item['quantity'] ?></td>
                    <td style="text-align:right; padding:8px; border:1px solid #e9ecef;"><?= number_format($item['unit_price'], 2) ?></td>
                    <td style="text-align:right; padding:8px; border:1px solid #e9ecef;"><?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
                <tr style="font-size:13px; font-weight:bold; background:#f8f9fa;">
                  <td colspan="3" style="text-align:right; padding:8px; border:1px solid #e9ecef;">Subtotal:</td>
                  <td style="text-align:right; padding:8px; border:1px solid #e9ecef;"><?= number_format($waybill->waybill_items_total, 2) ?></td>
                </tr>
              </table>
            </td>
          <?php endif; ?>

          <?php if (!empty($misc)): ?>
            <td colspan="1" style="vertical-align: top; width:<?php if (!empty($waybillItems)): ?> 50%;<?php else: ?>100% <?php endif; ?>">
              <table class="subHeadTable">
                <tr style="font-size:13px; border-bottom:1px solid #e9ecef;">
                  <td style="padding:8px;">Total Misc Charges</td>
                  <td style="text-align:right; padding:8px;"><?= number_format($misc_total, 2) ?></td>
                </tr>
              </table>
              <table class="subTable">
                <tr style="background:#f8f9fa; font-size:12px;">
                  <th style="text-align:left; padding:8px; border:1px solid #e9ecef;">Item</th>
                  <th style="text-align:center; padding:8px; border:1px solid #e9ecef;">Qty</th>
                  <th style="text-align:right; padding:8px; border:1px solid #e9ecef;">Unit Price</th>
                  <th style="text-align:right; padding:8px; border:1px solid #e9ecef;">Total</th>
                </tr>
                <?php foreach ($misc_items as $item): ?>
                  <tr style="font-size:12px; border-bottom:1px solid #e9ecef;">
                    <td style="padding:8px; border:1px solid #e9ecef;"><?= $item['misc_item'] ?></td>
                    <td style="text-align:center; padding:8px; border:1px solid #e9ecef;"><?= $item['misc_quantity'] ?></td>
                    <td style="text-align:right; padding:8px; border:1px solid #e9ecef;"><?= number_format($item['misc_price'], 2) ?></td>
                    <td style="text-align:right; padding:8px; border:1px solid #e9ecef;"><?= number_format($item['misc_quantity'] * $item['misc_price'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
                <tr style="font-size:13px; font-weight:bold; background:#f8f9fa;">
                  <td colspan="3" style="text-align:right; padding:8px; border:1px solid #e9ecef;">Subtotal:</td>
                  <td style="text-align:right; padding:8px; border:1px solid #e9ecef;"><?= number_format($misc_total, 2) ?></td>
                </tr>
              </table>
            <?php endif; ?>
            </td>
        </tr>

        <!-- TOTAL ROW -->
        <tr style="background:rgb(37, 99, 235);color:#fff;font-weight:bold;font-size:16px;">
          <td style="padding:12px;">TOTAL</td>
          <td style="text-align:right; padding:12px;">R <?= number_format($waybill->product_invoice_amount, 2) ?></td>
        </tr>
      </table>

      <!-- TERMS & CONDITIONS -->
      <table width="100%" cellpadding="5" cellspacing="0" style="margin-top:20px; border-top:1px solid #e9ecef;">
        <tr>
          <td style="font-size:11px;color:#666; padding:15px 0;">
            <div style="font-weight:bold;color:rgb(37, 99, 235); margin-bottom:5px;">Terms & Conditions</div>
            <ul style="margin:5px 0; padding-left:15px;">
              <li>This quotation is valid for 30 days from the date of issue</li>
              <li>Payment terms: 50% deposit required before dispatch</li>
              <li>Delivery time: Subject to route availability and customs clearance</li>
              <li>Insurance: Basic coverage included, additional coverage available</li>
              <li>All prices are in South African Rand (ZAR) and include VAT</li>
            </ul>
            <div style="margin-top:15px; text-align:center; font-size:12px;">
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
if ($testing) {
  echo '<pre>';
  print_r($_POST);
  echo '</pre>';
  exit();
}
$html = ob_get_clean();
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
