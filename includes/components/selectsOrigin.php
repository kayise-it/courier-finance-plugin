<?php if (!defined('ABSPATH')) { exit; } ?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label for="origin_country_select" class="<?= KIT_Commons::labelClass() ?>">Origin Country</label>
        <?php 
        // Determine default country ID - prioritize existing waybill data, then customer data, then default to 1
        $defaultCountryId = 1;
        
        // For edit mode, try to get country ID from waybill data
        if (isset($waybill['delivery_id']) && !empty($waybill['delivery_id'])) {
            $delData = KIT_Deliveries::get_delivery($waybill['delivery_id']);
            if ($delData && isset($delData->origin_country_id)) {
                $defaultCountryId = $delData->origin_country_id;
            }
        } elseif (isset($waybillFromStats['country_id'])) {
            $defaultCountryId = $waybillFromStats['country_id'];
        } elseif (isset($customer) && isset($customer->country_id)) {
            $defaultCountryId = $customer->country_id;
        }
        
        echo KIT_Deliveries::selectAllCountries('country_id', 'origin_country_select', $defaultCountryId, "required", 'origin'); 
        ?>
    </div>
    <div>
        <label for="origin_city_select" class="<?= KIT_Commons::labelClass() ?>">Origin City</label>
        <?php 
        // Determine default city ID - prioritize existing waybill data, then customer data, then default to 1
        $defaultCityId = 1;
        
        // For edit mode, try to get city ID from waybill delivery data
        if (isset($waybill['delivery_id']) && !empty($waybill['delivery_id'])) {
            $delData = KIT_Deliveries::get_delivery($waybill['delivery_id']);
            if ($delData && isset($delData->origin_city_id)) {
                $defaultCityId = $delData->origin_city_id;
            }
        } elseif (isset($waybillFromStats['country_id'])) {
            // For existing waybills, try to get the city from delivery data
            if (isset($waybill['delivery_id'])) {
                $delData = KIT_Deliveries::get_delivery($waybill['delivery_id']);
                if ($delData && isset($delData->origin_country_id)) {
                    $cityData = KIT_Deliveries::getCityData($delData->origin_country_id);
                    if ($cityData && isset($cityData->id)) {
                        $defaultCityId = $cityData->id;
                    }
                }
            }
        } elseif (isset($customer) && isset($customer->city_id)) {
            $defaultCityId = $customer->city_id;
        }
        
        echo KIT_Deliveries::selectAllCitiesByCountry('city_id', 'origin_city_select', $defaultCountryId, $defaultCityId); 
        ?>
    </div>
</div>