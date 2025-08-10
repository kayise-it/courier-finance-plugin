<?php
// waybill-form.php
// Start session at the very top to preserve form data
if (!session_id()) {
    session_start();
}

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submissions between steps
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save all submitted data to session
    $_SESSION['waybill_form_data'] = array_merge($_SESSION['waybill_form_data'] ?? [], $_POST);

    // Handle step navigation
    $current_step = isset($_POST['current_step']) ? intval($_POST['current_step']) : 1;

    if (isset($_POST['next_step'])) {
        $next_step = min($current_step + 1, 4);
    } elseif (isset($_POST['prev_step'])) {
        $next_step = max($current_step - 1, 1);
    } else {
        $next_step = $current_step;
    }

    // Redirect to the next step
    wp_redirect(add_query_arg(['step' => $next_step], wp_get_referer()));
    exit;
}

// Restore form data from session
$form_data = $_SESSION['waybill_form_data'] ?? [];


// Dummy customer list
if (isset($_GET['cust_id'])) {
    $customer_id = intval($_GET['cust_id']);
} else {
    $customer_id = 0;
}
$customers = tholaMaCustomer();

$selected_customer_key = $customer_id;
$is_existing_customer = false;

// Search through customers to find matching cust_id
foreach ($customers as $customer) {
    if ($customer->cust_id == $selected_customer_key) {
        $is_existing_customer = true;
        break;
    }
}

// Check if editing
$waybill_id = isset($_GET['waybill_id']) ? intval($_GET['waybill_id']) : 0;

$waybill = null;
$is_edit_mode = false;

$set_cust_id = isset($_GET['cust_id']) ? intval($_GET['cust_id']) : "";
if ($waybill_id > 0) {
    global $wpdb;
    $waybill = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$wpdb->prefix}kit_waybills WHERE id = %d", $waybill_id)
    );
    $is_edit_mode = !is_null($waybill);
}
$breadlinks = "";


// Get customer ID - first from GET param, then from waybill if editing
if (isset($_GET['cust_id'])) {
    $set_cust_id = intval($_GET['cust_id']);
} elseif ($waybill_id > 0 && !empty($waybill->customer_id)) {
    $set_cust_id = intval($waybill->customer_id);
} else {
    $set_cust_id = 0;
}


$customer_name = KIT_Customers::gamaCustomer($set_cust_id);

// Step handling - now using the persisted data
$current_step = isset($_GET['step']) ? intval($_GET['step']) : 1;
$max_steps = 4;

$form_action = $is_edit_mode
    ? admin_url('admin-post.php?action=update_waybill_action')
    : admin_url('admin-post.php?action=add_waybill_action');

$scheduled_deliveries = KIT_Deliveries::getScheduledDeliveries();

?>

<div class="wrap">
    <?php
    echo do_shortcode('[showheader title="Capture New Waybill" desc=""]');
    ?>
    <div class="mx-auto max-w-7xl">
        <?php
        $scheduled_deliveries = KIT_Deliveries::getScheduledDeliveries();

        if (!empty($scheduled_deliveries)):
            $encoded_customers = base64_encode(json_encode($customers));
            echo do_shortcode('[waybill_multiform 
           form_action="' . esc_url($form_action) . '" 
           waybill_id="' . esc_attr($waybill_id) . '" 
           is_edit_mode="' . ($is_edit_mode ? '1' : '0') . '" 
           waybill="' . esc_attr(json_encode($waybill)) . '" 
           customer="' . esc_attr($encoded_customers) . '"
           is_existing_customer="' . ($is_existing_customer ? '1' : '0') . '" 
           customer_id="' . esc_attr($set_cust_id) . '"]');

        else: ?>
            <div class="bg-white p-6 rounded-lg shadow-md">
                <p class="text-bold">No Deliver</p>
                <p class="text-bold"><a href="?page=kit-deliveries" class="text-bold">Create delivery</a> card in order to
                    create waybill</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Customer selection handling
            const customerSelect = document.getElementById('customer-select');
            const custIdInput = document.getElementById('cust_id');
            const companyInput = document.getElementById('company_name');
            const nameInput = document.getElementById('customer_name');
            const surnameInput = document.getElementById('customer_surname');
            const cellInput = document.getElementById('cell');
            const addressInput = document.getElementById('address');

            function populateCustomerDetails(customerId) {
                if (!customerSelect) return;
                const option = customerSelect.querySelector(`option[value="${customerId}"]`);
                if (!option) return;
                if (companyInput) companyInput.value = option.getAttribute('data-company_name') || '';
                if (nameInput) nameInput.value = option.getAttribute('data-name') || '';
                if (surnameInput) surnameInput.value = option.getAttribute('data-surname') || '';
                if (cellInput) cellInput.value = option.getAttribute('data-cell') || '';
                if (addressInput) addressInput.value = option.getAttribute('data-address') || '';
                if (custIdInput) custIdInput.value = customerId;
            }

            // Handle dropdown changes
            customerSelect?.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];

                // Access data-* attributes
                const name = selectedOption.getAttribute('data-name');
                const surname = selectedOption.getAttribute('data-surname');
                const cell = selectedOption.getAttribute('data-cell');
                const address = selectedOption.getAttribute('data-address');
                const company = selectedOption.getAttribute('data-company_name');

                console.log('Name:', name);
                console.log('Surname:', surname);
                console.log('Cell:', cell);
                console.log('Address:', address);

                if (this.value === 'new') {
                    // Clear all fields for new customer
                    if (companyInput) companyInput.value = '';
                    if (nameInput) nameInput.value = '';
                    if (surnameInput) surnameInput.value = '';
                    if (cellInput) cellInput.value = '';
                    if (addressInput) addressInput.value = '';
                    if (custIdInput) custIdInput.value = '0';
                } else if (this.value) {
                    // Populate fields with selected customer data
                    if (companyInput) companyInput.value = company || '';
                    if (nameInput) nameInput.value = name || '';
                    if (surnameInput) surnameInput.value = surname || '';
                    if (cellInput) cellInput.value = cell || '';
                    if (addressInput) addressInput.value = address || '';
                    if (custIdInput) custIdInput.value = this.value;
                }
            });

            // Check for initial customer ID on page load
            const initialCustomerId = custIdInput?.value;
            if (initialCustomerId && initialCustomerId !== '0') {
                populateCustomerDetails(initialCustomerId);
            }
        });
    </script>
</div>