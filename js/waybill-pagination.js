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