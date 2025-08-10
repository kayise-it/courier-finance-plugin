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

    ob_start(); ?>
    <!-- Modern Progress Stepper -->
    <nav class="sticky top-0 z-20 bg-white border-b border-gray-200 mb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <ol class="flex items-center justify-center py-4 space-x-4 sm:space-x-8">
                <li class="step-indicator flex items-center">
                    <div class="flex items-center">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium bg-blue-600 text-white">1</span>
                        <span class="ml-2 text-sm font-medium text-blue-600">Header</span>
                    </div>
                </li>
                <li class="step-indicator flex items-center">
                    <div class="flex items-center">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium bg-gray-200 text-gray-600">2</span>
                        <span class="ml-2 text-sm font-medium text-gray-500">Customer</span>
                    </div>
                </li>
                <li class="step-indicator flex items-center">
                    <div class="flex items-center">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium bg-gray-200 text-gray-600">3</span>
                        <span class="ml-2 text-sm font-medium text-gray-500">Items</span>
                    </div>
                </li>
                <li class="step-indicator flex items-center">
                    <div class="flex items-center">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium bg-gray-200 text-gray-600">4</span>
                        <span class="ml-2 text-sm font-medium text-gray-500">Destination</span>
                    </div>
                </li>
                <li class="step-indicator flex items-center">
                    <div class="flex items-center">
                        <span class="flex items-center justify-center w-8 h-8 rounded-full text-xs font-medium bg-gray-200 text-gray-600">5</span>
                        <span class="ml-2 text-sm font-medium text-gray-500">Review</span>
                    </div>
                </li>
            </ol>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-12 lg:gap-8">
            <!-- Main Form Content -->
            <div class="lg:col-span-8">
                <form method="POST" action="<?php echo esc_attr($form_action); ?>" class="space-y-6" id="multi-step-waybill-form" data-ajax-url="<?php echo admin_url('admin-ajax.php'); ?>">
                    <?php if ($is_edit_mode): ?>
                        <input type="hidden" name="waybill_id" value="<?php echo esc_attr($waybill_id); ?>">
                    <?php endif; ?>
                    <?php if ($is_existing_customer): ?>
                        <input type="hidden" name="exsitingCust" value="<?php echo esc_attr($is_existing_customer); ?>">
                    <?php endif; ?>
                    <?php wp_nonce_field($is_edit_mode ? 'update_waybill_nonce' : 'add_waybill_nonce'); ?>
                    
                    <!-- Step 1: Waybill Header -->
                    <div class="step step-1 active" id="step-1">
                        <?php require __DIR__ . '/waybill/steps/step1.php'; ?>
                    </div>
                    
                    <!-- Step 3: Items -->
                    <div class="step step-3 hidden" id="step-3">
                        <?php require __DIR__ . '/waybill/steps/step3.php'; ?>
                    </div>
                    
                    <!-- Step 4: Destination -->
                    <div class="step step-4 hidden" id="step-4">
                        <?php require __DIR__ . '/waybill/steps/step4.php'; ?>
                    </div>
                    
                    <!-- Step 5: Review & Charges -->
                    <div class="step step-5 hidden" id="step-5">
                        <?php require __DIR__ . '/waybill/steps/step5.php'; ?>
                    </div>
                </form>
            </div>

            <!-- Sticky Summary Sidebar (Desktop) -->
            <div class="hidden lg:block lg:col-span-4">
                <div class="sticky top-24" id="waybill-summary">
                    <div class="bg-white rounded-lg border border-gray-200 p-6 space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900">Summary</h3>
                        
                        <div class="space-y-3 text-sm">
                            <div id="summary-customer" class="text-gray-600">
                                <span class="font-medium">Customer:</span> <span class="summary-customer-text">Not selected</span>
                            </div>
                            <div id="summary-destination" class="text-gray-600">
                                <span class="font-medium">Destination:</span> <span class="summary-destination-text">Not selected</span>
                            </div>
                            <div id="summary-delivery" class="text-gray-600">
                                <span class="font-medium">Delivery:</span> <span class="summary-delivery-text">Not selected</span>
                            </div>
                        </div>

                        <div class="border-t pt-4 space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span>Items Subtotal:</span>
                                <span id="summary-items-total">R 0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Shipping:</span>
                                <span id="summary-shipping-total">R 0.00</span>
                            </div>
                            <div class="flex justify-between">
                                <span>Miscellaneous:</span>
                                <span id="summary-misc-total">R 0.00</span>
                            </div>
                            <?php 
                            // Check if current user is authorized to see totals
                            $current_user = wp_get_current_user();
                            $authorized_users = ['Thando', 'mel'];
                            $can_view_totals = in_array($current_user->user_login, $authorized_users);
                            ?>
                            <?php if ($can_view_totals): ?>
                            <div class="border-t pt-2 flex justify-between font-semibold">
                                <span>Total:</span>
                                <span id="summary-grand-total">R 0.00</span>
                            </div>
                            <?php else: ?>
                            <div class="border-t pt-2 flex justify-center">
                                <span class="text-xs text-gray-500 italic">Total hidden - Contact administrator</span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" form="multi-step-waybill-form" class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 disabled:bg-gray-300 disabled:cursor-not-allowed" id="summary-submit-btn" disabled>
                            <?php echo $is_edit_mode ? 'Update Waybill' : 'Create Waybill'; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if user can view totals
            const canViewTotals = <?php echo $can_view_totals ? 'true' : 'false'; ?>;

            // Accordion toggle
            const accordionToggle = document.querySelector('.customer-accordion-toggle');
            const accordionContent = document.querySelector('.customer-details-content');
            if (accordionToggle && accordionContent) {
                accordionToggle.addEventListener('click', function() {
                    accordionContent.classList.toggle('hidden');
                });
            }

            // Flexible input resolver (detects normal or "a" prefixed fields)
            function getCustomerInput(idBase) {
                return document.getElementById(idBase) || document.getElementById('a' + idBase);
            }

            const customerSelect = document.getElementById('customer-select');
            const custIdInput = document.getElementById('cust_id');

            const companyInput = getCustomerInput('company_name');
            const nameInput = getCustomerInput('customer_name');
            const surnameInput = getCustomerInput('customer_surname');
            const cellInput = getCustomerInput('cell');
            const addressInput = getCustomerInput('address');

            // Function to populate customer fields
            function populateCustomerDetails(customerId) {
                const option = customerSelect?.querySelector(`option[value="${customerId}"]`);
                if (option) {
                    if (companyInput) companyInput.value = option.getAttribute('data-company_name') || '';
                    if (nameInput) nameInput.value = option.getAttribute('data-name') || '';
                    if (surnameInput) surnameInput.value = option.getAttribute('data-surname') || '';
                    if (cellInput) cellInput.value = option.getAttribute('data-cell') || '';
                    if (addressInput) addressInput.value = option.getAttribute('data-address') || '';
                    if (custIdInput) custIdInput.value = customerId;
                }
            }

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

            // Function to update summary totals (only for authorized users)
            function updateSummaryTotals() {
                if (!canViewTotals) return; // Skip if user can't view totals
                
                // Calculate totals here (this would connect to your existing calculation logic)
                const summaryGrandTotal = document.getElementById('summary-grand-total');
                if (summaryGrandTotal) {
                    // Your total calculation logic would go here
                    // summaryGrandTotal.textContent = calculatedTotal.toFixed(2);
                }
            }

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
