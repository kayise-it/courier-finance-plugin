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
    <form method="POST" action="<?php echo esc_attr($form_action); ?>" class="" id="multi-step-waybill-form" data-ajax-url="<?php echo admin_url('admin-ajax.php'); ?>">
        <?php if ($is_edit_mode): ?>
            <input type="hidden" name="waybill_id" value="<?php echo esc_attr($waybill_id); ?>">
        <?php endif; ?>
        <?php if ($is_existing_customer): ?>
            <input type="hidden" name="exsitingCust" value="<?php echo esc_attr($is_existing_customer); ?>">
        <?php endif; ?>
        <?php wp_nonce_field($is_edit_mode ? 'update_waybill_nonce' : 'add_waybill_nonce'); ?>
        <!-- Step 1: Waybill Header -->
        <div class="step step-1 active" id="step-1" style="min-width: 500px;">
            <?php require __DIR__ . '/waybill/steps/step1.php'; ?>
        </div>
        
        <!-- Step 3: Items -->
        <div class="step step-3 hidden" id="step-3" style="min-width: 500px;">
            <?php require __DIR__ . '/waybill/steps/step3.php'; ?>
        </div>
        <!-- Step 4: Item Section -->
        <div class="step step-4 hidden" id="step-4" style="min-width: 500px;">
            <?php require __DIR__ . '/waybill/steps/step4.php'; ?>
        </div>
        <!-- Step 5: Charge Basis Section -->
        <div class="step step-5 hidden" id="step-5" style="min-width: 500px;">
            <?php require __DIR__ . '/waybill/steps/step5.php'; ?>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

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

            const nameInput = getCustomerInput('customer_name');
            const surnameInput = getCustomerInput('customer_surname');
            const cellInput = getCustomerInput('cell');
            const addressInput = getCustomerInput('address');

            // Function to populate customer fields
            function populateCustomerDetails(customerId) {
                const option = customerSelect?.querySelector(`option[value="${customerId}"]`);
                if (option) {
                    if (nameInput) nameInput.value = option.getAttribute('data-name') || '';
                    if (surnameInput) surnameInput.value = option.getAttribute('data-surname') || '';
                    if (cellInput) cellInput.value = option.getAttribute('data-cell') || '';
                    if (addressInput) addressInput.value = option.getAttribute('data-address') || '';
                    
                    // Populate email field
                    const emailInput = getCustomerInput('email_address');
                    if (emailInput) {
                        emailInput.value = option.getAttribute('data-email') || '';
                    }
                    
                    // Populate company name field
                    const companyNameInput = getCustomerInput('company_name');
                    if (companyNameInput) {
                        companyNameInput.value = option.getAttribute('data-company-name') || '';
                    }
                    
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
                        
                        // Clear email field
                        const emailInput = getCustomerInput('email_address');
                        if (emailInput) {
                            emailInput.value = '';
                        }
                        
                        // Clear company name field
                        const companyNameInput = getCustomerInput('company_name');
                        if (companyNameInput) {
                            companyNameInput.value = '';
                        }
                        
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
                return true; // Replace with actual validation logic if needed
            }

            // Progress bar / Step indicators
            function updateStepIndicator(activeStep) {
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
