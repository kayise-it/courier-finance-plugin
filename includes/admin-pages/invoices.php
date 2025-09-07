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
        <div class="invoice-card bg-white p-5 rounded-lg shadow-md">
            <h3 style="margin: 0 0 15px 0; color: #2563eb;">Generate Invoice</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Generate New Invoice', 'primary', 'md', ['href' => '#', 'fullWidth' => true, 'gradient' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Bulk Invoice Generation', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Invoice Templates', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- View Invoices -->
        <div class="invoice-card bg-white p-5 rounded-lg shadow-md">
            <h3 style="margin: 0 0 15px 0; color: #059669;">View Invoices</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('All Invoices', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Pending Invoices', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Paid Invoices', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- PDF Generator -->
        <div class="invoice-card bg-white p-5 rounded-lg shadow-md">
            <h3 style="margin: 0 0 15px 0; color: #dc2626;">PDF Generator</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Generate PDF Invoice', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('PDF Settings', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('PDF Templates', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- Print Waybills -->
        <div class="invoice-card bg-white p-5 rounded-lg shadow-md">
            <h3 style="margin: 0 0 15px 0; color: #7c3aed;">Print Waybills</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Print Single Waybill', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Bulk Print Waybills', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Print Settings', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- Document Templates -->
        <div class="invoice-card bg-white p-5 rounded-lg shadow-md">
            <h3 style="margin: 0 0 15px 0; color: #ea580c;">Document Templates</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Invoice Templates', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Waybill Templates', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Custom Templates', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- Email & Sharing -->
        <div class="invoice-card bg-white p-5 rounded-lg shadow-md">
            <h3 style="margin: 0 0 15px 0; color: #0891b2;">Email & Sharing</h3>
            <ul style="list-style: none; padding: 0; margin: 0;">
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Email Invoice', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Email Settings', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li style="margin-bottom: 10px;">
                    <?php echo KIT_Commons::renderButton('Share Documents', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>
    </div>
</div>

