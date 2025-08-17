<?php if (!defined('ABSPATH')) { exit; } ?>
<div id="scheduled-deliveries-container" class="mt-4">
    <h4 class="text-md font-medium text-gray-600 mb-2">Scheduled Deliveries</h4>
    <div id="scheduled-deliveries-list" class="flex gap-4 min-h-20 bg-slate-200 max-h-60 overflow-y-auto p-2 border rounded-md">
        <?php
        $default_country = ''; // or set a default country code
        $delivery_going = KIT_Deliveries::getScheduledDeliveries();

        foreach ($delivery_going as $delivery):
            echo KIT_Commons::DestinationButtonBox([
                'name'               => 'direction_id',
                'delivery_reference' => 'delivery_id',
                'direction_id'       => $delivery->direction_id,
                'dispatch_date'       => $delivery->dispatch_date,
                'truck_number'       => $delivery->truck_number,
                'status'    => $delivery->status,
                'description' => $delivery->description,
                'destination_country_id'     => $delivery->destination_country_id,
                'origin_country_id'        => $delivery->origin_country_id,
                'class' => 'delivery-button',
                'onclick' => 'handleDeliveryChange(' . $delivery->direction_id . ')',
            ]);
        endforeach; ?>
    </div>
    <div id="deliveryDetails"></div>
</div>