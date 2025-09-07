<?php if (!defined('ABSPATH')) { exit; } ?>

<?php
/**
 * Delivery Grid Component - Example of how to use the delivery card component
 * This shows different ways to use the reusable delivery card
 */

// Include the reusable delivery card component
require_once __DIR__ . '/deliveryCard.php';
?>

<div class="delivery-grid-examples">
    <h3 class="text-lg font-medium text-gray-900 mb-4">Delivery Grid Examples</h3>
    
    <!-- Example 1: Scheduled Deliveries -->
    <div class="mb-8">
        <h4 class="text-md font-medium text-gray-700 mb-3">Scheduled Deliveries</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <?php
            $scheduled_deliveries = KIT_Deliveries::getScheduledDeliveries();
            foreach ($scheduled_deliveries as $delivery):
                renderDeliveryCard($delivery, 'scheduled', true, 'handleDeliveryClick');
            endforeach;
            ?>
        </div>
    </div>
    
    <!-- Example 2: In-Transit Deliveries (Read-only) -->
    <div class="mb-8">
        <h4 class="text-md font-medium text-gray-700 mb-3">In-Transit Deliveries (Read-only)</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <?php
            // Example with read-only cards (not clickable)
            $in_transit_deliveries = []; // Replace with actual data
            if (!empty($in_transit_deliveries)):
                foreach ($in_transit_deliveries as $delivery):
                    renderDeliveryCard($delivery, 'in-transit', false);
                endforeach;
            else:
                echo '<div class="col-span-full text-center text-gray-500 py-8">No in-transit deliveries</div>';
            endif;
            ?>
        </div>
    </div>
    
    <!-- Example 3: Delivered Deliveries -->
    <div class="mb-8">
        <h4 class="text-md font-medium text-gray-700 mb-3">Delivered Deliveries</h4>
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
            <?php
            // Example with delivered status
            $delivered_deliveries = []; // Replace with actual data
            if (!empty($delivered_deliveries)):
                foreach ($delivered_deliveries as $delivery):
                    renderDeliveryCard($delivery, 'delivered', true, 'handleDeliveredDeliveryClick');
                endforeach;
            else:
                echo '<div class="col-span-full text-center text-gray-500 py-8">No delivered deliveries</div>';
            endif;
            ?>
        </div>
    </div>
</div>

<!-- Include the delivery card JavaScript -->
<script src="../../js/delivery-card.js"></script>


