<?php if (!defined('ABSPATH')) { exit; } ?>

<?php
/**
 * Reusable Delivery Card Component
 * 
 * @param object $delivery Delivery data object
 * @param string $card_type Type of card (scheduled, in-transit, delivered, etc.)
 * @param bool $clickable Whether the card is clickable
 * @param string $onclick_function JavaScript function to call on click
 * @param array|null $radio_options Radio button options (type, name, checked_id)
 */
function renderDeliveryCard($delivery, $card_type = 'scheduled', $clickable = true, $onclick_function = 'handleDeliveryClick', $radio_options = null) {
    // Get country names - support both ID-based and direct name-based
    $origin_country = 'Unknown';
    $dest_country = 'Unknown';
    
    if (isset($delivery->origin_country_id) && $delivery->origin_country_id) {
        $origin_country = getCountryNameById($delivery->origin_country_id);
    } elseif (isset($delivery->origin_country)) {
        $origin_country = $delivery->origin_country;
    }
    
    if (isset($delivery->destination_country_id) && $delivery->destination_country_id) {
        $dest_country = getCountryNameById($delivery->destination_country_id);
    } elseif (isset($delivery->destination_country)) {
        $dest_country = $delivery->destination_country;
    }
    
    // Determine status colors and text based on card type
    $status_config = getStatusConfig($card_type);
    
    // Determine if card should be clickable
    $cursor_class = $clickable ? 'cursor-pointer hover:shadow-md hover:border-blue-300' : 'cursor-default';
    
    // Format date - match the image format "04 Sep 2025"
    $day = date('d', strtotime($delivery->dispatch_date));
    $month = date('M', strtotime($delivery->dispatch_date));
    $year = date('Y', strtotime($delivery->dispatch_date));
?>

<div class="delivery-card bg-white rounded-lg border border-gray-200 p-3 group relative <?php echo $cursor_class; ?>" 
     data-index="<?php echo esc_attr($delivery->direction_id); ?>"
     <?php if ($clickable): ?>onclick="selectDeliveryCard(this, <?php echo esc_attr($delivery->direction_id); ?>)"<?php endif; ?>>
     
    <?php if ($radio_options): ?>
        <input type="<?php echo esc_attr($radio_options['type'] ?? 'radio'); ?>" 
               name="<?php echo esc_attr($radio_options['name'] ?? 'delivery_id'); ?>" 
               value="<?php echo esc_attr($delivery->id ?? $delivery->direction_id); ?>" 
               class="sr-only" 
               <?php echo ($radio_options['checked_id'] && ($delivery->id == $radio_options['checked_id'] || $delivery->direction_id == $radio_options['checked_id'])) ? 'checked' : ''; ?>>
    <?php endif; ?>
    
    <!-- Date Display - match image format "04 Sep 2025" -->
    <div class="text-center mb-2">
        <div class="text-xs font-bold text-gray-900"><?php echo $day . ' ' . $month . ' ' . $year; ?></div>
    </div>
    
    <!-- Route Information - show full country names like image -->
    <div class="text-xs text-center text-gray-700 leading-tight mb-2">
        <div class="font-medium"><?php echo htmlspecialchars($origin_country); ?></div>
        <div class="text-gray-400 text-xs">→</div>
        <div class="font-medium"><?php echo htmlspecialchars($dest_country); ?></div>
    </div>
    
    <!-- Status Indicator -->
    <div class="flex items-center justify-center space-x-1">
        <span class="inline-block w-2 h-2 <?php echo $status_config['color']; ?> rounded-full"></span>
        <span class="text-xs text-gray-500"><?php echo $status_config['text']; ?></span>
    </div>
    
    <!-- Hover Details (only for clickable cards) -->
    <?php if ($clickable): ?>
    <div class="absolute inset-0 bg-blue-50 border-2 border-blue-300 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none z-10">
        <div class="p-2 text-xs text-blue-800">
            <?php if (!empty($delivery->truck_number)): ?>
                <div class="font-medium">Truck: <?php echo htmlspecialchars($delivery->truck_number); ?></div>
            <?php endif; ?>
            <?php if (!empty($delivery->description)): ?>
                <div><?php echo htmlspecialchars($delivery->description); ?></div>
            <?php endif; ?>
            <?php if (empty($delivery->truck_number) && empty($delivery->description)): ?>
                <div class="font-medium">Click to select this delivery</div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
}

/**
 * Get status configuration based on card type
 */
function getStatusConfig($card_type) {
    switch ($card_type) {
        case 'scheduled':
            return ['color' => 'bg-green-500', 'text' => 'Scheduled'];
        case 'in-transit':
            return ['color' => 'bg-blue-500', 'text' => 'In Transit'];
        case 'delivered':
            return ['color' => 'bg-gray-500', 'text' => 'Delivered'];
        case 'cancelled':
            return ['color' => 'bg-red-500', 'text' => 'Cancelled'];
        default:
            return ['color' => 'bg-gray-400', 'text' => 'Unknown'];
    }
}

/**
 * Helper function to get country name by ID
 */
function getCountryNameById($country_id) {
    global $wpdb;
    
    // Check if WordPress database is available
    if (!isset($wpdb) || !is_object($wpdb)) {
        return 'Unknown';
    }
    
    $table_name = $wpdb->prefix . 'kit_operating_countries';
    $country = $wpdb->get_row($wpdb->prepare("SELECT country_name FROM $table_name WHERE id = %d", $country_id));
    return $country ? $country->country_name : 'Unknown';
}
?>
