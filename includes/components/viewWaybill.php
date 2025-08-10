<?php
// Determine which charge is greater
$mass_charge = floatval($waybill['mass_charge'] ?? 0);
$volume_charge = floatval($waybill['volume_charge'] ?? 0);

$is_mass_greater = $mass_charge > $volume_charge;
$is_volume_greater = $volume_charge > $mass_charge;
$is_equal = $mass_charge === $volume_charge;
?>
<div class="max-w-6xl mx-auto p-6 md:space-y-6 bg-white rounded-lg shadow-md">
    <div class="flex flex-col space-y-6 justify-between items-start border-b pb-4">
        <div class="grid grid-cols-2 w-full">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Waybill #<?= htmlspecialchars($waybill['waybill_no']) ?>
                </h1>
                <div class="flex items-center mt-2">
                    <?php
                    $pdfVerifier = KIT_Waybills::pdfVerifier($waybill['waybill_no'], $waybill_id = null);
                    if ($pdfVerifier['soWhat']) {
                        echo KIT_Commons::statusBadge('approved');
                    } else {
                        echo KIT_Commons::statusBadge('pending');
                    }
                    ?>
                    <span class="ml-2 text-xs text-gray-500">
                        <?php
                        $status_text = ucfirst($waybill['approval']);
                        $action_text = $waybill['approval'] === 'pending' ? 'Pending' : ($waybill['approval'] === 'rejected' ? 'Rejected' : ($waybill['approval'] === 'completed' ? 'Completed' : 'Approved'));

                        echo $action_text . ' By: ' . $waybill['approved_by_username'];
                        ?>
                    </span>
                    <span class="ml-2 text-xs text-gray-500">
                        Last Updated: <?= date('M j, Y', strtotime($waybill['last_updated_at'])) ?>
                    </span>
                </div>
            </div>
            <div class="text-right">
                <h1 class="display-3 font-bold">Total Waybill: <?= KIT_Commons::currency() . number_format($waybill['product_invoice_amount'], 2) ?></h1>
            </div>
        </div>

        <div class="flex space-x-3">
            <?php
            $pdfVerifier = KIT_Waybills::pdfVerifier($waybill['waybill_no'], $waybill_id = null);
            if ($pdfVerifier['soWhat']) { ?>
                <div class="flex flex-col">
                    <span class="opacity-0 text-gray-600 font-bold">Invoice Status:</span>
                    <a href="<?php echo admin_url('admin-ajax.php?action=generate_pdf&waybill_no=' . $waybill['waybill_no'] . '&pdf_nonce=' . wp_create_nonce("pdf_nonce")); ?>"
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
                <?= KIT_Commons::waybillQuoteStatus($waybill['waybill_no'], $waybill['id'], 'quoted', $waybill['approval'], 'select'); ?>
            </div>
            <div class="flex flex-col">
                <label class="<?= KIT_Commons::labelClass() ?>">Approval Status:</label>
                <?= KIT_Commons::waybillApprovalStatus($waybill['waybill_no'], $waybill['id'], 'quoted', $waybill['approval'], 'select'); ?>
            </div>
            <?php if ($waybill['warehouse']): ?>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Warehoused:</label>
                    <?= KIT_Commons::statusBadge($waybill['status']); ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="grid grid-cols-2 w-full">
            <div class="ps">
                <?= KIT_Commons::h2tag(['title' => 'Waybill Description', 'class' => '']) ?>
                <p class="text xs"><?= $waybill['miscellaneous']['others']['waybill_description'] ?></p>
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
                        'value' => KIT_Commons::currency() . number_format($waybill['product_invoice_amount'], 2),
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
                // Variables are already defined at the top of the file
                $total_mass_kg = floatval($waybill['total_mass_kg'] ?? 0);
                $total_volume = floatval($waybill['miscellaneous']['others']['total_volume'] ?? 0);

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
                    'value' => KIT_Commons::currency() . ($waybill['miscellaneous']['others']['mass_rate'] ?? 0) . ' x ' .
                        number_format($total_mass_kg, 2) . 'kg = ' .
                        KIT_Commons::currency() . number_format($mass_charge, 2) . $mass_label,
                    'classlabel' => '',
                    'classP' => '',
                    'onclick' => '',
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
                ]);
                echo '</div>';

                // Charge Basis output (dynamic)
                echo '<div class="flex gap-4">';
                echo KIT_Commons::LText([
                    'label' => "Charge Basis:",
                    'value' => ucfirst($waybill['charge_basis']),
                    'classlabel' => '',
                    'classP' => '',
                    'onclick' => '',
                ]);
                echo '</div>';

                //Waybill misc total:
                echo '<div class="flex gap-2">';
                echo KIT_Commons::LText([
                    'label' => "Waybill Amount:",
                    'value' => ($mass_charge > $volume_charge) ? KIT_Commons::currency() . number_format($mass_charge, 2) : KIT_Commons::currency() . number_format($volume_charge, 2),
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

                //Grand Total:
                echo '<div class="flex gap-2">';
                echo KIT_Commons::LText([
                    'label' => "Total:",
                    'value' => KIT_Commons::currency() . number_format($waybill['product_invoice_amount'], 2),
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
                        <?= htmlspecialchars($waybill['destination_country']) ?>
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
        <div>
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

                        $misc_result = KIT_Waybills::getMiscCharges($misc_data_for_processing, []);
                        $misc_total = floatval($misc_result->misc_total);
                    }
                ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="<?= KIT_Commons::thClasses(); ?>">Description</th>
                                    <th class="<?= KIT_Commons::thClasses(); ?>">Price</th>
                                    <th class="<?= KIT_Commons::thClasses(); ?>">Qty</th>
                                    <th class="<?= KIT_Commons::thClasses(); ?>">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php $temTotal = 0;

                                foreach ($misc_data['misc_items'] as $key => $item): ?>
                                    <tr>

                                        <td class="<?= KIT_Commons::tcolClasses() ?>">
                                            <?= htmlspecialchars($item['misc_item']) ?></td>
                                        <td class="<?= KIT_Commons::tcolClasses() ?>"><?= KIT_Commons::currency() ?>
                                            <?= number_format($item['misc_price'], 2) ?></td>
                                        <td class="<?= KIT_Commons::tcolClasses() ?>"><?= intval($item['misc_quantity']) ?>
                                        </td>
                                        <td class="<?= KIT_Commons::tcolClasses() ?>">
                                            <?= KIT_Commons::currency() ?>
                                            <?= number_format($item['misc_price'] * $item['misc_quantity'], 2) ?>
                                        </td>
                                    </tr>
                                    <?php $temTotal += $item['misc_price'] * $item['misc_quantity']; ?>
                                <?php endforeach; ?>
                                <tr>
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
                    <p class="text-sm text-gray-500">No miscellaneous items added</p>
                <?php endif; ?>
            </div>
        </div>
    </div>


    
    <div class="mt-6">
        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Notes Section</h2>
            <div class="space-y-2">
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Notes:</label>
                    <span class="font-medium"><?= htmlspecialchars($waybill['notes'] ?? 'No notes available') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add some interactive functionality
document.addEventListener('DOMContentLoaded', function() {
    // Add click-to-copy functionality for tracking number
    const trackingElement = document.querySelector('.font-mono');
    if (trackingElement) {
        trackingElement.style.cursor = 'pointer';
        trackingElement.title = 'Click to copy tracking number';
        trackingElement.addEventListener('click', function() {
            navigator.clipboard.writeText(this.textContent.trim()).then(function() {
                // Show brief success feedback
                const originalBg = trackingElement.style.background;
                trackingElement.style.background = '#10b981';
                trackingElement.style.color = 'white';
                setTimeout(() => {
                    trackingElement.style.background = originalBg;
                    trackingElement.style.color = '';
                }, 1000);
            });
        });
    }
    
    // Auto-refresh status if pending
    <?php if ($approval_status === 'pending'): ?>
    const statusRefreshInterval = setInterval(function() {
        // You can implement auto-refresh logic here if needed
        // For now, just log that we're checking
        console.log('Checking for status updates...');
    }, 30000); // Check every 30 seconds
    <?php endif; ?>
});
</script>