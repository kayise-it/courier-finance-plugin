<?php

// Exit if accessed directly
if (! defined('ABSPATH')) {
    exit;
}

class KIT_Company
{
    public static function get_details()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_company_details';
        return $wpdb->get_row("SELECT * FROM $table ORDER BY id ASC LIMIT 1");
    }

    public static function update_details($data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'kit_company_details';
        $fields = [
            'company_name' => sanitize_text_field($data['company_name'] ?? ''),
            'company_address' => sanitize_textarea_field($data['company_address'] ?? ''),
            'company_email' => sanitize_email($data['company_email'] ?? ''),
            'company_phone' => sanitize_text_field($data['company_phone'] ?? ''),
            'company_website' => esc_url_raw($data['company_website'] ?? ''),
            'company_registration' => sanitize_text_field($data['company_registration'] ?? ''),
            'company_vat_number' => sanitize_text_field($data['company_vat_number'] ?? ''),
            'bank_name' => sanitize_text_field($data['bank_name'] ?? ''),
            'account_number' => sanitize_text_field($data['account_number'] ?? ''),
            'branch_code' => sanitize_text_field($data['branch_code'] ?? ''),
            'account_type' => sanitize_text_field($data['account_type'] ?? ''),
            'account_holder' => sanitize_text_field($data['account_holder'] ?? ''),
            'swift_code' => sanitize_text_field($data['swift_code'] ?? ''),
            'iban' => sanitize_text_field($data['iban'] ?? ''),
            'vat_percentage' => floatval($data['vat_percentage'] ?? 15),
            'sadc_charge' => floatval($data['sadc_charge'] ?? 0),
            'sad500_charge' => floatval($data['sad500_charge'] ?? 0),
        ];
        $id = $wpdb->get_var("SELECT id FROM $table ORDER BY id ASC LIMIT 1");
        if ($id) {
            return (false !== $wpdb->update($table, $fields, ['id' => intval($id)]));
        }
        return (bool) $wpdb->insert($table, $fields);
    }
}

/**
 * Check if the service name already exists in the database.
 *
 * @param string $service_name The service name to check.
 * @return bool True if exists, false otherwise.
 */
function service_name_exists($service_name)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_services';

    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE name = %s",
        $service_name
    ));

    return ($result > 0);
}

/**
 * Create a new service.
 *
 * @param string $name The service name.
 * @param string $description The service description.
 * @param string $image The service image URL.
 * @return bool|int The inserted service ID on success, false on failure.
 */
function create_service($name, $description, $image = null)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_services';

    if (service_name_exists($name)) {
        return new WP_Error('service_exists', 'A service with this name already exists.');
    }

    $result = $wpdb->insert(
        $table_name,
        array(
            'name' => $name,
            'description' => $description,
            'image' => $image,
        ),
        array(
            '%s', // name
            '%s', // description
            '%s'  // image (can be null)
        )
    );

    return ($result !== false) ? $wpdb->insert_id : false;
}

/**
 * Get all services.
 *
 * @return array Array of services.
 */
function get_all_services()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_services';

    return $wpdb->get_results("SELECT * FROM $table_name");
}

/**
 * Get a single service by ID.
 *
 * @param int $service_id The service ID.
 * @return object|null The service object, or null if not found.
 */
function get_service_by_id($service_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_services';

    return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $service_id));
}

/**
 * Update an existing service.
 *
 * @param int $service_id The service ID.
 * @param string $name The new service name.
 * @param string $description The new service description.
 * @param string $image The new service image URL.
 * @return bool True on success, false on failure.
 */
function update_service($service_id, $name, $description, $image = null)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_services';

    if (service_name_exists($name)) {
        return new WP_Error('service_exists', 'A service with this name already exists.');
    }

    $result = $wpdb->update(
        $table_name,
        array(
            'name' => $name,
            'description' => $description,
            'image' => $image,
        ),
        array('id' => $service_id),
        array(
            '%s', // name
            '%s', // description
            '%s'  // image (can be null)
        ),
        array('%d') // id
    );

    return ($result !== false);
}

/**
 * Delete a service.
 *
 * @param int $service_id The service ID.
 * @return bool True on success, false on failure.
 */
function delete_service($service_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'kit_services';

    $result = $wpdb->delete(
        $table_name,
        array('id' => $service_id),
        array('%d')
    );

    return ($result !== false);
}

function kit_services_shortcode() {
    ob_start(); // Start output buffering

    // Hardcoded to match services.php exactly with icons
    $services = array(
        array(
            'name' => 'Freight Forwarding',
            'description' => 'We provide end-to-end freight forwarding services across air, sea, and road freight, ensuring timely delivery.',
            'icon' => 'flaticon-air-freight'
        ),
        array(
            'name' => 'Customs Clearance',
            'description' => 'We offer efficient customs clearance services, ensuring smooth compliance with regulations across the SADC region.',
            'icon' => 'flaticon-delivery-man'
        ),
        array(
            'name' => 'Warehousing',
            'description' => 'Our secure and efficient warehousing services use the latest technology for optimal storage and product distribution.',
            'icon' => 'flaticon-wholesale'
        ),
        array(
            'name' => 'Transportation',
            'description' => 'We provide reliable transportation services with a well-maintained fleet, ensuring safety, punctuality, and efficiency.',
            'icon' => 'flaticon-truck'
        ),
        array(
            'name' => 'Distribution',
            'description' => 'Our distribution solutions streamline your supply chain, ensuring your products reach the market efficiently and timely.',
            'icon' => 'flaticon-pallet'
        )
    );

    // Output services in a list
    $output = '<div class="service-slider owl-carousel">';
    foreach ( $services as $service ) {
        $output .= '<div class="single-serv-item">';
        $output .= '<div class="serv-icon">';
        $output .= '<i class="' . esc_html( $service['icon'] ) . '"></i>';
        $output .= '</div>';
        $output .= '<div class="serv-content">';
        $output .= '<h5>' . esc_html( $service['name'] ) . '</h5>';
        $output .= '<p>' . esc_html( $service['description'] ) . '</p>';
        $output .= '</div>';
        $output .= '</div>';
    }
    $output .= '</div>';

    return ob_get_clean() . $output; // Return buffered content
}
add_shortcode( 'kit_services', 'kit_services_shortcode' );

function kit_services_blocks() {
    ob_start(); // Start output buffering

    // Hardcoded to match services.php exactly with icons
    $services = array(
        array(
            'name' => 'Freight Forwarding',
            'description' => 'We provide end-to-end freight forwarding services across air, sea, and road freight, ensuring timely delivery.',
            'icon' => 'flaticon-air-freight'
        ),
        array(
            'name' => 'Customs Clearance',
            'description' => 'We offer efficient customs clearance services, ensuring smooth compliance with regulations across the SADC region.',
            'icon' => 'flaticon-delivery-man'
        ),
        array(
            'name' => 'Warehousing',
            'description' => 'Our secure and efficient warehousing services use the latest technology for optimal storage and product distribution.',
            'icon' => 'flaticon-wholesale'
        ),
        array(
            'name' => 'Transportation',
            'description' => 'We provide reliable transportation services with a well-maintained fleet, ensuring safety, punctuality, and efficiency.',
            'icon' => 'flaticon-truck'
        ),
        array(
            'name' => 'Distribution',
            'description' => 'Our distribution solutions streamline your supply chain, ensuring your products reach the market efficiently and timely.',
            'icon' => 'flaticon-pallet'
        )
    );

    $output = ''; // Initialize output before the loop
    foreach ( $services as $service ) {
        $output .= '<div class="col-lg-4 col-md-6 col-12">';
        $output .= '<div class="single-serv-item">';
        $output .= '<div class="serv-icon">';
        $output .= '<i class="' . esc_html( $service['icon'] ) . '"></i>';
        $output .= '</div>';
        $output .= '<div class="serv-content">';
        $output .= '<h5>' . esc_html( $service['name'] ) . '</h5>';
        $output .= '<p>' . esc_html( $service['description'] ) . '</p>';
        $output .= '</div>';
        $output .= '<a href="' . esc_html( strtolower(str_replace(' ', '-', $service['name'])) ) . '" class="read-more">Read More</a>';
        $output .= '</div>';
        $output .= '</div>';
    }
    return ob_get_clean() . $output; // Return buffered content
}
add_shortcode( 'kit_services_blocks', 'kit_services_blocks' );