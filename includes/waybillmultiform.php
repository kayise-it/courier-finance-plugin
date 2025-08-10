<?php
// File location: /includes/waybillmultiform.php
function kit_render_waybill_multiform($atts)
{
    $atts = shortcode_atts([
        'form_action' => '',
        'waybill_id' => '',
        'is_edit_mode' => '0',
        'waybill' => '{}',
        'customer_id' => '0',
        'delivery_id' => '0',
        'is_existing_customer' => '0',
        'ajaxform' => 'false',
        'customer' => '{}',
    ], $atts);

    $form_action = esc_url($atts['form_action']);
    $waybill_id = esc_attr($atts['waybill_id']);
    $is_edit_mode = $atts['is_edit_mode'] === '1';
    $is_existing_customer = $atts['is_existing_customer'];
    $customer_id = esc_attr($atts['customer_id']);
    $delivery_id = esc_attr($atts['delivery_id']);
    $direction_id = KIT_Deliveries::getDirectionId($delivery_id);

    // Decode JSON values
    $waybill = json_decode(stripslashes($atts['waybill']), true);
    $customer = $atts['customer'];
    
    // Check if current user is authorized to see totals
    $current_user = wp_get_current_user();
    $authorized_users = ['Thando', 'mel'];
    $can_view_totals = in_array($current_user->user_login, $authorized_users);

    ob_start(); ?>
    
    <!-- Fix WordPress Admin Footer Overlap -->
    <style>
        /* Force WordPress admin footer to stay at bottom */
        #wpfooter {
            position: relative !important;
            margin-top: 50px !important;
            z-index: 1 !important;
        }
        
        /* Ensure our form has enough bottom space */
        .waybill-form-container {
            padding-bottom: 150px !important;
            margin-bottom: 100px !important;
            min-height: calc(100vh + 200px) !important;
        }
        
        /* Fix admin wrap to prevent overlap */
        .wrap {
            margin-bottom: 100px !important;
        }
        
        /* WordPress admin body adjustments */
        #wpcontent {
            padding-bottom: 100px !important;
        }
    </style>
    
    <!-- Progressive Header -->
    <div class="waybill-form-container min-h-screen bg-gray-50 pb-20">
        <div class="sticky top-0 z-30 bg-white shadow-sm border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="flex items-center justify-between py-4">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-xl font-bold text-gray-900">
                                    <?php echo $is_edit_mode ? 'Edit Waybill' : 'Create Waybill'; ?>
                                </h1>
                                <p class="text-sm text-gray-600">Smart shipping documentation</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress -->
                    <div class="hidden lg:flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-blue-600 rounded-full animate-pulse"></div>
                            <span class="text-sm text-blue-600 font-medium">In Progress</span>
                        </div>
                        <div class="h-4 w-px bg-gray-300"></div>
                        <span class="text-sm text-gray-600" id="current-step-name">Basic Information</span>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="pb-3">
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-gradient-to-r from-blue-600 to-indigo-600 h-2 rounded-full transition-all duration-500 ease-out" style="width: 25%" id="progress-bar"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500 mt-2">
                        <span>Start</span>
                        <span class="hidden sm:inline">Customer</span>
                        <span class="hidden sm:inline">Items</span>
                        <span>Destination</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="lg:grid lg:grid-cols-12 lg:gap-8">
                
                <!-- Progressive Form -->
                <div class="lg:col-span-8 space-y-8">
                    <form method="POST" action="<?php echo esc_attr($form_action); ?>" id="progressive-waybill-form" data-ajax-url="<?php echo admin_url('admin-ajax.php'); ?>" novalidate>
                        <?php if ($is_edit_mode): ?>
                            <input type="hidden" name="waybill_id" value="<?php echo esc_attr($waybill_id); ?>">
                        <?php endif; ?>
                        <?php if ($is_existing_customer): ?>
                            <input type="hidden" name="exsitingCust" value="<?php echo esc_attr($is_existing_customer); ?>">
                        <?php endif; ?>
                        <?php wp_nonce_field($is_edit_mode ? 'update_waybill_nonce' : 'add_waybill_nonce'); ?>

                        <!-- Section 1: Basic Info -->
                        <div class="section-card active" id="section-1" data-section="1">
                            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                                <!-- Section Header -->
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-5 border-b border-gray-100">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                            <span class="text-sm font-bold text-blue-600">1</span>
                                        </div>
                                        <div>
                                            <h2 class="text-lg font-semibold text-gray-900">Start</h2>
                                            <p class="text-sm text-gray-600">Basic waybill information and charges</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section Content -->
                                <div class="p-6">
                                    <?php require __DIR__ . '/waybill/steps/step1.php'; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Section 2: Customer Information -->
                        <div class="section-card hidden" id="section-2" data-section="2">
                            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                                <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-5 border-b border-gray-100">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                                            <span class="text-sm font-bold text-green-600">2</span>
                                        </div>
                                        <div>
                                            <h2 class="text-lg font-semibold text-gray-900">Customer Details</h2>
                                            <p class="text-sm text-gray-600">Select customer and origin location</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6">
                                    <?php require __DIR__ . '/waybill/steps/step2.php'; ?>
                                </div>
                            </div>
                        </div>

                                                <!-- Section 3: Items & Weight -->
                        <div class="section-card hidden" id="section-3" data-section="3">
                            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                                <div class="bg-gradient-to-r from-emerald-50 to-teal-50 px-6 py-5 border-b border-gray-100">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                                            <span class="text-sm font-bold text-emerald-600">3</span>
                                        </div>
                                        <div>
                                            <h2 class="text-lg font-semibold text-gray-900">Items & Weight</h2>
                                            <p class="text-sm text-gray-600">Waybill items, dimensions, and weight details</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-6 space-y-8">
                                    <?php require __DIR__ . '/waybill/steps/step3.php'; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Section 4: Destination -->
                        <div class="section-card hidden" id="section-4" data-section="4">
                            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-5 border-b border-gray-100">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                                            <span class="text-sm font-bold text-blue-600">4</span>
                                        </div>
                                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Destination & Completion</h2>
                            <p class="text-sm text-gray-600">Delivery location, additional charges and waybill creation</p>
                                        </div>
                                    </div>
                                </div>
                <div class="p-6 space-y-8">
                    <!-- Destination Details -->
                                    <?php require __DIR__ . '/waybill/steps/step4.php'; ?>
                    
                    <!-- Additional Charges -->
                    <div class="border-t border-gray-200 pt-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Additional Charges & Options</h3>
                        <?php require __DIR__ . '/components/additionCharges.php'; ?>
                                        </div>
                                </div>
                            </div>
                        </div>



                        <!-- Navigation Controls -->
                        <div class="flex items-center justify-between pt-6">
                            <button type="button" id="prev-section" class="hidden inline-flex items-center px-6 py-3 border border-gray-300 text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                <svg class="mr-2 -ml-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd" />
                                </svg>
                                Previous
                            </button>
                            
                            <div class="flex space-x-4">
                                <button type="button" id="next-section" class="inline-flex items-center px-8 py-3 border border-transparent text-sm font-medium rounded-xl text-white bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                    <span id="next-text">Continue</span>
                                    <svg class="ml-2 -mr-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                                
                                <button type="submit" id="create-waybill" class="hidden inline-flex items-center px-8 py-3 border border-transparent text-sm font-medium rounded-xl shadow-sm text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200">
                                    <svg class="mr-2 -ml-1 w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <?php echo $is_edit_mode ? 'Update Waybill' : 'Create Waybill'; ?>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Smart Sidebar -->
                <div class="lg:col-span-4 space-y-6">
                    <!-- Live Preview Card -->
                    <div class="sticky top-32">
                        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                            <div class="bg-gradient-to-r from-slate-50 to-gray-50 px-6 py-5 border-b border-gray-100">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center">
                                        <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Waybill Preview</h3>
                                        <p class="text-sm text-gray-600">Live summary updates</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-6 space-y-6">
                                <!-- Customer Section -->
                                <div class="space-y-3">
                                    <h4 class="text-sm font-semibold text-gray-900 flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        Customer
                                    </h4>
                                    <div class="pl-6 text-sm text-gray-600">
                                        <div class="summary-customer-text">Continue to Step 2 to select customer</div>
                                    </div>
                                </div>

                                <!-- Shipping Section -->
                                <div class="space-y-3">
                                    <h4 class="text-sm font-semibold text-gray-900 flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        Destination
                                    </h4>
                                    <div class="pl-6 space-y-1 text-sm text-gray-600">
                                        <div>
                                            <span class="font-medium">To:</span> 
                                            <span class="summary-destination-text">Not selected</span>
                                        </div>
                                        <div>
                                            <span class="font-medium">Delivery:</span> 
                                            <span class="summary-delivery-text">Not selected</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Cost Breakdown -->
                                <div class="space-y-4 border-t border-gray-100 pt-6">
                                    <h4 class="text-sm font-semibold text-gray-900 flex items-center">
                                        <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                        </svg>
                                        Cost Breakdown
                                    </h4>
                                    <div class="space-y-3 text-sm">
                                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                            <span class="text-gray-600">Items:</span>
                                            <span class="font-medium" id="summary-items-total">R 0.00</span>
                                        </div>
                                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                            <span class="text-gray-600">Shipping:</span>
                                            <span class="font-medium" id="summary-shipping-total">R 0.00</span>
                                        </div>
                                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                            <span class="text-gray-600">Additional:</span>
                                            <span class="font-medium" id="summary-misc-total">R 0.00</span>
                                        </div>
                                        
                                        <?php if ($can_view_totals): ?>
                                        <div class="flex justify-between items-center p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-100">
                                            <span class="font-semibold text-gray-900">Total:</span>
                                            <span class="text-xl font-bold text-blue-600" id="summary-grand-total">R 0.00</span>
                                        </div>
                                        <?php else: ?>
                                        <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg text-center">
                                            <div class="text-sm font-medium text-amber-900">Total Restricted</div>
                                            <div class="text-xs text-amber-700 mt-1">Contact Thando or Mel for pricing</div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                            <div class="space-y-3">
                                <button type="button" class="w-full text-left px-4 py-3 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors duration-200 group">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">Save Draft</div>
                                            <div class="text-xs text-gray-500">Continue later</div>
                                        </div>
                                    </div>
                                </button>
                                <button type="button" class="w-full text-left px-4 py-3 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors duration-200 group">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">Use Template</div>
                                            <div class="text-xs text-gray-500">From previous waybill</div>
                                        </div>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if user can view totals
            const canViewTotals = <?php echo $can_view_totals ? 'true' : 'false'; ?>;
            
            // Section navigation is now handled in waybill-pagination.js
            console.log('Waybill multiform loaded - navigation handled by external JS');

            // Flexible input resolver (detects normal or "a" prefixed fields)
            function getCustomerInput(idBase) {
                return document.getElementById(idBase) || document.getElementById('a' + idBase);
            }

            // Initialize customer form element references
            const custIdInput = document.getElementById('cust_id');
            const customerSelect = document.getElementById('customer-select');
            const companyInput = getCustomerInput('company_name');
            const nameInput = getCustomerInput('customer_name');
            const surnameInput = getCustomerInput('customer_surname');
            const cellInput = getCustomerInput('cell');
            const addressInput = getCustomerInput('address');

            // Function to populate customer fields
            function populateCustomerDetails(customerId) {
                const option = customerSelect?.querySelector(`option[value="${customerId}"]`);
                if (option) {
                    const companyName = option.getAttribute('data-company_name') || '';
                    const customerName = option.getAttribute('data-name') || '';
                    const customerSurname = option.getAttribute('data-surname') || '';
                    const customerCell = option.getAttribute('data-cell') || '';
                    const customerAddress = option.getAttribute('data-address') || '';
                    const customerEmail = option.getAttribute('data-email_address') || '';
                    
                    if (companyInput) companyInput.value = companyName;
                    if (nameInput) nameInput.value = customerName;
                    if (surnameInput) surnameInput.value = customerSurname;
                    if (cellInput) cellInput.value = customerCell;
                    if (addressInput) addressInput.value = customerAddress;
                    if (custIdInput) custIdInput.value = customerId;
                    
                    // Update the sidebar with selected customer data
                    const customerData = {
                        id: customerId,
                        name: customerName,
                        surname: customerSurname,
                        company_name: companyName,
                        cell: customerCell,
                        email_address: customerEmail
                    };
                    updateCustomerSummary(customerData);
                }
            }

            // Customer selection handling
            if (customerSelect) {
                customerSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const customerText = document.querySelector('.summary-customer-text');
                    
                    if (customerText) {
                        if (this.value === 'new') {
                            customerText.textContent = 'New Customer';
                        } else if (this.value) {
                            customerText.textContent = selectedOption.textContent;
                        } else {
                            customerText.textContent = 'Complete Step 2 to select customer';
                        }
                    }
                    
                    // Trigger existing customer population logic
                    populateCustomerDetails(this.value);
                });
            }

            // Initialize form - section navigation is handled by waybill-pagination.js
            // No need to call showSection here as it's handled externally
            
            // Update contextual customer message based on initial step
            updateCustomerContextualMessage();
            
            // Initialize required fields for the starting step
            updateRequiredFieldsForCurrentStep();
            
            // Handle form submission with proper validation
            const form = document.getElementById('progressive-waybill-form');
            const createBtn = document.getElementById('create-waybill');
            
            if (form && createBtn) {
                createBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Before submitting, ensure all required fields are properly set for final validation
                    // Enable all fields that should be required for submission
                    const allImportantFields = ['customer_name', 'customer_surname', 'cell'];
                    allImportantFields.forEach(fieldId => {
                        const field = document.getElementById(fieldId);
                        if (field && field.value.trim()) {
                            field.setAttribute('required', 'required');
                        }
                    });
                    
                    // Validate all important steps before submission
                    let allValid = true;
                    const errors = [];
                    
                    // Check customer selection/info
                    const custId = document.getElementById('cust_id')?.value;
                    const newCustomerToggle = document.getElementById('new-customer-toggle')?.checked;
                    
                    if (!custId || custId === '0') {
                        if (!newCustomerToggle) {
                            errors.push('Please select a customer or toggle "New Customer" to add one');
                            allValid = false;
                        }
                    }
                    
                    if (newCustomerToggle) {
                        const customerName = document.getElementById('customer_name')?.value;
                        const customerSurname = document.getElementById('customer_surname')?.value; 
                        const cell = document.getElementById('cell')?.value;
                        
                        if (!customerName?.trim()) {
                            errors.push('Customer first name is required');
                            allValid = false;
                        }
                        if (!customerSurname?.trim()) {
                            errors.push('Customer last name is required');
                            allValid = false;
                        }
                        if (!cell?.trim()) {
                            errors.push('Customer phone number is required');
                            allValid = false;
                        }
                    }
                    
                    // Check mass/volume charges
                    let massCharge = parseFloat(document.getElementById('mass_charge')?.value || 0);
                    let volumeCharge = parseFloat(document.getElementById('volume_charge')?.value || 0);
                    
                    // Try to auto-fix charges if they're both 0
                    if (massCharge <= 0 && volumeCharge <= 0) {
                        const massInput = document.getElementById('total_mass_kg');
                        
                        // If no weight entered, set minimum 1kg
                        if (massInput && (!massInput.value || parseFloat(massInput.value) === 0)) {
                            massInput.value = '1';
                            
                            // Try to trigger calculation
                            const massRateInput = document.getElementById('mass_rate');
                            const massChargeInput = document.getElementById('mass_charge');
                            
                            if (massRateInput && massChargeInput) {
                                // Calculate rate for 1kg (from rate tier logic)
                                const rate = 40; // 1kg falls in 10-500kg tier = 40 R/kg
                                massRateInput.value = rate.toFixed(2);
                                massChargeInput.value = (1 * rate).toFixed(2);
                                massCharge = 1 * rate;
                            }
                        }
                        
                        // Final check after auto-fix attempt
                        massCharge = parseFloat(document.getElementById('mass_charge')?.value || 0);
                        volumeCharge = parseFloat(document.getElementById('volume_charge')?.value || 0);
                        
                        if (massCharge <= 0 && volumeCharge <= 0) {
                            errors.push('Please enter weight or dimensions in Step 3 to calculate shipping charges');
                            allValid = false;
                        }
                    }
                    
                    if (!allValid) {
                        alert('Please complete the following required fields:\\n• ' + errors.join('\\n• '));
                        return false;
                    }
                    
                    // All validation passed, submit the form
                    console.log('Form validation passed, submitting...');
                    form.submit();
                });
            }

            // Accordion toggle (keeping existing functionality)
            const accordionToggle = document.querySelector('.customer-accordion-toggle');
            const accordionContent = document.querySelector('.customer-details-content');
            if (accordionToggle && accordionContent) {
                accordionToggle.addEventListener('click', function() {
                    accordionContent.classList.toggle('hidden');
                });
            }

            // Duplicate declarations removed - now handled above

            if (customerSelect) {
                customerSelect.addEventListener('change', function() {
                    if (this.value === 'new') {
                        if (nameInput) nameInput.value = '';
                        if (surnameInput) surnameInput.value = '';
                        if (cellInput) cellInput.value = '';
                        if (addressInput) addressInput.value = '';
                        if (custIdInput) custIdInput.value = '0';
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

            const steps = document.querySelectorAll('.step');
            const nextButtons = document.querySelectorAll('.next-step');
            const prevButtons = document.querySelectorAll('.prev-step');
            const stepIndicators = document.querySelectorAll('.step-indicator');

            // Common function to switch steps
            function switchStep(fromStep, toStep) {
                if (!toStep || !fromStep) return;
                fromStep.classList.remove('active');
                fromStep.classList.add('hidden');
                toStep.classList.remove('hidden');
                toStep.classList.add('active');
                updateStepIndicator(toStep);
            }

            // NEXT step buttons
            nextButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const currentStep = document.querySelector('.step.active:not(.md\\:block)');
                    const targetId = this.getAttribute('data-target');
                    const targetStep = targetId ? document.getElementById(targetId) : currentStep?.nextElementSibling;

                    if (validateStep(currentStep)) {
                        switchStep(currentStep, targetStep);
                    }
                });
            });

            // PREVIOUS step buttons
            prevButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const currentStep = document.querySelector('.step.active');
                    const targetId = this.getAttribute('data-target');
                    //const prevStep = currentStep?.previousElementSibling;
                    const targetStep = targetId ? document.getElementById(targetId) : currentStep?.nextElementSibling;

                    switchStep(currentStep, targetStep);
                });
            });

            // Simple placeholder validation
            function validateStep(step) {
                if (step && step.id === 'step-4') {
                    const warehoused = document.getElementById('warehoused_option')?.checked;
                    const country = document.getElementById('stepDestinationSelect')?.value || '';
                    const delivery = document.querySelector('input[name="delivery_id"]')?.value || '';
                    const direction = document.querySelector('input[name="direction_id"]:checked')?.value || '';
                    if (!warehoused && (!country || !delivery || !direction)) {
                        alert('Please select a destination country and a scheduled delivery before continuing.');
                        return false;
                    }
                }
                return true;
            }

            // Update step indicator
            function updateStepIndicator(activeStep) {
                const indicators = document.querySelectorAll('.step-indicator');
                const stepNumber = activeStep ? parseInt(activeStep.id.split('-')[1]) : 1;
                
                indicators.forEach((indicator, index) => {
                    const span = indicator.querySelector('span:first-child');
                    const text = indicator.querySelector('span:last-child');
                    
                    if (index + 1 < stepNumber) {
                        // Completed steps
                        span.classList.remove('bg-gray-200', 'text-gray-600', 'bg-blue-600', 'text-white');
                        span.classList.add('bg-green-600', 'text-white');
                        text.classList.remove('text-gray-500', 'text-blue-600');
                        text.classList.add('text-green-600');
                    } else if (index + 1 === stepNumber) {
                        // Current step
                        span.classList.remove('bg-gray-200', 'text-gray-600', 'bg-green-600');
                        span.classList.add('bg-blue-600', 'text-white');
                        text.classList.remove('text-gray-500', 'text-green-600');
                        text.classList.add('text-blue-600');
                    } else {
                        // Future steps
                        span.classList.remove('bg-blue-600', 'text-white', 'bg-green-600');
                        span.classList.add('bg-gray-200', 'text-gray-600');
                        text.classList.remove('text-blue-600', 'text-green-600');
                        text.classList.add('text-gray-500');
                    }
                });
            }

            // Initialize stepper
            updateStepIndicator(document.querySelector('.step.active'));

            // Function to update customer display based on current step
            function updateCustomerContextualMessage() {
                const customerText = document.querySelector('.summary-customer-text');
                if (!customerText) return;
                
                const currentSection = document.querySelector('.section-card:not(.hidden)');
                if (!currentSection) return;
                
                const sectionNum = currentSection.getAttribute('data-section');
                
                // Only show contextual message if no customer is actually selected
                if (customerText.textContent.includes('Complete Step') || customerText.textContent === 'Not selected') {
                    switch(sectionNum) {
                        case '1':
                            customerText.textContent = 'Continue to Step 2 to select customer';
                            break;
                        case '2':
                            customerText.textContent = 'Select customer above';
                            break;
                        case '3':
                        case '4':
                            customerText.textContent = 'Return to Step 2 to change customer';
                            break;
                        default:
                            customerText.textContent = 'Complete Step 2 to select customer';
                    }
                }
            }

            // Function to update customer info in sidebar (make it global)
            function updateCustomerSummary(customer) {
                console.log('updateCustomerSummary called with:', customer);
                const customerText = document.querySelector('.summary-customer-text');
                console.log('Found customer text element:', customerText);
                
                if (customer && customer.id) {
                    if (customerText) {
                        customerText.innerHTML = `
                            <div class="font-medium text-gray-900">${customer.name} ${customer.surname}</div>
                            <div class="text-xs text-gray-600">${customer.company_name || 'No company'}</div>
                            <div class="text-xs text-gray-600">${customer.cell}</div>
                            <div class="text-xs text-gray-600">${customer.email_address || 'No email'}</div>
                        `;
                        console.log('Updated customer sidebar content');
                    }
                } else {
                    if (customerText) customerText.textContent = 'Complete Step 2 to select customer';
                }
            }
            
            // Function to manage required fields based on current step
            function updateRequiredFieldsForCurrentStep() {
                const currentSection = document.querySelector('.section-card:not(.hidden)');
                if (!currentSection) return;
                
                const sectionNum = parseInt(currentSection.getAttribute('data-section'));
                
                // All potential required fields across all steps
                const allRequiredFields = [
                    'customer_name', 'customer_surname', 'cell', 
                    'origin_country_select', 'origin_city_select',
                    'total_mass_kg'
                ];
                
                // Define which fields are required for each step
                const stepRequirements = {
                    1: [], // Step 1: Start - No required fields (basic info only)
                    2: ['customer_name', 'customer_surname', 'cell'], // Step 2: Customer Details
                    3: ['total_mass_kg'], // Step 3: Items & Weight
                    4: []  // Step 4: Destination & Completion - Review (handled separately)
                };
                
                // Update all fields based on current step
                allRequiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        const shouldBeRequired = stepRequirements[sectionNum]?.includes(fieldId);
                        const stepRequired = field.getAttribute('data-step-required');
                        
                        if (shouldBeRequired || (stepRequired && parseInt(stepRequired) === sectionNum)) {
                            field.setAttribute('required', 'required');
                            field.removeAttribute('disabled');
                        } else {
                            field.removeAttribute('required');
                            // Don't disable, just remove required to prevent validation issues
                        }
                    }
                });
                
                // Also check for fields with data-step-required attribute
                const stepRequiredFields = document.querySelectorAll(`[data-step-required="${sectionNum}"]`);
                stepRequiredFields.forEach(field => {
                    field.setAttribute('required', 'required');
                });
                
                // Remove required from fields that shouldn't be required in this step
                const allStepFields = document.querySelectorAll('[data-step-required]');
                allStepFields.forEach(field => {
                    const requiredStep = parseInt(field.getAttribute('data-step-required'));
                    if (requiredStep !== sectionNum) {
                        field.removeAttribute('required');
                    }
                });
                
                console.log(`Updated required fields for step ${sectionNum}:`, stepRequirements[sectionNum] || []);
            }
            
            // Function to validate only the current step
            function validateCurrentStep() {
                const currentSection = document.querySelector('.section-card:not(.hidden)');
                if (!currentSection) return true;
                
                const sectionNum = parseInt(currentSection.getAttribute('data-section'));
                
                // Get all required fields in the current visible section
                const requiredFields = currentSection.querySelectorAll('input[required], select[required], textarea[required]');
                
                let isValid = true;
                const invalidFields = [];
                
                requiredFields.forEach(field => {
                    // Only validate visible fields
                    if (field.offsetParent !== null) { // Field is visible
                        if (!field.value.trim()) {
                            isValid = false;
                            invalidFields.push(field);
                            
                            // Add visual feedback
                            field.classList.add('border-red-500', 'ring-red-500');
                            field.classList.remove('border-gray-300');
                        } else {
                            // Remove error styling if field is now valid
                            field.classList.remove('border-red-500', 'ring-red-500');
                            field.classList.add('border-gray-300');
                        }
                    }
                });
                
                if (!isValid) {
                    // Focus on the first invalid field
                    if (invalidFields.length > 0) {
                        invalidFields[0].focus();
                        invalidFields[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    
                    // Show error message
                    console.error(`Step ${sectionNum} validation failed. Missing required fields:`, invalidFields.map(f => f.name));
                }
                
                return isValid;
            }

            // Make functions globally available
            window.updateCustomerSummary = updateCustomerSummary;
            window.updateCustomerContextualMessage = updateCustomerContextualMessage;
            window.updateRequiredFieldsForCurrentStep = updateRequiredFieldsForCurrentStep;
            window.validateCurrentStep = validateCurrentStep;

            // Function to update destination info in sidebar 
            function updateDestinationSummary() {
                const destText = document.querySelector('.summary-destination-text');
                const deliveryText = document.querySelector('.summary-delivery-text');
                
                const countrySelect = document.getElementById('stepDestinationSelect');
                const selectedDelivery = document.querySelector('input[name="delivery_id"]');
                
                if (countrySelect && countrySelect.value) {
                    const selectedOption = countrySelect.options[countrySelect.selectedIndex];
                    const citySelect = document.getElementById('destination_city');
                    const cityText = citySelect && citySelect.value ? citySelect.options[citySelect.selectedIndex].text : 'Not selected';
                    
                    if (destText) destText.textContent = `${selectedOption.text}, ${cityText}`;
                    
                    if (selectedDelivery && selectedDelivery.value) {
                        const checkedDirection = document.querySelector('input[name="direction_id"]:checked');
                        if (checkedDirection) {
                            const deliveryLabel = document.querySelector(`label[for*="direction_${checkedDirection.value}"]`);
                            const deliveryDisplayText = deliveryLabel ? deliveryLabel.querySelector('div').textContent.trim() : 'Selected';
                            if (deliveryText) deliveryText.textContent = deliveryDisplayText;
                        } else {
                            if (deliveryText) deliveryText.textContent = 'Not selected';
                        }
                    } else {
                        if (deliveryText) deliveryText.textContent = 'Not selected';
                    }
                } else {
                    if (destText) destText.textContent = 'Not selected';
                    if (deliveryText) deliveryText.textContent = 'Not selected';
                }
            }

            // Function to update waybill items count in sidebar
            function updateWaybillItemsSummary() {
                const itemsStatus = document.getElementById('items-status');
                const itemsCount = document.getElementById('summary-items-count');
                
                const waybillItems = document.querySelectorAll('#custom-waybill-items .waybill-item');
                const count = waybillItems.length;
                
                if (itemsStatus) itemsStatus.textContent = count > 0 ? `${count} items` : 'No items';
                if (itemsCount) itemsCount.textContent = count > 0 ? `Items: ${count}` : 'Items: 0';
            }

            // Function to update summary totals (only for authorized users)
            function updateSummaryTotals() {
                if (!canViewTotals) return; // Skip if user can't view totals
                
                // Get shipping charges
                const massCharge = parseFloat(document.getElementById('mass_charge')?.value || 0);
                const volumeCharge = parseFloat(document.getElementById('volume_charge')?.value || 0);
                const shippingTotal = Math.max(massCharge, volumeCharge);
                
                // Get misc charges
                let miscTotal = 0;
                document.querySelectorAll('input[name="misc_price[]"]').forEach(input => {
                    miscTotal += parseFloat(input.value || 0);
                });
                
                // Get waybill items total
                let itemsTotal = 0;
                document.querySelectorAll('input[name*="item_value"]').forEach(input => {
                    itemsTotal += parseFloat(input.value || 0);
                });
                
                // Update sidebar displays
                const summaryItemsTotal = document.getElementById('summary-items-total');
                const summaryShippingTotal = document.getElementById('summary-shipping-total');
                const summaryMiscTotal = document.getElementById('summary-misc-total');
                const summaryGrandTotal = document.getElementById('summary-grand-total');
                
                if (summaryItemsTotal) summaryItemsTotal.textContent = `R ${itemsTotal.toFixed(2)}`;
                if (summaryShippingTotal) summaryShippingTotal.textContent = `R ${shippingTotal.toFixed(2)}`;
                if (summaryMiscTotal) summaryMiscTotal.textContent = `R ${miscTotal.toFixed(2)}`;
                
                if (summaryGrandTotal && canViewTotals) {
                    const grandTotal = shippingTotal + miscTotal;
                    summaryGrandTotal.textContent = `R ${grandTotal.toFixed(2)}`;
                }
            }

            // Function to monitor form changes and update sidebar
            function initSidebarUpdates() {
                // Monitor customer selection changes
                const custIdInput = document.getElementById('cust_id');
                if (custIdInput) {
                    custIdInput.addEventListener('change', function() {
                        console.log('Customer ID changed to:', this.value);
                        // If there's a customer selected, try to update sidebar
                        if (this.value && this.value !== '0') {
                            // Try to get customer data from the search or form
                            const searchInput = document.getElementById('customer-search');
                            if (searchInput && searchInput.value) {
                                console.log('Customer search has value, but no customer object available for sidebar update');
                            }
                        }
                        updateDestinationSummary();
                        updateSummaryTotals();
                    });
                }

                // Monitor customer form field changes to update sidebar
                function updateCustomerFromForm() {
                    const customerName = document.getElementById('customer_name')?.value || '';
                    const customerSurname = document.getElementById('customer_surname')?.value || '';
                    const companyName = document.getElementById('company_name')?.value || '';
                    const cellPhone = document.getElementById('cell')?.value || '';
                    const emailAddress = document.getElementById('email_address')?.value || '';
                    
                    // Only update if we have at least a name
                    if (customerName.trim() || customerSurname.trim()) {
                        const customerData = {
                            id: 'form-customer',
                            name: customerName,
                            surname: customerSurname,
                            company_name: companyName,
                            cell: cellPhone,
                            email_address: emailAddress
                        };
                        updateCustomerSummary(customerData);
                    } else {
                        // Clear customer summary if no name provided
                        const customerText = document.querySelector('.summary-customer-text');
                        if (customerText) {
                            customerText.textContent = 'Complete Step 2 to select customer';
                        }
                    }
                }

                // Add event listeners to customer form fields
                const customerFields = ['customer_name', 'customer_surname', 'company_name', 'cell', 'email_address'];
                customerFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (field) {
                        field.addEventListener('input', updateCustomerFromForm);
                        field.addEventListener('change', updateCustomerFromForm);
                    }
                });

                // Initial update when page loads (in case fields are pre-filled)
                updateCustomerFromForm();

                // Monitor destination changes
                const countrySelect = document.getElementById('stepDestinationSelect');
                if (countrySelect) {
                    countrySelect.addEventListener('change', updateDestinationSummary);
                }

                const citySelect = document.getElementById('destination_city');
                if (citySelect) {
                    citySelect.addEventListener('change', updateDestinationSummary);
                }

                // Monitor waybill items changes with mutation observer
                const waybillItemsContainer = document.getElementById('custom-waybill-items');
                if (waybillItemsContainer) {
                    const observer = new MutationObserver(function(mutations) {
                        let shouldUpdate = false;
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'childList') {
                                shouldUpdate = true;
                            }
                        });
                        if (shouldUpdate) {
                            updateWaybillItemsSummary();
                            updateSummaryTotals();
                        }
                    });
                    
                    observer.observe(waybillItemsContainer, {
                        childList: true,
                        subtree: true
                    });

                    // Also monitor value changes in waybill items
                    document.addEventListener('input', function(e) {
                        if (e.target.name && (e.target.name.includes('item_value') || e.target.name.includes('misc_price'))) {
                            updateSummaryTotals();
                        }
                    });
                }

                // Monitor dimension and charge changes
                document.addEventListener('input', function(e) {
                    if (e.target.id === 'mass_charge' || e.target.id === 'volume_charge') {
                        updateSummaryTotals();
                    }
                });

                // Initial update
                updateWaybillItemsSummary();
                updateDestinationSummary();
                updateSummaryTotals();
            }

            // Make other functions globally available too
            window.updateDestinationSummary = updateDestinationSummary;
            window.updateWaybillItemsSummary = updateWaybillItemsSummary;
            window.updateSummaryTotals = updateSummaryTotals;
            
            // Initialize sidebar updates
            initSidebarUpdates();
            
            console.log('Sidebar update functions initialized and made global');
            
            // Debug: Check if sidebar elements exist
            const customerText = document.querySelector('.summary-customer-text');
            const destText = document.querySelector('.summary-destination-text');
            const deliveryText = document.querySelector('.summary-delivery-text');
            console.log('Sidebar elements found:', {
                customerText: !!customerText,
                destText: !!destText,
                deliveryText: !!deliveryText
            });
            
            // Test function to manually update customer sidebar
            window.testCustomerUpdate = function() {
                const testCustomer = {
                    id: '123',
                    name: 'Thando',
                    surname: 'Hlophe', 
                    company_name: 'Jimenez Vaughn LLC',
                    cell: '12341324',
                    email_address: 'test@example.com'
                };
                console.log('Testing manual customer update...');
                updateCustomerSummary(testCustomer);
            };
            
            console.log('Test function created: window.testCustomerUpdate() - call this in console to test');

            // Progress bar / Step indicators (legacy function - remove duplicate)
            function updateStepIndicatorLegacy(activeStep) {
                const stepMatch = activeStep.className.match(/step-(\d)/);
                const stepNumber = stepMatch ? parseInt(stepMatch[1]) : -1;

                stepIndicators.forEach((indicator, index) => {
                    const circle = indicator.querySelector('div');
                    const label = indicator.querySelector('span');

                    if (index + 1 === stepNumber) {
                        circle.className = 'bg-blue-600 text-white';
                        label.className = 'font-semibold text-blue-600';
                    } else if (index + 1 < stepNumber) {
                        circle.className = 'bg-green-500 text-white';
                        label.className = 'font-semibold text-green-500';
                    } else {
                        circle.className = 'bg-gray-200 text-gray-600';
                        label.className = 'text-gray-500';
                    }
                });
            }
            // Dynamic misc items
            const addMiscBtn = document.getElementById('add-misc-btn');
            const miscItemsContainer = document.getElementById('misc-items-container');

            if (addMiscBtn && miscItemsContainer) {
                addMiscBtn.addEventListener('click', () => {
                    const miscItemGroup = document.createElement('div');
                    miscItemGroup.className = 'misc-item';
                    miscItemGroup.style = 'display: flex; gap: 10px; margin-bottom: 10px; align-items: center;';
                    miscItemGroup.innerHTML = `
            <input type="text" name="misc_item[]" placeholder="Item description" style="flex: 2; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <input type="number" name="misc_price[]" placeholder="Amount" style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <button type="button" class="remove-misc-btn" style="background-color: #ef4444; color: white; padding: 8px; border-radius: 4px; border: none; cursor: pointer;">×</button>`;
                    miscItemsContainer.appendChild(miscItemGroup);
                });
            }

            document.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-misc-btn')) {
                    e.target.closest('.misc-item').remove();
                }
            });

            // Add event listeners to existing remove buttons
            document.querySelectorAll('.remove-misc-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    this.closest('.misc-item').remove();
                });
            });

        });

        <?php if (!$atts['ajaxform']): ?>
            // AJAX Form Submission
            alert();
            jQuery(document).ready(function($) {
                $('#multi-step-waybill-form').on('submit', function(e) {
                    e.preventDefault();

                    var $form = $(this);
                    var $submitBtn = $form.find('button[type="submit"]');
                    var originalBtnText = $submitBtn.html();

                    // Show loading state
                    $submitBtn.prop('disabled', true).html('Processing...');

                    // Add the AJAX nonce to the form data
                    var formData = new FormData(this);
                    formData.append('action', 'process_waybill_form');
                    formData.append('_ajax_nonce', myPluginAjax.nonces.add); // This matches what check_ajax_referer expects

                    $.ajax({
                            url: myPluginAjax.ajax_url, // Make sure ajaxurl is defined
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json'
                        })
                        .done(function(response) {
                            if (response.success) {
                                // Success handling
                                alert(response.data.message);
                                if (response.data.waybill_id) {
                                    fetch(window.location.href, {
                                            method: 'GET',
                                            credentials: 'same-origin'
                                        })
                                        .then(res => res.text())
                                        .then(html => {
                                            const parser = new DOMParser();
                                            const doc = parser.parseFromString(html, 'text/html');
                                            const newTableBody = doc.querySelector('#customerWaybills tbody');
                                            const currentTableBody = document.querySelector('#customerWaybills tbody');

                                            if (newTableBody && currentTableBody) {
                                                currentTableBody.innerHTML = newTableBody.innerHTML;
                                            }
                                        })
                                        .catch(err => {
                                            console.error('Error refreshing table:', err);
                                            alert('Waybill saved, but failed to refresh the table.');
                                        });
                                }
                            } else {
                                alert('Error: ' + response.data.message);
                            }
                        })
                        .fail(function(xhr, status, error) {
                            alert('An error occurred: ' + error);
                        })
                        .always(function() {
                            $submitBtn.html(originalBtnText).prop('disabled', false);
                        });
                });

                // Initialize variables
                const ajaxurl = window.ajax_url || window.myPluginAjax?.ajax_url;
                const adminurl = window.myPluginAjax?.admin_url || window.location.origin + '/wp-admin/';

               

                // Refresh waybills table
                function refreshWaybillsTable(customerId) {
                    $('#waybillsTableBody').hide();
                    $('#waybillsLoading').show();
                    $('#waybillsEmpty').hide();

                    $.ajax({
                        url: myPluginAjax.ajax_url,
                        type: 'GET',
                        data: {
                            action: 'get_customer_waybills',
                            customer_id: customerId,
                            _ajax_nonce: myPluginAjax.nonces.getall
                        },
                        success: function(response) {
                            if (response.success && response.data?.waybills) {
                                renderWaybillsTable(response.data.waybills);
                            } else {
                                $('#waybillsEmpty').show();
                            }
                        },
                        error: function(xhr, status, error) {
                            alert('Error loading waybills: ' + error);
                        },
                        complete: function() {
                            $('#waybillsLoading').hide();
                        }
                    });
                }

                // Render waybills in table
                function renderWaybillsTable(waybills) {
                    const $tbody = $('#waybillsTableBody');
                    $tbody.empty();

                    if (waybills.length === 0) {
                        $('#waybillsEmpty').show();
                        return;
                    }

                    waybills.forEach(function(waybill) {
                        const statusClass = {
                            'pending': 'bg-yellow-100 text-yellow-800',
                            'shipped': 'bg-blue-100 text-blue-800',
                            'delivered': 'bg-green-100 text-green-800',
                            'cancelled': 'bg-red-100 text-red-800'
                        } [waybill.status.toLowerCase()] || 'bg-gray-100 text-gray-800';

                        $tbody.append(`
                <tr data-waybill-id="${waybill.waybill_id}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-xs font-medium text-blue-600">${waybill.waybill_no}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-xs text-gray-500">${new Date(waybill.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                            ${waybill.status.charAt(0).toUpperCase() + waybill.status.slice(1).toLowerCase()}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-xs text-gray-500">${waybill.item_count || 0}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-xs font-medium">R${waybill.total_amount ? parseFloat(waybill.total_amount).toFixed(2) : '0.00'}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                        <a href="${adminurl}admin.php?page=08600-Waybill-view&waybill_id=${waybill.waybill_id}&waybill_atts=view_waybill" 
                           class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                        <a href="${adminurl}admin.php?page=08600-Waybill-print&waybill_id=${waybill.waybill_id}" 
                           class="text-indigo-600 hover:text-indigo-900" target="_blank">Print</a>
                        <button class="delete-waybill text-red-600 hover:text-red-900 ml-3" 
                                data-waybill-id="${waybill.waybill_id}">Delesdsdte</button>
                    </td>
                </tr>`);
                    });

                    $tbody.show();
                    checkEmptyTable();
                }

                // Check if table is empty
                function checkEmptyTable() {
                    if ($('#waybillsTableBody tr').length === 0) {
                        $('#waybillsEmpty').show();
                    } else {
                        $('#waybillsEmpty').hide();
                    }
                }
            });
        <?php endif; ?>
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('waybill_multiform', 'kit_render_waybill_multiform');
