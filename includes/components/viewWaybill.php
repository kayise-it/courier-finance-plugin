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
        <div class="ps">
            <?= KIT_Commons::h2tag(['title' => 'Waybill Description', 'class' => '']) ?>
            <p class="text xs"><?= $waybill['miscellaneous']['others']['waybill_description'] ?></p>
        </div>
    </div>


    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
        <!-- Waybill Details -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Waybill Details</h2>
            <div class="space-y-3">
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Waybill Number:</label>
                    <span class="font-medium"><?= htmlspecialchars($waybill['waybill_no']) ?></span>
                </div>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Tracking Number:</label>
                    <span class="font-medium"><?= htmlspecialchars($waybill['tracking_number']) ?></span>
                </div>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Invoice Number:</label>
                    <span class="font-medium"><?= htmlspecialchars($waybill['product_invoice_number']) ?></span>
                </div>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Waybill Amoundst:</label>
                    <span class="font-medium"><?= KIT_Commons::currency() ?>
                        <?php
                        echo number_format($waybill['product_invoice_amount'], 2); ?>
                    </span>
                    <p class="" style="font-size: 9px">
                        <span class="font-medium"><?= KIT_Commons::currency() ?>
                            <?php
                            if ($prefferedCharge == 'mass') {
                                echo number_format($mass_charge, 2);
                            } else {
                                echo number_format($volume_charge, 2);
                            }
                            ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Customer Details -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Customer Details</h2>
            <div class="space-y-3">
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Customer Name:</label>
                    <span class="font-medium"><?= $waybill['customer_name'] ?></span>
                </div>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Customer Surname:</label>
                    <span class="font-medium"><?= $waybill['customer_surname'] ?></span>
                </div>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Contact:</label>
                    <span class="font-medium"><?= htmlspecialchars($waybill['cell']) ?></span>
                </div>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Email:</label>
                    <span class="font-medium"><?= $waybill['email_address'] ?></span>
                </div>

            </div>
        </div>
        <!-- Shipment Details -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h2 class="text-lg font-semibold text-gray-700 mb-3 border-b pb-2">Cost Details</h2>

            <div class="space-y-3">
                <div class="flex flex-col">
                    <div><label class="<?= KIT_Commons::labelClass() ?>">Total Mass32:</label></div>
                    <div class="relative">
                        <span class="font-medium flex items-center">
                            <?= KIT_Commons::currency() . ($mass_rate ?? 0) ?> x
                            <?= number_format($waybill['total_mass_kg'] ?? 0, 2) ?> kg (<?= KIT_Commons::currency() ?><?= number_format($mass_charge, 2) ?>)
                            <?php if ($is_mass_greater): ?>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-500 text-green-100">
                                    Highest
                                </span>
                            <?php elseif ($is_volume_greater): ?>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-500 text-red-100">
                                    Lowest
                                </span>
                            <?php elseif ($is_equal): ?>
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                    Equal
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Total Volume:</label>
                    <span class="font-medium flex items-center">
                        <?= number_format($waybill['miscellaneous']['others']['total_volume'], 2) ?> m³ (<?= KIT_Commons::currency() ?><?= number_format($volume_charge, 2) ?>)
                        <?php if ($is_volume_greater): ?>
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-500 text-green-100">
                                Highest
                            </span>
                        <?php elseif ($is_mass_greater): ?>
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-500 text-red-100">
                                Lowest
                            </span>
                        <?php elseif ($is_equal): ?>
                            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                                Equal
                            </span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Charge Basis:</label>
                    <span class="font-medium"><?= ucfirst($waybill['charge_basis']) ?></span>
                </div>

                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Wayb3ill Amount:</label>
                    <span class="font-medium"><?= KIT_Commons::currency() ?>
                        <?php
                        if ($prefferedCharge == 'mass') {
                            echo number_format($mass_charge, 2);
                        } else {
                            echo number_format($volume_charge, 2);
                        }
                        ?>
                    </span>
                </div>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Waybill misc total:</label>
                    <span class="font-medium"><?= KIT_Commons::currency() ?>
                        <?= number_format(($waybill['miscellaneous']['misc_total']) ?? 0, 2) ?></span>
                </div>
                <div class="flex flex-col">
                    <label class="<?= KIT_Commons::labelClass() ?>">Total:</label>
                    <?php
                    $total_amount = $waybill['product_invoice_amount'];
                    ?>
                    <span class="font-medium"><?= KIT_Commons::currency() ?>
                        <?= number_format($total_amount, 2) ?></span>
                </div>

                <div class="flex items-center">
                    <?php
                    $optionChoice = 3;
                    require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/additionCharges.php'); ?>
                </div>
                <div class="flex items-center">
                    <label class="<?= KIT_Commons::labelClass() ?>"></label>
                    <span class="text-xs text-gray-500 italic">
                        Waybill Amount + Misc Total
                    </span>
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

                        $misc_result = self::getMiscCharges($misc_data_for_processing, []);
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