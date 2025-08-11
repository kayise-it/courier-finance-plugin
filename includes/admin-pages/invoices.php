<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Invoices & Documents</h1>
    <hr class="wp-header-end">

    <div class="invoices-container" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
        
        <!-- Generate Invoice -->
        <div class="invoice-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #2563eb;">Generate Invoice</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-primary" style="width: 100%; text-align: left;">Generate New Invoice</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Bulk Invoice Generation</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Invoice Templates</a>
                </li>
            </ul>
        </div>

        <!-- View Invoices -->
        <div class="invoice-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #059669;">View Invoices</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">All Invoices</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Pending Invoices</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Paid Invoices</a>
                </li>
            </ul>
        </div>

        <!-- PDF Generator -->
        <div class="invoice-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #dc2626;">PDF Generator</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Generate PDF Invoice</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">PDF Settings</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">PDF Templates</a>
                </li>
            </ul>
        </div>

        <!-- Print Waybills -->
        <div class="invoice-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #7c3aed;">Print Waybills</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Print Single Waybill</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Bulk Print Waybills</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Print Settings</a>
                </li>
            </ul>
        </div>

        <!-- Document Templates -->
        <div class="invoice-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #ea580c;">Document Templates</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Invoice Templates</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Waybill Templates</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Custom Templates</a>
                </li>
            </ul>
        </div>

        <!-- Email & Sharing -->
        <div class="invoice-card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
            <h3 style="margin: 0 0 15px 0; color: #0891b2;">Email & Sharing</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Email Invoice</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Email Settings</a>
                </li>
                <li style="margin-bottom: 10px;">
                    <a href="#" class="button button-secondary" style="width: 100%; text-align: left;">Share Documents</a>
                </li>
            </ul>
        </div>
    </div>
</div>
