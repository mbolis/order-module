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
          <label for="om_main_form_splash">Frontespizio del modulo d'ordine</label>
          <?php $error = $errors['main_form_splash']; ?>
          <select id="om_main_form_splash" class="large" name="main_form_splash" <?php if($error) { ?>class="om-error"<?php } ?>>
            <option value=''></option>
            <?php foreach ($all_pages as $page) {?>
              <option value="<?php echo $page->ID; ?>"<?php if ($page->ID == $main_form_splash) { ?> selected<?php } ?>>
                <?php echo $page->post_title; ?> (agg: <?php echo date('d/m/y H:i', strtotime($page->post_modified)); ?>)
              </option>
            <?php } ?>
          </select>
          <?php if($error) { ?>
            <span class="om-error-message"><?php echo $error; ?></span>
          <?php } ?>
        </fieldset>
        <fieldset class="om-form-row">
          <label for="om_product_typologies">Tipologie di prodotto</label>
          <?php $error = $errors['product_typologies']; ?>
          <input type="text" id="om_product_typologies" name="product_typologies" <?php if($error) { ?>class="om-error"<?php } ?> value="<?php echo $product_typologies; ?>" />
          <div style="margin:0 0 1em 1em;font-size:80%">
            Elencare le tipologie disponibili per categorizzare i prodotti, separate da una virgola.<br>
            Ogni tipologia verr&agrave; visualizzata in una pagina a s&eacute; stante del modulo d' ordine.<br>
            L'ordine delle pagine del modulo &egrave; definito dall' ordinamento di questa lista.
          </div>
          <?php if($error) { ?>
            <span class="om-error-message"><?php echo $error; ?></span>
          <?php } ?>
        </fieldset>
        <fieldset class="om-form-row">
          <label for="om_product_units">Unit&agrave; di misura</label>
          <?php $error = $errors['product_units']; ?>
          <input type="text" id="om_product_units" name="product_units" <?php if($error) { ?>class="om-error"<?php } ?> value="<?php echo $product_units; ?>" />
          <div style="margin:0 0 1em 1em;font-size:80%">
            Elencare le unit&agrave; di misura usate per i prodotti, separate da una virgola.<br>
            Per ogni unit&agrave; di misura è possibile specificare il singolare e il plurale separati da una barra ("/").<br>
            Queste unit&agrave; di misura saranno disponibili per la selezione nella schermata dei Prodotti.
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
