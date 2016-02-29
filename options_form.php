<?php if($success) { ?>
  <div class="updated">
    <p>Opzioni aggiornate.</p>
  </div>
<?php } elseif ($errors) { ?>
  <div class="error">
    <p>Sono presenti errori.</p>
  </div>
<?php } ?>
<div class="wrap">
  <h2>Opzioni</h2>
  <div class="postbox">
    <div class="inside">
      <form method="POST" autocomplete="off">
        <fieldset class="om-form-row">
          <label for="om_main_form_page">Pagina del modulo d'ordine</label>
          <?php $error = $errors['main_form_page']; ?>
          <input type="text" id="om_main_form_page" name="main_form_page" <?php if($error) { ?>class="om-error"<?php } ?> value="<?php echo $main_form_page; ?>" />
          <?php if($error) { ?>
            <span class="om-error-message"><?php echo $error; ?></span>
          <?php } ?>
        </fieldset>
        <fieldset class="om-form-row">
          <label for="om_main_form_link">Link al modulo d'ordine</label>
          <input type="text" readonly id="om_main_form_link" value="<?php echo get_site_url() . '/' . $main_form_page; ?>" />
        </fieldset>
        <fieldset class="om-form-row">
          <label for="om_product_typologies">Tipologie di prodotto </label>
          <?php $error = $errors['product_typologies']; ?>
          <input type="text" id="om_product_typologies" name="product_typologies" <?php if($error) { ?>class="om-error"<?php } ?> value="<?php echo $product_typologies; ?>" />
          <div style="margin-left:1em;font-size:80%">
            Elencare le tipologie disponibili per categorizzare i prodotti, separate da una virgola.<br>
            Ogni tipologia verrà visualizzata in una pagina a s&eacute; stante del modulo d&#039; ordine.<br>
            L&#039;ordine delle pagine del modulo &egrave; definito dall&#039; ordinamento di questa lista.
          </div>
          <?php if($error) { ?>
            <span class="om-error-message"><?php echo $error; ?></span>
          <?php } ?>
        </fieldset>
        <input type="submit" class="button" name="submit" value="Salva" />
      </form>
    </div>
  </div>
</div>
<script>
  (function($) {
    $('.updated,.error').on('click', function() {
      $(this).remove();
    });
    var $mainFormLink = $('#om_main_form_link');
    $('#om_main_form_page').on('input', function() {
      $mainFormLink.val('<?php echo get_site_url() ?>/' + this.value);
    });
  }(jQuery));
</script>
