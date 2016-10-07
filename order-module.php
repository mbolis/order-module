<?php
/**
 * Plugin Name: Order Module
 * Plugin URI: 
 * Description: Create, show and handle an order module with your products.
 * Version: 0.4.0
 * Author: Marco Bolis
 * Author URI:
 * License: GPL3
 */

include_once 'ordmod_setup.php';
register_activation_hook(__FILE__, 'om_install');
register_activation_hook(__FILE__, 'om_set_options');
register_deactivation_hook(__FILE__, 'om_reset_options');

add_action('admin_menu', 'om_show_menu_entries');
function om_show_menu_entries() {
  add_object_page('Ordini', 'Ordini', 'manage_options', 'om-orders', 'om_orders', 'dashicons-carrot');
  add_submenu_page('om-orders', 'Ordini', 'Gestisci', 'manage_options', 'om-orders', 'om_orders');
  if (!om_has_active_order()) {
    add_submenu_page('om-orders', 'Apri un Nuovo Ordine', 'Nuovo Ordine', 'manage_options', 'om-new-order', 'om_new_order');
  }
  add_submenu_page('om-orders', 'Opzioni Ordini', 'Opzioni', 'manage_options', 'om-options', 'om_options');
  add_submenu_page('om-orders', 'Elenco Prodotti', 'Prodotti', 'manage_options', 'om-products', 'om_products');
  add_submenu_page('om-orders', 'Elenco GAS', 'GAS', 'manage_options', 'om-gas', 'om_gas');
}

if (is_admin() && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && $_GET['page'] === 'om-orders' && isset($_POST['download_report'])) {
  add_action('admin_init', 'om_download_admin_report', 1);
}
function om_download_admin_report() {
  if (current_user_can('manage_options')) {
    $order_id = $_POST['id_ordine'];
    $order_data = om_get_all_client_orders_full($order_id);
    require_once dirname(__FILE__) . '/excel_report.php';
    om_write_admin_order_report($order_data);
    exit;
  }
}
function om_orders() {
  if (!current_user_can('manage_options'))  {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
    if ($_POST['delete_order']) {
      if (om_delete_order($_POST['id_ordine'])) {
        $success = 'Ordine eliminato.';
      } else {
        $error = 'Impossibile eliminare ordine.';
      }
    }
  } else {
    $success = $_GET['success'];
  }

  $orders = om_get_top_orders(-1);
  if (strtotime($orders[0]['dt_chiusura']) > time()) {
    $current_order = array_shift($orders);
  }

  include 'ordmod_manage.php';
}

function om_x_page_id($page) {
  return $page->ID;
}
function om_options() {
  if (!current_user_can('manage_options'))  {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  $all_pages = get_pages(array('exclude' => get_option('om_main_form_page_id')));

  $errors = array();
  if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && $_POST['hazard_op'] === 'reset_all') {
      om_nuke_db();
      om_nuke_options();
      deactivate_plugins(plugin_basename(__FILE__));
      $activation_result = activate_plugin(plugin_basename(__FILE__));
      if (is_wp_error($activation_result)) {
        wp_safe_redirect('plugins.php?error=true&plugin=order-module/order-module.php');
        wp_die();
      }
      $hazard_op = 'reset_all';
  }
  if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && $_POST['hazard_op'] === 'nuke_all') {
      om_nuke_db();
      om_nuke_options();
      deactivate_plugins(plugin_basename(__FILE__));
      wp_safe_redirect('plugins.php?deactivate=true');
      wp_die();
  }
  if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && $_POST['submit']) {

    if ($_POST['hazard_op'] === 'nuke_all') {
    }

    $main_form_page = trim($_POST['main_form_page']);
    if (!$main_form_page || !preg_match('/^[-_a-zA-Z0-9]+$/', $main_form_page)) {
      $errors['main_form_page'] = 'Specificare un valore valido. Sono ammessi lettere, numeri, "-" e "_".';
    } elseif ($main_form_page !== get_option('om_main_form_page') && get_page_by_path($main_form_page)) {
      $errors['main_form_page'] = 'Il nome specificato &egrave; gi&agrave; in uso da un\'altra pagina.';
    }

    $main_form_splash = trim($_POST['main_form_splash']);
    if (!$main_form_splash || !in_array($main_form_splash, array_map('om_x_page_id', $all_pages))) {
      $errors['main_form_splash'] = 'Selezionare un valore dalla lista.';
    }

    $product_typologies = preg_split('/\\s*,\\s*/', stripslashes(trim($_POST['product_typologies'])));
    if (!$product_typologies) {
      $errors['product_typologies'] = 'Occorre specificare almeno una tipologia.';
    }
    $product_typologies = implode(', ', $product_typologies);

    $product_units = preg_split('/\\s*,\\s*/', stripslashes(trim($_POST['product_units'])));
    if (!$product_units) {
      $errors['product_units'] = 'Occorre specificare almeno un\' unit&agrave; di misura.';
    }
    for ($i = 0; $i < count($product_units); $i++) {
      $unit = preg_split('/\\s*\\/\\s*/', $product_units[$i]);
      if (count($unit) > 2) {
        $errors['product_units'] = 'C\'&egrave; una barra ("/") di troppo nella lista.';
        break;
      }
      if (count($unit) == 2) {
        $unit = $unit[0] . '/' . $unit[1];
      } else {
        $unit = $unit[0];
      }
      $product_units[$i] = $unit;
    }
    $product_units = implode(', ', $product_units);

    # Mail notification text
    $notification_mail_subject = stripslashes(trim($_POST['notification_mail_subject']));
    if (!$notification_mail_subject) {
      $errors['notification_mail_subject'] = "Indicare l'oggetto della mail";
    }
    $notification_mail_text = stripslashes(trim($_POST['notification_mail_text']));
    if (!$notification_mail_text) {
      $errors['notification_mail_text'] = "Indicare il testo della mail";
    }

    # User messages
    $message_order_not_available = stripslashes(trim($_POST['message_order_not_available']));
    if (!$message_order_not_available) {
      $errors['message_order_not_available'] = 'Indicare un messaggio';
    }
    $message_order_is_closed = stripslashes(trim($_POST['message_order_is_closed']));
    if (!$message_order_is_closed) {
      $errors['message_order_is_closed'] = 'Indicare un messaggio';
    }
    $message_form_success = stripslashes(trim($_POST['message_form_success']));
    if (!$message_form_success) {
      $errors['message_form_success'] = 'Indicare un messaggio';
    }
    $message_form_expired = stripslashes(trim($_POST['message_form_expired']));
    if (!$message_form_expired) {
      $errors['message_form_expired'] = 'Indicare un messaggio';
    }

    if (!$errors) {
      update_option('om_main_form_page', $main_form_page);
      wp_update_post(array(
        'ID' => get_option('om_main_form_page_id'),
        'post_name' => $main_form_page,
      ));
      update_option('om_main_form_splash', $main_form_splash);
      update_option('om_product_typologies', $product_typologies);
      update_option('om_product_units', $product_units);

      update_option('om_notification_mail_subject', $notification_mail_subject);
      update_option('om_notification_mail_text', $notification_mail_text);

      update_option('om_message_order_not_available', htmlentities($message_order_not_available));
      update_option('om_message_order_is_closed', htmlentities($message_order_is_closed));
      update_option('om_message_form_success', htmlentities($message_form_success));
      update_option('om_message_form_expired', htmlentities($message_form_expired));

      $success = TRUE;
    }

  } else {
    $main_form_page = get_option('om_main_form_page');
    $main_form_splash = get_option('om_main_form_splash');
    $product_typologies = get_option('om_product_typologies');
    $product_units = get_option('om_product_units');
    $notification_mail_subject = get_option('om_notification_mail_subject');
    $notification_mail_text = get_option('om_notification_mail_text');
    $message_order_not_available = get_option('om_message_order_not_available');
    $message_order_is_closed = get_option('om_message_order_is_closed');
    $message_form_success = get_option('om_message_form_success');
    $message_form_expired = get_option('om_message_form_expired');
  }
  include 'options_form.php';
}
function om_products() {
  if (!current_user_can('manage_options'))  {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }
  
  if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && $_POST['submit']) {
    $success = TRUE;
    $to_update = $_POST['update'];
    if ($to_update) {
      $success &= om_update_products($to_update);
    }
    $to_insert = $_POST['insert'];
    if ($to_insert) {
      $success &= om_insert_products($to_insert);
    }
    $to_delete = $_POST['delete'];
    if ($to_delete) {
      $success &= om_delete_products($to_delete);
    }
    $errors = !$success;
  }

  $units = explode(', ', get_option('om_product_units'));
  sort($units);
  $units_plural = array();
  for ($i = 0; $i < count($units); $i++) {
    $unit = split('/', $units[$i]);
    $units[$i] = $unit[0];
    $units_plural[$unit[0]] = $unit[1] ? $unit[1] : $unit[0];
  }

  $typologies = explode(', ', get_option('om_product_typologies'));
  $products = om_get_products_data($order_by='nome');
  $pages = get_pages(array(
    'sort_column' => 'post_title',
  ));
  include 'products_form.php';
}
function om_gas() {
  if (!current_user_can('manage_options'))  {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && $_POST['submit']) {
  } else {
    $gas_data = om_get_gas_data();
  }

  include 'gas_form.php';
}
if (is_admin()) {
  add_action('wp_ajax_om_delete_gas', 'om_json_delete_gas');
  function om_json_delete_gas() {
    if (!current_user_can('manage_options'))  {
      header('HTTP/1.1 403 Forbidden');
      die();
    }

    $id = $_POST['id'];
    if (!$id) {
      header('HTTP/1.1 400 Bad Request');
      die();
    }

    if (!om_delete_gas($id)) {
      header('HTTP/1.1 500 Internal Server Error');
    } else {
      header('HTTP/1.1 204 No Content');
    }
    die();
  }
  add_action('wp_ajax_om_save_gas', 'om_json_save_gas');
  function om_json_save_gas() {
    if (!current_user_can('manage_options'))  {
      header('HTTP/1.1 403 Forbidden');
      die();
    }

    $gas = $_POST['gas'];
    $gas['nome'] = stripslashes($gas['nome']);
    $gas['nome_contatto'] = stripslashes($gas['nome_contatto']);
    $gas['area'] = stripslashes($gas['area']);
    $gas['indirizzo'] = stripslashes($gas['indirizzo']);

    $id = $gas['id'];
    if ($id) {
      if (!om_update_gas($gas)) {
        header('HTTP/1.1 500 Internal Server Error');
        die();
      }
    } else {
      $id = om_insert_gas($gas);
      if (!$id) {
        header('HTTP/1.1 500 Internal Server Error');
        die();
      }
    }

    echo $id;
    die();
  }
}

function om_new_order() {
  if (!current_user_can('manage_options'))  {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  $errors = array();
  if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && $_POST['submit']) {
    $ordine = $_POST['ordine'];

    $dt_apertura = strtotime(str_replace('/', '-', $ordine['dt_apertura']));
    $dt_chiusura = strtotime(str_replace('/', '-', $ordine['dt_chiusura']));
    if (!$dt_apertura) {
      $errors['dt_apertura'] = 'Specificare una data di apertura ordine.';
    }
    if (!$dt_chiusura) {
      $errors['dt_chiusura'] = 'Specificare una data di chiusura ordine.';
    } elseif ($dt_apertura && $dt_chiusura <= $dt_apertura) {
      $errors['dt_chiusura'] = 'Specificare una data di chiusura ordine seguente alla data di apertura.';
    }

    $all_products = $_POST['prodotti'];
    $prodotti = array();
    $errors_prodotti = array();
    foreach ($all_products as $tipologia => $prodotti_tipologia) {
      for ($i=0; $i<count($prodotti_tipologia); $i++) {
        $prodotto = $prodotti_tipologia[$i];
        if ($prodotto['attivo']) {
          $prezzo = str_replace(',', '.', trim($prodotto['prezzo']));
          if ($prezzo && preg_match('/^[0-9]+(\.[0-9]+)?$/', $prezzo)) {
            $prodotto['prezzo'] = $prezzo;
            $prodotti[] = $prodotto;
          } else {
            $errors_prodotti[$prodotto['tipologia']][$i] = 'Il prezzo inserito non indica un valore numerico.';
          }
        }
      }
    }
    if ($errors_prodotti) {
      $errors['prodotti'] = $errors_prodotti;
    }

    if ($errors) {
      $dt_apertura = $dt_apertura ? date('d/m/Y H:i', $dt_apertura) : '';
      $dt_chiusura = $dt_chiusura ? date('d/m/Y H:i', $dt_chiusura) : '';
    } else {
      $ordine['dt_apertura'] = date('Y-m-d H:i:00', $dt_apertura);
      $ordine['dt_chiusura'] = date('Y-m-d H:i:00', $dt_chiusura);

      if (om_save_order($ordine, $prodotti)) {
        ?>
          Attendere prego...
          <script>location.href = '<?php echo admin_url('/admin.php?page=om-orders&success=' . urlencode('Nuovo ordine aperto. Data di chiusura: ' . date('d/m/Y H:i', $dt_chiusura))); ?>';</script>
        <?php

        exit;
      }
    }
  } else {
    $select_products = om_get_products_data();
    $all_products = array();
    foreach ($select_products as $product) {
      $typology = $product['tipologia'];
      if (isset($all_products[$typology])) {
        $all_products[$typology][] = $product;
      } else {
        $all_products[$typology] = array($product);
      }
    }
  }

  $tabs = array();
  foreach ($all_products as $product) {
    $tipologia = $product['tipologia'];
    if (!isset($tabs[$tipologia])) {
      $tabs[$tipologia] = array();
    }
    $tabs[$tipologia][] = $product;
  }

  include 'ordmod_new_order.php';
}

if (is_admin()) {
  add_action('wp_ajax_order_form_submit', 'om_json_order_form_submit');
  function om_json_order_form_submit() {
    if ($_POST['action'] === 'order_form_submit' && current_user_can('read')) {
      global $wpdb;
      header('Content-Type: application/json');
      $id = om_save_client_order($_POST);
      if ($id > 0) {
        $order_data = om_get_client_order_full($id);
        require_once dirname(__FILE__) . '/excel_report.php';
        om_send_client_order_report($order_data);
      } else {
        ob_start();
        $wpdb->show_errors();
	$wpdb->print_error();
        $wpdb->hide_errors();
        $message = ob_get_contents();
        ob_end_clean();
      }
      $result = array(
        'status' => $id <= 0 ? $id : 1,
        'message' => $message
      );
      echo json_encode($result);
    }
//    header('Content-Type: application/json');
//    $order = om_get_top_orders(1)[0];
//    if ($order) {
//      $now = time();
//      $dt_apertura = strtotime($order->dt_apertura . 'Europe/Rome');
//      $dt_chiusura = strtotime($order->dt_chiusura . 'Europe/Rome');
//      if ($dt_apertura <= $now && $now <= $dt_chiusura) {
//        $products = om_get_order_products($order->id);
//        $result = array(
//          'dt_chiusura' => $dt_chiusura,
//          'prodotti' => $products
//        );
//        echo json_encode($result);
//        wp_die();
//      }
//    }
//    echo 'false';
    wp_die();
  }
}
add_action('the_content', 'om_order_form_data');
function om_order_form_data($content) {
  global $post;
  if ($post->post_name === get_option('om_main_form_page') && current_user_can('read')) {
    $order = om_get_top_orders(1)[0];
    if (!$order) {
      return get_option('om_message_order_not_available'); # 'Nessun ordine'; // TODO
    }
    $now = time();
    $dt_apertura = strtotime($order['dt_apertura'] . 'Europe/Rome');
    $dt_chiusura = strtotime($order['dt_chiusura'] . 'Europe/Rome');
    if ($dt_apertura > $now) {
      return get_option('om_message_order_not_available'); # 'Ordine non ancora aperto'; // TODO
    }
    if ($now > $dt_chiusura) {
      return get_option('om_message_order_is_closed'); # 'Ordine scaduto'; // TODO
    }

    $user = wp_get_current_user();

    $content = file_get_contents('_ordmod_main_form.html', TRUE);

    $splash_page = get_post(get_option('om_main_form_splash'));
    if ($splash_page) {
      $content .= '<script type="text/html" id="splash">' . wpautop($splash_page->post_content) . '</script>';
    }
    $content .= '<script type="text/plain" id="om_message_form_success">' . get_option('om_message_form_success') . '</script>';
    $content .= '<script type="text/plain" id="om_message_form_expired">' . get_option('om_message_form_expired') . '</script>';

    $gas_list = om_get_gas_list();

    $tipologie = preg_split('/\\s*,\\s*/', trim(get_option('om_product_typologies')));
    $products_hash = array();
    foreach ($tipologie as $tipologia) {
      $products_hash[$tipologia] = array();
    }
    $products = om_get_order_products($order['id']);
    foreach ($products as $product) {
      if ($product['id_pagina']) {
        $prod_page = get_post($product['id_pagina']);
        if ($prod_page) {
          $product['pagina'] = get_site_url().'/'.$prod_page->post_name;
          if (preg_match('/<img\s+[^>]*?src=["\']?([^"\'>]+)/i', $prod_page->post_content, $match)) {
            $product['immagine'] = $match[1];
          }
        }
      }
      $products_hash[$product['tipologia']][] = $product;
    }
    $products_data = array();
    foreach ($products_hash as $typology => $products) {
      $products_data[] = array($typology, $products);
    }
    
    $content .= '
      <script>
        ajaxurl = "' . admin_url('admin-ajax.php') . '";
        username = "' . $user->user_login . '";
        jQuery(function() {
          loadGasList(' . json_encode($gas_list) . ');
          loadProducts(' . json_encode($products_data) . ');
        });
      </script>';
  }
  return $content;
}

add_action('admin_enqueue_scripts', 'om_admin_scripts');
function om_admin_scripts() {
  wp_enqueue_style('om-admin', plugins_url('css/admin.css', __FILE__));
  wp_enqueue_style('jquery-ui', plugins_url('jquery-ui/jquery-ui.css', __FILE__));
  wp_enqueue_style('jquery-ui.timepicker', plugins_url('jquery-ui/jquery-ui.timepicker.css', __FILE__));
  wp_enqueue_script('jquery-ui', plugins_url('jquery-ui/jquery-ui.js', __FILE__));
  wp_enqueue_script('datepicker-posfix', plugins_url('js/datepicker-posfix.js', __FILE__));
  wp_enqueue_script('jquery-ui.timepicker', plugins_url('jquery-ui/jquery-ui.timepicker.js', __FILE__));
  wp_enqueue_script('jquery-ui.datepicker-it', plugins_url('jquery-ui/jquery-ui.datepicker-it.js', __FILE__));
  wp_enqueue_script('jquery-ui.timepicker-it', plugins_url('jquery-ui/jquery-ui.timepicker-it.js', __FILE__));
  wp_enqueue_script('knockout', plugins_url('js/knockout.js', __FILE__));
}

add_action('wp_enqueue_scripts', 'om_scripts');
function om_scripts() {
  global $post;
  if ($post->post_name === get_option('om_main_form_page') && current_user_can('read')) {
    wp_enqueue_style('om-page', plugins_url('css/style.css', __FILE__));
    wp_enqueue_script('knockout', plugins_url('js/knockout.js', __FILE__));
    wp_enqueue_script('knockout-bindings', plugins_url('js/bindings.js', __FILE__), '', '', true);
  }
}

?>
