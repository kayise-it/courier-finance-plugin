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
// Global function for handling country changes (using working implementation from waybill)
function handleCountryChange(countryId, fieldName) {
    console.log('handleCountryChange called with:', countryId, 'field:', fieldName);
    if (!countryId) return;

    // Determine which city dropdown to update based on the field name
    let citySelectId = '';
    let isOrigin = false;

    if (fieldName === 'origin' || fieldName === 'origin_country') {
        citySelectId = 'origin_city_select';
        isOrigin = true;
    } else if (fieldName === 'destination' || fieldName === 'destination_country') {
        citySelectId = 'destination_city_select';
        isOrigin = false;
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

    // Use the same approach as the working waybill implementation
    const ajaxUrl = window.ajaxurl || (window.myPluginAjax && window.myPluginAjax.ajax_url);
    const nonce = window.myPluginAjax && window.myPluginAjax.nonces ? window.myPluginAjax.nonces.get_waybills_nonce : '';

    console.log('Using AJAX URL:', ajaxUrl);
    console.log('Using nonce:', nonce);
    console.log('Making AJAX request for', isOrigin ? 'origin' : 'destination', 'cities');

    // Fetch cities for the selected country
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
            console.error('AJAX error:', error);
            console.error('Status:', status);
            console.error('Response:', xhr.responseText);
            citySelect.innerHTML = '<option value="">Error loading cities</option>';
            citySelect.disabled = false;
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

function handleDeliveryChange(delivery_id) {
    console.log('Selected delivery ID:', delivery_id);
    const deliveryInput = document.querySelector('input[name="delivery_id"]');
    // get specialbtn should override the next-step function, because it is a special button, this button is disabled and is only enabled whern deliveryInput is not empty and 
    // the next-step function is enabled when the deliveryInput is not empty and the deliveryInput is not the same as the delivery_id
    const specialDeliveryBtn = document.getElementById('specialDeliveryBtn');
    specialDeliveryBtn.disabled = false;
    specialDeliveryBtn.classList.remove('bg-gray-300', 'text-gray-500');
    specialDeliveryBtn.classList.add('bg-blue-600', 'text-white');

    if (deliveryInput) {
        deliveryInput.value = delivery_id;
    }
}

// Delivery button click handler
document.addEventListener('click', function (e) {
    if (e.target.closest('.deliveryBtn')) {
        const input = e.target.closest('.deliveryBtn').querySelector('input[name="delivery_id"]');
        const selectedDeliveryId = input ? input.value : null;
        if (selectedDeliveryId) {
            handleDeliveryChange(selectedDeliveryId);
        }
    }
});



// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    initItemsPerPage();
});