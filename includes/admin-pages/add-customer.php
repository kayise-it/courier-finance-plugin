<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include customer functions
require_once plugin_dir_path(__FILE__) . '../customers/customers-functions.php';

// Form submission is handled in admin-menu.php

// Get error message if form submission failed
$error_message = '';
if (isset($_GET['error']) && $_GET['error'] == '1') {
    $error_message = 'Failed to save customer. Please try again.';
}
?>

<div class="wrap">
    <h1>Add New Customer</h1>
    
    <?php if (isset($error_message)): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6" style="max-width: 800px;">
        <form id="add-customer-form" method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="space-y-6">
            <input type="hidden" name="action" value="add_customer">
            <?php wp_nonce_field('add_customer_nonce', 'customer_nonce'); ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">Company Name *</label>
                    <input type="text" name="company_name" id="company_name" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                           value="<?php echo esc_attr($_POST['company_name'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">First Name *</label>
                    <input type="text" name="name" id="name" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                           value="<?php echo esc_attr($_POST['name'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="surname" class="block text-sm font-medium text-gray-700 mb-2">Last Name *</label>
                    <input type="text" name="surname" id="surname" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                           value="<?php echo esc_attr($_POST['surname'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="cell" class="block text-sm font-medium text-gray-700 mb-2">Cell Phone *</label>
                    <input type="tel" name="cell" id="cell" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                           value="<?php echo esc_attr($_POST['cell'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="email_address" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                    <input type="email" name="email_address" id="email_address" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                           value="<?php echo esc_attr($_POST['email_address'] ?? ''); ?>">
                </div>
                
                <div>
                    <label for="country_id" class="block text-sm font-medium text-gray-700 mb-2">Country</label>
                    <select name="country_id" id="country_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Select Country</option>
                        <option value="1" <?php selected($_POST['country_id'] ?? '', '1'); ?>>South Africa</option>
                        <option value="2" <?php selected($_POST['country_id'] ?? '', '2'); ?>>Zimbabwe</option>
                    </select>
                </div>
                
                <div>
                    <label for="city_id" class="block text-sm font-medium text-gray-700 mb-2">City</label>
                    <select name="city_id" id="city_id" 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">Select City</option>
                        <option value="1" <?php selected($_POST['city_id'] ?? '', '1'); ?>>Johannesburg</option>
                        <option value="2" <?php selected($_POST['city_id'] ?? '', '2'); ?>>Cape Town</option>
                        <option value="3" <?php selected($_POST['city_id'] ?? '', '3'); ?>>Durban</option>
                    </select>
                </div>
                
                <div>
                    <label for="vat_number" class="block text-sm font-medium text-gray-700 mb-2">VAT Number</label>
                    <input type="text" name="vat_number" id="vat_number" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"
                           placeholder="VAT registration number"
                           value="<?php echo esc_attr($_POST['vat_number'] ?? ''); ?>">
                </div>
            </div>
            
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700 mb-2">Address *</label>
                <textarea name="address" id="address" rows="3" required 
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500"><?php echo esc_textarea($_POST['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="flex justify-end gap-3 pt-6 border-t border-gray-200">
                <a href="<?php echo admin_url('admin.php?page=08600-customers'); ?>" 
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    Cancel
                </a>
                <?php echo KIT_Commons::renderButton('Save Customer', 'primary', 'lg', ['type' => 'submit']); ?>
            </div>
        </form>
    </div>
</div>