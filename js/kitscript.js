//file location: js/kitscript.js
function toggleDropdownDeliveryStatus(delivery_id) {
    const dropdown = document.getElementById('delivery-status-dropdown-' + delivery_id);
    dropdown.classList.toggle('hidden');
}

function hello() {
    alert("Hello");
}

function fetchRatePerKg() {
    // ✅ BULLETPROOF: Comprehensive input validation and sanitization
    try {
        const rawMass = jQuery('#total_mass_kg').val() || '';
        const total_mass_kg = parseFloat(rawMass) || 0;
        const direction_id = jQuery('#direction_id').val() || '';
        const origin_country_id = jQuery('#countrydestination_id').val() || '';
        let current_rate = jQuery('#current_rate').val() || '';

        // ✅ BULLETPROOF: Comprehensive validation
        if (rawMass !== '' && (isNaN(total_mass_kg) || total_mass_kg <= 0)) {
            console.warn('Invalid mass value for rate fetch:', rawMass);
            showRateFetchError('Invalid mass value. Please enter a positive number.');
            return;
        }
        
        if (total_mass_kg > 10000) {
            console.warn('Mass exceeds maximum limit:', total_mass_kg);
            showRateFetchError('Mass exceeds maximum limit of 10,000 kg.');
            return;
        }
        
        if (!direction_id) {
            console.warn('Missing direction_id for rate fetch');
            showRateFetchError('Missing direction information. Please refresh the page.');
            return;
        }
        
        if (!origin_country_id) {
            console.warn('Missing origin_country_id for rate fetch');
            showRateFetchError('Missing origin country information. Please refresh the page.');
            return;
        }

        // ✅ BULLETPROOF: Show loading indicator
        const massInput = document.getElementById('total_mass_kg');
        if (massInput) {
            massInput.classList.add('loading');
        }

        if (total_mass_kg > 0 && direction_id && origin_country_id) {
            jQuery.ajax({
                url: myPluginAjax.ajax_url,
                type: 'POST',
                dataType: 'json',
                timeout: 10000, // ✅ BULLETPROOF: 10 second timeout
                data: {
                    action: 'handle_get_price_per_kg',
                    total_mass_kg: total_mass_kg,
                    direction_id: direction_id,
                    origin_country_id: origin_country_id, // ✅ BULLETPROOF: Include origin_country_id
                    nonce: myPluginAjax.nonces.get_waybills_nonce
                },
                success: function (response) {
                    // ✅ BULLETPROOF: Hide loading indicator
                    if (massInput) {
                        massInput.classList.remove('loading');
                    }
                    
                    if (response && response.success && response.data) {
                        const rate = parseFloat(response.data.rate_per_kg);
                        const total_charge = parseFloat(response.data.total_charge);
                        
                        // ✅ BULLETPROOF: Validate response data
                        if (!Number.isFinite(rate) || rate <= 0) {
                            console.error('Invalid rate received:', response.data.rate_per_kg);
                            showRateFetchError('Invalid rate received from server.');
                            return;
                        }
                        
                        if (!Number.isFinite(total_charge) || total_charge <= 0) {
                            console.error('Invalid total charge received:', response.data.total_charge);
                            showRateFetchError('Invalid charge calculation received from server.');
                            return;
                        }

                        console.log('Rate per kg: R' + rate);
                        current_rate = rate;

                        // ✅ BULLETPROOF: Update all relevant fields
                        jQuery('#mass_charge_display').text(rate);
                        jQuery('#mass_charge').val(total_charge.toFixed(2));
                        jQuery('#mass_rate').val(rate.toFixed(2));
                        jQuery('#base_rate').val(rate.toFixed(2));
                        jQuery('#current_rate').val(rate.toFixed(2));
                        jQuery('#mass_charge').attr('data-base-charge', total_charge.toFixed(2));
                        
                        // ✅ BULLETPROOF: Clear any previous errors
                        jQuery('#rate-fetch-error').remove();
                        
                        // ✅ BULLETPROOF: Trigger recalculation if manipulator is active
                        if (typeof calculateMassCharge === 'function') {
                            calculateMassCharge();
                        }
                        
                    } else {
                        console.error('Rate fetch failed:', response);
                        const errorMsg = (response && response.data && response.data.message) 
                            ? response.data.message 
                            : 'Unable to fetch rate from server.';
                        showRateFetchError(errorMsg);
                    }
                },
                error: function (xhr, status, error) {
                    // ✅ BULLETPROOF: Hide loading indicator
                    if (massInput) {
                        massInput.classList.remove('loading');
                    }
                    
                    console.error('AJAX Error:', {xhr, status, error});
                    
                    let errorMessage = 'Network error. Please check your connection and try again.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Network connection lost. Please check your internet connection.';
                    } else if (xhr.status >= 500) {
                        errorMessage = 'Server error. Please try again later.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Access denied. Please refresh the page and try again.';
                    }
                    
                    showRateFetchError(errorMessage);
                }
            });
        }
        
    } catch (error) {
        // ✅ BULLETPROOF: Hide loading indicator
        const massInput = document.getElementById('total_mass_kg');
        if (massInput) {
            massInput.classList.remove('loading');
        }
        
        console.error('Rate fetch function error:', error);
        showRateFetchError('An unexpected error occurred. Please try again.');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const massInput = document.getElementById('total_mass_kg');
    if (massInput) {
        massInput.addEventListener('click', fetchRatePerKg);
    }

    let timeout;
    jQuery('#total_mass_kg').on('input', function () {
        clearTimeout(timeout);
        timeout = setTimeout(fetchRatePerKg, 500); // Wait 500ms after user stops typing
    });
    
    // ✅ BULLETPROOF: Auto-trigger rate fetch on page load if mass is present but rate is missing
    jQuery(document).ready(function() {
        setTimeout(function() {
            const massValue = parseFloat(jQuery('#total_mass_kg').val()) || 0;
            const rateValue = parseFloat(jQuery('#mass_rate').val()) || 0;
            const directionId = jQuery('#direction_id').val();
            const originCountryId = jQuery('#countrydestination_id').val();
            
            console.log('kitscript.js auto-trigger check:', {
                mass: massValue,
                rate: rateValue,
                directionId: directionId,
                originCountryId: originCountryId
            });
            
            if (massValue > 0 && rateValue <= 0 && directionId) {
                console.log('kitscript.js: Auto-triggering rate fetch on page load...');
                fetchRatePerKg();
            } else if (massValue > 0 && rateValue <= 0 && !directionId) {
                console.warn('kitscript.js: Cannot auto-trigger - direction_id missing');
                // Try to find direction_id from form
                const formDirectionId = jQuery('input[name="direction_id"]').val();
                if (formDirectionId) {
                    console.log('kitscript.js: Found direction_id in form, updating...');
                    jQuery('#direction_id').val(formDirectionId);
                    fetchRatePerKg();
                }
            }
        }, 1000); // 1 second delay to ensure all elements are loaded
    });
    // Dispatch date validation
    const dispatchDateInput = document.getElementById('dispatch_date');
    const today = new Date().toISOString().split('T')[0];

    if (dispatchDateInput) {
        // Set min date attribute
        dispatchDateInput.min = today;

        // Additional validation on form submission
        document.querySelector('form').addEventListener('submit', function (e) {
            const selectedDate = dispatchDateInput.value;
            if (selectedDate < today) {
                e.preventDefault();
                dispatchDateInput.focus();
            }
        });
    }

    // VAT checkbox functionality
    const vatCheckbox2 = document.getElementById('vat_include2');
    const vatCheckbox = document.getElementById('vat_include');
    const SADC = document.getElementById('sadc_certificate');
    const optionz = document.querySelectorAll('.optionz');
    const nextStep3 = document.getElementById('next-step-3');
    const addWaybillItemBtn = document.getElementById('add-waybill-item-btn');

    function toggleVatDisabled() {
        if (SADC && SADC.checked) {
            vatCheckbox2.checked = false;
            vatCheckbox2.disabled = true;
        } else if (SADC) {
            vatCheckbox2.checked = true;
            vatCheckbox2.disabled = false;
        }
    }
    function toggleOptionzDisabled() {
        if (vatCheckbox && vatCheckbox.checked || vatCheckbox2 && vatCheckbox2.checked) {
            //bg-gray-300 text-gray-500 rounded-md hover:bg-gray-400
            if (nextStep3) {
                nextStep3.disabled = true;
                nextStep3.classList.add('bg-gray-300', 'text-gray-500');
                nextStep3.classList.remove('bg-blue-600', 'text-white');
            }

            SADC.disabled = true;
            SADC.checked = 0;
        } else {
            
            if (nextStep3) {
            nextStep3.disabled = false;
            nextStep3.classList.remove('bg-gray-300', 'text-gray-500');
            nextStep3.classList.add('bg-blue-600', 'text-white');
            }
            SADC.disabled = false;
        }
    }

    if (vatCheckbox2) {
        vatCheckbox2.addEventListener('change', toggleOptionzDisabled);
        //vatCheckbox2.addEventListener('change', deleteAllItems);
        // Run on page load in case VAT is pre-checked
    }

    if (vatCheckbox) {
        vatCheckbox.addEventListener('change', toggleOptionzDisabled);
        // Run on page load in case VAT is pre-checked
        toggleOptionzDisabled();
    }

    

    if (SADC) {
        SADC.addEventListener('change', toggleVatDisabled);
        // Run on page load in case VAT is pre-checked
        toggleOptionzDisabled();
    }


    // Delete waybill
    jQuery(document).on('click', '.delete-waybill', function (e) {
        e.preventDefault();

        const $form = jQuery(this).closest('form');
        const waybillId = $form.find('input[name="waybill_id"]').val();
        const waybillNo = $form.find('input[name="waybill_no"]').val();
        const deliveryId = $form.find('input[name="delivery_id"]').val();
        const userId = $form.find('input[name="user_id"]').val();
        const nonce = $form.find('input[name="_wpnonce"]').val();
        const $row = jQuery(this).closest('tr');

        if (!confirm('Are you sure you want to delete this waybill?')) {
            return;
        }

        jQuery.ajax({
            url: myPluginAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_waybill',
                waybill_id: waybillId,
                waybill_no: waybillNo,
                delivery_id: deliveryId,
                user_id: userId,
                _ajax_nonce: nonce
            },
            beforeSend: function () {
                $row.css('opacity', '0.5');
            },
            success: function (response) {
                if (response.success) {
                    /* After deleting waybill reload the table #waybill-table-container */
                    jQuery('#waybill-table-parent').load(window.location.href + ' #waybill-table-container');
                } else {
                    alert('Error: ' + (response.data?.message || 'Failed to delete waybill'));
                    $row.css('opacity', '1');
                }
            },
            error: function (xhr, status, error) {
                alert('Error: ' + error);
                $row.css('opacity', '1');
            }
        });
    });

    // Select all rows
    jQuery(document).on('change', '.selectRow', function () {
        // Check if all rows are checked
        const allChecked = jQuery('.selectRow:checked').length === jQuery('.selectRow').length;
        jQuery('.selectAllRows').prop('checked', allChecked);
    });
    jQuery(document).on('click', '#add-waybill-item', function () {
        const nextStep3 = document.getElementById('next-step-3');
        if (nextStep3) {
            nextStep3.disabled = false;
            //hidden md:block next-step px-4 py-2 rounded-md hover:bg-blue-700 bg-gray-300 text-gray-500
            nextStep3.classList.remove('bg-gray-300', 'text-gray-500');
            nextStep3.classList.add('bg-blue-600', 'text-white');
        }
    });

    // Select all rows
    jQuery(document).on('change', '.selectAllRows', function () {
        // Check if all rows are checked
        const allChecked = jQuery(this).prop('checked');
        jQuery('.selectRow').prop('checked', allChecked);
    });

    // Delete delivery
    jQuery(document).on('click', '.delete-delivery', function (e) {
        e.preventDefault();
        const deliveryId = jQuery(this).data('delivery-id');
        if (!confirm('Are you sure you want to delete this delivery?')) {
            return;
        }
    });

    // Helpers for Step 4 next button styling
    function setStep4NextState(enabled) {
        const btn = document.getElementById('step4NextBtn');
        if (!btn) return;
        const active = [
            'inline-flex','items-center','px-6','py-3','bg-gradient-to-r','from-blue-600','to-indigo-600',
            'hover:from-blue-700','hover:to-indigo-700','text-white','font-semibold','rounded-xl','shadow-lg',
            'hover:shadow-xl','transform','hover:-translate-y-0.5','transition-all','duration-200'
        ];
        const disabled = ['opacity-50','cursor-not-allowed','pointer-events-none','bg-blue-400','bg-gray-300','text-gray-500'];
        if (enabled) {
            btn.classList.add(...active);
            btn.classList.remove(...disabled);
            btn.disabled = false;
            btn.setAttribute('aria-disabled','false');
        } else {
            btn.classList.remove(...active);
            btn.classList.add(...disabled);
            btn.disabled = true;
            btn.setAttribute('aria-disabled','true');
        }
    }

    // Warehoused checkbox
    jQuery(document).on('change', '#warehoused_option', function () {
        const scheduledDeliveriesList = document.getElementById('scheduled-deliveries-list');
        const nextBtn = document.getElementById('step4NextBtn');
        const destinationCountry = document.getElementById('stepDestinationSelect');
        const destinationCity = document.getElementById('destination_city');
        const destinationCountryHelp = document.getElementById('destination-country-help');
        const destinationCityHelp = document.getElementById('destination-city-help');
        
        if (jQuery(this).is(':checked')) {
            // Hide scheduled deliveries when warehoused is checked
            scheduledDeliveriesList.classList.add('hidden');
            
            // Enable the next button for warehoused items
            setStep4NextState(true);
            
            // Clear destination fields for warehoused items
            if (destinationCountry) destinationCountry.value = '';
            if (destinationCity) destinationCity.value = '';
            
            // Hide helper text for destination fields
            if (destinationCountryHelp) destinationCountryHelp.style.display = 'none';
            if (destinationCityHelp) destinationCityHelp.style.display = 'none';
            
        } else {
            // Show scheduled deliveries when warehoused is unchecked
            scheduledDeliveriesList.classList.remove('hidden');
            
            // Show helper text for destination fields
            if (destinationCountryHelp) destinationCountryHelp.style.display = 'block';
            if (destinationCityHelp) destinationCityHelp.style.display = 'block';
            
            // Validate destination selection
            validateDestinationSelection();
        }
    });
    
    // Destination country change handler
    jQuery(document).on('change', '#stepDestinationSelect', function () {
        validateDestinationSelection();
    });
    
    // Destination city change handler
    jQuery(document).on('change', '#destination_city', function () {
        validateDestinationSelection();
    });
    
    // Function to validate destination selection
    function validateDestinationSelection() {
        const warehousedCheckbox = document.getElementById('warehoused_option');
        const nextBtn = document.getElementById('step4NextBtn');
        const destinationCountry = document.getElementById('stepDestinationSelect');
        const destinationCity = document.getElementById('destination_city');
        const destinationCountryHelp = document.getElementById('destination-country-help');
        const destinationCityHelp = document.getElementById('destination-city-help');
        
        // If warehoused is checked, no validation needed
        if (warehousedCheckbox && warehousedCheckbox.checked) {
            setStep4NextState(true);
            return;
        }
        
        // Check if both country and city are selected
        const countrySelected = destinationCountry && destinationCountry.value && destinationCountry.value !== '';
        const citySelected = destinationCity && destinationCity.value && destinationCity.value !== '';
        
        // Update helper text colors based on selection
        if (destinationCountryHelp) {
            if (countrySelected) {
                destinationCountryHelp.classList.remove('text-red-500');
                destinationCountryHelp.classList.add('text-green-500');
                destinationCountryHelp.textContent = '✓ Country selected';
            } else {
                destinationCountryHelp.classList.remove('text-green-500');
                destinationCountryHelp.classList.add('text-red-500');
                destinationCountryHelp.textContent = '✗ Country required';
            }
        }
        
        if (destinationCityHelp) {
            if (citySelected) {
                destinationCityHelp.classList.remove('text-red-500');
                destinationCityHelp.classList.add('text-green-500');
                destinationCityHelp.textContent = '✓ City selected';
            } else {
                destinationCityHelp.classList.remove('text-green-500');
                destinationCityHelp.classList.add('text-red-500');
                destinationCityHelp.textContent = '✗ City required';
            }
        }
        
        setStep4NextState(countrySelected && citySelected);
    }
    
    // Initialize validation on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Run initial validation
        validateDestinationSelection();
        
        // Set initial state for helper text visibility
        const warehousedCheckbox = document.getElementById('warehoused_option');
        const destinationCountryHelp = document.getElementById('destination-country-help');
        const destinationCityHelp = document.getElementById('destination-city-help');
        
        if (warehousedCheckbox && warehousedCheckbox.checked) {
            if (destinationCountryHelp) destinationCountryHelp.style.display = 'none';
            if (destinationCityHelp) destinationCityHelp.style.display = 'none';
        }
    });

    // Remove legacy manipulator handler that overwrote rate; handled in weight.php now
});

// Global spinner management system
const SpinnerManager = {
    // Store active spinners to prevent duplicates
    activeSpinners: new Set(),

    // CSS for the spinner (injected once)
    injectStyles: function () {
        if (!document.getElementById('spinner-styles')) {
            const style = document.createElement('style');
            style.id = 'spinner-styles';
            style.textContent = `
                .input-spinner {
                    position: relative;
                }
                .input-spinner::after {
                    content: '';
                    position: absolute;
                    right: 8px;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 16px;
                    height: 16px;
                    border: 2px solid rgba(0,0,0,0.1);
                    border-radius: 50%;
                    border-top-color: #3498db;
                    animation: spin 1s linear infinite;
                }
                @keyframes spin {
                    to { transform: translateY(-50%) rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
    },

    // Show spinner on specific input
    show: function (inputElement) {
        this.injectStyles();
        if (!this.activeSpinners.has(inputElement)) {
            inputElement.classList.add('input-spinner');
            this.activeSpinners.add(inputElement);
        }
    },

    // Hide spinner from specific input
    hide: function (inputElement) {
        if (this.activeSpinners.has(inputElement)) {
            inputElement.classList.remove('input-spinner');
            this.activeSpinners.delete(inputElement);
        }
    },

    // Hide all spinners
    hideAll: function () {
        this.activeSpinners.forEach(input => {
            input.classList.remove('input-spinner');
        });
        this.activeSpinners.clear();
    }
};

// Customer Dashboard Tab Functionality
function switchCustomerTab(tabName) {
    // Hide all customer tab contents only
    const customerTabContents = document.querySelectorAll('.customer-tabs .tab-content');
    customerTabContents.forEach(content => {
        content.style.display = 'none';
    });

    // Remove active class from all customer tab buttons only
    const customerTabButtons = document.querySelectorAll('.customer-tabs .tab-btn');
    customerTabButtons.forEach(btn => {
        btn.classList.remove('active');
        btn.style.background = 'transparent';
        btn.style.color = '#6b7280';
    });

    // Show selected tab content
    const selectedContent = document.getElementById(tabName + '-content');
    if (selectedContent) {
        selectedContent.style.display = 'block';
    }

    // Add active class to selected tab button
    const selectedButton = document.getElementById(tabName + '-tab');
    if (selectedButton) {
        selectedButton.classList.add('active');
        selectedButton.style.background = 'white';
        selectedButton.style.color = '#374151';
    }
}

// Initialize customer dashboard tabs
document.addEventListener('DOMContentLoaded', function() {
    // Customer dashboard tab functionality
    const customerTabButtons = document.querySelectorAll('.customer-tabs .tab-btn');
    customerTabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.id.replace('-tab', '');
            switchCustomerTab(tabName);
        });
    });

    // Handle inline customer form submission
    const inlineCustomerForm = document.getElementById('inlineCustomerForm');
    if (inlineCustomerForm) {
        inlineCustomerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('saveCustomerBtn');
            let messagesDiv = document.getElementById('customerFormMessages');
            if (!messagesDiv) {
                // Create a messages container if it doesn't exist
                messagesDiv = document.createElement('div');
                messagesDiv.id = 'customerFormMessages';
                messagesDiv.style.marginBottom = '12px';
                // Insert before the form
                inlineCustomerForm.parentNode.insertBefore(messagesDiv, inlineCustomerForm);
            }
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
            
            // Collect form data
            const formData = new FormData(this);
            formData.append('action', 'save_customer_ajax');
            formData.append('nonce', customerAjax.nonce);
            const safeVal = (id) => {
                const el = document.getElementById(id);
                return el ? el.value : '';
            };
            formData.append('name', safeVal('name'));
            formData.append('surname', safeVal('surname'));
            formData.append('cell', safeVal('cell'));
            formData.append('email_address', safeVal('email_address'));
            formData.append('address', safeVal('address'));
            formData.append('company_name', safeVal('company_name'));
            formData.append('country_id', safeVal('country_id'));
            formData.append('city_id', safeVal('city_id'));
            formData.append('vat_number', safeVal('vat_number'));
            
            // Debug: Log form data
            console.log('Submitting customer form with data:', Object.fromEntries(formData));
            
            // Submit via AJAX
            fetch(ajaxurl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    messagesDiv.innerHTML = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">' + data.data.message + '</div>';
                    
                    // Reset form
                    inlineCustomerForm.reset();
                    
                    // Redirect to the manage-customers tab after 1.5 seconds
                    setTimeout(() => {
                        messagesDiv.innerHTML = '';
                        // Redirect to the manage-customers tab with a refresh parameter
                        const currentUrl = new URL(window.location);
                        currentUrl.searchParams.set('tab', 'manage-customers');
                        currentUrl.searchParams.set('customer_added', '1');
                        window.location.href = currentUrl.toString();
                    }, 1500);
                } else {
                    // Show error message
                    messagesDiv.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Error: ' + (data.data || 'Unknown error occurred') + '</div>';
                }
            })
            .catch(error => {
                messagesDiv.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Network error: ' + error.message + '</div>';
            })
            .finally(() => {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Customer';
            });
        });
    }

    // Handle country change for city loading
    const countrySelect = document.getElementById('country_id');
    const citySelect = document.getElementById('city_id');
    
    if (countrySelect && citySelect) {
        countrySelect.addEventListener('change', function() {
            const countryId = this.value;
            
            // Clear city options
            citySelect.innerHTML = '<option value="">Select City</option>';
            
            if (countryId) {
                // Load cities for selected country
                fetch(ajaxurl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'get_cities_by_country',
                        country_id: countryId,
                        nonce: customerAjax.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        data.data.forEach(city => {
                            const option = document.createElement('option');
                            option.value = city.id;
                            option.textContent = city.city_name;
                            citySelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading cities:', error);
                });
            }
        });
    }
    
    // Set default active tab to manage-customers
    switchCustomerTab('manage-customers');
});

// ✅ ADD ERROR HANDLING FUNCTION
function showRateFetchError(message) {
    let errorDiv = jQuery('#rate-fetch-error');
    if (errorDiv.length === 0) {
        errorDiv = jQuery('<div id="rate-fetch-error" class="text-red-600 text-sm mt-2"></div>');
        jQuery('#mass_rate').parent().append(errorDiv);
    }
    errorDiv.text(message);
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        errorDiv.fadeOut(500, function() {
            jQuery(this).remove();
        });
    }, 5000);
}