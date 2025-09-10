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
                </div>
            </div>
            <?php
            // Only show total to admin users
            if (KIT_Commons::isAdmin()): ?>
                <div class="text-right">
                    <h1 class="display-3 font-bold">Total Waybill: <?= KIT_Commons::displayWaybillTotal($waybill['product_invoice_amount'] ?? 0) ?></h1>
                </div>
            <?php
            endif;
            ?>
        </div>

        <div class="flex space-x-3">
            <?php
            $pdfVerifier = KIT_Waybills::pdfVerifier($waybill['waybill_no'] ?? '', $waybill_id = null);
            if (isset($pdfVerifier['soWhat']) && $pdfVerifier['soWhat']) { ?>
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
        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Waybill Details</h2>
            <div class="grid grid-cols-2">

                <div class="dddd">
                    <?= KIT_Commons::LText([
                        'label' => "Waybill Number:",
                        'value' => htmlspecialchars($waybill['waybill_no']),
                        'classlabel' => '',
                        'classP' => '',
                        'onclick' => '',
                        'is_dynamic' => false,
                    ]); ?>
                </div>
                <div class="dddd">
                    <?= KIT_Commons::LText([
                        'label' => "Tracking Number:",
                        'value' => htmlspecialchars($waybill['tracking_number']),
                        'classlabel' => '',
                        'classP' => '',
                        'onclick' => '',
                        'is_dynamic' => false,
                    ]); ?>
                </div>
                <div class="dddd">
                    <?= KIT_Commons::LText([
                        'label' => "Invoice Number:",
                        'value' => htmlspecialchars($waybill['product_invoice_number']),
                        'classlabel' => '',
                        'classP' => '',
                        'onclick' => '',
                        'is_dynamic' => false,
                    ]); ?>
                </div>
                <div class="dddd">
                    <?= KIT_Commons::LText([
                        'label' => "Waybill Amount:",
                        'value' => KIT_Commons::displayWaybillTotal($waybill['product_invoice_amount']),
                        'classlabel' => '',
                        'classP' => '',
                        'onclick' => '',
                        'is_dynamic' => false,
                    ]); ?>
                </div>

            </div>
        </div>

        <!-- Customer Details -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Customer Details</h2>
            <div class="grid grid-cols-2">

                <div class="dddd">
                    <?= KIT_Commons::LText([
                        'label' => "Name:",
                        'value' => htmlspecialchars($waybill['customer_name']),
                        'classlabel' => '',
                        'classP' => '',
                        'onclick' => '',
                        'is_dynamic' => false,
                    ]); ?>
                </div>
                <div class="dddd">
                    <?= KIT_Commons::LText([
                        'label' => "Surname:",
                        'value' => htmlspecialchars($waybill['customer_surname']),
                        'classlabel' => '',
                        'classP' => '',
                        'onclick' => '',
                        'is_dynamic' => false,
                    ]); ?>
                </div>
                <div class="dddd">
                    <?= KIT_Commons::LText([
                        'label' => "Contact:",
                        'value' => htmlspecialchars($waybill['cell']),
                        'classlabel' => '',
                        'classP' => '',
                        'onclick' => '',
                        'is_dynamic' => false,
                    ]); ?>
                </div>
            </div>
            <div class="dddd">
                <?= KIT_Commons::LText([
                    'label' => "Email:",
                    'value' => htmlspecialchars($waybill['email_address']),
                    'classlabel' => '',
                    'classP' => '',
                    'onclick' => '',
                    'is_dynamic' => false,
                ]); ?>
            </div>
        </div>

    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mt-6">
        <!-- Shipment Details -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Cost Details</h2>

            <div class="space-y-3">
                <?php
                $mass_charge = floatval($waybill['mass_charge'] ?? 0);
                $volume_charge = floatval($waybill['volume_charge'] ?? 0);
                $total_mass_kg = floatval($waybill['total_mass_kg'] ?? 0);
                $total_volume = floatval($waybill['miscellaneous']['others']['total_volume'] ?? 0);

                // Calculate mass rate if we have mass data
                $mass_rate = ($total_mass_kg > 0) ? $mass_charge / $total_mass_kg : 0;

                // Determine preferred charge (the one actually being used)
                $preferred_charge = ($mass_charge > $volume_charge) ? 'mass' : 'volume';

                // Dynamically compare and label
                if ($mass_charge > $volume_charge) {
                    $mass_label = ' <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-500 text-green-100">Highest</span>';
                    $volume_label = '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-500 text-red-100">Lowest</span>';
                } elseif ($volume_charge > $mass_charge) {
                    $mass_label = '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-500 text-red-100">Lowest</span>';
                    $volume_label = ' <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-500 text-green-100">Highest</span>';
                } else {
                    $mass_label = $volume_label = '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">Equal</span>';
                }

                // MASS output (dynamic)
                echo '<div class="flex gap-4">';
                echo KIT_Commons::LText([
                    'label' => "Total Mass:",
                    'value' => KIT_Commons::currency() . number_format($mass_rate, 2) . ' x ' .
                        number_format($total_mass_kg, 2) . 'kg = ' .
                        KIT_Commons::currency() . number_format($mass_charge, 2) . $mass_label,
                    'classlabel' => '',
                    'classP' => '',
                    'onclick' => '',
                    'allow_html' => true,
                ]);
                echo '</div>';
                // VOLUME output (dynamic)
                echo '<div class="flex gap-4">';
                echo KIT_Commons::LText([
                    'label' => "Total Volume:",
                    'value' => number_format($total_volume, 2) . 'm³ = ' . KIT_Commons::currency() . number_format($volume_charge, 2) . $volume_label,
                    'classlabel' => '',
                    'classP' => '',
                    'onclick' => '',
                    'allow_html' => true,
                ]);
                echo '</div>';

                // Charge Basis output (dynamic)
                echo '<div class="flex gap-4">';
                echo KIT_Commons::LText([
                    'label' => "Charge Basis:",
                    'value' => ucfirst($preferred_charge),
                    'classlabel' => '',
                    'classP' => '',
                    'onclick' => '',
                ]);
                echo '</div>';

                //Waybill Amount (the actual charge being used):
                echo '<div class="flex gap-2">';
                echo KIT_Commons::LText([
                    'label' => "Waybill Amount:",
                    'value' => ($preferred_charge == 'mass') ? KIT_Commons::currency() . number_format($mass_charge, 2) : KIT_Commons::currency() . number_format($volume_charge, 2),
                    'classlabel' => '',
                    'classP' => '',
                    'onclick' => '',
                ]);
                echo '</div>';

                //Waybill Total:
                echo '<div class="flex gap-2">';
                echo KIT_Commons::LText([
                    'label' => "Waybill misc total:",
                    'value' => KIT_Commons::currency() . number_format(($waybill['miscellaneous']['misc_total']) ?? 0, 2),
                    'classlabel' => '',
                    'classP' => '',
                    'onclick' => '',
                ]);
                echo '</div>';

                // Convert all values to float to ensure proper numeric comparison
                $vat_total = isset($waybill['miscellaneous']['others']['vat_total']) ? floatval($waybill['miscellaneous']['others']['vat_total']) : 0.0;
                $misc_total = isset($waybill['miscellaneous']['misc_total']) ? floatval($waybill['miscellaneous']['misc_total']) : 0.0;
                $mass_charge = floatval($waybill['mass_charge']);
                $volume_charge = floatval($waybill['volume_charge']);
                $waybill_items_total = floatval($waybill['waybill_items_total']);
                $product_invoice_amount = floatval($waybill['product_invoice_amount']);

                $ttt = [
                    'include_sadc' => $waybill['include_sadc'],
                    'include_sad500' => $waybill['include_sad500'],
                    'vat_include' => $waybill['vat_include']
                ];
                $calculated_total = KIT_Waybills::calculate_total($mass_charge, $volume_charge, $misc_total, $waybill_items_total, null, $ttt);
                ?>
                <div class="flex items-center">
                    <?php
                    $optionChoice = 3;
                    require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/additionCharges.php'); ?>
                </div>
                <div class="flex gap-2" style="font-size: 1.2rem; font-weight: bold;">
                    <label class="<?= KIT_Commons::labelClass() ?>">Grand Total:</label>
                    <span class="font-bold"><?= KIT_Commons::displayWaybillTotal($waybill['product_invoice_amount']) ?></span>
                    
                </div>
            </div>
        </div>
        <!-- Route Information -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Route Information</h2>
            <div class="space-y-3">
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Origin:</label>
                    <span class="font-medium"><?= htmlspecialchars($waybill['origin_country']) ?></span>
                </div>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Destination:</label>
                    <span class="font-medium">
                        <?= htmlspecialchars($waybill['destination_country'] ?? 'N/A') ?>
                    </span>
                </div>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Dimensions:</label>
                    <span class="font-medium">
                        <?= htmlspecialchars($waybill['item_length']) ?> ×
                        <?= htmlspecialchars($waybill['item_width']) ?> ×
                        <?= htmlspecialchars($waybill['item_height']) ?> cm
                    </span>
                </div>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Total Mass:</label>
                    <span class="font-medium"><?= htmlspecialchars($waybill['total_mass_kg']) ?> kg</span>
                </div>

            </div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Waybill Items</h2>
                <?php
                echo KIT_Commons::waybillTrackAndData($waybill['items']);
                ?>
            </div>
        </div>
        <!-- Miscellaneous Items Section -->
        <div class="">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Miscellaneous Items</h2>
                <?php
                $misc_data = null;
                if (!empty($waybill['miscellaneous'])) {
                    $misc_data = maybe_unserialize($waybill['miscellaneous']);
                }

                if (!empty($misc_data)):
                    // Use getMiscCharges to process the misc data
                    $misc_total = 0;

                    if (isset($misc_data['misc_items'])) {
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
                    }
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
                    <p class="text-gray-600">No Miscellaneous items</p>
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

        <a href="?page=08600-Waybill-view&waybill_id=<?= $waybill['id'] ?>&edit=true"
            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Edit Waybill
        </a>
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