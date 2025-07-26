<div class="">
    <tr>
        <th><label for="destination_country_select" class="<?= KIT_Commons::labelClass() ?>">Destination Country</label></th>
        <td>
            <?php
            if (isset($waybillToStats['country_id']) || isset($routeData->destination_country_id)) {
                $destinationCountryId = isset($waybillToStats['country_id'])
                    ? $waybillToStats['country_id']
                    : (isset($routeData->destination_country_id)
                        ? $routeData->destination_country_id
                        : null);

                echo KIT_Deliveries::selectAllCountries('destination_country', 'destination_country_select', $destinationCountryId, "required", 'destination');
            } else {
                echo KIT_Deliveries::selectAllCountries('destination_country', 'destination_country_select', "", "required", 'destination');
            }
            ?>
        </td>
    </tr>
    <tr>
        <th><label for="destination_city" class="<?= KIT_Commons::labelClass() ?>">Destination City</label></th>
        <td>
            <?php if (isset($waybillToStats['country_id'])) :
                $delData = KIT_Deliveries::get_delivery($waybill['delivery_id']);
                $cityData = KIT_Deliveries::getCityData($delData->destination_country_id);
             echo KIT_Deliveries::selectAllCitiesByCountry('destination_city', 'destination_city_select', $waybillToStats['country_id'], $delData->destination_city_id); ?>
            <?php else: ?>
                <?php echo KIT_Deliveries::selectAllCitiesByCountry('destination_city', 'destination_city_select', 3, $required = 'required', ''); ?>
            <?php endif; ?>
        </td>
    </tr>

</div>