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

    // Decode JSON values safely (handles string JSON or already-parsed arrays)
    $waybill_raw = $atts['waybill'] ?? '{}';
    if (is_array($waybill_raw)) {
        $waybill = $waybill_raw;
    } else {
        $waybill = json_decode(stripslashes((string)$waybill_raw), true);
    }
    
    // Load waybill items if in edit mode
    $waybill_items = [];
    if ($is_edit_mode && !empty($waybill['waybill_no'])) {
        global $wpdb;
        $items_table = $wpdb->prefix . 'kit_waybill_items';
        $items_sql = $wpdb->prepare("SELECT * FROM $items_table WHERE waybillno = %d", $waybill['waybill_no']);
        $waybill_items = $wpdb->get_results($items_sql, ARRAY_A);
    }
    
    // Make waybill_items available globally for included files
    global $waybill_items;
    
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
        
        <!-- Error Display Container -->
        <div id="form-errors" class="hidden mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Please fix the following errors:</h3>
                    <div id="error-list" class="mt-2 text-sm text-red-700">
                        <!-- Error messages will be inserted here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Display Container -->
        <div id="form-success" class="hidden mb-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">Success!</h3>
                    <div id="success-message" class="mt-2 text-sm text-green-700">
                        <!-- Success message will be inserted here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 1: Waybill Header -->
        <div class="step step-1 active" id="step-1">
            <?php require __DIR__ . '/waybill/steps/step1.php'; ?>
        </div>
        
        <!-- Step 3: Items -->
        <div class="step step-3 hidden" id="step-3">
            <?php require __DIR__ . '/waybill/steps/step3.php'; ?>
        </div>
        <!-- Step 4: Item Section -->
        <div class="step step-4 hidden" id="step-4">
            <?php require __DIR__ . '/waybill/steps/step4.php'; ?>
        </div>
        <!-- Step 5: Charge Basis Section -->
        <div class="step step-5 hidden" id="step-5">
            <?php require __DIR__ . '/waybill/steps/step5.php'; ?>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Error handling and validation system
            const form = document.getElementById('multi-step-waybill-form');
            const errorContainer = document.getElementById('form-errors');
            const errorList = document.getElementById('error-list');
            const successContainer = document.getElementById('form-success');
            const successMessage = document.getElementById('success-message');

            // Display server-provided errors when redirected from non-AJAX submit
            (function hydrateServerErrors() {
                try {
                    const params = new URLSearchParams(window.location.search);
                    const encoded = params.get('form_errors');
                    if (encoded) {
                        const decoded = JSON.parse(atob(decodeURIComponent(encoded)));
                        if (Array.isArray(decoded) && decoded.length) {
                            decoded.forEach(err => {
                                showError(err.message, err.field || null);
                            });
                        }
                        // Clean the URL to avoid re-showing on refresh
                        params.delete('form_errors');
                        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                        window.history.replaceState({}, document.title, newUrl);
                    }
                } catch (e) {
                    // ignore URL decoding errors
                }
            })();

            // Validation rules
            const validationRules = {
                'customer_name': {
                    required: true,
                    message: 'Customer name is required'
                },
                'customer_surname': {
                    required: true,
                    message: 'Customer surname is required'
                },
                'cell': {
                    required: true,
                    message: 'Cell phone number is required'
                },
                'address': {
                    required: true,
                    message: 'Address is required'
                },
                'destination_country': {
                    required: function() {
                        return !document.getElementById('warehoused_option')?.checked;
                    },
                    message: 'Destination country is required for non-warehoused items'
                },
                'destination_city': {
                    required: function() {
                        return !document.getElementById('warehoused_option')?.checked;
                    },
                    message: 'Destination city is required for non-warehoused items'
                }
            };

            // Show error message
            function showError(message, fieldId = null) {
                errorContainer.classList.remove('hidden');
                const errorItem = document.createElement('div');
                errorItem.className = 'error-item';
                if (fieldId) {
                    errorItem.innerHTML = `<a href="javascript:void(0)" data-field="${fieldId}" class="error-link text-red-600 hover:text-red-800 underline cursor-pointer">${message}</a>`;
                    highlightField(fieldId, true);
                } else {
                    errorItem.textContent = message;
                }
                errorList.appendChild(errorItem);
                
                // Scroll to error container
                errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // Show success message
            function showSuccess(message) {
                successContainer.classList.remove('hidden');
                successMessage.textContent = message;
                successContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            // Clear all errors
            function clearErrors() {
                errorContainer.classList.add('hidden');
                errorList.innerHTML = '';
                clearFieldHighlights();
            }

            // Clear success message
            function clearSuccess() {
                successContainer.classList.add('hidden');
                successMessage.textContent = '';
            }

            // Highlight field with error
            function highlightField(fieldId, hasError = false) {
                const field = document.getElementById(fieldId);
                if (field) {
                    if (hasError) {
                        field.classList.add('border-red-500', 'bg-red-50');
                        field.classList.remove('border-gray-300', 'focus:border-blue-500');
                    } else {
                        field.classList.remove('border-red-500', 'bg-red-50');
                        field.classList.add('border-gray-300', 'focus:border-blue-500');
                    }
                }
            }

            // Clear all field highlights
            function clearFieldHighlights() {
                document.querySelectorAll('.border-red-500').forEach(field => {
                    field.classList.remove('border-red-500', 'bg-red-50');
                    field.classList.add('border-gray-300', 'focus:border-blue-500');
                });
            }

            // Validate a single field
            function validateField(fieldId) {
                const field = document.getElementById(fieldId);
                if (!field) return true;

                const rule = validationRules[fieldId];
                if (!rule) return true;

                let isRequired = rule.required;
                if (typeof isRequired === 'function') {
                    isRequired = isRequired();
                }

                if (isRequired && (!field.value || field.value.trim() === '')) {
                    highlightField(fieldId, true);
                    return false;
                } else {
                    highlightField(fieldId, false);
                    return true;
                }
            }

            // Validate all fields
            function validateForm() {
                clearErrors();
                let isValid = true;
                const errors = [];

                // Validate each field
                Object.keys(validationRules).forEach(fieldId => {
                    if (!validateField(fieldId)) {
                        isValid = false;
                        errors.push({
                            message: validationRules[fieldId].message,
                            fieldId: fieldId
                        });
                    }
                });

                // Show errors if any
                if (!isValid) {
                    errors.forEach(error => {
                        showError(error.message, error.fieldId);
                    });
                }

                return isValid;
            }

            // Always run client-side validation before any submission (AJAX or non-AJAX)
            const multiStepForm = document.getElementById('multi-step-waybill-form');
            if (multiStepForm) {
                multiStepForm.addEventListener('submit', function(e) {
                    // If invalid, prevent navigation to admin-post and show inline errors
                    if (!validateForm()) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                }, { capture: true });
            }

            // Defensive: delegate submit guard in case the direct listener misses
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (!form) return;
                if (form.id === 'multi-step-waybill-form' || form.getAttribute('data-role') === 'waybill-form') {
                    if (typeof validateForm === 'function') {
                        if (!validateForm()) {
                            e.preventDefault();
                            e.stopPropagation();
                            return false;
                        }
                    } else {
                        // Minimal fallback: ensure all [required] have values
                        const requiredFields = form.querySelectorAll('[required]');
                        let ok = true;
                        requiredFields.forEach(function(f) {
                            if (ok && (!f.value || String(f.value).trim() === '')) {
                                ok = false;
                            }
                        });
                        if (!ok) {
                            e.preventDefault();
                            e.stopPropagation();
                            return false;
                        }
                    }
                }
            }, true);

            // Real-time validation on field blur
            Object.keys(validationRules).forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('blur', () => {
                        validateField(fieldId);
                    });
                    field.addEventListener('input', () => {
                        if (field.classList.contains('border-red-500')) {
                            validateField(fieldId);
                        }
                    });
                }
            });

            // Error link click handler
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('error-link')) {
                    e.preventDefault();
                    const fieldId = e.target.getAttribute('data-field');
                    if (fieldId) {
                        scrollToField(fieldId);
                    }
                }
            });

            // Function to scroll to field and switch to correct step if needed
            function scrollToField(fieldId) {
                const field = document.getElementById(fieldId);
                if (!field) return;

                // Find which step contains this field
                const step = field.closest('.step');
                if (step && !step.classList.contains('active')) {
                    // Switch to the step containing the field
                    const currentStep = document.querySelector('.step.active');
                    if (currentStep) {
                        switchStep(currentStep, step);
                    }
                }

                // Scroll to the field with a small delay to ensure step switch is complete
                setTimeout(() => {
                    field.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center',
                        inline: 'nearest'
                    });
                    
                    // Add a brief flash effect to draw attention
                    field.style.transition = 'box-shadow 0.3s ease';
                    field.style.boxShadow = '0 0 0 3px rgba(239, 68, 68, 0.3)';
                    setTimeout(() => {
                        field.style.boxShadow = '';
                    }, 1000);
                }, 100);
            }

            // Warehoused checkbox change handler
            const warehousedCheckbox = document.getElementById('warehoused_option');
            if (warehousedCheckbox) {
                warehousedCheckbox.addEventListener('change', () => {
                    // Re-validate destination fields when warehoused status changes
                    validateField('destination_country');
                    validateField('destination_city');
                });
            }

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

            const nameInput = getCustomerInput('name');
            const surnameInput = getCustomerInput('surname');
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
                    const targetStep = targetId ? document.getElementById(targetId) : currentStep?.previousElementSibling;

                    switchStep(currentStep, targetStep);
                });
            });

            // Enhanced step validation
            function validateStep(step) {
                // Clear any previous errors
                clearErrors();
                
                // Get all required fields in the current step
                const requiredFields = step.querySelectorAll('[required], .required');
                let isValid = true;
                const errors = [];

                requiredFields.forEach(field => {
                    if (!field.value || field.value.trim() === '') {
                        isValid = false;
                        const fieldName = field.getAttribute('name') || field.getAttribute('id') || 'this field';
                        errors.push({
                            message: `${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)} is required`,
                            fieldId: field.getAttribute('id')
                        });
                    }
                });

                // Show errors if any
                if (!isValid) {
                    errors.forEach(error => {
                        showError(error.message, error.fieldId);
                    });
                    return false;
                }

                return true;
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
            <button type="button" class="remove-misc-btn bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-sm font-medium transition-colors duration-200">×</button>`;
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
            jQuery(document).ready(function($) {
                $('#multi-step-waybill-form').on('submit', function(e) {
                    e.preventDefault();

                    var $form = $(this);
                    var $submitBtn = $form.find('button[type="submit"]');
                    var originalBtnText = $submitBtn.html();

                    // Clear previous messages
                    $('#form-errors').addClass('hidden');
                    $('#form-success').addClass('hidden');
                    $('.border-red-500').removeClass('border-red-500 bg-red-50').addClass('border-gray-300');

                    // Client-side validation
                    if (!validateForm()) {
                        return false;
                    }

                    // Show loading state
                    $submitBtn.prop('disabled', true).html('<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Processing...');

                    // Add the AJAX nonce to the form data
                    var formData = new FormData(this);
                    formData.append('action', 'process_waybill_form');
                    formData.append('_ajax_nonce', myPluginAjax.nonces.add);

                    $.ajax({
                            url: myPluginAjax.ajax_url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json'
                        })
                        .done(function(response) {
                            if (response.success) {
                                // Success handling
                                showSuccess(response.data.message);
                                
                                if (response.data.waybill_id) {
                                    // Refresh the waybills table if it exists
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
                                            showError('Waybill saved, but failed to refresh the table.');
                                        });
                                }
                                
                                // Reset form after successful submission
                                setTimeout(() => {
                                    $form[0].reset();
                                    clearErrors();
                                    clearSuccess();
                                    // Go back to step 1
                                    $('.step').removeClass('active').addClass('hidden');
                                    $('#step-1').removeClass('hidden').addClass('active');
                                }, 3000);
                            } else {
                                // Error handling with field highlighting
                                if (response.data && response.data.errors) {
                                    // Handle field-specific errors
                                    response.data.errors.forEach(function(error) {
                                        showError(error.message, error.field);
                                    });
                                } else {
                                    showError(response.data.message || 'An error occurred while saving the waybill.');
                                }
                            }
                        })
                        .fail(function(xhr, status, error) {
                            let errorMessage = 'An error occurred while processing your request.';
                            
                            // Try to parse error response
                            if (xhr.responseJSON && xhr.responseJSON.data) {
                                errorMessage = xhr.responseJSON.data.message || errorMessage;
                            } else if (xhr.responseText) {
                                try {
                                    const response = JSON.parse(xhr.responseText);
                                    errorMessage = response.data?.message || errorMessage;
                                } catch (e) {
                                    errorMessage = 'Network error: ' + error;
                                }
                            }
                            
                            showError(errorMessage);
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
                            showError('Error loading waybills: ' + error);
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
                        <button class="delete-waybill bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm font-medium transition-colors duration-200 ml-3" 
                                data-waybill-id="${waybill.waybill_id}">Delete</button>
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
