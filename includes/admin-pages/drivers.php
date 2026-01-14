<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include unified table class
require_once plugin_dir_path(__FILE__) . '../class-unified-table.php';
// Include toast component
require_once plugin_dir_path(__FILE__) . '../components/toast.php';

/**
 * Render driver multiform for modal or inline use
 * 
 * @param array $atts Array of attributes:
 *   - form_action: Form action URL
 *   - driver_id: Driver ID for editing (empty for new)
 *   - edit_mode: Whether in edit mode (boolean)
 *   - driver: Driver object/array (null for new)
 *   - is_modal: Whether rendered in modal (boolean)
 * @return string HTML form content
 */
function kit_render_driver_multiform($atts = [])
{
    $atts = shortcode_atts([
        'form_action' => '',
        'driver_id' => '',
        'edit_mode' => false,
        'driver' => null,
        'is_modal' => false,
    ], $atts);

    $form_action = esc_url($atts['form_action'] ?: admin_url('admin.php?page=manage-drivers'));
    $driver_id = esc_attr($atts['driver_id']);
    $edit_mode = $atts['edit_mode'];
    $driver = $atts['driver'];
    $is_modal = $atts['is_modal'];

    // Convert driver to object if it's an array
    if (is_array($driver) && !empty($driver)) {
        $driver = (object) $driver;
    }

    ob_start();
    ?>
    <form method="POST" action="<?php echo $form_action; ?>" class="space-y-6">
        <input type="hidden" name="action" value="save_driver">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('save_driver_nonce'); ?>">
        <?php if ($edit_mode && $driver && isset($driver->id)): ?>
            <input type="hidden" name="driver_id" value="<?php echo esc_attr($driver->id); ?>">
        <?php endif; ?>
        <?php if ($is_modal): ?>
            <input type="hidden" name="is_modal" value="1">
        <?php endif; ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php
            echo KIT_Commons::Linput([
                'label' => 'Driver Name',
                'name'  => 'name',
                'id'    => 'name',
                'type'  => 'text',
                'value' => $driver && isset($driver->name) ? esc_attr($driver->name) : '',
                'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-white transition',
                'required' => true
            ]);
            
            echo KIT_Commons::Linput([
                'label' => 'Phone',
                'name'  => 'phone',
                'id'    => 'phone',
                'type'  => 'tel',
                'value' => $driver && isset($driver->phone) ? esc_attr($driver->phone) : '',
                'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-white transition',
            ]);
            
            echo KIT_Commons::Linput([
                'label' => 'Email',
                'name'  => 'email',
                'id'    => 'email',
                'type'  => 'email',
                'value' => $driver && isset($driver->email) ? esc_attr($driver->email) : '',
                'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-white transition',
            ]);
            
            echo KIT_Commons::Linput([
                'label' => 'License Number',
                'name'  => 'license_number',
                'id'    => 'license_number',
                'type'  => 'text',
                'value' => $driver && isset($driver->license_number) ? esc_attr($driver->license_number) : '',
                'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-white transition',
            ]);
            ?>
            
            <div class="flex items-center space-x-3">
                <label class="<?= KIT_Commons::labelClass() ?> mb-0">Status</label>
                <label class="flex items-center cursor-pointer">
                    <input type="checkbox" id="is_active" name="is_active" value="1" 
                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                           <?php 
                           if ($edit_mode && $driver && isset($driver->is_active)) {
                               echo $driver->is_active ? 'checked' : '';
                           } else {
                               echo 'checked'; // Default to active for new drivers
                           }
                           ?>>
                    <span class="ml-2 text-sm text-gray-700">Active</span>
                </label>
            </div>
        </div>

        <div class="flex space-x-3 pt-4 border-t">
            <?php
            echo KIT_Commons::renderButton(
                $edit_mode ? 'Update Driver' : 'Add Driver',
                'primary',
                'md',
                ['type' => 'submit']
            );
            ?>
            <?php if (!$is_modal): ?>
                <a href="<?php echo admin_url('admin.php?page=manage-drivers'); ?>" 
                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </a>
            <?php endif; ?>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

// Handle form submission
if (isset($_POST['action']) && $_POST['action'] === 'save_driver') {
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'save_driver_nonce')) {
        wp_die('Security check failed');
    }

    if (!current_user_can('kit_view_waybills')) {
        wp_die('Unauthorized');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'kit_drivers';

    // Validate required fields
    $name = sanitize_text_field($_POST['name'] ?? '');
    if (empty(trim($name))) {
        wp_redirect(add_query_arg([
            'page' => 'manage-drivers',
            'add' => isset($_POST['driver_id']) ? '' : '1',
            'edit' => isset($_POST['driver_id']) ? intval($_POST['driver_id']) : '',
            'message' => urlencode('Driver name is required.'),
            'success' => '0'
        ], admin_url('admin.php')));
        exit;
    }

    // Convert empty strings to NULL for optional fields
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $license_number = sanitize_text_field($_POST['license_number'] ?? '');
    
    $data = [
        'name' => $name,
        'phone' => $phone === '' ? null : $phone,
        'email' => $email === '' ? null : $email,
        'license_number' => $license_number === '' ? null : $license_number,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    // Format specifiers for $wpdb->insert/update (null values use null in format array)
    $format = [
        '%s', // name
        $data['phone'] === null ? null : '%s', // phone
        $data['email'] === null ? null : '%s', // email
        $data['license_number'] === null ? null : '%s', // license_number
        '%d'  // is_active
    ];
    
    if (isset($_POST['driver_id']) && !empty($_POST['driver_id'])) {
        // Update
        $driver_id = intval($_POST['driver_id']);
        $result = $wpdb->update(
            $table,
            $data,
            ['id' => $driver_id],
            $format,
            ['%d']
        );
        
        if ($result === false) {
            $error = $wpdb->last_error;
            $message = !empty($error) ? 'Failed to update driver: ' . esc_html($error) : 'Failed to update driver.';
        } else if ($result === 0) {
            $message = 'No changes were made to the driver.';
        } else {
            $message = 'Driver updated successfully!';
        }
    } else {
        // Create
        $result = $wpdb->insert($table, $data, $format);
        
        if ($result === false) {
            $error = $wpdb->last_error;
            $message = !empty($error) ? 'Failed to create driver: ' . esc_html($error) : 'Failed to create driver.';
        } else {
            $message = 'Driver created successfully!';
        }
    }

    // Redirect to avoid resubmission (toast will be shown on redirected page)
    wp_redirect(add_query_arg([
        'page' => 'manage-drivers',
        'message' => urlencode($message),
        'success' => ($result !== false && $result !== 0 ? '1' : '0')
    ], admin_url('admin.php')));
    exit;
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['_wpnonce'])) {
    if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_driver_' . $_GET['delete'])) {
        wp_die('Security check failed');
    }

    if (!current_user_can('kit_view_waybills')) {
        wp_die('Unauthorized');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'kit_drivers';
    $result = $wpdb->delete($table, ['id' => intval($_GET['delete'])]);

    wp_redirect(add_query_arg([
        'page' => 'manage-drivers',
        'message' => urlencode($result !== false ? 'Driver deleted successfully!' : 'Failed to delete driver.'),
        'success' => ($result !== false ? '1' : '0')
    ], admin_url('admin.php')));
    exit;
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['bulk_ids']) && wp_verify_nonce($_POST['bulk_nonce'] ?? '', 'bulk_action_nonce')) {
    if (!current_user_can('kit_view_waybills')) {
        wp_die('Unauthorized');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'kit_drivers';
    $ids = array_map('intval', explode(',', sanitize_text_field($_POST['bulk_ids'])));
    $ids = array_filter($ids);
    
    if (empty($ids)) {
        wp_redirect(add_query_arg([
            'page' => 'manage-drivers',
            'message' => urlencode('No drivers selected.'),
            'success' => '0'
        ], admin_url('admin.php')));
        exit;
    }

    $action = sanitize_text_field($_POST['bulk_action']);
    $message = '';
    $success = false;

    switch ($action) {
        case 'delete':
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $result = $wpdb->query($wpdb->prepare(
                "DELETE FROM $table WHERE id IN ($placeholders)",
                $ids
            ));
            $success = $result !== false;
            $message = $success ? sprintf('Successfully deleted %d driver(s).', count($ids)) : 'Failed to delete drivers.';
            break;

        case 'update_status':
            $status = isset($_POST['status_value']) ? intval($_POST['status_value']) : 0;
            $placeholders = implode(',', array_fill(0, count($ids), '%d'));
            $result = $wpdb->query($wpdb->prepare(
                "UPDATE $table SET is_active = %d WHERE id IN ($placeholders)",
                array_merge([$status], $ids)
            ));
            $success = $result !== false;
            $status_text = $status ? 'active' : 'inactive';
            $message = $success ? sprintf('Successfully set %d driver(s) to %s.', count($ids), $status_text) : 'Failed to update driver status.';
            break;

        case 'export':
            // Export will be handled via GET parameter
            wp_redirect(add_query_arg([
                'page' => 'manage-drivers',
                'export_selected' => implode(',', $ids)
            ], admin_url('admin.php')));
            exit;
    }

    if ($action !== 'export') {
        wp_redirect(add_query_arg([
            'page' => 'manage-drivers',
            'message' => urlencode($message),
            'success' => ($success ? '1' : '0')
        ], admin_url('admin.php')));
        exit;
    }
}

// Get driver for editing
$driver = null;
$edit_mode = false;
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    global $wpdb;
    $table = $wpdb->prefix . 'kit_drivers';
    $driver = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", intval($_GET['edit'])));
    $edit_mode = $driver !== null;
}

// Get all drivers
global $wpdb;
$table = $wpdb->prefix . 'kit_drivers';
$drivers = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");

// Show toast messages from URL parameters (backward compatibility)
if (isset($_GET['message'])) {
    if (class_exists('KIT_Toast')) {
        KIT_Toast::ensure_toast_loads();
        $success = isset($_GET['success']) && $_GET['success'] === '1';
        $toast_type = $success ? 'success' : 'error';
        $toast_title = $success ? 'Success' : 'Error';
        echo KIT_Toast::show(urldecode($_GET['message']), $toast_type, $toast_title);
    }
}
?>

<div class="wrap">
    <?php
    echo KIT_Commons::showingHeader([
        'title' => 'Manage Drivers',
        'desc' => '',
        'content' => KIT_Modal::render('new-driver-modal', 'Add New Driver', kit_render_driver_multiform([
            'form_action' => admin_url('admin.php?page=manage-drivers&add=1'),
            'driver_id' => '',
            'edit_mode' => false,
            'driver' => null,
            'is_modal' => true
        ])),
        'icon' => KIT_Commons::icon('user'),
    ]);
    ?>
    
    <?php if (isset($_GET['add']) || $edit_mode): ?>
        <!-- Add/Edit Driver Form -->
        <div class="bg-white rounded-lg shadow-md border border-gray-200 p-6 max-w-3xl">
            <div class="flex items-center justify-between mb-6 pb-4 border-b">
                <h2 class="text-2xl font-bold text-gray-800"><?php echo $edit_mode ? 'Edit Driver' : 'Add New Driver'; ?></h2>
                <a href="<?php echo admin_url('admin.php?page=manage-drivers'); ?>" class="text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            </div>
            
            <form method="POST" action="<?php echo esc_url(admin_url('admin.php?page=manage-drivers')); ?>" class="space-y-6">
                <input type="hidden" name="action" value="save_driver">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('save_driver_nonce'); ?>">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="driver_id" value="<?php echo esc_attr($driver->id); ?>">
                <?php endif; ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php
                    echo KIT_Commons::Linput([
                        'label' => 'Driver Name',
                        'name'  => 'name',
                        'id'    => 'name',
                        'type'  => 'text',
                        'value' => $driver ? esc_attr($driver->name) : '',
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-white transition',
                        'required' => true
                    ]);
                    
                    echo KIT_Commons::Linput([
                        'label' => 'Phone',
                        'name'  => 'phone',
                        'id'    => 'phone',
                        'type'  => 'tel',
                        'value' => $driver ? esc_attr($driver->phone) : '',
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-white transition',
                    ]);
                    
                    echo KIT_Commons::Linput([
                        'label' => 'Email',
                        'name'  => 'email',
                        'id'    => 'email',
                        'type'  => 'email',
                        'value' => $driver ? esc_attr($driver->email) : '',
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-white transition',
                    ]);
                    
                    echo KIT_Commons::Linput([
                        'label' => 'License Number',
                        'name'  => 'license_number',
                        'id'    => 'license_number',
                        'type'  => 'text',
                        'value' => $driver ? esc_attr($driver->license_number) : '',
                        'class' => 'w-full rounded-lg border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 px-4 py-2 text-gray-800 bg-white transition',
                    ]);
                    ?>
                    
                    <div class="flex items-center space-x-3">
                        <label class="<?= KIT_Commons::labelClass() ?> mb-0">Status</label>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" id="is_active" name="is_active" value="1" 
                                   class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500"
                                   <?php 
                                   // Default to checked for new drivers, or use existing value for edit
                                   if ($edit_mode) {
                                       echo ($driver && $driver->is_active) ? 'checked' : '';
                                   } else {
                                       echo 'checked'; // Default to active for new drivers
                                   }
                                   ?>>
                            <span class="ml-2 text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                </div>

                <div class="flex space-x-3 pt-4 border-t">
                    <?php
                    echo KIT_Commons::renderButton(
                        $edit_mode ? 'Update Driver' : 'Add Driver',
                        'primary',
                        'md',
                        ['type' => 'submit']
                    );
                    ?>
                    <a href="<?php echo admin_url('admin.php?page=manage-drivers'); ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    <?php else: ?>
        <!-- Drivers List -->
        <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden">
            <?php
            // Convert drivers to array format for unified table
            $drivers_data = [];
            foreach ($drivers as $driver_row) {
                // Combine phone and email into contact field
                $contact_parts = [];
                if (!empty($driver_row->phone)) {
                    $contact_parts[] = esc_html($driver_row->phone);
                }
                if (!empty($driver_row->email)) {
                    $contact_parts[] = esc_html($driver_row->email);
                }
                $contact = !empty($contact_parts) ? implode(' • ', $contact_parts) : 'N/A';
                
                $drivers_data[] = [
                    'id' => $driver_row->id,
                    'name' => $driver_row->name,
                    'contact' => $contact,
                    'phone' => $driver_row->phone ?: 'N/A', // Keep for search
                    'email' => $driver_row->email ?: 'N/A', // Keep for search
                    'license_number' => $driver_row->license_number ?: 'N/A',
                    'is_active' => $driver_row->is_active,
                ];
            }

            // Define columns for the unified table
            $columns = [
                'name' => [
                    'label' => 'Driver Name',
                    'callback' => function($value, $row) {
                        $initial = strtoupper(substr($value, 0, 1));
                        $is_active = !empty($row['is_active']);
                        $dot_color = $is_active ? 'bg-green-500' : 'bg-red-500';
                        return '<div class="flex items-center">
                            <div class="flex-shrink-0 relative">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-blue-600 font-semibold">' . esc_html($initial) . '</span>
                                </div>
                                <span class="status-indicator absolute bottom-0 right-0 block h-3 w-3 rounded-full ' . $dot_color . ' border-2 border-white"></span>
                            </div>
                            <div class="ml-4">
                                <div class="text-sm font-medium text-gray-900">' . esc_html($value) . '</div>
                            </div>
                        </div>';
                    }
                ],
                'contact' => 'Contact',
                'license_number' => 'License Number',
            ];

            // Define actions for the table (row-level actions)
            $actions = [
                [
                    'label' => 'Edit',
                    'href' => admin_url('admin.php?page=manage-drivers&edit={id}'),
                    'class' => 'inline-flex items-center px-2.5 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition-colors'
                ],
                [
                    'label' => 'Delete',
                    'callback' => function($href, $row) {
                        $driver_id = is_array($row) ? ($row['id'] ?? '') : (is_object($row) ? ($row->id ?? '') : '');
                        if (empty($driver_id)) {
                            return '#';
                        }
                        return wp_nonce_url(
                            admin_url('admin.php?page=manage-drivers&delete=' . intval($driver_id)),
                            'delete_driver_' . intval($driver_id)
                        );
                    },
                    'class' => 'inline-flex items-center px-2.5 py-1.5 text-sm font-medium text-red-600 hover:text-red-800 hover:bg-red-50 rounded-md transition-colors',
                    'onclick' => 'return confirm(\'Are you sure you want to delete this driver? This action cannot be undone.\');'
                ]
            ];

            // Render unified table
            echo KIT_Unified_Table::infinite($drivers_data, $columns, [
                'title' => 'All Drivers',
                'actions' => $actions,
                'searchable' => true,
                'sortable' => true,
                'pagination' => true,
                'items_per_page' => 20,
                'bulk_management' => true,
                'bulk_actions_list' => ['delete', 'export', 'status_active', 'status_inactive'],
                'empty_message' => 'No drivers found. <a href="' . admin_url('admin.php?page=manage-drivers&add=1') . '">Add your first driver</a>.',
                'search_placeholder' => 'Search drivers...',
                'search_filters' => [
                    ['value' => 'name', 'label' => 'Driver Name', 'placeholder' => 'Search by driver name...'],
                    ['value' => 'contact', 'label' => 'Contact', 'placeholder' => 'Search by phone or email...'],
                    ['value' => 'license_number', 'label' => 'License Number', 'placeholder' => 'Search by license number...']
                ],
                'search_default_filter' => 'name',
            ]);
            ?>
        </div>
    <?php endif; ?>
</div>
