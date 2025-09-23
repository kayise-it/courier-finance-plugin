<?php if (!defined('ABSPATH')) {
    exit;
} ?>

<?= KIT_Commons::prettyHeading([
    'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
    'words' => 'Customer Information'
]) ?>
<?php 
$is_existing_customer = $atts['is_existing_customer'];
// Initialize customer_id with a default value if not set
$customer_id = isset($customer_id) ? $customer_id : (isset($customer) && isset($customer->cust_id) ? $customer->cust_id : '');
?>
<input type="hidden" id="cust_id" name="cust_id" value="<?php echo esc_attr($customer_id); ?>">

<?php 
// Use customer data passed from shortcode if available, otherwise load from database
if (isset($customers_data) && is_array($customers_data)) {
    $customers = $customers_data;
} else {
    // Use the standalone function that returns properly aliased data
    $customers = tholaMaCustomer();
}
?>


<!-- Step 2: Customer Selection and Details - Symmetrical, Simple UI -->

<div class="max-w-3xl mx-auto">
    <div class="grid grid-cols-1 gap-8 mb-8">
        <!-- Left: Customer Search & Type -->
        <div class="flex flex-col gap-6">
            <!-- Customer Search -->
            <div>
                <label for="customer-search" class="block text-sm font-semibold text-gray-700 mb-2">Select Customer</label>
                <div class="relative">
                    <input 
                        type="text" 
                        id="customer-search" 
                        name="customer_search" 
                        placeholder="Type to search customers..."
                        class="block w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition px-4 py-2 bg-white text-gray-800"
                        autocomplete="off"
                    >
                    <input type="hidden" id="customer-select" name="customer_select" value="<?php echo !empty($customer_id) ? $customer_id : 'new'; ?>">
                    
                    <div id="customer-results" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto">
                        <!-- Results will be populated by JavaScript -->
                    </div>
                </div>
                <div class="mt-2 flex flex-wrap gap-2">
                    <button type="button" id="add-new-customer-btn" class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition">
                        + Add New Customer
                    </button>
                    <button type="button" id="recent-customers-btn" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition">
                        Recent Customers
                    </button>
                </div>
            </div>
            <!-- Client Type -->
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Is the client a business or an individual?</label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="client_type" id="client_type_business" value="business" class="client-type-radio" checked>
                        <span class="ml-2">Business</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="client_type" id="client_type_individual" value="individual" class="client-type-radio">
                        <span class="ml-2">Individual</span>
                    </label>
                </div>
            </div>
        </div>
        <!-- Right: Customer Details (always visible, no accordion) -->
        <div class="rounded-xl border border-gray-200 bg-gradient-to-br from-blue-50 to-white shadow-inner px-6 py-6">
            <div class="grid grid-cols-1 gap-4">
                <div id="company_name_wrapper">
                    <?= KIT_Commons::Linput([
                        'label' => 'Company Name',
                        'name'  => 'company_name',
                        'id'    => 'company_name',
                        'type'  => 'text',
                        'value' => esc_attr($is_existing_customer ? $customer->company_name ?? $customer->customer_name : ''),
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-gray-50 transition',
                        'special' => 'autocomplete="organization"'
                    ]); ?>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Customer Name',
                        'name'  => 'customer_name',
                        'id'    => 'customer_name',
                        'type'  => 'text',
                        'value' => esc_attr($is_existing_customer ? $customer->customer_name : ''),
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-gray-50 transition',
                        'special' => 'autocomplete="given-name"'
                    ]); ?>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Customer Surname',
                        'name'  => 'customer_surname',
                        'id'    => 'customer_surname',
                        'type'  => 'text',
                        'value' => esc_attr($is_existing_customer ? $customer->customer_surname : ''),
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-gray-50 transition',
                        'special' => 'autocomplete="family-name"'
                    ]); ?>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Telephone',
                        'name'  => 'telephone',
                        'id'    => 'telephone',
                        'type'  => 'tel',
                        'value' => esc_attr($is_existing_customer ? $customer->cell : ''),
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-gray-50 transition',
                        'special' => 'autocomplete="tel"'
                    ]); ?>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Cell',
                        'name'  => 'cell',
                        'id'    => 'cell',
                        'type'  => 'tel',
                        'value' => esc_attr($is_existing_customer ? $customer->cell : ''),
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-gray-50 transition',
                        'special' => 'autocomplete="tel"'
                    ]); ?>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Email',
                        'name'  => 'email_address',
                        'id'    => 'email_address',
                        'type'  => 'email',
                        'value' => esc_attr($is_existing_customer ? $customer->email_address : ''),
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-gray-50 transition',
                        'special' => 'autocomplete="email"'
                    ]); ?>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Address',
                        'name'  => 'address',
                        'id'    => 'address',
                        'type'  => 'text',
                        'value' => esc_attr($is_existing_customer ? $customer->address : ''),
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-gray-50 transition',
                        'special' => 'autocomplete="street-address"'
                    ]); ?>
                </div>
                <div>
                    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsOrigin.php'); ?>
                </div>
            </div>
        </div>
    </div>
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mt-8">
        <?php echo KIT_Commons::renderButton('Next: Waybill Items', 'primary', 'md', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />',
            'iconPosition' => 'right',
            'data-target' => 'step-3',
            'classes' => 'next-step w-full md:w-auto px-8 py-3 rounded-lg text-base font-semibold bg-gradient-to-r from-blue-500 to-blue-700 text-white shadow-md hover:from-blue-600 hover:to-blue-800 transition',
            'gradient' => true,
            'disabled' => "false"
        ]); ?>
    </div>
</div>

<script>
    window.CUSTOMERS_DATA = window.CUSTOMERS_DATA || (function() {
        try {
            const customers = <?php echo json_encode($customers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            const customersObj = {};
            if (customers && Array.isArray(customers)) {
                customers.forEach(customer => {
                    customersObj[customer.cust_id] = customer;
                });
            }
            return customersObj;
        } catch (e) {
            console.error('Error loading customers:', e);
            return {};
        }
    })();

    document.addEventListener('DOMContentLoaded', function() {
        // Client type radio logic
        const clientTypeRadios = document.querySelectorAll('.client-type-radio');
        const companyNameWrapper = document.getElementById('company_name_wrapper');
        const companyNameInput = document.getElementById('company_name');

        function updateCompanyNameVisibility() {
            const selectedType = document.querySelector('input[name="client_type"]:checked')?.value;
            if (selectedType === 'individual') {
                if (companyNameWrapper) companyNameWrapper.style.display = 'none';
                if (companyNameInput) companyNameInput.value = '1ndividual';
            } else {
                if (companyNameWrapper) companyNameWrapper.style.display = '';
            }
        }

        clientTypeRadios.forEach(function(radio) {
            radio.addEventListener('change', updateCompanyNameVisibility);
        });
        updateCompanyNameVisibility();

        // Customer selection logic
        const customerSearch = document.getElementById('customer-search');
        const customerSelect = document.getElementById('customer-select');
        const customerResults = document.getElementById('customer-results');
        const custIdInput = document.getElementById('cust_id');
        const addNewCustomerBtn = document.getElementById('add-new-customer-btn');
        const recentCustomersBtn = document.getElementById('recent-customers-btn');

        function getCustomerInput(idBase) {
            return document.getElementById(idBase) ||
                document.getElementById('a' + idBase) ||
                document.getElementById('a_' + idBase) ||
                document.querySelector('[name="' + idBase + '"]');
        }
        const nameInput = getCustomerInput('customer_name');
        const surnameInput = getCustomerInput('customer_surname');
        const cellInput = getCustomerInput('cell');
        const addressInput = getCustomerInput('address');
        const emailInput = getCustomerInput('email_address');

        function searchCustomers(query) {
            
            if (!query || query.length < 2) {
                customerResults.classList.add('hidden');
                return;
            }
            const results = [];
            const searchTerm = query.toLowerCase();
            
            if (window.CUSTOMERS_DATA) {
                Object.values(window.CUSTOMERS_DATA).forEach(customer => {
                    const name = (customer.customer_name || '').toLowerCase();
                    const surname = (customer.customer_surname || '').toLowerCase();
                    const company = (customer.company_name || '').toLowerCase();
                    const cell = (customer.cell || '').toLowerCase();
                    const email = (customer.email_address || '').toLowerCase();
                    
                    
                    if (name.includes(searchTerm) || 
                        surname.includes(searchTerm) || 
                        company.includes(searchTerm) ||
                        cell.includes(searchTerm) ||
                        email.includes(searchTerm) ||
                        `${name} ${surname}`.includes(searchTerm)) {
                        results.push(customer);
                    }
                });
            }
            displaySearchResults(results.slice(0, 10));
        }

        function displaySearchResults(results) {
            customerResults.innerHTML = '';
            if (results.length === 0) {
                customerResults.innerHTML = '<div class="px-4 py-2 text-gray-500 text-sm">No customers found</div>';
            } else {
                results.forEach(customer => {
                    const item = document.createElement('div');
                    item.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0';
                    item.innerHTML = `
                        <div class="font-medium text-gray-900">${customer.customer_name} ${customer.customer_surname}</div>
                        <div class="text-sm text-gray-500">${customer.company_name || customer.cell || ''}</div>
                    `;
                    item.addEventListener('click', () => selectCustomer(customer));
                    customerResults.appendChild(item);
                });
            }
            customerResults.classList.remove('hidden');
        }

        function selectCustomer(customer) {
            customerSearch.value = `${customer.customer_name} ${customer.customer_surname}`;
            customerSelect.value = customer.cust_id;
            custIdInput.value = customer.cust_id;
            customerResults.classList.add('hidden');
            populateCustomerDetails(customer.cust_id);
        }

        function addNewCustomer() {
            customerSearch.value = '';
            customerSelect.value = 'new';
            custIdInput.value = '0';
            customerResults.classList.add('hidden');
            clearCustomerFields();
        }

        function showRecentCustomers() {
            if (!window.CUSTOMERS_DATA) return;
            const recentCustomers = Object.values(window.CUSTOMERS_DATA)
                .sort((a, b) => (b.created_at || '').localeCompare(a.created_at || ''))
                .slice(0, 10);
            displaySearchResults(recentCustomers);
        }

        function populateCustomerDetails(customerId) {
            if (!window.CUSTOMERS_DATA || !window.CUSTOMERS_DATA[customerId]) {
                return;
            }
            const customer = window.CUSTOMERS_DATA[customerId];
            const dn = customer.customer_name || '';
            const ds = customer.customer_surname || '';
            const dc = customer.cell || '';
            const da = customer.address || '';
            const de = customer.email_address || '';
            const dco = customer.company_name || customer.customer_name || '';
            if (nameInput) nameInput.value = dn;
            if (surnameInput) surnameInput.value = ds;
            if (cellInput) cellInput.value = dc;
            if (addressInput) addressInput.value = da;
            if (emailInput) emailInput.value = de;
            if (companyNameInput) companyNameInput.value = dco;
            if (custIdInput) custIdInput.value = customerId;
            updateCompanyNameVisibility();
            populateOriginFromCustomer(customerId);
        }

        function populateOriginFromCustomer(customerId) {
            if (!window.CUSTOMERS_DATA || !window.CUSTOMERS_DATA[customerId]) {
                return;
            }
            const customer = window.CUSTOMERS_DATA[customerId];
            const customerData = {
                country_id: customer.country_id || '',
                city_id: customer.city_id || ''
            };
            const originCountryId = customerData.country_id || 1;
            const originCountrySelect = document.getElementById('origin_country_select');
            if (originCountrySelect) {
                originCountrySelect.value = originCountryId;
                // Always use our custom function to ensure city gets set properly
                loadCitiesForCountry(originCountryId, 'origin', customerData.city_id);
            }
        }

        function loadCitiesForCountry(countryId, fieldName, defaultCityId) {
            if (!countryId) return;
            let citySelectId = '';
            if (fieldName === 'origin' || fieldName === 'origin_country') {
                citySelectId = 'origin_city_select';
            } else if (fieldName === 'destination' || fieldName === 'destination_country') {
                citySelectId = 'destination_city_select';
            } else {
                return;
            }
            const citySelect = document.getElementById(citySelectId);
            if (!citySelect) return;
            citySelect.innerHTML = '<option value="">Loading cities...</option>';
            citySelect.disabled = true;
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
                        if (response.success && Array.isArray(response.data)) {
                            citySelect.innerHTML = '<option value="">Select City</option>';
                            response.data.forEach(function(city) {
                                const option = document.createElement('option');
                                option.value = city.id;
                                option.textContent = city.city_name;
                                citySelect.appendChild(option);
                            });
                            if (defaultCityId) {
                                citySelect.value = defaultCityId;
                            }
                        } else {
                            citySelect.innerHTML = '<option value="">No cities found</option>';
                        }
                        citySelect.disabled = false;
                    },
                    error: function() {
                        citySelect.innerHTML = '<option value="">Error loading cities</option>';
                        citySelect.disabled = false;
                    }
                });
            } else {
                citySelect.innerHTML = '<option value="">AJAX not available</option>';
                citySelect.disabled = false;
            }
        }

        function clearCustomerFields() {
            if (nameInput) nameInput.value = '';
            if (surnameInput) surnameInput.value = '';
            if (cellInput) cellInput.value = '';
            if (addressInput) addressInput.value = '';
            if (emailInput) emailInput.value = '';
            if (companyNameInput) companyNameInput.value = '';
            if (custIdInput) custIdInput.value = '0';
            const businessRadio = document.getElementById('client_type_business');
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
                if (!customerSearch.contains(e.target) && !customerResults.contains(e.target)) {
                    customerResults.classList.add('hidden');
                }
            });
            customerSearch.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    customerResults.classList.add('hidden');
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
        if (initialCustomerId && initialCustomerId !== '0' && window.CUSTOMERS_DATA && window.CUSTOMERS_DATA[initialCustomerId]) {
            const customer = window.CUSTOMERS_DATA[initialCustomerId];
            customerSearch.value = `${customer.customer_name} ${customer.customer_surname}`;
            customerSelect.value = initialCustomerId;
            populateCustomerDetails(initialCustomerId);
        } else if (initialCustomerId === '0' || !initialCustomerId) {
            customerSearch.value = '';
            customerSelect.value = 'new';
        }
    });
</script>
<!-- End of Selection -->