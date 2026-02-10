<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?= KIT_Commons::prettyHeading([
    'icon' => '<path d="M16 7a4 4 0 1 0-8 0v2a4 4 0 0 0 8 0V7z" /><path d="M12 19v-2m0 0a7 7 0 0 1-7-7V7a7 7 0 0 1 14 0v3a7 7 0 0 1-7 7z" />',
    'words' => 'Customer Information'
]) ?>
<?php
$is_existing_customer = $atts['is_existing_customer'] ?? false;
// Initialize customer_id with a default value if not set
$customer_id = isset($customer_id) ? $customer_id : (isset($customer) && isset($customer->cust_id) ? $customer->cust_id : '');
?>
<input type="hidden" id="cust_id" name="cust_id" value="<?php echo esc_attr($customer_id); ?>">

<?php
// Use customer data passed from shortcode if available and non-empty, otherwise load from database.
// This ensures frontend portal (which may pass an empty customers_data payload) still has full customer data.
if (isset($customers_data) && is_array($customers_data) && !empty($customers_data)) {
    $customers = $customers_data;
} else {
    // Use the standalone function that returns properly aliased data
    $customers = tholaMaCustomer();
}

// If we still have no customers, fall back to the last waybill's customer only.
if (empty($customers)
    && class_exists('KIT_Dashboard')
    && method_exists('KIT_Dashboard', 'get_last_waybill_customer_id')
    && function_exists('get_customer_details')
) {
    $last_cust_id = (int) KIT_Dashboard::get_last_waybill_customer_id();
    if ($last_cust_id > 0) {
        $last_customer = get_customer_details($last_cust_id);
        if ($last_customer) {
            // Normalise to an array of objects so JSON encoding works the same way
            if (!is_array($last_customer)) {
                $customers = [$last_customer];
            } else {
                $customers = [$last_customer];
            }
        }
    }
}
?>

<style>
    /* Smooth transitions for progressive disclosure */
    #customer-details-form {
        max-height: 0;
        overflow: hidden;
        opacity: 0;
        transition: max-height 0.4s ease-out, opacity 0.3s ease-out, padding 0.3s ease-out;
    }
    
    #customer-details-form.show {
        max-height: 3000px;
        opacity: 1;
        padding-top: 1.5rem;
    }
    
    /* Enhanced search input */
    #customer-search {
        transition: all 0.2s ease;
    }
    
    #customer-search:focus {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }
    
    /* Selected customer badge */
    .selected-customer-badge {
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Improved autocomplete dropdown */
    #customer-results {
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    #customer-results .customer-result-item {
        transition: background-color 0.15s ease;
    }
    
    #customer-results .customer-result-item:hover {
        background-color: #f3f4f6;
    }
    
    /* Field group styling */
    .field-group {
        background-color: #f9fafb;
        border-left: 3px solid #3b82f6;
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 0.375rem;
    }
    
    .field-group-title {
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    /* Required field indicator */
    .required-field::after {
        content: ' *';
        color: #ef4444;
        font-weight: 600;
    }
    
    .required-asterisk {
        color: #ef4444;
        font-weight: 600;
    }
</style>

<div class="w-full mx-auto">
    <!-- Section 1: Customer Search (Always Visible) -->
    <div class="mb-6 pb-6 border-b border-gray-200">
        <!-- Customer Search -->
        <div class="mb-4">
            <label for="customer-search" class="block text-sm font-semibold text-gray-700 mb-3">
                <svg class="inline-block w-4 h-4 mr-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                Select Customer
            </label>
            <div class="relative">
                <input
                    type="text"
                    id="customer-search"
                    name="customer_search"
                    placeholder="Type to search customers..."
                    class="block w-full rounded-lg border-2 border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all px-4 py-3 bg-white text-gray-800 text-base"
                    autocomplete="off">
                <input type="hidden" id="customer-select" name="customer_select" value="<?php echo !empty($customer_id) ? $customer_id : 'new'; ?>">

                <div id="customer-results" class="absolute z-50 w-full mt-2 bg-white border border-gray-300 rounded-lg shadow-xl hidden max-h-60 overflow-y-auto">
                    <!-- Results will be populated by JavaScript -->
                </div>
            </div>
            
            <!-- Quick Action Buttons -->
            <div class="mt-3 flex flex-wrap gap-2">
                <?php echo KIT_Commons::renderButton('Add New Customer', 'primary', 'lg', ['type' => 'button', 'id' => 'add-new-customer-btn', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />', 'iconPosition' => 'left']); ?>
                <?php echo KIT_Commons::renderButton('Recent Customers', 'secondary', 'lg', ['type' => 'button', 'id' => 'recent-customers-btn', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />', 'iconPosition' => 'left']); ?>
            </div>
        </div>
        
        <!-- Selected Customer Badge (shown when customer is selected) -->
        <div id="selected-customer-badge" class="hidden selected-customer-badge mb-4">
            <div class="inline-flex items-center px-3 py-1.5 rounded-full bg-blue-50 border border-blue-200 text-sm">
                <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="font-medium text-blue-900">Selected: </span>
                <span id="selected-customer-name" class="text-blue-700"></span>
            </div>
        </div>
        
        <!-- Client Type Selection -->
        <div class="mt-4">
            <label class="block text-sm font-semibold text-gray-700 mb-3">
                <svg class="inline-block w-4 h-4 mr-1 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Client Type
            </label>
            <div class="flex gap-6">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="radio" name="client_type" id="client_type_business" value="business" class="client-type-radio w-4 h-4 text-blue-600 focus:ring-blue-500" checked>
                    <span class="ml-2 text-gray-700 font-medium">Business</span>
                </label>
                <label class="inline-flex items-center cursor-pointer">
                    <input type="radio" name="client_type" id="client_type_individual" value="individual" class="client-type-radio w-4 h-4 text-blue-600 focus:ring-blue-500">
                    <span class="ml-2 text-gray-700 font-medium">Individual</span>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Section 2: Customer Details Form (Conditionally Visible) -->
    <div id="customer-details-form" class="<?php echo ($is_existing_customer && !empty($customer_id)) ? 'show' : ''; ?>">
        <!-- Personal Information Group -->
        <div class="field-group">
            <div class="field-group-title">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Personal Information
            </div>
            <div class="space-y-4">
                <div id="company_name_wrapper">
                    <?= KIT_Commons::Linput([
                        'label' => 'Company Name',
                        'name'  => 'company_name',
                        'id'    => 'company_name',
                        'type'  => 'text',
                        'value' => esc_attr($is_existing_customer ? $customer->company_name ?? $customer->customer_name : ''),
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-gray-800 bg-white transition',
                        'special' => 'autocomplete="organization"'
                    ]); ?>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <?= KIT_Commons::Linput([
                            'label' => 'Customer Name',
                            'name'  => 'customer_name',
                            'id'    => 'customer_name',
                            'type'  => 'text',
                            'value' => esc_attr($is_existing_customer ? $customer->customer_name : ''),
                            'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-gray-800 bg-white transition',
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
                            'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-gray-800 bg-white transition',
                            'special' => 'autocomplete="family-name"'
                        ]); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Contact Information Group -->
        <div class="field-group">
            <div class="field-group-title">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
                Contact Information
            </div>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <?= KIT_Commons::Linput([
                            'label' => 'Cell',
                            'name'  => 'cell',
                            'id'    => 'cell',
                            'type'  => 'tel',
                            'value' => esc_attr($is_existing_customer ? $customer->cell : ''),
                            'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-gray-800 bg-white transition',
                            'special' => 'autocomplete="tel" required'
                        ]); ?>
                    </div>
                    <div>
                        <?= KIT_Commons::Linput([
                            'label' => 'Telephone',
                            'name'  => 'telephone',
                            'id'    => 'telephone',
                            'type'  => 'tel',
                            'value' => esc_attr($is_existing_customer ? $customer->cell : ''),
                            'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-gray-800 bg-white transition',
                            'special' => 'autocomplete="tel"'
                        ]); ?>
                    </div>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Email',
                        'name'  => 'email_address',
                        'id'    => 'email_address',
                        'type'  => 'email',
                        'value' => esc_attr($is_existing_customer ? $customer->email_address : ''),
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-gray-800 bg-white transition',
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
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-gray-800 bg-white transition',
                        'special' => 'autocomplete="street-address" required'
                    ]); ?>
                </div>
                <div>
                    <?= KIT_Commons::Linput([
                        'label' => 'Notes (optional)',
                        'name'  => 'customer_notes',
                        'id'    => 'customer_notes',
                        'type'  => 'text',
                        'value' => '',
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-3 text-gray-800 bg-white transition',
                    ]); ?>
                </div>
            </div>
        </div>
        
        <!-- Location Information Group -->
        <div class="field-group">
            <div class="field-group-title">
                <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Origin Location
            </div>
            <div>
                <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsOrigin.php'); ?>
            </div>
        </div>
    </div>
    
    <!-- Summary Bar -->
    <div class="sticky top-16 z-10 mt-6 rounded-lg border border-gray-200 bg-white/90 backdrop-blur px-4 py-3 shadow-sm">
        <div class="flex flex-wrap gap-3 text-sm text-gray-700">
            <div><span class="font-semibold">Waybill #:</span> <?php echo isset($waybill_no) ? esc_html($waybill_no) : (isset($_POST['waybill_no']) ? esc_html($_POST['waybill_no']) : '—'); ?></div>
            <div><span class="font-semibold">Customer:</span> <?php echo $is_existing_customer ? esc_html(trim(($customer->company_name ?? $customer->customer_name ?? '') . ' ' . ($customer->customer_surname ?? ''))) : '—'; ?></div>
            <div><span class="font-semibold">Origin:</span> <span id="summary-origin">—</span></div>
            <div><span class="font-semibold">Items:</span> <span id="summary-items">0</span></div>
        </div>
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
        const customerDetailsForm = document.getElementById('customer-details-form');
        const selectedCustomerBadge = document.getElementById('selected-customer-badge');
        const selectedCustomerName = document.getElementById('selected-customer-name');
        
        // Client type radio logic
        const clientTypeRadios = document.querySelectorAll('.client-type-radio');
        const companyNameWrapper = document.getElementById('company_name_wrapper');
        const companyNameInput = document.getElementById('company_name');

        function updateCompanyNameVisibility() {
            const selectedType = document.querySelector('input[name="client_type"]:checked')?.value;
            if (selectedType === 'individual') {
                if (companyNameWrapper) {
                    companyNameWrapper.style.display = 'none';
                    companyNameInput.value = '';
                }
            } else {
                if (companyNameWrapper) {
                    companyNameWrapper.style.display = 'block';
                }
            }
        }

        function showCustomerForm() {
            if (customerDetailsForm) {
                customerDetailsForm.classList.add('show');
            }
        }

        function hideCustomerForm() {
            if (customerDetailsForm) {
                customerDetailsForm.classList.remove('show');
            }
        }

        function showSelectedCustomerBadge(customerName) {
            if (selectedCustomerBadge && selectedCustomerName) {
                selectedCustomerName.textContent = customerName;
                selectedCustomerBadge.classList.remove('hidden');
            }
        }

        function hideSelectedCustomerBadge() {
            if (selectedCustomerBadge) {
                selectedCustomerBadge.classList.add('hidden');
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

        // #region agent log: recent customers init (hypotheses A,B)
        try {
            fetch('http://127.0.0.1:7243/ingest/eac88981-a808-4140-9871-c5bc5fb2b15c', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    sessionId: 'debug-session',
                    runId: 'recent-customers-1',
                    hypothesisId: 'A',
                    location: 'customerSelection.php:init',
                    message: 'Recent customers init',
                    data: {
                        hasButton: !!recentCustomersBtn,
                        customersDataType: typeof window.CUSTOMERS_DATA,
                        customersCount: window.CUSTOMERS_DATA ? Object.keys(window.CUSTOMERS_DATA).length : 0
                    },
                    timestamp: Date.now()
                })
            }).catch(() => {});
        } catch (e) {}
        // #endregion

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
                customerResults.innerHTML = '<div class="px-4 py-3 text-gray-500 text-sm text-center">No customers found</div>';
            } else {
                results.forEach(customer => {
                    const item = document.createElement('div');
                    item.className = 'customer-result-item px-4 py-3 cursor-pointer border-b border-gray-100 last:border-b-0';
                    const displayName = `${customer.customer_name || ''} ${customer.customer_surname || ''}`.trim();
                    const secondaryInfo = customer.company_name || customer.cell || customer.email_address || '';
                    item.innerHTML = `
                        <div class="font-medium text-gray-900">${displayName}</div>
                        <div class="text-sm text-gray-500 mt-1">${secondaryInfo}</div>
                    `;
                    item.addEventListener('click', () => selectCustomer(customer));
                    customerResults.appendChild(item);
                });
            }
            customerResults.classList.remove('hidden');
        }

        function selectCustomer(customer) {
            const displayName = `${customer.customer_name || ''} ${customer.customer_surname || ''}`.trim();
            customerSearch.value = displayName;
            customerSelect.value = customer.cust_id;
            custIdInput.value = customer.cust_id;
            customerResults.classList.add('hidden');
            showCustomerForm();
            showSelectedCustomerBadge(displayName);
            populateCustomerDetails(customer.cust_id);
        }

        function addNewCustomer() {
            customerSearch.value = '';
            customerSelect.value = 'new';
            custIdInput.value = '0';
            customerResults.classList.add('hidden');
            showCustomerForm();
            hideSelectedCustomerBadge();
            clearCustomerFields();
        }

        function showRecentCustomers() {
            // #region agent log: showRecentCustomers entry (hypotheses A,B,C)
            try {
                const formForLog = document.getElementById('multi-step-waybill-form') || document.querySelector('form[data-last-waybill-customer-id]');
                const lastCustIdLog = formForLog ? parseInt(formForLog.getAttribute('data-last-waybill-customer-id') || '0', 10) : 0;
                fetch('http://127.0.0.1:7243/ingest/eac88981-a808-4140-9871-c5bc5fb2b15c', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        sessionId: 'debug-session',
                        runId: 'recent-customers-1',
                        hypothesisId: 'B',
                        location: 'customerSelection.php:showRecentCustomers',
                        message: 'showRecentCustomers called',
                        data: {
                            hasCustomersData: !!window.CUSTOMERS_DATA,
                            customersCount: window.CUSTOMERS_DATA ? Object.keys(window.CUSTOMERS_DATA).length : 0,
                            lastCustId: lastCustIdLog
                        },
                        timestamp: Date.now()
                    })
                }).catch(() => {});
            } catch (e) {}
            // #endregion

            // "Recent Customer" = load last waybill's client into the form if available
            const form = document.getElementById('multi-step-waybill-form') || document.querySelector('form[data-last-waybill-customer-id]');
            const lastCustId = form ? parseInt(form.getAttribute('data-last-waybill-customer-id'), 10) : 0;
            if (lastCustId && window.CUSTOMERS_DATA && window.CUSTOMERS_DATA[lastCustId]) {
                // #region agent log: select last customer (hypothesis C)
                try {
                    fetch('http://127.0.0.1:7243/ingest/eac88981-a808-4140-9871-c5bc5fb2b15c', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            sessionId: 'debug-session',
                            runId: 'recent-customers-1',
                            hypothesisId: 'C',
                            location: 'customerSelection.php:showRecentCustomers',
                            message: 'Selecting last waybill customer',
                            data: { lastCustId: lastCustId },
                            timestamp: Date.now()
                        })
                    }).catch(() => {});
                } catch (e) {}
                // #endregion
                selectCustomer(window.CUSTOMERS_DATA[lastCustId]);
                return;
            }
            // Fallback: show list of recent customers (by created_at)
            if (!window.CUSTOMERS_DATA) return;
            const recentCustomers = Object.values(window.CUSTOMERS_DATA)
                .sort((a, b) => (b.created_at || '').localeCompare(a.created_at || ''))
                .slice(0, 10);
            // #region agent log: fallback recent customers list (hypothesis C)
            try {
                fetch('http://127.0.0.1:7243/ingest/eac88981-a808-4140-9871-c5bc5fb2b15c', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        sessionId: 'debug-session',
                        runId: 'recent-customers-1',
                        hypothesisId: 'C',
                        location: 'customerSelection.php:showRecentCustomers',
                        message: 'Showing recent customers list',
                        data: { recentCount: recentCustomers.length },
                        timestamp: Date.now()
                    })
                }).catch(() => {});
            } catch (e) {}
            // #endregion
            if (!recentCustomers.length) {
                // No previous customers exist yet – make this explicit to the user
                alert('No recent customers found. Please add a customer first.');
            }
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
        
        // Initialize form visibility based on existing customer
        const initialCustomerId = custIdInput?.value;
        if (initialCustomerId && initialCustomerId !== '0' && window.CUSTOMERS_DATA && window.CUSTOMERS_DATA[initialCustomerId]) {
            const customer = window.CUSTOMERS_DATA[initialCustomerId];
            const displayName = `${customer.customer_name || ''} ${customer.customer_surname || ''}`.trim();
            customerSearch.value = displayName;
            customerSelect.value = initialCustomerId;
            showCustomerForm();
            showSelectedCustomerBadge(displayName);
            populateCustomerDetails(initialCustomerId);
        } else if (initialCustomerId === '0' || !initialCustomerId) {
            customerSearch.value = '';
            customerSelect.value = 'new';
            hideCustomerForm();
            hideSelectedCustomerBadge();
        }
        
        // Add required field indicators (red asterisks)
        function addRequiredIndicators() {
            // Find all required inputs within the customer details form
            const requiredInputs = customerDetailsForm?.querySelectorAll('input[required], textarea[required], select[required]') || [];
            requiredInputs.forEach(input => {
                const inputId = input.id || input.name;
                if (!inputId) return;
                
                // Try multiple methods to find the label
                let label = document.querySelector(`label[for="${inputId}"]`);
                if (!label) {
                    // Try finding label that contains the input
                    label = input.closest('div')?.querySelector('label');
                }
                if (!label) {
                    // Try finding by parent structure (common pattern in Linput)
                    const parentDiv = input.closest('div');
                    if (parentDiv) {
                        label = parentDiv.previousElementSibling?.tagName === 'LABEL' ? parentDiv.previousElementSibling : null;
                    }
                }
                
                if (label && !label.querySelector('.required-asterisk')) {
                    const asterisk = document.createElement('span');
                    asterisk.className = 'required-asterisk text-red-500 ml-1';
                    asterisk.textContent = '*';
                    label.appendChild(asterisk);
                }
            });
        }
        
        // Add required indicators after form is shown
        setTimeout(addRequiredIndicators, 200);
        
        // Re-add indicators when form is shown
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                    if (customerDetailsForm.classList.contains('show')) {
                        setTimeout(addRequiredIndicators, 200);
                    }
                }
            });
        });
        if (customerDetailsForm) {
            observer.observe(customerDetailsForm, { attributes: true });
        }
    });
</script>
<!-- End of Selection -->
