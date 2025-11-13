<?php if (!defined('ABSPATH')) { exit; }

$sadcChargeValue   = class_exists('KIT_Waybills') ? floatval(KIT_Waybills::sad()) : 0.0;
$sad500ChargeValue = class_exists('KIT_Waybills') ? floatval(KIT_Waybills::sadc_certificate()) : 0.0;
$currencySymbol    = class_exists('KIT_Commons') ? KIT_Commons::currency() : 'R';
$sadcChargeDisplay = $currencySymbol . number_format($sadcChargeValue, 2);
$sad500ChargeDisplay = $currencySymbol . number_format($sad500ChargeValue, 2);

if (!isset($optionChoice)) : ?>

    <div class="">
        <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider mb-3">Additional Options</h3>
        <div class="">
            <div class="gap-4">
                <div class="bg-slate-100 border-dotted col-span-2 border-2 border-gray-300 rounded-lg p-4 gap-2">
                    <!-- Checkbox for SADC Certificate -->
                    <div>
                        <input type="checkbox" name="include_sadc" id="sadc_certificate" <?= (isset($waybill['include_sadc']) && $waybill['include_sadc'] == 1)? 'checked' : '' ?> class="optionz" value="1">
                        <label for="sadc_certificate" class="flex items-center gap-2">
                            <span>SADC Certificate</span>
                            <span class="text-[11px] text-gray-500 font-medium">+<?= $sadcChargeDisplay ?></span>
                        </label>
                    </div>
                    <div>
                        <input type="checkbox" name="include_sad500" id="include_sad500" <?= (isset($waybill['include_sad500']) && $waybill['include_sad500'] == 1)? 'checked' : '' ?> class="" value="1">
                        <label for="include_sad500" class="flex items-center gap-2">
                            <span>SAD500</span>
                            <span class="text-[11px] text-gray-500 font-medium">+<?= $sad500ChargeDisplay ?></span>
                        </label>
                    </div>
                    <div>
                        <!-- The VAT option Checkbox -->
                        <input type="checkbox" name="vat_include" id="vat_include2" <?= (isset($waybill['vat_include']) && $waybill['vat_include'] == 1)? 'checked' : '' ?> value="1">
                        <label for="vat_include">VAT Included</label>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Note: VAT cannot be combined with other fees</p>
    </div>
<?php elseif (isset($optionChoice) && $optionChoice == 2): ?>
    <div class="gap-4">
        <div class="bg-slate-100 border-dotted col-span-2 border-2 border-gray-300 rounded-lg p-2 gap-2">
            <!-- Checkbox for SADC Certificate -->
            <div>
                <input type="checkbox" <?= (!empty($waybill['include_sadc'])) ? 'checked' : '' ?> name="include_sadc" id="sadc_certificate" class="optionz" value="1">
                <label for="sadc_certificate" class="flex items-center gap-2">
                    <span>SADC Certificate</span>
                    <span class="text-[11px] text-gray-500 font-medium">+<?= $sadcChargeDisplay ?></span>
                </label>
            </div>
            <div>
                <input type="checkbox" <?= (!empty($waybill['include_sad500'])) ? 'checked' : '' ?> name="include_sad500" id="include_sad500" class="" value="1">
                <label for="include_sad500" class="flex items-center gap-2">
                    <span>SAD500</span>
                    <span class="text-[11px] text-gray-500 font-medium">+<?= $sad500ChargeDisplay ?></span>
                </label>
            </div>
        </div>
        <div class="bg-slate-100 border-dotted border-2 mt-2 border-gray-300 rounded-lg p-2">
            <!-- The VAT option Checkbox -->
            <input type="checkbox" <?= (!empty($waybill['vat_include'])) ? 'checked' : '' ?> name="vat_include" id="vat_include" value="1">
            <label for="vat_include">VAT Included (10%)</label>
        </div>
    </div>
<?php elseif (isset($optionChoice) && $optionChoice == 3): ?>
    <!-- Display only, no inputs -->
    <div class="flex flex-col gap-2">
        <div>
            <span class="font-semibold">SADC Certificate:</span>
            <span><?= !empty($waybill['include_sadc']) ? '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-500 text-green-100">
                                    Yes
                                </span>: +' . $sadcChargeDisplay : '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-500 text-red-100">
                                    No
                                </span>' ?></span>
        </div>
        <div>
            <span class="font-semibold">SAD500:</span>
            <span><?= !empty($waybill['include_sad500']) ? '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-500 text-green-100">
                                    Yes
                                </span> +' . $sad500ChargeDisplay : '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-500 text-red-100">
                                    No
                                </span>' ?></span>
        </div>
        <div>
            <span class="font-semibold">VAT Included:</span>
            <span><?= !empty($waybill['vat_include']) ? '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-500 text-blue-100">
                                    Yes
                                </span> +' . $currencySymbol . ($waybill['miscellaneous']['others']['vat_total']??0) : '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-500 text-red-100">
                                    No
                                </span>' ?></span>
        </div>
        <?php if (empty($waybill['vat_include'])): ?>
        <div>
            <span class="font-semibold">Agent Clearing & Documentation:</span>
            <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-blue-500 text-blue-100">
                Yes
            </span>: +<?= $currencySymbol . number_format(KIT_Waybills::international_price_in_rands(), 2) ?>
        </div>
        <?php endif; ?>
    </div>
    <?php elseif (isset($optionChoice) && $optionChoice == 4):
        echo '<div class="flex gap-4">';
        
        // SADC Certificate button
        $sadcHighlight = (!empty($waybill['include_sadc']) && $waybill['include_sadc'] == 1);
        echo KIT_Commons::renderButton('SADC Certificate +' . $sadcChargeDisplay, $sadcHighlight ? 'warning' : 'secondary', 'sm', [
            'classes' => $sadcHighlight ? 'bg-yellow-400 text-black hover:bg-yellow-500' : '',
            'gradient' => $sadcHighlight
        ]);
        
        // SAD500 button
        $sad500Highlight = (!empty($waybill['include_sad500']) && $waybill['include_sad500'] == 1);
        echo KIT_Commons::renderButton('SAD500 +' . $sad500ChargeDisplay, $sad500Highlight ? 'warning' : 'secondary', 'sm', [
            'classes' => $sad500Highlight ? 'bg-yellow-400 text-black hover:bg-yellow-500' : '',
            'gradient' => $sad500Highlight
        ]);
        
        // VAT button
        $vatHighlight = (!empty($waybill['miscellaneous']['others']['vat_total']) && $waybill['miscellaneous']['others']['vat_total'] > 0);
        echo KIT_Commons::renderButton('VAT +10%', $vatHighlight ? 'warning' : 'secondary', 'sm', [
            'classes' => $vatHighlight ? 'bg-yellow-400 text-black hover:bg-yellow-500' : '',
            'gradient' => $vatHighlight
        ]);
        
        // Agent Clearing button
        if (empty($waybill['vat_include'])) {
            echo KIT_Commons::renderButton('Agent Clearing & Documentation +' . $currencySymbol . number_format(KIT_Waybills::international_price_in_rands(), 2), 'warning', 'sm', [
                'classes' => 'bg-yellow-400 text-black hover:bg-yellow-500',
                'gradient' => true
            ]);
        }
        echo '</div>';
        ?>

<?php endif; ?>