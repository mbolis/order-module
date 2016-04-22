<?php
function om_install() {
  global $wpdb;
  $prefix = $wpdb->prefix . 'om_';
  $order_table = $prefix . 'ordine';
  $order_product_table = $prefix . 'prodotto_ordine';
  $order_client_table = $prefix . 'ordine_cliente';
  $order_row_table = $prefix . 'riga_ordine';
  $product_table = $prefix . 'prodotto';
  $gas_table = $prefix . 'gas';

  $charset_collate = $wpdb->get_charset_collate();

  // Create order table
  $order_sql = "CREATE TABLE $order_table (
    id int(11) NOT NULL AUTO_INCREMENT,
    dt_apertura datetime NOT NULL,
    dt_chiusura datetime NOT NULL,
    dt_modifica datetime,
    dt_accesso datetime,
    PRIMARY KEY  (id)
  ) $charset_collate;";
  
  // Create order product table
  $order_product_sql = "CREATE TABLE $order_product_table (
    id int(11) NOT NULL AUTO_INCREMENT,
    id_ordine int(11) NOT NULL,
    id_prodotto int(11) NOT NULL,
    prezzo decimal(11,3) NOT NULL,
    PRIMARY KEY  (id)
  ) $charset_collate;";

  // Create client order table
  $order_client_sql = "CREATE TABLE $order_client_table (
    id int(11) NOT NULL AUTO_INCREMENT,
    id_ordine int(11) NOT NULL,
    username varchar(60) NOT NULL,
    progressivo int(3) NOT NULL,
    nome varchar(255) NOT NULL,
    id_gas int(11),
    area varchar(255) NOT NULL,
    indirizzo text NOT NULL,
    telefono varchar(30),
    note text,
    dt_modifica datetime,
    dt_accesso datetime,
    PRIMARY KEY  (id)
  ) $charset_collate;";

  // Create order row table
  $order_row_sql = "CREATE TABLE $order_row_table (
    id int(11) NOT NULL AUTO_INCREMENT,
    id_ordine_cliente int(11) NOT NULL,
    id_prodotto_ordine int(11) NOT NULL,
    quantita decimal(11,3) NOT NULL,
    PRIMARY KEY  (id)
  ) $charset_collate;";

  // Create product table
  $product_sql = "CREATE TABLE $product_table (
    id int(11) NOT NULL AUTO_INCREMENT,
    nome varchar(255) NOT NULL,
    tipologia varchar(30) NOT NULL,
    unita_misura varchar(20) NOT NULL,
    unita_misura_plurale varchar(20) NOT NULL,
    provenienza text,
    pagina varchar(255),
    prezzo decimal(11,3),
    attivo bit,
    PRIMARY KEY  (id)
  ) $charset_collate;";

  // Create client contact table
  $gas_sql = "CREATE TABLE $gas_table (
    id int(11) NOT NULL AUTO_INCREMENT,
    nome varchar(255) NOT NULL,
    nome_contatto varchar(255),
    area varchar(255) NOT NULL,
    indirizzo text NOT NULL,
    telefono varchar(30),
    PRIMARY KEY  (id)
  ) $charset_collate;";
  
  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta(array(
    $order_sql,
    $order_product_sql,
    $order_client_sql,
    $order_row_sql,
    $product_sql,
    $gas_sql,
  ));
}

function om_has_active_order() {
  global $wpdb;
  $order_table = $wpdb->prefix . 'om_ordine';
  $open_orders = $wpdb->get_var("
    SELECT COUNT(*)
    FROM $order_table
    WHERE dt_chiusura > NOW()
  ");
  return $open_orders > 0;
}

function om_get_top_orders($depth=2) {
  global $wpdb;
  $order_table = $wpdb->prefix . 'om_ordine';
  return $wpdb->get_results("
    SELECT *
    FROM $order_table
    ORDER BY dt_chiusura DESC
    LIMIT $depth
  ");
}

function om_save_order($ordine, $prodotti) {
  global $wpdb;
  $order_table = $wpdb->prefix . 'om_ordine';
  if ($wpdb->insert($order_table, $ordine, '%s')) {
    $id_ordine = $wpdb->insert_id;

    $product_table = $wpdb->prefix . 'om_prodotto';
    $wpdb->query("UPDATE $product_table SET attivo=0");

    $product_order_table = $wpdb->prefix . 'om_prodotto_ordine';
    $inserted = 0;
    foreach ($prodotti as $prodotto) {
      // update prodotti with new default values
      $wpdb->update(
        $product_table,
        array(
          'prezzo' => $prodotto['prezzo'],
          'attivo' => 1
        ),
        array('id_prodotto' => $prodotto['id']),
        array(
          '%f',
          '%d'
        ),
        array('%d')
      );

      // insert actual order products
      $inserted += $wpdb->insert(
        $product_order_table,
        array(
          'id_ordine' => $id_ordine,
          'id_prodotto' => $prodotto['id'],
          'prezzo' => $prodotto['prezzo']
        ),
        array('%d', '%d', '%f')
      );
    }
    return $inserted;
  }
  return FALSE;
}

function om_get_gas_list($order_by='nome') {
  global $wpdb;
  $gas_table = $wpdb->prefix . 'om_gas';
  return $wpdb->get_results("
    SELECT id, nome, area, nome_contatto
    FROM $gas_table
    ORDER BY $order_by
  ");
}

function om_get_gas_data($order_by='nome') {
  global $wpdb;
  $gas_table = $wpdb->prefix . 'om_gas';
  return $wpdb->get_results("
    SELECT *
    FROM $gas_table
    ORDER BY $order_by
  ");
}

function om_delete_gas($id) {
  global $wpdb;
  $gas_table = $wpdb->prefix . 'om_gas';
  $deleted = $wpdb->delete($gas_table, array('id' => $id), '%d');
  return $deleted === 1;
}

function om_insert_gas($gas) {
  global $wpdb;
  $gas_table = $wpdb->prefix . 'om_gas';
  if ($wpdb->insert($gas_table, $gas, '%s')) {
    return $wpdb->insert_id;
  }
}

function om_update_gas($gas) {
  global $wpdb;
  $gas_table = $wpdb->prefix . 'om_gas';
  $id = (int) $gas['id'];
  unset($gas['id']);
  return $wpdb->update($gas_table, $gas, array('id' => $id), '%s');
}

function om_get_products_data($order_by='tipologia, nome') {
  global $wpdb;
  $product_table = $wpdb->prefix . 'om_prodotto';
  return $wpdb->get_results("
    SELECT *
    FROM $product_table
    ORDER BY $order_by
  ");
}

function om_update_products($to_update) {
  global $wpdb;
  $product_table = $wpdb->prefix . 'om_prodotto';
  $updated = 0;
  foreach ($to_update as $row) {
    $id = (int) $row['id'];
    unset($row['id']);
    if (!$row['id_pagina']) {
      unset($row['id_pagina']);
    }
    $updated += $wpdb->update($product_table, $row, array('id' => $id), array(
      'nome' => '%s',
      'tipologia' => '%s',
      'unita_misura' => '%s',
      'unita_misura_plurale' => '%s',
      'provenienza' => '%s',
      'id_pagina' => '%d',
    ), '%d');
  }
  return $updated === count($to_update);
}
function om_insert_products($to_insert) {
  global $wpdb;
  $product_table = $wpdb->prefix . 'om_prodotto';
  $inserted = 0;
  foreach ($to_insert as $row) {
    if (!$row['id_pagina']) {
      unset($row['id_pagina']);
    }
    $inserted += $wpdb->insert($product_table, $row, array(
      'nome' => '%s',
      'tipologia' => '%s',
      'unita_misura' => '%s',
      'unita_misura_plurale' => '%s',
      'provenienza' => '%s',
      'id_pagina' => '%d',
    ));
  }
  return $inserted === count($to_insert);
}
function om_delete_products($to_delete) {
  global $wpdb;
  $product_table = $wpdb->prefix . 'om_prodotto';
  $deleted = 0;
  foreach ($to_delete as $row) {
    $deleted += $wpdb->delete($product_table, $row, '%d');
  }
$wpdb->print_error();
  return $deleted === count($to_delete);
}

function om_get_order_products($order_id) {
  global $wpdb;
  $order_product_table = $wpdb->prefix . 'om_prodotto_ordine';
  $product_table = $wpdb->prefix . 'om_prodotto';
  return $wpdb->get_results(
    $wpdb->prepare("
      SELECT p.id,
             p.nome,
             p.tipologia,
             p.unita_misura,
             p.unita_misura_plurale,
             p.provenienza,
             p.pagina,
             op.prezzo
      FROM $order_product_table op INNER JOIN $product_table p ON (op.id_prodotto=p.id)
      WHERE op.id_ordine=%d
      ORDER BY tipologia, nome",
      $order_id
    )
  );
}

function om_set_options() {
  if (!get_option('om_main_form_page_id')) {
    global $user_ID;
    $page = array(
      'post_type' => 'page',
      'post_name' => 'om_main_form',
      'post_title' => "Modulo d'Ordine",
      'post_status' => 'publish',
      'post_content' => '',
      'post_parent' => 0,
      'post_author' => $user_ID,
      'comment_status' => 'closed',
    );
    $page_id = wp_insert_post($page);
    add_option('om_main_form_page_id', $page_id);

    add_option('om_main_form_page', 'om_main_form');
    add_option('om_main_form_splash', '');
    add_option('om_product_typologies', '');
    add_option('om_product_units', '');
  }
}
function om_reset_options() {
  //delete_option('om_main_form_page_id');
  //delete_option('om_main_form_page');
  //delete_option('om_main_form_splash_page');
  //delete_option('om_product_typologies', '');
  //delete_option('om_product_units');
}
?>
