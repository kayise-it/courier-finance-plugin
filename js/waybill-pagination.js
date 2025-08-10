//The full path to this file including the folder name is:
// wp-content/plugins/my-plugin/js/waybill-pagination.js

// Simple non-AJAX pagination - just handle items per page dropdown
function initItemsPerPage() {
    const selector = document.getElementById('items-per-page');
    if (selector) {
        selector.addEventListener('change', function () {
            const itemsPerPage = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('items_per_page', itemsPerPage);
            url.searchParams.set('paged', 1); // Reset to first page
            window.location.href = url.toString();
        });
    }
}

// Country change handler
function handleCountryChange(value) {
    console.log('Selected country:', value);

    jQuery('#countrydestination_id').val(value);

    // 1️⃣ Get Cities
    jQuery.ajax({
        url: myPluginAjax.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'handle_get_cities_for_country',
            country_id: value,
            nonce: myPluginAjax.nonces.get_waybills_nonce
        },
        success: function (response) {
            if (response.success && Array.isArray(response.data)) {
                const citySelect = document.getElementById('destination_city');
                const origin_country = document.getElementById('origin_country');
                if (citySelect) {
                    citySelect.innerHTML = '';


                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = 'Select City';
                    citySelect.appendChild(defaultOption);

                    response.data.forEach(function (city, index) {
                        const option = document.createElement('option');
                        option.value = city.id;
                        option.textContent = city.city_name;
                        if (index === 0) option.selected = true;
                        citySelect.appendChild(option);
                    });
                }
            }
        },
        error: function (xhr) {
            console.error('City AJAX Error:', xhr.responseText);
        }
    });

    // 2️⃣ Get Deliveries
    jQuery.ajax({
        url: myPluginAjax.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'handle_get_countryDeliveries',
            country_id: value,
            nonce: myPluginAjax.nonces.get_waybills_nonce
        },
        success: function (response) {
            if (response.success && Array.isArray(response.data)) {
                const deliveryList = document.getElementById('scheduled-deliveries-list');
                if (deliveryList) {
                    deliveryList.innerHTML = ''; // Clear existing

                    response.data.forEach(function (delivery, index) {
                        const label = document.createElement('label');
                        label.setAttribute('for', `direction_${delivery.direction_id}`);
                        label.className = "deliveryBtn bg-white w-[100px] h-[100px] rounded-[5px] border-2 border-gray-300 cursor-pointer relative flex items-center justify-center text-center text-[11px] font-medium leading-tight hover:shadow-md transition-all duration-200 ";
                        if (index === 0) label.classList.add("border-blue-500", "bg-blue-100", "shadow-lg");

                        console.log('Delivery:', delivery); // Debug

                        label.innerHTML = `
                            <input type="hidden" name="delivery_id" id="delivery_${delivery.delivery_id}" value="${delivery.delivery_id}">
                            <input type="radio" name="direction_id" id="direction_${delivery.direction_id}" value="${delivery.direction_id}" class="sr-only peer" ${index === 0 ? 'checked' : ''}>
                            <div>
                                <div class="font-bold text-[12px]">${delivery.dispatch_date}</div>
                                <div class="text-gray-500">${delivery.status}</div>
                                <div class="text-gray-600 mt-1">${delivery.origin_country} → ${delivery.destination_country}</div>
                            </div>
                        `;

                        deliveryList.appendChild(label);
                    });
                }
            } else {
                console.log('No deliveries or invalid response:', response);
            }
        },
        error: function (xhr) {
            console.error('Deliveries AJAX Error:', xhr.responseText);
        }
    });
}

// originCountry change handler for cities and destinationCountry change handler for cities
function gaybitch(value, type) {
    console.log('Selected country:', value);

    // 1️⃣ Get Cities
    jQuery.ajax({
        url: myPluginAjax.ajax_url,
        type: 'POST',
        dataType: 'json',
        data: {
            action: 'handle_get_cities_for_country',
            country_id: value,
            nonce: myPluginAjax.nonces.get_waybills_nonce
        },
        success: function (response) {
            if (response.success && Array.isArray(response.data)) {

                console.log('Cities:', response.data);
                const citySelect = document.getElementById(type + '_city_select');
                if (citySelect) {
                    citySelect.innerHTML = '';

                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = 'Select City';
                    citySelect.appendChild(defaultOption);

                    response.data.forEach(function (city, index) {
                        const option = document.createElement('option');
                        option.value = city.id;
                        option.textContent = city.city_name;
                        if (index === 0) option.selected = true;
                        citySelect.appendChild(option);
                    });
                }
            }
        },
        error: function (xhr) {
            console.error('City AJAX Error:', xhr.responseText);
        }
    });
}



// Modal handling
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('customerModal');
    const openBtn = document.getElementById('customerModalButton');
    const closeBtn = document.getElementById('customerModalClose');

    if (openBtn && closeBtn && modal) {
        openBtn.addEventListener('click', () => modal.classList.remove('hidden'));
        closeBtn.addEventListener('click', () => modal.classList.add('hidden'));

        // Close when clicking outside modal
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    }
});

function handleDeliveryChange(delivery_id, direction_id) {
    console.log('Selected delivery ID:', delivery_id, 'Direction ID:', direction_id);
    
    // Set the delivery ID in the hidden input
    const selectedDeliveryIdInput = document.getElementById('selected_delivery_id');
    if (selectedDeliveryIdInput) {
        selectedDeliveryIdInput.value = delivery_id;
    }
    
    // Set the direction ID radio button
    if (direction_id) {
        const selectedDirectionIdRadio = document.getElementById(`direction_${direction_id}`);
        if (selectedDirectionIdRadio) {
            selectedDirectionIdRadio.checked = true;
        }
    }

    // Enable the next step button
    const specialDeliveryBtn = document.getElementById('specialDeliveryBtn');
    if (specialDeliveryBtn) {
        specialDeliveryBtn.disabled = false;
        specialDeliveryBtn.classList.remove('bg-gray-300', 'text-gray-500');
        specialDeliveryBtn.classList.add('bg-blue-600', 'text-white');
        
        // Update button text
        const btnText = document.getElementById('next-btn-text');
        if (btnText) {
            btnText.textContent = 'Next: Review & Charges';
        }
    }
    
    // Update summary if user is authorized (check if function exists and user can view totals)
    if (typeof updateSummaryTotals === 'function') {
        updateSummaryTotals();
    }
}

// Delivery button click handler
document.addEventListener('click', function (e) {
    if (e.target.closest('.deliveryBtn')) {
        const deliveryBtn = e.target.closest('.deliveryBtn');
        const deliveryInput = deliveryBtn.querySelector('input[name="delivery_id"]');
        const directionInput = deliveryBtn.querySelector('input[name="direction_id"]');
        
        const selectedDeliveryId = deliveryInput ? deliveryInput.value : null;
        const selectedDirectionId = directionInput ? directionInput.value : null;
        
        if (selectedDeliveryId && selectedDirectionId) {
            handleDeliveryChange(selectedDeliveryId, selectedDirectionId);
        }
    }
});



// Section Navigation for Progressive Multi-Section Form
function initSectionNavigation() {
    // Progressive form navigation
    let currentSection = 1;
    const totalSections = 4;
    
    const sections = document.querySelectorAll('.section-card');
    const prevBtn = document.getElementById('prev-section');
    const nextBtn = document.getElementById('next-section');
    const createBtn = document.getElementById('create-waybill');
    const progressBar = document.getElementById('progress-bar');
    const stepName = document.getElementById('current-step-name');
    const nextText = document.getElementById('next-text');

    // Debug logging
    console.log('Section Navigation initialized:', {
        sections: sections.length,
        prevBtn: !!prevBtn,
        nextBtn: !!nextBtn,
        createBtn: !!createBtn,
        progressBar: !!progressBar,
        stepName: !!stepName,
        nextText: !!nextText
    });

    const sectionNames = [
        'Start',
        'Customer Details',
        'Items & Weight', 
        'Destination & Completion'
    ];

    const nextButtonTexts = [
        'Continue to Customer Details',
        'Continue to Items & Weight',
        'Continue to Destination',
        'Create Waybill'
    ];

    function updateProgress() {
        if (progressBar && stepName && nextText) {
            const progress = (currentSection / totalSections) * 100;
            progressBar.style.width = progress + '%';
            stepName.textContent = sectionNames[currentSection - 1];
            nextText.textContent = nextButtonTexts[currentSection - 1];
        }
    }

    function showSection(sectionNum) {
        console.log('Showing section:', sectionNum, 'Total sections found:', sections.length);
        
        // First, ensure ALL sections are hidden and not active
        sections.forEach((section) => {
            section.classList.add('hidden');
            section.classList.remove('active');
            section.style.display = 'none'; // Force hide
        });
        
        // Then show only the target section
        sections.forEach((section, index) => {
            if (index + 1 === sectionNum) {
                console.log('Showing section', index + 1, section);
                section.classList.remove('hidden');
                section.classList.add('active');
                section.style.display = 'block'; // Force show
            }
        });

        // Update navigation
        if (prevBtn) prevBtn.classList.toggle('hidden', sectionNum === 1);
        if (nextBtn) nextBtn.classList.toggle('hidden', sectionNum === totalSections);
        if (createBtn) createBtn.classList.toggle('hidden', sectionNum !== totalSections);

        updateProgress();
        
        // Update contextual customer message based on current step
        if (typeof window.updateCustomerContextualMessage === 'function') {
            window.updateCustomerContextualMessage();
        }
        
        // Update required fields for the new step
        if (typeof window.updateRequiredFieldsForCurrentStep === 'function') {
            window.updateRequiredFieldsForCurrentStep();
        }
        
        // Force calculation update for step 3 when it becomes visible
        if (sectionNum === 3) {
            setTimeout(() => {
                // Try to trigger calculations for step 3
                const massInput = document.getElementById('total_mass_kg');
                const dimensionInputs = ['item_length', 'item_width', 'item_height'];
                
                if (massInput && massInput.value) {
                    massInput.dispatchEvent(new Event('input'));
                }
                
                let allDimensionsFilled = true;
                dimensionInputs.forEach(id => {
                    const input = document.getElementById(id);
                    if (!input || !input.value) {
                        allDimensionsFilled = false;
                    }
                });
                
                if (allDimensionsFilled) {
                    const lengthInput = document.getElementById('item_length');
                    if (lengthInput) {
                        lengthInput.dispatchEvent(new Event('input'));
                    }
                }
            }, 200);
        }
        
        // Smooth scroll to top of section
        const activeSection = document.querySelector('.section-card.active');
        console.log('Active section after update:', activeSection);
        if (activeSection) {
            activeSection.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'start' 
            });
        }
    }

    // Section validation
    function validateCurrentSection() {
        // Use the new smart validation system if available
        if (typeof window.validateCurrentStep === 'function') {
            return window.validateCurrentStep();
        }
        
        // Fallback to legacy validation
        switch(currentSection) {
            case 1:
                // Waybill details validation - only validate visible required fields
                const waybillNo = document.getElementById('waybill_no')?.value;
                if (!waybillNo?.trim()) {
                    alert('Waybill number is required');
                    return false;
                }
                break;
            case 2:
                // Customer validation - but only if fields are visible
                const customerForm = document.getElementById('customer-details-form');
                const newCustomerToggle = document.getElementById('new-customer-toggle')?.checked;
                
                if (newCustomerToggle && customerForm && !customerForm.classList.contains('hidden')) {
                    // Validate new customer fields only if they're visible
                    const customerName = document.getElementById('customer_name')?.value;
                    const customerSurname = document.getElementById('customer_surname')?.value;
                    const cell = document.getElementById('cell')?.value;
                    if (!customerName?.trim() || !customerSurname?.trim() || !cell?.trim()) {
                        alert('Please fill in customer name, surname, and phone number');
                        return false;
                    }
                } else {
                    // Check if a customer is selected
                    const custId = document.getElementById('cust_id')?.value;
                    if (!custId || custId === '0') {
                        alert('Please select a customer or toggle "New Customer" to add one');
                        return false;
                    }
                }
                break;
            case 4:
                // Destination validation
                const warehoused = document.getElementById('warehoused_option')?.checked;
                const country = document.getElementById('stepDestinationSelect')?.value || '';
                const delivery = document.querySelector('input[name="delivery_id"]')?.value || '';
                const direction = document.querySelector('input[name="direction_id"]:checked')?.value || '';
                if (!warehoused && (!country || !delivery || !direction)) {
                    alert('Please select a destination country and a scheduled delivery before continuing.');
                    return false;
                }
                break;
        }
        return true;
    }

    // Navigation event listeners
    if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
            console.log('Next button clicked! Current section:', currentSection);
            e.preventDefault();
            
            if (validateCurrentSection()) {
                console.log('Validation passed, moving to next section');
                currentSection = Math.min(currentSection + 1, totalSections);
                showSection(currentSection);
            } else {
                console.log('Validation failed');
            }
        });
    } else {
        console.error('Next button not found!');
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', function(e) {
            console.log('Previous button clicked! Current section:', currentSection);
            e.preventDefault();
            currentSection = Math.max(currentSection - 1, 1);
            showSection(currentSection);
        });
    }

    // Initialize first section
    if (sections.length > 0) {
        showSection(currentSection);
    }
}

// Customer Search & Selection Functionality
function initCustomerSearch() {
    const toggle = document.getElementById('new-customer-toggle');
    const searchContainer = document.getElementById('customer-search-container');
    const customerForm = document.getElementById('customer-details-form');
    const customerSelect = document.getElementById('customer-select');
    const selectedDisplay = document.getElementById('selected-customer-display');
    const clearButton = document.getElementById('clear-customer-selection');
    const custIdInput = document.getElementById('cust_id');
    
    if (!toggle || !searchContainer || !customerForm || !customerSelect) return;

    let allCustomers = [];
    let selectedCustomer = null;

    // Load all customers on initialization
    loadAllCustomers();

    function loadAllCustomers() {
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
                if (response.success && response.data) {
                    allCustomers = response.data;
                    populateCustomerSelect(allCustomers);
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

    function populateCustomerSelect(customers) {
        customerSelect.innerHTML = '<option value="">Select a customer or type to search...</option>';
        
        customers.forEach(customer => {
            const option = document.createElement('option');
            option.value = customer.id;
            option.textContent = `${customer.name} ${customer.surname} - ${customer.company_name || 'No company'} (${customer.cell})`;
            option.setAttribute('data-customer', JSON.stringify(customer));
            customerSelect.appendChild(option);
        });
        
        // Make select searchable by converting to searchable dropdown
        makeSelectSearchable();
    }

    function makeSelectSearchable() {
        // Create a searchable input that filters the select options
        const wrapper = document.createElement('div');
        wrapper.className = 'relative';
        
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Type to search customers or click to browse...';
        searchInput.className = 'w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm';
        
        const dropdown = document.createElement('div');
        dropdown.className = 'absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden';
        
        customerSelect.parentNode.insertBefore(wrapper, customerSelect);
        wrapper.appendChild(searchInput);
        wrapper.appendChild(dropdown);
        customerSelect.style.display = 'none';

        function filterOptions(query) {
            dropdown.innerHTML = '';
            
            const filtered = allCustomers.filter(customer => {
                const searchText = `${customer.name} ${customer.surname} ${customer.company_name || ''} ${customer.cell} ${customer.email_address || ''}`.toLowerCase();
                return searchText.includes(query.toLowerCase());
            });

            if (filtered.length === 0) {
                dropdown.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">No customers found</div>';
            } else {
                filtered.forEach(customer => {
                    const option = document.createElement('div');
                    option.className = 'p-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0';
                    option.innerHTML = `
                        <div class="font-medium text-gray-900">${customer.name} ${customer.surname}</div>
                        <div class="text-sm text-gray-600">${customer.company_name || 'No company'} • ${customer.cell}</div>
                    `;
                    option.addEventListener('click', () => selectCustomer(customer));
                    dropdown.appendChild(option);
                });
            }
            
            dropdown.classList.remove('hidden');
        }

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length === 0) {
                dropdown.classList.add('hidden');
            } else {
                filterOptions(query);
            }
        });

        searchInput.addEventListener('click', function() {
            if (this.value.trim() === '') {
                filterOptions(''); // Show all customers
            }
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!wrapper.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    }

    function selectCustomer(customer) {
        selectedCustomer = customer;
        
        // Update display elements
        const nameEl = document.getElementById('selected-customer-name');
        const detailsEl = document.getElementById('selected-customer-details');
        
        if (nameEl) nameEl.textContent = `${customer.name} ${customer.surname}`;
        if (detailsEl) detailsEl.textContent = `${customer.company_name || 'No company'} • ${customer.cell}`;
        
        // Set customer ID
        if (custIdInput) {
            custIdInput.value = customer.id;
            console.log('Set customer ID to:', customer.id);
        }
        
        // Update search input to show selected customer
        const searchInput = customerSelect.parentNode.querySelector('input[type="text"]');
        if (searchInput) {
            searchInput.value = `${customer.name} ${customer.surname}`;
        }
        
        // Hide dropdown, show selection
        const dropdown = customerSelect.parentNode.querySelector('.absolute');
        if (dropdown) dropdown.classList.add('hidden');
        if (selectedDisplay) selectedDisplay.classList.remove('hidden');
        
        // Update summary
        if (typeof updateCustomerSummary === 'function') {
            updateCustomerSummary(customer);
        }
    }

    function clearCustomerSelection() {
        selectedCustomer = null;
        
        if (custIdInput) custIdInput.value = '';
        if (selectedDisplay) selectedDisplay.classList.add('hidden');
        
        // Clear search input
        const searchInput = customerSelect.parentNode.querySelector('input[type="text"]');
        if (searchInput) searchInput.value = '';
        
        // Clear customer form fields
        ['customer_name', 'customer_surname', 'company_name', 'cell', 'email_address', 'address'].forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) field.value = '';
        });
        
        // Update summary
        if (typeof updateCustomerSummary === 'function') {
            updateCustomerSummary(null);
        } else {
            // Fallback: directly update the sidebar with contextual message
            const customerText = document.querySelector('.summary-customer-text');
            if (customerText) {
                if (typeof updateCustomerContextualMessage === 'function') {
                    updateCustomerContextualMessage();
                } else {
                    customerText.textContent = 'Not selected';
                }
            }
        }
    }

    // Toggle between new customer and search
    toggle.addEventListener('change', function() {
        if (this.checked) {
            // New customer mode
            searchContainer.classList.add('hidden');
            customerForm.classList.remove('hidden');
            clearCustomerSelection();
            
            // Update sidebar to show "New Customer"
            if (typeof updateCustomerSummary === 'function') {
                updateCustomerSummary({ id: 'new', name: 'New', surname: 'Customer', company_name: '', cell: '', email_address: '' });
            } else {
                const customerText = document.querySelector('.summary-customer-text');
                if (customerText) {
                    customerText.textContent = 'New Customer';
                }
            }
        } else {
            // Search mode
            searchContainer.classList.remove('hidden');
            customerForm.classList.add('hidden');
            clearCustomerSelection();
            
            // Clear sidebar
            if (typeof updateCustomerSummary === 'function') {
                updateCustomerSummary(null);
            } else {
                const customerText = document.querySelector('.summary-customer-text');
                if (customerText) {
                    customerText.textContent = 'Complete Step 2 to select customer';
                }
            }
        }
    });

    // Search functionality
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 2) {
                hideSearchResults();
                return;
            }

            // Show spinner
            const spinner = document.getElementById('search-spinner');
            if (spinner) spinner.classList.remove('hidden');
            
            searchTimeout = setTimeout(() => {
                performCustomerSearch(query);
            }, 300);
        });
    }

    // Clear selection
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            clearCustomerSelection();
        });
    }
}

// Debug function for testing section visibility
window.debugSections = function() {
    const sections = document.querySelectorAll('.section-card');
    console.log('=== SECTION DEBUG ===');
    sections.forEach((section, index) => {
        const isHidden = section.classList.contains('hidden');
        const isActive = section.classList.contains('active');
        const sectionId = section.id;
        const sectionContent = section.querySelector('.p-6')?.innerHTML.substring(0, 100) + '...';
        console.log(`Section ${index + 1} (${sectionId}):`, {
            hidden: isHidden,
            active: isActive,
            content: sectionContent
        });
    });
    console.log('=== END DEBUG ===');
};

document.addEventListener('DOMContentLoaded', function () {
    initItemsPerPage();
    initSectionNavigation(); // Add section navigation
    // initCustomerSearch(); // Disabled - using inline customer search in step2.php instead
    
    // Add debug button for testing (you can call window.debugSections() in console)
    console.log('Waybill form loaded. Use window.debugSections() to debug section visibility.');
});
