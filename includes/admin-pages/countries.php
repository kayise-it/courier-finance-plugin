<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include user roles for permission checking
require_once plugin_dir_path(__FILE__) . '../user-roles.php';

// Include unified table class
require_once plugin_dir_path(__FILE__) . '../class-unified-table.php';

// Handle country management actions
if (isset($_POST['add_country']) && check_admin_referer('add_country_action', 'add_country_nonce')) {
    global $wpdb;
    $table = $wpdb->prefix . 'kit_operating_countries';
    
    $name = sanitize_text_field($_POST['country_name']);
    $code = sanitize_text_field($_POST['country_code']);
    $charge_group = intval($_POST['charge_group']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($name && $code) {
        $result = $wpdb->insert($table, [
            'country_name' => $name,
            'country_code' => $code,
            'charge_group' => $charge_group,
            'is_active' => $is_active,
            'created_at' => current_time('mysql'),
        ]);
        
        if ($result) {
            echo '<div class="notice notice-success"><p>Country added successfully.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error adding country: ' . $wpdb->last_error . '</p></div>';
        }
    } else {
        echo '<div class="notice notice-error"><p>Name and code are required.</p></div>';
    }
}

// Handle country update
if (isset($_POST['edit_country']) && check_admin_referer('edit_country_action', 'edit_country_nonce')) {
    global $wpdb;
    $table = $wpdb->prefix . 'kit_operating_countries';
    
    $id = intval($_POST['country_id']);
    $name = sanitize_text_field($_POST['country_name']);
    $code = sanitize_text_field($_POST['country_code']);
    $charge_group = intval($_POST['charge_group']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($id && $name && $code) {
        $result = $wpdb->update($table, [
            'country_name' => $name,
            'country_code' => $code,
            'charge_group' => $charge_group,
            'is_active' => $is_active,
        ], ['id' => $id]);
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>Country updated successfully.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error updating country: ' . $wpdb->last_error . '</p></div>';
        }
    } else {
        echo '<div class="notice notice-error"><p>All fields are required.</p></div>';
    }
}

// Handle country deletion
if (isset($_GET['delete_country']) && check_admin_referer('delete_country_' . intval($_GET['delete_country']))) {
    global $wpdb;
    $table = $wpdb->prefix . 'kit_operating_countries';
    $id = intval($_GET['delete_country']);
    
    $result = $wpdb->delete($table, ['id' => $id]);
    
    if ($result) {
        echo '<div class="notice notice-success"><p>Country deleted successfully.</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error deleting country: ' . $wpdb->last_error . '</p></div>';
    }
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['country_ids']) && is_array($_POST['country_ids'])) {
    global $wpdb;
    $table = $wpdb->prefix . 'kit_operating_countries';
    $action = sanitize_text_field($_POST['bulk_action']);
    $country_ids = array_map('intval', $_POST['country_ids']);
    $updated_count = 0;
    
    if (!empty($country_ids)) {
        switch ($action) {
            case 'activate':
                $result = $wpdb->query($wpdb->prepare(
                    "UPDATE $table SET is_active = 1 WHERE id IN (" . implode(',', array_fill(0, count($country_ids), '%d')) . ")",
                    ...$country_ids
                ));
                $updated_count = $result;
                break;
                
            case 'deactivate':
                $result = $wpdb->query($wpdb->prepare(
                    "UPDATE $table SET is_active = 0 WHERE id IN (" . implode(',', array_fill(0, count($country_ids), '%d')) . ")",
                    ...$country_ids
                ));
                $updated_count = $result;
                break;
                
            case 'delete':
                $result = $wpdb->query($wpdb->prepare(
                    "DELETE FROM $table WHERE id IN (" . implode(',', array_fill(0, count($country_ids), '%d')) . ")",
                    ...$country_ids
                ));
                $updated_count = $result;
                break;
        }
        
        if ($updated_count > 0) {
            $action_text = ucfirst($action) . 'd';
            echo '<div class="notice notice-success"><p>' . $action_text . ' ' . $updated_count . ' countr' . ($updated_count === 1 ? 'y' : 'ies') . ' successfully.</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>No countries were updated.</p></div>';
        }
    }
}

// Handle quick toggle (AJAX)
if (isset($_POST['action']) && $_POST['action'] === 'toggle_country_status') {
    global $wpdb;
    $table = $wpdb->prefix . 'kit_operating_countries';
    $id = intval($_POST['country_id']);
    $new_status = intval($_POST['new_status']);
    
    $result = $wpdb->update($table, ['is_active' => $new_status], ['id' => $id]);
    
    if ($result !== false) {
        wp_send_json_success(['message' => 'Status updated successfully']);
    } else {
        wp_send_json_error(['message' => 'Failed to update status']);
    }
}

// Get countries data
global $wpdb;
$table = $wpdb->prefix . 'kit_operating_countries';
$countries = $wpdb->get_results("SELECT * FROM $table ORDER BY country_name ASC");

// Get edit country data if editing
$edit_country = false;
if (isset($_GET['edit_country'])) {
    $edit_id = intval($_GET['edit_country']);
    $edit_country = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $edit_id));
}

// Get statistics
$total_countries = count($countries);
$active_countries = count(array_filter($countries, function($c) { return $c->is_active; }));
$inactive_countries = $total_countries - $active_countries;
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Operating Countries</h1>
    <a href="?page=08600-countries&add_new=1" class="page-title-action">Add New Country</a>
    <hr class="wp-header-end">

    <!-- Statistics Cards -->
    <div class="row" style="margin: 20px 0;">
        <div class="col-md-4">
            <div class="card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; text-align: center;">
                <h3 style="margin: 0; color: #0073aa; font-size: 2em;"><?php echo $total_countries; ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;">Total Countries</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; text-align: center;">
                <h3 style="margin: 0; color: #00a32a; font-size: 2em;"><?php echo $active_countries; ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;">Active Countries</p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; text-align: center;">
                <h3 style="margin: 0; color: #d63638; font-size: 2em;"><?php echo $inactive_countries; ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;">Inactive Countries</p>
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 32px; align-items: flex-start; margin-top: 20px;">
        <!-- Countries List -->
        <div style="flex: 2;">
            <?php
            // Prepare data for unified table
            $countries_data = [];
            foreach ($countries as $country) {
                $countries_data[] = [
                    'id' => $country->id,
                    'country_name' => $country->country_name,
                    'country_code' => $country->country_code,
                    'charge_group' => $country->charge_group,
                    'is_active' => $country->is_active,
                    'created_at' => $country->created_at
                ];
            }

            // Define columns
            $columns = [
                'country_name' => [
                    'label' => 'Name',
                    'sortable' => true,
                    'searchable' => true,
                    'callback' => function($value, $row) {
                        return '<strong>' . esc_html($value) . '</strong>';
                    }
                ],
                'country_code' => [
                    'label' => 'Code',
                    'sortable' => true,
                    'searchable' => true,
                    'callback' => function($value, $row) {
                        return '<code>' . esc_html($value) . '</code>';
                    }
                ],
                'charge_group' => [
                    'label' => 'Charge Group',
                    'sortable' => true,
                    'searchable' => false,
                    'callback' => function($value, $row) {
                        return '<span class="badge" style="background: #f0f0f1; color: #50575e; padding: 2px 8px; border-radius: 3px; font-size: 11px;">Group ' . esc_html($value) . '</span>';
                    }
                ],
                'is_active' => [
                    'label' => 'Status',
                    'sortable' => true,
                    'searchable' => false,
                    'callback' => function($value, $row) {
                        $status_text = $value ? 'Active' : 'Inactive';
                        $status_class = $value ? 'status-active' : 'status-inactive';
                        $status_color = $value ? '#00a32a' : '#d63638';
                        $toggle_text = $value ? 'Deactivate' : 'Activate';
                        $new_status = $value ? 0 : 1;
                        
                        return '
                            <div class="flex items-center gap-2">
                                <span class="' . $status_class . '" style="color: ' . $status_color . '; font-weight: 600;">● ' . $status_text . '</span>
                                <button type="button" 
                                        class="quick-toggle-btn text-xs px-2 py-1 rounded border hover:bg-gray-50" 
                                        data-country-id="' . $row['id'] . '" 
                                        data-new-status="' . $new_status . '"
                                        style="color: ' . $status_color . '; border-color: ' . $status_color . ';">
                                    ' . $toggle_text . '
                                </button>
                            </div>
                        ';
                    }
                ],
                'created_at' => [
                    'label' => 'Created',
                    'sortable' => true,
                    'searchable' => false,
                    'callback' => function($value, $row) {
                        return esc_html(date('M j, Y', strtotime($value)));
                    }
                ]
            ];

            // Define actions
            $actions = [
                [
                    'label' => 'Edit',
                    'href' => '?page=08600-countries&edit_country={id}',
                    'class' => 'button button-small'
                ],
                [
                    'label' => 'Delete',
                    'href' => wp_nonce_url('?page=08600-countries&delete_country={id}', 'delete_country_{id}'),
                    'class' => 'button button-small',
                    'onclick' => 'return confirm(\'Are you sure you want to delete this country? This action cannot be undone.\');',
                    'style' => 'color: #d63638;'
                ]
            ];

            // Render unified table
            echo KIT_Unified_Table::advanced($countries_data, $columns, [
                'title' => 'Operating Countries',
                'actions' => $actions,
                'searchable' => true,
                'sortable' => true,
                'pagination' => true,
                'items_per_page' => 20,
                'current_page' => 1,
                'show_items_per_page' => true,
                'exportable' => true,
                'empty_message' => 'No countries found. <a href="?page=08600-countries&add_new=1">Add your first country</a>.',
                'bulk_actions' => [
                    'activate' => 'Activate',
                    'deactivate' => 'Deactivate',
                    'delete' => 'Delete'
                ]
            ]);
            ?>
        </div>

        <!-- Add/Edit Form -->
        <div style="flex: 1; min-width: 320px;">
            <?php if ($edit_country): ?>
                <div class="card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px;">
                    <h3>Edit Country</h3>
                    <form method="post">
                        <?php wp_nonce_field('edit_country_action', 'edit_country_nonce'); ?>
                        <input type="hidden" name="country_id" value="<?php echo esc_attr($edit_country->id); ?>">
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="country_name">Country Name</label>
                                </th>
                                <td>
                                    <input type="text" id="country_name" name="country_name" 
                                           value="<?php echo esc_attr($edit_country->country_name); ?>" 
                                           class="regular-text" required>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="country_code">Country Code</label>
                                </th>
                                <td>
                                    <input type="text" id="country_code" name="country_code" 
                                           value="<?php echo esc_attr($edit_country->country_code); ?>" 
                                           class="regular-text" required 
                                           style="text-transform: uppercase;">
                                    <p class="description">Use ISO country codes (e.g., US, GB, ZA)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="charge_group">Charge Group</label>
                                </th>
                                <td>
                                    <input type="number" id="charge_group" name="charge_group" 
                                           value="<?php echo esc_attr($edit_country->charge_group); ?>" 
                                           min="0" max="9" class="small-text">
                                    <p class="description">Pricing group (0-9)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Status</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="is_active" value="1" 
                                               <?php checked($edit_country->is_active, 1); ?>> 
                                        Active (country will appear in selection lists)
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" name="edit_country" class="button button-primary">Update Country</button>
                            <a href="?page=08600-countries" class="button">Cancel</a>
                        </p>
                    </form>
                </div>
            <?php elseif (isset($_GET['add_new'])): ?>
                <div class="card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px;">
                    <h3>Add New Country</h3>
                    <form method="post">
                        <?php wp_nonce_field('add_country_action', 'add_country_nonce'); ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="country_name">Country Name</label>
                                </th>
                                <td>
                                    <input type="text" id="country_name" name="country_name" 
                                           class="regular-text" required 
                                           placeholder="e.g., United States">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="country_code">Country Code</label>
                                </th>
                                <td>
                                    <input type="text" id="country_code" name="country_code" 
                                           class="regular-text" required 
                                           placeholder="e.g., US" 
                                           style="text-transform: uppercase;">
                                    <p class="description">Use ISO country codes (e.g., US, GB, ZA)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="charge_group">Charge Group</label>
                                </th>
                                <td>
                                    <input type="number" id="charge_group" name="charge_group" 
                                           value="1" min="0" max="9" class="small-text">
                                    <p class="description">Pricing group (0-9)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">Status</th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="is_active" value="1" checked> 
                                        Active (country will appear in selection lists)
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" name="add_country" class="button button-primary">Add Country</button>
                            <a href="?page=08600-countries" class="button">Cancel</a>
                        </p>
                    </form>
                </div>
            <?php else: ?>
                <div class="card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px;">
                    <h3>Quick Actions</h3>
                    <p>
                        <a href="?page=08600-countries&add_new=1" class="button button-primary">Add New Country</a>
                    </p>
                    <p>
                        <a href="?page=route-management" class="button">Manage Routes</a>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.row {
    display: flex;
    gap: 20px;
    margin: 20px 0;
}

.col-md-4 {
    flex: 1;
}

.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.badge {
    background: #f0f0f1;
    color: #50575e;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
}

.status-active {
    color: #00a32a;
    font-weight: 600;
}

.status-inactive {
    color: #d63638;
    font-weight: 600;
}

.form-table th {
    width: 150px;
    padding: 20px 10px 20px 0;
}

.form-table td {
    padding: 15px 10px;
}

.description {
    font-style: italic;
    color: #666;
    margin: 5px 0 0 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Auto-uppercase country codes
    $('#country_code').on('input', function() {
        this.value = this.value.toUpperCase();
    });
    
    // Quick toggle functionality
    $(document).on('click', '.quick-toggle-btn', function(e) {
        e.preventDefault();
        
        const $btn = $(this);
        const countryId = $btn.data('country-id');
        const newStatus = $btn.data('new-status');
        const $row = $btn.closest('tr');
        
        // Disable button during request
        $btn.prop('disabled', true).text('Updating...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_country_status',
                country_id: countryId,
                new_status: newStatus,
                nonce: '<?php echo wp_create_nonce('toggle_country_status'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Reload the page to show updated status
                    location.reload();
                } else {
                    alert('Error: ' + (response.data.message || 'Failed to update status'));
                    $btn.prop('disabled', false).text($btn.data('original-text'));
                }
            },
            error: function() {
                alert('Error: Failed to update status');
                $btn.prop('disabled', false).text($btn.data('original-text'));
            }
        });
    });
    
    // Store original button text
    $('.quick-toggle-btn').each(function() {
        $(this).data('original-text', $(this).text());
    });
});
</script>
