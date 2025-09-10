//file location: js/kitscript.js
function toggleDropdownDeliveryStatus(delivery_id) {
    var dropdown = document.getElementById('delivery-status-dropdown-' + delivery_id);
    dropdown.classList.toggle('hidden');
}

function hello() {
    alert("Hello");
}

function fetchRatePerKg() {
    // ✅ BULLETPROOF: Comprehensive input validation and sanitization
    try {
        var rawMass = jQuery('#total_mass_kg').val() || '';
        var total_mass_kg = parseFloat(rawMass) || 0;
        var direction_id = jQuery('#direction_id').val() || '';
        var origin_country_id = jQuery('#countrydestination_id').val() || '';
        var current_rate = jQuery('#current_rate').val() || '';

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
        var massInput = document.getElementById('total_mass_kg');
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
                        var rate = parseFloat(response.data.rate_per_kg);
                        var total_charge = parseFloat(response.data.total_charge);
                        
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
                        var errorMsg = (response && response.data && response.data.message) 
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
                    
                    var errorMessage = 'Network error. Please check your connection and try again.';
                    
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
        var massInput = document.getElementById('total_mass_kg');
        if (massInput) {
            massInput.classList.remove('loading');
        }
        
        console.error('Rate fetch function error:', error);
        showRateFetchError('An unexpected error occurred. Please try again.');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    var massInput = document.getElementById('total_mass_kg');
    if (massInput) {
        massInput.addEventListener('click', fetchRatePerKg);
    }

    var timeout;
    jQuery('#total_mass_kg').on('input', function () {
        clearTimeout(timeout);
        timeout = setTimeout(fetchRatePerKg, 500); // Wait 500ms after user stops typing
    });
    
    // ✅ BULLETPROOF: Auto-trigger rate fetch on page load if mass is present but rate is missing
    jQuery(document).ready(function() {
        setTimeout(function() {
            var massValue = parseFloat(jQuery('#total_mass_kg').val()) || 0;
            var rateValue = parseFloat(jQuery('#mass_rate').val()) || 0;
            var directionId = jQuery('#direction_id').val();
            var originCountryId = jQuery('#countrydestination_id').val();
            
            
            if (massValue > 0 && rateValue <= 0 && directionId) {
                fetchRatePerKg();
            } else if (massValue > 0 && rateValue <= 0 && !directionId) {
                console.warn('kitscript.js: Cannot auto-trigger - direction_id missing');
                // Try to find direction_id from form
                var formDirectionId = jQuery('input[name="direction_id"]').val();
                if (formDirectionId) {
                    jQuery('#direction_id').val(formDirectionId);
                    fetchRatePerKg();
                }
            }
        }, 1000); // 1 second delay to ensure all elements are loaded
    });
    // Dispatch date validation
    var dispatchDateInput = document.getElementById('dispatch_date');
    var today = new Date().toISOString().split('T')[0];

    if (dispatchDateInput) {
        // Set min date attribute
        dispatchDateInput.min = today;

        // Additional validation on form submission
        document.querySelector('form').addEventListener('submit', function (e) {
            var selectedDate = dispatchDateInput.value;
            if (selectedDate < today) {
                e.preventDefault();
                dispatchDateInput.focus();
            }
        });
    }

    // VAT checkbox functionality
    var vatCheckbox2 = document.getElementById('vat_include2');
    var vatCheckbox = document.getElementById('vat_include');
    var SADC = document.getElementById('sadc_certificate');
    var optionz = document.querySelectorAll('.optionz');
    var nextStep3 = document.getElementById('next-step-3');
    var addWaybillItemBtn = document.getElementById('add-waybill-item-btn');

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
        // Only run this function if we're on a step that has VAT checkboxes
        if (!vatCheckbox && !vatCheckbox2) {
            return; // Exit early if no VAT checkboxes exist on this step
        }
        
        if (vatCheckbox && vatCheckbox.checked || vatCheckbox2 && vatCheckbox2.checked) {
            //bg-gray-300 text-gray-500 rounded-md hover:bg-gray-400
            if (nextStep3) {
                nextStep3.disabled = true;
                nextStep3.classList.add('bg-gray-300', 'text-gray-500');
                nextStep3.classList.remove('bg-blue-600', 'text-white');
            }

            if (SADC) {
                SADC.disabled = true;
                SADC.checked = 0;
            }
        } else {
            
            if (nextStep3) {
            nextStep3.disabled = false;
            nextStep3.classList.remove('bg-gray-300', 'text-gray-500');
            nextStep3.classList.add('bg-blue-600', 'text-white');
            }
            if (SADC) {
                SADC.disabled = false;
            }
        }
    }

    if (vatCheckbox2) {
        vatCheckbox2.addEventListener('change', toggleOptionzDisabled);
        //vatCheckbox2.addEventListener('change', deleteAllItems);
        // Run on page load in case VAT is pre-checked
        toggleOptionzDisabled();
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

        var $form = jQuery(this).closest('form');
        var waybillId = $form.find('input[name="waybill_id"]').val();
        var waybillNo = $form.find('input[name="waybill_no"]').val();
        var deliveryId = $form.find('input[name="delivery_id"]').val();
        var userId = $form.find('input[name="user_id"]').val();
        var nonce = $form.find('input[name="_wpnonce"]').val();
        var $row = jQuery(this).closest('tr');

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
        var allChecked = jQuery('.selectRow:checked').length === jQuery('.selectRow').length;
        jQuery('.selectAllRows').prop('checked', allChecked);
    });
    jQuery(document).on('click', '#add-waybill-item', function () {
        var nextStep3 = document.getElementById('next-step-3');
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
        var allChecked = jQuery(this).prop('checked');
        jQuery('.selectRow').prop('checked', allChecked);
    });

    // Delete delivery
    jQuery(document).on('click', '.delete-delivery', function (e) {
        e.preventDefault();
        var deliveryId = jQuery(this).data('delivery-id');
        if (!confirm('Are you sure you want to delete this delivery?')) {
            return;
        }
    });

    // Helpers for Step 4 next button styling
    function setStep4NextState(enabled) {
        var btn = document.getElementById('step4NextBtn');
        if (!btn) return;
        var active = [
            'inline-flex','items-center','px-6','py-3','bg-gradient-to-r','from-blue-600','to-indigo-600',
            'hover:from-blue-700','hover:to-indigo-700','text-white','font-semibold','rounded-xl','shadow-lg',
            'hover:shadow-xl','transform','hover:-translate-y-0.5','transition-all','duration-200'
        ];
        var disabled = ['opacity-50','cursor-not-allowed','pointer-events-none','bg-blue-400','bg-gray-300','text-gray-500'];
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
        var scheduledDeliveriesList = document.getElementById('scheduled-deliveries-list');
        var nextBtn = document.getElementById('step4NextBtn');
        var destinationCountry = document.getElementById('stepDestinationSelect');
        var destinationCity = document.getElementById('destination_city');
        var destinationCountryHelp = document.getElementById('destination-country-help');
        var destinationCityHelp = document.getElementById('destination-city-help');
        
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
        var warehousedCheckbox = document.getElementById('warehoused_option');
        var nextBtn = document.getElementById('step4NextBtn');
        var destinationCountry = document.getElementById('stepDestinationSelect');
        var destinationCity = document.getElementById('destination_city');
        var destinationCountryHelp = document.getElementById('destination-country-help');
        var destinationCityHelp = document.getElementById('destination-city-help');
        
        // If warehoused is checked, no validation needed
        if (warehousedCheckbox && warehousedCheckbox.checked) {
            setStep4NextState(true);
            return;
        }
        
        // Check if both country and city are selected
        var countrySelected = destinationCountry && destinationCountry.value && destinationCountry.value !== '';
        var citySelected = destinationCity && destinationCity.value && destinationCity.value !== '';
        
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
        var warehousedCheckbox = document.getElementById('warehoused_option');
        var destinationCountryHelp = document.getElementById('destination-country-help');
        var destinationCityHelp = document.getElementById('destination-city-help');
        
        if (warehousedCheckbox && warehousedCheckbox.checked) {
            if (destinationCountryHelp) destinationCountryHelp.style.display = 'none';
            if (destinationCityHelp) destinationCityHelp.style.display = 'none';
        }
    });

    // Remove legacy manipulator handler that overwrote rate; handled in weight.php now
});

// Global spinner management system
var SpinnerManager = {
    // Store active spinners to prevent duplicates
    activeSpinners: new Set(),

    // CSS for the spinner (injected once)
    injectStyles: function () {
        if (!document.getElementById('spinner-styles')) {
            var style = document.createElement('style');
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
        var self = this;
        this.activeSpinners.forEach(function(input) {
            input.classList.remove('input-spinner');
        });
        this.activeSpinners.clear();
    }
};

// Customer Dashboard Tab Functionality
function switchCustomerTab(tabName) {
    // Hide all customer tab contents only
    var customerTabContents = document.querySelectorAll('.customer-tabs .tab-content');
    customerTabContents.forEach(function(content) {
        content.style.display = 'none';
    });

    // Remove active class from all customer tab buttons only
    var customerTabButtons = document.querySelectorAll('.customer-tabs .tab-btn');
    customerTabButtons.forEach(function(btn) {
        btn.classList.remove('active');
        btn.style.background = 'transparent';
        btn.style.color = '#6b7280';
    });

    // Show selected tab content
    var selectedContent = document.getElementById(tabName + '-content');
    if (selectedContent) {
        selectedContent.style.display = 'block';
    }

    // Add active class to selected tab button
    var selectedButton = document.getElementById(tabName + '-tab');
    if (selectedButton) {
        selectedButton.classList.add('active');
        selectedButton.style.background = 'white';
        selectedButton.style.color = '#374151';
    }
}

// Initialize customer dashboard tabs
document.addEventListener('DOMContentLoaded', function() {
    // Customer dashboard tab functionality
    var customerTabButtons = document.querySelectorAll('.customer-tabs .tab-btn');
    customerTabButtons.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tabName = this.id.replace('-tab', '');
            switchCustomerTab(tabName);
        });
    });

    // Handle inline customer form submission
    var inlineCustomerForm = document.getElementById('inlineCustomerForm');
    if (inlineCustomerForm) {
        inlineCustomerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            var submitBtn = document.getElementById('saveCustomerBtn');
            var messagesDiv = document.getElementById('customerFormMessages');
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
            var formData = new FormData(this);
            formData.append('action', 'save_customer_ajax');
            formData.append('nonce', customerAjax.nonce);
            var safeVal = function(id) {
                var el = document.getElementById(id);
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
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    // Show success message
                    messagesDiv.innerHTML = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">' + data.data.message + '</div>';
                    
                    // Reset form
                    inlineCustomerForm.reset();
                    
                    // Redirect to the manage-customers tab after 1.5 seconds
                    setTimeout(function() {
                        messagesDiv.innerHTML = '';
                        // Redirect to the manage-customers tab with a refresh parameter
                        var currentUrl = new URL(window.location);
                        currentUrl.searchParams.set('tab', 'manage-customers');
                        currentUrl.searchParams.set('customer_added', '1');
                        window.location.href = currentUrl.toString();
                    }, 1500);
                } else {
                    // Show error message
                    messagesDiv.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Error: ' + (data.data || 'Unknown error occurred') + '</div>';
                }
            })
            .catch(function(error) {
                messagesDiv.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Network error: ' + error.message + '</div>';
            })
            .finally(function() {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save Customer';
            });
        });
    }

    // Handle country change for city loading
    var countrySelect = document.getElementById('country_id');
    var citySelect = document.getElementById('city_id');
    
    if (countrySelect && citySelect) {
        countrySelect.addEventListener('change', function() {
            var countryId = this.value;
            
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
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success && data.data) {
                        data.data.forEach(function(city) {
                            var option = document.createElement('option');
                            option.value = city.id;
                            option.textContent = city.city_name;
                            citySelect.appendChild(option);
                        });
                    }
                })
                .catch(function(error) {
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
    var errorDiv = jQuery('#rate-fetch-error');
    if (errorDiv.length === 0) {
        errorDiv = jQuery('<div id="rate-fetch-error" class="text-red-600 text-sm mt-2"></div>');
        jQuery('#mass_rate').parent().append(errorDiv);
    }
    errorDiv.text(message);
    
    // Auto-hide after 5 seconds
    setTimeout(function() {
        errorDiv.fadeOut(500, function() {
            jQuery(this).remove();
        });
    }, 5000);
}