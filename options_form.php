<?php if($success) { ?>
	<div class="updated">
		<p>Opzioni aggiornate.</p>
	</div>
<?php } elseif ($errors) { ?>
	<div class="error">
		<p>Sono presenti errori.</p>
	</div>
<?php } ?>
<?php if ($hazard_op == 'reset_all') { ?>
	<div class="updated">
		<p>Tutti i dati del plugin sono stati azzerati. Buona fortuna!</p>
	</div>
<?php } ?>
<div class="wrap">
	<h2>Opzioni</h2>
	<div class="postbox">
		<div class="inside">
			<form id="om_options_form" method="POST" accept-charset="utf-8" autocomplete="off">
				<fieldset class="om-form-row">
					<label for="om_main_form_page">Pagina del modulo d'ordine</label><br>
					<?php $error = $errors['main_form_page']; ?>
					<input type="text" id="om_main_form_page" name="main_form_page" <?php if($error) { ?>class="om-error"<?php } ?> value="<?php echo $main_form_page; ?>" />
					<?php if($error) { ?>
						<span class="om-error-message"><?php echo $error; ?></span>
					<?php } ?>
				</fieldset>
				<fieldset class="om-form-row">
					<label for="om_main_form_link">Link al modulo d'ordine</label><br>
					<input type="text" readonly id="om_main_form_link" value="<?php echo get_site_url() . '/' . $main_form_page; ?>?omform=yes" />
				</fieldset>
				<fieldset class="om-form-row">
					<label for="om_main_form_splash">Frontespizio del modulo d'ordine</label><br>
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
					<label for="om_background_image">Immagine di sfondo</label><br>
					<input type="hidden" id="om_background_image" name="background_image" value="<?php echo $background_image; ?>" />
					<button type="button" id="btn_select_background_image">Seleziona</button><br><br>
					<img id="img_background_image" style="max-width:800px" src="<?php echo $background_image; ?>">
				</fieldset>
				<fieldset class="om-form-row">
					<label for="om_product_typologies">Tipologie di prodotto</label><br>
					<?php $error = $errors['product_typologies']; ?>
					<input type="text" id="om_product_typologies" name="product_typologies" <?php if($error) { ?>class="om-error"<?php } ?> value="<?php echo htmlentities($product_typologies); ?>" />
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
					<label for="om_product_units">Unit&agrave; di misura</label><br>
					<?php $error = $errors['product_units']; ?>
					<input type="text" id="om_product_units" name="product_units" <?php if($error) { ?>class="om-error"<?php } ?> value="<?php echo htmlentities($product_units); ?>" />
					<div style="margin:0 0 1em 1em;font-size:80%">
						Elencare le unit&agrave; di misura usate per i prodotti, separate da una virgola.<br>
						Per ogni unit&agrave; di misura è possibile specificare il singolare e il plurale separati da una barra ("/").<br>
						Queste unit&agrave; di misura saranno disponibili per la selezione nella schermata dei Prodotti.
					</div>
					<?php if($error) { ?>
						<span class="om-error-message"><?php echo $error; ?></span>
					<?php } ?>
				</fieldset>
				<fieldset class="om-form-row">
					<h3>Mail di notifica Ordine Effettuato</h3>
					<div style="margin:0 0 1em 1em;font-size:80%">
						Quando viene registrato correttamente un ordine, verr&agrave; inviato il seguente messaggio e-mail, assieme al riassunto dell'ordine in formato <em>Excel</em>.<br>
						Inserire di seguito l'oggetto e il testo del messaggio.
					</div>
					<label for="om_notification_mail_subject">Oggetto</label><br>
					<?php $error = $errors['notification_mail_subject']; ?>
					<input type="text" id="om_notification_mail_subject" name="notification_mail_subject" <?php if($error) { ?>class="om-error"<?php } ?> value="<?php echo $notification_mail_subject; ?>" />
					<?php if($error) { ?>
						<span class="om-error-message"><?php echo $error; ?></span>
					<?php } ?>
					<br>
					<label for="om_notification_mail_text">Testo</label><br>
					<?php $error = $errors['notification_mail_text']; ?>
					<textarea id="om_notification_mail_text" name="notification_mail_text" <?php if($error) { ?>class="om-error"<?php } ?>><?php echo $notification_mail_text; ?></textarea>
					<?php if($error) { ?>
						<span class="om-error-message"><?php echo $error; ?></span>
					<?php } ?>
				</fieldset>
				<fieldset class="om-form-row">
					<h3>Messaggi utente</h3>
					<label for="om_message_order_not_available">Ordine non disponibile</label><br>
					<?php $error = $errors['message_order_not_available']; ?>
					<textarea id="om_message_order_not_available" name="message_order_not_available" <?php if($error) { ?>class="om-error"<?php } ?>><?php echo $message_order_not_available; ?></textarea>
					<?php if($error) { ?>
						<span class="om-error-message"><?php echo $error; ?></span>
					<?php } ?>
					<br>
					<label for="om_message_order_is_closed">Ordine chiuso</label><br>
					<?php $error = $errors['message_order_is_closed']; ?>
					<textarea id="om_message_order_is_closed" name="message_order_is_closed" <?php if($error) { ?>class="om-error"<?php } ?>><?php echo $message_order_is_closed; ?></textarea>
					<?php if($error) { ?>
						<span class="om-error-message"><?php echo $error; ?></span>
					<?php } ?>
					<br>
					<label for="om_message_form_success">Ordine inviato con successo</label><br>
					<?php $error = $errors['message_form_success']; ?>
					<textarea id="om_message_form_success" name="message_form_success" <?php if($error) { ?>class="om-error"<?php } ?>><?php echo $message_form_success; ?></textarea>
					<?php if($error) { ?>
						<span class="om-error-message"><?php echo $error; ?></span>
					<?php } ?>
					<br>
					<label for="om_message_form_expired">Ordine scaduto / non pi&ugrave; disponibile</label><br>
					<?php $error = $errors['message_form_expired']; ?>
					<textarea id="om_message_form_expired" name="message_form_expired" <?php if($error) { ?>class="om-error"<?php } ?>><?php echo $message_form_expired; ?></textarea>
					<?php if($error) { ?>
						<span class="om-error-message"><?php echo $error; ?></span>
					<?php } ?>
				</fieldset>
				<input type="submit" class="button" name="submit" value="Salva" />
			</form>
			<div class="om-hazard-wrapper">
				<button type="button" id="om_hazard_toggle">Mostra le opzioni ad Alto Rischio</button>
				<div class="om-hazard-container">
					<form id="om_reset_all" method="POST">
						<input type="hidden" name="hazard_op" value="reset_all">
						<button>Azzera i dati salvati e le impostazioni</button>
						<em>Eliminer&agrave; tutti i dati in modo permanente!</em>
					</form>
					<form id="om_nuke_all" method="POST">
						<input type="hidden" name="hazard_op" value="nuke_all">
						<button>Disinstalla <strong>totalmente</strong> il plugin</button>
						<em>Eliminer&agrave; tutti i dati in modo permanente!</em>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	(function($) {
		document.getElementById('om_options_form').onkeypress = checkEnter;
		function checkEnter(e){
			e = e || event;
			var txtArea = /textarea/i.test((e.target || e.srcElement).tagName);
			return txtArea || (e.keyCode || e.which || e.charCode || 0) !== 13;
		}

		$('.updated,.error').on('click', function() {
			$(this).remove();
		});

		var $mainFormLink = $('#om_main_form_link');
		$('#om_main_form_page').on('input', function() {
			$mainFormLink.val('<?php echo get_site_url() ?>/' + this.value);
		});

		var $backgroundUrl = $('#om_background_image');
		var $backgroundUrlImage = $('#img_background_image');
		$('#btn_select_background_image').on('click', function(e) {
			e.preventDefault();
			var selectedImage, frame = wp.media.frames.om_background_image;
			if (!frame) {
				frame = wp.media.frames.om_background_image = wp.media({
					title : 'Immagine di Sfondo',
					multiple : false,
					library : {
						type : 'image'
					},
					button : {
						text : 'Usa l\'immagine selezionata'
					}
				});
				
				frame.on('close', selectImage);
				frame.on('select', selectImage);
				function selectImage() {
					var selection = frame.state().get('selection');
					if (selection) {
						selection.each(function(attachment) {
							var url = attachment.attributes.url;
							$backgroundUrl.val(url);
							$backgroundUrlImage.attr('src', url);
						});
					}
				}
			}

			frame.open();
		});

		$('#om_hazard_toggle').on('click', function() {
			var $hazardContainer = $('.om-hazard-container');
			if ($hazardContainer.is(':hidden')) {
				$hazardContainer.show();
				this.textContent = 'Nascondi le opzioni ad Alto Rischio';
			} else {
				$hazardContainer.hide();
				this.textContent = 'Mostra le opzioni ad Alto Rischio';
			}
		});
		$('.om-hazard-container form').on('submit', function(e) {
			if (confirm('Sei sicuro di voler cancellare TUTTI i dati\nrelativi a ordini, prodotti e opzioni del plugin?')) {
				if (confirm('Sei DAVVERO sicuro SICURO???')) {
					return true;
				}
			}
			e.preventDefault();
			return false;
		});
	}(jQuery));
</script>
