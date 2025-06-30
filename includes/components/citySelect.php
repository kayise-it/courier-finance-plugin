<!-- File location: includes/components/countriesSelect.php -->
<div id="waybill-form-city">
<?php 
$selectedCountry = 1;
$cities = KIT_Deliveries::get_Cities_forCountry($selectedCountry); 

?>
  <input type="text" id="destination_city" name="destination_city"
    class="w-full px-3 py-2 border border-gray-300 rounded-md"
    value="<?php echo esc_attr($is_existing_customer ? $customer->destination_city : 'sdfsadf'); ?>">
</div>