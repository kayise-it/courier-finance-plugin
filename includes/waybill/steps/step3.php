<!-- Waybill Items Step -->
<div class="bg-white p-6" id="step-3">
    <!-- VAT Validation Message -->
    <div id="vat-validation-message" class="mb-6 p-4 rounded-lg border-l-4" style="display: none;">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">VAT Calculation Required</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>You selected VAT in Step 1. To calculate VAT correctly, you need to add waybill items with their individual prices.</p>
                    <p class="mt-1 font-medium">Please add at least one waybill item to proceed.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Waybill Items Control -->
    <?=
     KIT_Commons::waybillItemsControl([
        'container_id' => 'custom-waybill-items',
        'button_id' => 'add-waybill-item',
        'group_name' => 'custom_items',
        'existing_items' => [/* 
            [
                'item_name' => 'Laptop Dell XPS',
                'quantity' => 1,
                'unit_price' => 1200,
                'total_price' => 2 * 25
            ],
            [
                'item_name' => 'Wireless Mouse',
                'quantity' => 2,
                'unit_price' => 25,
                'total_price' => 2 * 25
            ] */
        ],
        'input_class' => 'border border-gray-300 rounded px-3 py-2 bg-white',
        'remove_btn_class' => 'bg-red-500 text-white px-3 py-2 rounded hover:bg-red-600 transition-colors duration-200',
        'add_btn_class' => 'bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors duration-200',
        'specialClass' => '!text-[10px]',
    ]);
    ?>

    <!-- Navigation Buttons -->
    <div class="flex justify-between mt-8">
        <button type="button" 
                class="prev-step inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed" 
                data-target="step-2">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
            Back
        </button>
       
        <button type="button" 
                id="next-step-3" 
                class="next-step inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed" 
                data-target="step-4">
            Next: Charges & Fees
            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const vatCheckbox = document.getElementById('vat_include2') || document.getElementById('vat_include');
    const nextButton = document.getElementById('next-step-3');
    const vatMessage = document.getElementById('vat-validation-message');
    const waybillItemsContainer = document.getElementById('custom-waybill-items');
    
    // Function to check if VAT is enabled
    function isVatEnabled() {
        return vatCheckbox && vatCheckbox.checked;
    }
    
    // Function to count waybill items
    function countWaybillItems() {
        if (!waybillItemsContainer) return 0;
        const itemRows = waybillItemsContainer.querySelectorAll('.waybill-item-row');
        return itemRows.length;
    }
    
    // Function to validate and update button state
    function validateAndUpdateButton() {
        const hasVat = isVatEnabled();
        const itemCount = countWaybillItems();
        
        if (hasVat && itemCount === 0) {
            // VAT is checked but no items - show message and disable button
            vatMessage.style.display = 'block';
            vatMessage.className = 'mb-6 p-4 rounded-lg border-l-4 border-yellow-400 bg-yellow-50';
            nextButton.disabled = true;
            nextButton.classList.add('opacity-50', 'cursor-not-allowed');
            nextButton.classList.remove('hover:bg-blue-700');
        } else if (hasVat && itemCount > 0) {
            // VAT is checked and items exist - hide message and enable button
            vatMessage.style.display = 'none';
            nextButton.disabled = false;
            nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
            nextButton.classList.add('hover:bg-blue-700');
        } else {
            // VAT is not checked - hide message and enable button
            vatMessage.style.display = 'none';
            nextButton.disabled = false;
            nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
            nextButton.classList.add('hover:bg-blue-700');
        }
    }
    
    // Listen for VAT checkbox changes
    if (vatCheckbox) {
        vatCheckbox.addEventListener('change', validateAndUpdateButton);
    }
    
    // Listen for waybill items changes (using MutationObserver)
    if (waybillItemsContainer) {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    validateAndUpdateButton();
                }
            });
        });
        
        observer.observe(waybillItemsContainer, {
            childList: true,
            subtree: true
        });
    }
    
    // Initial validation
    validateAndUpdateButton();
    
    // Enhanced button hover effects
    const buttons = document.querySelectorAll('.prev-step, .next-step');
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
