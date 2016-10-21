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
          <?php if ($ordine['id']) { ?>
            <input type="hidden" name="ordine[id]" value="<?php echo $ordine['id']; ?>">
          <?php } ?>
          
          <label for="om_form_open_date" class="om-form-label">Data di Apertura:</label>
          <input type="text" id="om_form_open_date" name="ordine[dt_apertura]" <?php if($error_dta) { ?>class="om-error"<?php } ?> value="<?php echo $dt_apertura; ?>">
          <label for="om_form_open_date" class="om-form-label">Data di Chiusura:</label>
          <input type="text" id="om_form_close_date" name="ordine[dt_chiusura]" <?php if($error_dtc) { ?>class="om-error"<?php } ?> value="<?php echo $dt_chiusura; ?>">
          <div class="om-error-message"><?php echo $error_dta; ?></div>
          <div class="om-error-message"><?php echo $error_dtc; ?></div>
        </fieldset>
        <?php
          $typologies = preg_split('/\\s*,\\s*/', trim(get_option('om_product_typologies')));
          $p_i = 0;
          foreach ($typologies as $typology) {
            $typology_attr = htmlspecialchars($typology);
            $typology_html = htmlentities($typology);
            echo "<h3>$typology_html</h3>";

            $products = $all_products[$typology];
            $typology_errors = $errors['prodotti'][$typology];
            for ($i=0; $i<count($products); $i++) {
              $product = $products[$i];
              $error = $typology_errors[$i]; 

              $name_path = "prodotti[$typology_attr][$i]";
          ?>

          <fieldset class="om-form-row">
            <input type="hidden" name="<?php echo $name_path; ?>[id]" value="<?php echo $product['id']; ?>" />
            <input type="hidden" name="<?php echo $name_path; ?>[tipologia]" value="<?php echo htmlspecialchars($product['tipologia']); ?>" />
            <input type="hidden" name="<?php echo $name_path; ?>[nome]" value="<?php echo htmlspecialchars($product['nome']); ?>">
            <input type="hidden" name="<?php echo $name_path; ?>[unita_misura]" value="<?php echo htmlspecialchars($product['unita_misura']); ?>">

            <input type="checkbox" name="<?php echo $name_path; ?>[attivo]" id="om_form_chk_<?php echo $p_i; ?>" value="1" <?php if ($product['attivo']) { echo 'checked'; } ?> />
            <label for="om_form_chk_<?php echo $p_i; ?>" class="om-product-name small"><?php echo htmlentities($product['nome']); ?></label>
            <input type="text" name="<?php echo $name_path; ?>[prezzo]" class="smaller<?php if($error) { ?> om-error<?php } ?>" value="<?php echo $product['prezzo']; ?>" />
            <span style="display:inline-block;min-width:120px">&euro;/<?php echo htmlentities($product['unita_misura']); ?></span>
            <input type="checkbox" name="<?php echo $name_path; ?>[extra]" id="om_form_extra_chk_<?php echo $p_i; ?>" value="1" <?php if ($product['extra']) { echo 'checked'; } ?> />
            <label for="om_form_extra_chk_<?php echo $p_i; ?>" class="smallest">Extra:</label>
            <input type="text" name="<?php echo $name_path; ?>[extra_testo]" class="large" value="<?php echo htmlspecialchars($product['extra_testo']); ?>" />

            <?php if ($error) { ?><br><span class="om-error-message"><?php echo $error; ?></span><?php } ?>
          </fieldset>

        <?php
              $p_i++;
            }
          }
        ?>
        <input type="submit" class="button" name="submit" value="<?php echo $ordine['id'] ? 'Conferma' : 'Avvia'; ?> Ordine" />
      </form>
    </div>
  </div>
</div>
<script>
(function($) {
  document.getElementById('om_form_new_order').onkeypress = checkEnter;
  function checkEnter(e){
    e = e || event;
    var txtArea = /textarea/i.test((e.target || e.srcElement).tagName);
    return txtArea || (e.keyCode || e.which || e.charCode || 0) !== 13;
  }

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
    now.setMinutes(minutes - pastQuarter);
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
