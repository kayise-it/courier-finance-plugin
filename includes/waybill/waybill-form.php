<?php
// waybill-form.php
// Start session at the very top to preserve form data - TEMPORARILY DISABLED FOR DEBUGGING
// if (!session_id()) {
//     session_start();
// }

if (!defined('ABSPATH')) {
    exit;
}

// Debug: Check if required classes exist
if (!class_exists('KIT_Customers')) {
    error_log('KIT_Customers class not found in waybill-form.php');
    echo '<div class="error"><p>Error: KIT_Customers class not found.</p></div>';
    return;
}

if (!class_exists('KIT_Deliveries')) {
    error_log('KIT_Deliveries class not found in waybill-form.php');
    echo '<div class="error"><p>Error: KIT_Deliveries class not found.</p></div>';
    return;
}

if (!class_exists('KIT_Commons')) {
    error_log('KIT_Commons class not found in waybill-form.php');
    echo '<div class="error"><p>Error: KIT_Commons class not found.</p></div>';
    return;
}

// Debug: Check if required functions exist
if (!function_exists('tholaMaCustomer')) {
    error_log('tholaMaCustomer function not found in waybill-form.php');
    echo '<div class="error"><p>Error: tholaMaCustomer function not found.</p></div>';
    return;
}

// Handle form submissions between steps - TEMPORARILY DISABLED FOR DEBUGGING
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     // Save all submitted data to session
//     $_SESSION['waybill_form_data'] = array_merge($_SESSION['waybill_form_data'] ?? [], $_POST);

//     // Handle step navigation
//     $current_step = isset($_POST['current_step']) ? intval($_POST['current_step']) : 1;

//     if (isset($_POST['next_step'])) {
//         $next_step = min($current_step + 1, 4);
//     } elseif (isset($_POST['prev_step'])) {
//         $next_step = max($current_step - 1, 1);
//     } else {
//         $next_step = $current_step;
//     }

//     // Redirect to the next step
//     wp_redirect(add_query_arg(['step' => $next_step], wp_get_referer()));
//     exit;
// }

// Restore form data from session - TEMPORARILY DISABLED FOR DEBUGGING
// $form_data = $_SESSION['waybill_form_data'] ?? [];
$form_data = [];


// Dummy customer list
if (isset($_GET['cust_id'])) {
    $customer_id = intval($_GET['cust_id']);
} else {
    $customer_id = 0;
}

// Debug: Try to get customers
try {
    $customers = tholaMaCustomer();
    if (empty($customers)) {
        error_log('tholaMaCustomer returned empty result');
        $customers = [];
    }
} catch (Exception $e) {
    error_log('Error calling tholaMaCustomer: ' . $e->getMessage());
    $customers = [];
}

$selected_customer_key = $customer_id;
$is_existing_customer = false;

// Search through customers to find matching cust_id
foreach ($customers as $customer) {
    if ($customer->cust_id == $selected_customer_key) {
        $is_existing_customer = true;
        break;
    }
}

// Check if editing
$waybill_id = isset($_GET['waybill_id']) ? intval($_GET['waybill_id']) : 0;

$waybill = null;
$is_edit_mode = false;

$set_cust_id = isset($_GET['cust_id']) ? intval($_GET['cust_id']) : "";
if ($waybill_id > 0) {
    global $wpdb;
    $waybill = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}kit_waybills WHERE id = %d", $waybill_id),
        ARRAY_A
    );

    // Convert null values to empty strings to prevent deprecation warnings
    if ($waybill) {
        $waybill = array_map(function($value) {
            return $value === null ? '' : $value;
        }, $waybill);
    }
    $is_edit_mode = !is_null($waybill);
}
$breadlinks = "";


// Get customer ID - first from GET param, then from waybill if editing
if (isset($_GET['cust_id'])) {
    $set_cust_id = intval($_GET['cust_id']);
} elseif ($waybill_id > 0 && !empty($waybill->customer_id)) {
    $set_cust_id = intval($waybill->customer_id);
} else {
    $set_cust_id = 0;
}


// Debug: Try to get customer name
try {
    $customer_name = KIT_Customers::gamaCustomer($set_cust_id);
} catch (Exception $e) {
    error_log('Error calling KIT_Customers::gamaCustomer: ' . $e->getMessage());
    $customer_name = 'Unknown Customer';
}

// Step handling - now using the persisted data
$current_step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$max_steps = 4;

$form_action = $is_edit_mode
    ? admin_url('admin-post.php?action=update_waybill_action')
    : admin_url('admin-post.php?action=add_waybill_action');

// Debug: Try to get scheduled deliveries
try {
    $scheduled_deliveries = KIT_Deliveries::getScheduledDeliveries();
    if (empty($scheduled_deliveries)) {
        error_log('KIT_Deliveries::getScheduledDeliveries returned empty result');
        $scheduled_deliveries = [];
    }
} catch (Exception $e) {
    error_log('Error calling KIT_Deliveries::getScheduledDeliveries: ' . $e->getMessage());
    $scheduled_deliveries = [];
}

?>

<div class="wrap">
    <?php
    echo KIT_Commons::showingHeader([
        'title' => 'Capturess New Waybill',
        'desc' => ''
    ]);
    ?>
    <div class="ajaxReload mx-auto max-w-7xl">
        <?php
        $scheduled_deliveries = KIT_Deliveries::getScheduledDeliveries();

        if (!empty($scheduled_deliveries)):
            $encoded_customers = base64_encode(json_encode($customers));
            echo do_shortcode('[waybill_multiform 
           form_action="' . esc_url($form_action) . '" 
           waybill_id="' . esc_attr($waybill_id) . '" 
           is_edit_mode="' . ($is_edit_mode ? '1' : '0') . '" 
           waybill="' . esc_attr(json_encode($waybill)) . '" 
           customer="' . esc_attr($encoded_customers) . '"
           is_existing_customer="' . ($is_existing_customer ? '1' : '0') . '" 
           customer_id="' . esc_attr($set_cust_id) . '"]');

        else: 
            // Get delivery form content for modal
            $delivery_form_content = KIT_Deliveries::deliveryForm(null, true);
            
            // Render Create Delivery Modal
            $create_delivery_modal = KIT_Modal::render(
                'create-delivery-modal',
                'Create New Delivery',
                $delivery_form_content,
                '3xl',
                false,
                ''
            );
            
            echo $create_delivery_modal;
            ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p class="text-bold mb-4">No Deliveries Available</p>
                <p class="text-bold mb-4">Create a delivery to proceed with creating a waybill</p>
                <?php
                echo KIT_Commons::renderButton(
                    'Create Delivery',
                    'primary',
                    'md',
                    [
                        'type' => 'button',
                        'modal' => 'create-delivery-modal',
                        'classes' => 'create-delivery-btn',
                        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />',
                        'iconPosition' => 'left',
                        'gradient' => true
                    ]
                );
                ?>
            </div>
            <script>
            // Populate delivery form with dummy data for testing when modal opens
            (function() {
                function populateDeliveryForm() {
                    // Destination Country: Tanzania (ID: 2)
                    const destinationCountrySelect = document.getElementById('destination_country_select');
                    if (destinationCountrySelect && destinationCountrySelect.value !== '2') {
                        destinationCountrySelect.value = '2'; // Tanzania
                        console.log('✅ Set destination country to Tanzania (ID: 2)');
                        
                        // Trigger change to load cities
                        const changeEvent = new Event('change', { bubbles: true });
                        destinationCountrySelect.dispatchEvent(changeEvent);
                        
                        // Wait for cities to load, then set Arusha
                        setTimeout(function() {
                            const destinationCitySelect = document.getElementById('destination_city_select');
                            if (destinationCitySelect) {
                                // Try to find Arusha by text or set ID 8
                                let found = false;
                                for (let option of destinationCitySelect.options) {
                                    const optionText = option.textContent.toLowerCase().trim();
                                    if (optionText.includes('arusha') || option.value === '8') {
                                        destinationCitySelect.value = option.value;
                                        found = true;
                                        console.log('✅ Set destination city to Arusha (ID: ' + option.value + ')');
                                        break;
                                    }
                                }
                                if (!found) {
                                    console.warn('⚠️ Arusha not found in city dropdown');
                                }
                            }
                        }, 600);
                    }
                    
                    // Dispatch Date: 25 Nov 2025
                    const dispatchDate = document.getElementById('dispatch_date');
                    if (dispatchDate && !dispatchDate.value) {
                        dispatchDate.value = '2025-11-25';
                        console.log('✅ Set dispatch date to 2025-11-25');
                    }
                    
                    // Driver: James (ID: 3)
                    const driverSelect = document.getElementById('driver_id');
                    if (driverSelect && driverSelect.value !== '3') {
                        // Try to find James by name or ID
                        let found = false;
                        for (let option of driverSelect.options) {
                            const optionText = option.textContent.toLowerCase().trim();
                            if (optionText.includes('james') || option.value === '3') {
                                driverSelect.value = option.value;
                                found = true;
                                console.log('✅ Set driver to James (ID: ' + option.value + ')');
                                break;
                            }
                        }
                        if (!found) {
                            console.warn('⚠️ Driver James not found in dropdown');
                        }
                    }
                }
                
                // Watch for modal opening
                document.addEventListener('DOMContentLoaded', function() {
                    const modal = document.getElementById('create-delivery-modal');
                    if (modal) {
                        // Use MutationObserver to detect when modal becomes visible
                        const observer = new MutationObserver(function(mutations) {
                            if (modal.classList.contains('flex') && !modal.classList.contains('hidden')) {
                                // Modal is open, populate fields after a short delay
                                setTimeout(populateDeliveryForm, 200);
                            }
                        });
                        
                        // Start observing
                        observer.observe(modal, {
                            attributes: true,
                            attributeFilter: ['class']
                        });
                        
                        // Also listen for click events on the modal open button
                        const openButton = document.querySelector('[modal="create-delivery-modal"], [data-modal="create-delivery-modal"], .create-delivery-btn');
                        if (openButton) {
                            openButton.addEventListener('click', function() {
                                setTimeout(populateDeliveryForm, 400);
                            });
                        }
                        
                        // Also try to populate if modal is already open (for page refreshes)
                        if (modal.classList.contains('flex') && !modal.classList.contains('hidden')) {
                            setTimeout(populateDeliveryForm, 500);
                        }
                    }
                });
            })();
            </script>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Customer selection handling
            const customerSelect = document.getElementById('customer-select');
            const custIdInput = document.getElementById('cust_id');
            const nameInput = document.getElementById('customer_name');
            const surnameInput = document.getElementById('surname');
            const cellInput = document.getElementById('cell');
            const addressInput = document.getElementById('address');

            

            // Handle dropdown changes
            if (customerSelect) {
            customerSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];

                // Access data-* attributes
                const name = selectedOption.getAttribute('data-name');
                const surname = selectedOption.getAttribute('data-surname');
                const cell = selectedOption.getAttribute('data-cell');
                const address = selectedOption.getAttribute('data-address');
                const companyName = selectedOption.getAttribute('data-company-name');


                if (this.value === 'new') {
                    // Clear all fields for new customer
                    nameInput.value = '';
                    surnameInput.value = '';
                    cellInput.value = '';
                    addressInput.value = '';
                    custIdInput.value = '0';
                    
                    // Clear company name field
                    const companyNameInput = document.getElementById('company_name');
                    if (companyNameInput) {
                        companyNameInput.value = '';
                    }
                } else if (this.value) {
                    // Populate fields with selected customer data
                    jQuery("#customer_name").value = name ;
                    jQuery("#customer_surname").value = surname;
                    cellInput.value = cell ||'';
                    addressInput.value = address ||'';
                    custIdInput.value = '0';
                    
                    // Populate company name field
                    const companyNameInput = document.getElementById('company_name');
                    if (companyNameInput) {
                        companyNameInput.value = companyName || '';
                    }
                }
            });

            // Check for initial customer ID on page load
            const initialCustomerId = custIdInput.value;
            if (initialCustomerId && initialCustomerId !== '0') {
                populateCustomerDetails(initialCustomerId);
            }
            }

            // Handle delivery creation form submission via AJAX
            // Use a more specific selector and higher priority
            jQuery(document).on('submit', '#create-delivery-modal #edit-delivery-form', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                console.log('Intercepting delivery form submission in create-delivery-modal');
                
                const $form = jQuery(this);
                const submitButton = $form.find('#save-delivery-btn');
                const originalButtonText = submitButton.length ? submitButton.text() : 'Saving...';
                
                // Show loading state
                if (submitButton.length) {
                    submitButton.prop('disabled', true).text('Saving...');
                }
                
                // Get all form data including files
                const formData = new FormData(this);
                formData.append('action', 'kit_deliveries_crud');
                
                // Ensure task is set to create_delivery
                if (!formData.has('task')) {
                    formData.append('task', 'create_delivery');
                } else {
                    formData.set('task', 'create_delivery');
                }
                
                // Ensure delivery_id is 0 for new delivery
                if (!formData.has('delivery_id')) {
                    formData.append('delivery_id', '0');
                } else {
                    formData.set('delivery_id', '0');
                }
                
                console.log('Submitting delivery form via AJAX...');
                
                jQuery.ajax({
                    url: ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(data) {
                        console.log('AJAX response:', data);
                        if (data.success) {
                            const newDeliveryId = data.data;
                            console.log('Delivery created successfully with ID:', newDeliveryId);
                            
                            // Display delivery ID in #displayDeliveryID
                            const deliveryIdDisplay = document.getElementById('delivery_id_display');
                            const displayDeliveryID = document.getElementById('displayDeliveryID');
                            if (deliveryIdDisplay) {
                                deliveryIdDisplay.textContent = newDeliveryId;
                                console.log('✅ Displayed delivery ID:', newDeliveryId);
                            }
                            if (displayDeliveryID) {
                                displayDeliveryID.style.display = 'block';
                            }
                            
                            // Store delivery ID in sessionStorage for auto-selection when navigating to step 4
                            if (typeof Storage !== 'undefined') {
                                sessionStorage.setItem('recentlyCreatedDeliveryId', newDeliveryId);
                                console.log('✅ Stored delivery ID for auto-selection:', newDeliveryId);
                            }
                            
                            // Close modal
                            const modal = jQuery('#create-delivery-modal');
                            modal.removeClass('flex').addClass('hidden');
                            jQuery('body').removeClass('overflow-hidden');
                            
                            // Reload the .ajaxReload container
                            reloadWaybillForm(newDeliveryId);
                        } else {
                            console.error('Error creating delivery:', data.data);
                            alert('Error creating delivery: ' + (data.data || 'Unknown error'));
                            
                            // Restore button
                            if (submitButton.length) {
                                submitButton.prop('disabled', false).text(originalButtonText);
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX error:', error, xhr.responseText);
                        alert('Error creating delivery. Please try again.');
                        
                        // Restore button
                        if (submitButton.length) {
                            submitButton.prop('disabled', false).text(originalButtonText);
                        }
                    }
                });
                
                return false;
            });
            
            // Function to reload waybill form and auto-select new delivery
            function reloadWaybillForm(newDeliveryId) {
                console.log('Reloading waybill form, will auto-select delivery:', newDeliveryId);
                
                // Store delivery ID in sessionStorage for auto-selection after reload
                if (typeof Storage !== 'undefined') {
                    sessionStorage.setItem('autoSelectDeliveryId', newDeliveryId);
                }
                
                // Get current URL parameters
                const urlParams = new URLSearchParams(window.location.search);
                const custId = urlParams.get('cust_id') || '';
                const waybillId = urlParams.get('waybill_id') || '';
                const step = urlParams.get('step') || '4'; // Default to step 4
                
                // Build reload URL with auto-select parameter - use correct page slug
                let reloadUrl = '<?php echo admin_url('admin.php'); ?>?page=08600-waybill-create';
                if (custId) reloadUrl += '&cust_id=' + custId;
                if (waybillId) reloadUrl += '&waybill_id=' + waybillId;
                reloadUrl += '&step=4'; // Always go to step 4 after creating delivery
                reloadUrl += '&auto_select_delivery=' + newDeliveryId; // Pass delivery ID in URL
                
                console.log('Redirecting to:', reloadUrl);
                
                // Reload the page with the delivery ID parameter
                window.location.href = reloadUrl;
            }
            
            // Function to automatically select a delivery by ID
            function autoSelectDelivery(deliveryId) {
                console.log('🎯 Attempting to auto-select delivery:', deliveryId);
                
                if (!deliveryId) {
                    console.warn('No delivery ID provided for auto-selection');
                    return;
                }
                
                // Try multiple times with increasing delays in case the DOM hasn't fully loaded
                let attempts = 0;
                const maxAttempts = 20;
                
                const trySelect = () => {
                    attempts++;
                    console.log('🔍 Auto-select attempt', attempts, 'of', maxAttempts);
                    
                    // Try to find the delivery card by delivery ID or direction ID
                    const deliveryCards = document.querySelectorAll('.delivery-card');
                    console.log('📦 Found', deliveryCards.length, 'delivery cards');
                    
                    // Log all card IDs for debugging
                    if (attempts === 1) {
                        console.log('📋 Available delivery cards:');
                        deliveryCards.forEach((card, index) => {
                            console.log(`  Card ${index + 1}:`, {
                                deliveryId: card.getAttribute('data-delivery-id'),
                                directionId: card.getAttribute('data-direction-id'),
                                index: card.getAttribute('data-index')
                            });
                        });
                    }
                    
                    for (let card of deliveryCards) {
                        const cardDeliveryId = card.getAttribute('data-delivery-id');
                        const cardDirectionId = card.getAttribute('data-direction-id');
                        const cardIndex = card.getAttribute('data-index');
                        
                        // Match by delivery ID first (most specific), then direction ID, then index
                        const matches = cardDeliveryId === String(deliveryId) || 
                                       cardDirectionId === String(deliveryId) || 
                                       cardIndex === String(deliveryId);
                        
                        if (matches) {
                            // Get direction ID from the card - MUST have a valid direction_id
                            let directionId = cardDirectionId;
                            
                            // If direction_id is missing, we have a problem - log it
                            if (!directionId || directionId === '' || directionId === '0') {
                                console.error('❌ CRITICAL: Delivery card has no direction_id!', {
                                    cardDeliveryId: cardDeliveryId,
                                    cardDirectionId: cardDirectionId,
                                    cardIndex: cardIndex,
                                    card: card
                                });
                                // Try to get it from the onclick attribute as fallback
                                const onclickAttr = card.getAttribute('onclick');
                                if (onclickAttr) {
                                    const match = onclickAttr.match(/handleDeliveryClick\(this,\s*(\d+)\)/);
                                    if (match && match[1]) {
                                        directionId = match[1];
                                        console.log('✅ Found direction_id from onclick attribute:', directionId);
                                    }
                                }
                                
                                // If still no direction_id, we can't proceed
                                if (!directionId || directionId === '' || directionId === '0') {
                                    console.error('❌ Cannot select delivery: missing direction_id. Delivery may not be properly configured.');
                                    return false;
                                }
                            }
                            
                            console.log('✅ Found matching delivery card!', {
                                searchDeliveryId: deliveryId,
                                cardDeliveryId: cardDeliveryId,
                                cardDirectionId: cardDirectionId,
                                cardIndex: cardIndex,
                                selectedDirectionId: directionId
                            });
                            
                            // Ensure the card is visible (scroll into view if needed)
                            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            
                            // Small delay to ensure card is in view
                            setTimeout(() => {
                                console.log('🎯 Attempting to select card with direction_id:', directionId);
                                
                                // Call the handler function directly (no click event to avoid bubbling)
                                console.log('👉 Calling selection function directly (no click event)');
                                if (typeof selectDeliveryCard === 'function') {
                                    console.log('👉 Calling selectDeliveryCard directly');
                                    selectDeliveryCard(card, directionId, true); // true = skipDetailsDisplay
                                } else if (typeof window.selectDeliveryCard === 'function') {
                                    console.log('👉 Calling window.selectDeliveryCard directly');
                                    window.selectDeliveryCard(card, directionId, true); // true = skipDetailsDisplay
                                } else if (typeof handleDeliveryClick === 'function') {
                                    console.log('👉 Calling handleDeliveryClick directly');
                                    handleDeliveryClick(card, directionId);
                                } else if (typeof window.handleDeliveryClick === 'function') {
                                    console.log('👉 Calling window.handleDeliveryClick directly');
                                    window.handleDeliveryClick(card, directionId);
                                }
                                
                                // Set the hidden fields to ensure they're set even if handlers don't work
                                const directionIdField = document.getElementById('direction_id');
                                const selectedDeliveryIdField = document.getElementById('selected_delivery_id');
                                
                                if (directionIdField) {
                                    directionIdField.value = directionId;
                                    console.log('✅ Set direction_id field to:', directionId);
                                }
                                
                                if (selectedDeliveryIdField) {
                                    selectedDeliveryIdField.value = cardDeliveryId || cardIndex;
                                    console.log('✅ Set selected_delivery_id field to:', selectedDeliveryIdField.value);
                                }
                                
                                // Ensure the card has the selected class after function call
                                // Use a small delay to let the function complete
                                setTimeout(() => {
                                    // Force the selected class if it's not there
                                    if (!card.classList.contains('selected')) {
                                        console.log('🔧 Card missing selected class, adding it now');
                                        document.querySelectorAll('.delivery-card').forEach(c => c.classList.remove('selected'));
                                        card.classList.add('selected');
                                    }
                                    
                                    // Get city ID from card and ensure city is set
                                    const destinationCityId = card.getAttribute('data-destination-city-id');
                                    if (destinationCityId) {
                                        const citySelect = document.getElementById('destination_city');
                                        if (citySelect) {
                                            // Wait a bit for cities to load, then set the city
                                            setTimeout(() => {
                                                if (citySelect.value !== destinationCityId) {
                                                    citySelect.value = destinationCityId;
                                                    console.log('✅ Set destination_city to:', destinationCityId);
                                                    
                                                    // Trigger change event to update validation
                                                    const cityChangeEvent = new Event('change', { bubbles: true });
                                                    citySelect.dispatchEvent(cityChangeEvent);
                                                    
                                                    // Also trigger validation function if it exists
                                                    if (typeof validateDestinationSelection === 'function') {
                                                        validateDestinationSelection();
                                                    }
                                                    
                                                    // Trigger jQuery change event as well
                                                    if (typeof jQuery !== 'undefined') {
                                                        jQuery(citySelect).trigger('change');
                                                    }
                                                }
                                            }, 500);
                                        }
                                    }
                                    
                                    // Verify selection was successful after a longer delay
                                    setTimeout(() => {
                                        const directionIdFieldCheck = document.getElementById('direction_id');
                                        const selectedDeliveryIdCheck = document.getElementById('selected_delivery_id');
                                        const selectedCardCheck = document.querySelector('.delivery-card.selected');
                                        const citySelectCheck = document.getElementById('destination_city');
                                        
                                        // Final check and force if needed
                                        if (!card.classList.contains('selected')) {
                                            console.log('🔧 Final check: Card still missing selected class, forcing it');
                                            document.querySelectorAll('.delivery-card').forEach(c => c.classList.remove('selected'));
                                            card.classList.add('selected');
                                        }
                                        
                                        // Re-validate city if needed
                                        if (citySelectCheck && citySelectCheck.value && typeof validateDestinationSelection === 'function') {
                                            validateDestinationSelection();
                                        }
                                        
                                        if (directionIdFieldCheck && directionIdFieldCheck.value && card.classList.contains('selected')) {
                                            console.log('✅✅ Successfully auto-selected delivery!', {
                                                deliveryId: deliveryId,
                                                direction_id: directionIdFieldCheck.value,
                                                selected_delivery_id: selectedDeliveryIdCheck?.value,
                                                city_value: citySelectCheck?.value,
                                                cardHasSelectedClass: card.classList.contains('selected'),
                                                cardElement: card
                                            });
                                            
                                            // Trigger rate fetch if mass is present
                                            const totalMassInput = document.getElementById('total_mass_kg');
                                            if (totalMassInput && totalMassInput.value && parseFloat(totalMassInput.value) > 0) {
                                                if (typeof fetchRatePerKg === 'function') {
                                                    console.log('💰 Triggering rate fetch...');
                                                    fetchRatePerKg();
                                                }
                                            }
                                            
                                            // Trigger button condition check
                                            if (typeof checkButtonConditions === 'function') {
                                                checkButtonConditions();
                                            }
                                        } else {
                                            console.warn('⚠️ Selection verification failed:', {
                                                direction_id: directionIdFieldCheck?.value,
                                                selected_card: selectedCardCheck,
                                                card_has_class: card.classList.contains('selected'),
                                                selected_delivery_id: selectedDeliveryIdCheck?.value,
                                                city_value: citySelectCheck?.value,
                                                expected_direction_id: directionId
                                            });
                                            
                                            // Final retry: Force everything manually
                                            console.log('🔄 Final retry: Forcing all values manually');
                                            if (directionIdFieldCheck) {
                                                directionIdFieldCheck.value = directionId;
                                            }
                                            if (selectedDeliveryIdCheck) {
                                                selectedDeliveryIdCheck.value = cardDeliveryId || cardIndex;
                                            }
                                            document.querySelectorAll('.delivery-card').forEach(c => c.classList.remove('selected'));
                                            card.classList.add('selected');
                                            
                                            // Set city if we have it
                                            if (destinationCityId && citySelectCheck) {
                                                citySelectCheck.value = destinationCityId;
                                                const cityChangeEvent = new Event('change', { bubbles: true });
                                                citySelectCheck.dispatchEvent(cityChangeEvent);
                                                if (typeof validateDestinationSelection === 'function') {
                                                    validateDestinationSelection();
                                                }
                                            }
                                            
                                            // Verify one more time
                                            setTimeout(() => {
                                                if (card.classList.contains('selected') && directionIdFieldCheck && directionIdFieldCheck.value === directionId) {
                                                    console.log('✅✅ Final retry successful!');
                                                    if (typeof checkButtonConditions === 'function') {
                                                        checkButtonConditions();
                                                    }
                                                    if (typeof validateDestinationSelection === 'function') {
                                                        validateDestinationSelection();
                                                    }
                                                } else {
                                                    console.error('❌ Final retry also failed. Card:', card, 'Direction ID:', directionId);
                                                }
                                            }, 200);
                                        }
                                    }, 1500);
                                }, 300);
                            }, 200);
                            
                            return true;
                        }
                    }
                    
                    // If not found and we haven't exceeded max attempts, try again
                    if (attempts < maxAttempts) {
                        const delay = attempts < 5 ? 500 : 800; // Longer delays after first few attempts
                        setTimeout(trySelect, delay);
                    } else {
                        console.error('❌ Could not find delivery card with ID after', maxAttempts, 'attempts:', deliveryId);
                        console.log('💡 Make sure the delivery exists and is visible in the current filter');
                    }
                    
                    return false;
                };
                
                // Wait a bit longer before starting to ensure scheduled deliveries are loaded
                setTimeout(() => {
                    trySelect();
                }, 1500);
            }
            
            // Check for auto-select parameter on page load
            const urlParams = new URLSearchParams(window.location.search);
            const autoSelectDeliveryId = urlParams.get('auto_select_delivery');
            const sessionDeliveryId = (typeof Storage !== 'undefined') ? sessionStorage.getItem('autoSelectDeliveryId') : null;
            const deliveryIdToSelect = autoSelectDeliveryId || sessionDeliveryId;
            
            if (deliveryIdToSelect) {
                console.log('🚀 Auto-select delivery ID found in URL/session:', deliveryIdToSelect);
                // Clear sessionStorage
                if (typeof Storage !== 'undefined') {
                    sessionStorage.removeItem('autoSelectDeliveryId');
                }
                // Remove from URL without reload
                if (autoSelectDeliveryId) {
                    urlParams.delete('auto_select_delivery');
                    const newUrl = window.location.pathname + '?' + urlParams.toString();
                    window.history.replaceState({}, '', newUrl);
                }
                
                // Use MutationObserver to wait for delivery cards to be added to DOM
                const deliveriesContainer = document.getElementById('scheduled-deliveries-container');
                const deliveriesList = document.getElementById('scheduled-deliveries-list');
                
                const attemptAutoSelect = () => {
                    // First, check if deliveries list is loaded and has cards
                    const deliveriesList = document.getElementById('scheduled-deliveries-list');
                    if (!deliveriesList) {
                        console.log('⏳ Deliveries list not found yet, waiting...');
                        return false;
                    }
                    
                    // Check if list has finished loading (has delivery cards or empty state)
                    const deliveryCards = deliveriesList.querySelectorAll('.delivery-card');
                    const emptyState = deliveriesList.querySelector('.text-center, .empty-state');
                    
                    // If list is empty and no empty state, it's still loading
                    if (deliveryCards.length === 0 && !emptyState) {
                        console.log('⏳ Deliveries list still loading, waiting...');
                        return false;
                    }
                    
                    // First, check if PHP marked a card for auto-selection
                    let cardToSelect = document.querySelector('.delivery-card[data-auto-select="true"]');
                    let directionId = null;
                    
                    if (cardToSelect) {
                        directionId = cardToSelect.getAttribute('data-auto-select-direction-id');
                        console.log('✅ Found PHP-marked card, direction_id:', directionId);
                    } else {
                        // Fallback: Find card by delivery ID
                        console.log('🔍 Checking', deliveryCards.length, 'delivery cards for ID:', deliveryIdToSelect);
                        
                        for (let card of deliveryCards) {
                            const cardDeliveryId = card.getAttribute('data-delivery-id');
                            const cardDirectionId = card.getAttribute('data-direction-id');
                            const cardIndex = card.getAttribute('data-index');
                            
                            if (cardDeliveryId === String(deliveryIdToSelect) || 
                                cardDirectionId === String(deliveryIdToSelect) || 
                                cardIndex === String(deliveryIdToSelect)) {
                                cardToSelect = card;
                                directionId = cardDirectionId || cardIndex || cardDeliveryId;
                                console.log('✅ Found matching card by ID, direction_id:', directionId);
                                break;
                            }
                        }
                    }
                    
                    if (cardToSelect && directionId) {
                        console.log('✅ Found card to select:', {
                            card: cardToSelect,
                            directionId: directionId,
                            deliveryId: cardToSelect.getAttribute('data-delivery-id')
                        });
                        
                        // Scroll card into view
                        cardToSelect.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        
                        // Wait for functions to be available, then select
                        const doSelection = () => {
                            console.log('🎯 Auto-selecting delivery card with direction_id:', directionId);
                            
                            // Simple approach: Just call selectDeliveryCard directly
                            // It handles everything: selection, form fields, country, city, etc.
                            if (typeof selectDeliveryCard === 'function') {
                                selectDeliveryCard(cardToSelect, directionId, true); // true = skipDetailsDisplay
                            } else if (typeof window.selectDeliveryCard === 'function') {
                                window.selectDeliveryCard(cardToSelect, directionId, true);
                            } else {
                                console.warn('⚠️ selectDeliveryCard function not found, doing manual selection');
                                // Fallback: manual selection
                                document.querySelectorAll('.delivery-card').forEach(c => c.classList.remove('selected'));
                                cardToSelect.classList.add('selected');
                                
                                const directionIdField = document.getElementById('direction_id');
                                if (directionIdField) {
                                    directionIdField.value = directionId;
                                }
                            }
                        };
                        
                        // Wait for functions to load, then select
                        setTimeout(doSelection, 500);
                        
                        return true;
                    }
                    
                    return false;
                };
                
                // Wait for deliveries list to be loaded before attempting auto-selection
                const waitForDeliveriesList = () => {
                    const deliveriesList = document.getElementById('scheduled-deliveries-list');
                    if (!deliveriesList) {
                        console.log('⏳ Waiting for scheduled-deliveries-list to appear...');
                        return false;
                    }
                    
                    // Check if list has finished loading
                    const deliveryCards = deliveriesList.querySelectorAll('.delivery-card');
                    const emptyState = deliveriesList.querySelector('.text-center, .empty-state, .bg-white.p-6');
                    const isLoading = deliveriesList.querySelector('.animate-spin, .loading');
                    
                    // If there's a loading indicator, list is still loading
                    if (isLoading) {
                        console.log('⏳ Deliveries list still loading (spinner detected)...');
                        return false;
                    }
                    
                    // If no cards and no empty state, might still be loading
                    if (deliveryCards.length === 0 && !emptyState) {
                        console.log('⏳ Deliveries list appears empty, waiting for content...');
                        return false;
                    }
                    
                    // List has finished loading (either has cards or shows empty state)
                    console.log('✅ Deliveries list loaded:', {
                        cards: deliveryCards.length,
                        hasEmptyState: !!emptyState
                    });
                    return true;
                };
                
                // Try immediately if DOM is ready
                if (document.readyState === 'complete' || document.readyState === 'interactive') {
                    // Wait for deliveries list to load first
                    const checkAndSelect = () => {
                        if (waitForDeliveriesList()) {
                            // List is loaded, try to select
                            if (!attemptAutoSelect()) {
                                console.log('⏳ Card not found yet, setting up MutationObserver');
                                
                                // Use MutationObserver to watch for card additions
                                const deliveriesList = document.getElementById('scheduled-deliveries-list');
                                if (deliveriesList) {
                                    const observer = new MutationObserver((mutations) => {
                                        mutations.forEach((mutation) => {
                                            if (mutation.addedNodes.length > 0) {
                                                console.log('📦 New nodes added to deliveries list');
                                                if (attemptAutoSelect()) {
                                                    observer.disconnect();
                                                }
                                            }
                                        });
                                    });
                                    
                                    observer.observe(deliveriesList, {
                                        childList: true,
                                        subtree: true
                                    });
                                    
                                    // Also try periodically as fallback
                                    let attempts = 0;
                                    const maxAttempts = 20;
                                    const interval = setInterval(() => {
                                        attempts++;
                                        if (attemptAutoSelect() || attempts >= maxAttempts) {
                                            clearInterval(interval);
                                            observer.disconnect();
                                        }
                                    }, 500);
                                }
                            }
                        } else {
                            // List not ready yet, check again
                            setTimeout(checkAndSelect, 300);
                        }
                    };
                    
                    // Start checking after a short delay
                    setTimeout(checkAndSelect, 500);
                } else {
                    // Wait for DOM to be ready
                    document.addEventListener('DOMContentLoaded', () => {
                        const checkAndSelect = () => {
                            if (waitForDeliveriesList()) {
                                if (!attemptAutoSelect()) {
                                    // Set up observer if card not found
                                    const deliveriesList = document.getElementById('scheduled-deliveries-list');
                                    if (deliveriesList) {
                                        const observer = new MutationObserver((mutations) => {
                                            mutations.forEach((mutation) => {
                                                if (mutation.addedNodes.length > 0) {
                                                    if (attemptAutoSelect()) {
                                                        observer.disconnect();
                                                    }
                                                }
                                            });
                                        });
                                        
                                        observer.observe(deliveriesList, {
                                            childList: true,
                                            subtree: true
                                        });
                                    }
                                }
                            } else {
                                setTimeout(checkAndSelect, 300);
                            }
                        };
                        setTimeout(checkAndSelect, 500);
                    });
                }
            }
        }); // End of DOMContentLoaded
    </script>
</div>