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
            // Use our reusable component
            renderDeliveryCard($delivery, 'scheduled', true, 'handleDeliveryClick');
        endforeach; ?>
    </div>
    
    <!-- Enhanced Delivery Details -->
    <div id="deliveryDetails" class="mt-6 p-4 bg-white rounded-lg border border-gray-200 hidden">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-medium text-gray-900">Selected Delivery Details</h4>
            
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
        }
    }
    
    // Check all conditions after delivery selection
    checkButtonConditions();
}

// Function to check all conditions for enabling the next button
function checkButtonConditions() {
    const nextButton = document.getElementById('step4NextBtn');
    if (!nextButton) return;
    
    // Check if a delivery is selected
    const hasDeliverySelected = document.querySelector('.delivery-card.selected') !== null;
    
    // Check if warehoused option is checked
    const isWarehoused = document.getElementById('warehoused_option')?.checked || false;
    
    // Check destination country (always required)
    const destinationCountry = document.getElementById('stepDestinationSelect')?.value || '';
    
    // Check destination city (only required if not warehoused)
    const destinationCity = document.getElementById('destination_city')?.value || '';
    
    // Determine if we can proceed based on the two valid scenarios:
    // 1. Delivery selected AND destination country set
    // 2. Warehoused checked AND destination country set
    let canProceed = false;
    
    if (hasDeliverySelected && destinationCountry) {
        // Scenario 1: Delivery selected + destination country
        canProceed = true;
        console.log('Condition 1 met: Delivery selected + destination country set');
    } else if (isWarehoused && destinationCountry) {
        // Scenario 2: Warehoused + destination country + direction_id = 1 for SA rates
        const directionIdField = document.getElementById('direction_id');
        const directionIdValue = directionIdField ? directionIdField.value.trim() : '';
        if (directionIdValue === '1') {
            canProceed = true;
            console.log('Condition 2 met: Warehoused + destination country + SA direction_id set');
        } else {
            console.log('Condition 2 failed: Warehoused but no SA direction_id for rate calculation');
        }
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
        nextButton.removeAttribute('disabled');
        nextButton.classList.remove('opacity-50', 'cursor-not-allowed');
        nextButton.classList.add('bg-blue-600', 'text-white', 'hover:bg-blue-700');
        console.log('Next button enabled - conditions met');
    } else {
        nextButton.disabled = true;
        nextButton.classList.add('opacity-50', 'cursor-not-allowed');
        nextButton.classList.remove('bg-blue-600', 'text-white', 'hover:bg-blue-700');
        console.log('Next button disabled - conditions not met');
    }
}

// Set default rate for warehoused items (South Africa - direction_id = 1)
function setWarehouseDefaultRate() {
    console.log('🏠 Setting warehouse default rate to South Africa (direction_id = 1)');
    
    // Set direction_id to 1 for South Africa warehouse rates
    const directionIdField = document.getElementById('direction_id');
    if (directionIdField) {
        directionIdField.value = '1';
        console.log('Set direction_id to 1 for South Africa warehouse rate calculation');
    }
    
    // Set destination country to South Africa if not already set
    const destinationCountryField = document.getElementById('stepDestinationSelect');
    if (destinationCountryField && !destinationCountryField.value) {
        // Assuming 2 is South Africa ID in your countries table
        destinationCountryField.value = '2';
        console.log('Set destination country to South Africa for warehouse');
        
        // Trigger change event to load cities
        const changeEvent = new Event('change', { bubbles: true });
        destinationCountryField.dispatchEvent(changeEvent);
    }
    
    // Set destination city to a default warehouse city if not set
    const destinationCityField = document.getElementById('destination_city');
    if (destinationCityField && !destinationCityField.value) {
        // You might need to set this to the actual warehouse city ID
        // For now, we'll let the user select or it will be set when country changes
        console.log('Destination city will be set when country loads cities');
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
        warehousedOption.addEventListener('change', function() {
            if (this.checked) {
                // Clear delivery selection when warehouse is checked
                console.log('📦 Warehouse checked - clearing delivery selection');
                clearDeliverySelection();
                
                // Set default rate charge group to South Africa for warehoused items
                setWarehouseDefaultRate();
                
                // Add a small delay to ensure clearing is complete before validation
                setTimeout(() => {
                    checkButtonConditions();
                }, 100);
            } else {
                console.log('📦 Warehouse unchecked - delivery selection can be made');
                checkButtonConditions();
            }
        });
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