<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Invoices & Documents</h1>
    <hr class="wp-header-end">

    <div class="invoices-container grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        
        <!-- Generate Invoice -->
        <div class="invoice-card bg-white p-5 rounded-lg shadow-md border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Generate Invoice</h3>
            <ul class="list-none p-0 m-0 space-y-2.5">
                <li>
                    <?php echo KIT_Commons::renderButton('Generate New Invoice', 'primary', 'md', ['href' => '#', 'fullWidth' => true, 'gradient' => true]); ?>
                </li>
                <li>
                    <?php echo KIT_Commons::renderButton('Bulk Invoice Generation', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li>
                    <?php echo KIT_Commons::renderButton('Invoice Templates', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- View Invoices -->
        <div class="invoice-card bg-white p-5 rounded-lg shadow-md border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">View Invoices</h3>
            <ul class="list-none p-0 m-0 space-y-2.5">
                <li>
                    <?php echo KIT_Commons::renderButton('All Invoices', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li>
                    <?php echo KIT_Commons::renderButton('Pending Invoices', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li>
                    <?php echo KIT_Commons::renderButton('Paid Invoices', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- PDF Generator -->
        <div class="invoice-card bg-white p-5 rounded-lg shadow-md border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">PDF Generator</h3>
            <ul class="list-none p-0 m-0 space-y-2.5">
                <li>
                    <?php echo KIT_Commons::renderButton('Generate PDF Invoice', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li>
                    <?php echo KIT_Commons::renderButton('PDF Settings', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li>
                    <?php echo KIT_Commons::renderButton('PDF Templates', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- Print Waybills -->
        <div class="invoice-card bg-white p-5 rounded-lg shadow-md border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Print Waybills</h3>
            <ul class="list-none p-0 m-0 space-y-2.5">
                <li>
                    <?php echo KIT_Commons::renderButton('Print Single Waybill', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li>
                    <?php echo KIT_Commons::renderButton('Bulk Print Waybills', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li>
                    <?php echo KIT_Commons::renderButton('Print Settings', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- Document Templates -->
        <div class="invoice-card bg-white p-5 rounded-lg shadow-md border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Document Templates</h3>
            <ul class="list-none p-0 m-0 space-y-2.5">
                <li>
                    <?php echo KIT_Commons::renderButton('Invoice Templates', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li>
                    <?php echo KIT_Commons::renderButton('Waybill Templates', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li>
                    <?php echo KIT_Commons::renderButton('Custom Templates', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>

        <!-- Email & Sharing -->
        <div class="invoice-card bg-white p-5 rounded-lg shadow-md border border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Email & Sharing</h3>
            <ul class="list-none p-0 m-0 space-y-2.5">
                <li>
                    <?php echo KIT_Commons::renderButton('Email Invoice', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li>
                    <?php echo KIT_Commons::renderButton('Email Settings', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
                <li>
                    <?php echo KIT_Commons::renderButton('Share Documents', 'secondary', 'md', ['href' => '#', 'fullWidth' => true]); ?>
                </li>
            </ul>
        </div>
    </div>
</div>

