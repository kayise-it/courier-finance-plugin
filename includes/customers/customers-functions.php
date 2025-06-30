<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
class KIT_Customers
{
    public static function init()
    {
        add_action('admin_post_add_waybill_action', [self::class, 'process_form']);
        add_action('kit_waybills_list', [__CLASS__, 'kit_get_all_waybills_table']);
        add_action('admin_post_update_customer', [self::class, 'handle_update_customer']);
    }
    public static function gamaCustomer($id)
    {

        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';
        return $wpdb->get_var("SELECT name FROM $table_name WHERE cust_id=" . $id);
    }
    public static function idCustomer($id)
    {

        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';
        return $wpdb->get_var("SELECT id FROM $table_name WHERE cust_id=" . $id);
    }

    public static function save_customer($cust)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';

        // First check if customer already exists
        $existing_customer = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE name = %s AND surname = %s",
                sanitize_text_field($cust['customer_name']),
                sanitize_text_field($cust['customer_surname'])
            )
        );

        if ($existing_customer) {
            // Customer already exists, return their ID or some indicator
            return $existing_customer->cust_id;
        }

        // Sanitize inputs
        $cust_data = [
            'cust_id'  => rand(1000, 9999),
            'name'     => sanitize_text_field($cust['customer_name']),
            'surname'  => sanitize_text_field($cust['customer_surname']),
            'cell'     => sanitize_text_field($cust['cell']),
            'address'  => sanitize_text_field($cust['address']),
        ];

        // Insert into DB
        $inserted = $wpdb->insert($table_name, $cust_data);

        if ($inserted === false) {
            return false; // Insert failed
        }

        return $cust_data['cust_id']; // Return the new customer ID
    }

    public static function update_customer($cust_id, $data)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';

        // Sanitize input data
        $update_data = [];
        if (isset($data['name'])) {
            $update_data['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['surname'])) {
            $update_data['surname'] = sanitize_text_field($data['surname']);
        }
        if (isset($data['cell'])) {
            $update_data['cell'] = sanitize_text_field($data['cell']);
        }
        if (isset($data['address'])) {
            $update_data['address'] = sanitize_text_field($data['address']);
        }

        // Only update if there is data
        if (!empty($update_data)) {
            $updated = $wpdb->update(
                $table_name,
                $update_data,
                ['cust_id' => intval($cust_id)]
            );
            return $updated !== false;
        }
        return false;
    }

    public static function handle_update_customer()
    {
        if (!isset($_POST['cust_update_nonce']) || !wp_verify_nonce($_POST['cust_update_nonce'], 'update_customer_nonce')) {
            wp_die('Nonce verification failed');
        }

        $cust_id = intval($_POST['cust_id']);

        $data = [
            'name'    => sanitize_text_field($_POST['name']),
            'surname' => sanitize_text_field($_POST['surname']),
            'cell'    => sanitize_text_field($_POST['cell']),
            'address' => sanitize_textarea_field($_POST['address']),
        ];

        // 🔥 Call your method here
        KIT_Customers::update_customer($cust_id, $data);

        //get the customer id
        $id = KIT_Customers::idCustomer($cust_id);

        // Redirect back to the customer list or confirmation page
        wp_redirect(admin_url('admin.php?page=edit-customer&edit_customer=' . $id . '&updated=1'));
        exit;
    }

    //After the new customer is saved, go back to the customer table and update the cust_id to the new customer id
    public static function update_customer_id($customer_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';

        // Update the customer ID
        $updated = $wpdb->update($table_name, ['cust_id' => $customer_id], ['id' => $customer_id]);

        return $updated;
    }

    public static function delete_customer($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';

        // Sanitize and delete
        $deleted = $wpdb->delete($table_name, ['id' => intval($id)]);

        if ($deleted) {
            echo '<div class="bg-red-100 text-red-800 p-4 rounded mb-4">Customer deleted successfully. 🗑️</div>';
            wp_redirect(admin_url('admin.php?page=customers-dashboard'));
            exit;
        } else {
            echo '<div class="bg-yellow-100 text-yellow-800 p-4 rounded mb-4">Customer not found or already deleted.</div>';
        }
    }

    public static function tholaMaCustomer()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'kit_customers';
        return $wpdb->get_results("SELECT * FROM $table_name");
    }
}
// Initialize
KIT_Customers::init();


function customer_dashboard()
{

    if (isset($_GET['delete_customer'])) {
        delete_customer($_GET['delete_customer']);
    }

    $customers = tholaMaCustomer();


?>
    <div class="wrap">
        <?php
        echo KIT_Commons::showingHeader([
            'title' => 'Customer Dashboard',
            'desc'  => '',
            'content'  => customer_form(),
        ]);
        ?>
        <div class="max-w-7xl mx-auto">
            <div class="overflow-x-auto bg-white shadow-md rounded-xl">
                <table class="min-w-full text-left text-sm text-gray-700">
                    <thead class="bg-gray-100 border-b font-semibold uppercase">
                        <tr>
                            <th class="px-6 py-4">#</th>
                            <th class="px-6 py-4">Name</th>
                            <th class="px-6 py-4">Email</th>
                            <th class="px-6 py-4">Phone</th>
                            <th class="px-6 py-4">Waybills</th>
                            <th class="px-6 py-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($customers as $cust):

                        ?>
                            <!-- Example row -->
                            <tr class="border-b hover:bg-gray-50">
                                <td class="px-6 py-4"><?php echo ($cust->cust_id) ?? 'N/A'; ?></td>
                                <td class="px-6 py-4">
                                    <p><?php echo ($cust->name) ?? 'N/A'; ?></p>
                                </td>
                                <td class="px-6 py-4"><?php echo ($cust->surname) ?? 'N/A'; ?></td>
                                <td class="px-6 py-4"><?php echo ($cust->cell) ?? 'N/A'; ?></td>
                                <td class="px-6 py-4">
                                    <a href="?page=all-customer-waybills&cust_id=<?php echo $cust->cust_id; ?>"
                                        class="text-blue-600 hover:underline">
                                        Waybills
                                    </a>
                                </td>
                                <td class="px-6 py-4 space-x-2">
                                    <a href="?page=edit-customer&edit_customer=<?php echo $cust->id; ?>"
                                        class="text-blue-600 hover:underline">
                                        View
                                    </a>
                                    <a href="?page=customers-dashboard&delete_customer=<?php echo $cust->id; ?>"
                                        class="text-red-600 hover:underline"
                                        onclick="return confirm('Are you sure you want to delete this customer?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach ?>
                        <!-- More rows will be looped in here -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php
}

function customer_button_with_modal()
{
?>
    <div class="p-6">
        <!-- Trigger Button -->
        <button onclick="document.getElementById('thaboModal').classList.remove('hidden')"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl shadow">
            Open Modal
        </button>

        <!-- Modal Overlay -->
        <div id="thaboModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
            <!-- Modal Content -->
            <div class="bg-white p-6 rounded-xl shadow-xl w-96 text-center">
                <h2 class="text-xl font-semibold mb-4">Hey Thabo 👋</h2>
                <p class="mb-6">Welcome to the modal!</p>
                <button onclick="document.getElementById('thaboModal').classList.add('hidden')"
                    class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-lg">
                    Close
                </button>
            </div>
        </div>
    </div>
<?php
}

function zamazama($customer = null)
{
    ob_start();
?>
    <div class="bg-yellow-100 text-yellow-800 p-4 rounded mb-4">Zamazama function called. Nothing to do here yet.</div>
    <input type="text" name="cust_id" value="232332">
<?php
    return ob_get_clean();
}

function theForm($customer = null)
{
?>
    <input type="hidden" name="cust_id" id="cust_id" value="<?= esc_attr($customer->cust_id ?? '') ?>">

    <div class="space-y-5">
        <!-- Name Field -->
        <div class="relative">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
            <div class="relative">
                <input type="text" id="name" name="name" value="<?= esc_attr($customer->name ?? '') ?>"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                    placeholder="John" required>
            </div>
        </div>

        <!-- Surname Field -->
        <div class="relative">
            <label for="surname" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
            <div class="relative">
                <input type="text" id="surname" name="surname" value="<?= esc_attr($customer->surname ?? '') ?>"
                    class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                    placeholder="Doe" required>
            </div>
        </div>

        <!-- Phone Field -->
        <div class="relative">
            <label for="cell" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
            <div class="relative flex items-center">
                <span class="absolute left-3 text-gray-500">+</span>
                <input type="tel" id="cell" name="cell" value="<?= esc_attr($customer->cell ?? '') ?>"
                    class="w-full pl-8 px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                    placeholder="123 456 7890" required>
            </div>
        </div>

        <!-- Address Field -->
        <div class="relative">
            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
            <textarea id="address" name="address" rows="3"
                class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200"
                placeholder="123 Main St, Anytown" required><?= esc_textarea($customer->address ?? '') ?></textarea>
        </div>

    </div>
<?php
}

function customer_form()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_customers';

    // Edit mode
    $is_edit = false;
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $customer = null;

    if ($id) {
        $is_edit = true;
        $customer = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }

    // Handle form submit
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['customer_submit'])) {
        save_customer();
    }

    ob_start();
?>
    <div class="customer-form-container">
        <!-- Trigger Button -->
        <button id="customerModalButton" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl shadow">
            + Add New Customer
        </button>

        <!-- Modal -->
        <div id="customerModal" class="fixed hidden inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-xl w-full max-w-xl relative">
                <!-- Close Button -->
                <button id="customerModalClose" class="absolute top-3 right-4 text-gray-600 hover:text-black text-xl">
                    &times;
                </button>

                <h2 class="text-xl font-bold mb-4"><?= $is_edit ? 'Edit Customer' : 'Add Customer' ?></h2>
                <div class="">
                    <form method="post" class="space-y-4" id="customerForm">
                        <?php if ($is_edit): ?>
                            <input type="hidden" name="customer_id" value="<?= esc_attr($id) ?>">
                        <?php endif; ?>

                        <div class="">
                            <?php echo theForm(null); ?>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" name="customer_submit"
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                                <?= $is_edit ? 'Update' : 'Save' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
<?php
    return ob_get_clean();
}

function save_customer()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_customers';

    // Sanitize inputs
    $cust_data = [
        'cust_id'  => rand(1000, 9999),
        'name'     => sanitize_text_field($_POST['name']),
        'surname'  => sanitize_text_field($_POST['surname']),
        'cell'     => sanitize_text_field($_POST['cell']),
        'address'  => sanitize_text_field($_POST['address']),
    ];

    // Insert into DB
    $inserted = $wpdb->insert($table_name, $cust_data);

    if ($inserted) {
        echo '<div class="bg-green-100 text-green-800 p-4 rounded mb-4">Customer saved successfully. 🎉</div>';
        wp_redirect(admin_url('admin.php?page=customers-dashboard'));
        exit;
    } else {
        echo '<div class="bg-red-100 text-red-800 p-4 rounded mb-4">Failed to save customer. 😢</div>';
    }
}

function delete_customer($id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_customers';

    // Sanitize and delete
    $deleted = $wpdb->delete($table_name, ['id' => intval($id)]);

    if ($deleted) {
        echo '<div class="bg-red-100 text-red-800 p-4 rounded mb-4">Customer deleted successfully. 🗑️</div>';
        wp_redirect(admin_url('admin.php?page=customers-dashboard'));
        exit;
    } else {
        echo '<div class="bg-yellow-100 text-yellow-800 p-4 rounded mb-4">Customer not found or already deleted.</div>';
    }
}

function tholaMaCustomer()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_customers';
    return $wpdb->get_results("SELECT * FROM $table_name");
}

function gamaCustomer()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_customers';
    $results = $wpdb->get_results("SELECT * FROM $table_name");
    $customers = array_map(fn($row) => $row->name, $results);
    return $customers;
}
function get_customer_details($customer_id)
{
    global $wpdb;

    // Sanitize the input
    $customer_id = absint($customer_id);
    if (!$customer_id) {
        return false;
    }

    $table_name = $wpdb->prefix . 'kit_customers';

    // Prepare and execute a parameterized query
    $query = $wpdb->prepare(
        "SELECT * FROM $table_name WHERE cust_id = %d",
        $customer_id
    );

    $customer = $wpdb->get_row($query);
    // Return false if no customer found
    if (empty($customer)) {
        return false;
    }

    return $customer;
}

function edit_customer()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_customers';
    $id = isset($_GET['edit_customer']) ? intval($_GET['edit_customer']) : 0;
    $customer = $wpdb->get_row("SELECT * FROM $table_name WHERE id = $id");

    ob_start();
?>
    <div class="wrap">
        <?php
        echo KIT_Commons::showingHeader([
            'title' => 'Update Customer',
            'desc'  => KIT_Commons::kitButton([
                'color' => 'green',
                'href'  => admin_url('admin.php?page=customers-dashboard')
            ], 'Back'),
        ]);
        ?>
        <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden p-6 mt-7">
            <h1 class="text-xl font-bold mb-4">Our Client</h1>
            <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>" class="space-y-4">
                <?php wp_nonce_field('update_customer_nonce', 'cust_update_nonce'); ?>
                <input type="hidden" name="action" value="update_customer" />
                <?php theForm($customer); ?>

                <div class="flex justify-end">
                    <button type="submit" name="customer_submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                        Upd78ate
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

function customer_waybills($customer_id)
{
    global $wpdb;

    // Sanitize and validate the customer ID
    $customer_id = intval($customer_id);  // Use intval to ensure it's a valid integer

    // Query to get all waybills for the given customer ID
    $table_name = $wpdb->prefix . 'kit_waybills';
    $query = $wpdb->prepare("SELECT * FROM $table_name WHERE customer_id = %d", $customer_id);

    // Execute the query
    $waybills = $wpdb->get_results($query);

    return $waybills;
}

function view_customer_waybills()
{
    if (isset($_GET['cust_id'])) {

        $customer_id = intval($_GET['cust_id']);
        $customer = get_customer_details($customer_id); // You'll need to implement this
        $waybills = customer_waybills($customer_id);
        echo KIT_Commons::showingHeader([
            'title' => 'Customers Waybills',
            'desc' => "342234",
        ]);

        $customers   = KIT_Customers::tholaMaCustomer();
        $form_action = admin_url('admin-post.php?action=add_waybill_action');

        $modal_path = realpath(plugin_dir_path(__FILE__) . '../components/modal.php');

        if (file_exists($modal_path)) {
            require_once $modal_path;
        } else {
            error_log("Modal.php not found at: " . $modal_path);
            // Optional: Show a safe error or fallback content
        }
    ?>


        <div class="<?= KIT_Commons::container() ?> flex gap-4 min-h-screen bg-gray-100">
            <!-- Left Sidebar - Customer Details -->
            <div class="w-1/3">
                <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-4">Customer Details</h2>

                    <?php if ($customer) : ?>
                        <div class="space-y-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-700"><?php echo esc_html($customer->name); ?></h3>
                                <p class="text-gray-600"><?php echo esc_html($customer->surname); ?></p>
                            </div>

                            <div class="border-t border-gray-200 pt-4">
                                <h4 class="font-medium text-gray-800">Contact Information</h4>
                                <p class="text-gray-600 mt-1">
                                    <span class="block"><?php echo esc_html($customer->cell); ?></span>
                                    <span class="block"><?php echo esc_html(($customer->email) ?? ''); ?></span>
                                </p>
                            </div>

                            <div class="border-t border-gray-200 pt-4">
                                <h4 class="font-medium text-gray-800">Shipping Address</h4>
                                <p class="text-gray-600 mt-1">
                                    <span class="block"><?php echo esc_html(($customer->destination_city) ?? ''); ?></span>
                                    <span class="block"><?php echo esc_html(($customer->destination_country) ?? ''); ?></span>
                                </p>
                            </div>

                            <div class="border-t border-gray-200 pt-4">
                                <h4 class="font-medium text-gray-800">Statistics</h4>
                                <div class="grid grid-cols-2 gap-4 mt-2">
                                    <div class="bg-blue-50 p-3 rounded-lg">
                                        <p class="text-sm text-blue-600">Total Waybills</p>
                                        <p class="text-xl font-bold"><?php echo count($waybills); ?></p>
                                    </div>
                                    <div class="bg-green-50 p-3 rounded-lg">
                                        <p class="text-sm text-green-600">Active Shipments</p>
                                        <p class="text-xl font-bold">3</p> <!-- Replace with dynamic data -->
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else : ?>
                        <p class="text-red-500">Customer not found</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Content - Waybills Table -->
            <div class="w-2/3">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Waybill History</h2>
                        <?php
                        echo KIT_Modal::render(
                            'create-waybill-modal',
                            'Create New Waybill',
                            kit_render_waybill_multiform([
                                'form_action'          => $form_action,
                                'waybill_id'           => '',
                                'is_edit_mode'         => '0',
                                'waybill'              => '{}',
                                'customer_id'          => $customer_id,
                                'is_existing_customer' => '',
                                'customer'             => $customers
                            ]),
                            '3xl'
                        );
                        ?>

                    </div>

                    <?php if (!empty($waybills)) : ?>
                        <?php
                        KIT_Waybills::render_waybills_with_items($waybills);
                        ?>
                      
                        <!-- Pagination -->
                        <div class="flex items-center justify-between mt-6">
                            <div class="text-sm text-gray-500">
                                Showing <span class="font-medium">1</span> to <span class="font-medium">10</span> of <span
                                    class="font-medium"><?php echo count($waybills); ?></span> results
                            </div>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 border rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Previous
                                </button>
                                <button class="px-3 py-1 border rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    Next
                                </button>
                            </div>
                        </div>
                    <?php else : ?>
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No waybills found</h3>
                            <p class="mt-1 text-sm text-gray-500">This customer doesn't have any waybills yet.</p>
                            <div class="mt-6">
                                <?php
                                echo KIT_Commons::kitButton([
                                    'color' => 'blue',
                                    'modal' => 'create-waybill-modal',
                                    'icon' => 'plus',
                                ], 'Create New Waybill'); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
<?php
    } else {
        return '<div class="p-6 text-red-500">No customer selected.</div>';
    }
}
