<!-- 
$waybill['include_sad500']
$waybill['include_sadc']
$waybill['vat_include']
$waybill['warehouse']
 -->

<?php
// The logic here is broken and confusing:
// - The elseif conditions are incorrect: `isset($optionChoice) && !isset($optionChoice) == 2` will never be true.
// - The intent is to show different UI for different $optionChoice values (1, 2, 3, etc).
// - The first block is for when $optionChoice is not set (default).
// - The second and third blocks are unreachable due to the logic error.

if (!isset($optionChoice)) : ?>

    <div class="">
        <h3 class="text-xs font-semibold text-gray-700 uppercase tracking-wider mb-3">Additional Options</h3>
        <div class="">
            <div class="gap-4">
                <div class="bg-slate-100 border-dotted col-span-2 border-2 border-gray-300 rounded-lg p-4 gap-2">
                    <!-- Checkbox for SADC Certificate -->
                    <div>
                        <input type="checkbox" name="include_sadc" id="sadc_certificate" <?= (isset($waybill['include_sadc']) && $waybill['include_sadc'] == 1)? 'checked' : '' ?> class="optionz" value="1">
                        <label for="sadc_certificate">SADC Certificate</label>
                    </div>
                    <div>
                        <input type="checkbox" name="include_sad500" id="include_sad500" <?= (isset($waybill['include_sad500']) && $waybill['include_sad500'] == 1)? 'checked' : '' ?> class="" value="1">
                        <label for="include_sad500">SAD500</label>
                    </div>
                    <div>
                        <!-- The VAT option Checkbox -->
                        <input type="checkbox" name="vat_include" id="vat_include2" <?= (isset($waybill['vat_include']) && $waybill['vat_include'] == 1)? 'checked' : '' ?> value="1">
                        <label for="vat_include">VAT 22Included</label>
                    </div>
                </div>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-2">Note: VAT cannot be combined with SAD500, but SADC can be charged with VAT</p>
    </div>
<?php elseif (isset($optionChoice) && $optionChoice == 2): ?>
    <div class="gap-4">
        <div class="bg-slate-100 border-dotted col-span-2 border-2 border-gray-300 rounded-lg p-2 gap-2">
            <!-- Checkbox for SADC Certificate -->
            <div>
                <input type="checkbox" <?= (!empty($waybill['include_sadc'])) ? 'checked' : '' ?> name="include_sadc" id="sadc_certificate" class="optionz" value="1">
                <label for="sadc_certificate">SADC Certificate</label>
            </div>
            <div>
                <input type="checkbox" <?= (!empty($waybill['include_sad500'])) ? 'checked' : '' ?> name="include_sad500" id="include_sad500" class="" value="1">
                <label for="include_sad500">SAD500</label>
            </div>
        </div>
        <div class="bg-slate-100 border-dotted border-2 mt-2 border-gray-300 rounded-lg p-2">
            <!-- The VAT option Checkbox -->
            <input type="checkbox" <?= (!empty($waybill['vat_include'])) ? 'checked' : '' ?> name="vat_include" id="vat_include" value="1">
            <label for="vat_include">VAT Inc33luded</label>
        </div>
    </div>
<?php elseif (isset($optionChoice) && $optionChoice == 3): ?>
    <!-- Display only, no inputs -->
    <div class="flex flex-col gap-2">
        <div>
            <span class="font-semibold">SADC Certificate:</span>
            <span><?= !empty($waybill['include_sadc']) ? '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-500 text-green-100">
                                    Yes
                                </span>: +' . KIT_Commons::currency() . KIT_Waybills::sad() : '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-500 text-red-100">
                                    No
                                </span>' ?></span>
        </div>
        <div>
            <span class="font-semibold">SAD500:</span>
            <span><?= !empty($waybill['include_sad500']) ? '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-500 text-green-100">
                                    Yes
                                </span> +' . KIT_Commons::currency() . KIT_Waybills::sadc_certificate() : '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-500 text-red-100">
                                    No
                                </span>' ?></span>
        </div>
        <div>
            <span class="font-semibold">VAT Included:</span>
            <span><?= !empty($waybill['vat_include']) ? '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-green-500 text-green-100">
                                    Yes
                                </span> +' . KIT_Commons::currency() . ($waybill['miscellaneous']['others']['vat_total']??0) : '<span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-red-500 text-red-100">
                                    No
                                </span>' ?></span>
        </div>
    </div>
    <?php elseif (isset($optionChoice) && $optionChoice == 4):
        echo '<div class="flex gap-4">';
        echo KIT_Commons::buttonBox('SADC +'. KIT_Commons::currency() .KIT_Waybills::sad(), (!empty($waybill['include_sad500']) && $waybill['include_sad500'] == 1) ? 'highlight' : '');
        echo KIT_Commons::buttonBox('SAD500 +'. KIT_Commons::currency() .KIT_Waybills::sadc_certificate(), (!empty($waybill['include_sad500']) && $waybill['include_sad500'] == 1) ? 'highlight' : '');
        echo KIT_Commons::buttonBox('VAT +10%', (!empty($waybill['miscellaneous']['others']['vat_total']) && $waybill['miscellaneous']['others']['vat_total'] > 0) ? 'highlight' : '');
        echo '</div>';
        ?>

<?php endif; ?>