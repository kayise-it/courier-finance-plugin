<?php if (!defined('ABSPATH')) { exit; } ?>

<?php
// Include the reusable delivery card component
require_once __DIR__ . '/deliveryCard.php';
?>
<style>
/* Simple selection styling for delivery cards */
.delivery-card.selected {
    background-color: #dbeafe !important;
    border: 2px solid #3b82f6 !important;
}



/* Smooth transitions for all properties */
.delivery-card {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Enhanced hover effect */
.delivery-card:hover:not(.selected) {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
}
</style>
<div id="scheduled-deliveries-container" class="mt-4">
    <h4 class="text-md font-medium text-gray-600 mb-2">Scheduled Deliveries</h4>
    
    <!-- Smart Grouping Header -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-3">
            <span class="text-sm font-medium text-gray-700">Group by:</span>
            <select id="grouping-option" class="text-sm border border-gray-200 rounded-lg px-3 py-1 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="none">No grouping</option>
                <option value="week">Week</option>
                <option value="month">Month</option>
            </select>
        </div>
        <div class="text-sm text-gray-500">
            <span id="delivery-count"><?php echo count(KIT_Deliveries::getScheduledDeliveries()); ?></span> deliveries available
        </div>
    </div>
    
    <!-- Horizontal Row Layout - match the image layout -->
    <div id="scheduled-deliveries-list" class="flex flex-wrap gap-3 min-h-32 max-h-96 overflow-y-auto">
        <?php
        $delivery_going = KIT_Deliveries::getScheduledDeliveries();

        foreach ($delivery_going as $delivery):
            // Debug: Log what we're getting
            error_log('Delivery data: ' . print_r($delivery, true));
            // Use our reusable component
            renderDeliveryCard($delivery, 'scheduled', true, 'handleDeliveryClick');
        endforeach; ?>
    </div>
    
    <!-- Enhanced Delivery Details -->
    <div id="deliveryDetails" class="mt-6 p-4 bg-white rounded-lg border border-gray-200 hidden">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-medium text-gray-900">Selected Delivery Details</h4>
            <?php echo KIT_Commons::renderButton('Clear Selection', 'primary', 'lg', [
                'type' => 'button',
                'onclick' => 'clearDeliverySelection()',
                'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>',
                'iconPosition' => 'left',
                'gradient' => true
            ]); ?>
        </div>
        <div id="delivery-details-content" class="space-y-3"></div>
        

    </div>
    
    <!-- Empty State -->
    <div id="deliveries-empty-state" class="<?php echo empty($delivery_going) ? 'block' : 'hidden'; ?> text-center py-8">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No scheduled deliveries</h3>
        <p class="text-gray-600">No deliveries are currently scheduled</p>
    </div>
</div>

<!-- Simple JavaScript for Delivery Card Selection -->
<script>
// Simple function to select delivery cards
function selectDeliveryCard(cardElement, directionId) {
    console.log('Card clicked:', directionId);
    
    // Remove selection from all cards
    document.querySelectorAll('.delivery-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // Select the clicked card
    cardElement.classList.add('selected');
    console.log('Card selected:', directionId);
    
    // ✅ CRITICAL: Update the direction_id field for rate calculation
    const directionIdField = document.getElementById('direction_id');
    if (directionIdField) {
        directionIdField.value = directionId;
        console.log('Updated direction_id field to:', directionId);
        
        // Trigger rate fetch if mass is present
        const totalMassInput = document.getElementById('total_mass_kg');
        if (totalMassInput && totalMassInput.value && parseFloat(totalMassInput.value) > 0) {
            console.log('Mass present, triggering rate fetch...');
            if (typeof fetchRatePerKg === 'function') {
                try {
                    fetchRatePerKg();
                } catch(e) {
                    console.error('Rate fetch failed:', e);
                }
            }
        }
    } else {
        console.warn('direction_id field not found!');
    }
    
    // Force button to be disabled first, then check conditions
    const nextButton = document.getElementById('step4NextBtn');
    if (nextButton) {
        nextButton.disabled = true;
        nextButton.classList.add('opacity-50', 'cursor-not-allowed');
        nextButton.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700');
        console.log('Button temporarily disabled, checking conditions...');
    }
    
    // Show delivery details
    const deliveryDetails = document.getElementById('deliveryDetails');
    if (deliveryDetails) {
        deliveryDetails.classList.remove('hidden');
        
        // Get delivery information from the card
        const dateElement = cardElement.querySelector('.text-xs.font-bold');
        const routeElements = cardElement.querySelectorAll('.font-medium');
        
        const date = dateElement ? dateElement.textContent : 'Unknown';
        const origin = routeElements[0] ? routeElements[0].textContent : 'Unknown';
        const destination = routeElements[1] ? routeElements[1].textContent : 'Unknown';
        
        // Populate the details content
        const detailsContent = document.getElementById('delivery-details-content');
        if (detailsContent) {
            detailsContent.innerHTML = `
                <div class="bg-blue-50 p-4 rounded-lg mb-4">
                    <h5 class="font-medium text-blue-900 mb-2">Selected Delivery</h5>
                    <div class="text-sm text-blue-800">
                        <div class="font-medium">${date}</div>
                        <div>${origin} → ${destination}</div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm font-medium text-gray-500">Direction ID:</span>
                        <span class="text-sm text-gray-900 ml-2">${directionId}</span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">Date:</span>
                        <span class="text-sm text-gray-900 ml-2">${date}</span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">Origin:</span>
                        <span class="text-sm text-gray-900 ml-2">${origin}</span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">Destination:</span>
                        <span class="text-sm text-gray-900 ml-2">${destination}</span>
                    </div>
                    <div>
                        <span class="text-sm font-medium text-gray-500">Status:</span>
                        <span class="text-sm text-gray-900 ml-2">Scheduled</span>
                    </div>
                </div>
            `;
            
            // Enable the button when delivery details are successfully populated
            const nextButton = document.getElementById('step4NextBtn');
            console.log('Looking for button:', nextButton);
            if (nextButton) {
                console.log('Button found, current disabled state:', nextButton.disabled);
                console.log('Button has disabled attribute:', nextButton.hasAttribute('disabled'));
                
                nextButton.disabled = false;
                nextButton.removeAttribute('disabled'); // Remove the disabled attribute completely
                nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
                nextButton.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');
                
                console.log('Button enabled - delivery details populated successfully');
                console.log('Button disabled attribute removed:', nextButton.hasAttribute('disabled'));
                console.log('Button final disabled state:', nextButton.disabled);
            } else {
                console.log('Button NOT found!');
            }
        }
    }
    
    // Button is now enabled - no need to check additional conditions
}

// Function to check all conditions for enabling the next button
function checkButtonConditions() {
    const nextButton = document.getElementById('step4NextBtn');
    if (!nextButton) return;
    
    // Check if a delivery is selected
    const hasDeliverySelected = document.querySelector('.delivery-card.selected') !== null;
    
    // Check if warehoused option is checked
    const isWarehoused = document.getElementById('warehoused_option')?.checked || false;
    
    // Check destination fields (only required if not warehoused)
    const destinationCountry = document.getElementById('stepDestinationSelect')?.value || '';
    const destinationCity = document.getElementById('destination_city')?.value || '';
    
    let canProceed = hasDeliverySelected;
    
    if (!isWarehoused) {
        canProceed = canProceed && destinationCountry && destinationCity;
    }
    
    console.log('Button conditions check:', {
        hasDeliverySelected,
        isWarehoused,
        destinationCountry,
        destinationCity,
        canProceed
    });
    
    if (canProceed) {
        nextButton.disabled = false;
        nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
        nextButton.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');
        console.log('Next button enabled - all conditions met');
    } else {
        nextButton.disabled = true;
        nextButton.classList.add('opacity-50', 'cursor-not-allowed');
        nextButton.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700');
        console.log('Next button disabled - conditions not met');
    }
}

// Clear delivery selection
function clearDeliverySelection() {
    const deliveryDetails = document.getElementById('deliveryDetails');
    if (deliveryDetails) {
        deliveryDetails.classList.add('hidden');
    }
    
    // Remove selection from all cards
    document.querySelectorAll('.delivery-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    // ✅ CRITICAL: Clear the direction_id field
    const directionIdField = document.getElementById('direction_id');
    if (directionIdField) {
        directionIdField.value = '';
        console.log('Cleared direction_id field');
    }
    
    // Disable the button when delivery details are cleared
    const nextButton = document.getElementById('step4NextBtn');
    if (nextButton) {
        nextButton.disabled = true;
        nextButton.classList.add('opacity-50', 'cursor-not-allowed');
        nextButton.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700');
        console.log('Button disabled - delivery selection cleared');
    }
    
    // Check button conditions after clearing selection
    checkButtonConditions();
}

// Grouping functionality
document.addEventListener('DOMContentLoaded', function() {
    const groupingSelect = document.getElementById('grouping-option');
    if (groupingSelect) {
        groupingSelect.addEventListener('change', function() {
            const grouping = this.value;
            console.log('Grouping changed to:', grouping);
        });
    }
    
    // Add event listeners for destination fields and warehoused option
    const destinationCountry = document.getElementById('stepDestinationSelect');
    const destinationCity = document.getElementById('destination_city');
    const warehousedOption = document.getElementById('warehoused_option');
    
    if (destinationCountry) {
        destinationCountry.addEventListener('change', checkButtonConditions);
    }
    
    if (destinationCity) {
        destinationCity.addEventListener('change', checkButtonConditions);
    }
    
    if (warehousedOption) {
        warehousedOption.addEventListener('change', checkButtonConditions);
    }
    
    // Initial check of button conditions
    checkButtonConditions();
    
    // Add manual button control functions for testing
    window.disableStep4Button = function() {
        const nextButton = document.getElementById('step4NextBtn');
        if (nextButton) {
            nextButton.disabled = true;
            nextButton.classList.add('opacity-50', 'cursor-not-allowed');
            nextButton.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700');
            console.log('Button manually disabled');
        }
    };
    
    window.enableStep4Button = function() {
        const nextButton = document.getElementById('step4NextBtn');
        if (nextButton) {
            nextButton.disabled = false;
            nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
            nextButton.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');
            console.log('Button manually enabled');
        }
    };
});
</script>