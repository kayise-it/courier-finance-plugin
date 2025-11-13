//file location: js/kitscript.js
function toggleDropdownDeliveryStatus(delivery_id) {
    var dropdown = document.getElementById('delivery-status-dropdown-' + delivery_id);
    dropdown.classList.toggle('hidden');
}

function hello() {
    alert("Hello");
}

(function initializeCustomersData() {
    if (typeof window === 'undefined') {
        return;
    }

    const existingData = (window.CUSTOMERS_DATA && typeof window.CUSTOMERS_DATA === 'object') ? window.CUSTOMERS_DATA : {};
    const localizedCustomers = (typeof EditWaybillData !== 'undefined' && Array.isArray(EditWaybillData.customers)) ? EditWaybillData.customers : [];
    const customersFromLocalized = {};

    if (Array.isArray(localizedCustomers)) {
        localizedCustomers.forEach(customer => {
            if (customer && typeof customer === 'object' && customer.cust_id) {
                customersFromLocalized[customer.cust_id] = customer;
            }
        });
    }

    window.CUSTOMERS_DATA = Object.assign({}, existingData, customersFromLocalized);
})();

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
        // Helper function to properly parse numbers with comma decimal separators
        function parseNumber(value) {
            if (!value || value === '') return 0;
            // Convert comma to dot for decimal separator
            var normalized = value.toString().replace(',', '.');
            var parsed = parseFloat(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        }
        
        setTimeout(function() {
            var massValue = parseNumber(jQuery('#total_mass_kg').val());
            var rateValue = parseNumber(jQuery('#mass_rate').val());
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
    // Dispatch date validation - REMOVED: Allow editing of old deliveries
    // Previously restricted dates to today or later, but users need to edit past deliveries
    var dispatchDateInput = document.getElementById('dispatch_date');
    
    if (dispatchDateInput) {
        // Remove any min date restriction to allow editing old deliveries
        dispatchDateInput.removeAttribute('min');
        
        // Removed date validation on form submission - allow past dates
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
    jQuery(document).on('change', '#pending_option', function () {
        var scheduledDeliveriesList = document.getElementById('scheduled-deliveries-list');
        var nextBtn = document.getElementById('step4NextBtn');
        var destinationCountry = document.getElementById('stepDestinationSelect');
        var destinationCity = document.getElementById('destination_city');
        var destinationCountryHelp = document.getElementById('destination-country-help');
        var destinationCityHelp = document.getElementById('destination-city-help');
        
        if (jQuery(this).is(':checked')) {
            // Hide scheduled deliveries when pending is checked
            scheduledDeliveriesList.classList.add('hidden');
            
            // Enable the next button for pending items
            setStep4NextState(true);
            
            // Clear destination fields for pending items
            if (destinationCountry) destinationCountry.value = '';
            if (destinationCity) destinationCity.value = '';
            
            // Hide helper text for destination fields
            if (destinationCountryHelp) destinationCountryHelp.style.display = 'none';
            if (destinationCityHelp) destinationCityHelp.style.display = 'none';
            
        } else {
            // Show scheduled deliveries when pending is unchecked
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
        var pendingCheckbox = document.getElementById('pending_option');
        var nextBtn = document.getElementById('step4NextBtn');
        var destinationCountry = document.getElementById('stepDestinationSelect');
        var destinationCity = document.getElementById('destination_city');
        var destinationCountryHelp = document.getElementById('destination-country-help');
        var destinationCityHelp = document.getElementById('destination-city-help');
        
        // If pending is checked, no validation needed
        if (pendingCheckbox && pendingCheckbox.checked) {
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
        var pendingCheckbox = document.getElementById('pending_option');
        var destinationCountryHelp = document.getElementById('destination-country-help');
        var destinationCityHelp = document.getElementById('destination-city-help');
        
        if (pendingCheckbox && pendingCheckbox.checked) {
            if (destinationCountryHelp) destinationCountryHelp.style.display = 'none';
            if (destinationCityHelp) destinationCityHelp.style.display = 'none';
        }
    });

    (function initEditWaybillEditPage() {
        const editForm = document.querySelector('form[action*="update_waybill_action"]');
        const customerSearch = document.getElementById('customer-search-edit');

        if (!editForm || !customerSearch) {
            return;
        }

        const customersData = (window.CUSTOMERS_DATA && typeof window.CUSTOMERS_DATA === 'object') ? window.CUSTOMERS_DATA : {};
        const customerSelect = document.getElementById('customer-select-edit');
        const customerResults = document.getElementById('customer-results-edit');
        const custIdInput = document.querySelector('input[name="cust_id"]');
        const addNewCustomerBtn = document.getElementById('add-new-customer-btn-edit');
        const recentCustomersBtn = document.getElementById('recent-customers-btn-edit');

        const originDefaults = {
            country: (document.getElementById('origin_country_initial')?.value || '').trim(),
            city: (() => {
                const val = (document.getElementById('origin_city_initial')?.value || '').trim();
                return val === '0' ? '' : val;
            })()
        };
        let allowAutoOriginFromCustomer = !originDefaults.city;
        let originRestoreApplied = false;

        const destinationDefaults = {
            country: (document.getElementById('destination_country_initial')?.value || '').trim(),
            city: (() => {
                const val = (document.getElementById('destination_city_initial')?.value || '').trim();
                return val === '0' ? '' : val;
            })()
        };
        let destinationRestoreApplied = false;

        function getCustomerInput(idBase) {
            return document.getElementById(idBase) ||
                document.getElementById('a' + idBase) ||
                document.getElementById('a_' + idBase) ||
                document.querySelector('[name="' + idBase + '"]');
        }

        const nameInput = getCustomerInput('customer_name_edit');
        const surnameInput = getCustomerInput('customer_surname_edit');
        const cellInput = getCustomerInput('cell_edit');
        const addressInput = getCustomerInput('address_edit');
        const emailInput = getCustomerInput('email_address_edit');
        const telephoneInput = getCustomerInput('telephone_edit');
        const companyNameInput = document.getElementById('company_name_edit');
        const companyNameWrapper = document.getElementById('company_name_wrapper_edit');
        const clientTypeRadios = document.querySelectorAll('.client-type-radio-edit');

        function updateCompanyNameVisibility() {
            const selectedType = document.querySelector('input[name="client_type"]:checked')?.value;
            if (selectedType === 'individual') {
                if (companyNameWrapper) companyNameWrapper.style.display = 'none';
                if (companyNameInput) companyNameInput.value = '1ndividual';
            } else if (companyNameWrapper) {
                companyNameWrapper.style.display = '';
            }
        }

        clientTypeRadios.forEach(function(radio) {
            radio.addEventListener('change', updateCompanyNameVisibility);
        });
        updateCompanyNameVisibility();

        function restoreOriginSelections(force) {
            if (originRestoreApplied && !force) {
                return;
            }

            const originCountrySelect = document.getElementById('origin_country_select');
            const originCitySelect = document.getElementById('origin_city_select');
            if (!originCountrySelect) {
                return;
            }

            if (originDefaults.country) {
                originCountrySelect.value = originDefaults.country;
            }

            const applyCitySelection = () => {
                if (!originCitySelect || !originDefaults.city) {
                    return false;
                }
                const targetValue = String(originDefaults.city);
                if (String(originCitySelect.value) === targetValue) {
                    return true;
                }
                const match = Array.from(originCitySelect.options || []).some(option => String(option.value) === targetValue);
                if (match) {
                    originCitySelect.value = targetValue;
                    return true;
                }
                return false;
            };

            if (originCitySelect && originDefaults.city) {
                const observer = new MutationObserver((mutations, obs) => {
                    if (applyCitySelection()) {
                        obs.disconnect();
                    }
                });
                observer.observe(originCitySelect, { childList: true });
                applyCitySelection();
            }

            if (originDefaults.country && typeof loadCitiesForCountry === 'function') {
                loadCitiesForCountry(originDefaults.country, 'origin', originDefaults.city || '');
            } else if (originDefaults.country && originCountrySelect.onchange) {
                originCountrySelect.onchange();
                setTimeout(applyCitySelection, 250);
            } else {
                applyCitySelection();
            }

            originRestoreApplied = true;
        }

        function restoreDestinationSelections(force) {
            if (destinationRestoreApplied && !force) {
                return;
            }

            const destinationCountrySelect = document.getElementById('destination_country_select');
            const destinationCitySelect = document.getElementById('destination_city_select');
            if (!destinationCountrySelect) {
                return;
            }

            if (destinationDefaults.country) {
                destinationCountrySelect.value = destinationDefaults.country;
            }

            const applyDestinationCity = () => {
                if (!destinationCitySelect || !destinationDefaults.city) {
                    return false;
                }
                const targetValue = String(destinationDefaults.city);
                if (String(destinationCitySelect.value) === targetValue) {
                    return true;
                }
                const match = Array.from(destinationCitySelect.options || []).some(option => String(option.value) === targetValue);
                if (match) {
                    destinationCitySelect.value = targetValue;
                    return true;
                }
                return false;
            };

            const observeDestination = destinationCitySelect && destinationDefaults.city;
            if (observeDestination) {
                const observer = new MutationObserver((mutations, obs) => {
                    if (applyDestinationCity()) {
                        obs.disconnect();
                    }
                });
                observer.observe(destinationCitySelect, { childList: true });
                applyDestinationCity();
            }

            if (destinationDefaults.country && typeof loadCitiesForCountry === 'function') {
                loadCitiesForCountry(destinationDefaults.country, 'destination', destinationDefaults.city || '');
            } else if (destinationDefaults.country && destinationCountrySelect.onchange) {
                destinationCountrySelect.onchange();
                setTimeout(applyDestinationCity, 250);
            } else {
                applyDestinationCity();
            }

            destinationRestoreApplied = true;
        }

        function searchCustomers(query) {
            if (!customerResults) {
                return;
            }
            if (!query || query.length < 2) {
                customerResults.classList.add('hidden');
                return;
            }
            const results = [];
            const searchTerm = query.toLowerCase();

            Object.values(customersData).forEach(customer => {
                const name = (customer.customer_name || '').toLowerCase();
                const surname = (customer.customer_surname || '').toLowerCase();
                const company = (customer.company_name || '').toLowerCase();
                const cell = (customer.cell || '').toLowerCase();
                const email = (customer.email_address || '').toLowerCase();

                if (
                    name.includes(searchTerm) ||
                    surname.includes(searchTerm) ||
                    company.includes(searchTerm) ||
                    cell.includes(searchTerm) ||
                    email.includes(searchTerm) ||
                    `${name} ${surname}`.includes(searchTerm)
                ) {
                    results.push(customer);
                }
            });

            displaySearchResults(results.slice(0, 10));
        }

        function displaySearchResults(results) {
            if (!customerResults) {
                return;
            }
            customerResults.innerHTML = '';
            if (results.length === 0) {
                customerResults.innerHTML = '<div class="px-4 py-2 text-gray-500 text-sm">No customers found</div>';
            } else {
                results.forEach(customer => {
                    const item = document.createElement('div');
                    item.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0';
                    item.innerHTML = `
                        <div class="font-medium text-gray-900">${customer.customer_name || ''} ${customer.customer_surname || ''}</div>
                        <div class="text-sm text-gray-500">${customer.company_name || customer.cell || ''}</div>
                    `;
                    item.addEventListener('click', () => selectCustomer(customer));
                    customerResults.appendChild(item);
                });
            }
            customerResults.classList.remove('hidden');
        }

        function selectCustomer(customer) {
            allowAutoOriginFromCustomer = true;
            originDefaults.country = '';
            originDefaults.city = '';
            originRestoreApplied = true;

            customerSearch.value = `${customer.customer_name || ''} ${customer.customer_surname || ''}`.trim();
            if (customerSelect) customerSelect.value = customer.cust_id || '';
            if (custIdInput) custIdInput.value = customer.cust_id || '';
            if (customerResults) customerResults.classList.add('hidden');
            populateCustomerDetails(customer.cust_id);
        }

        function addNewCustomer() {
            customerSearch.value = '';
            if (customerSelect) customerSelect.value = 'new';
            if (custIdInput) custIdInput.value = '0';
            if (customerResults) customerResults.classList.add('hidden');
            clearCustomerFields();
            allowAutoOriginFromCustomer = true;
            originDefaults.country = '';
            originDefaults.city = '';
            originRestoreApplied = true;
        }

        function showRecentCustomers() {
            if (!customerResults) return;
            const recentCustomers = Object.values(customersData)
                .sort((a, b) => (b.created_at || '').localeCompare(a.created_at || ''))
                .slice(0, 10);
            displaySearchResults(recentCustomers);
        }

        function populateCustomerDetails(customerId) {
            if (!customersData || !customersData[customerId]) {
                return;
            }
            const customer = customersData[customerId];
            const dn = customer.customer_name || '';
            const ds = customer.customer_surname || '';
            const dc = customer.cell || '';
            const da = customer.address || '';
            const de = customer.email_address || '';
            const dco = customer.company_name || customer.customer_name || '';
            if (nameInput) nameInput.value = dn;
            if (surnameInput) surnameInput.value = ds;
            if (cellInput) cellInput.value = dc;
            if (telephoneInput) telephoneInput.value = dc;
            if (addressInput) addressInput.value = da;
            if (emailInput) emailInput.value = de;
            if (companyNameInput) companyNameInput.value = dco;
            if (custIdInput) custIdInput.value = customerId;
            updateCompanyNameVisibility();
            populateOriginFromCustomer(customerId);

            const confirmationDiv = document.createElement('div');
            confirmationDiv.className = 'mt-2 p-2 bg-green-100 border border-green-300 rounded text-sm text-green-700 customer-change-confirmation';
            confirmationDiv.innerHTML = '✅ Customer updated! Click "Save Changes" to apply.';

            const existingConfirmation = document.querySelector('.customer-change-confirmation');
            if (existingConfirmation) {
                existingConfirmation.remove();
            }

            customerSearch.parentNode.appendChild(confirmationDiv);

            setTimeout(() => {
                if (confirmationDiv.parentNode) {
                    confirmationDiv.remove();
                }
            }, 3000);
        }

        function populateOriginFromCustomer(customerId) {
            if (!customersData || !customersData[customerId] || !allowAutoOriginFromCustomer) {
                return;
            }
            const customer = customersData[customerId];
            const customerData = {
                country_id: customer.country_id || '',
                city_id: customer.city_id || ''
            };
            const originCountryId = customerData.country_id || 1;
            const originCountrySelect = document.getElementById('origin_country_select');
            if (originCountrySelect) {
                originCountrySelect.value = originCountryId;
                if (typeof loadCitiesForCountry === 'function') {
                    loadCitiesForCountry(originCountryId, 'origin', customerData.city_id);
                } else if (originCountrySelect.onchange) {
                    originCountrySelect.onchange();
                }
            }
        }

        function clearCustomerFields() {
            if (nameInput) nameInput.value = '';
            if (surnameInput) surnameInput.value = '';
            if (cellInput) cellInput.value = '';
            if (telephoneInput) telephoneInput.value = '';
            if (addressInput) addressInput.value = '';
            if (emailInput) emailInput.value = '';
            if (companyNameInput) companyNameInput.value = '';
            if (custIdInput) custIdInput.value = '0';
            const businessRadio = document.getElementById('client_type_business_edit');
            if (businessRadio) businessRadio.checked = true;
            updateCompanyNameVisibility();
        }

        if (customerSearch) {
            customerSearch.addEventListener('input', function() {
                searchCustomers(this.value);
            });
            customerSearch.addEventListener('focus', function() {
                if (this.value.length >= 2) {
                    searchCustomers(this.value);
                }
            });
            document.addEventListener('click', function(e) {
                if (!customerSearch.contains(e.target) && !customerResults?.contains(e.target)) {
                    customerResults?.classList.add('hidden');
                }
            });
            customerSearch.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    customerResults?.classList.add('hidden');
                }
            });
        }

        if (addNewCustomerBtn) {
            addNewCustomerBtn.addEventListener('click', addNewCustomer);
        }
        if (recentCustomersBtn) {
            recentCustomersBtn.addEventListener('click', showRecentCustomers);
        }

        const initialCustomerId = custIdInput?.value;
        if (initialCustomerId && initialCustomerId !== '0' && customersData[initialCustomerId]) {
            const customer = customersData[initialCustomerId];
            customerSearch.value = `${customer.customer_name || ''} ${customer.customer_surname || ''}`.trim();
            if (customerSelect) customerSelect.value = initialCustomerId;
            populateCustomerDetails(initialCustomerId);
        } else if (initialCustomerId === '0' || !initialCustomerId) {
            customerSearch.value = '';
            if (customerSelect) customerSelect.value = 'new';
        }

        if (!allowAutoOriginFromCustomer) {
            setTimeout(() => restoreOriginSelections(false), 0);
        }
        setTimeout(() => restoreDestinationSelections(false), 0);

        const warehouseCheckbox = document.getElementById('pending_option');
        if (warehouseCheckbox) {
            warehouseCheckbox.addEventListener('change', function() {
                const isWarehoused = this.checked;
                if (typeof window.waybillData === 'undefined') {
                    window.waybillData = {};
                }
                window.waybillData.warehouse = isWarehoused;

                const warehouseStatusField = document.getElementById('warehouse_status');
                if (warehouseStatusField) {
                    warehouseStatusField.value = isWarehoused ? '1' : '0';
                }

                const confirmationDiv = document.createElement('div');
                confirmationDiv.className = 'mt-2 p-2 bg-blue-100 border border-blue-300 rounded text-sm text-blue-700 warehouse-confirmation';
                confirmationDiv.innerHTML = isWarehoused ?
                    '✅ Waybill marked as warehoused' :
                    '✅ Waybill removed from warehouse';

                const existingConfirmation = document.querySelector('.warehouse-confirmation');
                if (existingConfirmation) {
                    existingConfirmation.remove();
                }

                this.parentNode.appendChild(confirmationDiv);

                setTimeout(() => {
                    if (confirmationDiv.parentNode) {
                        confirmationDiv.remove();
                    }
                }, 3000);
            });
        }

        window.selectDeliveryCard = function(cardElement, directionId) {
            document.querySelectorAll('.delivery-card').forEach(card => {
                card.classList.remove('selected');
            });

            cardElement.classList.add('selected');

            const dispatchDate = cardElement.getAttribute('data-dispatch-date') || '';
            const truckNumber = cardElement.getAttribute('data-truck-number') || '';
            const driverId = cardElement.getAttribute('data-driver-id') || '';
            const deliveryId = cardElement.getAttribute('data-delivery-id') || '';

            const deliveryIdField = document.getElementById('delivery_id');
            const directionIdField = document.getElementById('direction_id');

            if (deliveryIdField) {
                deliveryIdField.value = deliveryId || directionId;
            }
            if (directionIdField) {
                directionIdField.value = directionId;
            }

            const dispatchDateField = document.getElementById('dispatch_date_edit');
            if (dispatchDateField && dispatchDate) {
                dispatchDateField.value = dispatchDate;
            }

            const truckNumberField = document.getElementById('truck_number_edit');
            if (truckNumberField && truckNumber) {
                truckNumberField.value = truckNumber;
            }

            const truckDriverField = document.getElementById('truck_driver_edit');
            if (truckDriverField && driverId) {
                truckDriverField.value = driverId;
            }

            const confirmationDiv = document.createElement('div');
            confirmationDiv.className = 'mt-2 p-2 bg-green-100 border border-green-300 rounded text-sm text-green-700 delivery-assignment-confirmation';
            confirmationDiv.innerHTML = '✅ Delivery, dispatch date, truck, and driver updated! Click "Save Changes" to apply.';

            const existingConfirmation = document.querySelector('.delivery-assignment-confirmation');
            if (existingConfirmation) {
                existingConfirmation.remove();
            }

            cardElement.parentNode.appendChild(confirmationDiv);

            setTimeout(() => {
                if (confirmationDiv.parentNode) {
                    confirmationDiv.remove();
                }
            }, 3000);
        };

        if (typeof window.handleDeliveryClick === 'undefined') {
            window.handleDeliveryClick = function(cardElement, directionId) {
                if (typeof window.selectDeliveryCard === 'function') {
                    window.selectDeliveryCard(cardElement, directionId);
                } else {
                    console.error('selectDeliveryCard function not found');
                }
            };
        }

        const currentDeliveryId = document.getElementById('current_delivery_id')?.value;
        if (currentDeliveryId) {
            setTimeout(() => {
                const deliveryCards = document.querySelectorAll('.delivery-card');
                deliveryCards.forEach(card => {
                    const cardId = card.getAttribute('data-index');
                    if (cardId === currentDeliveryId && typeof window.selectDeliveryCard === 'function') {
                        window.selectDeliveryCard(card, cardId);
                    }
                });
            }, 500);
        }

        setTimeout(() => {
            const deliveryCards = document.querySelectorAll('.delivery-card');
            deliveryCards.forEach(card => {
                if (!card.hasAttribute('onclick')) {
                    card.addEventListener('click', function(e) {
                        e.preventDefault();
                        const directionId = this.getAttribute('data-index') ||
                            this.getAttribute('data-delivery-id') ||
                            this.getAttribute('data-direction-id');
                        if (directionId && typeof window.selectDeliveryCard === 'function') {
                            window.selectDeliveryCard(this, directionId);
                        }
                    });
                }
            });
        }, 100);
    })();

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
            var cn = (safeVal('company_name') || '').trim();
            formData.append('company_name', cn !== '' ? cn : 'Individual');
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