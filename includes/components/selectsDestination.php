<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label for="destination_country_select" class="<?= KIT_Commons::labelClass() ?>">Destination Country</label>
        <?php
        $destinationCountryId = 1; // Default to South Africa
        
        // For edit mode, try to get destination country ID from waybill data
        if (isset($waybill['delivery_id']) && !empty($waybill['delivery_id'])) {
            $delData = KIT_Deliveries::get_delivery($waybill['delivery_id']);
            if ($delData && isset($delData->destination_country_id)) {
                $destinationCountryId = $delData->destination_country_id;
            }
        } elseif (isset($waybillToStats['country_id']) || isset($routeData->destination_country_id)) {
            $destinationCountryId = isset($waybillToStats['country_id'])
                ? $waybillToStats['country_id']
                : (isset($routeData->destination_country_id)
                    ? $routeData->destination_country_id
                    : 1);
        }

        echo KIT_Deliveries::selectAllCountries('destination_country', 'destination_country_select', $destinationCountryId, "required", 'destination');
        ?>
    </div>
    <div>
        <label for="destination_city_select" class="<?= KIT_Commons::labelClass() ?>">Destination City</label>
        <?php
        $destinationCityId = 1; // Default city
        
        // For edit mode, try to get destination city ID from waybill data
        if (isset($waybill['delivery_id']) && !empty($waybill['delivery_id'])) {
            $delData = KIT_Deliveries::get_delivery($waybill['delivery_id']);
            if ($delData && isset($delData->destination_city_id)) {
                $destinationCityId = $delData->destination_city_id;
            }
        } elseif (isset($waybillToStats['country_id'])) {
            $delData = KIT_Deliveries::get_delivery($waybill['delivery_id']);
            if ($delData && isset($delData->destination_city_id)) {
                $destinationCityId = $delData->destination_city_id;
            }
        }
        
        echo KIT_Deliveries::selectAllCitiesByCountry('destination_city', 'destination_city_select', $destinationCountryId, $destinationCityId);
        ?>
    </div>
</div>