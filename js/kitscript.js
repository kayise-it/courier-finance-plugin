//file location: js/kitscript.js
function toggleDropdownDeliveryStatus(delivery_id) {
    const dropdown = document.getElementById('delivery-status-dropdown-' + delivery_id);
    dropdown.classList.toggle('hidden');
}

function hello() {
    alert("Hello");
}

function fetchRatePerKg() {
    const total_mass_kg = parseFloat(jQuery('#total_mass_kg').val()) || 0;
    const country_id = jQuery('#countrydestination_id').val(); // Hidden input or radio
    let current_rate = jQuery('#current_rate').val();


    if (total_mass_kg > 0) {
        jQuery.ajax({
            url: myPluginAjax.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'handle_get_price_per_kg',
                total_mass_kg: total_mass_kg,
                origin_country_id: country_id,
                nonce: myPluginAjax.nonces.get_waybills_nonce
            },
            success: function (response) {

                if (response.success) {
                    const rate = response.data.rate_per_kg;

                    console.log('Rate per kg: R' + rate);
                    current_rate = rate;

                    jQuery('#mass_charge_display').text(rate);
                    jQuery('#mass_charge').val(rate * total_mass_kg);
                    jQuery('#mass_rate').val(rate);
                    jQuery('#current_rate').val(rate);
                } else {
                    console.warn(response.data.message);
                }
            },
            error: function (xhr, status, error) {
                console.error('AJAX Error:', error);
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const massInput = document.getElementById('total_mass_kg');
    if (massInput) {
        massInput.addEventListener('click', fetchRatePerKg);
    }

    let timeout;
    jQuery('#total_mass_kg').on('input', function () {
        clearTimeout(timeout);
        timeout = setTimeout(fetchRatePerKg, 0); // Wait 500ms after user stops typing
    });
    // Dispatch date validation
    const dispatchDateInput = document.getElementById('dispatch_date');
    const today = new Date().toISOString().split('T')[0];

    if (dispatchDateInput) {
        // Set min date attribute
        dispatchDateInput.min = today;

        // Additional validation on form submission
        document.querySelector('form').addEventListener('submit', function (e) {
            const selectedDate = dispatchDateInput.value;
            if (selectedDate < today) {
                e.preventDefault();
                dispatchDateInput.focus();
            }
        });
    }

    // VAT checkbox functionality
    const vatCheckbox = document.getElementById('vat_include');
    const optionz = document.querySelectorAll('.optionz');

    function toggleOptionzDisabled() {
        if (vatCheckbox && vatCheckbox.checked) {
            optionz.forEach(function (opt) {
                opt.disabled = true;
            });
        } else if (vatCheckbox) {
            optionz.forEach(function (opt) {
                opt.disabled = false;
            });
        }
    }

    if (vatCheckbox) {
        vatCheckbox.addEventListener('change', toggleOptionzDisabled);
        // Run on page load in case VAT is pre-checked
        toggleOptionzDisabled();
    }


    // Delete waybill
    jQuery(document).on('click', '.delete-waybill', function (e) {
        e.preventDefault();

        const $form = jQuery(this).closest('form');
        const waybillId = $form.find('input[name="waybill_id"]').val();
        const waybillNo = $form.find('input[name="waybill_no"]').val();
        const deliveryId = $form.find('input[name="delivery_id"]').val();
        const userId = $form.find('input[name="user_id"]').val();
        const nonce = $form.find('input[name="_wpnonce"]').val();
        const $row = jQuery(this).closest('tr');

        if (!confirm('Are you sure you want to delete this waybill?')) {
            return;
        }

        jQuery.ajax({
            url: myPluginAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_waybill',
                waybill_id: waybillId,
                waybill_no: waybillNo,
                delivery_id: deliveryId,
                user_id: userId,
                _ajax_nonce: nonce
            },
            beforeSend: function () {
                $row.css('opacity', '0.5');
            },
            success: function (response) {
                if (response.success) {
                    /* After deleting waybill reload the table #waybill-table-container */
                    jQuery('#waybill-table-parent').load(window.location.href + ' #waybill-table-container');
                } else {
                    alert('Error: ' + (response.data?.message || 'Failed to delete waybill'));
                    $row.css('opacity', '1');
                }
            },
            error: function (xhr, status, error) {
                alert('Error: ' + error);
                $row.css('opacity', '1');
            }
        });
    });

    // Select all rows
    jQuery(document).on('change', '.selectRow', function () {
        // Check if all rows are checked
        const allChecked = jQuery('.selectRow:checked').length === jQuery('.selectRow').length;
        jQuery('.selectAllRows').prop('checked', allChecked);
    });

    // Select all rows
    jQuery(document).on('change', '.selectAllRows', function () {
        // Check if all rows are checked
        const allChecked = jQuery(this).prop('checked');
        jQuery('.selectRow').prop('checked', allChecked);
    });

    // Delete delivery
    jQuery(document).on('click', '.delete-delivery', function (e) {
        e.preventDefault();
        const deliveryId = jQuery(this).data('delivery-id');
        if (!confirm('Are you sure you want to delete this delivery?')) {
            return;
        }
    });

    // Warehoused checkbox
    jQuery(document).on('change', '#warehoused_option', function () {
        const scheduledDeliveriesList = document.getElementById('scheduled-deliveries-list');
        if (jQuery(this).is(':checked')) {
            scheduledDeliveriesList.classList.add('hidden');
            const specialDeliveryBtn = document.getElementById('specialDeliveryBtn');
            specialDeliveryBtn.disabled = false;
            specialDeliveryBtn.classList.remove('bg-gray-300', 'text-gray-500');
            specialDeliveryBtn.classList.add('bg-blue-600', 'text-white');
        } else {
            scheduledDeliveriesList.classList.remove('hidden');
        }
    });

    jQuery('#enable_price_manipulator').on('change', function () {
        let current_rate = parseFloat(jQuery('#current_rate').val().replace(',', '.')) || 0;
        if (this.checked) {
            /* When mass_charge_manipulator is changed, then alert the value after 2 seconds */
            jQuery('#mass_charge_manipulator').on('input', function () {
                const manipulatorVal = parseFloat(jQuery('#mass_charge_manipulator').val()) || 0;
                const rateVal = parseFloat(current_rate) || 0;
                const sum = rateVal + manipulatorVal;
                jQuery('#manipulated_mass_charge_display').text('+ R' + manipulatorVal + ' = ' + sum);

                /* Now update the #mass_charge where we take now the new value 'sum' and multiply it by the total_mass_kg */
                const total_mass_kg = parseFloat(jQuery('#total_mass_kg').val()) || 0;
                const new_mass_charge = sum * total_mass_kg;
                jQuery('#mass_charge').val(new_mass_charge);
                jQuery('#mass_rate').val(sum);

                // Take this new new_mass_charge and add create a hidden input called new_mass_rate to send via POST
                // If the input doesn't exist, create it; otherwise, update its value
                if (jQuery('#new_mass_rate').length === 0) {
                    jQuery('<input>').attr({
                        type: 'hidden',
                        id: 'new_mass_rate',
                        name: 'new_mass_rate',
                        value: sum
                    }).appendTo('form');
                } else {
                    jQuery('#new_mass_rate').val(sum);
                }

            });
        } else {
            

            jQuery('#mass_charge_display').text(current_rate);
            jQuery('#manipulated_mass_charge_display').text("");
            jQuery('#mass_rate').val(current_rate);

            // Optional: remove the hidden input if it exists
            if (jQuery('#new_mass_rate').length > 0) {
                jQuery('#new_mass_rate').remove();
            }
        }
    });
});

// Global spinner management system
const SpinnerManager = {
    // Store active spinners to prevent duplicates
    activeSpinners: new Set(),

    // CSS for the spinner (injected once)
    injectStyles: function () {
        if (!document.getElementById('spinner-styles')) {
            const style = document.createElement('style');
            style.id = 'spinner-styles';
            style.textContent = `
                .input-spinner {
                    position: relative;
                }
                .input-spinner::after {
                    content: '';
                    position: absolute;
                    right: 8px;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 16px;
                    height: 16px;
                    border: 2px solid rgba(0,0,0,0.1);
                    border-radius: 50%;
                    border-top-color: #3498db;
                    animation: spin 1s linear infinite;
                }
                @keyframes spin {
                    to { transform: translateY(-50%) rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
    },

    // Show spinner on specific input
    show: function (inputElement) {
        this.injectStyles();
        if (!this.activeSpinners.has(inputElement)) {
            inputElement.classList.add('input-spinner');
            this.activeSpinners.add(inputElement);
        }
    },

    // Hide spinner from specific input
    hide: function (inputElement) {
        if (this.activeSpinners.has(inputElement)) {
            inputElement.classList.remove('input-spinner');
            this.activeSpinners.delete(inputElement);
        }
    },

    // Hide all spinners
    hideAll: function () {
        this.activeSpinners.forEach(input => {
            input.classList.remove('input-spinner');
        });
        this.activeSpinners.clear();
    }
};