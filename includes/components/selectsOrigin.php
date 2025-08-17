<?php if (!defined('ABSPATH')) { exit; } ?>
<div>
    <tr>
        <th><label for="origin_country_select" class="<?= KIT_Commons::labelClass() ?>">Origin Country</label></th>
        <td>
            <?php if (isset($waybillFromStats['country_id'])) : ?>
                <?php echo KIT_Deliveries::selectAllCountries('origin_country', 'origin_country_select', $waybillFromStats['country_id'], $required = "required", 'origin'); ?>
            <?php else: ?>
                <?php echo KIT_Deliveries::selectAllCountries('origin_country', 'origin_country_select', 1, "required", 'origin'); ?>
            <?php endif; ?>
        </td>
    </tr>
    <tr>
        <th><label for="origin_city" class="<?= KIT_Commons::labelClass() ?>">Origin City</label></th>
        <td>
            <?php if (isset($waybillFromStats['country_id'])) : ?>
                <?php
                $delData = KIT_Deliveries::get_delivery($waybill['delivery_id']);
                $cityData = KIT_Deliveries::getCityData($delData->origin_country_id);
                ?>
                <?php echo KIT_Deliveries::selectAllCitiesByCountry('origin_city', 'origin_city_select', $waybillFromStats['country_id'], $cityData->id); ?>
            <?php else: ?>
                <?php echo KIT_Deliveries::selectAllCitiesByCountry('origin_city', 'origin_city_select', 1, 1); ?>
            <?php endif; ?>
        </td>
    </tr>
</div>