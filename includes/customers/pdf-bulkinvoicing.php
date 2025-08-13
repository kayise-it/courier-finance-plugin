<?php

// Exit if accessed directly
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__, 4) . '/'); // Adjust the path as needed
}

// Load DOMPDF setup
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once ABSPATH . 'wp-load.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (isset($_GET['selected_ids'])) {
    $selected_ids_string = $_GET['selected_ids']; // e.g. "4000,4003,4004"
    $selected_ids_array = array_map('intval', explode(',', $selected_ids_string));

    if (!empty($selected_ids_array)) {
        global $wpdb;
        
        // Get company details from settings
        $company_name = get_option('kit_company_name', 'KAYISE IT');
        $company_address = get_option('kit_company_address', '');
        $company_email = get_option('kit_company_email', 'info@kayiseit.co.za');
        $company_phone = get_option('kit_company_phone', '0877022625');
        $company_vat_number = get_option('kit_company_vat_number', '');
        
        // Get VAT percentage
        $vat_percentage = get_option('kit_vat_percentage', 15);
        
        // Fetch waybill details
        $waybills_table = $wpdb->prefix . 'kit_waybills';
        $customers_table = $wpdb->prefix . 'kit_customers';
        $deliveries_table = $wpdb->prefix . 'kit_deliveries';
        $shipping_directions_table = $wpdb->prefix . 'kit_shipping_directions';
        $cities_table = $wpdb->prefix . 'kit_operating_cities';
        $countries_table = $wpdb->prefix . 'kit_operating_countries';
        
        $placeholders = implode(',', array_fill(0, count($selected_ids_array), '%d'));
        $query = $wpdb->prepare("
            SELECT 
                w.id,
                w.waybill_no,
                w.product_invoice_amount,
                w.miscellaneous,
                w.created_at,
                c.name AS customer_name,
                c.surname AS customer_surname,
                c.email AS customer_email,
                c.phone AS customer_phone,
                c.address AS customer_address,
                sd.destination_country_id,
                sd.origin_country_id,
                dest_country.country_name AS destination_country,
                orig_country.country_name AS origin_country,
                city.city_name AS destination_city
            FROM $waybills_table w
            LEFT JOIN $customers_table c ON w.customer_id = c.cust_id
            LEFT JOIN $deliveries_table d ON w.delivery_id = d.id
            LEFT JOIN $shipping_directions_table sd ON d.direction_id = sd.id
            LEFT JOIN $cities_table city ON w.city_id = city.id
            LEFT JOIN $countries_table dest_country ON sd.destination_country_id = dest_country.id
            LEFT JOIN $countries_table orig_country ON sd.origin_country_id = orig_country.id
            WHERE w.id IN ($placeholders)
            ORDER BY w.created_at ASC
        ", $selected_ids_array);
        
        $waybills = $wpdb->get_results($query);
        
        if (!empty($waybills)) {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('isFontSubsettingEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);

        ob_start(); // Start buffering HTML content

            // Calculate totals
            $subtotal = 0;
            $total_vat = 0;
            $grand_total = 0;
            
            foreach ($waybills as $waybill) {
                $misc_amount = !empty($waybill->miscellaneous) ? unserialize($waybill->miscellaneous) : [];
                $misc_total = isset($misc_amount['total']) ? $misc_amount['total'] : 0;
                $waybill_total = (float)$waybill->product_invoice_amount + (float)$misc_total;
                $subtotal += $waybill_total;
            }
            
            $total_vat = $subtotal * ($vat_percentage / 100);
            $grand_total = $subtotal + $total_vat;
            
            $invoice_number = 'BULK-' . date('Ymd-His');
            $invoice_date = date('d/m/Y');
        ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulk Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info {
            float: left;
            width: 60%;
        }
        .invoice-info {
            float: right;
            width: 35%;
            text-align: right;
        }
        .clear {
            clear: both;
        }
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 10px;
        }
        .invoice-number {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .invoice-date {
            color: #666;
        }
        .customer-info {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .customer-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #2563eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #2563eb;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            float: right;
            width: 300px;
        }
        .total-row {
            padding: 8px 0;
        }
        .total-label {
            font-weight: bold;
            text-align: right;
            padding-right: 10px;
        }
        .total-value {
            font-weight: bold;
            text-align: right;
        }
        .grand-total {
            font-size: 16px;
            color: #2563eb;
            border-top: 2px solid #2563eb;
            padding-top: 10px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        .waybill-count {
            background-color: #e3f2fd;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="invoice-title"><?php echo esc_html($company_name); ?></div>
            <?php if (!empty($company_address)): ?>
                <div><?php echo nl2br(esc_html($company_address)); ?></div>
            <?php endif; ?>
            <div>Email: <?php echo esc_html($company_email); ?></div>
            <div>Phone: <?php echo esc_html($company_phone); ?></div>
            <?php if (!empty($company_vat_number)): ?>
                <div>VAT Number: <?php echo esc_html($company_vat_number); ?></div>
            <?php endif; ?>
        </div>
        <div class="invoice-info">
            <div class="invoice-number">Invoice #<?php echo esc_html($invoice_number); ?></div>
            <div class="invoice-date">Date: <?php echo esc_html($invoice_date); ?></div>
            <div>Type: Bulk Invoice</div>
        </div>
        <div class="clear"></div>
    </div>

    <div class="waybill-count">
        Bulk Invoice for <?php echo count($waybills); ?> Waybill<?php echo count($waybills) > 1 ? 's' : ''; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Waybill #</th>
                <th>Destination Country</th>
                <th>Destination City</th>
                <th class="text-right">Amount (R)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($waybills as $waybill): 
                $misc_amount = !empty($waybill->miscellaneous) ? unserialize($waybill->miscellaneous) : [];
                $misc_total = isset($misc_amount['total']) ? $misc_amount['total'] : 0;
                $waybill_total = (float)$waybill->product_invoice_amount + (float)$misc_total;
            ?>
            <tr>
                <td><?php echo esc_html($waybill->waybill_no); ?></td>
                <td><?php echo esc_html($waybill->destination_country ?: 'N/A'); ?></td>
                <td><?php echo esc_html($waybill->destination_city ?: 'N/A'); ?></td>
                <td class="text-right"><?php echo number_format($waybill_total, 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="total-row">
            <span class="total-label">Subtotal:</span>
            <span class="total-value">R <?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div class="total-row">
            <span class="total-label">VAT (<?php echo $vat_percentage; ?>%):</span>
            <span class="total-value">R <?php echo number_format($total_vat, 2); ?></span>
        </div>
        <div class="total-row grand-total">
            <span class="total-label">Grand Total:</span>
            <span class="total-value">R <?php echo number_format($grand_total, 2); ?></span>
        </div>
    </div>

    <div class="clear"></div>

    <div class="footer">
        <p><strong>Payment Terms:</strong> Payment due within 30 days of invoice date.</p>
        <p><strong>Banking Details:</strong></p>
        <?php 
        $bank_name = get_option('kit_bank_name', '');
        $account_number = get_option('kit_account_number', '');
        $branch_code = get_option('kit_branch_code', '');
        $account_holder = get_option('kit_account_holder', '');
        ?>
        <?php if (!empty($bank_name)): ?>
            <p>Bank: <?php echo esc_html($bank_name); ?></p>
        <?php endif; ?>
        <?php if (!empty($account_number)): ?>
            <p>Account Number: <?php echo esc_html($account_number); ?></p>
        <?php endif; ?>
        <?php if (!empty($branch_code)): ?>
            <p>Branch Code: <?php echo esc_html($branch_code); ?></p>
        <?php endif; ?>
        <?php if (!empty($account_holder)): ?>
            <p>Account Holder: <?php echo esc_html($account_holder); ?></p>
        <?php endif; ?>
        <p><strong>Reference:</strong> Please use Invoice #<?php echo esc_html($invoice_number); ?> as payment reference.</p>
    </div>
</body>
</html>
    <?php 
        $html = ob_get_clean(); // Get the full buffered HTML
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

            $dompdf->stream("bulk-invoice-{$invoice_number}.pdf", [
            "Attachment" => true // Set to false if you want inline preview in browser
        ]);
        } else {
            echo "No waybills found for the selected IDs.";
        }
    } else {
        echo "No waybill IDs provided.";
    }
} else {
    echo "Invalid request.";
}
?>