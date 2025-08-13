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

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    if (wp_verify_nonce($_POST['settings_nonce'], 'save_settings')) {
        switch ($_POST['action']) {
            case 'save_banking':
                update_option('kit_bank_name', sanitize_text_field($_POST['bank_name']));
                update_option('kit_account_number', sanitize_text_field($_POST['account_number']));
                update_option('kit_branch_code', sanitize_text_field($_POST['branch_code']));
                update_option('kit_account_type', sanitize_text_field($_POST['account_type']));
                update_option('kit_account_holder', sanitize_text_field($_POST['account_holder']));
                update_option('kit_swift_code', sanitize_text_field($_POST['swift_code']));
                update_option('kit_iban', sanitize_text_field($_POST['iban']));
                $message = 'Banking details saved successfully!';
                break;
                
            case 'save_company':
                update_option('kit_company_name', sanitize_text_field($_POST['company_name']));
                update_option('kit_company_address', sanitize_textarea_field($_POST['company_address']));
                update_option('kit_company_email', sanitize_email($_POST['company_email']));
                update_option('kit_company_phone', sanitize_text_field($_POST['company_phone']));
                update_option('kit_company_website', esc_url_raw($_POST['company_website']));
                update_option('kit_company_registration', sanitize_text_field($_POST['company_registration']));
                update_option('kit_company_vat_number', sanitize_text_field($_POST['company_vat_number']));
                $message = 'Company details saved successfully!';
                break;
                
            case 'save_charges':
                update_option('kit_vat_percentage', floatval($_POST['vat_percentage']));
                update_option('kit_sadc_charge', floatval($_POST['sadc_charge']));
                update_option('kit_sad500_charge', floatval($_POST['sad500_charge']));
                $message = 'Charges and VAT settings saved successfully!';
                break;
        }
    }
}
?>

<div class="wrap">
    <div class="settings-header mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Settings & Configuration</h1>
        <p class="text-gray-600">Manage your company settings, banking details, and system charges</p>
        </div>

    <?php if (isset($message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <span class="font-medium">Success!</span> <?php echo esc_html($message); ?>
        </div>
    <?php endif; ?>

    <!-- Tab Navigation -->
    <div class="border-b border-gray-200 mb-8">
        <nav class="flex space-x-8" aria-label="Tabs">
            <button id="tab-banking" class="tab-button active px-3 py-2 text-sm font-medium rounded-md transition-colors">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                Banking Details
            </button>
            <button id="tab-company" class="tab-button px-3 py-2 text-sm font-medium rounded-md transition-colors">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                Company Details
            </button>
            <button id="tab-charges" class="tab-button px-3 py-2 text-sm font-medium rounded-md transition-colors">
                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                </svg>
                VAT & Charges
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <div class="tab-content">
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
                                       value="<?php echo esc_attr(get_option('kit_bank_name', '')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group">
                                <label for="account_number" class="block text-sm font-medium text-gray-700 mb-2">Account Number</label>
                                <input type="text" id="account_number" name="account_number" 
                                       value="<?php echo esc_attr(get_option('kit_account_number', '')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                            <div class="form-group">
                                <label for="branch_code" class="block text-sm font-medium text-gray-700 mb-2">Branch Code</label>
                                <input type="text" id="branch_code" name="branch_code" 
                                       value="<?php echo esc_attr(get_option('kit_branch_code', '')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                            <div class="form-group">
                                <label for="account_type" class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                                <select id="account_type" name="account_type" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="savings" <?php selected(get_option('kit_account_type'), 'savings'); ?>>Savings Account</option>
                                    <option value="current" <?php selected(get_option('kit_account_type'), 'current'); ?>>Current Account</option>
                                    <option value="business" <?php selected(get_option('kit_account_type'), 'business'); ?>>Business Account</option>
                    </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="account_holder" class="block text-sm font-medium text-gray-700 mb-2">Account Holder Name</label>
                                <input type="text" id="account_holder" name="account_holder" 
                                       value="<?php echo esc_attr(get_option('kit_account_holder', '')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group">
                                <label for="swift_code" class="block text-sm font-medium text-gray-700 mb-2">Swift Code</label>
                                <input type="text" id="swift_code" name="swift_code" 
                                       value="<?php echo esc_attr(get_option('kit_swift_code', '')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group md:col-span-2">
                                <label for="iban" class="block text-sm font-medium text-gray-700 mb-2">IBAN (International Bank Account Number)</label>
                                <input type="text" id="iban" name="iban" 
                                       value="<?php echo esc_attr(get_option('kit_iban', '')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>
                        
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                                Save Banking Details
                            </button>
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
                                       value="<?php echo esc_attr(get_option('kit_company_name', '')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group md:col-span-2">
                                <label for="company_address" class="block text-sm font-medium text-gray-700 mb-2">Company Address</label>
                                <textarea id="company_address" name="company_address" rows="3" 
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"><?php echo esc_textarea(get_option('kit_company_address', '')); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="company_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" id="company_email" name="company_email" 
                                       value="<?php echo esc_attr(get_option('kit_company_email', '')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group">
                                <label for="company_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" id="company_phone" name="company_phone" 
                                       value="<?php echo esc_attr(get_option('kit_company_phone', '')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group">
                                <label for="company_website" class="block text-sm font-medium text-gray-700 mb-2">Website</label>
                                <input type="url" id="company_website" name="company_website" 
                                       value="<?php echo esc_attr(get_option('kit_company_website', '')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group">
                                <label for="company_registration" class="block text-sm font-medium text-gray-700 mb-2">Company Registration Number</label>
                                <input type="text" id="company_registration" name="company_registration" 
                                       value="<?php echo esc_attr(get_option('kit_company_registration', '')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            
                            <div class="form-group">
                                <label for="company_vat_number" class="block text-sm font-medium text-gray-700 mb-2">VAT Registration Number</label>
                                <input type="text" id="company_vat_number" name="company_vat_number" 
                                       value="<?php echo esc_attr(get_option('kit_company_vat_number', '')); ?>" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                                Save Company Details
                            </button>
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
                                           value="<?php echo esc_attr(get_option('kit_vat_percentage', '15')); ?>" 
                                           step="0.01" min="0" max="100"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 text-sm">%</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Standard VAT rate for South Africa</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="sadc_charge" class="block text-sm font-medium text-gray-700 mb-2">
                                    SADC Charge (R)
                                </label>
                                <div class="relative">
                                    <input type="number" id="sadc_charge" name="sadc_charge" 
                                           value="<?php echo esc_attr(get_option('kit_sadc_charge', '0')); ?>" 
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
                                           value="<?php echo esc_attr(get_option('kit_sad500_charge', '0')); ?>" 
                                           step="0.01" min="0"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                                        <span class="text-gray-500 text-sm">R</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">SAD500 customs declaration charge</p>
                            </div>
            </div>
            
                        <!-- Preview Section -->
                        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-900 mb-3">Charge Preview</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
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
            </div>
        </div>
        
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                                Save VAT & Charges
                            </button>
                        </div>
                    </form>
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
    
    function updatePreview() {
        document.getElementById('vat-preview').textContent = (vatInput.value || 0) + '%';
        document.getElementById('sadc-preview').textContent = 'R ' + parseFloat(sadcInput.value || 0).toFixed(2);
        document.getElementById('sad500-preview').textContent = 'R ' + parseFloat(sad500Input.value || 0).toFixed(2);
    }
    
    vatInput.addEventListener('input', updatePreview);
    sadcInput.addEventListener('input', updatePreview);
    sad500Input.addEventListener('input', updatePreview);
    
    // Initialize preview
    updatePreview();
});
</script>
