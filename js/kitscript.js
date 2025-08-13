//file location: js/kitscript.js
function toggleDropdownDeliveryStatus(delivery_id) {
    const dropdown = document.getElementById('delivery-status-dropdown-' + delivery_id);
    dropdown.classList.toggle('hidden');
}

function hello() {
    alert("Hello");
}

function fetchRatePerKg() {
    const total_mass_kg = parseFloat(jQuery('#total_mass_kg').val()) || 0;
    const country_id = jQuery('#countrydestination_id').val(); // Hidden input or radio
    let current_rate = jQuery('#current_rate').val();


    if (total_mass_kg > 0) {
        jQuery.ajax({
            url: myPluginAjax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'handle_get_price_per_kg',
                total_mass_kg: total_mass_kg,
                origin_country_id: country_id,
                nonce: myPluginAjax.nonces.get_waybills_nonce
            },
            success: function (response) {

                if (response.success) {
                    const rate = response.data.rate_per_kg;

                    console.log('Rate per kg: R' + rate);
                    current_rate = rate;

                    jQuery('#mass_charge_display').text(rate);
                    jQuery('#mass_charge').val(rate * total_mass_kg);
                    jQuery('#mass_rate').val(rate);
                    jQuery('#current_rate').val(rate);
                } else {
                    console.warn(response.data.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
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
        timeout = setTimeout(fetchRatePerKg, 0); // Wait 500ms after user stops typing
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

    // Warehoused checkbox
    jQuery(document).on('change', '#warehoused_option', function () {
        const scheduledDeliveriesList = document.getElementById('scheduled-deliveries-list');
        const specialDeliveryBtn = document.getElementById('specialDeliveryBtn');
        const destinationCountry = document.getElementById('stepDestinationSelect');
        const destinationCity = document.getElementById('destination_city');
        const destinationCountryHelp = document.getElementById('destination-country-help');
        const destinationCityHelp = document.getElementById('destination-city-help');
        
        if (jQuery(this).is(':checked')) {
            // Hide scheduled deliveries when warehoused is checked
            scheduledDeliveriesList.classList.add('hidden');
            
            // Enable the next button for warehoused items
            specialDeliveryBtn.disabled = false;
            specialDeliveryBtn.classList.remove('bg-gray-300', 'text-gray-500');
            specialDeliveryBtn.classList.add('bg-blue-600', 'text-white');
            
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
        const specialDeliveryBtn = document.getElementById('specialDeliveryBtn');
        const destinationCountry = document.getElementById('stepDestinationSelect');
        const destinationCity = document.getElementById('destination_city');
        const destinationCountryHelp = document.getElementById('destination-country-help');
        const destinationCityHelp = document.getElementById('destination-city-help');
        
        // If warehoused is checked, no validation needed
        if (warehousedCheckbox && warehousedCheckbox.checked) {
            specialDeliveryBtn.disabled = false;
            specialDeliveryBtn.classList.remove('bg-gray-300', 'text-gray-500');
            specialDeliveryBtn.classList.add('bg-blue-600', 'text-white');
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
        
        if (countrySelected && citySelected) {
            specialDeliveryBtn.disabled = false;
            specialDeliveryBtn.classList.remove('bg-gray-300', 'text-gray-500');
            specialDeliveryBtn.classList.add('bg-blue-600', 'text-white');
        } else {
            specialDeliveryBtn.disabled = true;
            specialDeliveryBtn.classList.remove('bg-blue-600', 'text-white');
            specialDeliveryBtn.classList.add('bg-gray-300', 'text-gray-500');
        }
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

    jQuery('#enable_price_manipulator').on('change', function () {
        let current_rate = parseFloat(jQuery('#current_rate').val().replace(',', '.')) || 0;
        if (this.checked) {
            /* When mass_charge_manipulator is changed, then alert the value after 2 seconds */
            jQuery('#mass_charge_manipulator').on('input', function () {
                const manipulatorVal = parseFloat(jQuery('#mass_charge_manipulator').val()) || 0;
                const rateVal = parseFloat(current_rate) || 0;
                const sum = rateVal + manipulatorVal;
                jQuery('#manipulated_mass_charge_display').text('+ R' + manipulatorVal + ' = ' + sum);

                /* Now update the #mass_charge where we take now the new value 'sum' and multiply it by the total_mass_kg */
                const total_mass_kg = parseFloat(jQuery('#total_mass_kg').val()) || 0;
                const new_mass_charge = sum * total_mass_kg;
                jQuery('#mass_charge').val(new_mass_charge);
                jQuery('#mass_rate').val(sum);

                // Take this new new_mass_charge and add create a hidden input called new_mass_rate to send via POST
                // If the input doesn't exist, create it; otherwise, update its value
                if (jQuery('#new_mass_rate').length === 0) {
                    jQuery('<input>').attr({
                        type: 'hidden',
                        id: 'new_mass_rate',
                        name: 'new_mass_rate',
                        value: sum
                    }).appendTo('form');
                } else {
                    jQuery('#new_mass_rate').val(sum);
                }

            });
        } else {
            

            jQuery('#mass_charge_display').text(current_rate);
            jQuery('#manipulated_mass_charge_display').text("");
            jQuery('#mass_rate').val(current_rate);

            // Optional: remove the hidden input if it exists
            if (jQuery('#new_mass_rate').length > 0) {
                jQuery('#new_mass_rate').remove();
            }
        }
    });
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
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => {
        content.style.display = 'none';
    });

    // Remove active class from all tab buttons
    const tabButtons = document.querySelectorAll('.tab-btn');
    tabButtons.forEach(btn => {
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
            formData.append('name', safeVal('customer_name'));
            formData.append('surname', safeVal('customer_surname'));
            formData.append('cell', safeVal('cell'));
            formData.append('email_address', safeVal('email_address'));
            formData.append('address', safeVal('address'));
            formData.append('company_name', safeVal('company_name'));
            formData.append('country_id', safeVal('country_id'));
            formData.append('city_id', safeVal('city_id'));
            
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
                    
                    // Switch to overview tab after 2 seconds
                    setTimeout(() => {
                        switchCustomerTab('overview');
                        messagesDiv.innerHTML = '';
                    }, 2000);
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
});