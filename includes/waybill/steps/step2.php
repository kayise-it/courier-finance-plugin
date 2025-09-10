<?php if (!defined('ABSPATH')) {
    exit;
} ?>

<?= KIT_Commons::prettyHeading([
    'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
    'words' => 'Customer Information'
]) ?>
<?php $is_existing_customer = $atts['is_existing_customer'] ?>
<input type="hidden" id="cust_id" name="cust_id" value="<?php echo esc_attr($customer_id); ?>">

<?php $customers = KIT_Customers::tholaMaCustomer(); ?>
<div class="mb-6">
    <label for="customer-search" class="block text-sm font-semibold text-gray-700 mb-2">Select Customer</label>
    <div class="relative">
        <!-- Search Input -->
        <input 
            type="text" 
            id="customer-search" 
            name="customer_search" 
            placeholder="Type to search customers..."
            class="block w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition px-4 py-2 bg-white text-gray-800"
            autocomplete="off"
        >
        
        <!-- Hidden select for form submission -->
        <input type="hidden" id="customer-select" name="customer_select" value="<?php echo $customer_id ? $customer_id : 'new'; ?>">
        
        <!-- Dropdown Arrow -->
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </div>
        
        <!-- Search Results Dropdown -->
        <div id="customer-results" class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg hidden max-h-60 overflow-y-auto">
            <!-- Results will be populated by JavaScript -->
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="mt-2 flex flex-wrap gap-2">
        <button type="button" id="add-new-customer-btn" class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-full hover:bg-blue-200 transition">
            + Add New Customer
        </button>
        <button type="button" id="recent-customers-btn" class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-full hover:bg-gray-200 transition">
            Recent Customers
        </button>
    </div>
</div>

<div class="rounded-xl border border-gray-200 bg-gradient-to-br from-blue-50 to-white shadow-inner mb-8 overflow-hidden">
    <button type="button" class="customer-accordion-toggle w-full flex items-center justify-between px-6 py-4 bg-blue-100 hover:bg-blue-200 transition focus:outline-none focus:ring-2 focus:ring-blue-400 text-blue-800 font-semibold text-lg">
        <span>Customer Details</span>
        <svg class="w-5 h-5 transition-transform duration-200" id="customer-details-arrow" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path d="M19 9l-7 7-7-7" />
        </svg>
    </button>
    <div class="customer-details-content px-6 py-6 bg-white space-y-6 border-t border-gray-100" style="display: none;">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Row 1: Company Name & Customer Name -->
            <div>
                <?= KIT_Commons::Linput([
                    'label' => 'Company Name',
                    'name'  => 'company_name',
                    'id'    => 'company_name',
                    'type'  => 'text',
                    'value' => esc_attr($is_existing_customer ? $customer->company_name ?? $customer->name : ''),
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
                    'value' => esc_attr($is_existing_customer ? $customer->name : ''),
                    'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-gray-50 transition',
                    'special' => 'autocomplete="given-name"'
                ]); ?>
            </div>

            <!-- Row 2: Customer Surname & Cell -->
            <div>
                <?= KIT_Commons::Linput([
                    'label' => 'Customer Surname',
                    'name'  => 'customer_surname',
                    'id'    => 'customer_surname',
                    'type'  => 'text',
                    'value' => esc_attr($is_existing_customer ? $customer->surname : ''),
                    'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-gray-50 transition',
                    'special' => 'autocomplete="family-name"'
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

            <!-- Row 3: Email (full width) -->
            <div class="md:col-span-2">
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
            <!-- Row 4: Address (full width) -->
            <div class="md:col-span-2">
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
        </div>
        <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsOrigin.php'); ?>
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

<script>
    window.CUSTOMERS_DATA = window.CUSTOMERS_DATA || (function() {
        try {
            const customers = <?php echo json_encode($customers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
            // Convert array to object indexed by cust_id for easier lookup
            const customersObj = {};
            customers.forEach(customer => {
                customersObj[customer.cust_id] = customer;
            });
            return customersObj;
        } catch (e) {
            return {};
        }
    })();

    document.addEventListener('DOMContentLoaded', function() {
        // Accordion toggle for customer details
        const accordionBtn = document.querySelector('.customer-accordion-toggle');
        const detailsContent = document.querySelector('.customer-details-content');
        const arrowIcon = document.getElementById('customer-details-arrow');
        
        console.log('Accordion elements found:', { accordionBtn, detailsContent, arrowIcon });
        
        if (accordionBtn && detailsContent) {
            // Start collapsed on mobile, open on desktop
            if (window.innerWidth < 768) {
                detailsContent.style.display = 'none';
            } else {
                detailsContent.style.display = 'block';
            }
            
            accordionBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                console.log('Accordion clicked! Current display:', detailsContent.style.display);
                
                // Toggle the content visibility
                if (detailsContent.style.display === 'none') {
                    detailsContent.style.display = 'block';
                } else {
                    detailsContent.style.display = 'none';
                }
                
                // Rotate the arrow
                if (arrowIcon) {
                    arrowIcon.classList.toggle('rotate-180');
                }
                
                console.log('After toggle - display:', detailsContent.style.display);
            });
        } else {
            console.error('Accordion elements not found!', { accordionBtn, detailsContent });
        }

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
        const companyNameInput = getCustomerInput('company_name');

        // Search customers function
        function searchCustomers(query) {
            if (!query || query.length < 2) {
                customerResults.classList.add('hidden');
                return;
            }

            const results = [];
            const searchTerm = query.toLowerCase();

            // Search through customers data
            if (window.CUSTOMERS_DATA) {
                Object.values(window.CUSTOMERS_DATA).forEach(customer => {
                    const name = (customer.name || '').toLowerCase();
                    const surname = (customer.surname || '').toLowerCase();
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

            displaySearchResults(results.slice(0, 10)); // Limit to 10 results
        }

        // Display search results
        function displaySearchResults(results) {
            customerResults.innerHTML = '';
            
            if (results.length === 0) {
                customerResults.innerHTML = '<div class="px-4 py-2 text-gray-500 text-sm">No customers found</div>';
            } else {
                results.forEach(customer => {
                    const item = document.createElement('div');
                    item.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0';
                    item.innerHTML = `
                        <div class="font-medium text-gray-900">${customer.name} ${customer.surname}</div>
                        <div class="text-sm text-gray-500">${customer.company_name || customer.cell || ''}</div>
                    `;
                    item.addEventListener('click', () => selectCustomer(customer));
                    customerResults.appendChild(item);
                });
            }
            
            customerResults.classList.remove('hidden');
        }

        // Select customer function
        function selectCustomer(customer) {
            customerSearch.value = `${customer.name} ${customer.surname}`;
            customerSelect.value = customer.cust_id;
            custIdInput.value = customer.cust_id;
            customerResults.classList.add('hidden');
            
            // Populate customer details
            populateCustomerDetails(customer.cust_id);
        }

        // Add new customer function
        function addNewCustomer() {
            customerSearch.value = 'Add New Customer';
            customerSelect.value = 'new';
            custIdInput.value = '0';
            customerResults.classList.add('hidden');
            clearCustomerFields();
        }

        // Show recent customers
        function showRecentCustomers() {
            if (!window.CUSTOMERS_DATA) return;
            
            // Get last 10 customers (you can modify this logic)
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
            const dn = customer.name || '';
            const ds = customer.surname || '';
            const dc = customer.cell || '';
            const da = customer.address || '';
            const de = customer.email_address || '';
            const dco = customer.company_name || customer.name || '';

            if (nameInput) nameInput.value = dn;
            if (surnameInput) surnameInput.value = ds;
            if (cellInput) cellInput.value = dc;
            if (addressInput) addressInput.value = da;
            if (emailInput) emailInput.value = de;
            if (companyNameInput) companyNameInput.value = dco;
            if (custIdInput) custIdInput.value = customerId;
            
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
                // Set the country value
                originCountrySelect.value = originCountryId;

                // Trigger change event to load cities
                const changeEvent = new Event('change', {
                    bubbles: true
                });
                originCountrySelect.dispatchEvent(changeEvent);

                // Load cities for the selected country
                if (typeof handleCountryChange === 'function') {
                    handleCountryChange(originCountryId, 'origin');
                } else {
                    loadCitiesForCountry(originCountryId, 'origin', customerData.city_id);
                }
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

                            // Set the default city if provided
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
        }

        // Event listeners for new search interface
        if (customerSearch) {
            // Search input events
            customerSearch.addEventListener('input', function() {
                searchCustomers(this.value);
            });

            customerSearch.addEventListener('focus', function() {
                if (this.value.length >= 2) {
                    searchCustomers(this.value);
                }
            });

            // Hide results when clicking outside
            document.addEventListener('click', function(e) {
                if (!customerSearch.contains(e.target) && !customerResults.contains(e.target)) {
                    customerResults.classList.add('hidden');
                }
            });

            // Handle keyboard navigation
            customerSearch.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    customerResults.classList.add('hidden');
                }
            });
        }

        // Quick action buttons
        if (addNewCustomerBtn) {
            addNewCustomerBtn.addEventListener('click', addNewCustomer);
        }

        if (recentCustomersBtn) {
            recentCustomersBtn.addEventListener('click', showRecentCustomers);
        }

        // Initialize with existing customer if any
        const initialCustomerId = custIdInput?.value;
        if (initialCustomerId && initialCustomerId !== '0' && window.CUSTOMERS_DATA && window.CUSTOMERS_DATA[initialCustomerId]) {
            const customer = window.CUSTOMERS_DATA[initialCustomerId];
            customerSearch.value = `${customer.name} ${customer.surname}`;
            customerSelect.value = initialCustomerId;
            populateCustomerDetails(initialCustomerId);
        } else if (initialCustomerId === '0' || !initialCustomerId) {
            customerSearch.value = 'Add New Customer';
            customerSelect.value = 'new';
        }

        // Delivery card highlight logic (if used elsewhere)
        (function setupDeliverySelection() {
            function updateVisual() {
                const cards = document.querySelectorAll('.delivery-card');
                const checked = document.querySelector('input[name="delivery_id"]:checked');
                const checkedId = checked ? checked.value : null;
                cards.forEach(card => {
                    card.classList.toggle('ring-2', checkedId && card.getAttribute('data-delivery-id') === checkedId);
                    card.classList.toggle('ring-blue-500', checkedId && card.getAttribute('data-delivery-id') === checkedId);
                    card.classList.toggle('bg-blue-50', checkedId && card.getAttribute('data-delivery-id') === checkedId);
                    card.classList.toggle('border-blue-500', checkedId && card.getAttribute('data-delivery-id') === checkedId);
                    card.classList.toggle('border-gray-200', !(checkedId && card.getAttribute('data-delivery-id') === checkedId));
                    card.classList.toggle('bg-white', !(checkedId && card.getAttribute('data-delivery-id') === checkedId));
                });
            }
            document.addEventListener('click', function(e) {
                const card = e.target.closest('.delivery-card');
                if (!card) return;
                const id = card.getAttribute('data-delivery-id');
                const radio = document.querySelector('input[name="delivery_id"][value="' + id + '"]');
                if (radio) {
                    radio.checked = true;
                    updateVisual();
                }
            });
            document.addEventListener('change', function(e) {
                if (e.target && e.target.name === 'delivery_id') {
                    updateVisual();
                }
            });
            const observer = new MutationObserver(updateVisual);
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            updateVisual();
        })();
    });
</script>
<!-- End of Selection -->