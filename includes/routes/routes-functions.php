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
        // Removed duplicate menu registration - Routes is now handled in admin-menu.php
        // add_action('admin_menu', [self::class, 'register_admin_menu']);
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

    // Removed duplicate menu registration - Routes is now handled in admin-menu.php
    // public static function register_admin_menu()
    // {
    //     if (function_exists('add_menu_page')) {
    //         add_menu_page(
    //             'Route Management',
    //             'Routes',
    //             'manage_options',
    //             'route-management',
    //             [self::class, 'plugin_route_management_page'],
    //             'dashicons-location-alt',
    //             6
    //         );
    //         add_submenu_page(
    //             'route-management',
    //             'Create Route',
    //             'Create Route',
    //             'manage_options',
    //             'route-create',
    //             [self::class, 'route_create_page']
    //         );
    //     }
    // }

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
        }));
        
        // Get recent routes for overview
        $recent_routes = array_slice($routes, 0, 5);
        ?>
        
        <div class="wrap">
            <?php
            echo KIT_Commons::showingHeader([
                'title' => 'Route Management',
                'desc' => 'Manage shipping routes and destinations',
            ]);
            ?>
            
            <div class="<?= KIT_Commons::container() ?>">
                <!-- Tab Navigation -->
                <div class="route-tabs" style="margin-bottom: 30px;">
                    <div style="display: flex; background: #f3f4f6; padding: 4px; border-radius: 8px; width: fit-content;">
                        <button id="overview-tab" class="tab-btn active" style="padding: 12px 24px; border-radius: 6px; font-weight: 500; font-size: 14px; transition: all 0.2s ease; background: white; color: #374151; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: none; cursor: pointer;">
                            Overview
                        </button>
                        <button id="create-tab" class="tab-btn" style="padding: 12px 24px; border-radius: 6px; font-weight: 500; font-size: 14px; transition: all 0.2s ease; background: transparent; color: #6b7280; border: none; cursor: pointer;">
                            Create Route
                        </button>
                        <button id="manage-tab" class="tab-btn" style="padding: 12px 24px; border-radius: 6px; font-weight: 500; font-size: 14px; transition: all 0.2s ease; background: transparent; color: #6b7280; border: none; cursor: pointer;">
                            Manage Routes
                        </button>
                    </div>
                </div>

                <!-- Overview Tab Content -->
                <div id="overview-content" class="tab-content">
                    <!-- Statistics Cards -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="width: 48px; height: 48px; background: #dbeafe; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <span style="font-size: 24px; color: #2563eb;">🗺️</span>
                                </div>
                                <div>
                                    <p style="font-size: 14px; color: #6b7280; margin: 0 0 4px 0;">Total Routes</p>
                                    <p style="font-size: 28px; font-weight: 700; color: #111827; margin: 0;"><?php echo $total_routes; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="width: 48px; height: 48px; background: #dcfce7; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <span style="font-size: 24px; color: #16a34a;">✅</span>
                                </div>
                                <div>
                                    <p style="font-size: 14px; color: #6b7280; margin: 0 0 4px 0;">Active Routes</p>
                                    <p style="font-size: 28px; font-weight: 700; color: #111827; margin: 0;"><?php echo $active_routes; ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
                            <div style="display: flex; align-items: center; gap: 16px;">
                                <div style="width: 48px; height: 48px; background: #fee2e2; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                    <span style="font-size: 24px; color: #dc2626;">❌</span>
                                </div>
                                <div>
                                    <p style="font-size: 14px; color: #6b7280; margin: 0 0 4px 0;">Inactive Routes</p>
                                    <p style="font-size: 28px; font-weight: 700; color: #111827; margin: 0;"><?php echo $inactive_routes; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Routes -->
                    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; margin-bottom: 30px;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 20px 0;">Recent Routes</h3>
                        <?php if (!empty($recent_routes)): ?>
                            <div style="display: flex; flex-direction: column; gap: 12px;">
                                <?php foreach ($recent_routes as $route): ?>
                                    <div style="display: flex; justify-content: between; align-items: center; padding: 16px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
                                        <div style="flex: 1;">
                                            <p style="font-weight: 500; color: #111827; margin: 0 0 4px 0;">
                                                <?php echo esc_html($route->origin_country_name); ?> → <?php echo esc_html($route->destination_country_name); ?>
                                            </p>
                                            <p style="font-size: 14px; color: #6b7280; margin: 0;"><?php echo esc_html($route->description); ?></p>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <?php echo $route->is_active ? 
                                                '<span style="padding: 4px 12px; background: #dcfce7; color: #16a34a; border-radius: 20px; font-size: 12px; font-weight: 500;">Active</span>' : 
                                                '<span style="padding: 4px 12px; background: #fee2e2; color: #dc2626; border-radius: 20px; font-size: 12px; font-weight: 500;">Inactive</span>'; ?>
                                            <a href="?page=route-create&route_id=<?php echo $route->route_id; ?>&route_atts=edit_route" style="color: #2563eb; font-size: 14px; text-decoration: none;">Edit</a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #6b7280; text-align: center; padding: 40px;">No routes found</p>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 20px 0;">Quick Actions</h3>
                        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                            <button onclick="switchTab('create')" style="padding: 12px 24px; background: #2563eb; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: background 0.2s ease;">Create New Route</button>
                            <button onclick="switchTab('manage')" style="padding: 12px 24px; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;">View All Routes</button>
                        </div>
                    </div>
                </div>

                <!-- Create Route Tab Content -->
                <div id="create-content" class="tab-content" style="display: none;">
                    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
                        <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 20px 0;">Create New Route</h3>
                        <?php echo self::routeForm(); ?>
                    </div>
                </div>

                <!-- Manage Routes Tab Content -->
                <div id="manage-content" class="tab-content" style="display: none;">
                    <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
                        <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 20px;">
                            <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0;">All Routes</h3>
                            <button onclick="switchTab('create')" style="padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 6px; font-size: 14px; font-weight: 500; cursor: pointer;">Create New</button>
                        </div>
                        
                        <!-- Search and Filter -->
                        <div style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
                            <input type="text" placeholder="Search routes..." style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; flex: 1; min-width: 200px;">
                            <select style="padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: white;">
                                <option value="">All Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <?php
                        $options = [
                            'itemsPerPage' => 10,
                            'currentPage' => $_GET['paged'] ?? 1,
                            'tableClass' => 'min-w-full text-left text-xs text-gray-700',
                            'emptyMessage' => 'No routes found',
                            'id' => 'routesTable',
                        ];

                        $columns = [
                            'route_id' => ['label' => 'Route ID', 'align' => 'text-left'],
                            'origin_country_id' => ['label' => 'Origin', 'align' => 'text-left'],
                            'destination_country_id' => ['label' => 'Destination', 'align' => 'text-left'],
                            'description' => ['label' => 'Description', 'align' => 'text-left'],
                            'status' => ['label' => 'Status', 'align' => 'text-center'],
                            'actions' => ['label' => 'Actions', 'align' => 'text-center'],
                        ];

                        $route_actions = function ($key, $row) {
                            if ($key === 'origin_country_id') {
                                return '<span class="font-medium text-gray-900">' . $row->origin_country_name . '</span>';
                            }
                            if ($key === 'destination_country_id') {
                                return '<span class="font-medium text-gray-900">' . $row->destination_country_name . '</span>';
                            }
                            if ($key === 'status') {
                                return $row->is_active ? 
                                    '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">Active</span>' : 
                                    '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">Inactive</span>';
                            }
                            if ($key === 'actions') {
                                return '<button onclick="editRoute(' . $row->route_id . ')" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Edit
                                </button>';
                            }
                            return htmlspecialchars(($row->$key ?? '') ?: '');
                        };

                        echo KIT_Commons::render_versatile_table($routes, $columns, $route_actions, $options);
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <style>
        .tab-btn:hover {
            background: #e5e7eb !important;
            color: #374151 !important;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .recent-route-card:hover {
            background: #f3f4f6;
        }
        .quick-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        </style>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab switching functionality
            function switchTab(tabName) {
                document.querySelectorAll('.tab-content').forEach(content => {
                    content.style.display = 'none';
                });
                document.querySelectorAll('.tab-btn').forEach(btn => {
                    btn.classList.remove('active');
                    btn.style.background = 'transparent';
                    btn.style.color = '#6b7280';
                    btn.style.boxShadow = 'none';
                });
                const selectedContent = document.getElementById(tabName + '-content');
                if (selectedContent) {
                    selectedContent.style.display = 'block';
                }
                const selectedBtn = document.getElementById(tabName + '-tab');
                if (selectedBtn) {
                    selectedBtn.classList.add('active');
                    selectedBtn.style.background = 'white';
                    selectedBtn.style.color = '#374151';
                    selectedBtn.style.boxShadow = '0 1px 3px rgba(0,0,0,0.1)';
                }
            }
            window.switchTab = switchTab;
            document.getElementById('overview-tab').addEventListener('click', () => switchTab('overview'));
            document.getElementById('create-tab').addEventListener('click', () => switchTab('create'));
            document.getElementById('manage-tab').addEventListener('click', () => switchTab('manage'));
        });

        function editRoute(routeId) {
            window.location.href = '?page=route-create&route_id=' + routeId + '&route_atts=edit_route';
        }
        </script>


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
        <div style="display: flex; flex-direction: column; gap: 24px;">
            <!-- Origin Section -->
            <div>
                <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px 0;">Origin Details</h3>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin: 0 0 8px 0;">Origin Country & City</label>
                        <div style="background: #f9fafb; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsOrigin.php'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Destination Section -->
            <div>
                <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px 0;">Destination Details</h3>
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin: 0 0 8px 0;">Destination Country & City</label>
                        <div style="background: #f9fafb; padding: 16px; border-radius: 8px; border: 1px solid #e5e7eb;">
                            <?php require(COURIER_FINANCE_PLUGIN_PATH . 'includes/components/selectsDestination.php'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Route Status -->
            <div>
                <h3 style="font-size: 16px; font-weight: 600; color: #111827; margin: 0 0 16px 0;">Route Status</h3>
                <div style="display: flex; gap: 16px;">
                    <?php
                    $statuses = [
                        'active' => 'Active',
                        'inactive' => 'Inactive'
                    ];
                    foreach ($statuses as $value => $label) {
                        $isChecked = ($route_status === $value || $route_status === ($value === 'active' ? 1 : 0));
                    ?>
                        <label class="route-status-radio" style="display: block; width: 100px; height: 80px; border-radius: 12px; border: 2px solid; cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; transition: all 0.2s ease; user-select: none; <?php echo $isChecked ? 'border-color: #2563eb; background: #dbeafe; color: #1e40af; box-shadow: 0 4px 6px rgba(37, 99, 235, 0.1);' : 'border-color: #d1d5db; background: white; color: #6b7280;'; ?>">
                            <input
                                type="radio"
                                name="route_status"
                                id="route_status_<?php echo esc_attr($value); ?>"
                                value="<?php echo esc_attr($value); ?>"
                                style="display: none;"
                                <?php checked($isChecked); ?> />
                            <span><?php echo esc_html($label); ?></span>
                        </label>
                    <?php } ?>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const radios = document.querySelectorAll('input[name="route_status"]');
            radios.forEach(function(radio) {
                radio.addEventListener('change', function() {
                    document.querySelectorAll('.route-status-radio').forEach(function(label) {
                        label.style.borderColor = '#d1d5db';
                        label.style.background = 'white';
                        label.style.color = '#6b7280';
                        label.style.boxShadow = 'none';
                    });
                    if (radio.checked) {
                        radio.parentElement.style.borderColor = '#2563eb';
                        radio.parentElement.style.background = '#dbeafe';
                        radio.parentElement.style.color = '#1e40af';
                        radio.parentElement.style.boxShadow = '0 4px 6px rgba(37, 99, 235, 0.1)';
                    }
                });
            });
        });
        </script>
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

        $is_edit_mode = ($route_id && $route_atts == 'edit_route');
        $page_title = $is_edit_mode ? 'Edit Route' : 'Create Route';
        $page_description = $is_edit_mode ? 'Update route details and settings' : 'Create a new shipping route';
        
    ?>
        <div class="wrap">
            <?php
            echo KIT_Commons::showingHeader([
                'title' => $page_title,
                'desc' => $page_description,
            ]);
            ?>
            
            <div class="<?= KIT_Commons::container() ?>">
                <div style="background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb;">
                    <form id="route-create-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                        <input type="hidden" name="action" value="create_route">
                        <input type="hidden" name="security" value="<?php echo wp_create_nonce('create_route'); ?>">
                        <?php if ($is_edit_mode) { ?>
                            <input type="hidden" name="route_id" value="<?php echo $route_id; ?>">
                        <?php } ?>
                        
                        <?php if ($is_edit_mode) { ?>
                            <?php echo self::routeForm($route); ?>
                        <?php } else { ?>
                            <?php echo self::routeForm(); ?>
                        <?php } ?>
                        
                        <div style="display: flex; gap: 12px; margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
                            <button type="submit" style="padding: 12px 24px; background: #2563eb; color: white; border: none; border-radius: 8px; font-weight: 500; cursor: pointer; transition: background 0.2s ease;">
                                <?php echo $is_edit_mode ? 'Update Route' : 'Create Route'; ?>
                            </button>
                            <button type="button" onclick="window.location.href='?page=route-management'" style="padding: 12px 24px; background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s ease;">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
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
    
        // Tables
        $operating_countries_table = $wpdb->prefix . 'kit_operating_countries';
        $shipping_directions_table = $wpdb->prefix . 'kit_shipping_directions';
    
        $origin_country_name = '';
        $destination_country_name = '';
    
        // ✅ Fetch and patch country names and is_active = 1
        if ($origin_country_id && $destination_country_id) {
            // Get origin country
            $origin_row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT country_name, is_active FROM $operating_countries_table WHERE id = %d",
                    $origin_country_id
                )
            );
    
            // Get destination country
            $destination_row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT country_name, is_active FROM $operating_countries_table WHERE id = %d",
                    $destination_country_id
                )
            );
    
            // Update is_active = 1 if needed
            if ($origin_row && $origin_row->is_active == 0) {
                $wpdb->update(
                    $operating_countries_table,
                    ['is_active' => 1],
                    ['id' => $origin_country_id],
                    ['%d'],
                    ['%d']
                );
            }
    
            if ($destination_row && $destination_row->is_active == 0) {
                $wpdb->update(
                    $operating_countries_table,
                    ['is_active' => 1],
                    ['id' => $destination_country_id],
                    ['%d'],
                    ['%d']
                );
            }
    
            $origin_country_name = $origin_row ? $origin_row->country_name : '';
            $destination_country_name = $destination_row ? $destination_row->country_name : '';
        }
    
        // Create route description
        $route_description = $origin_country_name . ' to ' . $destination_country_name;
    
        $route_status = isset($_POST['route_status']) && $_POST['route_status'] === 'active' ? 1 : 0;
    
        $data = [
            'origin_country_id'      => $origin_country_id,
            'destination_country_id' => $destination_country_id,
            'description'            => $route_description,
            'is_active'              => $route_status,
            'created_at'             => function_exists('current_time') ? current_time('mysql') : date('Y-m-d H:i:s'),
        ];
    
        $is_ajax = defined('DOING_AJAX') && DOING_AJAX;
    
        $result = $wpdb->insert($shipping_directions_table, $data);
    
        if ($is_ajax) {
            if ($result) {
                wp_send_json_success('Route created successfully');
            } else {
                wp_send_json_error('Failed to create route');
            }
        } else {
            if ($result) {
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
