<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="bg-white p-6">
<?= KIT_Commons::prettyHeading([
                    'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
                    'words' => 'Charges & Fees'
                ]) ?>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
        <div>
            <?php require COURIER_FINANCE_PLUGIN_PATH . 'includes/components/countriesSelect.php'; ?>
            <p class="text-xs text-gray-500 mt-1" id="destination-country-help">Required for non-pending items</p>
        </div>
        <div class="<?= KIT_Commons::yspacingClass(); ?>">
            <label for="destination_city" class="<?= KIT_Commons::labelClass() ?>">Destination City</label>
            <div id="destinationWrap">
                <select class="<?= KIT_Commons::selectClass(); ?>" name="destination_city" id="destination_city">
                    <!-- Display the options here -->
                    <option value="">Select City</option>
                </select>
            </div>
            <p class="text-xs text-gray-500 mt-1" id="destination-city-help">Required for non-pending items</p>
        </div>
        <script>
            (function() {
                // Ensure the city dropdown is populated on initial render (e.g., inside modal)
                function getDestinationCountrySelect() {
                    return document.getElementById('stepDestinationSelect') || document.getElementById('destination_country_select');
                }

                function safelyPopulate() {
                    var countrySelect = getDestinationCountrySelect();
                    if (!countrySelect) return false;
                    var countryId = countrySelect.value;
                    if (!countryId) return false;
                    if (typeof handleCountryChange === 'function') {
                        handleCountryChange(countryId, 'destination_country');
                        return true;
                    }
                    return false;
                }

                // Try immediately, then retry a few times to wait for external scripts
                var attempts = 0;
                function tryPopulateWithBackoff() {
                    attempts++;
                    if (safelyPopulate()) return;
                    if (attempts < 10) {
                        setTimeout(tryPopulateWithBackoff, 100 * attempts);
                    }
                }

                // Kick off attempts after current call stack
                setTimeout(tryPopulateWithBackoff, 0);

                // Also bind to common lifecycle events
                document.addEventListener('DOMContentLoaded', tryPopulateWithBackoff);
                document.addEventListener('kit:modal:open', tryPopulateWithBackoff);
            })();
        </script>
        <div class="<?= KIT_Commons::yspacingClass(); ?>">

            <label for="pending_option" class="<?= KIT_Commons::labelClass(); ?>">Warehoused
                <input type="checkbox" name="pending" id="pending_option" value="1" class="mr-2">
            </label>
            <p class="text-xs text-gray-500 mt-1">Check this if the item is to be pending (no destination required)</p>
        </div>
        <!-- Hidden field to persist the selected delivery id across steps/submission -->
        <input type="hidden" name="delivery_id" id="selected_delivery_id" value="" />
    </div>

    <!-- Scheduled Deliveries Container -->
    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/scheduledDeliveries.php'); ?>
    
    <!-- Pre-populate fields when delivery_id is provided -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add a small delay to ensure all components are loaded
        setTimeout(() => {
            // Check if we have a delivery_id from the modal
            const deliveryIdField = document.getElementById('delivery_id');
            const selectedDeliveryId = deliveryIdField ? deliveryIdField.value : null;
            
            console.log('🔍 Delivery ID field:', deliveryIdField);
            console.log('🔍 Selected delivery ID:', selectedDeliveryId);
            console.log('🔍 All delivery ID fields:', document.querySelectorAll('[id*="delivery_id"]'));
            
            if (selectedDeliveryId && selectedDeliveryId !== '') {
                console.log('🚀 Pre-populating fields for delivery_id:', selectedDeliveryId);
                
                // First, get delivery data via AJAX to understand what we're looking for
                if (typeof fetch !== 'undefined') {
                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: new URLSearchParams({
                            action: 'get_delivery_data',
                            delivery_id: selectedDeliveryId,
                            _ajax_nonce: '<?php echo wp_create_nonce('get_delivery_data'); ?>'
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            console.log('✅ Got delivery data:', data.data);
                            
                            // Pre-populate destination country
                            const destinationCountrySelect = document.getElementById('stepDestinationSelect');
                            if (destinationCountrySelect && data.data.destination_country_id) {
                                destinationCountrySelect.value = data.data.destination_country_id;
                                console.log('🌍 Set destination country to:', data.data.destination_country_id);
                                
                                // Trigger change event to load cities
                                const changeEvent = new Event('change', { bubbles: true });
                                destinationCountrySelect.dispatchEvent(changeEvent);
                                
                                // Set destination city after cities are loaded
                                setTimeout(() => {
                                    const destinationCitySelect = document.getElementById('destination_city');
                                    console.log('🏙️ City select element:', destinationCitySelect);
                                    console.log('🏙️ Available city options:', destinationCitySelect ? destinationCitySelect.options.length : 'No select found');
                                    
                                    if (destinationCitySelect && data.data.destination_city_id) {
                                        // Try to set the city by ID first
                                        destinationCitySelect.value = data.data.destination_city_id;
                                        console.log('🏙️ Set destination city ID to:', data.data.destination_city_id);
                                        console.log('🏙️ Current city value after setting:', destinationCitySelect.value);
                                        
                                        // If ID didn't work, try by name
                                        if (destinationCitySelect.value !== data.data.destination_city_id) {
                                            console.log('🏙️ ID setting failed, trying by name:', data.data.destination_city_name);
                                            const cityOptions = destinationCitySelect.querySelectorAll('option');
                                            for (let option of cityOptions) {
                                                if (option.textContent.includes(data.data.destination_city_name) || 
                                                    data.data.destination_city_name.includes(option.textContent)) {
                                                    destinationCitySelect.value = option.value;
                                                    console.log('🏙️ Set city by name to:', option.value, option.textContent);
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // Force trigger change event on city select
                                        const cityChangeEvent = new Event('change', { bubbles: true });
                                        destinationCitySelect.dispatchEvent(cityChangeEvent);
                                        
                                        // Trigger validation check after setting city
                                        setTimeout(() => {
                                            console.log('🔍 Triggering validation check...');
                                            if (typeof checkButtonConditions === 'function') {
                                                checkButtonConditions();
                                            } else {
                                                console.log('⚠️ checkButtonConditions function not found');
                                            }
                                        }, 200);
                                    } else {
                                        console.log('❌ City select not found or no city ID provided');
                                    }
                                }, 1500); // Increased timeout to ensure cities are loaded
                            }
                            
                            // Now find and click the matching delivery card
                            findAndClickDeliveryCard(selectedDeliveryId, data.data);
                            
                        } else {
                            if ((typeof myPluginAjax !== 'undefined' && myPluginAjax.wp_debug) || (typeof window !== 'undefined' && window.WP_DEBUG)) {
                                console.error('❌ Failed to get delivery data:', data);
                            }
                            // Fallback: try to find and click delivery card without AJAX data
                            findAndClickDeliveryCard(selectedDeliveryId, null);
                            
                            // Also try warehouse fallback
                            setTimeout(() => {
                                if ((typeof myPluginAjax !== 'undefined' && myPluginAjax.wp_debug) || (typeof window !== 'undefined' && window.WP_DEBUG)) {
                                    console.log('🔄 Trying warehouse fallback...');
                                }
                                const warehouseCheckbox = document.getElementById('pending_option');
                                if (warehouseCheckbox) {
                                    warehouseCheckbox.checked = true;
                                    if ((typeof myPluginAjax !== 'undefined' && myPluginAjax.wp_debug) || (typeof window !== 'undefined' && window.WP_DEBUG)) {
                                        console.log('✅ Checked warehouse option as fallback');
                                    }
                                    
                                    // Trigger validation check
                                    if (typeof checkButtonConditions === 'function') {
                                        checkButtonConditions();
                                    }
                                }
                            }, 2000);
                        }
                    })
                    .catch(error => {
                        if ((typeof myPluginAjax !== 'undefined' && myPluginAjax.wp_debug) || (typeof window !== 'undefined' && window.WP_DEBUG)) {
                            console.error('❌ Error getting delivery data:', error);
                        }
                        // Fallback: try to find and click delivery card without AJAX data
                        findAndClickDeliveryCard(selectedDeliveryId, null);
                        
                        // Also try warehouse fallback
                        setTimeout(() => {
                            if ((typeof myPluginAjax !== 'undefined' && myPluginAjax.wp_debug) || (typeof window !== 'undefined' && window.WP_DEBUG)) {
                                console.log('🔄 Trying warehouse fallback due to error...');
                            }
                            const warehouseCheckbox = document.getElementById('pending_option');
                            if (warehouseCheckbox) {
                                warehouseCheckbox.checked = true;
                                if ((typeof myPluginAjax !== 'undefined' && myPluginAjax.wp_debug) || (typeof window !== 'undefined' && window.WP_DEBUG)) {
                                    console.log('✅ Checked warehouse option as fallback');
                                }
                                
                                // Trigger validation check
                                if (typeof checkButtonConditions === 'function') {
                                    checkButtonConditions();
                                }
                            }
                        }, 2000);
                    });
                } else {
                    if ((typeof myPluginAjax !== 'undefined' && myPluginAjax.wp_debug) || (typeof window !== 'undefined' && window.WP_DEBUG)) {
                        console.log('⚠️ No fetch available, trying fallback approach...');
                    }
                    findAndClickDeliveryCard(selectedDeliveryId, null);
                }
            }
        }, 500); // Wait 500ms for all components to load
        
        // Function to find and click the correct delivery card
        function findAndClickDeliveryCard(deliveryId, deliveryData) {
            console.log('🔍 Looking for delivery card with ID:', deliveryId);
            
            const deliveryCards = document.querySelectorAll('.delivery-card');
            console.log('📋 Found', deliveryCards.length, 'delivery cards');
            
            // Debug: Log all delivery cards and their data-index values
            deliveryCards.forEach((card, index) => {
                const cardId = card.getAttribute('data-index');
                const cardText = card.textContent.trim();
                console.log(`📋 Card ${index}: data-index="${cardId}", text="${cardText.substring(0, 50)}..."`);
            });
            
            let matchingCard = null;
            
            // Strategy 1: Try exact ID match
            deliveryCards.forEach(card => {
                const cardId = card.getAttribute('data-index');
                console.log('🔍 Checking card with data-index:', cardId);
                
                if (cardId === deliveryId) {
                    matchingCard = card;
                    console.log('✅ Found exact match!');
                }
            });
            
            // Strategy 2: If no exact match and we have delivery data, try content matching
            if (!matchingCard && deliveryData) {
                console.log('🔍 No exact match, trying content matching...');
                deliveryCards.forEach(card => {
                    const cardText = card.textContent.toLowerCase();
                    const originCountry = deliveryData.origin_country_name || '';
                    const destCountry = deliveryData.destination_country_name || '';
                    const destCity = deliveryData.destination_city_name || '';
                    
                    console.log('🔍 Card text:', cardText);
                    console.log('🔍 Looking for:', originCountry.toLowerCase(), '->', destCountry.toLowerCase());
                    
                    if (cardText.includes(originCountry.toLowerCase()) && 
                        cardText.includes(destCountry.toLowerCase())) {
                        matchingCard = card;
                        console.log('✅ Found content match!');
                    }
                });
            }
            
            // Strategy 3: If still no match, use first available card
            if (!matchingCard && deliveryCards.length > 0) {
                console.log('⚠️ No match found, using first available card as fallback');
                matchingCard = deliveryCards[0];
            }
            
            // Strategy 4: If still no card found, try to find any card with similar content
            if (!matchingCard && deliveryCards.length > 0) {
                console.log('⚠️ Still no card, trying to find any card with delivery content...');
                deliveryCards.forEach(card => {
                    const cardText = card.textContent.toLowerCase();
                    if (cardText.includes('tanzania') || cardText.includes('south africa') || cardText.includes('scheduled')) {
                        matchingCard = card;
                        console.log('✅ Found card with delivery content');
                        return;
                    }
                });
            }
            
            // Click the matching card
            if (matchingCard) {
                console.log('🖱️ Clicking delivery card...');
                console.log('🖱️ Card element:', matchingCard);
                console.log('🖱️ Card text content:', matchingCard.textContent);
                
                const directionId = matchingCard.getAttribute('data-index');
                console.log('🖱️ Direction ID from card:', directionId);
                
                if (typeof selectDeliveryCard === 'function') {
                    console.log('🖱️ Calling selectDeliveryCard function...');
                    selectDeliveryCard(matchingCard, directionId);
                    console.log('✅ Called selectDeliveryCard function');
                    
                    // Check if card is now selected
                    setTimeout(() => {
                        if (matchingCard.classList.contains('selected')) {
                            console.log('✅ Card is now selected (has selected class)');
                        } else {
                            console.log('❌ Card is not selected after function call');
                        }
                    }, 100);
                } else {
                    console.log('⚠️ selectDeliveryCard function not found, manually selecting...');
                    
                    // Force click the card element
                    matchingCard.click();
                    console.log('🖱️ Triggered click event on card');
                    
                    // Add selected class
                    matchingCard.classList.add('selected');
                    console.log('✅ Added selected class to card');
                    
                    // Set delivery ID field
                    const selectedDeliveryField = document.getElementById('selected_delivery_id');
                    if (selectedDeliveryField) {
                        selectedDeliveryField.value = deliveryId;
                        console.log('✅ Set selected_delivery_id to:', deliveryId);
                    } else {
                        console.log('❌ selected_delivery_id field not found');
                    }
                    
                    // Set direction ID field
                    const directionIdField = document.getElementById('direction_id');
                    if (directionIdField) {
                        directionIdField.value = directionId;
                        console.log('✅ Set direction_id field to:', directionId);
                    } else {
                        console.log('❌ direction_id field not found');
                    }
                    
                    // Try to trigger any change events
                    const changeEvent = new Event('change', { bubbles: true });
                    matchingCard.dispatchEvent(changeEvent);
                    console.log('✅ Triggered change event on card');
                    
                    console.log('✅ Manually selected card and set fields');
                }
            } else {
                console.log('❌ No delivery card found to select');
            }
        }
    });
    </script>
    
    <div class="flex justify-between mt-8">
        <button type="button" class="md:hidden prev-step px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400" data-target="step-3">
            Back
        </button>
        <button type="button" class="hidden md:block prev-step px-4 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400" data-target="step-3">
            Back
        </button>
        <?php echo KIT_Commons::renderButton('Next: Charges & Fees', 'secondary', 'md', [
            'id' => 'step4NextBtn',
            'disabled' => true,
            'data-target' => 'step-5',
            'classes' => 'next-step'
        ]); ?>
    </div>
</div>