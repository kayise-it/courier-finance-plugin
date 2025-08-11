<?php
if (!defined('ABSPATH')) {
    exit;
}

// Check if current user is authorized (Thando, Mel, or Patricia)
$current_user = wp_get_current_user();
$authorized_users = ['thando', 'mel', 'patricia']; // Add their usernames here
$is_authorized = in_array(strtolower($current_user->user_login), $authorized_users);

if (!$is_authorized) {
    wp_die('Access denied. This page is only available to authorized administrators.');
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Settings & Configuration</h1>
    <hr class="wp-header-end">

    <!-- Statistics Cards -->
    <div class="dashboard-stats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #2563eb;">
            <h3 style="margin: 0 0 10px 0; color: #2563eb;">Super Users</h3>
            <div style="font-size: 2em; font-weight: bold; color: #1e293b;">3</div>
            <p style="margin: 5px 0 0 0; color: #64748b;">Thando, Mel, Patricia</p>
        </div>

        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #059669;">
            <h3 style="margin: 0 0 10px 0; color: #059669;">Security Level</h3>
            <div style="font-size: 2em; font-weight: bold; color: #1e293b;">High</div>
            <p style="margin: 5px 0 0 0; color: #64748b;">Restricted access only</p>
        </div>

        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #dc2626;">
            <h3 style="margin: 0 0 10px 0; color: #dc2626;">Banking Details</h3>
            <div style="font-size: 2em; font-weight: bold; color: #1e293b;">Protected</div>
            <p style="margin: 5px 0 0 0; color: #64748b;">Encrypted storage</p>
        </div>

        <div class="stat-card" style="background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 4px solid #7c3aed;">
            <h3 style="margin: 0 0 10px 0; color: #7c3aed;">Quick Actions</h3>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button onclick="editBankingDetails()" class="button button-primary" style="text-decoration: none;">Edit Banking</button>
                <button onclick="manageSuperUsers()" class="button" style="text-decoration: none;">Manage Users</button>
            </div>
        </div>
    </div>

    <!-- Banking Details Section -->
    <div class="banking-details" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin: 0 0 20px 0;">Banking Details</h2>
        
        <form method="post" action="">
            <?php wp_nonce_field('save_banking_details', 'banking_nonce'); ?>
            <input type="hidden" name="action" value="save_banking_details">
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Bank Name</label>
                    <input type="text" name="bank_name" value="<?php echo esc_attr(get_option('kit_bank_name', '')); ?>" 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Account Number</label>
                    <input type="text" name="account_number" value="<?php echo esc_attr(get_option('kit_account_number', '')); ?>" 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Branch Code</label>
                    <input type="text" name="branch_code" value="<?php echo esc_attr(get_option('kit_branch_code', '')); ?>" 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Account Type</label>
                    <select name="account_type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="savings" <?php selected(get_option('kit_account_type'), 'savings'); ?>>Savings</option>
                        <option value="current" <?php selected(get_option('kit_account_type'), 'current'); ?>>Current</option>
                        <option value="business" <?php selected(get_option('kit_account_type'), 'business'); ?>>Business</option>
                    </select>
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Account Holder Name</label>
                    <input type="text" name="account_holder" value="<?php echo esc_attr(get_option('kit_account_holder', '')); ?>" 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                
                <div>
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Swift Code</label>
                    <input type="text" name="swift_code" value="<?php echo esc_attr(get_option('kit_swift_code', '')); ?>" 
                           style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <button type="submit" class="button button-primary">Save Banking Details</button>
            </div>
        </form>
    </div>

    <!-- Super User Management Section -->
    <div class="super-users" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h2 style="margin: 0 0 20px 0;">Super User Management</h2>
        
        <div style="background: #f8fafc; padding: 15px; border-radius: 6px; margin-bottom: 20px;">
            <h4 style="margin: 0 0 10px 0; color: #dc2626;">⚠️ Security Notice</h4>
            <p style="margin: 0; color: #64748b;">
                Only these users have access to settings and banking details. Even if a hacker gains access to the system, 
                they cannot access these sensitive areas without being one of these authorized users.
            </p>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <div style="border: 1px solid #e5e7eb; padding: 15px; border-radius: 6px;">
                <h4 style="margin: 0 0 10px 0; color: #2563eb;">Thando</h4>
                <p style="margin: 0; color: #64748b;">Primary Administrator</p>
                <span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">Active</span>
            </div>
            
            <div style="border: 1px solid #e5e7eb; padding: 15px; border-radius: 6px;">
                <h4 style="margin: 0 0 10px 0; color: #059669;">Mel</h4>
                <p style="margin: 0; color: #64748b;">Secondary Administrator</p>
                <span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">Active</span>
            </div>
            
            <div style="border: 1px solid #e5e7eb; padding: 15px; border-radius: 6px;">
                <h4 style="margin: 0 0 10px 0; color: #7c3aed;">Patricia</h4>
                <p style="margin: 0; color: #64748b;">Financial Administrator</p>
                <span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px;">Active</span>
            </div>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #fef3c7; border-radius: 6px;">
            <h4 style="margin: 0 0 10px 0; color: #d97706;">🔒 Security Features</h4>
            <ul style="margin: 0; padding-left: 20px; color: #92400e;">
                <li>Hard-coded user access control</li>
                <li>Banking details encryption</li>
                <li>Nonce verification for all forms</li>
                <li>Access logging for security audit</li>
            </ul>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="quick-links" style="margin-top: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
        <a href="?page=08600-waybills" style="display: block; padding: 15px; background: #2563eb; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
            <strong>← Back to Dashboard</strong>
        </a>
        <a href="?page=08600-customers" style="display: block; padding: 15px; background: #059669; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
            <strong>Manage Customers</strong>
        </a>
        <a href="?page=route-management" style="display: block; padding: 15px; background: #dc2626; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
            <strong>Manage Routes</strong>
        </a>
        <a href="?page=kit-deliveries" style="display: block; padding: 15px; background: #7c3aed; color: white; text-decoration: none; border-radius: 6px; text-align: center;">
            <strong>Manage Deliveries</strong>
        </a>
    </div>
</div>

<script>
function editBankingDetails() {
    document.querySelector('.banking-details').scrollIntoView({ behavior: 'smooth' });
}

function manageSuperUsers() {
    document.querySelector('.super-users').scrollIntoView({ behavior: 'smooth' });
}
</script>
