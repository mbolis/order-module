<?php
require_once dirname(__FILE__) . '/PHPExcel/PHPExcel.php';

function om_send_client_order_report($data, $ext = 'xlsx') {
  PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_to_sqlite);
  $excel = PHPExcel_IOFactory::load(dirname(__FILE__) . '/template_modulo_ordine.xls');

  /* Order Info */
  $order = $data['client_order'];
 
  $dt_chiusura = strtotime($order['dt_chiusura']);
  $day_of_week = date('w', $dt_chiusura);
  if ($day_of_week == 1) {
    $week_start = $dt_chiusura;
  } else {
    $week_start = strtotime('next monday', $dt_chiusura);
  }
  $week_end = strtotime('next sunday', $week_start);
  $week_start = date('d.m', $week_start);
  $week_end = date('d.m', $week_end);
  $title = "Modulo Ordine per CONSEGNE NELLA SETTIMANA $week_start – $week_end";

  if ($order['id_gas']) {
    $recipient = 'GAS ' . $order['nome_gas'] . "\n" . $order['nome_contatto_gas'];
  } else {
    $recipient = $order['nome_cliente'];
  }
  $address = $order['indirizzo'] . "\nTel.: " . $order['telefono'];
  $notes = $order['note'];

  $excel->setActiveSheetIndex(0)
        ->setCellValue('B1', $title)
        ->setCellValue('C7', $recipient)
        ->setCellValue('C8', $address)
        ->setCellValue('C9', $notes);

  /* Products Table */
  $products = $data['products'];
  $n_products = count($products);

  $sheet = $excel->getActiveSheet();
  $sheet->insertNewRowBefore(5, $n_products - 1);

  $total = 0;
  for ($i = 0; $i < count($products); $i++) {
    $product = $products[$i];

    $description = $product['nome'];
    if ($product['provenienza']) {
      $description .= ' (' . $product['provenienza'] . ')';
    }
    $price = $product['prezzo'];
    $qty = $product['quantita'];
    $unit = $product['unita_misura'];

    $unit_abbr = $unit == 'kg' ? 'kg' : 'u';

    $rown = $i + 4;
    $sheet->setCellValue("B$rown", $description)
          ->setCellValue("C$rown", $price)
          ->setCellValue("D$rown", $qty)
          ->setCellValue("E$rown", $unit)
          ->setCellValue("F$rown", "=C$rown*D$rown") #FIXME: "=C$rown*D$rown"
          ->duplicateStyle($sheet->getStyle('B4'), "B$rown")
          ->duplicateStyle($sheet->getStyle('C4'), "C$rown")
          ->duplicateStyle($sheet->getStyle('D4'), "D$rown")
          ->duplicateStyle($sheet->getStyle('E4'), "E$rown")
          ->duplicateStyle($sheet->getStyle('F4'), "F$rown");

    $sheet->getStyle("C$rown")->getNumberFormat()->setFormatCode("[$\xE2\x82\xAC/$unit_abbr-410]* #,##0.00");
    $sheet->getStyle("F$rown")->getNumberFormat()->setFormatCode("[$\xE2\x82\xAC-410]* #,##0.00");

    $total += $price * $qty;
  }

  $sheet->setCellValue('F'.($rown+2), "=SUM(F4:F$rown)") #FIXME: "=SUM(F4:F$rown)"
        ->getStyle('F'.($rown+2))->getNumberFormat()->setFormatCode("[$\xE2\x82\xAC-410]* #,##0.00");

  $file_name = $_POST['client']['username'] . '_' . date('YmdHis');
  $file_path = dirname(__FILE__) . '/tmp/' . $file_name . '.' . $ext;
  _om_write_excel_file($excel, $file_path, $ext);
  wp_mail('mbolis1984@gmail.com', 'miao miao', 'bau bau bau bau', array(), array($file_path));
  _om_delete_excel_file($file_path);

  $excel->disconnectWorksheets();
}

/*
// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="01simple.xlsx"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header ('Pragma: public'); // HTTP/1.0
*/

function _om_write_excel_file($excel, $file_path, $ext) {
  if ($ext === 'xlsx') {
    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
  } else {
    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
  }

  $writer->setPreCalculateFormulas(TRUE)->save($file_path);
}
function _om_delete_excel_file($file_path) {
  unlink($file_path);
}
