<?php if (!defined('ABSPATH')) {
    exit;
}
// Access waybill items from global scope
global $waybill_items;
?>
<div class="bg-white p-6" id="step-3">
    <?= KIT_Commons::prettyHeading([
        'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
        'words' => 'Parcels'
    ]) ?>
    <p class="text-xs text-gray-600 mb-6">
        Add items for each waybill. Each waybill section below corresponds to a waybill created in the previous steps.
    </p>

    <!-- VAT Validation Message -->
    <div id="vat-validation-message" class="mb-6 p-4 rounded-lg border-l-4 border-yellow-400 bg-yellow-50" style="display: none;">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">VAT Calculation Required</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>You selected VAT in Step 1. To calculate VAT correctly, you need to add parcels with their individual prices.</p>
                    <p class="mt-1 font-medium">Please add at least one parcel to proceed.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Container for multiple parcels sections -->
    <div id="waybill-items-sections" class="space-y-6">
        <!-- Default: Single parcels section (for backward compatibility) -->
        <div class="waybill-items-section border border-gray-200 rounded-lg p-6 bg-gray-50" data-waybill-index="0">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Waybill #1 Items</h3>
            </div>
            
            <?=
            KIT_Commons::dynamicItemsControl([
                'container_id' => 'custom-waybill-items-0',
                'button_id' => 'add-waybill-item-0',
                'group_name' => 'custom_items',
                'existing_items' => $waybill_items ?? [],
                'input_class' => 'border border-gray-300 rounded px-3 py-2 bg-white',
                'remove_btn_class' => 'bg-red-50 text-white px-3 py-2 rounded hover:bg-red-100 transition-colors duration-200',
                'add_btn_class' => 'bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors duration-200',
                'specialClass' => '!text-[10px]',
                'item_type' => 'waybill',
                'title' => 'Items',
                'subtotal_id' => 'waybill-subtotal-0',
                'show_invoices' => true,
                'waybill_no' => '' // Will be set dynamically from form via JavaScript
            ]);
            ?>
        </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="flex justify-between mt-8">
        <?php echo KIT_Commons::renderButton('Back', 'secondary', 'lg', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />',
            'iconPosition' => 'left',
            'data-target' => 'step-4',
            'classes' => 'prev-step'
        ]); ?>
        
        <?php echo KIT_Commons::renderButton($is_edit_mode ? 'Update Waybill' : 'Create Waybill', 'success', 'lg', [
            'type' => 'submit',
            'classes' => 'submit-btn',
            'id' => 'next-step-3',
            'gradient' => true
        ]);
        ?>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const vatCheckbox = document.getElementById('vat_include2') || document.getElementById('vat_include');
        const nextButton = document.getElementById('next-step-3');
        const vatMessage = document.getElementById('vat-validation-message');
        const sectionsContainer = document.getElementById('waybill-items-sections');

        // Function to check if VAT is enabled
        function isVatEnabled() {
            return vatCheckbox && vatCheckbox.checked;
        }

        // Function to count parcels across all sections
        function countWaybillItems() {
            if (!sectionsContainer) return 0;
            const allItemRows = sectionsContainer.querySelectorAll('.dynamic-item');
            return allItemRows.length;
        }

        // Function to validate and update button state
        function validateAndUpdateButton() {
            const hasVat = isVatEnabled();
            const itemCount = countWaybillItems();

            if (hasVat && itemCount === 0) {
                // VAT is checked but no items - show message and disable button
                if (vatMessage) vatMessage.style.display = 'block';
                if (nextButton) {
                    nextButton.disabled = true;
                    nextButton.classList.add('opacity-50', 'cursor-not-allowed');
                }
            } else {
                // VAT is not checked or items exist - hide message and enable button
                if (vatMessage) vatMessage.style.display = 'none';
                if (nextButton) {
                    nextButton.disabled = false;
                    nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }
        }

        // Listen for VAT checkbox changes
        if (vatCheckbox) {
            vatCheckbox.addEventListener('change', validateAndUpdateButton);
        }

        // Listen for parcels changes across all sections (using MutationObserver)
        if (sectionsContainer) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        validateAndUpdateButton();
                    }
                });
            });

            observer.observe(sectionsContainer, {
                childList: true,
                subtree: true
            });
        }

        // Initial validation
        validateAndUpdateButton();

        // Track number of waybills from previous steps
        // This is set in step4 when waybills are added
        window.waybillCount = window.waybillCount || 1;
        
        // Update items sections based on waybill count from step 4
        function updateWaybillItemsSections() {
            if (!sectionsContainer) return;
            
            const currentCount = window.waybillCount || 1;
            const existingSections = sectionsContainer.querySelectorAll('.waybill-items-section');
            const existingCount = existingSections.length;
            
            // Add missing sections
            if (currentCount > existingCount) {
                for (let i = existingCount; i < currentCount; i++) {
                    addWaybillItemsSection(i);
                }
            }
            
            // Remove extra sections
            if (currentCount < existingCount) {
                for (let i = existingCount - 1; i >= currentCount; i--) {
                    const section = sectionsContainer.querySelector(`[data-waybill-index="${i}"]`);
                    if (section) section.remove();
                }
            }
        }
        
        // Function to add a new parcels section
        function addWaybillItemsSection(waybillIndex) {
            if (!sectionsContainer) return;
            
            // Check if section already exists
            const existing = sectionsContainer.querySelector(`[data-waybill-index="${waybillIndex}"]`);
            if (existing) return;
            
            const section = document.createElement('div');
            section.className = 'waybill-items-section border border-gray-200 rounded-lg p-6 bg-gray-50';
            section.setAttribute('data-waybill-index', waybillIndex);
            
            section.innerHTML = `
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Waybill #${waybillIndex + 1} Items</h3>
                </div>
                <div id="custom-waybill-items-${waybillIndex}">
                    <p class="text-gray-500 text-sm">Items for waybill #${waybillIndex + 1} will be added here</p>
                </div>
            `;
            
            sectionsContainer.appendChild(section);
        }
        
        // Update sections when step 3 is shown
        const step3Element = document.getElementById('step-3');
        if (step3Element) {
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        if (!step3Element.classList.contains('hidden')) {
                            updateWaybillItemsSections();
                        }
                    }
                });
            });
            observer.observe(step3Element, { attributes: true });
        }
        
        // Initial update
        updateWaybillItemsSections();

        // Enhanced button hover effects
        const buttons = document.querySelectorAll('.prev-step, .next-step, .submit-btn');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                if (!this.disabled) {
                    this.style.transform = 'translateY(-1px)';
                    this.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
                }
            });

            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '';
            });

            button.addEventListener('click', function() {
                if (this.disabled) {
                    return false;
                }
            });
        });
    });
</script>