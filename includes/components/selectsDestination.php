<?php if (!defined('ABSPATH')) {
    exit;
}
$destination_city_id = ($waybill['miscellaneous']['others']['destination_city_id']) ?? 0;
$destination_country_id = ($waybill['miscellaneous']['others']['destination_country_id']) ?? 0;

$defaultCountryId = ($destination_country_id) ? $destination_country_id : 1;


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
    $defaultCityId = ($destination_city_id) ? $destination_city_id : 1;


    echo KIT_Deliveries::selectAllCitiesByCountry('destination_city', 'destination_city_select', $destination_country_id, $defaultCityId);
    ?>
</div>