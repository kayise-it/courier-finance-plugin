<?php
//file location: 
/**
 * Plugin Name: 08600 Services and Quotations
 * Description: Plugin to manage services and quotations.
 * Version: 1.0
 * Author: Thando Hlophe kayise it
 * Author URI: https://kayiseit.com
 * Text Domain: 08600-services-quotations
 */

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}
define('COURIER_FINANCE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('COURIER_FINANCE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Enqueue styles for the admin panel
function customStyling()
{
    wp_enqueue_style('autsincss', plugin_dir_url(__FILE__) . 'assets/css/austin.css', array(), '1.0');
    wp_enqueue_style('kit-tailwindcss', plugin_dir_url(__FILE__) . 'assets/css/frontend.css', array(), '1.0');
}


// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
include_once(plugin_dir_path(__FILE__) . 'includes/class-plugin.php');

// Activate and deactivate hooks
register_activation_hook(__FILE__, array('Database', 'activate'));
register_deactivation_hook(__FILE__, array('Database', 'deactivate'));

function kit_remove_manage_options_from_editor()
{
    $editor = get_role('editor');
    if ($editor && $editor->has_cap('manage_options')) {
        $editor->remove_cap('manage_options');
    }
}



// Initialize the plugin
Plugin::init();

// Include the service functions
include_once plugin_dir_path(__FILE__) . 'includes/commons.php';
include_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
include_once plugin_dir_path(__FILE__) . 'includes/admin-pages.php';
include_once(plugin_dir_path(__FILE__) . 'includes/services/services-functions.php');
include_once(plugin_dir_path(__FILE__) . 'includes/quotations/quotations-functions.php');
require_once plugin_dir_path(__FILE__) . 'includes/waybill/waybill-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/customers/customers-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/user-roles.php';
require_once plugin_dir_path(__FILE__) . 'includes/deliveries/deliveries-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/waybillmultiform.php';
require_once plugin_dir_path(__FILE__) . 'includes/countries/opc-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/routes/routes-functions.php';

function my_plugin_enqueue_scripts()
{
    wp_enqueue_script('kitscript', plugin_dir_url(__FILE__) . 'js/kitscript.js', ['jquery'], null, true);
    wp_enqueue_script('waybill-pagination', plugin_dir_url(__FILE__) . '/js/waybill-pagination.js', ['jquery'], null, true);

    $localize_data = [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonces' => [
            'add'    => wp_create_nonce('add_waybill_nonce'),
            'delete' => wp_create_nonce('delete_waybill_nonce'),
            'update' => wp_create_nonce('update_waybill_nonce'),
            'get_waybills_nonce' => wp_create_nonce('get_waybills_nonce'),
            'get_cities_nonce'   => wp_create_nonce('get_cities_nonce'),
            'kit_waybill_nonce'  => wp_create_nonce('kit_waybill_nonce'),
            'pdf_nonce'          => wp_create_nonce('pdf_nonce'),
        ],
    ];

    // Localize both
    wp_localize_script('waybill-pagination', 'myPluginAjax', $localize_data);
    wp_localize_script('kitscript', 'myPluginAjax', $localize_data);
}

/**
 * Main plugin page callback with form to insert new service
 */
function plugin_main_page()
{
    // Start output buffering
    ob_start();

    echo KIT_Commons::showingHeader([
        'title' => 'Services',
        'desc' => "342234",
    ]);

    echo '<div class="' . KIT_Commons::container() . '">';
    // Check if the form is submitted
    if (isset($_POST['submit_service'])) {
        $service_name = sanitize_text_field($_POST['service_name']);
        $service_description = sanitize_textarea_field($_POST['service_description']);
        $service_image = sanitize_text_field($_POST['service_image']);

        // Check if the service name already exists
        if (service_name_exists($service_name)) {
            echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p class="font-bold">Error</p>
                <p>Service name already exists.</p>
            </div>';
        } else {
            // Insert the service into the database
            insert_service($service_name, $service_description, $service_image);
            echo '<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p class="font-bold">Success</p>
                <p>Service added successfully!</p>
            </div>';
        }
    }

    // Form for adding new service
?>
    <div class="grid grid-cols-3 gap-6">
        <!-- Add New Service Card -->
        <div class="bg-white rounded-lg shadow-md p-6 flex-1">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Add New Service</h2>
            <form method="POST" action="">
                <div class="space-y-4">
                    <div>
                        <label for="service_name" class="block text-sm font-medium text-gray-700 mb-1">Service Name</label>
                        <input type="text" name="service_name" id="service_name" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label for="service_description"
                            class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="service_description" id="service_description" rows="3" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>

                    <div>
                        <label for="service_image" class="block text-sm font-medium text-gray-700 mb-1">Flat Icon</label>
                        <input type="text" name="service_image" id="service_image"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="pt-2">
                        <button type="submit" name="submit_service"
                            class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Add Service
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Services List Card -->
        <div class="col-span-2 bg-white rounded-lg shadow-md p-6 flex-1">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Existing Services</h2>
            <?php
            // Get all services from the database
            global $wpdb;
            $services_table_name = $wpdb->prefix . 'kit_services';

            // Query to retrieve services from the database
            $services = $wpdb->get_results("SELECT * FROM $services_table_name");

            // If no services are found, display a message
            if (empty($services)) {
                echo '<p class="text-gray-500">No services found.</p>';
            } else {
                plugin_services_list_page();
            }
            ?>
        </div>
    </div>
<?php

    echo '</div>';
    // Output the buffered content
    echo ob_get_clean();
}

/**
 * Insert a new service into the database
 */
function insert_service($name, $description, $image)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_services';

    $wpdb->insert(
        $table_name,
        array(
            'name'        => $name,
            'description' => $description,
            'image'       => $image,
        ),
        array(
            '%s', // name
            '%s', // description
            '%s', // image
        )
    );
}

/**
 * Services list page callback
 */
function plugin_services_list_page()
{
    // Get all services
    $services = get_all_services();
    if (! empty($services)) {
        echo '<div class="overflow-x-auto">'; // Add this container for horizontal scrolling
        echo '<table class="w-full striped">'; // Remove "fixed" class
        echo '<thead>';
        echo '<tr>';
        echo '<th class="whitespace-nowrap px-4 py-2 text-left">Name</th>';
        echo '<th class="whitespace-nowrap px-4 py-2 text-left">Description</th>';
        echo '<th class="whitespace-nowrap px-4 py-2 text-left">Image</th>';
        echo '<th class="whitespace-nowrap px-4 py-2 text-left">Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($services as $service) {
            echo '<tr>';
            echo '<td class="px-4 py-2 max-w-xs truncate">' . esc_html($service->name) . '</td>';
            echo '<td class="px-4 py-2 max-w-md">' . esc_html($service->description) . '</td>';
            echo '<td class="px-4 py-2 max-w-xs truncate">' . esc_html($service->image) . '</td>';
            echo '<td class="px-4 py-2 whitespace-nowrap">';
            echo '<a href="' . esc_url(admin_url('admin.php?page=08600-services-edit&id=' . $service->id)) . '"
                    class="text-blue-600 hover:text-blue-800">Edit</a> | ';
            echo '<a href="' . esc_url(admin_url('admin-post.php?action=delete_service&id=' . $service->id)) . '"
                    onclick="return confirm(\'Are you sure you want to delete this service?\')"
                    class="text-red-600 hover:text-red-800">Delete</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
        echo '</div>'; // Close overflow container
    } else {
        echo '<p>No services found.</p>';
    }
}

/**
 * Handle service deletion
 */
function handle_service_deletion()
{
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $service_id = intval($_GET['id']);
        delete_service($service_id); // Delete the service from the database
    }

    wp_redirect(admin_url('admin.php?page=08600-services-list')); // Redirect back to the services list page
    exit;
}
add_action('admin_post_delete_service', 'handle_service_deletion');

/**
 * Services list page callback
 */
function plugin_quotations_list_page()
{
    echo '<div class="wrap">';
    echo KIT_Commons::showingHeader([
        'title' => 'Admin View Quotations',
        'desc' => "342234",
    ]);
    echo '<div class="' . KIT_Commons::container() . ' space-y-4">';
    echo kit_get_all_quotations_table();
    echo '</div>';
    echo '</div>';
}



// Function to display a specific quotation
// Function to display a specific quotation
function quotation_view_page()
{
    if (!isset($_GET['quotation_id'])) {
        echo '<div class="error"><p>Quotation ID is missing.</p></div>';
        return;
    }

    $quotation_id = intval($_GET['quotation_id']);
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_quotations';

    // Sample data structure based on your array
    $quotation = (object)[
        'id' => 'Q-' . str_pad($quotation_id, 5, '0', STR_PAD_LEFT),
        'delivery_id' => '',
        'customer_id' => '',
        'waybill_no' => '22',
        'product_invoice_number' => 'INV-20250505-6557',
        'product_invoice_amount' => '0.00',
        'item_length' => '73.00',
        'item_width' => '52.00',
        'item_height' => '54.00',
        'total_volume' => '34.00',
        'total_mass_kg' => '76.00',
        'unit_volume' => '69.00',
        'unit_mass' => '4.00',
        'charge_basis' => 'VOLUME',
        'mass_charge' => '16.00',
        'volume_charge' => '45.00',
        'consignor_name' => '',
        'consignor_code' => '',
        'consignor_address' => '',
        'contact_name' => '',
        'vat_number' => '',
        'telephone' => '',
        'warehouse' => '',
        'miscellaneous' => '',
        'include_sad500' => 1,
        'include_sadc' => 0,
        'return_load' => 0,
        'is_new_customer' => 1,
        'tracking_number' => 'TRACK-20250505-CBEC9F',
        'created_by' => '',
        'last_updated_by' => '1569',
        'last_updated_at' => '2025-05-05 13:17:25',
        'status' => '',
        'created_at' => '2025-05-05 00:00:00',
        'cust_id' => '2898',
        'name' => 'Thando',
        'surname' => 'Hlophe',
        'cell' => '23232323',
        'address' => '21 Cussons street',
        'delivery_reference' => '51',
        'origin_country' => 'South Africa',
        'destination_country' => 'Tanzania',
        'destination_city' => 'Neque sunt similique',
        'dispatch_date' => '2025-04-26',
        'truck_number' => '428',
        'ID' => '1569',
        'user_login' => 'Thando',
        'user_pass' => '$wp$2y$10$6gU8Yn2mzhvc28U3NXxyZu3D9xHnqnFhA1nK8nTsGtbuc.eev2GkG',
        'user_nicename' => 'thando',
        'user_email' => 'thando@kayiseit.com',
        'user_url' => '',
        'user_registered' => '2025-02-22 04:57:52',
        'user_activation_key' => '',
        'user_status' => '0',
        'display_name' => 'Thando Hlophe',
        'waybill_id' => '',
        'subtotal' => '1200.00',
        'total' => '1380.00',
    ];

    // Calculate totals
    $subtotal = floatval($quotation->subtotal ?? 0);
    $final_cost = floatval($quotation->total ?? 0);
    $tax = $final_cost - $subtotal;
?>

    <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden font-sans">
        <!-- Header Section with gradient -->
        <div class="bg-gradient-to-r from-indigo-600 to-blue-500 p-8 text-white">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold tracking-tight">QUOTATION</h1>
                    <div class="mt-4 space-y-1 text-blue-100">
                        <p class="font-medium flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2h-1V9z"
                                    clip-rule="evenodd" />
                            </svg>
                            Quotation #: <?php echo esc_html($quotation->id); ?>
                        </p>
                        <p class="flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                    clip-rule="evenodd" />
                            </svg>
                            Date: <?php echo date('F d, Y', strtotime($quotation->created_at ?? 'now')); ?>
                        </p>
                    </div>
                </div>
                <div class="bg-white/10 p-4 rounded-lg backdrop-blur-sm">
                    <p class="font-medium">Valid Until: <?php echo date('F d, Y', strtotime('+7 days')); ?></p>
                    <p class="text-sm mt-1">Tracking #: <?php echo esc_html($quotation->tracking_number); ?></p>
                </div>
            </div>
        </div>

        <!-- Company & Client Info -->
        <div class="grid md:grid-cols-2 gap-8 p-8 border-b">
            <!-- Left Column - Company Details -->
            <div class="space-y-4">
                <div class="flex items-center gap-4 mb-4">
                    <img class="w-32" src="<?php echo plugin_dir_url(__FILE__) . 'img/logo.png'; ?>" alt="Company Logo">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">08600 Logistics</h2>
                        <p class="text-blue-600 font-medium">Transport & Logistics Solutions</p>
                    </div>
                </div>
                <div class="space-y-1 text-gray-600">
                    <p>123 Business Street</p>
                    <p>Johannesburg, 2000</p>
                    <p>South Africa</p>
                </div>
                <div class="mt-4 space-y-1">
                    <p class="text-blue-600 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                        </svg>
                        +27 11 123 4567
                    </p>
                    <p class="text-blue-600 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                        info@08600.co.za
                    </p>
                    <p class="text-blue-600 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                clip-rule="evenodd" />
                        </svg>
                        VAT: 123456789
                    </p>
                </div>
            </div>

            <!-- Right Column - Bill To -->
            <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                <h3 class="text-lg font-semibold text-gray-700 mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-500" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    BILL TO
                </h3>
                <p class="font-medium text-gray-800 text-lg">
                    <?php echo esc_html($quotation->name . ' ' . $quotation->surname); ?></p>
                <p class="text-gray-600"><?php echo esc_html($quotation->address); ?></p>
                <p class="text-gray-600"><?php echo esc_html($quotation->origin_country); ?></p>

                <div class="mt-4 space-y-1">
                    <p class="text-sm text-gray-600 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                        <?php echo esc_html($quotation->user_email); ?>
                    </p>
                    <p class="text-sm text-gray-600 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path
                                d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                        </svg>
                        <?php echo esc_html($quotation->cell); ?>
                    </p>
                    <p class="text-sm text-gray-600 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                clip-rule="evenodd" />
                        </svg>
                        Destination: <?php echo esc_html($quotation->destination_country); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Shipping Details -->
        <div class="px-8 py-6 bg-blue-50 border-b">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">SHIPPING DETAILS</h3>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <p class="text-sm text-gray-500">Waybill Number</p>
                    <p class="font-medium"><?php echo esc_html($quotation->waybill_no); ?></p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <p class="text-sm text-gray-500">Invoice Number</p>
                    <p class="font-medium"><?php echo esc_html($quotation->product_invoice_number); ?></p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <p class="text-sm text-gray-500">Dispatch Date</p>
                    <p class="font-medium"><?php echo date('M d, Y', strtotime($quotation->dispatch_date)); ?></p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <p class="text-sm text-gray-500">Dimensions (L×W×H)</p>
                    <p class="font-medium">
                        <?php echo esc_html($quotation->item_length); ?>cm ×
                        <?php echo esc_html($quotation->item_width); ?>cm ×
                        <?php echo esc_html($quotation->item_height); ?>cm
                    </p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <p class="text-sm text-gray-500">Total Volume</p>
                    <p class="font-medium"><?php echo esc_html($quotation->total_volume); ?> m³</p>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-sm">
                    <p class="text-sm text-gray-500">Total Weight</p>
                    <p class="font-medium"><?php echo esc_html($quotation->total_mass_kg); ?> kg</p>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="px-8 py-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="text-left py-4 px-4 font-semibold text-gray-700 uppercase text-sm">DESCRIPTION</th>
                            <th class="text-right py-4 px-4 font-semibold text-gray-700 uppercase text-sm">AMOUNT (ZAR)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <!-- Base Shipping -->
                        <tr class="hover:bg-gray-50">
                            <td class="py-4 px-4">
                                <p class="font-medium">International Shipping</p>
                                <p class="text-sm text-gray-500 mt-1">
                                    <?php echo esc_html($quotation->origin_country); ?> to
                                    <?php echo esc_html($quotation->destination_country); ?>
                                    <?php if ($quotation->charge_basis === 'VOLUME'): ?>
                                        <br>Based on volume (<?php echo esc_html($quotation->total_volume); ?> m³)
                                    <?php else: ?>
                                        <br>Based on weight (<?php echo esc_html($quotation->total_mass_kg); ?> kg)
                                    <?php endif; ?>
                                </p>
                            </td>
                            <td class="py-4 px-4 text-right font-medium">
                                R
                                <?php echo number_format($quotation->charge_basis === 'VOLUME' ? $quotation->volume_charge : $quotation->mass_charge, 2); ?>
                            </td>
                        </tr>

                        <!-- Additional Fees -->
                        <?php if ($quotation->include_sad500) : ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 px-4">
                                    <p class="text-gray-600">SAD500 Documentation Fee</p>
                                    <p class="text-sm text-gray-400">Customs documentation</p>
                                </td>
                                <td class="py-4 px-4 text-right">R 350.00</td>
                            </tr>
                        <?php endif; ?>

                        <?php if ($quotation->include_sadc) : ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-4 px-4">
                                    <p class="text-gray-600">SADC Certificate</p>
                                    <p class="text-sm text-gray-400">Regional trade agreement</p>
                                </td>
                                <td class="py-4 px-4 text-right">R 1000.00</td>
                            </tr>
                        <?php endif; ?>

                        <!-- Discount -->
                        <?php if ($quotation->return_load) : ?>
                            <tr class="hover:bg-gray-50 bg-blue-50">
                                <td class="py-4 px-4 text-blue-600 font-medium">
                                    <p>Return Load Discount (10%)</p>
                                    <p class="text-sm text-blue-400">Special promotion</p>
                                </td>
                                <td class="py-4 px-4 text-right text-blue-600 font-medium">- R
                                    <?php echo number_format($subtotal * 0.1, 2); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Total Section -->
            <div class="mt-8 flex justify-end">
                <div class="w-80 bg-gray-50 p-6 rounded-lg border border-gray-200">
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="font-medium text-gray-700">Subtotal:</span>
                            <span class="text-gray-600">R <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="font-medium text-gray-700">VAT (15%):</span>
                            <span class="text-gray-600">R <?php echo number_format($tax, 2); ?></span>
                        </div>
                        <div class="border-t border-gray-200 pt-3 mt-2">
                            <div class="flex justify-between items-center">
                                <span class="font-bold text-gray-800">Total:</span>
                                <span class="text-xl font-bold text-blue-600">R
                                    <?php echo number_format($final_cost, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Notes -->
        <div class="bg-gray-50 p-8 border-t">
            <div class="grid md:grid-cols-2 gap-8">
                <div class="text-sm text-gray-600 space-y-2">
                    <p class="font-medium text-gray-700">Payment Details:</p>
                    <div class="bg-white p-4 rounded-lg">
                        <p><span class="font-medium">Bank:</span> Standard Bank</p>
                        <p><span class="font-medium">Account:</span> 123 456 789</p>
                        <p><span class="font-medium">Branch:</span> 000000</p>
                        <p><span class="font-medium">Reference:</span> <?php echo esc_html($quotation->id); ?></p>
                    </div>
                </div>
                <div class="text-sm text-gray-600 space-y-2">
                    <p class="font-medium text-gray-700">Notes:</p>
                    <div class="bg-white p-4 rounded-lg">
                        <ul class="list-disc pl-5 space-y-1">
                            <li>Payment due within 14 days</li>
                            <li>VAT included where applicable</li>
                            <li>Prices subject to change based on final measurements</li>
                            <li>Contact us for any questions</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="mt-8 flex flex-wrap gap-4 justify-end">
                <button onclick="window.print()"
                    class="bg-white text-blue-600 px-6 py-2 rounded-lg border border-blue-200 hover:bg-blue-50 transition flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z"
                            clip-rule="evenodd" />
                    </svg>
                    Print Quotation
                </button>
                <a href="<?php echo plugins_url('pdf-generator.php', __FILE__); ?>?quotation_id=<?php echo $quotation_id; ?>"
                    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition flex items-center
                gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z"
                            clip-rule="evenodd" />
                    </svg>
                    Download PDF
                </a>
            </div>
        </div>
    </div>

    <?php
}


function quote_instructions()
{
    ?>§
    <div class="bg-white p-6 rounded-lg shadow-lg space-y-6">
        <h2 class="text-2xl font-semibold">Customer Pricing Details</h2>

        <div class="space-y-4">
            <h3 class="font-medium">Constant Costs</h3>
            <div class="flex justify-between">
                <span>SAD500 Fee</span>
                <span>R350</span>
            </div>
            <div class="flex justify-between">
                <span>SADC Certificate</span>
                <span>R1000</span>
            </div>
            <div class="flex justify-between">
                <span>TRA Clearing Fee (in USD)</span>
                <span>$100</span>
            </div>
        </div>

        <div class="space-y-4">
            <h3 class="font-medium">Shipping Rates</h3>
            <div class="flex flex-col space-y-2">
                <div class="flex justify-between">
                    <span>Weight-Based Pricing (R per kg)</span>
                    <span>10 kg - 500 kg: R40.00</span>
                </div>
                <div class="flex justify-between">
                    <span>Volume-Based Pricing (R per m³)</span>
                    <span>0 m³ - 1 m³: R7,500</span>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <h3 class="font-medium">Other Factors</h3>
            <p>Discounts are applied based on volume. Larger volumes receive greater discounts.</p>
        </div>
    </div>

<?php
}
// ✅ Reusable Quotation Form
function quotation_form($type, $quotation = null)
{
    $action = ($type === 'edit') ? 'kit_update_quotation' : 'kit_add_quotation';
    $submit_text = ($type === 'edit') ? 'Update quotation' : 'Add quotation';
    $nonce_action = ($type === 'edit') ? 'kit_edit_quotation_action' : 'kit_add_quotation_action';
    $nonce_name = ($type === 'edit') ? 'kit_quotation_nonce' : 'kit_add_quotation_nonce';
?>
    <form method="POST" id="quotationForm" action="<?php echo admin_url('admin-post.php'); ?>"
        class="max-w-2xl mx-auto bg-white shadow-lg rounded-lg p-6 space-y-4">
        <?php wp_nonce_field($nonce_action, $nonce_name); ?>
        <input type="hidden" name="action" value="<?php echo $action; ?>">

        <h2 class="text-2xl font-bold text-gray-700">Get a Shipping Quote</h2>

        <!-- Customer Information -->
        <div class="space-y-4">
            <h3 class="text-lg font-semibold">Your Information</h3>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-600">Name</label>
                <input type="text" name="customer_name" class="w-full p-2 border rounded-lg" placeholder="Enter your name"
                    required>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-600">Email</label>
                <input type="email" name="customer_email" class="w-full p-2 border rounded-lg"
                    placeholder="Enter your email" required>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-600">Phone</label>
                <input type="tel" name="customer_phone" class="w-full p-2 border rounded-lg"
                    placeholder="Enter your phone number" required>
            </div>
        </div>

        <!-- Sender Information -->
        <div class="space-y-4 mt-6">
            <h3 class="text-lg font-semibold">Sender Information</h3>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-600">Sender Name</label>
                <input type="text" name="sender_name" class="w-full p-2 border rounded-lg" placeholder="Sender's name"
                    required>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-600">Sender Email</label>
                <input type="email" name="sender_email" class="w-full p-2 border rounded-lg" placeholder="Sender's email"
                    required>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-600">Sender Phone</label>
                <input type="tel" name="sender_phone" class="w-full p-2 border rounded-lg"
                    placeholder="Sender's phone number" required>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-600">Sender Address</label>
                <textarea name="sender_address" class="w-full p-2 border rounded-lg" placeholder="Full sender address"
                    required></textarea>
            </div>
        </div>

        <!-- Receiver Information -->
        <div class="space-y-4 mt-6">
            <h3 class="text-lg font-semibold">Receiver Information</h3>
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-600">Receiver Name</label>
                <input type="text" name="receiver_name" class="w-full p-2 border rounded-lg" placeholder="Receiver's name"
                    required>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-600">Receiver Email</label>
                <input type="email" name="receiver_email" class="w-full p-2 border rounded-lg"
                    placeholder="Receiver's email" required>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-600">Receiver Phone</label>
                <input type="tel" name="receiver_phone" class="w-full p-2 border rounded-lg"
                    placeholder="Receiver's phone number" required>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-600">Receiver Address</label>
                <textarea name="receiver_address" class="w-full p-2 border rounded-lg" placeholder="Full receiver address"
                    required></textarea>
            </div>
        </div>

        <!-- Shipping Details -->
        <div class="space-y-4 mt-6">
            <h3 class="text-lg font-semibold">Shipping Details</h3>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-600">Send Location</label>
                <input type="text" name="send_location" class="w-full p-2 border rounded-lg"
                    placeholder="Where is the package being sent from?" required>
            </div>

            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-600">Delivery Address</label>
                <textarea name="delivery_address" class="w-full p-2 border rounded-lg" placeholder="Full delivery address"
                    required></textarea>
            </div>

            <!-- Shipping Method Selection -->
            <div class="mt-4">
                <h4 class="text-md font-medium">Shipping Method</h4>
                <div class="flex space-x-4">
                    <label class="flex items-center space-x-2">
                        <input type="radio" name="shipping_method" value="weight" class="form-radio" checked>
                        <span>Weight-Based</span>
                    </label>
                    <label class="flex items-center space-x-2">
                        <input type="radio" name="shipping_method" value="volume" class="form-radio">
                        <span>Volume-Based</span>
                    </label>
                </div>
            </div>

            <!-- Weight-Based Input -->
            <div id="weightInput" class="mt-4">
                <label class="block text-sm font-medium text-gray-600">Weight (kg)</label>
                <input type="number" name="weight" class="w-full p-2 border rounded-lg"
                    placeholder="Enter weight in kg">
            </div>

            <!-- Volume-Based Input -->
            <div id="volumeInput" class="hidden mt-4">
                <label class="block text-sm font-medium text-gray-600">Dimensions (m)</label>
                <div class="grid grid-cols-3 gap-2">
                    <input type="number" name="length" placeholder="Length"
                        class="p-2 border rounded-lg">
                    <input type="number" name="width" placeholder="Width"
                        class="p-2 border rounded-lg">
                    <input type="number" name="height" placeholder="Height"
                        class="p-2 border rounded-lg">
                </div>
            </div>
        </div>

        <!-- Additional Fees otherz-->
        <div class="space-y-4 mt-6">
            <h3 class="text-lg font-semibold">Additional Fees</h3>
            <label class="flex items-center space-x-2">
                <input type="checkbox" name="include_sad500" class="form-checkbox">
                <span>Include SAD500 Fee (R350)</span>
            </label>
            <label class="flex items-center space-x-2">
                <input type="checkbox" name="include_sadc" class="form-checkbox">
                <span>Include SADC Certificate (R1000)</span>
            </label>
        </div>

        <!-- Return Load Discount -->
        <div class="mt-4">
            <label class="flex items-center space-x-2">
                <input type="checkbox" name="return_load" class="form-checkbox">
                <span>Apply Return Load Discount</span>
            </label>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded-lg mt-6">Get Quote</button>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const weightInput = document.getElementById('weightInput');
            const volumeInput = document.getElementById('volumeInput');
            const shippingMethods = document.querySelectorAll('input[name="shipping_method"]');

            shippingMethods.forEach(method => {
                method.addEventListener('change', function() {
                    if (this.value === 'weight' || this.value === 'mass') {
                        weightInput.classList.remove('hidden');
                        volumeInput.classList.add('hidden');
                    } else {
                        weightInput.classList.add('hidden');
                        volumeInput.classList.remove('hidden');
                    }
                });
            });
        });
    </script>
<?php
}
