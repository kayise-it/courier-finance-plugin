<!-- Customer Information -->
<?php $is_existing_customer = $atts['is_existing_customer'] ?>
<!-- Hidden field to store customer ID -->
<input type="hidden" id="cust_id" name="cust_id" value="<?php echo esc_attr($customer_id); ?>">

<!-- Customer Search & Selection -->
<div class="mb-6">
    <!-- New Customer Toggle -->
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Customer Selection</h3>
        <div class="flex items-center space-x-3">
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="new-customer-toggle" class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                <span class="ml-3 text-sm font-medium text-gray-700">New Customer</span>
            </label>
        </div>
    </div>

    <!-- Customer Selection (Hidden when new customer is selected) -->
    <div id="customer-search-container" class="mb-4">
        <label for="customer-select" class="block text-sm font-medium text-gray-700 mb-2">Select Customer</label>
        <div class="relative">
            <select id="customer-select" 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm bg-white">
                <option value="">Loading customers...</option>
            </select>
        </div>
        
        <!-- Selected Customer Display -->
        <div id="selected-customer-display" class="hidden mt-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <div class="font-medium text-blue-900" id="selected-customer-name"></div>
                    <div class="text-sm text-blue-700" id="selected-customer-details"></div>
                </div>
                <button type="button" id="clear-customer-selection" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    Change
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Customer Details Form (Only shown for new customers) -->
<div id="customer-details-form" class="hidden bg-gray-50 rounded-lg border border-gray-200 overflow-hidden mb-6">
    <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-200">
        <div class="flex items-center space-x-3">
            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <div>
                <h4 class="text-sm font-semibold text-green-900">New Customer Details</h4>
                <p class="text-xs text-green-700">Fill in the information for the new customer</p>
            </div>
        </div>
    </div>

    <div class="px-6 py-6 bg-white space-y-6">
        <!-- Company Name (Full Width) -->
        <div>
            <?= KIT_Commons::Linput([
                'label' => 'Company Name',
                'name'  => 'company_name',
                'id'    => 'company_name',
                'type'  => 'text',
                'value' => esc_attr($is_existing_customer ? ($customer->company_name ?? '') : ''),
                'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm',
                'special' => 'placeholder="Enter company name..."'
            ]); ?>
        </div>

        <!-- Name and Surname (Two columns) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <?= KIT_Commons::Linput([
                    'label' => 'First Name *',
                    'name'  => 'customer_name',
                    'id'    => 'customer_name',
                    'type'  => 'text',
                    'value' => esc_attr($is_existing_customer ? $customer->name : ''),
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm',
                    'special' => 'placeholder="First name" data-step-required="2"'
                ]); ?>
            </div>
            <div>
                <?= KIT_Commons::Linput([
                    'label' => 'Last Name *',
                    'name'  => 'customer_surname',
                    'id'    => 'customer_surname',
                    'type'  => 'text',
                    'value' => esc_attr($is_existing_customer ? $customer->surname : ''),
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm',
                    'special' => 'placeholder="Last name" data-step-required="2"'
                ]); ?>
            </div>
        </div>

        <!-- Contact Information (Two columns) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <?= KIT_Commons::Linput([
                    'label' => 'Phone Number *',
                    'name'  => 'cell',
                    'id'    => 'cell',
                    'type'  => 'tel',
                    'value' => esc_attr($is_existing_customer ? $customer->cell : ''),
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm',
                    'special' => 'placeholder="+27 xxx xxx xxx" data-step-required="2"'
                ]); ?>
            </div>
            <div>
                <?= KIT_Commons::Linput([
                    'label' => 'Email Address',
                    'name'  => 'email_address',
                    'id'    => 'email_address',
                    'type'  => 'email',
                    'value' => esc_attr($is_existing_customer ? $customer->email_address : ''),
                    'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm',
                    'special' => 'placeholder="email@example.com"'
                ]); ?>
            </div>
        </div>

        <!-- Address (Full width) -->
        <div>
            <?= KIT_Commons::Linput([
                'label' => 'Address',
                'name'  => 'address',
                'id'    => 'address',
                'type'  => 'text',
                'value' => esc_attr($is_existing_customer ? $customer->address : ''),
                'class' => 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm',
                'special' => 'placeholder="Street address, city, postal code"'
            ]); ?>
        </div>
    </div>
</div>

<!-- Origin Location -->
<style>
    /* Style the generated select elements */
    #origin_country_select, #origin_city_select {
        width: 100% !important;
        padding: 12px 16px 12px 40px !important;
        border: 1px solid #d1d5db !important;
        border-radius: 8px !important;
        font-size: 14px !important;
        background-color: white !important;
        outline: none !important;
        transition: all 0.2s !important;
    }
    
    #origin_country_select:focus, #origin_city_select:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1) !important;
    }
</style>

<div class="bg-white rounded-lg border border-gray-200 p-6">
    <div class="flex items-center space-x-3 mb-6">
        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        </div>
        <div>
            <h4 class="text-lg font-semibold text-gray-900">Origin Location</h4>
            <p class="text-sm text-gray-600">Select where the shipment will be collected</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Origin Country -->
        <div>
            <label for="origin_country_select" class="block text-sm font-medium text-gray-700 mb-2">Origin Country *</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                    </svg>
                </div>
                <?php if (isset($waybillFromStats['country_id'])) : ?>
                    <div class="relative">
                        <?php echo KIT_Deliveries::selectAllCountries('origin_country', 'origin_country_select', $waybillFromStats['country_id'], $required = "required", 'origin'); ?>
                    </div>
                <?php else: ?>
                    <div class="relative">
                        <?php echo KIT_Deliveries::selectAllCountries('origin_country', 'origin_country_select', 1, "required", 'origin'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Origin City -->
        <div>
            <label for="origin_city_select" class="block text-sm font-medium text-gray-700 mb-2">Origin City *</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <?php if (isset($waybillFromStats['country_id'])) : ?>
                    <?php
                    $delData = KIT_Deliveries::get_delivery($waybill['delivery_id']);
                    $cityData = KIT_Deliveries::getCityData($delData->origin_country_id);
                    ?>
                    <div class="relative">
                        <?php echo KIT_Deliveries::selectAllCitiesByCountry('origin_city', 'origin_city_select', $waybillFromStats['country_id'], $cityData->id); ?>
                    </div>
                <?php else: ?>
                    <div class="relative">
                        <?php echo KIT_Deliveries::selectAllCitiesByCountry('origin_city', 'origin_city_select', 1, 1); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Origin Location Visual Indicator -->
    <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
        <div class="flex items-start space-x-3">
            <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="text-sm font-medium text-blue-900">Collection Point</p>
                <p class="text-sm text-blue-700">This is where our courier will collect the shipment from.</p>
            </div>
        </div>
    </div>
</div>

<script>
// Simple customer dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const customerSelect = document.getElementById('customer-select');
    if (!customerSelect) return;
    
    // Load customers immediately
    loadCustomersIntoSelect();
    
    function loadCustomersIntoSelect() {
        if (typeof myPluginAjax === 'undefined') {
            console.error('myPluginAjax not available');
            return;
        }
        
        customerSelect.innerHTML = '<option value="">Loading customers...</option>';
        
        jQuery.ajax({
            url: myPluginAjax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'get_all_customers',
                nonce: myPluginAjax.nonces.get_waybills_nonce
            },
            success: function(response) {
                console.log('Customer response:', response);
                if (response.success && response.data) {
                    populateSelect(response.data);
                } else {
                    customerSelect.innerHTML = '<option value="">No customers found</option>';
                }
            },
            error: function(xhr) {
                console.error('Failed to load customers:', xhr.responseText);
                customerSelect.innerHTML = '<option value="">Error loading customers</option>';
            }
        });
    }
    
    function populateSelect(customers) {
        customerSelect.innerHTML = '<option value="">Select a customer...</option>';
        
        customers.forEach(customer => {
            const option = document.createElement('option');
            option.value = customer.id;
            option.textContent = `${customer.name} ${customer.surname} - ${customer.company_name || 'No company'} (${customer.cell})`;
            option.setAttribute('data-name', customer.name);
            option.setAttribute('data-surname', customer.surname);
            option.setAttribute('data-company', customer.company_name || '');
            option.setAttribute('data-cell', customer.cell || '');
            option.setAttribute('data-email', customer.email_address || '');
            option.setAttribute('data-address', customer.address || '');
            customerSelect.appendChild(option);
        });
    }
    
    // Handle customer selection
    customerSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (this.value) {
            const customer = {
                id: this.value,
                name: selectedOption.getAttribute('data-name'),
                surname: selectedOption.getAttribute('data-surname'),
                company_name: selectedOption.getAttribute('data-company'),
                cell: selectedOption.getAttribute('data-cell'),
                email_address: selectedOption.getAttribute('data-email'),
                address: selectedOption.getAttribute('data-address')
            };
            
            console.log('Customer selected:', customer);
            
            // Set customer ID
            const custIdInput = document.getElementById('cust_id');
            if (custIdInput) {
                custIdInput.value = customer.id;
            }
            
            // Show selected customer display
            const selectedDisplay = document.getElementById('selected-customer-display');
            const nameEl = document.getElementById('selected-customer-name');
            const detailsEl = document.getElementById('selected-customer-details');
            
            if (nameEl) nameEl.textContent = `${customer.name} ${customer.surname}`;
            if (detailsEl) detailsEl.textContent = `${customer.company_name || 'No company'} • ${customer.cell}`;
            if (selectedDisplay) selectedDisplay.classList.remove('hidden');
            
            // Update summary if function exists
            if (typeof updateCustomerSummary === 'function') {
                updateCustomerSummary(customer);
            }
        } else {
            // Clear selection
            const custIdInput = document.getElementById('cust_id');
            if (custIdInput) custIdInput.value = '';
            
            const selectedDisplay = document.getElementById('selected-customer-display');
            if (selectedDisplay) selectedDisplay.classList.add('hidden');
            
            if (typeof updateCustomerSummary === 'function') {
                updateCustomerSummary(null);
            }
        }
    });
    
    // Handle clear button
    const clearButton = document.getElementById('clear-customer-selection');
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            customerSelect.value = '';
            customerSelect.dispatchEvent(new Event('change'));
        });
    }
});
</script>