//The full path to this file including the folder name is:
// wp-content/plugins/my-plugin/js/waybill-pagination.js

// Simple non-AJAX pagination - just handle items per page dropdown
function initItemsPerPage() {
    var selector = document.getElementById('items-per-page');
    if (selector) {
        selector.addEventListener('change', function () {
            var itemsPerPage = this.value;
            var url = new URL(window.location.href);
            url.searchParams.set('items_per_page', itemsPerPage);
            url.searchParams.set('paged', 1); // Reset to first page
            window.location.href = url.toString();
        });
    }
}

// Country change handler
// Global function for handling country changes (using working implementation from waybill)
function handleCountryChange(countryId, fieldName) {
    if (!countryId) return;

    var primaryId = '';
    var fallbackId = '';
    var isOrigin = false;

    if (fieldName === 'origin' || fieldName === 'origin_country') {
        primaryId = 'origin_city_select';
        fallbackId = 'origin_city';
        isOrigin = true;
    } else if (fieldName === 'destination' || fieldName === 'destination_country') {
        primaryId = 'destination_city_select';
        fallbackId = 'destination_city';
        isOrigin = false;
    } else {
        return;
    }

    var citySelect = document.getElementById(primaryId) || document.getElementById(fallbackId);
    if (!citySelect) return;

    // Try preloaded map first for instant update
    var citiesMap = (window.myPluginAjax && myPluginAjax.countryCities) || {};
    var cities = citiesMap && citiesMap[String(countryId)] ? citiesMap[String(countryId)] : [];

    if (Array.isArray(cities) && cities.length) {
        citySelect.innerHTML = '<option value="">Select City</option>';
        cities.forEach(function(city) {
            var option = document.createElement('option');
            option.value = city.id;
            option.textContent = city.city_name;
            citySelect.appendChild(option);
        });
        citySelect.disabled = false;
    } else {
        citySelect.innerHTML = '<option value="">No cities found</option>';
        citySelect.disabled = false;
    }

    // Keep scheduled deliveries refresh for destination changes
    if (!isOrigin) {
        var ajaxUrl2 = window.ajaxurl || (window.myPluginAjax && window.myPluginAjax.ajax_url);
        var list = document.getElementById('scheduled-deliveries-list');
        if (list && ajaxUrl2) {
            list.innerHTML = '<div class="text-gray-500">Loading deliveries...</div>';
            jQuery.ajax({
                url: ajaxUrl2,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_deliveries_by_country_id',
                    country: countryId,
                    nonce: (window.myPluginAjax && window.myPluginAjax.nonces && (myPluginAjax.nonces.deliveries_nonce || myPluginAjax.nonces.get_waybills_nonce)) || ''
                },
                success: function(res){
                    if (res && res.success && res.html) {
                        list.innerHTML = res.html;
                    } else if (res && res.data && res.data.html) {
                        list.innerHTML = res.data.html;
                    } else {
                        list.innerHTML = '<div class="text-gray-500">No deliveries found</div>';
                    }
                },
                error: function(){
                    list.innerHTML = '<div class="text-red-600">Error loading deliveries</div>';
                }
            });
        }
    }
}

// originCountry change handler for cities and destinationCountry change handler for cities
function gaybitch(value, type) {
    var citySelect = document.getElementById(type + '_city_select');
    if (!citySelect) return;

    var citiesMap = (window.myPluginAjax && myPluginAjax.countryCities) || {};
    var cities = citiesMap && citiesMap[String(value)] ? citiesMap[String(value)] : [];

    citySelect.innerHTML = '';
    var defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.textContent = 'Select City';
    citySelect.appendChild(defaultOption);

    cities.forEach(function (city, index) {
        var option = document.createElement('option');
        option.value = city.id;
        option.textContent = city.city_name;
        if (index === 0) option.selected = true;
        citySelect.appendChild(option);
    });
}



// Modal handling
document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('customerModal');
    var openBtn = document.getElementById('customerModalButton');
    var closeBtn = document.getElementById('customerModalClose');

    if (openBtn && closeBtn && modal) {
        openBtn.addEventListener('click', function() { modal.classList.remove('hidden'); });
        closeBtn.addEventListener('click', function() { modal.classList.add('hidden'); });

        // Close when clicking outside modal
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.classList.add('hidden');
            }
        });
    }
});

function handleDeliveryChange(delivery_id) {
    
    // Find and check the radio button for this delivery
    var radioButton = document.querySelector(`input[name="direction_id"][value="${delivery_id}"]`);
    if (radioButton) {
        radioButton.checked = true;
        // Trigger change event to ensure CSS classes update
        radioButton.dispatchEvent(new Event('change', { bubbles: true }));
    }
    
    var deliveryInput = document.querySelector('input[name="delivery_id"]');
    var specialDeliveryBtn = document.getElementById('specialDeliveryBtn');
    
    if (specialDeliveryBtn) {
        specialDeliveryBtn.disabled = false;
        specialDeliveryBtn.classList.remove('bg-gray-300', 'text-gray-500');
        specialDeliveryBtn.classList.add('bg-blue-600', 'text-white');
    }

    if (deliveryInput) {
        deliveryInput.value = delivery_id;
    }
}

// Delivery button click handler
document.addEventListener('click', function (e) {
    if (e.target.closest('.deliveryBtn')) {
        var input = e.target.closest('.deliveryBtn').querySelector('input[name="delivery_id"]');
        var selectedDeliveryId = input ? input.value : null;
        if (selectedDeliveryId) {
            handleDeliveryChange(selectedDeliveryId);
        }
    }
});



// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    initItemsPerPage();
    // Removed auto city-loading on page load to preserve server-selected defaults
});