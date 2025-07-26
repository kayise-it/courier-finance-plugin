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
        <div class="flex gap-4">
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-slate-100 border-dotted col-span-2 border-2 border-gray-300 rounded-lg p-4 grid grid-cols-2 gap-2">
                    <!-- Checkbox for SADC Certificate -->
                    <div>
                        <input type="checkbox" name="include_sadc" id="sadc_certificate" class="optionz" value="1">
                        <label for="sadc_certificate">SADC Certificate</label>
                    </div>
                    <div>
                        <input type="checkbox" name="include_sad500" id="include_sad500" class="" value="1">
                        <label for="include_sad500">SAD500</label>
                    </div>
                    <div>
                        <!-- The VAT option Checkbox -->
                        <input type="checkbox" name="vat_include" id="vat_include" value="1">
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
                <label for="sadc_certificate">SADC Certificate</label>
            </div>
            <div>
                <input type="checkbox" <?= (!empty($waybill['include_sad500'])) ? 'checked' : '' ?> name="include_sad500" id="include_sad500" class="optionz" value="1">
                <label for="include_sad500">SAD500</label>
            </div>
        </div>
        <div class="bg-slate-100 border-dotted border-2 mt-2 border-gray-300 rounded-lg p-2">
            <!-- The VAT option Checkbox -->
            <input type="checkbox" <?= (!empty($waybill['vat_include'])) ? 'checked' : '' ?> name="vat_include" id="vat_include" value="1">
            <label for="vat_include">VAT Included</label>
        </div>
    </div>
<?php elseif (isset($optionChoice) && $optionChoice == 3): ?>
    <!-- Display only, no inputs -->
    <div class="flex flex-col gap-2">
        <div>
            <span class="font-semibold">SADC Certificate:</span>
            <span><?= !empty($waybill['include_sadc']) ? 'Yes' : 'No' ?></span>
        </div>
        <div>
            <span class="font-semibold">SAD500:</span>
            <span><?= !empty($waybill['include_sad500']) ? 'Yes' : 'No' ?></span>
        </div>
        <div>
            <span class="font-semibold">VAT Included:</span>
            <span><?= !empty($waybill['vat_include']) ? 'Yes' : 'No' ?></span>
        </div>
    </div>
<?php endif; ?>