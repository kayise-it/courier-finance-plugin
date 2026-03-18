<?php if (!defined('ABSPATH')) {
    exit;
}

$destination_city_id = 0;
$destination_country_id = 0;

// For waybill edit/view: prefer saved route from miscellaneous.others (source of truth from DB)
if (isset($waybill['miscellaneous'])) {
    $raw_misc = $waybill['miscellaneous'];
    $misc = is_array($raw_misc) ? $raw_misc : maybe_unserialize($raw_misc);
    if (is_array($misc) && isset($misc['others']) && is_array($misc['others'])) {
        $from_others = [
            'destination_city_id' => intval($misc['others']['destination_city_id'] ?? 0),
            'destination_country_id' => intval($misc['others']['destination_country_id'] ?? 0),
        ];
        if ($from_others['destination_country_id'] > 0 || $from_others['destination_city_id'] > 0) {
            $destination_country_id = $from_others['destination_country_id'];
            $destination_city_id = $from_others['destination_city_id'];
        }
    }
}

// Fallback: waybill row city_id (e.g. when miscellaneous not yet saved)
if (!$destination_country_id && !$destination_city_id && isset($waybill) && is_array($waybill) && !empty($waybill['city_id'])) {
    $destination_city_id = intval($waybill['city_id']);
}

// Check for delivery object (used on delivery forms) only if not already set from waybill
if (!$destination_city_id && !$destination_country_id && isset($delivery) && is_object($delivery)) {
    $destination_city_id = isset($delivery->destination_city_id) ? intval($delivery->destination_city_id) : 0;
    $destination_country_id = isset($delivery->destination_country_id) ? intval($delivery->destination_country_id) : 0;
}

// Fallback to top-level waybill values (e.g. from delivery direction JOIN)
if (!$destination_city_id && isset($waybill['destination_city_id'])) {
    $destination_city_id = intval($waybill['destination_city_id']);
} elseif (!$destination_city_id && isset($waybill['destination_city'])) {
    $destination_city_id = intval($waybill['destination_city']);
}

if (!$destination_country_id && isset($waybill['destination_country_id'])) {
    $destination_country_id = intval($waybill['destination_country_id']);
} elseif (!$destination_country_id && isset($waybill['destination_country'])) {
    $destination_country_id = intval($waybill['destination_country']);
}

$defaultCountryId = $destination_country_id ? $destination_country_id : 1;
?>
<div>
    <label for="destination_country_select" class="<?= KIT_Commons::labelClass() ?>">Destination Country</label>
    <?php
    // Auto-detect context for enhanced behavior - routes need all countries
    $options = [];
    if (isset($_GET['page']) && in_array($_GET['page'], ['route-create'])) {
        $options = [
            'show_all_countries' => true,
            'show_inactive_indicators' => true
        ];
    }

    echo KIT_Deliveries::selectAllCountries('destination_country', 'destination_country_select', $defaultCountryId, "required", 'destination', $options);
    ?>
</div>
<div>
    <label for="destination_city_select" class="<?= KIT_Commons::labelClass() ?>">Destination City</label>
    <?php
    $defaultCityId = $destination_city_id ? $destination_city_id : 1;

    echo KIT_Deliveries::selectAllCitiesByCountry('destination_city', 'destination_city_select', $destination_country_id, $defaultCityId);
    ?>
</div>
<input type="hidden" id="destination_country_initial" value="<?= esc_attr($defaultCountryId); ?>">
<input type="hidden" id="destination_city_initial" value="<?= esc_attr($destination_city_id ?: ''); ?>">