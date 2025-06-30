//The full path to this file including the folder name is:
// wp-content/plugins/my-plugin/js/waybill-pagination.js
document.addEventListener('click', function (e) {
    // Target both numbered pages and next/previous links
    const paginationLink = e.target.closest('a.page-numbers, .tablenav-pages a');

    if (paginationLink) {
        e.preventDefault();
        const link = paginationLink;
        const url = new URL(link.href);

        // Get page number (either from paged parameter or next/prev links)
        let paged = url.searchParams.get('paged');
        if (!paged) {
            // Handle next/prev links by extracting page number from URL
            const pageMatch = link.href.match(/paged=(\d+)/);
            paged = pageMatch ? pageMatch[1] : 1;
        }

        console.log('Loading page:', paged); // Debug

        fetch(myPluginAjax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'load_waybill_page',
                    paged: paged,
                    nonce: myPluginAjax.nonces.get_waybills_nonce
                }),
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(html => {
                const container = document.querySelector('#waybill-table-container');
                if (container) {
                    container.innerHTML = html;
                    console.log('Pagination updated successfully');

                    // Re-highlight current page
                    highlightCurrentPage();
                }
            })
            .catch(err => {
                console.error('Error:', err);
                alert('Error loading page: ' + err.message);
            });
    }
});

// Helper function to highlight current page
function highlightCurrentPage() {
    document.querySelectorAll('.page-numbers').forEach(link => {
        if (link.classList.contains('current')) {
            link.classList.add('bg-blue-50', 'border-blue-500', 'text-blue-600');
            link.classList.remove('bg-white', 'border-gray-300', 'text-gray-700');
        }
    });
}

function initItemsPerPage() {
    const selector = document.getElementById('items-per-page');
    if (selector) {
        selector.addEventListener('change', function () {
            const itemsPerPage = this.value;
            const url = new URL(window.location.href);
            url.searchParams.set('items_per_page', itemsPerPage);
            url.searchParams.set('paged', 1); // Reset to first page

            // Use AJAX instead of page reload
            fetch(myPluginAjax.ajax_url, {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'load_waybill_page',
                        items_per_page: itemsPerPage,
                        paged: 1,
                        nonce: myPluginAjax.nonces.get_waybills_nonce
                    }),
                })
                .then(response => response.text())
                .then(html => {
                    document.getElementById('Debug').innerHTML = html;
                    initItemsPerPage(); // Reattach event listener
                })
                .catch(console.error);
        });
    }
}

// alert this handleCountryChange(this.value)
function handleCountryChange(value) {
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
                const citySelect = document.getElementById('destination_city');
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
            nonce: myPluginAjax.nonces.get_waybills_nonce // Reuse same nonce unless you separate them
        },
        success: function (response) {
            if (response.success && Array.isArray(response.data)) {
                const deliveryList = document.getElementById('scheduled-deliveries-list');
                deliveryList.innerHTML = ''; // Clear existing

                response.data.forEach(function (delivery, index) {
                    const label = document.createElement('label');
                    //set attribute for for label
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
            } else {
                console.log('No deliveries or invalid response:', response);
            }
        },
        error: function (xhr) {
            console.error('Deliveries AJAX Error:', xhr.responseText);
        }
    });
}

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
    jQuery('input[name="delivery_id"]').val(delivery_id);
    // You can now trigger AJAX, update UI, etc.
}
    // 
// Initialize on page load
document.addEventListener('DOMContentLoaded', highlightCurrentPage);
document.addEventListener('DOMContentLoaded', initItemsPerPage);
document.addEventListener('click', function (e) {
    if (e.target.closest('.deliveryBtn')) {
        const input = e.target.closest('.deliveryBtn').querySelector('input[name="delivery_id"]');
        const selectedDeliveryId = input ? input.value : null;
        handleDeliveryChange(selectedDeliveryId);
    }
});