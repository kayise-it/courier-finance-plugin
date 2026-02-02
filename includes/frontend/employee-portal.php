<?php
/**
 * Frontend Employee Portal
 * Custom login and dashboard for employees (data_capturer, manager) - no wp-admin.
 * Non-admin employees cannot see prices.
 *
 * @package CourierFinancePlugin
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies
require_once plugin_dir_path(__FILE__) . 'employee-login.php';
require_once plugin_dir_path(__FILE__) . 'employee-dashboard.php';

/**
 * Initialize employee portal
 */
function kit_employee_portal_init() {
    add_shortcode('kit_employee_login', 'kit_employee_login_shortcode');
    add_shortcode('kit_employee_dashboard', 'kit_employee_dashboard_shortcode');

    // Redirect employees after login
    add_filter('login_redirect', 'kit_employee_login_redirect', 10, 3);

    // Redirect employees from wp-admin to frontend dashboard when appropriate
    add_action('admin_init', 'kit_employee_admin_redirect', 1);

    // Redirect logged-in employees away from login page (before shortcode renders)
    add_action('template_redirect', 'kit_employee_login_page_redirect');

    // Enqueue dashboard assets when page has dashboard shortcode (must be before wp_head)
    add_action('wp', 'kit_employee_maybe_enqueue_dashboard_assets');

    // Enqueue login form styles
    add_action('wp_enqueue_scripts', 'kit_employee_enqueue_login_styles', 20);
}

/**
 * Redirect employees to frontend dashboard after login (when using wp-login.php)
 */
function kit_employee_login_redirect($redirect_to, $requested_redirect_to, $user) {
    if (is_wp_error($user) || !$user) {
        return $redirect_to;
    }

    $roles = (array) $user->roles;
    $is_employee = in_array('data_capturer', $roles) || in_array('manager', $roles);
    $is_admin = in_array('administrator', $roles) || user_can($user, 'manage_options');

    if ($is_employee && !$is_admin) {
        return apply_filters('kit_employee_dashboard_url', home_url('/employee-dashboard/'));
    }

    return $redirect_to;
}

/**
 * Redirect logged-in employees from login page to dashboard
 */
function kit_employee_login_page_redirect() {
    if (!is_user_logged_in() || !is_singular()) {
        return;
    }
    global $post;
    if (!$post || !has_shortcode($post->post_content, 'kit_employee_login')) {
        return;
    }
    if (!class_exists('KIT_User_Roles') || !KIT_User_Roles::can_view_waybills()) {
        return;
    }
    $dashboard_url = apply_filters('kit_employee_dashboard_url', home_url('/employee-dashboard/'));
    if (KIT_User_Roles::can_see_prices()) {
        $dashboard_url = admin_url('admin.php?page=08600-dashboard');
    }
    wp_safe_redirect($dashboard_url);
    exit;
}

/**
 * Redirect employees from wp-admin when they land on main dashboard
 */
function kit_employee_admin_redirect() {
    if (!is_user_logged_in()) {
        return;
    }

    $user = wp_get_current_user();
    $roles = (array) $user->roles;
    $is_employee = in_array('data_capturer', $roles) || in_array('manager', $roles);
    $is_admin = in_array('administrator', $roles) || current_user_can('manage_options');

    if (!$is_employee || $is_admin) {
        return;
    }

    $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

    // Redirect when on main WP dashboard (no page) or 08600-dashboard (we want frontend for employees)
    if ($page === '' || $page === '08600-dashboard' || $page === 'index.php') {
        $dashboard_url = apply_filters('kit_employee_dashboard_url', home_url('/employee-dashboard/'));
        wp_safe_redirect($dashboard_url);
        exit;
    }
}

/**
 * Enqueue dashboard assets when page has dashboard shortcode
 */
function kit_employee_maybe_enqueue_dashboard_assets() {
    if (!is_singular()) {
        return;
    }
    global $post;
    if (!$post || !has_shortcode($post->post_content, 'kit_employee_dashboard')) {
        return;
    }
    kit_employee_enqueue_dashboard_assets();
}

/**
 * Enqueue dashboard CSS/JS for frontend dashboard
 */
function kit_employee_enqueue_dashboard_assets() {
    static $enqueued = false;
    if ($enqueued) {
        return;
    }
    $enqueued = true;

    $plugin_url = defined('COURIER_FINANCE_PLUGIN_URL') ? COURIER_FINANCE_PLUGIN_URL : plugin_dir_url(dirname(dirname(__FILE__)));
    wp_enqueue_style('kit-tailwindcss', $plugin_url . 'assets/css/frontend.css', array(), '1.0');
    wp_enqueue_style('kit-dashboard-css', $plugin_url . 'assets/css/dashboard.css', array('kit-tailwindcss'), '1.0');
    wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4');
    wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true);
    wp_enqueue_script('kit-dashboard-map', $plugin_url . 'assets/js/dashboard-map.js', array('jquery', 'leaflet-js'), '1.0', true);

    global $wpdb;
    $default_map_address = 'Unit 1, Kya North Park, 28 Bernie St, Kya Sands, Randburg, 2188';
    $map_center_address = '';
    if ($wpdb && isset($wpdb->prefix)) {
        $table = $wpdb->prefix . 'kit_company_details';
        if ($wpdb->get_var("SHOW TABLES LIKE '" . esc_sql($table) . "'") === $table) {
            $map_center_address = (string) $wpdb->get_var('SELECT company_address FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1');
        }
    }
    $map_center_address = trim($map_center_address) !== '' ? trim($map_center_address) : $default_map_address;

    wp_localize_script('kit-dashboard-map', 'kitDashboardMap', array(
        'ajaxurl'              => admin_url('admin-ajax.php'),
        'nonce'                => wp_create_nonce('kit_dashboard_map'),
        'map_center_address'   => $map_center_address,
        'map_geocode_query'    => 'Kya Sands, Randburg, South Africa',
        'map_fallback_lat'     => -26.0789,
        'map_fallback_lng'     => 28.0123,
        'map_logo_url'         => $plugin_url . 'img/logo.png',
    ));
}

/**
 * Enqueue login form styles
 */
function kit_employee_enqueue_login_styles() {
    global $post;
    if (!$post || !has_shortcode($post->post_content, 'kit_employee_login')) {
        return;
    }

    $plugin_url = defined('COURIER_FINANCE_PLUGIN_URL') ? COURIER_FINANCE_PLUGIN_URL : plugin_dir_url(dirname(dirname(__FILE__)));
    wp_enqueue_style('kit-employee-login', $plugin_url . 'assets/css/frontend.css', array(), '1.0');
    wp_add_inline_style('kit-employee-login', kit_employee_login_inline_css());
}

/**
 * Inline CSS for employee login form
 */
function kit_employee_login_inline_css() {
    return '
        .kit-employee-login-wrap { max-width: 400px; margin: 2rem auto; padding: 1rem; }
        .kit-employee-login-box { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 2rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .kit-employee-login-title { margin: 0 0 0.5rem; font-size: 1.5rem; font-weight: 600; }
        .kit-employee-login-desc { color: #6b7280; margin-bottom: 1.5rem; }
        .kit-employee-login-error { background: #fef2f2; color: #dc2626; padding: 0.75rem; border-radius: 6px; margin-bottom: 1rem; }
        .kit-employee-login-form label { display: block; margin-bottom: 0.25rem; font-weight: 500; }
        .kit-employee-login-form input[type="text"],
        .kit-employee-login-form input[type="password"] { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 6px; margin-bottom: 1rem; }
        .kit-employee-remember { margin: 1rem 0; }
        .kit-employee-submit { margin-top: 1.5rem; }
        .kit-employee-submit .button { padding: 0.5rem 1.5rem; cursor: pointer; }
        .kit-employee-dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding: 0.5rem 0; }
        .kit-employee-greeting { font-weight: 500; }
        .kit-employee-logout { color: #6b7280; text-decoration: none; }
        .kit-employee-logout:hover { color: #111; }
        .kit-employee-dashboard-wrap .kit-dashboard-wrap { max-width: 1400px; margin: 0 auto; padding: 1rem; }
    ';
}

add_action('init', 'kit_employee_portal_init', 20);
