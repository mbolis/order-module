<?php
/**
 * Plugin Name: Order Module
 * Plugin URI: 
 * Description: Create, show and handle an order module with your products.
 * Version: 0.2.0
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
function om_orders() {
  if (!current_user_can('manage_options'))  {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  $success = $_GET['success'];

  $orders = om_get_top_orders();
  if (strtotime($orders[0]->dt_chiusura) > time()) {
    $current_order = $orders[0];
    $last_order = $orders[1];
  } else {
    $last_order = $orders[0];
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
  if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && $_POST['submit']) {
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

    $product_typologies = preg_split('/\\s*,\\s*/', trim($_POST['product_typologies']));
    if (!$product_typologies) {
      $errors['product_typologies'] = 'Occorre specificare almeno una tipologia.';
    }
    $product_typologies = implode(', ', $product_typologies);

    $product_units = preg_split('/\\s*,\\s*/', trim($_POST['product_units']));
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

    if (!$errors) {
      update_option('om_main_form_page', $main_form_page);
      wp_update_post(array(
        'ID' => get_option('om_main_form_page_id'),
        'post_name' => $main_form_page,
      ));
      update_option('om_main_form_splash', $main_form_splash);
      update_option('om_product_typologies', $product_typologies);
      update_option('om_product_units', $product_units);
      $success = TRUE;
    }

  } else {
    $main_form_page = get_option('om_main_form_page');
    $main_form_splash = get_option('om_main_form_splash');
    $product_typologies = get_option('om_product_typologies');
    $product_units = get_option('om_product_units');
  }
  include 'options_form.php';
  // TODO include 'uninstall_form.php';
  // $current = get_settings('active_plugins');
  // array_splice($current, array_search("myplugin.php", $current), 1 );
  // update_option('active_plugins', $current);
  // header('Location: plugins.php?deactivate=true');
  // die();
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
    'sort_column' => 'post_title'
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
    foreach ($all_products as $i => $prodotto) {
      if ($prodotto['attivo']) {
        $prezzo = str_replace(',', '.', trim($prodotto['prezzo']));
        if ($prezzo && preg_match('/^[0-9]+(\.[0-9]+)?$/', $prezzo)) {
          $prodotto['prezzo'] = $prezzo;
          $prodotti[] = $prodotto;
        } else {
          $errors_prodotti[$i] = 'Il prezzo inserito non indica un valore numerico.';
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
      $all_products[] = (array) $product;
    }
  }

  $tabs = array();
  foreach ($all_products as $product) {
    $tipologia = $product->tipologia;
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
      return 'Nessun ordine'; // TODO
    }
    $now = time();
    $dt_apertura = strtotime($order->dt_apertura . 'Europe/Rome');
    $dt_chiusura = strtotime($order->dt_chiusura . 'Europe/Rome');
    if ($dt_apertura > $now) {
      return 'Ordine non ancora aperto'; // TODO
    }
    if ($now > $dt_chiusura) {
      return 'Ordine scaduto'; // TODO
    }

    $user = wp_get_current_user();

    $content = file_get_contents('_ordmod_main_form.html', TRUE);

    $splash_page = get_post(get_option('om_main_form_splash'));
    if ($splash_page) {
      $content .= '<script type="text/html" id="splash">' . wpautop($splash_page->post_content) . '</script>';
    }

    $gas_list = om_get_gas_list();

    $tipologie = preg_split('/\\s*,\\s*/', trim(get_option('om_product_typologies')));
    $products_hash = array();
    foreach ($tipologie as $tipologia) {
      $products_hash[$tipologia] = array();
    }
    $products = om_get_order_products($order->id);
    foreach ($products as $product) {
      $products_hash[$product->tipologia][] = $product;
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
