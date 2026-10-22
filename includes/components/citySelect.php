<?php if (!defined('ABSPATH')) { exit; } ?>
<div id="waybill-form-city">
  <?php
  $selectedCountry = 1;
  $cities = KIT_Deliveries::get_Cities_forCountry($selectedCountry);

  ?>
  <?php echo KIT_Commons::Linput([
      'no_label' => true,
      'label' => '',
      'name' => 'destination_city',
      'id' => 'destination_city',
      'type' => 'text',
      'value' => $is_existing_customer ? $customer->destination_city : 'sdfsadf',
      'preset' => '',
      'class' => 'w-full px-3 py-2 border border-gray-300 rounded-md',
  ]); ?>
</div>