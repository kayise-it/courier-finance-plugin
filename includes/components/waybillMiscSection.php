<?php
/**
 * Waybill Miscellaneous Items Section Component
 * Shows Miscellaneous Items for a single waybill (used in Step 6)
 * 
 * @param int $waybill_index The index of this waybill (0, 1, 2, etc.)
 */
if (!defined('ABSPATH')) {
    exit;
}

$waybill_index = $waybill_index ?? 0;
$is_first = $waybill_index === 0;

// Set global waybill_index for child components to use
$GLOBALS['current_waybill_index'] = $waybill_index;
?>
<div class="waybill-misc-section border-2 border-gray-300 rounded-lg p-6 bg-white mb-6" data-waybill-index="<?php echo esc_attr($waybill_index); ?>">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Waybill #<?php echo esc_html($waybill_index + 1); ?> - Miscellaneous Items</h2>
        <?php if (!$is_first): ?>
            <?php echo KIT_Commons::renderButton('Remove Waybill', 'danger', 'lg', ['type' => 'button', 'classes' => 'remove-waybill-misc-section bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors']); ?>
        <?php endif; ?>
    </div>

    <!-- Miscellaneous Items -->
    <div class="p-6 rounded-lg bg-slate-100">
        <table class="table w-full">
            <tbody>
                <tr id="misc-charges-container-<?php echo $waybill_index; ?>">
                    <td colspan="2">
                        <?php
                        // Use 'misc' for index 0 (single waybill), 'waybills[index][misc]' for others (parcels)
                        $misc_group_name = ($waybill_index === 0) ? 'misc' : 'waybills[' . $waybill_index . '][misc]';
                        echo KIT_Commons::dynamicItemsControl([
                            'container_id' => 'misc-items-' . $waybill_index,
                            'button_id' => 'add-misc-item-' . $waybill_index,
                            'group_name' => $misc_group_name,
                            'item_type' => 'misc',
                            'title' => 'Miscellaneous Items',
                            'input_class' => '',
                            'existing_items' => [],
                            'subtotal_id' => 'misc-total-' . $waybill_index,
                            'currency_symbol' => KIT_Commons::currency(),
                            'show_subtotal' => true,
                            'show_invoices' => true,
                            'waybill_no' => 'TEMP-' . uniqid()
                        ]);
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>









