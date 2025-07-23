<!-- File location: includes/components/countriesSelect.php -->
<div class="<?= KIT_Commons::yspacingClass(); ?>" id="waybill-form-country">
  <?php $delivery_id = (isset($delivery_id)) ? $delivery_id : ''; ?>
  <label for="stepDestinationSelect" class="block text-xs font-medium">Destination Country</label>
  <?php echo KIT_Deliveries::CountrySelect('destination_country', 'stepDestinationSelect', $delivery_id);  ?>
</div>
