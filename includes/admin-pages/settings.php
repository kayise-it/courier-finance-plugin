<?php
if (!defined('ABSPATH')) {
    exit;
}

// Include user roles for strict access control
require_once plugin_dir_path(__FILE__) . '../user-roles.php';

// Check if current user has admin capabilities
$wpdb_global_was_set = true; // marker
global $wpdb;

// STRICT ACCESS CONTROL: Only specific administrators can access settings
if (!KIT_User_Roles::can_access_settings()) {
    wp_die('Access denied. This page is only available to authorized administrators (Thando, Mel, Patricia).');
}

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    if (wp_verify_nonce($_POST['settings_nonce'], 'save_settings')) {
        global $wpdb; 
        $table = $wpdb->prefix . 'kit_company_details';
        // Only update columns that were posted to avoid wiping existing values
        $sanitizers = [
            'company_name' => function($v){ return sanitize_text_field($v); },
            'company_address' => function($v){ return sanitize_textarea_field($v); },
            'company_email' => function($v){ return sanitize_email($v); },
            'company_phone' => function($v){ return sanitize_text_field($v); },
            'company_website' => function($v){ return esc_url_raw($v); },
            'company_registration' => function($v){ return sanitize_text_field($v); },
            'company_vat_number' => function($v){ return sanitize_text_field($v); },
            'bank_name' => function($v){ return sanitize_text_field($v); },
            'account_number' => function($v){ return sanitize_text_field($v); },
            'branch_code' => function($v){ return sanitize_text_field($v); },
            'account_type' => function($v){ return sanitize_text_field($v); },
            'account_holder' => function($v){ return sanitize_text_field($v); },
            'swift_code' => function($v){ return sanitize_text_field($v); },
            'iban' => function($v){ return sanitize_text_field($v); },
            'vat_percentage' => function($v){ return (float)$v; },
            'sadc_charge' => function($v){ return (float)$v; },
            'sad500_charge' => function($v){ return (float)$v; },
            'international_price' => function($v){ return (float)$v; },
        ];
        $fields = [];
        foreach ($sanitizers as $key => $fn) {
            if (array_key_exists($key, $_POST)) {
                $fields[$key] = $fn($_POST[$key]);
            }
        }
        // If nothing to update, do nothing
        if (!empty($fields)) {
            // Maintain a single-row table
            $exists = $wpdb->get_var("SELECT id FROM $table ORDER BY id ASC LIMIT 1");
            if ($exists) {
                $wpdb->update($table, $fields, ['id' => intval($exists)]);
                $message = 'Settings updated successfully.';
            } else {
                $wpdb->insert($table, $fields);
                $message = 'Settings saved successfully.';
            }
        }
    }

    // Save brand colors (60/30/10 rule)
    if (isset($_POST['action']) && $_POST['action'] === 'save_colors' && wp_verify_nonce($_POST['settings_nonce'], 'save_settings')) {
        $primary   = isset($_POST['primary_color']) ? sanitize_hex_color($_POST['primary_color']) : '';
        $secondary = isset($_POST['secondary_color']) ? sanitize_hex_color($_POST['secondary_color']) : '';
        $accent    = isset($_POST['accent_color']) ? sanitize_hex_color($_POST['accent_color']) : '';

        // Fallbacks if invalid
        if (!$primary)   { $primary   = '#2563eb'; }
        if (!$secondary) { $secondary = '#111827'; }
        if (!$accent)    { $accent    = '#10b981'; }

        $schema = [
            'primary'   => $primary,
            'secondary' => $secondary,
            'accent'    => $accent,
            'rule'      => '60/30/10',
            'updated_at'=> current_time('mysql'),
        ];

        $json_path = plugin_dir_path(__FILE__) . '../../colorSchema.json';
        // Ensure we can write the file
        $written = @file_put_contents($json_path, wp_json_encode($schema, JSON_PRETTY_PRINT));
        if ($written !== false) {
            $message = 'Colors saved successfully.';
        } else {
            $message = 'Failed to save colors. Please check file permissions.';
        }
    }
}
?>

<div class="wrap">
    <div class="settings-header mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Settings & Configuration</h1>
        <p class="text-gray-600">Manage your company settings, banking details, and system charges</p>
        </div>

    <?php if (isset($message)) { 
        require_once plugin_dir_path(__FILE__) . '../components/toast.php';
        echo KIT_Toast::success($message);
    } ?>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 mb-8">
        <nav class="flex space-x-8" aria-label="Tabs">
            <?php echo KIT_Commons::renderButton('Banking Details', 'ghost', 'sm', ['id' => 'tab-banking', 'classes' => 'tab-button active', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>', 'iconPosition' => 'left']); ?>
            <?php echo KIT_Commons::renderButton('Company Details', 'ghost', 'sm', ['id' => 'tab-company', 'classes' => 'tab-button', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>', 'iconPosition' => 'left']); ?>
            <?php echo KIT_Commons::renderButton('VAT & Charges', 'ghost', 'sm', ['id' => 'tab-charges', 'classes' => 'tab-button', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>', 'iconPosition' => 'left']); ?>
            <?php echo KIT_Commons::renderButton('Color Scheme', 'ghost', 'sm', ['id' => 'tab-colors', 'classes' => 'tab-button', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7a4 4 0 018 0v10a4 4 0 11-8 0V7zm8 0a4 4 0 018 0v4a4 4 0 01-4 4h-4" />', 'iconPosition' => 'left']); ?>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="tab-contents">
        <!-- Banking Details Tab -->
        <div id="content-banking" class="tab-panel active">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Banking Details</h2>
                    <p class="text-sm text-gray-600 mt-1">Configure your company's banking information for payments and invoices</p>
                </div>
                
                <div class="p-6">
                    <form method="post" action="">
                        <?php wp_nonce_field('save_settings', 'settings_nonce'); ?>
                        <input type="hidden" name="action" value="save_banking">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group">
                                <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-2">Bank Name</label>
                                <input type="text" id="bank_name" name="bank_name" 
                                       value="<?php echo esc_attr($wpdb->get_var('SELECT bank_name FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group">
                                <label for="account_number" class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                                <input type="text" id="account_number" name="account_number" 
                                       value="<?php echo esc_attr($wpdb->get_var('SELECT account_number FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                            <div class="form-group">
                                <label for="branch_code" class="block text-sm font-medium text-gray-700 mb-2">Branch Code</label>
                                <input type="text" id="branch_code" name="branch_code" 
                                       value="<?php echo esc_attr($wpdb->get_var('SELECT branch_code FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                            <div class="form-group">
                                <label for="account_type" class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                                <select id="account_type" name="account_type" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <?php $acct = $wpdb->get_var('SELECT account_type FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1'); ?>
                                    <option value="savings" <?php selected($acct, 'savings'); ?>>Savings Account</option>
                                    <option value="current" <?php selected($acct, 'current'); ?>>Current Account</option>
                                    <option value="business" <?php selected($acct, 'business'); ?>>Business Account</option>
                    </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="account_holder" class="block text-sm font-medium text-gray-700 mb-2">Account Holder Name</label>
                                <input type="text" id="account_holder" name="account_holder" 
                                       value="<?php echo esc_attr($wpdb->get_var('SELECT account_holder FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group">
                                <label for="swift_code" class="block text-sm font-medium text-gray-700 mb-2">Swift Code</label>
                                <input type="text" id="swift_code" name="swift_code" 
                                       value="<?php echo esc_attr($wpdb->get_var('SELECT swift_code FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group md:col-span-2">
                                <label for="iban" class="block text-sm font-medium text-gray-700 mb-2">IBAN (International Bank Account Number)</label>
                                <input type="text" id="iban" name="iban" 
                                       value="<?php echo esc_attr($wpdb->get_var('SELECT iban FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <?php echo KIT_Commons::renderButton('Save Banking Details', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]); ?>
                        </div>
                    </form>
                </div>
            </div>
                </div>
                
        <!-- Company Details Tab -->
        <div id="content-company" class="tab-panel hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Company Details</h2>
                    <p class="text-sm text-gray-600 mt-1">Update your company information and contact details</p>
                </div>
                
                <div class="p-6">
                    <form method="post" action="">
                        <?php wp_nonce_field('save_settings', 'settings_nonce'); ?>
                        <input type="hidden" name="action" value="save_company">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-group md:col-span-2">
                                <label for="company_name" class="block text-sm font-medium text-gray-700 mb-2">Company Name</label>
                                <input type="text" id="company_name" name="company_name" 
                                       value="<?php echo esc_attr($wpdb->get_var('SELECT company_name FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group md:col-span-2">
                                <label for="company_address" class="block text-sm font-medium text-gray-700 mb-2">Company Address</label>
                                <textarea id="company_address" name="company_address" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo esc_textarea($wpdb->get_var('SELECT company_address FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="company_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" id="company_email" name="company_email" 
                                       value="<?php echo esc_attr($wpdb->get_var('SELECT company_email FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group">
                                <label for="company_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" id="company_phone" name="company_phone" 
                                       value="<?php echo esc_attr($wpdb->get_var('SELECT company_phone FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group">
                                <label for="company_website" class="block text-sm font-medium text-gray-700 mb-2">Website</label>
                                <input type="url" id="company_website" name="company_website" 
                                       value="<?php echo esc_attr($wpdb->get_var('SELECT company_website FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group">
                                <label for="company_registration" class="block text-sm font-medium text-gray-700 mb-2">Company Registration Number</label>
                                <input type="text" id="company_registration" name="company_registration" 
                                       value="<?php echo esc_attr($wpdb->get_var('SELECT company_registration FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group">
                                <label for="company_vat_number" class="block text-sm font-medium text-gray-700 mb-2">VAT Registration Number</label>
                                <input type="text" id="company_vat_number" name="company_vat_number" 
                                       value="<?php echo esc_attr($wpdb->get_var('SELECT company_vat_number FROM ' . $wpdb->prefix . 'kit_company_details LIMIT 1')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <?php echo KIT_Commons::renderButton('Save Company Details', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]); ?>
            </div>
        </form>
    </div>
            </div>
        </div>
        
        <!-- VAT & Charges Tab -->
        <div id="content-charges" class="tab-panel hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">VAT & Charges</h2>
                    <p class="text-sm text-gray-600 mt-1">Configure VAT percentage and SADC/SAD500 charges</p>
                </div>
                
                <div class="p-6">
                    <form method="post" action="">
                        <?php wp_nonce_field('save_settings', 'settings_nonce'); ?>
                        <input type="hidden" name="action" value="save_charges">
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="form-group">
                                <label for="vat_percentage" class="block text-sm font-medium text-gray-700 mb-2">
                                    VAT Percentage (%)
                                </label>
                                <div class="relative">
                                    <input type="number" id="vat_percentage" name="vat_percentage" 
                                            value="<?php echo esc_attr(KIT_Waybills::vatRate()); ?>" 
                                           step="0.01" min="0" max="100" disabled
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 text-sm">%</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">VAT rate is fixed at 10% (managed in code)</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="sadc_charge" class="block text-sm font-medium text-gray-700 mb-2">
                                    SADC Charge (R)
                                </label>
                                <div class="relative">
                                    
                                    <input type="number" id="sadc_charge" name="sadc_charge" 
                                            value="<?php echo esc_attr(KIT_Waybills::sad()); ?>" 
                                           step="0.01" min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 text-sm">R</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">SADC documentation charge</p>
            </div>
            
                            <div class="form-group">
                                <label for="sad500_charge" class="block text-sm font-medium text-gray-700 mb-2">
                                    SAD500 Charge (R)
                                </label>
                                <div class="relative">
                                    <input type="number" id="sad500_charge" name="sad500_charge" 
                                            value="<?php echo esc_attr(KIT_Waybills::sadc_certificate()); ?>" 
                                           step="0.01" min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 text-sm">R</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">SAD500 customs declaration charge</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="international_price" class="block text-sm font-medium text-gray-700 mb-2">
                                    International Price (USD)
                                </label>
                                <div class="relative">
                                    <input type="number" id="international_price" name="international_price" 
                                            value="<?php echo esc_attr(KIT_Waybills::international_price()); ?>" 
                                           step="0.01" min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 text-sm">$</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">International shipping fee in USD (converted to Rands)</p>
                                
                                <!-- Live Exchange Rate Display -->
                                <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-blue-700 font-medium">Live Exchange Rate:</span>
                                        <span class="text-blue-800 font-semibold" id="exchange-rate">Loading...</span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between text-sm">
                                        <span class="text-blue-600">Converted to ZAR:</span>
                                        <span class="text-blue-900 font-bold text-lg" id="zar-amount">R 0.00</span>
                                    </div>
                                    <div class="mt-1 text-xs text-blue-500">
                                        <span id="last-updated">Last updated: Never</span>
                                    </div>
                                </div>
                            </div>
            </div>
            
                        <!-- Preview Section -->
                        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Charge Preview</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">VAT Rate:</span>
                                    <span class="font-medium" id="vat-preview">15%</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">SADC Charge:</span>
                                    <span class="font-medium" id="sadc-preview">R 0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">SAD500 Charge:</span>
                                    <span class="font-medium" id="sad500-preview">R 0.00</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">International Price:</span>
                                    <span class="font-medium" id="international-preview">$0.00</span>
                                </div>
            </div>
        </div>
        
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <?php echo KIT_Commons::renderButton('Save VAT & Charges', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]); ?>
                            
                            <!-- Database Migration Button -->
                            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <h4 class="text-sm font-medium text-yellow-800 mb-2">Database Migration</h4>
                                <p class="text-xs text-yellow-700 mb-3">If the International Price field is not working, click the button below to add it to the database.</p>
                                <?php echo KIT_Commons::renderButton('Add International Price Field to Database', 'warning', 'md', ['id' => 'migrate-db', 'type' => 'button', 'gradient' => true]); ?>
                                <div id="migration-result" class="mt-2 text-sm"></div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Brand Colors (60/30/10) Tab -->
        <div id="content-colors" class="tab-panel hidden">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-900">Brand Colors (60 / 30 / 10)</h2>
                    <p class="text-sm text-gray-600 mt-1">Set your primary (60%), secondary (30%), and accent (10%) brand colors.</p>
                </div>
                <div class="p-6">
                    <?php
                        $schema_path = plugin_dir_path(__FILE__) . '../../colorSchema.json';
                        $schema = [ 'primary' => '#2563eb', 'secondary' => '#111827', 'accent' => '#10b981' ];
                        if (file_exists($schema_path)) {
                            $loaded = json_decode(file_get_contents($schema_path), true);
                            if (is_array($loaded)) { $schema = array_merge($schema, $loaded); }
                        }
                    ?>
                    <form method="post" action="">
                        <?php wp_nonce_field('save_settings', 'settings_nonce'); ?>
                        <input type="hidden" name="action" value="save_colors">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Primary (60%)</label>
                                <input type="color" name="primary_color" value="<?= esc_attr($schema['primary']); ?>" class="w-20 h-10 p-0 border rounded">
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Secondary (30%)</label>
                                <input type="color" name="secondary_color" value="<?= esc_attr($schema['secondary']); ?>" class="w-20 h-10 p-0 border rounded">
                            </div>
                            <div class="form-group">
                                <label class="block text-sm font-medium text-gray-700 mb-2">Accent (10%)</label>
                                <input type="color" name="accent_color" value="<?= esc_attr($schema['accent']); ?>" class="w-20 h-10 p-0 border rounded">
                            </div>
                        </div>
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <?php echo KIT_Commons::renderButton('Save Colors', 'primary', 'lg', ['type' => 'submit', 'gradient' => true]); ?>
                        </div>
                    </form>
                    <div class="mt-6 grid grid-cols-3 gap-4">
                        <div class="rounded-lg border p-3" style="background: <?= esc_attr($schema['primary']); ?>; color: #fff;">Primary</div>
                        <div class="rounded-lg border p-3" style="background: <?= esc_attr($schema['secondary']); ?>; color: #fff;">Secondary</div>
                        <div class="rounded-lg border p-3" style="background: <?= esc_attr($schema['accent']); ?>; color: #fff;">Accent</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Security Notice -->
    <div class="mt-8 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Security Notice</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>This settings page is restricted to authorized administrators only. All changes are logged for security purposes.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.tab-button {
    @apply text-gray-500 hover:text-gray-700 hover:bg-gray-100;
}

.tab-button.active {
    @apply text-blue-600 bg-blue-50 border-b-2 border-blue-600;
}

.tab-panel {
    @apply transition-all duration-300 ease-in-out;
}

.form-group {
    @apply space-y-1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabs = document.querySelectorAll('.tab-button');
    const panels = document.querySelectorAll('.tab-panel');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.id.replace('tab-', 'content-');
            
            // Remove active class from all tabs and panels
            tabs.forEach(t => t.classList.remove('active'));
            panels.forEach(p => p.classList.add('hidden'));
            
            // Add active class to clicked tab and show target panel
            tab.classList.add('active');
            document.getElementById(target).classList.remove('hidden');
        });
    });
    
    // Live preview for charges
    const vatInput = document.getElementById('vat_percentage');
    const sadcInput = document.getElementById('sadc_charge');
    const sad500Input = document.getElementById('sad500_charge');
    const internationalInput = document.getElementById('international_price');
    
    function updatePreview() {
        document.getElementById('vat-preview').textContent = (vatInput.value || 0) + '%';
        document.getElementById('sadc-preview').textContent = 'R ' + parseFloat(sadcInput.value || 0).toFixed(2);
        document.getElementById('sad500-preview').textContent = 'R ' + parseFloat(sad500Input.value || 0).toFixed(2);
        document.getElementById('international-preview').textContent = '$' + parseFloat(internationalInput.value || 0).toFixed(2);
    }
    
    vatInput.addEventListener('input', updatePreview);
    sadcInput.addEventListener('input', updatePreview);
    sad500Input.addEventListener('input', updatePreview);
    internationalInput.addEventListener('input', updatePreview);
    
    // Initialize preview
    updatePreview();
    
    // Live Exchange Rate Functionality
    let currentExchangeRate = 18.50; // Default fallback rate
    
    async function fetchExchangeRate() {
        try {
            // Try to fetch from a free exchange rate API
            const response = await fetch('https://api.exchangerate-api.com/v4/latest/USD');
            if (response.ok) {
                const data = await response.json();
                currentExchangeRate = data.rates.ZAR;
                updateExchangeRateDisplay();
                updateLastUpdated();
            } else {
                throw new Error('Failed to fetch exchange rate');
            }
        } catch (error) {
            console.log('Using fallback exchange rate:', currentExchangeRate);
            updateExchangeRateDisplay();
            updateLastUpdated();
        }
    }
    
    function updateExchangeRateDisplay() {
        const usdAmount = parseFloat(internationalInput.value) || 0;
        const zarAmount = usdAmount * currentExchangeRate;
        
        document.getElementById('exchange-rate').textContent = `1 USD = R ${currentExchangeRate.toFixed(2)}`;
        document.getElementById('zar-amount').textContent = `R ${zarAmount.toFixed(2)}`;
    }
    
    function updateLastUpdated() {
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', { 
            hour12: false, 
            hour: '2-digit', 
            minute: '2-digit',
            second: '2-digit'
        });
        document.getElementById('last-updated').textContent = `Last updated: ${timeString}`;
    }
    
    // Update exchange rate display when international price changes
    internationalInput.addEventListener('input', updateExchangeRateDisplay);
    
    // Fetch exchange rate on page load and every 5 minutes
    fetchExchangeRate();
    setInterval(fetchExchangeRate, 5 * 60 * 1000); // Update every 5 minutes
    
    // Database Migration Functionality
    const migrateButton = document.getElementById('migrate-db');
    const migrationResult = document.getElementById('migration-result');
    
    if (migrateButton) {
        migrateButton.addEventListener('click', async function() {
            migrateButton.disabled = true;
            migrateButton.textContent = 'Adding Field...';
            migrationResult.innerHTML = '<span class="text-blue-600">⏳ Adding international_price field to database...</span>';
            
            try {
                // Create a simple AJAX request to run the migration
                const formData = new FormData();
                formData.append('action', 'migrate_international_price');
                formData.append('nonce', '<?php echo wp_create_nonce("migrate_international_price"); ?>');
                
                const response = await fetch(ajaxurl || '/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    migrationResult.innerHTML = '<span class="text-green-600">✅ ' + result.data.message + '</span>';
                    // Refresh the page after successful migration
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    migrationResult.innerHTML = '<span class="text-red-600">❌ ' + (result.data.message || 'Migration failed') + '</span>';
                }
            } catch (error) {
                migrationResult.innerHTML = '<span class="text-red-600">❌ Error: ' + error.message + '</span>';
            } finally {
                migrateButton.disabled = false;
                migrateButton.textContent = 'Add International Price Field to Database';
            }
        });
    }
});
</script>
