<?php if($errors) { ?>
  <div class="error">
    <p>Sono presenti errori.</p>
  </div>
<?php } ?>
<div class="wrap">
  <h2>Nuovo Ordine</h2>
  <div class="postbox">
    <div class="inside">
      <form id="om_form_new_order" method="post">
        <fieldset class="om-form-dates">
          <?php $error_dta = $errors['dt_apertura']; ?>
          <?php $error_dtc = $errors['dt_chiusura']; ?>
          <label for="om_form_open_date" class="om-form-label">Data di Apertura:</label>
          <input type="text" id="om_form_open_date" name="ordine[dt_apertura]" <?php if($error_dta) { ?>class="om-error"<?php } ?> value="<?php echo $dt_apertura; ?>" />
          <label for="om_form_open_date" class="om-form-label">Data di Chiusura:</label>
          <input type="text" id="om_form_close_date" name="ordine[dt_chiusura]" <?php if($error_dtc) { ?>class="om-error"<?php } ?> value="<?php echo $dt_chiusura; ?>" />
          <div class="om-error-message"><?php echo $error_dta; ?></div>
          <div class="om-error-message"><?php echo $error_dtc; ?></div>
        </fieldset>
        <?php for ($i=0; $i<count($all_products); $i++) {
            $product = $all_products[$i];
            $error = $errors['prodotti'][$i]; ?>

          <fieldset class="om-form-row">
            <input type="hidden" name="prodotti[<?php echo $i; ?>][id]" value="<?php echo $product['id']; ?>" />
            <input type="checkbox" id="om_form_chk_<?php echo $i; ?>" name="prodotti[<?php echo $i; ?>][attivo]" value="1" <?php if ($product['attivo']) { echo 'checked'; } ?> />
            <label for="om_form_chk_<?php echo $i; ?>" class="om-product-name small"><?php echo $product['nome']; ?></label>
            <input type="hidden" name="prodotti[<?php echo $i; ?>][nome]" value="<?php echo $product['nome']; ?>">
            <input type="text" id="om_form_price_<?php echo $i; ?>" name="prodotti[<?php echo $i; ?>][prezzo]" class="smaller<?php if($error) { ?> om-error<?php } ?>" value="<?php echo $product['prezzo']; ?>" />
            <input type="hidden" name="prodotti[<?php echo $i; ?>][unita_misura]" value="<?php echo $product['unita_misura']; ?>">
            &euro;/<?php echo $product['unita_misura']; ?>
            <span class="om-error-message"><?php echo $error; ?></span>
          </fieldset>

        <?php } ?>
        <input type="submit" class="button" name="submit" value="Avvia Ordine" />
      </form>
    </div>
  </div>
</div>
<script>
(function($) {
  $.datepicker.setDefaults({
    showOn : 'both'
  });
  $.timepicker.setDefaults({
    stepMinute : 15,
    oneLine : true,
    separator : ' '
  });

  var now = new Date;
  var minutes = now.getMinutes();
  var pastQuarter = minutes % 15;
  if (pastQuarter != 0) {
    console.log(minutes, pastQuarter)
    now.setMinutes(minutes + 15 - pastQuarter);
  }

  var closeDatePicker = $('#om_form_close_date').datetimepicker({
      minDateTime : now
  });
  var openDatePicker = $('#om_form_open_date')
    .datetimepicker({
      minDateTime : now,
      onSelect : function(dateTime) {
        dateTime = $.datepicker.parseDateTime('dd/mm/yy', 'HH:mm', dateTime);
        closeDatePicker.datetimepicker('option', 'minDateTime', dateTime);

        if (closeDatePicker.datetimepicker('getDate').getTime() < dateTime.getTime()) {
          closeDatePicker.datetimepicker('setDate', null);
        }
      }
    });
    if (!openDatePicker.val()) {
      openDatePicker.datetimepicker('setDate', now);
    }
}(jQuery));
</script>
