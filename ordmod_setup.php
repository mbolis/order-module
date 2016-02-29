<?php
function om_install() {
  global $wpdb;
  $prefix = $wpdb->prefix . 'om_';
  $order_table = $prefix . 'ordine';
  $order_row_table = $prefix . 'riga_ordine';
  $order_product_table = $prefix . 'prodotto_ordine';
  $product_table = $prefix . 'prodotto';
  $client_table = $prefix . 'contatto';

  $charset_collate = $wpdb->get_charset_collate();

  // Create order table
  $order_sql = "CREATE TABLE $order_table (
    id smallint(5) NOT NULL AUTO_INCREMENT  PRIMARY KEY,
    dt_apertura datetime NOT NULL,
    dt_chiusura datetime NOT NULL,
    dt_modifica datetime,
    dt_accesso datetime
  ) $charset_collate;";

  // Create order row table
  $order_row_sql = "CREATE TABLE $order_row_table (
    id mediumint(9) NOT NULL AUTO_INCREMENT  PRIMARY KEY,
    id_ordine smallint(5) NOT NULL,
    cliente varchar(100) NOT NULL,
    id_prodotto_ordine mediumint(9) NOT NULL,
    quantita decimal(6,3) NOT NULL,
    id_contatto smallint(4) NOT NULL,
    dt_modifica datetime,
    dt_accesso datetime
  ) $charset_collate;";
  
  // Create order product table
  $order_product_sql = "CREATE TABLE $order_product_table (
    id mediumint(9) NOT NULL AUTO_INCREMENT  PRIMARY KEY,
    id_ordine smallint(5) NOT NULL,
    id_prodotto smallint(5) NOT NULL,
    prezzo decimal(5,2) NOT NULL
  ) $charset_collate;";

  // Create product table
  $product_sql = "CREATE TABLE $product_table (
    id smallint(5) NOT NULL AUTO_INCREMENT  PRIMARY KEY,
    nome varchar(255) NOT NULL,
    tipologia varchar(30) NOT NULL,
    unita_misura varchar(20) NOT NULL,
    provenienza varchar(255),
    pagina varchar(255),
    prezzo decimal(5,2),
    attivo bit
  ) $charset_collate;";

  // Create client contact table
  $client_sql = "CREATE TABLE $client_table (
    id smallint(4) NOT NULL AUTO_INCREMENT  PRIMARY KEY,
    nome varchar(255) NOT NULL,
    nome_contatto varchar(255),
    area varchar(30) NOT NULL,
    indirizzo text NOT NULL,
    telefono varchar(30)
  ) $charset_collate;";
  
  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  dbDelta(array(
    $order_sql,
    $order_row_sql,
    $order_product_sql,
    $product_sql,
    $client_sql,
  ));

  // TODO : Install main form
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

    $product_table = $wpdb->prefix . 'om_prodotto_ordine';
    $inserted = 0;
    foreach ($prodotti as $prodotto) {
      $inserted += $wpdb->insert(
        $product_table,
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

function om_get_products_data() {
  global $wpdb;
  $product_table = $wpdb->prefix . 'om_prodotto';
  return $wpdb->get_results("
    SELECT *
    FROM $product_table
    ORDER BY tipologia, nome
  ");
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
  add_option('om_main_form_page', 'om_main_form');
  add_option('om_main_form_page_id', 0);
  add_option('om_main_form_splash_page', '');
  add_option('om_product_typologies', '');
}
function om_reset_options() {
  delete_option('om_main_form_page');
  delete_option('om_main_form_page_id');
  delete_option('om_main_form_splash_page');
  delete_option('om_product_typologies');
}
?>
