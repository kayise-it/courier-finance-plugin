<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label for="origin_country_select" class="<?= KIT_Commons::labelClass() ?>">Origin Country</label>
        <?php 
        // Determine default country ID - prioritize existing waybill data, then customer data, then default to 1
        $defaultCountryId = 1;
        
        // For route creation, get all countries (active and inactive)
        // For edit mode, try to get country ID from route data
        if (isset($routeData) && $routeData !== null) {
            $defaultCountryId = kit_Commons::verifyint($routeData->origin_country_id);
        }
        
        // ROUTE-SPECIFIC: Use rule system to show ALL countries for route management
        echo KIT_Deliveries::selectAllCountries('country_id', 'origin_country_select', $defaultCountryId, "required", 'origin', [
            'show_all_countries' => true,
            'show_inactive_indicators' => true
        ]); 
        ?>
    </div>
    <div>
        <label for="origin_city_select" class="<?= KIT_Commons::labelClass() ?>">Origin City</label>
        <?php 
        // Determine default city ID - prioritize existing route data, then default to 1
        $defaultCityId = 1;
        
        // For edit mode, try to get city ID from route data
        if (isset($routeData) && $routeData !== null) {
            // For routes, try to get the city ID from route data
            if (isset($routeData->origin_city_id)) {
                $defaultCityId = intval($routeData->origin_city_id);
            }
        }
        // Also check if we're editing a waybill and have origin_city_id in miscellaneous data
        elseif (isset($waybill['miscellaneous']) && is_array($waybill['miscellaneous']) && isset($waybill['miscellaneous']['others']['origin_city_id'])) {
            $defaultCityId = intval($waybill['miscellaneous']['others']['origin_city_id']);
        }
        
        echo KIT_Deliveries::selectAllCitiesByCountry('city_id', 'origin_city_select', $defaultCountryId, $defaultCityId); 
        ?>
    </div>
</div>
