<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Global component for displaying waybill information in a minimalistic, professional format
 * 
 * @param array $waybill Waybill data array (must include waybill_no, tracking_number, product_invoice_number, product_invoice_amount)
 *                       Optional: delivery_id, delivery_reference (for delivery truck link)
 * @param string $waybill_id Optional waybill ID for linking
 * @param array $options Optional configuration:
 *   - 'show_amount' (bool): Whether to show the amount (default: true, respects KIT_User_Roles::can_see_prices())
 *   - 'currency_symbol' (string): Currency symbol to use (default: KIT_Commons::currency())
 *   - 'class' (string): Additional CSS classes for the container
 *   - 'exclude' (array): Array of field names to exclude from display. Valid values: 'waybill', 'tracking', 'invoice', 'amount', 'delivery'
 *     Aliases supported: 'grand_total', 'total', 'price' (all map to 'amount')
 *   - 'enable_js_updates' (bool): Whether to enable JavaScript updates for the amount field (default: false)
 *     When enabled, uses id="waybilltotalMockup" so existing JS can update it. Only enable for one instance per page.
 */

// Ensure waybill data exists
if (empty($waybill) || !is_array($waybill)) {
    return;
}

// Extract options - support both $options and $waybill_info_options variable names
// Priority: $waybill_info_options (most common) > $options (function parameter)
if (isset($waybill_info_options) && is_array($waybill_info_options)) {
    $options = $waybill_info_options;
} elseif (!isset($options) || !is_array($options)) {
    $options = [];
}
$show_amount = isset($options['show_amount']) ? $options['show_amount'] : true;
$currency_symbol = isset($options['currency_symbol']) ? $options['currency_symbol'] : (class_exists('KIT_Commons') ? KIT_Commons::currency() : 'R');
$container_class = isset($options['class']) ? ' ' . esc_attr($options['class']) : '';
$exclude_fields = isset($options['exclude']) && is_array($options['exclude']) ? $options['exclude'] : [];
$enable_js_updates = isset($options['enable_js_updates']) ? (bool) $options['enable_js_updates'] : false;

// Normalize exclude fields (convert to lowercase for case-insensitive matching)
$exclude_fields = array_map('strtolower', $exclude_fields);

// Map aliases to actual field names (for backward compatibility)
$field_aliases = [
    'grand_total' => 'amount',
    'total' => 'amount',
    'price' => 'amount',
];

// Normalize exclude fields: replace aliases with actual field names
$normalized_exclude = [];
foreach ($exclude_fields as $field) {
    if (isset($field_aliases[$field])) {
        $normalized_exclude[] = $field_aliases[$field];
    } else {
        $normalized_exclude[] = $field;
    }
}
$exclude_fields = array_unique($normalized_exclude);

// Helper function to check if a field should be excluded
$is_excluded = function($field_name) use ($exclude_fields) {
    return in_array(strtolower($field_name), $exclude_fields);
};

// Check if user can see prices (if KIT_User_Roles exists)
$can_see_prices = true;
if (class_exists('KIT_User_Roles')) {
    $can_show_amount = $show_amount && KIT_User_Roles::can_see_prices();
} else {
    $can_show_amount = $show_amount;
}
?>

<!-- Professional minimalistic waybill info display -->
<div class="rounded-lg border border-gray-200 bg-white shadow-sm p-4 min-w-0 overflow-hidden<?php echo $container_class; ?>">
<!-- distribute the content evenly across the container -->
<div class="flex flex-wrap justify-between items-center gap-x-8 gap-y-3 text-sm">
        <?php
        // Track which fields are visible to conditionally show dividers
        $visible_fields = [];
        
        // Waybill Number
        if (!$is_excluded('waybill')) {
            $visible_fields[] = 'waybill';
        ?>
        <div class="text-center items-center gap-2">
            <span class="text-gray-500 text-xs font-medium uppercase tracking-wide">Waybill</span>
            <br>
            <span class="text-gray-900 font-semibold text-base"><?php echo esc_html($waybill['waybill_no'] ?? 'N/A'); ?></span>
        </div>
        <?php
        }
        
        // Tracking Number
        if (!$is_excluded('tracking')) {
            if (!empty($visible_fields)): ?>
                <div class="hidden sm:block w-px h-5 bg-gray-200"></div>
            <?php endif;
            $visible_fields[] = 'tracking';
        ?>
        <div class="text-center items-center gap-2">
            <span class="text-gray-500 text-xs font-medium uppercase tracking-wide">Tracking</span>
            <br>
            <span class="text-gray-900 font-semibold text-base"><?php echo esc_html($waybill['tracking_number'] ?? 'N/A'); ?></span>
        </div>
        <?php
        }
        
        // Invoice Number
        if (!$is_excluded('invoice')) {
            if (!empty($visible_fields)): ?>
                <div class="hidden sm:block w-px h-5 bg-gray-200"></div>
            <?php endif;
            $visible_fields[] = 'invoice';
        ?>
        <div class="text-center items-center gap-2">
            <span class="text-gray-500 text-xs font-medium uppercase tracking-wide">Invoice</span>
            <br>
            <span class="text-gray-900 font-semibold text-base"><?php echo esc_html($waybill['product_invoice_number'] ?? 'N/A'); ?></span>
        </div>
        <?php
        }
        
        // Delivery Truck
        if (!$is_excluded('delivery')) {
            if (!empty($visible_fields)): ?>
                <div class="hidden sm:block w-px h-5 bg-gray-200"></div>
            <?php endif;
            $visible_fields[] = 'delivery';
            $delivery_id = intval($waybill['delivery_id'] ?? 0);
            $delivery_reference = $waybill['delivery_reference'] ?? '';
            $tooltip_id = 'delivery-tooltip-' . uniqid();
        ?>
        <div class="text-center items-center gap-2 relative">
            <span class="text-gray-500 text-xs font-medium uppercase tracking-wide">Delivery</span>
            <br>
            <?php if ($delivery_id > 0 && !empty($delivery_reference)): ?>
                <a
                    href="?page=view-deliveries&delivery_id=<?php echo urlencode($delivery_id); ?>"
                    class="font-semibold text-base text-blue-600 hover:text-blue-800 hover:underline custom-tooltip-trigger"
                    target="_blank"
                    rel="noopener"
                    data-tooltip-id="<?php echo esc_attr($tooltip_id); ?>"
                    data-tooltip-text="Open delivery <?php echo esc_attr($delivery_reference); ?> in a new tab"
                >
                    <?php echo esc_html($delivery_reference); ?>
                </a>
            <?php else: ?>
                <span
                    class="text-gray-900 font-semibold text-base custom-tooltip-trigger"
                    <?php if (!empty($delivery_reference)): ?>
                        data-tooltip-id="<?php echo esc_attr($tooltip_id); ?>"
                        data-tooltip-text="<?php echo esc_attr($delivery_reference); ?>"
                    <?php endif; ?>
                >
                    <?php echo esc_html($delivery_reference ?: 'N/A'); ?>
                </span>
            <?php endif; ?>
            
            <!-- Custom Tooltip -->
            <div id="<?php echo esc_attr($tooltip_id); ?>" class="custom-tooltip">
                <div class="custom-tooltip-arrow"></div>
                <div class="custom-tooltip-content">
                    <div class="custom-tooltip-icon">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                    </div>
                    <div class="custom-tooltip-text">
                        <span class="tooltip-message"></span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        }
        
        // Amount
        if ($can_show_amount && !$is_excluded('amount')) {
            if (!empty($visible_fields)): ?>
                <div class="hidden sm:block w-px h-5 bg-gray-200"></div>
            <?php endif;
            $visible_fields[] = 'amount';
        ?>
        <div class="text-center items-center gap-2">
            <span class="text-gray-500 text-xs font-medium uppercase tracking-wide">Amount</span>
            <br>
            <span class="text-gray-900 font-semibold text-base">
                <?php echo esc_html($currency_symbol); ?>
                <span class="waybilltotalMockup"<?php echo $enable_js_updates ? ' id="waybilltotalMockup"' : ''; ?>><?php echo number_format($waybill['product_invoice_amount'] ?? 0, 2); ?></span>
            </span>
        </div>
        <?php
        }
        ?>
    </div>
</div>

<style>
.custom-tooltip {
    position: absolute;
    bottom: calc(100% + 12px);
    left: 50%;
    transform: translateX(-50%);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.2s ease, visibility 0.2s ease;
    z-index: 1000;
}

.custom-tooltip.active {
    opacity: 1;
    visibility: visible;
}

.custom-tooltip-content {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    padding: 16px 20px;
    min-width: 200px;
    max-width: 300px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.custom-tooltip-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, #a855f7 0%, #3b82f6 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: white;
}

.custom-tooltip-text {
    flex: 1;
    color: #4b5563;
    font-size: 14px;
    line-height: 1.5;
}

.custom-tooltip-arrow {
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-top: 8px solid white;
}

.custom-tooltip-trigger {
    cursor: pointer;
    position: relative;
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const triggers = document.querySelectorAll('.custom-tooltip-trigger');
        
        triggers.forEach(function(trigger) {
            const tooltipId = trigger.getAttribute('data-tooltip-id');
            if (!tooltipId) return;
            
            const tooltip = document.getElementById(tooltipId);
            if (!tooltip) return;
            
            const tooltipText = trigger.getAttribute('data-tooltip-text');
            if (tooltipText && tooltip.querySelector('.tooltip-message')) {
                tooltip.querySelector('.tooltip-message').textContent = tooltipText;
            }
            
            trigger.addEventListener('mouseenter', function() {
                tooltip.classList.add('active');
            });
            
            trigger.addEventListener('mouseleave', function() {
                tooltip.classList.remove('active');
            });
        });
    });
})();
</script>
