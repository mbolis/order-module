<?php if($success) { ?>
  <div class="updated">
    <p><?php echo $success; ?></p>
  </div>
<?php } elseif($error) { ?>
  <div class="error">
    <p><?php echo $error; ?></p>
  </div>
<?php } ?>
<div class="wrap">
  <div id="col-container">
    <div id="col-left">
      <h2>Ordini</h2>
          <ul class="om-order-list">
        <?php if ($current_order) { ?>
            <li class="om-order-select om-order-current postbox" data-om-order-id="<?php echo $current_order['id']; ?>">
              <h3>Ordine corrente</h3>
              <div class="inside">
                <span class="om-date-info"><strong>Data apertura:</strong> <?php echo date('d/m/Y H:i', strtotime($current_order['dt_apertura'])); ?></span><br>
                <span class="om-date-info"><strong>Data chiusura:</strong> <?php echo date('d/m/Y H:i', strtotime($current_order['dt_chiusura'])); ?></span>
              </div>
            </li>
        <?php } else { ?>
            <li class="postbox">
                <h3>Nessun ordine attivo</h3>
                <a href="<?php echo admin_url('admin.php?page=om-new-order'); ?>" class="om-new-order" title="Apri un nuovo ordine">Nuovo ordine</a>
            </li>
        <?php } ?>
        <?php if (count($orders)) { ?>
            <li><h3>Ordini Precedenti</h3></li>
          <?php foreach ($orders as $order) { ?>
            <li class="om-order-select postbox" data-om-order-id="<?php echo $order['id']; ?>">
              <div class="inside">
                <span class="om-date-info"><strong>Data apertura:</strong> <?php echo date('d/m/Y H:i', strtotime($order['dt_apertura'])); ?></span><br>
                <span class="om-date-info"><strong>Data chiusura:</strong> <?php echo date('d/m/Y H:i', strtotime($order['dt_chiusura'])); ?></span>
              </div>
            </li>
          <?php } ?>
        <?php } ?>
          </ul>
    </div>
    <div id="col-right">
      <div class="inside">
        <form method="POST" class="om-info-area">
          <input type="hidden" id="om_id_ordine" name="id_ordine">
          <input type="submit" id="btn_download_report" class="om-order-info-button" name="download_report" value="Scarica Excel">
          <input type="submit" id="btn_delete_order" class="om-order-info-button" name="delete_order" value="Elimina Ordine">
        </form>
      </div>
    </div>
  </div>
</div>
<script>
  (function($) {
    $('.updated,.error').on('click', function() {
      $(this).remove();
    });

    $('.om-order-select').bind('click', function() {
      $('.om-order-select').removeClass('om-order-selected-row');
      var orderId = 0|$(this).addClass('om-order-selected-row').data('om-order-id');
      $('#om_id_ordine').val(orderId);
      $('.om-info-area').show().css('margin-top', this.offsetTop);
      if ($(this).is('.om-order-current')) {
        $('#btn_delete_order').hide();
      } else {
        $('#btn_delete_order').show();
      }
    });

    $('#btn_delete_order').bind('click', function(e) {
      if (!confirm('Si desidera realmente eliminare definitivamente i dati di questo ordine?')) {
        e.preventDefault();
        return false;
      }
    });
  }(jQuery));
</script>
