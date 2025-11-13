<?php
// Determine which charge is greater
$mass_charge = floatval($waybill['mass_charge'] ?? 0);
$volume_charge = floatval($waybill['volume_charge'] ?? 0);
$waybill_no = intval($waybill['waybill_no'] ?? 0);

$is_mass_greater = $mass_charge > $volume_charge;
$is_volume_greater = $volume_charge > $mass_charge;
$is_equal = $mass_charge === $volume_charge;
$preferred_charge = $is_mass_greater ? 'mass' : ($is_volume_greater ? 'volume' : 'mass');
$primary_charge = $preferred_charge === 'mass' ? $mass_charge : $volume_charge;
$vat_charge = 0.0;

$destinationCountryName = KIT_Routes::get_country_name_by_id($waybill['miscellaneous']['others']['destination_country_id'] ?? 0);
// Use canonical waybill.city_id for destination city
$destinationCityName = KIT_Routes::get_city_name_by_id($waybill['city_id'] ?? 0);
$originCountryName = KIT_Routes::get_country_name_by_id($waybill['miscellaneous']['others']['origin_country_id'] ?? 0);
$originCityName = KIT_Routes::get_city_name_by_id($waybill['miscellaneous']['others']['origin_city_id'] ?? 0);

// Normalize miscellaneous data (may be serialized string or array)
$rawMisc = $waybill['miscellaneous'] ?? [];
if (!is_array($rawMisc)) {
    $maybeMisc = function_exists('maybe_unserialize') ? maybe_unserialize($rawMisc) : @unserialize($rawMisc);
    $misc_data = is_array($maybeMisc) ? $maybeMisc : [];
} else {
    $misc_data = $rawMisc;
}

// Safely resolve totals and flags
$waybill_items_total = floatval($waybill['waybill_items_total'] ?? 0);
$include_sad500 = intval($waybill['include_sad500'] ?? 0) === 1;
$include_sadc = intval($waybill['include_sadc'] ?? 0) === 1;
$include_vat = intval($waybill['vat_include'] ?? 0) === 1;

$misc_total = 0.0;
if (isset($misc_data['misc_total'])) {
    $misc_total = floatval($misc_data['misc_total']);
} elseif (isset($misc_data['misc_items']) && is_array($misc_data['misc_items'])) {
    foreach ($misc_data['misc_items'] as $misc_item) {
        $price = isset($misc_item['misc_price']) ? floatval($misc_item['misc_price']) : 0.0;
        $qty = isset($misc_item['misc_quantity']) ? floatval($misc_item['misc_quantity']) : 0.0;
        $misc_total += $price * $qty;
    }
}

$international_price_rands = isset($misc_data['others']['international_price_rands'])
    ? KIT_Waybills::normalize_amount($misc_data['others']['international_price_rands'])
    : 0.0;
$handling_fee = (!$include_vat && $international_price_rands > 0) ? $international_price_rands : 0.0;
$intl_amount = $handling_fee;

$stored_sad500 = isset($misc_data['others']['include_sad500']) ? KIT_Waybills::normalize_amount($misc_data['others']['include_sad500']) : 0.0;
$stored_sadc = isset($misc_data['others']['include_sadc']) ? KIT_Waybills::normalize_amount($misc_data['others']['include_sadc']) : 0.0;

$sad500_amount = $include_sad500 ? ($stored_sad500 > 0 ? $stored_sad500 : floatval(KIT_Waybills::sadc_certificate())) : 0.0;
$sadc_amount = $include_sadc ? ($stored_sadc > 0 ? $stored_sadc : floatval(KIT_Waybills::sad())) : 0.0;
$stored_total = floatval($waybill['product_invoice_amount'] ?? 0);
$additional_charges_total = null;
$calculated_total = null;

$calculated_items_total = 0.0;
if (!empty($waybill['items']) && is_array($waybill['items'])) {
    foreach ($waybill['items'] as $item) {
        $qty = isset($item['quantity']) ? floatval($item['quantity']) : 0.0;
        $price = isset($item['unit_price']) ? floatval($item['unit_price']) : 0.0;
        $line_total = $qty * $price;
        if (isset($item['total_price']) && is_numeric($item['total_price'])) {
            $line_total = floatval($item['total_price']);
        }
        $calculated_items_total += $line_total;
    }
}
if ($calculated_items_total > 0) {
    $waybill_items_total = $calculated_items_total;
}
$vat_charge = ($include_vat && $waybill_items_total > 0) ? $waybill_items_total * 0.10 : 0.0;

$calculation_breakdown = null;
$using_breakdown = false;
if (class_exists('KIT_Waybills') && $waybill_no > 0) {
    $double_calc = KIT_Waybills::doubleCalcWaybillTotal([
        'waybill_no' => $waybill_no,
        'update_if_mismatch' => false,
    ]);
    if (is_array($double_calc) && empty($double_calc['error'])) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[viewWaybill] doubleCalc breakdown for waybill ' . $waybill_no . ': ' . print_r($double_calc, true));
            error_log('[viewWaybill] raw mass_charge=' . $mass_charge . ' volume_charge=' . $volume_charge . ' misc_total=' . $misc_total . ' waybill_items_total=' . $waybill_items_total);
        }
    }
    if (is_array($double_calc) && empty($double_calc['error'])) {
        $stored_total = floatval($double_calc['db_total'] ?? $stored_total);
        $calculated_total = floatval($double_calc['calc_total'] ?? $calculated_total);
        $calculation_breakdown = $double_calc['breakdown'] ?? null;
        $using_breakdown = is_array($calculation_breakdown);
    }
}

if ($using_breakdown) {
    $base_charges = $calculation_breakdown['base_charges'] ?? [];
    $additional_charges = $calculation_breakdown['additional_charges'] ?? [];
    $totals_section = $calculation_breakdown['totals'] ?? [];

    if (isset($base_charges['mass_charge'])) {
        $mass_charge = floatval($base_charges['mass_charge']);
    }
    if (isset($base_charges['volume_charge'])) {
        $volume_charge = floatval($base_charges['volume_charge']);
    }
    if (isset($base_charges['primary_charge']['amount'])) {
        $primary_charge = floatval($base_charges['primary_charge']['amount']);
    }
    if (isset($base_charges['primary_charge']['basis'])) {
        $preferred_charge = $base_charges['primary_charge']['basis'];
    }

    if (isset($additional_charges['misc_total'])) {
        $misc_total = floatval($additional_charges['misc_total']);
    }
    if (isset($additional_charges['sad500'])) {
        $sad500_amount = floatval($additional_charges['sad500']);
    }
    if (isset($additional_charges['sadc'])) {
        $sadc_amount = floatval($additional_charges['sadc']);
    }
    if (isset($additional_charges['vat'])) {
        $vat_charge = floatval($additional_charges['vat']);
    }
    if (isset($additional_charges['international_price'])) {
        $handling_fee = floatval($additional_charges['international_price']);
        $intl_amount = $handling_fee;
    }
    $additional_charges_total = isset($additional_charges['total'])
        ? floatval($additional_charges['total'])
        : $sad500_amount + $sadc_amount + ($include_vat ? $vat_charge : $handling_fee);

    if (isset($totals_section['final_total'])) {
        $calculated_total = floatval($totals_section['final_total']);
    }
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[viewWaybill] breakdown primary=' . $primary_charge . ' mass=' . $mass_charge . ' volume=' . $volume_charge . ' additional_total=' . $additional_charges_total . ' calc_total=' . $calculated_total);
    }
}

$stored_total = floatval($stored_total);

$total_volume = 0.0;
if (isset($misc_data['others']['total_volume'])) {
    $total_volume = floatval($misc_data['others']['total_volume']);
} elseif (isset($waybill['total_volume'])) {
    $total_volume = floatval($waybill['total_volume']);
}

$total_mass_kg = floatval($waybill['total_mass_kg'] ?? 0);
$mass_rate = ($total_mass_kg > 0) ? ($mass_charge > 0 ? $mass_charge / $total_mass_kg : 0.0) : 0.0;

// Determine preferred charge and calculate totals
if (!$using_breakdown) {
    $epsilon = 0.005;
    $mass_gt = ($mass_charge - $volume_charge) > $epsilon;
    $vol_gt = ($volume_charge - $mass_charge) > $epsilon;
    $preferred_charge = $mass_gt ? 'mass' : ($vol_gt ? 'volume' : 'mass');
    $primary_charge = $preferred_charge === 'mass' ? $mass_charge : $volume_charge;
}

$volume_rate = 0.0;
$volume_display_charge = $volume_charge;
if ($total_volume > 0.0) {
    if ($volume_charge > 0.0) {
        $volume_rate = $volume_charge / $total_volume;
    } elseif (!empty($misc_data['others']['volume_rate_used'])) {
        $volume_rate = floatval($misc_data['others']['volume_rate_used']);
        $volume_display_charge = $volume_rate * $total_volume;
    } else {
        $volume_rate = 0.74;
        $volume_display_charge = $volume_rate * $total_volume;
    }
}

$additional_charges_total = $additional_charges_total ?? ($sad500_amount + $sadc_amount + ($include_vat ? $vat_charge : $handling_fee));
$calculated_total = $calculated_total ?? ($primary_charge + $misc_total + $vat_charge + $handling_fee + $additional_charges_total);
$totals_match = abs($calculated_total - $stored_total) < 0.01;
$grand_total_display = $calculated_total;

if (!$totals_match && class_exists('KIT_Waybills') && $waybill_no > 0) {
    $updatedTotalCheck = KIT_Waybills::doubleCalcWaybillTotal([
        'waybill_no' => $waybill_no,
        'update_if_mismatch' => true,
    ]);

    if (is_array($updatedTotalCheck) && empty($updatedTotalCheck['error'])) {
        if (!empty($updatedTotalCheck['updated'])) {
            $stored_total = floatval($updatedTotalCheck['calc_total'] ?? $stored_total);
        } elseif (isset($updatedTotalCheck['db_total'])) {
            $stored_total = floatval($updatedTotalCheck['db_total']);
        }
        $totals_match = abs($calculated_total - $stored_total) < 0.01;
    }
}

?>
<div class="max-w-6xl mx-auto p-3 md:p-6 space-y-4 md:space-y-6 bg-white rounded-lg shadow-md">
    <?php if (isset($_GET['approval_updated']) && $_GET['approval_updated'] == '1'): ?>
        <div id="approval-success-message" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            <strong>Success!</strong> Approval status has been updated.
        </div>
    <?php endif; ?>


    <!-- Blue badge with dark blue border, light blue background, and white text, to track 1) Who created waybill 2) Who invoiced waybill 3) Who approved waybill -->
    <div class="flex flex-row space-x-4">
        <div class="flex flex-col items-start">
            <div class="bg-blue-100 border border-blue-400 text-blue-700 rounded-lg p-2">
                <span class="font-bold">Created by:</span>
                <?= KIT_Commons::getNameOfUser($waybill['created_by']) ?>
                <span class="text-xs text-gray-500">
                    <?= date('M j, Y', strtotime($waybill['created_at'] ?? 'now')) ?>
                </span>
            </div>
        </div>
        <?php if (!empty($waybill['invoiced_at']) && strtotime($waybill['invoiced_at']) > 0): ?>
        <div class="flex flex-col items-start">
            <div class="bg-blue-100 border border-blue-400 text-blue-700 rounded-lg p-2">
                <span class="font-bold">Invoiced by:</span>
                <?= KIT_Commons::getNameOfUser($waybill['created_by']) ?>
                <span class="text-xs text-gray-500">
                    <?= date('M j, Y', strtotime($waybill['invoiced_at'])) ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($waybill['approved_by']) && $waybill['approved_by'] > 0 && !empty($waybill['approved_at']) && strtotime($waybill['approved_at']) > 0): ?>
        <div class="flex flex-col items-start">
            <div class="bg-blue-100 border border-blue-400 text-blue-700 rounded-lg p-2">
                <span class="font-bold">Approved by:</span>
                <?= KIT_Commons::getNameOfUser($waybill['approved_by']) ?>
                <span class="text-xs text-gray-500">
                    <?= date('M j, Y', strtotime($waybill['approved_at'])) ?>
                </span>
            </div>
        </div>
        <?php endif; ?>
    </div>

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
                    echo 'Waybill not found or not pending.';
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
            <div>
                <h1 class="text-xl md:text-2xl font-bold text-gray-800">Waybill #<?= htmlspecialchars($waybill['waybill_no'] ?? 'N/A') ?>
                </h1>
                <div class="flex flex-wrap items-center gap-2 mt-2">
                    <?php
                    $pdfVerifier = KIT_Waybills::pdfVerifier($waybill['waybill_no'] ?? '', $waybill_id = null);
                    if (isset($pdfVerifier['soWhat']) && $pdfVerifier['soWhat']) {
                        echo KIT_Commons::statusBadge('approved');
                    } else {
                        echo KIT_Commons::statusBadge('pending');
                    }
                    ?>
                    <span class="text-xs text-gray-500">
                        <?php
                        $status_text = ucfirst($waybill['approval'] ?? 'pending');
                        $action_text = ($waybill['approval'] ?? 'pending') === 'pending' ? 'Pending' : (($waybill['approval'] ?? 'pending') === 'rejected' ? 'Rejected' : (($waybill['approval'] ?? 'pending') === 'completed' ? 'Completed' : 'Approved'));

                        echo $action_text . ' By: ' . ($waybill['approved_by_username'] ?? 'N/A');
                        ?>
                    </span>
                    <span class="text-xs text-gray-500">
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
                            <span class="text-xl font-bold text-gray-900"><?= KIT_Commons::displayWaybillTotal($grand_total_display) ?></span>
                        </div>
                        <?php if (!$totals_match): ?>
                            <div class="text-xs text-red-600 mt-2">
                                Stored total out of date: <?= KIT_Commons::displayWaybillTotal($stored_total) ?>. Re-save or verify to sync.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
            endif;
            ?>
        </div>
        <?php if (!$totals_match): ?>
            <div class="w-full bg-yellow-50 border border-yellow-200 text-yellow-800 text-xs md:text-sm rounded-md px-3 py-2">
                <strong>Heads up:</strong> Calculated total <?= KIT_Commons::displayWaybillTotal($grand_total_display) ?> differs from stored total <?= KIT_Commons::displayWaybillTotal($stored_total) ?>.
                Re-run the total verification utility or re-save the waybill to update the database.
            </div>
        <?php endif; ?>

        <div class="flex space-x-3">
            <?php
            // Check both waybill approval status AND user permissions for PDF access
            $pdfVerifier = KIT_Waybills::pdfVerifier($waybill['waybill_no'] ?? '', $waybill_id = null);
            $canAccessPDF = isset($pdfVerifier['soWhat']) && $pdfVerifier['soWhat'] && KIT_User_Roles::can_see_prices();

            if ($canAccessPDF) { ?>
                <div class="flex flex-col">
                    <span class="opacity-0 text-gray-600 font-bold">Invoice Status:</span>
                    <a href="<?php echo plugin_dir_url(__FILE__) . '../../pdf-generator.php?waybill_no=' . $waybill['waybill_no'] . '&pdf_nonce=' . wp_create_nonce("pdf_nonce"); ?>"
                        target="_blank" rel="noopener noreferrer"
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
                        $waybill['destination_country_id'] ?? '',
                        $waybill['status']
                    ); ?>
                </div>
            <?php endif; ?>
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 w-full">
            <div class="ps">
                <?= KIT_Commons::h2tag(['title' => 'Waybill Description', 'class' => '']) ?>
                <?php
                // Safely get waybill description with proper fallback
                // Priority: 1) Direct 'description' column, 2) miscellaneous['others']['waybill_description']
                $waybill_description = '';
                
                // First check the direct 'description' column (from database)
                if (!empty($waybill['description'])) {
                    $waybill_description = trim($waybill['description']);
                }
                
                // Fallback to miscellaneous field if direct column is empty
                if (empty($waybill_description) && !empty($waybill['miscellaneous'])) {
                    $description_misc = maybe_unserialize($waybill['miscellaneous']);
                    if (is_array($description_misc) && isset($description_misc['others']['waybill_description'])) {
                        $waybill_description = trim($description_misc['others']['waybill_description']);
                    }
                }
                ?>
                <p class="text-sm text-gray-700 <?= empty($waybill_description) ? 'italic text-gray-400' : '' ?>">
                    <?= !empty($waybill_description) ? esc_html($waybill_description) : 'No description provided' ?>
                </p>
            </div>

        </div>
    </div>

    <!-- COMPACT COST SUMMARY -->
    <?php if (class_exists('KIT_User_Roles') && KIT_User_Roles::can_see_prices()): ?>
        <div class="bg-gray-50 border border-gray-200 rounded-md p-3 mb-4">
            <h2 class="text-sm font-semibold text-gray-800 mb-2">Cost Summary</h2>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- LEFT: Components (dense) -->
                <div class="space-y-2">
                    <div class="bg-white border border-gray-200 rounded p-3">
                        <div class="flex justify-between text-xs text-gray-600">
                            <span>Mass</span>
                            <span><?= KIT_Commons::currency() . number_format($mass_rate, 2) ?> × <?= number_format($total_mass_kg, 2) ?>kg = <?= KIT_Commons::currency() . number_format($mass_charge, 2) ?></span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-600 mt-1">
                            <span>Volume</span>
                            <span><?= number_format($total_volume, 2) ?>m³ × <?= KIT_Commons::currency() . number_format($volume_rate, 2) ?> = <?= KIT_Commons::currency() . number_format($volume_display_charge, 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center mt-2 pt-2 border-t">
                            <span class="text-xs font-semibold">Basis: <?= ucfirst($preferred_charge) ?></span>
                            <span class="text-sm font-bold"><?= KIT_Commons::currency() . number_format($primary_charge, 2) ?></span>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded p-3">
                        <?php if ($waybill_items_total > 0): ?>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-600">Items value (not added)</span>
                                <span class="font-semibold"><?= KIT_Commons::currency() . number_format($waybill_items_total, 2) ?></span>
                            </div>
                            <?php if ($vat_charge > 0): ?>
                                <div class="flex justify-between text-xs mt-1">
                                    <span class="text-gray-600">VAT (10%)</span>
                                    <span class="font-semibold"><?= KIT_Commons::currency() . number_format($vat_charge, 2) ?></span>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-600">Items value</span>
                                <span class="font-semibold"><?= KIT_Commons::currency() . number_format($waybill_items_total, 2) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="flex justify-between text-xs mt-1">
                            <span class="text-gray-600">Miscellaneous</span>
                            <span class="font-semibold"><?= KIT_Commons::currency() . number_format($misc_total, 2) ?></span>
                        </div>
                    </div>

                    <div class="bg-white border border-gray-200 rounded p-3">
                        <?php

                        // Show International Price if present (regardless of VAT)
                        if (isset($misc_data['others']['international_price_rands']) && floatval($misc_data['others']['international_price_rands']) > 0): ?>
                            <div class="flex justify-between text-xs mt-1">
                                <span class="text-gray-600">Handling Fee</span>
                                <span class="font-semibold"><?= KIT_Commons::currency() . number_format($international_price_rands, 2) ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($include_sad500): ?>
                            <div class="flex justify-between text-xs"><span class="text-gray-600">SAD500</span><span class="font-medium"><?= KIT_Commons::currency() . number_format($sad500_amount, 2) ?></span></div>
                        <?php endif; ?>
                        <?php if ($include_sadc): ?>
                            <div class="flex justify-between text-xs mt-1"><span class="text-gray-600">SADC</span><span class="font-medium"><?= KIT_Commons::currency() . number_format($sadc_amount, 2) ?></span></div>
                        <?php endif; ?>
                        <?php if (!$include_sad500 && !$include_sadc && $handling_fee <= 0 && !isset($misc_data['others']['international_price_rands'])): ?>
                            <div class="text-[11px] text-gray-500">No additional charges<?php if (isset($waybill['international_price_in_rands']) && floatval($waybill['international_price_in_rands']) > 0): ?> — International Price: <span class="font-medium text-gray-700"><?= KIT_Commons::currency() . number_format(floatval($waybill['international_price_in_rands']), 2) ?></span><?php endif; ?></div>
                        <?php endif; ?>
                        <div class="flex justify-between items-center mt-2 pt-2 border-t">
                            <span class="text-xs font-semibold">Additional total</span>
                            <span class="text-sm font-bold"><?= KIT_Commons::currency() . number_format($additional_charges_total, 2) ?></span>
                        </div>

                    </div>
                </div>

                <!-- RIGHT: Totals (dense) -->
                <div class="space-y-2">
                    <div class="bg-white border border-gray-200 rounded p-3">
                        <div class="flex justify-between text-xs"><span>A. Primary</span><span class="font-medium"><?= KIT_Commons::currency() . number_format($primary_charge, 2) ?></span></div>
                        <?php if ($vat_charge > 0): ?>
                            <div class="flex justify-between text-xs mt-1"><span>B. VAT (10%)</span><span class="font-medium"><?= KIT_Commons::currency() . number_format($vat_charge, 2) ?></span></div>
                        <?php elseif ($handling_fee > 0): ?>
                            <div class="flex justify-between text-xs mt-1"><span>B. Handling Fee</span><span class="font-medium"><?= KIT_Commons::currency() . number_format($handling_fee, 2) ?></span></div>
                        <?php endif; ?>
                        <div class="flex justify-between text-xs mt-1"><span>C. Misc</span><span class="font-medium"><?= KIT_Commons::currency() . number_format($misc_total, 2) ?></span></div>
                        <div class="flex justify-between text-xs mt-1"><span>D. Additional</span><span class="font-medium"><?= KIT_Commons::currency() . number_format($additional_charges_total, 2) ?></span></div>
                        <div class="flex justify-between items-center mt-2 pt-2 border-t">
                            <span class="text-sm font-semibold">Subtotal (A + B + C + D)</span>
                            <span class="text-base font-bold"><?= KIT_Commons::currency() . number_format($calculated_total, 2) ?></span>
                        </div>
                        <?php if (!$include_vat && isset($waybill['international_price_in_rands']) && floatval($waybill['international_price_in_rands']) > 0): ?>
                            <div class="flex justify-between text-xs mt-1"><span>International Price (R)</span><span class="font-medium"><?= KIT_Commons::currency() . number_format(floatval($waybill['international_price_in_rands']), 2) ?></span></div>
                        <?php endif; ?>
                    </div>

                    <div class="bg-blue-50 border border-blue-300 rounded p-3">
                        <div class="flex justify-between items-center">
                            <span class="text-sm font-semibold">Grand Total</span>
                            <span class="text-lg font-bold text-blue-700"><?= KIT_Commons::displayWaybillTotal($grand_total_display) ?></span>
                        </div>
                        <?php if (!$totals_match): ?>
                            <div class="mt-1 text-[11px] text-red-600">
                                Stored: <?= KIT_Commons::displayWaybillTotal($stored_total) ?> (outdated)
                            </div>
                        <?php endif; ?>
                        <div class="mt-2 grid grid-cols-3 gap-2 text-[11px] text-gray-700">
                            <div>VAT: <strong><?= $include_vat ? 'Yes' : 'No' ?></strong></div>
                            <div>SAD500: <strong><?= $include_sad500 ? 'Yes' : 'No' ?></strong></div>
                            <div>SADC: <strong><?= $include_sadc ? 'Yes' : 'No' ?></strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- BASIC WAYBILL INFORMATION -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-4 md:mb-6">
        <!-- Document Information -->
        <div class="border border-gray-200 rounded-lg p-3 md:p-4 overflow-x-hidden md:overflow-x-auto">
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

        <!-- Customer Information -->
        <div class="border border-gray-200 rounded-lg p-3 md:p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Customer Information</h3>
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

        <!-- Shipment Details -->
        <div class="border border-gray-200 rounded-lg p-3 md:p-4">
            <h3 class="text-sm font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Shipment Details</h3>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-700">Origin</span>
                    <span class="text-sm font-semibold text-gray-900">
                        <?php
                        echo htmlspecialchars($originCountryName ?? 'N/A') . ",".
                         htmlspecialchars($originCityName ?? 'N/A');
                        ?>
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-700">Destination</span>
                    <span class="text-sm font-semibold text-gray-900">
                        <?php
                        echo htmlspecialchars($destinationCountryName ?? 'N/A') . ",".
                         htmlspecialchars($destinationCityName ?? 'N/A');
                        ?>
                    </span>
                </div>
				<div class="flex justify-between items-center">
					<span class="text-sm text-gray-700">Dimensions</span>
					<span class="text-sm font-semibold text-gray-900">
						<?php
							$len = isset($waybill['item_length']) && $waybill['item_length'] !== '' ? floatval($waybill['item_length']) : 0;
							$wid = isset($waybill['item_width']) && $waybill['item_width'] !== '' ? floatval($waybill['item_width']) : 0;
							$hei = isset($waybill['item_height']) && $waybill['item_height'] !== '' ? floatval($waybill['item_height']) : 0;
							echo number_format($len, 2) . ' × ' . number_format($wid, 2) . ' × ' . number_format($hei, 2) . ' cm';
						?>
					</span>
				</div>
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-700">Total Mass</span>
                    <span class="text-sm font-semibold text-gray-900">
                        <?= htmlspecialchars($waybill['total_mass_kg'] ?? '0') ?> kg
                    </span>
                </div>
				<?php
					$volume_val = isset($waybill['total_volume']) ? floatval($waybill['total_volume']) : 0.0;
					if ($volume_val <= 0 && !empty($waybill['miscellaneous'])) {
						$maybe_misc = maybe_unserialize($waybill['miscellaneous']);
						if (is_array($maybe_misc) && isset($maybe_misc['others']['total_volume'])) {
							$volume_val = floatval($maybe_misc['others']['total_volume']);
						}
					}
					$has_any_volume = $volume_val > 0;
					if ($has_any_volume): ?>
				<div class="flex justify-between items-center">
					<span class="text-sm text-gray-700">Total Volume</span>
					<span class="text-sm font-semibold text-gray-900">
						<?= number_format($volume_val, 3) ?> m³
					</span>
				</div>
				<?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 waybill-items-container">
        <div class="min-w-0">
            <div class="border border-gray-200 rounded-lg p-3 md:p-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-4 border-b border-gray-100 pb-2">Waybill Items</h3>
                <div class="overflow-x-auto">
                    <?php
                    echo KIT_Commons::waybillTrackAndData($waybill['items']);
                    ?>
                </div>
            </div>
        </div>
        <!-- Miscellaneous Items Section -->
        <div class="min-w-0">
            <div class="border border-gray-200 rounded-lg p-3 md:p-4">
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
                    <div class="overflow-x-auto min-w-0">
                        <table class="<?= KIT_Commons::tableClasses(); ?> w-full min-w-full">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="<?= KIT_Commons::thClasses() ?> text-left whitespace-nowrap">Description</th>
                                    <th class="<?= KIT_Commons::thClasses() ?> text-center whitespace-nowrap">Price</th>
                                    <th class="<?= KIT_Commons::thClasses() ?> text-center whitespace-nowrap">Qty</th>
                                    <th class="<?= KIT_Commons::thClasses() ?> text-right whitespace-nowrap">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="<?= KIT_Commons::tbodyClasses() ?>">
                                <?php $temTotal = 0;

                                foreach ($misc_data['misc_items'] as $key => $item): ?>
                                    <tr class="border-t border-gray-100">

                                        <td class="<?= KIT_Commons::tcolClasses() ?> break-words">
                                            <?= htmlspecialchars($item['misc_item']) ?></td>
                                        <td class="<?= KIT_Commons::tcolClasses() ?> text-center whitespace-nowrap"><?= KIT_Commons::currency() ?>
                                            <?= number_format($item['misc_price'], 2) ?></td>
                                        <td class="<?= KIT_Commons::tcolClasses() ?> text-center whitespace-nowrap"><?= intval($item['misc_quantity']) ?>
                                        </td>
                                        <td class="<?= KIT_Commons::tcolClasses() ?> text-right whitespace-nowrap">
                                            <?= KIT_Commons::currency() ?>
                                            <?= number_format($item['misc_price'] * $item['misc_quantity'], 2) ?>
                                        </td>
                                    </tr>
                                    <?php $temTotal += $item['misc_price'] * $item['misc_quantity']; ?>
                                <?php endforeach; ?>
                                <tr class="border-t border-gray-100">
                                    <td colspan="3" class="<?= KIT_Commons::tcolClasses() ?> text-right font-semibold whitespace-nowrap">
                                        Total</td>
                                    <td class="<?= KIT_Commons::tcolClasses() ?> font-bold whitespace-nowrap">
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

<style>
    /* Fix for overlapping tables in waybill items section */
    .waybill-items-container {
        max-width: 100%;
        overflow: hidden;
    }
    
    .waybill-items-container table {
        table-layout: auto;
        width: 100%;
        max-width: 100%;
    }
    
    .waybill-items-container .overflow-x-auto {
        max-width: 100%;
        overflow-x: auto;
        overflow-y: visible;
    }
    
    /* Ensure grid items don't overflow */
    .grid.grid-cols-1.lg\\:grid-cols-2 > div {
        min-width: 0;
        max-width: 100%;
    }
    
    /* Responsive table adjustments */
    @media (max-width: 1024px) {
        .waybill-items-container {
            width: 100%;
        }
    }
</style>