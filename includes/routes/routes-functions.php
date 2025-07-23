<?php
// includes/routes/routes-functions.php

if (! defined('ABSPATH')) {
    exit;
}

class KIT_Routes
{
    public static function init()
    {
        // Register admin menu
        add_action('admin_menu', [self::class, 'register_admin_menu']);
        add_action('admin_post_create_route', [self::class, 'create_route']);
        add_action('wp_ajax_nopriv_create_route', [self::class, 'create_route']);
        add_action('wp_ajax_delete_route', [self::class, 'delete_route']);
        add_action('wp_ajax_nopriv_delete_route', [self::class, 'delete_route']);
        add_action('wp_ajax_update_route', [self::class, 'update_route']);
        add_action('wp_ajax_nopriv_update_route', [self::class, 'update_route']);
        add_action('wp_ajax_get_route', [self::class, 'get_route']);
        add_action('wp_ajax_nopriv_get_route', [self::class, 'get_route']);
        add_action('wp_ajax_get_routes', [self::class, 'get_routes']);
        add_action('wp_ajax_nopriv_get_routes', [self::class, 'get_routes']);
        add_action('wp_ajax_get_route_by_id', [self::class, 'get_route_by_id']);
        add_action('wp_ajax_nopriv_get_route_by_id', [self::class, 'get_route_by_id']);
        add_action('wp_ajax_get_route_by_name', [self::class, 'get_route_by_name']);
        add_action('wp_ajax_nopriv_get_route_by_name', [self::class, 'get_route_by_name']);
        add_action('wp_ajax_get_route_by_description', [self::class, 'get_route_by_description']);
        // Register AJAX handlers here as needed
        // add_action('wp_ajax_...', [self::class, 'ajax_handler']);
    }

    public static function register_admin_menu()
    {
        if (function_exists('add_menu_page')) {
            add_menu_page(
                'Route Management',
                'Routes',
                'manage_options',
                'route-management',
                [self::class, 'plugin_route_management_page'],
                'dashicons-location-alt',
                6
            );
            add_submenu_page(
                'route-management',
                'Create Route',
                'Create Route',
                'manage_options',
                'route-create',
                [self::class, 'route_create_page']
            );
        }
    }

    public static function get_country_name_by_id($country_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_operating_countries';
        $country_name = $wpdb->get_var($wpdb->prepare("SELECT country_name FROM $table WHERE id = %d", $country_id));
        return $country_name;
    }

    public static function get_routes()
    {
        global $wpdb;
        //Left join the wp_kit_operating_countries table to the wp_kit_shipping_directions table on the origin_country_id and destination_country_id
        $routes = $wpdb->get_results("SELECT sd.id as route_id, sd.origin_country_id, sd.destination_country_id, sd.description, sd.is_active, oc.country_name as origin_country_name, dc.country_name as destination_country_name
        FROM {$wpdb->prefix}kit_shipping_directions sd
        LEFT JOIN {$wpdb->prefix}kit_operating_countries oc ON sd.origin_country_id = oc.id
        LEFT JOIN {$wpdb->prefix}kit_operating_countries dc ON sd.destination_country_id = dc.id");

        return $routes;
    }

    public static function plugin_route_management_page()
    {
        $countries = KIT_Commons::get_countries();
        $cities = KIT_Commons::get_cities();

        $origin_country = isset($_GET['origin_country']) ? $_GET['origin_country'] : '';
        $destination_country = isset($_GET['destination_country']) ? $_GET['destination_country'] : '';
        $status = isset($_GET['status']) ? $_GET['status'] : '';

        $routes = self::get_routes();
        $total_routes = count($routes);
        $active_routes = count(array_filter($routes, function ($route) {
            return $route->is_active == 1;
        }));
        $inactive_routes = count(array_filter($routes, function ($route) {
            return $route->is_active == 0;
        })); ?>
        <div class="wrap">
            <?php echo KIT_Commons::showingHeader([
                'title' => 'Route List',
                'desc' => "342234",
            ]); ?>
            <div class="<?php echo KIT_Commons::container(); ?>">
                <h1 class="wp-heading-inline">Route List</h1>
                <a href="?page=route-create" id="add-new-route" class="page-title-action">Add New Route</a>
                <a href="#" id="export-routes" class="page-title-action">Export</a>
                <a href="#" id="import-routes" class="page-title-action">Import</a>
                <hr class="wp-header-end">

                <!-- Analytics Widgets -->
                <div class="route-analytics-widgets" style="display: flex; gap: 20px; margin-bottom: 20px;">
                    <div class="widget" style="background: #f8fafc; padding: 16px; border-radius: 8px; min-width: 120px;">
                        <strong id="total-routes"><?php echo $total_routes; ?></strong><br>Total Routes
                    </div>
                    <div class="widget" style="background: #f8fafc; padding: 16px; border-radius: 8px; min-width: 120px;">
                        <strong id="active-routes"><?php echo $active_routes; ?></strong><br>Active
                    </div>
                    <div class="widget" style="background: #f8fafc; padding: 16px; border-radius: 8px; min-width: 120px;">
                        <strong id="inactive-routes"><?php echo $inactive_routes; ?></strong><br>Inactive
                    </div>
                </div>

                <!-- Route Table using KIT_Commons::render_versatile_table -->
                <?php
                $columns = [
                    'route_id' => ['label' => 'Route ID', 'align' => 'text-left px-4 py-2'],
                    'origin_country_id' => ['label' => 'Origin', 'align' => 'text-left px-4 py-2'],
                    'destination_country_id' => ['label' => 'Destination', 'align' => 'text-left px-4 py-2'],
                    'description' => ['label' => 'Description', 'align' => 'text-left px-4 py-2'],
                    'status' => ['label' => 'Status', 'align' => 'text-center px-4 py-2'],
                    'actions' => ['label' => 'Actions', 'align' => 'text-center px-4 py-2'],
                ];
                $cell_callback = function ($key, $row) {
                    if ($key === 'origin_country_id') {
                        //Return the country name from the wp_kit_operating_countries table
                        return $row->origin_country_name;
                    }
                    if ($key === 'destination_country_id') {
                        return $row->destination_country_name;
                    }
                    if ($key === 'status') {
                        //Return a badge with the color of the status tailwind class
                        return $row->is_active ? '<span class="bg-green-500 text-white px-2 py-1 rounded-md">Active</span>' : '<span class="bg-red-500 text-white px-2 py-1 rounded-md">Inactive</span>';
                    }
                    if ($key === 'actions') {
                        return '<a href="?page=route-create&route_id=' . $row->route_id . '&route_atts=edit_route" class="text-blue-600 hover:underline">Edit</a>';
                    }
                    return htmlspecialchars(($row->$key ?? '') ?: '');
                };

                echo KIT_Commons::render_versatile_table($routes, $columns, $cell_callback, ['itemsPerPage' => 10]);
                ?>
            </div>
            <script>
                // Placeholder for AJAX and modal logic
                // You would implement JS to handle opening the modal, populating fields, submitting via AJAX, etc.
            </script>
        </div>
    <?php
    }

    /**
     * Render the Route form for create/edit.
     * @param array|null $routeData
     */
    public static function routeForm($routeData = null)
    {


        // Get countries and cities
        if ($routeData !== null) {
            $origin_country_id = kit_Commons::verifyint($routeData->origin_country_id);

            $destination_country_id = kit_Commons::verifyint($routeData->destination_country_id);

            $route_status = $routeData->is_active;

            $currentCountryId = $origin_country_id;
        } else {
            $route_status = 'active'; // or 1, depending on your logic
        }

    ?>
        <table class="form-table">
            <tr>
                <th><label for="origin_city">Origin City</label></th>
                <td>
                    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsOrigin.php'); ?>
                </td>
            </tr>
            <tr>
                <th><label for="destination_country">Destination Country</label></th>
                <td>
                    <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsDestination.php'); ?>
                </td>
            </tr>
            <tr>
                <th><label for="route_status">Status</label></th>
                <td>

                    <div class="flex gap-6">
                        <?php
                        $statuses = [
                            'active' => 'Active',
                            'inactive' => 'Inactive'
                        ];
                        foreach ($statuses as $value => $label) {
                            $isChecked = ($route_status === $value || $route_status === ($value === 'active' ? 1 : 0));
                        ?>
                            <label class="route-status-radio block w-[80px] h-[80px] rounded-xl border-2 cursor-pointer flex flex-col items-center justify-center text-sm font-semibold transition-all duration-200
                                <?php echo $isChecked ? 'border-blue-600 bg-blue-50 text-blue-800 shadow' : 'border-gray-300 bg-white text-gray-700'; ?>
                                hover:border-blue-400 hover:bg-blue-100"
                                style="user-select: none;">
                                <input
                                    type="radio"
                                    name="route_status"
                                    id="route_status_<?php echo esc_attr($value); ?>"
                                    value="<?php echo esc_attr($value); ?>"
                                    class="sr-only peer"
                                    <?php checked($isChecked); ?> />
                                <span><?php echo esc_html($label); ?></span>
                            </label>
                        <?php } ?>
                    </div>
                    <script>
                        // Add active class on click
                        document.addEventListener('DOMContentLoaded', function() {
                            const radios = document.querySelectorAll('input[name="route_status"]');
                            radios.forEach(function(radio) {
                                radio.addEventListener('change', function() {
                                    document.querySelectorAll('.route-status-radio').forEach(function(label) {
                                        label.classList.remove('border-blue-600', 'bg-blue-50', 'text-blue-800', 'shadow-lg');
                                        label.classList.add('border-gray-300', 'bg-white', 'text-gray-700');
                                    });
                                    if (radio.checked) {
                                        radio.parentElement.classList.remove('border-gray-300', 'bg-white', 'text-gray-700');
                                        radio.parentElement.classList.add('border-blue-600', 'bg-blue-50', 'text-blue-800', 'shadow-lg');
                                    }
                                });
                            });
                        });
                    </script>
                </td>
            </tr>
        </table>
    <?php
    }

    public static function get_route_by_id($route_id)
    {
        global $wpdb;
        $route = $wpdb->get_row("SELECT sd.*, oc.country_name as origin_country_name, dc.country_name as destination_country_name
        FROM {$wpdb->prefix}kit_shipping_directions sd
        LEFT JOIN {$wpdb->prefix}kit_operating_countries oc ON sd.origin_country_id = oc.id
        LEFT JOIN {$wpdb->prefix}kit_operating_countries dc ON sd.destination_country_id = dc.id
        WHERE sd.id = $route_id");

        return $route;
    }

    public static function route_create_page()
    {

        //route_id=1&route_atts=edit_route if route_id is set, then we are editing a route
        $route_id = isset($_GET['route_id']) ? $_GET['route_id'] : '';
        $route_atts = isset($_GET['route_atts']) ? $_GET['route_atts'] : '';
        if ($route_id && $route_atts == 'edit_route') {
            $route = self::get_route_by_id($route_id);
        }

        $is_page = isset($_GET['page']) ? $_GET['page'] : null;
        $is_route_id = isset($_GET['route_id']) ? $_GET['route_id'] : null;
        $is_route_atts = isset($_GET['route_atts']) ? $_GET['route_atts'] : null;

        $countries = KIT_Commons::get_countries();
        $cities = KIT_Commons::get_cities();

        $origin_country = isset($_GET['origin_country']) ? $_GET['origin_country'] : '';
        $origin_city = isset($_GET['origin_city']) ? $_GET['origin_city'] : '';
        $destination_country = isset($_GET['destination_country']) ? $_GET['destination_country'] : '';
        $destination_city = isset($_GET['destination_city']) ? $_GET['destination_city'] : '';
        $route_description = isset($_GET['route_description']) ? $_GET['route_description'] : '';
        $route_status = isset($_GET['route_status']) ? $_GET['route_status'] : '';


        //page=route-create&route_id=1&route_atts=edit_route
    ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?= ($is_page) ? 'Edit Route' : 'Create Route'; ?></h1>
            <form id="route-create-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="create_route">
                <input type="hidden" name="security" value="<?php echo wp_create_nonce('create_route'); ?>">
                <?php if ($route_id && $route_atts == 'edit_route') { ?>
                    <input type="hidden" name="route_id" value="<?php echo $route_id; ?>">
                <?php }
                //if route_id is set, then we are editing a route
                //if route_id is set, then we are editing a route
                if ($route_id && $route_atts == 'edit_route') {
                    echo self::routeForm($route);
                } else {
                    echo self::routeForm();
                }
                ?>
                <p class="submit">
                    <button type="submit" class="button button-primary">Save Route</button>
                    <button type="button" class="button route-create-cancel">Cancel</button>
                </p>
            </form>
        </div>
<?php
    }

    public static function create_route()
    {
        global $wpdb;

        // Sanitize/validate input
        $route_name = isset($_POST['route_name']) ? sanitize_text_field($_POST['route_name']) : '';
        $origin_country_id = isset($_POST['origin_country']) ? intval($_POST['origin_country']) : 0;
        $origin_city_id = isset($_POST['origin_city']) ? intval($_POST['origin_city']) : 0;
        $destination_country_id = isset($_POST['destination_country']) ? intval($_POST['destination_country']) : 0;
        $destination_city_id = isset($_POST['destination_city']) ? intval($_POST['destination_city']) : 0;

        // Fetch country names from wp_kit_operating_countries using $origin_country_id and $destination_country_id
        $operating_countries_table = $wpdb->prefix . 'kit_operating_countries';
        $origin_country_name = '';
        $destination_country_name = '';

        if ($origin_country_id && $destination_country_id) {
            $origin_row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT country_name FROM $operating_countries_table WHERE id = %d",
                    $origin_country_id
                )
            );
            $destination_row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT country_name FROM $operating_countries_table WHERE id = %d",
                    $destination_country_id
                )
            );
            $origin_country_name = $origin_row ? $origin_row->country_name : '';
            $destination_country_name = $destination_row ? $destination_row->country_name : '';
        }

        $route_description = $origin_country_name . ' to ' . $destination_country_name;

        $route_status = isset($_POST['route_status']) && $_POST['route_status'] === 'active' ? 1 : 0;

        $table = $wpdb->prefix . 'kit_shipping_directions';
        $data = [
            'origin_country_id'      => $origin_country_id,
            'destination_country_id' => $destination_country_id,
            'description'            => $route_description,
            'is_active'              => $route_status,
            'created_at'             => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s'),
        ];

        $is_ajax = defined('DOING_AJAX') && DOING_AJAX;

        $result = $wpdb->insert($table, $data);

        if ($is_ajax) {
            if ($result) {
                if (function_exists('wp_send_json_success')) {
                    wp_send_json_success('Route created successfully');
                } else {
                    echo json_encode(['success' => true, 'data' => 'Route created successfully']);
                    wp_die();
                }
            } else {
                if (function_exists('wp_send_json_error')) {
                    wp_send_json_error('Failed to create route');
                } else {
                    echo json_encode(['success' => false, 'data' => 'Failed to create route']);
                    wp_die();
                }
            }
        } else {
            // Normal PHP request (form POST)
            if ($result) {
                // Redirect or show a message
                // You can adjust this as needed for your UI
                echo '<div class="notice notice-success"><p>Route created successfully.</p></div>';
                wp_redirect(admin_url('admin.php?page=route-management'));
                exit;
            } else {
                echo '<div class="notice notice-error"><p>Failed to create route.</p></div>';
            }
        }
    }
}

KIT_Routes::init();
