<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="mb-6">
    <h2 class="text-lg font-medium text-gray-700 mb-3">Customer Information</h2>
    <?php $is_existing_customer = $atts['is_existing_customer'] ?>
    <!-- Hidden field to store customer ID -->
    <input type="hidden" id="cust_id" name="cust_id" value="<?php echo esc_attr($customer_id); ?>">

    <!-- Customer selection dropdown -->
    <?php $customers = KIT_Customers::tholaMaCustomer(); ?>
    <div class="mb-4">
        <?php
        echo KIT_Commons::customerSelect([
            'label' => 'Select Customer',
            'name' => 'customer_select',
            'id' => 'customer-select',
            'existing_customer' => $customer_id,
            'customer' => $customers,
        ]);
        ?>
    </div>

    <!-- Customer Details Form -->
    <div class="border rounded-md overflow-hidden mb-4">
        <?php echo KIT_Commons::renderButton('Customer Details', 'secondary', 'md', [
            'classes' => 'customer-accordion-toggle w-full text-left bg-gray-100 hover:bg-gray-200 font-medium'
        ]); ?>

        <div class="customer-details-content px-4 py-3 bg-white">
            <div class=" gap-4">
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Company Name',
                        'name'  => 'company_name',
                        'id'    => 'company_name',
                        'type'  => 'text',
                        'value' => esc_attr($is_existing_customer ? $customer->name : ''),
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                        'special' => 'autocomplete="off" data-lpignore="true" data-1p-ignore="true"'
                    ]); ?>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Customer Name',
                        'name'  => 'customer_name',
                        'id'    => 'customer_name',
                        'type'  => 'text',
                        'value' => esc_attr($is_existing_customer ? $customer->name : ''),
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                        'special' => 'autocomplete="off" data-lpignore="true" data-1p-ignore="true"'
                    ]); ?>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Customer Surname',
                        'name'  => 'customer_surname',
                        'id'    => 'customer_surname',
                        'type'  => 'text',
                        'value' => esc_attr($is_existing_customer ? $customer->surname : ''),
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                        'special' => 'autocomplete="off" data-lpignore="true" data-1p-ignore="true"'
                    ]); ?>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Cell',
                        'name'  => 'cell',
                        'id'    => 'cell',
                        'type'  => 'tel',
                        'value' => esc_attr($is_existing_customer ? $customer->cell : ''),
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                        'special' => 'autocomplete="off" data-lpignore="true" data-1p-ignore="true"'
                    ]); ?>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Email',
                        'name'  => 'email_address',
                        'id'    => 'email_address',
                        'type'  => 'email',
                        'value' => esc_attr($is_existing_customer ? $customer->email_address : ''),
                        'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                        'special' => 'autocomplete="off" data-lpignore="true" data-1p-ignore="true"'
                    ]); ?>
                </div>

            </div>
            <div>

                <?= KIT_Commons::Linput([
                    'label' => 'Address',
                    'name'  => 'address',
                    'id'    => 'address',
                    'type'  => 'text',
                    'value' => esc_attr($is_existing_customer ? $customer->address : ''),
                    'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500',
                    'special' => 'autocomplete="off" data-lpignore="true" data-1p-ignore="true"'
                ]); ?>
            </div>
            <div>
                <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsOrigin.php'); ?>
            </div>
        </div>
    </div>
</div>
<div class="flex justify-between mt-8">
    <?php echo KIT_Commons::renderButton('Back', 'secondary', 'md', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12" />',
        'iconPosition' => 'left',
        'data-target' => 'step-1',
        'classes' => 'md:hidden prev-step'
    ]); ?>
    <?php echo KIT_Commons::renderButton('Next: Waybill Items', 'primary', 'md', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />',
        'iconPosition' => 'right',
        'data-target' => 'step-3',
        'classes' => 'md:hidden next-step',
        'gradient' => true
    ]); ?>
    <?php echo KIT_Commons::renderButton('Next: Waybill Items', 'primary', 'md', [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />',
        'iconPosition' => 'right',
        'data-target' => 'step-3',
        'classes' => 'next-step',
        'gradient' => true
    ]); ?>

</div>

<script>
// Customer dataset fallback for population when <option> lacks data-* attributes
window.CUSTOMERS_DATA = window.CUSTOMERS_DATA || (function(){
    try {
        return <?php echo wp_json_encode($customers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    } catch (e) { return {}; }
})();
document.addEventListener('DOMContentLoaded', function() {
    // Customer selection functionality for step2
    const customerSelect = document.getElementById('customer-select');
    const custIdInput = document.getElementById('cust_id');
    
    // Function to get customer input fields (robust lookup)
    function getCustomerInput(idBase) {
        const byId = document.getElementById(idBase);
        if (byId) return byId;
        const byPref = document.getElementById('a' + idBase) || document.getElementById('a_' + idBase);
        if (byPref) return byPref;
        const byName = document.querySelector('[name="' + idBase + '"]');
        if (byName) return byName;
        return null;
    }
    
    const nameInput = getCustomerInput('customer_name');
    const surnameInput = getCustomerInput('customer_surname');
    const cellInput = getCustomerInput('cell');
    const addressInput = getCustomerInput('address');
    const emailInput = getCustomerInput('email_address');
    const companyNameInput = getCustomerInput('company_name');

    // Function to populate customer fields
    function populateCustomerDetails(customerId) {
        const option = customerSelect?.querySelector(`option[value="${customerId}"]`);
        let dn = '', ds = '', dc = '', da = '', de = '', dco = '';
        if (option) {
            dn = option.getAttribute('data-name') || '';
            ds = option.getAttribute('data-surname') || '';
            dc = option.getAttribute('data-cell') || '';
            da = option.getAttribute('data-address') || '';
            de = option.getAttribute('data-email') || '';
            dco = option.getAttribute('data-company-name') || '';
        }
        // Fallback: use server-provided dataset if any field is empty
        if ((!dn || !ds || !dc || !da || !de || !dco) && window.CUSTOMERS_DATA && window.CUSTOMERS_DATA[customerId]) {
            const row = window.CUSTOMERS_DATA[customerId];
            dn = dn || (row.name || '');
            ds = ds || (row.surname || '');
            dc = dc || (row.cell || '');
            da = da || (row.address || '');
            de = de || (row.email_address || '');
            dco = dco || (row.company_name || row.name || '');
        }
        if (nameInput) nameInput.value = dn; else console.warn('customer_name input not found');
        if (surnameInput) surnameInput.value = ds; else console.warn('customer_surname input not found');
        if (cellInput) cellInput.value = dc; else console.warn('cell input not found');
        if (addressInput) addressInput.value = da; else console.warn('address input not found');
        if (emailInput) emailInput.value = de; else console.warn('email_address input not found');
        if (companyNameInput) companyNameInput.value = dco; else console.warn('company_name input not found');
        if (custIdInput) custIdInput.value = customerId;

        // Handle origin country and city population with proper timing
        populateOriginFromCustomer(customerId);
    }

    // Function to populate origin information from customer data
    function populateOriginFromCustomer(customerId) {
        // Get customer data to determine origin country
        const customerData = window.CUSTOMERS_DATA && window.CUSTOMERS_DATA[customerId];
        if (!customerData) {
            console.log('No customer data available for origin population');
            return;
        }

        // Check if customer has origin country information
        // This assumes the customer data includes origin_country_id or similar
        const originCountryId = customerData.origin_country_id || customerData.country_id || 1; // Default to 1 if not specified
        
        console.log('Setting origin country to:', originCountryId);
        
        // Set the origin country dropdown
        const originCountrySelect = document.getElementById('origin_country_select');
        if (originCountrySelect) {
            originCountrySelect.value = originCountryId;
            
            // Create a custom event to trigger city loading
            const changeEvent = new Event('change', { bubbles: true });
            originCountrySelect.dispatchEvent(changeEvent);
            
            // If handleCountryChange is available, use it; otherwise, implement our own city loading
            if (typeof handleCountryChange === 'function') {
                console.log('Using handleCountryChange for origin country:', originCountryId);
                handleCountryChange(originCountryId, 'origin');
            } else {
                console.log('Implementing custom city loading for origin country:', originCountryId);
                loadCitiesForCountry(originCountryId, 'origin');
            }
            
            // After cities are loaded, set the origin city if available
            setTimeout(() => {
                const originCityId = customerData.origin_city_id || customerData.city_id;
                if (originCityId) {
                    const originCitySelect = document.getElementById('origin_city_select') || document.getElementById('origin_city');
                    if (originCitySelect) {
                        originCitySelect.value = originCityId;
                        console.log('Set origin city to:', originCityId);
                    }
                }
            }, 1500); // Wait 1.5 seconds for cities to load
        } else {
            console.warn('Origin country select not found');
        }
    }

    // Custom function to load cities for a country (fallback if handleCountryChange is not available)
    function loadCitiesForCountry(countryId, fieldName) {
        if (!countryId) return;

        // Determine which city dropdown to update
        let citySelectId = '';
        if (fieldName === 'origin' || fieldName === 'origin_country') {
            citySelectId = 'origin_city_select';
        } else if (fieldName === 'destination' || fieldName === 'destination_country') {
            citySelectId = 'destination_city_select';
        } else {
            console.error('Unknown field name:', fieldName);
            return;
        }

        const citySelect = document.getElementById(citySelectId);
        if (!citySelect) {
            console.error('City select element not found:', citySelectId);
            return;
        }

        // Show loading state
        citySelect.innerHTML = '<option value="">Loading cities...</option>';
        citySelect.disabled = true;

        // Make AJAX request to get cities
        const ajaxUrl = window.ajaxurl || (window.myPluginAjax && window.myPluginAjax.ajax_url);
        const nonce = window.myPluginAjax && window.myPluginAjax.nonces ? window.myPluginAjax.nonces.get_waybills_nonce : '';

        if (ajaxUrl) {
            jQuery.ajax({
                url: ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'handle_get_cities_for_country',
                    country_id: countryId,
                    nonce: nonce
                },
                success: function(response) {
                    console.log('Cities response:', response);
                    if (response.success && Array.isArray(response.data)) {
                        citySelect.innerHTML = '<option value="">Select City</option>';
                        response.data.forEach(function(city) {
                            const option = document.createElement('option');
                            option.value = city.id;
                            option.textContent = city.city_name;
                            citySelect.appendChild(option);
                        });
                        console.log('Cities loaded successfully:', response.data.length, 'cities');
                    } else {
                        console.log('No cities found or invalid response');
                        citySelect.innerHTML = '<option value="">No cities found</option>';
                    }
                    citySelect.disabled = false;
                },
                error: function(xhr, status, error) {
                    console.error('Error loading cities:', error);
                    citySelect.innerHTML = '<option value="">Error loading cities</option>';
                    citySelect.disabled = false;
                }
            });
        } else {
            console.error('AJAX URL not available');
            citySelect.innerHTML = '<option value="">AJAX not available</option>';
            citySelect.disabled = false;
        }
    }

    // Function to clear customer fields
    function clearCustomerFields() {
        if (nameInput) nameInput.value = '';
        if (surnameInput) surnameInput.value = '';
        if (cellInput) cellInput.value = '';
        if (addressInput) addressInput.value = '';
        if (emailInput) emailInput.value = '';
        if (companyNameInput) companyNameInput.value = '';
        if (custIdInput) custIdInput.value = '0';
    }

    if (customerSelect) {
        customerSelect.addEventListener('change', function() {
            if (this.value === 'new') {
                clearCustomerFields();
            } else if (this.value) {
                populateCustomerDetails(this.value);
            }
        });

        // Initial population on page load (for edit mode)
        const initialCustomerId = custIdInput?.value;
        if (initialCustomerId && initialCustomerId !== '0') {
            populateCustomerDetails(initialCustomerId);
        }
    }

    // Delivery card highlight logic: click to select + visual highlight
    (function setupDeliverySelection(){
        function updateVisual() {
            const cards = document.querySelectorAll('.delivery-card');
            const checked = document.querySelector('input[name="delivery_id"]:checked');
            const checkedId = checked ? checked.value : null;
            cards.forEach(card => {
                const isActive = checkedId && card.getAttribute('data-delivery-id') === checkedId;
                card.style.borderColor = isActive ? '#2563eb' : '#d1d5db';
                card.style.boxShadow = isActive ? '0 0 0 2px rgba(37,99,235,0.25)' : 'none';
                card.style.backgroundColor = isActive ? '#eff6ff' : '#ffffff';
            });
        }
        // Delegated click handler so it works for dynamically injected cards
        document.addEventListener('click', function(e){
            const card = e.target.closest('.delivery-card');
            if (!card) return;
            const id = card.getAttribute('data-delivery-id');
            const radio = document.querySelector('input[name="delivery_id"][value="' + id + '"]');
            if (radio) { radio.checked = true; updateVisual(); }
        });
        document.addEventListener('change', function(e){
            if (e.target && e.target.name === 'delivery_id') {
                updateVisual();
            }
        });
        // Observe DOM mutations to re-apply highlight after AJAX renders new cards
        const observer = new MutationObserver(updateVisual);
        observer.observe(document.body, { childList: true, subtree: true });
        updateVisual();
    })();
});
</script>