//file location: js/kitscript.js
document.addEventListener('DOMContentLoaded', function () {
    const feeOptions = document.querySelectorAll('.fee-option');
    const vatOption = document.getElementById('vat_option');

    feeOptions.forEach(option => {
        // Handle click on the label/button area
        option.addEventListener('click', function (e) {
            // If the click is directly on the input, let it handle itself
            if (e.target.tagName === 'INPUT') return;

            // Find the input element within the fee-option
            const input = this.querySelector('input[type="checkbox"]');
            if (!input) return;

            // Prevent checking others if VAT is already checked
            if (vatOption.checked && input.id !== 'vat_option') {
                return;
            }

            // Toggle the checkbox state
            input.checked = !input.checked;

            // If VAT was just checked, uncheck all others
            if (input.id === 'vat_option' && input.checked) {
                feeOptions.forEach(opt => {
                    const otherInput = opt.querySelector('input[type="checkbox"]');
                    if (otherInput && otherInput.id !== 'vat_option') {
                        otherInput.checked = false;
                        opt.classList.remove('bg-blue-100', 'border-blue-500');
                    }
                });
            }

            // If another option was checked while VAT is checked, uncheck VAT
            if (input.id !== 'vat_option' && input.checked && vatOption.checked) {
                vatOption.checked = false;
                vatOption.closest('.fee-option').classList.remove('bg-blue-100', 'border-blue-500');
            }

            // Update visual state
            updateVisualState(input);
        });

        // Also handle changes directly on the input (in case user clicks it)
        const input = option.querySelector('input[type="checkbox"]');
        if (input) {
            input.addEventListener('change', function () {
                // Prevent checking others if VAT is already checked
                if (vatOption.checked && this.id !== 'vat_option') {
                    this.checked = false;
                    return;
                }

                // If VAT was checked, uncheck all others
                if (this.id === 'vat_option' && this.checked) {
                    feeOptions.forEach(opt => {
                        const otherInput = opt.querySelector('input[type="checkbox"]');
                        if (otherInput && otherInput.id !== 'vat_option') {
                            otherInput.checked = false;
                            opt.classList.remove('bg-blue-100', 'border-blue-500');
                        }
                    });
                }

                // If another option was checked while VAT is checked, uncheck VAT
                if (this.id !== 'vat_option' && this.checked && vatOption.checked) {
                    vatOption.checked = false;
                    vatOption.closest('.fee-option').classList.remove('bg-blue-100', 'border-blue-500');
                }

                updateVisualState(this);
            });
        }
    });

    function updateVisualState(input) {
        const parent = input.closest('.fee-option');
        if (input.checked) {
            parent.classList.add('bg-blue-100', 'border-blue-500');
        } else {
            parent.classList.remove('bg-blue-100', 'border-blue-500');
        }
    }


});


jQuery(function ($) {
    $(document).on('click', '.delete-waybill', function (e) {
        e.preventDefault();

        const $form = $(this).closest('form');
        const waybillId = $form.find('input[name="waybill_id"]').val();
        const waybillNo = $form.find('input[name="waybill_no"]').val();
        const deliveryId = $form.find('input[name="delivery_id"]').val();
        const userId = $form.find('input[name="user_id"]').val();
        const nonce = $form.find('input[name="_wpnonce"]').val();
        const $row = $(this).closest('tr');

        if (!confirm('Are you sure you want to delete this waybill?')) {
            return;
        }

        $.ajax({
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
                    $row.fadeOut(300, function () {
                        $(this).remove();
                        checkEmptyTable();
                    });
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
});

// Attach event listeners for relevant fields to recalculate the cost when values change
function attachEventListeners() {
    const fields = ['weight_kg', 'volume_m3', 'additional_fees', 'discount_percent', 'include_sad500', 'include_sadc_certificate', 'include_tra_clearing_fee'];

    fields.forEach(field => {
        const element = document.getElementById(field);
        element.addEventListener('input', calculateTotalCost);
        element.addEventListener('change', calculateTotalCost); // For checkboxes
    });
}

// Call the function to set up event listeners and initialize values
window.onload = () => {};