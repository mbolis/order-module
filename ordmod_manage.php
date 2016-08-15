<?php if($success) { ?>
  <div class="updated">
    <p><?php echo $success; ?></p>
  </div>
<?php } ?>
<div class="wrap">
  <div id="col-container">
    <div id="col-left">
      <h2>Ordini</h2>
          <ul class="om-order-list">
        <?php if ($current_order) { ?>
            <li class="om-order-select postbox">
              <h3>Ordine corrente</h3>
              <div class="inside">
                <span class="om-date-info"><strong>Data apertura:</strong> <?php echo date('d/m/Y H:i', strtotime($current_order->dt_apertura)); ?></span><br>
                <span class="om-date-info"><strong>Data chiusura:</strong> <?php echo date('d/m/Y H:i', strtotime($current_order->dt_chiusura)); ?></span>
              </div>
            </li>
        <?php } else { ?>
            <li class="postbox">
                <h3>Nessun ordine attivo</h3>
                <a href="<?php echo admin_url('admin.php?page=om-new-order'); ?>" class="om-new-order" title="Apri un nuovo ordine">Nuovo ordine</a>
            </li>
        <?php } ?>  
        <?php if ($last_order) { ?>
            <li class="om-order-select postbox">
              <h3>Ultimo ordine</h3>
              <div class="inside">
                <span class="om-date-info"><strong>Data apertura:</strong> <?php echo date('d/m/Y H:i', strtotime($last_order->dt_apertura)); ?></span><br>
                <span class="om-date-info"><strong>Data chiusura:</strong> <?php echo date('d/m/Y H:i', strtotime($last_order->dt_chiusura)); ?></span>
              </div>
            </li>
        <?php } ?>
          </ul>
    </div>
  </div>
</div>
<script>
  (function($) {
    $('.updated,.error').on('click', function() {
      $(this).remove();
    });
  }(jQuery));
</script>
