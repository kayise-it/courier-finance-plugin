<?php
// Determine which charge is greater
$mass_charge = floatval($waybill['mass_charge'] ?? 0);
$volume_charge = floatval($waybill['volume_charge'] ?? 0);

$is_mass_greater = $mass_charge > $volume_charge;
$is_volume_greater = $volume_charge > $mass_charge;
$is_equal = $mass_charge === $volume_charge;
?>
<div class="max-w-6xl mx-auto p-6 md:space-y-6 bg-white rounded-lg shadow-md">
    <?php if (isset($_GET['approval_updated']) && $_GET['approval_updated'] == '1'): ?>
        <div id="approval-success-message" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            <strong>Success!</strong> Approval status has been updated.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['invoice_status_updated']) && $_GET['invoice_status_updated'] == '1'): ?>
        <div id="invoice-status-message" class="mb-4 p-4 bg-blue-100 border border-blue-400 text-blue-700 rounded">
            <strong>Note:</strong> Invoice status has been automatically set to "Pending" because the approval status was changed from "Approved" or "Completed".
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['approval_error']) && $_GET['approval_error'] == '1'): ?>
        <div id="approval-error-message" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <strong>Error!</strong> Failed to update approval status. Please try again.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['assignment_success']) && $_GET['assignment_success'] == '1'): ?>
        <div id="assignment-success-message" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            <strong>Success!</strong> Waybill has been assigned to delivery truck.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['assignment_error'])): ?>
        <div id="assignment-error-message" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            <strong>Error!</strong>
            <?php
            $error_code = $_GET['assignment_error'];
            switch ($error_code) {
                case '1':
                    echo 'Invalid waybill or delivery ID.';
                    break;
                case '2':
                    echo 'Waybill not found or not warehoused.';
                    break;
                case '3':
                    echo 'Delivery not found.';
                    break;
                case '4':
                    echo 'Waybill is already assigned to a delivery.';
                    break;
                case '5':
                    echo 'Failed to assign waybill. Please try again.';
                    break;
                default:
                    echo 'Unknown error occurred.';
                    break;
            }
            ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col space-y-6 justify-between items-start border-b pb-4">
        <div class="grid grid-cols-2 w-full">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Waybill #<?= htmlspecialchars($waybill['waybill_no'] ?? 'N/A') ?>
                </h1>
                <div class="flex items-center mt-2">
                    <?php
                    $pdfVerifier = KIT_Waybills::pdfVerifier($waybill['waybill_no'] ?? '', $waybill_id = null);
                    if (isset($pdfVerifier['soWhat']) && $pdfVerifier['soWhat']) {
                        echo KIT_Commons::statusBadge('approved');
                    } else {
                        echo KIT_Commons::statusBadge('pending');
                    }
                    ?>
                    <span class="ml-2 text-xs text-gray-500">
                        <?php
                        $status_text = ucfirst($waybill['approval'] ?? 'pending');
                        $action_text = ($waybill['approval'] ?? 'pending') === 'pending' ? 'Pending' : (($waybill['approval'] ?? 'pending') === 'rejected' ? 'Rejected' : (($waybill['approval'] ?? 'pending') === 'completed' ? 'Completed' : 'Approved'));

                        echo $action_text . ' By: ' . ($waybill['approved_by_username'] ?? 'N/A');
                        ?>
                    </span>
                    <span class="ml-2 text-xs text-gray-500">
                        Last Updated: <?= date('M j, Y', strtotime($waybill['last_updated_at'] ?? 'now')) ?>
                    </span>
                    <div class="createdby">
                    </div>

                </div>
            </div>
            <?php
            // Only show total to specific admins who can see prices
            if (class_exists('KIT_User_Roles') && KIT_User_Roles::can_see_prices()): ?>
                <div class="text-right">
                <div class="border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-900">Grand Total</span>
                                <span class="text-xl font-bold text-gray-900"><?= KIT_Commons::displayWaybillTotal($waybill['product_invoice_amount']) ?></span>
                            </div>
                        </div>
                </div>
            <?php
            endif;
            ?>
        </div>

        <div class="flex space-x-3">
            <?php
            // Check both waybill approval status AND user permissions for PDF access
            $pdfVerifier = KIT_Waybills::pdfVerifier($waybill['waybill_no'] ?? '', $waybill_id = null);
            $canAccessPDF = isset($pdfVerifier['soWhat']) && $pdfVerifier['soWhat'] && KIT_User_Roles::can_see_prices();

            if ($canAccessPDF) { ?>
                <div class="flex flex-col">
                    <span class="opacity-0 text-gray-600 font-bold">Invoice Status:</span>
                    <a href="<?php echo plugin_dir_url(__FILE__) . '../../pdf-generator.php?waybill_no=' . $waybill['waybill_no'] . '&pdf_nonce=' . wp_create_nonce("pdf_nonce"); ?>"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        PDF
                    </a>
                </div>
            <?php
            }
            ?>
            <div class="flex flex-col">
                <label class="<?= KIT_Commons::labelClass() ?>">Invoice Status:</label>
                <?= KIT_Commons::waybillQuoteStatus(esc_attr((string)($waybill['waybill_no'] ?? '')), esc_attr((string)($waybill['id'] ?? '')), 'select'); ?>
            </div>
            <div class="flex flex-col">
                <label class="<?= KIT_Commons::labelClass() ?>">Approval Status:</label>
                <?= KIT_Commons::waybillApprovalStatus(esc_attr((string)($waybill['waybill_no'] ?? '')), esc_attr((string)($waybill['id'] ?? '')), esc_attr((string)($waybill['approval'] ?? '')), 'select'); ?>
            </div>
            <?php
            // Check if waybill has warehouse items
            require_once plugin_dir_path(__FILE__) . '../warehouse/warehouse-functions.php';
            $warehouse_items = KIT_Warehouse::getWarehouseItems($waybill['id']);
            if (!empty($warehouse_items)): ?>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Warehoused:</label>
                    <?= KIT_Commons::warehouseDeliveryAssignment(
                        $waybill['id'],
                        $waybill['waybill_no'],
                        $waybill['destination_country'] ?? '',
                        $waybill['destination_city'] ?? '',
                        $waybill['status']
                    ); ?>
                </div>
            <?php endif; ?>
            <?php
            // Decide which button to show based on whether DB total matches recalculation
            $allowFix = false;
            $buttonText = 'Confirm Waybill Total';
            if (class_exists('KIT_Waybills')) {
                $checkTotal = KIT_Waybills::doubleCalcWaybillTotal(['waybill_no' => intval($waybill['waybill_no'])]);
                if (is_array($checkTotal) && empty($checkTotal['error'])) {
                    $allowFix = !$checkTotal['matches'];
                    if ($allowFix) {
                        $buttonText = 'Verify & Update DB';
                    }
                }
            }
            ?>

        </div>
        <!-- VAT Warning Display -->
        <?php if (isset($_GET['vat_warning']) && $_GET['vat_warning'] == '1'): ?>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>VAT Warning:</strong> VAT was checked but no waybill items were found. No VAT was added to the total.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-2 w-full">
            <div class="ps">
                <?= KIT_Commons::h2tag(['title' => 'Waybill Description', 'class' => '']) ?>
                <p class="text xs"><?= esc_html($waybill['miscellaneous']['others']['waybill_description'] ?? '') ?></p>
            </div>

        </div>
    </div>


    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <!-- Waybill Details -->
        <div class="space-y-6">
            <!-- Document Information Section -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Document Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">Waybill Number</span>
                        <span class="text-sm font-semibold text-gray-900">
                            <?= htmlspecialchars($waybill['waybill_no'] ?? 'N/A') ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">Tracking Number</span>
                        <span class="text-sm font-semibold text-gray-900">
                            <?= htmlspecialchars($waybill['tracking_number'] ?? 'N/A') ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">Invoice Number</span>
                        <span class="text-sm font-semibold text-gray-900">
                            <?= htmlspecialchars($waybill['product_invoice_number'] ?? 'N/A') ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Financial Information Section -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Financial Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">Waybill Amount</span>
                        <span class="text-sm font-semibold text-gray-900">
                            <?= KIT_Commons::displayWaybillTotal($waybill['product_invoice_amount'] ?? 0) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer Details -->
        <div class="space-y-6">
            <!-- Personal Information Section -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Personal Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">Name</span>
                        <span class="text-sm font-semibold text-gray-900">
                            <?= htmlspecialchars($waybill['customer_name'] ?? 'N/A') ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">Surname</span>
                        <span class="text-sm font-semibold text-gray-900">
                            <?= htmlspecialchars($waybill['customer_surname'] ?? 'N/A') ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Contact Information</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">Contact</span>
                        <span class="text-sm font-semibold text-gray-900">
                            <?= htmlspecialchars($waybill['cell'] ?? 'N/A') ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">Email</span>
                        <span class="text-sm font-semibold text-gray-900">
                            <?= htmlspecialchars($waybill['email_address'] ?? 'N/A') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
        <!-- Shipment Details -->
        <?php if (class_exists('KIT_User_Roles') && KIT_User_Roles::can_see_prices()): ?>
            <div class="">
                <h3 class="text-lg font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Cost Details</h3>

                <div class="space-y-3">
                    <?php
                    $mass_charge = floatval($waybill['mass_charge'] ?? 0);
                    $volume_charge = floatval($waybill['volume_charge'] ?? 0);
                    $total_mass_kg = floatval($waybill['total_mass_kg'] ?? 0);
                    // Support both legacy and new storage locations for total_volume
                    $total_volume = 0.0;
                    if (isset($waybill['miscellaneous']['others']['total_volume'])) {
                        $total_volume = floatval($waybill['miscellaneous']['others']['total_volume']);
                    } elseif (isset($waybill['total_volume'])) {
                        $total_volume = floatval($waybill['total_volume']);
                    }

                    // Calculate mass rate if we have mass data
                    $mass_rate = ($total_mass_kg > 0) ? $mass_charge / $total_mass_kg : 0;

                    // Tie-safe comparisons using epsilon to avoid float quirks
                    $epsilon = 0.005; // 0.5c tolerance
                    $mass_gt = ($mass_charge - $volume_charge) > $epsilon;
                    $vol_gt = ($volume_charge - $mass_charge) > $epsilon;
                    $is_equal = !$mass_gt && !$vol_gt;

                    // Determine preferred charge (the one actually being used)
                    // On ties, default deterministically to 'mass'
                    $preferred_charge = $mass_gt ? 'mass' : ($vol_gt ? 'volume' : 'mass');

                    // Dynamically compare and label
                    if ($mass_gt) {
                        $mass_label = ' <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-500 text-green-100">Highest</span>';
                        $volume_label = '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-500 text-red-100">Lowest</span>';
                    } elseif ($vol_gt) {
                        $mass_label = '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-500 text-red-100">Lowest</span>';
                        $volume_label = ' <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-500 text-green-100">Highest</span>';
                    } else {
                        $mass_label = $volume_label = '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">Equal</span>';
                    }

                    // NEW CLEAN COST DETAILS DESIGN
                    ?>
                    <div class="space-y-6">
                        <!-- Pricing Calculation Section -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Pricing Calculation</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-700">Mass Charge</span>
                                    <div class="text-right">
                                        <div class="text-sm font-medium">
                                            <?= KIT_Commons::currency() . number_format($mass_rate, 2) ?> × <?= number_format($total_mass_kg, 2) ?>kg
                                        </div>
                                        <div class="text-sm font-semibold">
                                            <?= KIT_Commons::currency() . number_format($mass_charge, 2) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-700">Volume Charge</span>
                                    <div class="text-right">
                                        <div class="text-sm font-medium">
                                            <?= number_format($total_volume, 2) ?>m³ × <?= KIT_Commons::currency() . '0.74' ?>
                                        </div>
                                        <div class="text-sm font-semibold">
                                            <?= KIT_Commons::currency() . number_format($volume_charge, 2) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="pt-2 border-t border-gray-100">
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm font-medium text-gray-900">Charge Basis</span>
                                        <span class="text-sm font-semibold"><?= ucfirst($preferred_charge) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Base Amount Section -->
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Base Amount</h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-700">Waybill Amount</span>
                                    <span class="text-sm font-semibold">
                                        <?= ($preferred_charge == 'mass') ? KIT_Commons::currency() . number_format($mass_charge, 2) : KIT_Commons::currency() . number_format($volume_charge, 2) ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-700">Miscellaneous</span>
                                    <span class="text-sm font-semibold">
                                        <?= KIT_Commons::currency() . number_format(($waybill['miscellaneous']['misc_total']) ?? 0, 2) ?>
                                    </span>
                                </div>
                            </div>
                        </div>



                        <!-- Grand Total Section -->
                        <div class="border-2 border-gray-300 rounded-lg p-4 bg-gray-50">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-semibold text-gray-900">Grand Total</span>
                                <span class="text-xl font-bold text-gray-900"><?= KIT_Commons::displayWaybillTotal($waybill['product_invoice_amount']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <!-- Route Information -->
        <div class="space-y-6">
            <!-- Route Details Section -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Route Details</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">Origin</span>
                        <span class="text-sm font-semibold text-gray-900">
                            <?= htmlspecialchars($waybill['origin_country'] ?? 'N/A') ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">Destination</span>
                        <span class="text-sm font-semibold text-gray-900">
                            <?php
                            $destination = '';
                            if (!empty($waybill['destination_city'])) {
                                $destination = htmlspecialchars($waybill['destination_city']);
                            }
                            if (!empty($waybill['destination_country'])) {
                                $destination .= ($destination ? ', ' : '') . htmlspecialchars($waybill['destination_country']);
                            }
                            echo $destination ?: 'N/A';
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Package Details Section -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Package Details</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">Dimensions</span>
                        <span class="text-sm font-semibold text-gray-900">
                            <?= htmlspecialchars($waybill['item_length'] ?? '0') ?> ×
                            <?= htmlspecialchars($waybill['item_width'] ?? '0') ?> ×
                            <?= htmlspecialchars($waybill['item_height'] ?? '0') ?> cm
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-700">Total Mass</span>
                        <span class="text-sm font-semibold text-gray-900">
                            <?= htmlspecialchars($waybill['total_mass_kg'] ?? '0') ?> kg
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="">
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Waybill Items</h3>
                <?php
                echo KIT_Commons::waybillTrackAndData($waybill['items']);
                ?>
            </div>
        </div>
        <!-- Miscellaneous Items Section -->
        <div class="">
            <div class="border border-gray-200 rounded-lg p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Miscellaneous Items</h3>
                <?php
                $misc_data = null;
                if (!empty($waybill['miscellaneous'])) {
                    $misc_data = maybe_unserialize($waybill['miscellaneous']);
                }

                if (!empty($misc_data) && isset($misc_data['misc_items']) && !empty($misc_data['misc_items'])):
                    // Use getMiscCharges to process the misc data
                    $misc_total = 0;

                    // Convert the stored format to the format expected by getMiscCharges
                    $misc_data_for_processing = [
                        'misc_item' => [],
                        'misc_price' => [],
                        'misc_quantity' => []
                    ];

                    foreach ($misc_data['misc_items'] as $item) {
                        $misc_data_for_processing['misc_item'][] = $item['misc_item'];
                        $misc_data_for_processing['misc_price'][] = $item['misc_price'];
                        $misc_data_for_processing['misc_quantity'][] = $item['misc_quantity'];
                    }

                    $misc_result = self::getMiscCharges($misc_data_for_processing, []);
                    $misc_total = floatval($misc_result->misc_total);
                ?>
                    <div class="overflow-x-auto">
                        <table class="<?= KIT_Commons::tableClasses(); ?> w-full">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="<?= KIT_Commons::thClasses() ?> text-left">Description</th>
                                    <th class="<?= KIT_Commons::thClasses() ?> text-center">Price</th>
                                    <th class="<?= KIT_Commons::thClasses() ?> text-center">Qty</th>
                                    <th class="<?= KIT_Commons::thClasses() ?> text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="<?= KIT_Commons::tbodyClasses() ?>">
                                <?php $temTotal = 0;

                                foreach ($misc_data['misc_items'] as $key => $item): ?>
                                    <tr class="border-t border-gray-100">

                                        <td class="<?= KIT_Commons::tcolClasses() ?>">
                                            <?= htmlspecialchars($item['misc_item']) ?></td>
                                        <td class="<?= KIT_Commons::tcolClasses() ?> text-center"><?= KIT_Commons::currency() ?>
                                            <?= number_format($item['misc_price'], 2) ?></td>
                                        <td class="<?= KIT_Commons::tcolClasses() ?> text-center"><?= intval($item['misc_quantity']) ?>
                                        </td>
                                        <td class="<?= KIT_Commons::tcolClasses() ?> text-right">
                                            <?= KIT_Commons::currency() ?>
                                            <?= number_format($item['misc_price'] * $item['misc_quantity'], 2) ?>
                                        </td>
                                    </tr>
                                    <?php $temTotal += $item['misc_price'] * $item['misc_quantity']; ?>
                                <?php endforeach; ?>
                                <tr class="border-t border-gray-100">
                                    <td colspan="3" class="<?= KIT_Commons::tcolClasses() ?> text-right font-semibold">
                                        Total</td>
                                    <td class="<?= KIT_Commons::tcolClasses() ?> font-bold">
                                        <?= KIT_Commons::currency() ?> <?= number_format($misc_total, 2) ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-gray-600">No Misc Items</p>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <!-- Notes Section -->
    <?php if ($waybill['approval'] === 'pending') : ?>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex flex-row items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-xs font-medium text-yellow-800">Approval Required</h3>
                    <div class="mt-2 text-xs text-yellow-700">
                        <p>This waybill is pending manager approval before processing.</p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>


    <div class="flex justify-end space-x-3 border-t pt-4">
        <?php if (KIT_User_Roles::can_edit_approved_waybill($waybill['approval'] ?? 'pending')): ?>
            <a href="?page=08600-Waybill-view&waybill_id=<?= $waybill['id'] ?>&edit=true"
                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Edit Waybill
            </a>
        <?php elseif (($waybill['approval'] ?? 'pending') === 'approved' || ($waybill['approval'] ?? 'pending') === 1): ?>
            <span class="px-4 py-2 border border-gray-300 rounded-md text-gray-500 bg-gray-100 cursor-not-allowed" title="Waybill is approved and locked for editing. Only administrators can edit approved waybills.">
                Edit Waybill (Locked)
            </span>
        <?php endif; ?>
        <button
            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
            Save
        </button>
    </div>
</div>
<?php
// Ensure WordPress functions are available
if (!function_exists('admin_url')) {
    function admin_url($path = '')
    {
        return $path;
    }
}
if (!function_exists('wp_create_nonce')) {
    function wp_create_nonce($action = '')
    {
        return '';
    }
}
if (!function_exists('maybe_unserialize')) {
    function maybe_unserialize($original)
    {
        if (is_serialized($original)) {
            return unserialize($original);
        }
        return $original;
    }
    function is_serialized($data)
    {
        // If it isn't a string, it isn't serialized
        if (!is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' == $data) return true;
        if (!preg_match('/^([adObis]):/', $data, $badions)) return false;
        switch ($badions[1]) {
            case 'a':
            case 'O':
            case 's':
                if (preg_match("/^{$badions[1]}:[0-9]+:/s", $data)) return true;
                break;
            case 'b':
            case 'i':
            case 'd':
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;$/", $data)) return true;
                break;
        }
        return false;
    }
}
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-hide success/error messages after 10 seconds
        const messages = [
            'approval-success-message',
            'invoice-status-message',
            'approval-error-message',
            'assignment-success-message',
            'assignment-error-message'
        ];

        messages.forEach(function(messageId) {
            const message = document.getElementById(messageId);
            if (message) {
                setTimeout(function() {
                    message.style.transition = 'opacity 0.5s ease-out';
                    message.style.opacity = '0';
                    setTimeout(function() {
                        message.remove();
                    }, 500); // Wait for fade out animation
                }, 10000); // 10 seconds
            }
        });
    });
</script>