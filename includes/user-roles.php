<?php
/**
 * Custom User Roles for 08600 Waybill Plugin
 * 
 * Data Capturer Role: Can view waybills, view past trips, and access specific fields
 * Manager Role: Can see what Data Capturer captured, update data, and view past trips
 */

if (!defined('ABSPATH')) {
    exit;
}

class KIT_User_Roles {
    
    /**
     * Initialize user roles
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'create_custom_roles'));
        add_action('admin_init', array(__CLASS__, 'check_user_permissions'));
        add_filter('user_contactmethods', array(__CLASS__, 'add_custom_user_fields'));
    }
    
    /**
     * Create custom user roles
     */
    public static function create_custom_roles() {
        // Data Capturer Role
        add_role('data_capturer', 'Data Capturer', array(
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'publish_posts' => false,
            'upload_files' => true,
            'edit_pages' => true,
            'read_private_pages' => true,
            'edit_private_pages' => false,
            'edit_published_pages' => false,
            'publish_pages' => false,
            'delete_pages' => false,
            'delete_private_pages' => false,
            'delete_published_pages' => false,
            'manage_options' => false,
            'kit_view_waybills' => true,
            'kit_view_past_trips' => true,
            'kit_access_description' => true,
            'kit_access_weight' => true,
            'kit_access_dimensions' => true,
            'kit_access_sad500' => true,
            'kit_access_sadc_certificate' => true,
            'kit_access_vat' => true,
        ));
        
        // Manager Role
        add_role('manager', 'Manager', array(
            'read' => true,
            'edit_posts' => true,
            'delete_posts' => false,
            'publish_posts' => true,
            'upload_files' => true,
            'edit_pages' => true,
            'read_private_pages' => true,
            'edit_private_pages' => true,
            'edit_published_pages' => true,
            'publish_pages' => true,
            'delete_pages' => false,
            'delete_private_pages' => false,
            'delete_published_pages' => false,
            'manage_options' => false,
            'kit_view_waybills' => true,
            'kit_view_past_trips' => true,
            'kit_update_data' => true,
            'kit_access_description' => true,
            'kit_access_weight' => true,
            'kit_access_dimensions' => true,
            'kit_access_sad500' => true,
            'kit_access_sadc_certificate' => true,
            'kit_access_vat' => true,
        ));
    }
    
    /**
     * Check user permissions and modify interface accordingly
     */
    public static function check_user_permissions() {
        $current_user = wp_get_current_user();
        $user_roles = $current_user->roles;
        
        // Add custom capabilities to existing roles
        if (in_array('administrator', $user_roles)) {
            $admin_role = get_role('administrator');
            $admin_role->add_cap('kit_view_waybills');
            $admin_role->add_cap('kit_view_past_trips');
            $admin_role->add_cap('kit_update_data');
            $admin_role->add_cap('kit_access_description');
            $admin_role->add_cap('kit_access_weight');
            $admin_role->add_cap('kit_access_dimensions');
            $admin_role->add_cap('kit_access_sad500');
            $admin_role->add_cap('kit_access_sadc_certificate');
            $admin_role->add_cap('kit_access_vat');
        }
        
        if (in_array('editor', $user_roles)) {
            $editor_role = get_role('editor');
            $editor_role->add_cap('kit_view_waybills');
            $editor_role->add_cap('kit_view_past_trips');
            $editor_role->add_cap('kit_update_data');
            $editor_role->add_cap('kit_access_description');
            $editor_role->add_cap('kit_access_weight');
            $editor_role->add_cap('kit_access_dimensions');
            $editor_role->add_cap('kit_access_sad500');
            $editor_role->add_cap('kit_access_sadc_certificate');
            $editor_role->add_cap('kit_access_vat');
        }
    }
    
    /**
     * Add custom user fields for company email
     */
    public static function add_custom_user_fields($contact_methods) {
        $contact_methods['company_email'] = 'Company Email';
        $contact_methods['company_phone'] = 'Company Phone';
        $contact_methods['company_address'] = 'Company Address';
        return $contact_methods;
    }
    
    /**
     * Check if user can access specific field
     */
    public static function can_access_field($field_name) {
        $current_user = wp_get_current_user();
        
        // Administrators and editors have full access
        if (in_array('administrator', $current_user->roles) || in_array('editor', $current_user->roles)) {
            return true;
        }
        
        // Check specific field permissions
        switch ($field_name) {
            case 'description':
                return current_user_can('kit_access_description');
            case 'weight':
                return current_user_can('kit_access_weight');
            case 'dimensions':
                return current_user_can('kit_access_dimensions');
            case 'sad500':
                return current_user_can('kit_access_sad500');
            case 'sadc_certificate':
                return current_user_can('kit_access_sadc_certificate');
            case 'vat':
                return current_user_can('kit_access_vat');
            default:
                return false;
        }
    }
    
    /**
     * Check if user can update data
     */
    public static function can_update_data() {
        return current_user_can('kit_update_data') || current_user_can('manage_options');
    }
    
    /**
     * Check if user can view waybills
     */
    public static function can_view_waybills() {
        return current_user_can('kit_view_waybills') || current_user_can('edit_pages');
    }
    
    /**
     * Check if user can view past trips
     */
    public static function can_view_past_trips() {
        return current_user_can('kit_view_past_trips') || current_user_can('edit_pages');
    }
    
    /**
     * Get user role display name
     */
    public static function get_user_role_display_name() {
        $current_user = wp_get_current_user();
        $roles = $current_user->roles;
        
        if (in_array('administrator', $roles)) {
            return 'Administrator';
        } elseif (in_array('manager', $roles)) {
            return 'Manager';
        } elseif (in_array('data_capturer', $roles)) {
            return 'Data Capturer';
        } elseif (in_array('editor', $roles)) {
            return 'Editor';
        } else {
            return 'User';
        }
    }
    
    /**
     * Get accessible fields for current user
     */
    public static function get_accessible_fields() {
        $fields = array();
        
        if (self::can_access_field('description')) {
            $fields[] = 'description';
        }
        if (self::can_access_field('weight')) {
            $fields[] = 'weight';
        }
        if (self::can_access_field('dimensions')) {
            $fields[] = 'dimensions';
        }
        if (self::can_access_field('sad500')) {
            $fields[] = 'sad500';
        }
        if (self::can_access_field('sadc_certificate')) {
            $fields[] = 'sadc_certificate';
        }
        if (self::can_access_field('vat')) {
            $fields[] = 'vat';
        }
        
        return $fields;
    }
}

// Initialize the user roles system
KIT_User_Roles::init();
