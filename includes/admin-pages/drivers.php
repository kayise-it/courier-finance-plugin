<?php
if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['action']) && $_POST['action'] === 'save_driver') {
    if (!wp_verify_nonce($_POST['nonce'], 'save_driver_nonce')) {
        wp_die('Security check failed');
    }

    if (!current_user_can('kit_view_waybills')) {
        wp_die('Unauthorized');
    }

    global $wpdb;
    $table = $wpdb->prefix . 'kit_drivers';

    // Convert empty strings to NULL for optional fields
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $license_number = sanitize_text_field($_POST['license_number'] ?? '');
    
    $data = [
        'name' => sanitize_text_field($_POST['name']),
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
        $result = $wpdb->update(
            $table,
            $data,
            ['id' => intval($_POST['driver_id'])],
            $format,
            ['%d']
        );
        $message = $result !== false ? 'Driver updated successfully!' : 'Failed to update driver.';
    } else {
        // Create
        $result = $wpdb->insert($table, $data, $format);
        $message = $result !== false ? 'Driver created successfully!' : 'Failed to create driver.';
    }

    // Redirect to avoid resubmission
    wp_redirect(add_query_arg([
        'page' => 'manage-drivers',
        'message' => urlencode($message),
        'success' => ($result !== false ? '1' : '0')
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

// Show messages
if (isset($_GET['message'])) {
    $success = isset($_GET['success']) && $_GET['success'] === '1';
    $message_class = $success ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800';
    echo '<div class="' . $message_class . ' border rounded-lg p-4 mb-4">' . esc_html(urldecode($_GET['message'])) . '</div>';
}
?>

<div class="wrap">
    <?php
    echo KIT_Commons::showingHeader([
        'title' => 'Manage Drivers',
        'desc' => 'Add and manage truck drivers for your deliveries',
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
            
            <form method="POST" action="" class="space-y-6">
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
                                   <?php echo ($driver && $driver->is_active) ? 'checked' : 'checked'; ?>>
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
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">All Drivers</h2>
                    <p class="text-sm text-gray-600 mt-1"><?php echo count($drivers); ?> driver(s) registered</p>
                </div>
                <a href="<?php echo admin_url('admin.php?page=manage-drivers&add=1'); ?>" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                    + Add New Driver
                </a>
            </div>
            
            <?php if (empty($drivers)): ?>
                <div class="text-center py-12">
                    <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No drivers found</h3>
                    <p class="text-gray-600 mb-4">Get started by adding your first driver</p>
                    <a href="<?php echo admin_url('admin.php?page=manage-drivers&add=1'); ?>" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                        Add First Driver
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Driver Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">License Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($drivers as $driver_row): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <span class="text-blue-600 font-semibold">
                                                    <?php echo strtoupper(substr($driver_row->name, 0, 1)); ?>
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo esc_html($driver_row->name); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo esc_html($driver_row->phone ?: 'N/A'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo esc_html($driver_row->email ?: 'N/A'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo esc_html($driver_row->license_number ?: 'N/A'); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $driver_row->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $driver_row->is_active ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="<?php echo admin_url('admin.php?page=manage-drivers&edit=' . $driver_row->id); ?>" 
                                               class="text-blue-600 hover:text-blue-900 transition">
                                                Edit
                                            </a>
                                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=manage-drivers&delete=' . $driver_row->id), 'delete_driver_' . $driver_row->id); ?>" 
                                               class="text-red-600 hover:text-red-900 transition"
                                               onclick="return confirm('Are you sure you want to delete this driver? This action cannot be undone.');">
                                                Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
