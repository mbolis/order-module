<?php
/**
 * Plugin Name: Order Module
 * Plugin URI: 
 * Description: Create, show and handle an order module with your products.
 * Version: 0.1.0
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
}
function om_orders() {
  if (!current_user_can('manage_options'))  {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  $orders = om_get_top_orders();
  if (strtotime($orders[0]->dt_chiusura) > time()) {
    $current_order = $orders[0];
    $last_order = $orders[1];
  } else {
    $last_order = $orders[0];
  }

  include 'ordmod_manage.php';
}
function om_options() {
  if (!current_user_can('manage_options'))  {
    wp_die(__('You do not have sufficient permissions to access this page.'));
  }

  $errors = array();
  if (strtoupper($_SERVER['REQUEST_METHOD']) === 'POST' && $_POST['submit']) {
    $main_form_page = trim($_POST['main_form_page']);
    if ($main_form_page && preg_match('/^[-_a-zA-Z0-9]+$/', $main_form_page)) {
      update_option('om_main_form_page', $main_form_page);
      $success = TRUE;
    } else {
      $errors['main_form_page'] = 'Specificare un valore valido. Sono ammessi lettere, numeri, "-" e "_".';
    }

    $product_typologies = preg_split('/\\s*,\\s*/', trim($_POST['product_typologies']));
    if ($product_typologies) {
      update_option('om_product_typologies', implode(',', $product_typologies));
      $product_typologies = implode(',', $product_typologies);
      $success = TRUE;
    } else {
      $errors['product_typologies'] = 'Occorre specificare almeno una tipologia.';
    }
  } else {
    $main_form_page = get_option('om_main_form_page');
    $product_typologies = get_option('om_product_typologies');
  }
  include 'options_form.php';
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
          $errors_prodotti[$i] = 'Il prezzo non indica un valore numerico.';
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

        global $user_ID;
        $page_id = get_option('om_main_form_page_id');
        $page = array(
          'ID' => $page_id,
          'post_type' => 'page',
          'post_name' => get_option('om_main_form_page'),
          'post_title' => "Modulo d'Ordine",
          'post_status' => 'publish',
          'post_content' => file_get_contents('_ordmod_main_form.html', TRUE),
          'post_parent' => 0,
          'post_author' => $user_ID,
          'comment_status' => 'closed',
        );
        $page_id = wp_insert_post($page);
        update_option('om_main_form_page_id', $page_id);

        ?>
          Attendere prego...
          <script>alert(<?php echo $page_id; ?>);location.href = '<?php echo admin_url('/admin.php?page=om-orders&success=' . urlencode('Nuovo ordine aperto. Data di chiusura: ' . date('d/m/Y H:i', $dt_chiusura))); ?>';</script>
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

//if (current_user_can('read')) {
  add_action('wp_ajax_order_form_submit', 'om_json_order_form_submit');
  add_action('wp_ajax_nopriv_order_form_submit', 'om_json_order_form_submit');
  function om_json_order_form_submit() {
    header('Content-Type: application/json');
    $order = om_get_top_orders(1)[0];
    if ($order) {
      $now = time();
      $dt_apertura = strtotime($order->dt_apertura . 'Europe/Rome');
      $dt_chiusura = strtotime($order->dt_chiusura . 'Europe/Rome');
      if ($dt_apertura <= $now && $now <= $dt_chiusura) {
        $products = om_get_order_products($order->id);
        $result = array(
          'dt_chiusura' => $dt_chiusura,
          'prodotti' => $products
        );
        echo json_encode($result);
        wp_die();
      }
    }
    echo 'false';
    wp_die();
  }
//}
add_action('the_content', 'om_order_form_data');
function om_order_form_data($content) {
  global $post;
  if ($post->post_name === get_option('om_main_form_page') && current_user_can('read')) {
    $order = om_get_top_orders(1)[0];
    if (!$order) {
      return 'Nessun ordine';
    }
    $now = time();
    $dt_apertura = strtotime($order->dt_apertura . 'Europe/Rome');
    $dt_chiusura = strtotime($order->dt_chiusura . 'Europe/Rome');
    if ($dt_apertura > $now) {
      return 'Ordine non ancora aperto';
    }
    if ($now > $dt_chiusura) {
      return 'Ordine scaduto';
    }

    $splash_page = get_page_by_path(get_option('om_main_form_splash_page'));
    if ($splash_page) {
      $content = preg_replace('/<!--\\s*SPLASH\\s*-->/', $splash_page->post_content, $content);
    }

    $products = om_get_order_products($order->id);
    $tipologie = preg_split('/\\s*,\\s*/', trim(get_option('om_product_typologies')));
    $content .= '
      <script>
        tipologie(' . json_encode($tipologie) . ');
        dataApertura(new Date(' . $dt_apertura . '));
        dataChiusura(new Date(' . $dt_chiusura . '));
        prodotti(' . json_encode($products) . ');
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
}

add_action('wp_enqueue_scripts', 'om_scripts');
function om_scripts() {
  wp_enqueue_style('om-page', plugins_url('css/style.css', __FILE__));
  wp_enqueue_script('knockout', plugins_url('js/knockout.js', __FILE__));
}

?>
