<div class="bg-purple-100 mb-6">
<tr>
    <th><label for="destination_country_select">Destination Country</label></th>
    <td>
        <?php if (isset($waybillToStats['country_id'])) : ?>
            <?php echo KIT_Deliveries::selectAllCountries('destination_country', 'destination_country_select', $waybillToStats['country_id'], $required = "required", 'destination'); ?>
        <?php else: ?>
            <?php echo KIT_Deliveries::selectAllCountries('destination_country', 'destination_country_select', "", $required = "required", 'destination'); ?>
        <?php endif; ?>
    </td>
</tr>
<tr>
    <th><label for="destination_city">Destination City</label></th>
    <td>
        <?php if (isset($waybillToStats['country_id'])) : ?>

            <?php
            $delData = KIT_Deliveries::get_delivery($waybill['delivery_id']);

            $cityData = KIT_Deliveries::getCityData( $delData->destination_country_id);
            ?>
            
            <?php  echo KIT_Deliveries::selectAllCitiesByCountry('destination_city', 'destination_city_select', $waybillToStats['country_id'], $cityData->id); ?>
        <?php else: ?>
            <?php echo KIT_Deliveries::selectAllCitiesByCountry('destination_city', 'destination_city_select', 3, $required = 'required', ''); ?>
        <?php endif; ?>
    </td>
</tr>

</div>