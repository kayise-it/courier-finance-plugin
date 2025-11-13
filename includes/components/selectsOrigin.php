<?php if (!defined('ABSPATH')) {
    exit;
} 

?>
<div>
    <label for="origin_country_select" class="<?= KIT_Commons::labelClass() ?>">Origin Country</label>
    <?php
    // Extract origin data from waybill miscellaneous field
    $origin_city_id = 0;
    $origin_country_id = 0;
    
    if (isset($waybill['miscellaneous']) && is_array($waybill['miscellaneous'])) {
        $misc = maybe_unserialize($waybill['miscellaneous']);
        if (is_array($misc) && isset($misc['others'])) {
            // Debug: Show all available keys in others
            echo "<!-- DEBUG: Available keys in others = " . implode(', ', array_keys($misc['others'])) . " -->";
            
            $origin_city_id = intval($misc['others']['origin_city_id'] ?? 0);
            $origin_country_id = intval($misc['others']['origin_country_id'] ?? 0);
            
            // Debug: Show the actual values found
            echo "<!-- DEBUG: Found origin_city_id = " . $origin_city_id . " -->";
            echo "<!-- DEBUG: Found origin_country_id = " . $origin_country_id . " -->";
        }
    }

    // Fallback to top-level waybill values if present
    if (!$origin_city_id && isset($waybill['origin_city_id'])) {
        $origin_city_id = intval($waybill['origin_city_id']);
    } elseif (!$origin_city_id && isset($waybill['origin_city'])) {
        $origin_city_id = intval($waybill['origin_city']);
    }

    if (!$origin_country_id && isset($waybill['origin_country_id'])) {
        $origin_country_id = intval($waybill['origin_country_id']);
    } elseif (!$origin_country_id && isset($waybill['origin_country'])) {
        $origin_country_id = intval($waybill['origin_country']);
    }

    // Determine default country ID - prioritize delivery form data, then existing waybill data, then customer data, then default to 1
    $defaultCountryId = ($origin_country_id) ? $origin_country_id : 1;

    // PRIORITY 1: Use waybill's saved miscellaneous data (highest priority for edit mode)
    // This is already set above from $origin_country_id
    
    // PRIORITY 2: For delivery form context, use the delivery data directly
    if ($defaultCountryId == 1 && isset($delivery) && $delivery && isset($delivery->origin_country_id)) {
        $defaultCountryId = $delivery->origin_country_id;
    }
    // PRIORITY 3: For edit mode, try to get country ID from waybill delivery data (only if no miscellaneous data)
    elseif ($defaultCountryId == 1 && isset($waybill['delivery_id']) && !empty($waybill['delivery_id'])) {
        $delData = KIT_Deliveries::get_delivery($waybill['delivery_id']);
        if ($delData && isset($delData->origin_country_id)) {
            $defaultCountryId = $delData->origin_country_id;
        }
    } elseif (isset($waybillFromStats['country_id'])) {
        $defaultCountryId = $waybillFromStats['country_id'];
    } elseif (isset($customer) && isset($customer->country_id)) {
        $defaultCountryId = $customer->country_id;
    } elseif (isset($customer) && $customer && is_array($customer)) {
        $defaultCountryId = $customer['country_id'];
    }

    // Auto-detect context for enhanced behavior - routes need all countries
    $options = [];
    if (isset($_GET['page']) && in_array($_GET['page'], ['route-create'])) {
        $options = [
            'show_all_countries' => true,
            'show_inactive_indicators' => true
        ];
    }
    echo KIT_Deliveries::selectAllCountries('origin_country', 'origin_country_select', $defaultCountryId, "required", 'origin', $options);
    ?>
</div>
<div>
    <label for="origin_city_select" class="<?= KIT_Commons::labelClass() ?>">Origin City</label>
    <?php
    // Determine default city ID - prioritize delivery form data, then existing waybill delivery city, then customer city, then default to 1
    $defaultCityId = ($origin_city_id) ? $origin_city_id : 1;
    
    // Debug: Check what values we have
    echo "<!-- DEBUG: origin_city_id = " . $origin_city_id . " -->";
    echo "<!-- DEBUG: defaultCityId = " . $defaultCityId . " -->";
    echo "<!-- DEBUG: waybill miscellaneous structure = " . print_r($waybill['miscellaneous'] ?? 'NOT SET', true) . " -->";
    
    // PRIORITY 1: Use waybill's saved miscellaneous data (already set above from $origin_city_id)
    
    // PRIORITY 2: For delivery form context, we don't have origin_city_id stored, so default to first city of the country
    // This is a limitation of the current system design - origin cities are not stored per delivery
    // Only override if no saved city data exists
    if ($defaultCityId == 1 && isset($waybill['delivery_id']) && !empty($waybill['delivery_id'])) {
        $delData = KIT_Deliveries::get_delivery($waybill['delivery_id']);
        // Note: origin_city_id doesn't exist in deliveries table, so we'll use default
        if ($delData && isset($delData->origin_country_id)) {
            $defaultCityId = 1; // Default to first city of the origin country
        }
    }

    // Note: We intentionally removed the branch that attempted to resolve a city
    // from origin_country_id, because it incorrectly passed a country_id to
    // getCityData (which expects a city_id). This caused the selector to fail
    // to highlight the intended default (e.g., Johannesburg with id=1).

    echo KIT_Deliveries::selectAllCitiesByCountry('origin_city', 'origin_city_select', $defaultCountryId, $defaultCityId);
    ?>
</div>
<input type="hidden" id="origin_country_initial" value="<?= esc_attr($defaultCountryId); ?>">
<input type="hidden" id="origin_city_initial" value="<?= esc_attr($origin_city_id ?: ''); ?>">