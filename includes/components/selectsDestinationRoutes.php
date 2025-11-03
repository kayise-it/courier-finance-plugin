<?php if (!defined('ABSPATH')) {
    exit;
} ?>
<div>
    <label for="destination_country_select" class="<?= KIT_Commons::labelClass() ?>">Destination Country</label>
    <?php
    $destinationCountryId = 1; // Default to South Africa

    // For route management, get all countries (active and inactive)
    // For edit mode, try to get destination country ID from route data
    if (isset($routeData) && $routeData !== null) {
        $destinationCountryId = kit_Commons::verifyint($routeData->destination_country_id);
    }

    // ROUTE-SPECIFIC: Use rule system to show ALL countries for route management
    echo KIT_Deliveries::selectAllCountries('destination_country', 'destination_country_select', $destinationCountryId, "required", 'destination', [
        'show_all_countries' => true,
        'show_inactive_indicators' => true
    ]);
    ?>
</div>
<div>
    <label for="destination_city_select" class="<?= KIT_Commons::labelClass() ?>">Destination City</label>
    <?php
    $destinationCityId = 1; // Default city

    // For edit mode, try to get destination city ID from route data
    if (isset($routeData) && $routeData !== null) {
        // For routes, try to get the city ID
        // This is a placeholder for route-specific logic
    }

    echo KIT_Deliveries::selectAllCitiesByCountry('destination_city', 'destination_city_select', $destinationCountryId, $destinationCityId);
    ?>
</div>