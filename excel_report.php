<?php
require_once dirname(__FILE__) . '/PHPExcel/PHPExcel.php';

function om_send_client_order_report($data, $ext = 'xlsx') {
  PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_to_sqlite);
  $excel = PHPExcel_IOFactory::load(dirname(__FILE__) . '/template_modulo_ordine.xls');

  /* Order Info */
  $order = $data['client_order'];

  $email = $order['email_cliente'];
  if (!$email) {
    return 1;
  }

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
    $address = $order['indirizzo'] . "\nTel.: " . $order['telefono'];
  }
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
  if ($n_products > 1) {
    $sheet->insertNewRowBefore(5, $n_products - 1);
  }

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
          ->setCellValue("F$rown", "=C$rown*D$rown")
          ->duplicateStyle($sheet->getStyle('B4'), "B$rown")
          ->duplicateStyle($sheet->getStyle('C4'), "C$rown")
          ->duplicateStyle($sheet->getStyle('D4'), "D$rown")
          ->duplicateStyle($sheet->getStyle('E4'), "E$rown")
          ->duplicateStyle($sheet->getStyle('F4'), "F$rown");

    $sheet->getStyle("C$rown")->getNumberFormat()->setFormatCode("[$\xE2\x82\xAC/$unit_abbr-410]* #,##0.00");
    $sheet->getStyle("F$rown")->getNumberFormat()->setFormatCode("[$\xE2\x82\xAC-410]* #,##0.00");

    $total += $price * $qty;
  }

  if (isset($rown)) {
    $sheet->setCellValue('F'.($rown+2), "=SUM(F4:F$rown)")
          ->getStyle('F'.($rown+2))->getNumberFormat()->setFormatCode("[$\xE2\x82\xAC-410]* #,##0.00");
  }

  $file_name = $_POST['client']['username'] . '_' . date('YmdHis');
  $file_path = dirname(__FILE__) . '/tmp/' . $file_name . '.' . $ext;
  _om_write_excel_file($excel, $file_path, $ext);

  $subject = get_option('om_notification_mail_subject');
  $text = get_option('om_notification_mail_text');
  wp_mail($email, $subject, $text, array(), array($file_path));

#  _om_delete_excel_file($file_path);

  $excel->disconnectWorksheets();
  return 0;
}

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

function om_write_admin_order_report($data, $ext = 'xlsx') {
  PHPExcel_Settings::setCacheStorageMethod(PHPExcel_CachedObjectStorageFactory::cache_to_sqlite);
  $excel = new PHPExcel();

  ### TODO : put this in upper left corner !!!

  $dt_chiusura = strtotime($order['dt_chiusura']);
  $day_of_week = date('w', $dt_chiusura);
  if ($day_of_week == 1) {
    $week_start = $dt_chiusura;
  } else {
    $week_start = strtotime('next monday', $dt_chiusura);
  }
  $week_end = strtotime('next sunday', $week_start);
  $week_start = date('d/m', $week_start);
  $week_end = date('d/m', $week_end);
  $title = "Modulo Ordine per CONSEGNE NELLA SETTIMANA $week_start – $week_end";

  ###

  $products = $data['products'];
  $client_orders = $data['client_orders'];
  $sheet = $excel->getActiveSheet()->setTitle('Tutti gli ordini');

  $all_borders = array(
    'borders' => array(
      'allborders' => array(
        'style' => PHPExcel_Style_Border::BORDER_THIN,
      )
    )
  );
  $out_borders = array(
    'borders' => array(
      'outline' => array(
        'style' => PHPExcel_Style_Border::BORDER_THIN,
      )
    )
  );

  $gas_map = array();
  $min_col = 'D';
  $ccol = $min_col;
  for ($i=0; $i<count($client_orders); $i++) {
    $order = $client_orders[$i];

    # Set column and header style
    $client_lcol = $ccol++;
    $client_rcol = $ccol++;
    $sheet->getColumnDimension($client_lcol)->setWidth(10);
    $sheet->getColumnDimension($client_rcol)->setWidth(20);
    $sheet->setCellValue("${client_lcol}1", $order['nome_cliente'])
          ->mergeCells("${client_lcol}1:${client_rcol}1");
    $sheet->getStyle("${client_lcol}1:${client_rcol}2")->applyFromArray($all_borders);

    $id_gas = $order['id_gas'];
    if ($id_gas) {
      $gas_range = $gas_map[$id_gas];
      if (!$gas_range) {

        # Start GAS range
        $sheet->setCellValue("${client_lcol}2", "GAS: ${order['nome_gas']}
${order['nome_contatto_gas']}
${order['indirizzo']}
Tel.: ${order['telefono']}");

        $gas_range = array(
          'start' => $client_lcol,
          'name' => $order['nome_gas'],
          'tot' => array(),
        );
      }
      $gas_range['end'] = $client_rcol;
      $gas_map[$id_gas] = $gas_range;
    } else {
      $sheet->setCellValue("${client_lcol}2", "${order['indirizzo']}\nTel.: ${order['telefono']}")
            ->mergeCells("${client_lcol}2:${client_rcol}2");
    }

    $max_col = $client_rcol;
  }

  # Merge GAS ranges (same address)
  foreach ($gas_map as $gas_range) {
    $sheet->mergeCells("${gas_range['start']}2:${gas_range['end']}2");
  }

  # Header and left column style
  $sheet->getColumnDimension('A')->setAutoSize(TRUE);
  $sheet->getColumnDimension('B')->setWidth(12);
  $sheet->getColumnDimension('C')->setWidth(12);
  $sheet->getRowDimension(2)->setRowHeight(80);
  $sheet->getStyle("D1:${max_col}2")->applyFromArray(array(
    'alignment' => array(
      'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
      'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
      'wrap' => TRUE,
    )
  ));

  $first_prod_row = 3;
  $row = $first_prod_row;
  foreach ($products as $id => $product) {
    $unit = $product['unita_misura'];
    $unit_abbr = strlen($unit) == 2 ? $unit : substr($unit, 0, 1) . '.';
    $sheet->getStyle("B$row")->getNumberFormat()->setFormatCode("[$\xE2\x82\xAC/$unit_abbr-410]* #,##0.00");
    $sheet->getStyle("C$row")->getNumberFormat()->setFormatCode("0.### \"$unit_abbr\";;;");

    $sum_range = "\$D\$$row:\$$max_col\$$row";
    $sheet->setCellValue("A$row", $product['nome'])->setCellValue("B$row", (float) $product['prezzo']);

    $ccol = 'D';
    $tot_qty = 0;
    foreach ($client_orders as $order) {
      $qty_col = $ccol++;
      $price_col = $ccol++;

      $qty = $order['righe_ordine'][$id];
      $qty = $qty ? $qty : 0;
      $tot_qty += $qty;
      if ($order['id_gas']) {
        $gas_tot_qty = $gas_map[$order['id_gas']]['tot'];
        $gas_map[$order['id_gas']]['tot'][$id] = (isset($gas_tot_qty[$id]) ? $gas_tot_qty[$id] : 0) + $qty;
      }
      $sheet->setCellValue("$qty_col$row", (float) $qty)
        ->getStyle("$qty_col$row")->getNumberFormat()->setFormatCode("0.### \"$unit_abbr\";;;");

      $sheet->setCellValue("$price_col$row", "=\$B$row*$qty_col$row")
            ->getStyle("$price_col$row")
              ->applyFromArray(array('font' => array('size' => 9)))
              ->getNumberFormat()->setFormatCode("(#,##0.00 [$\xE2\x82\xAC-410]);;;");
    }

    $sheet->getStyle("A$row:$max_col$row")->applyFromArray($all_borders);
    $sheet->setCellValue("C$row", $tot_qty);

    $last_prod_row = $row++;
  }

  $fill_gray = array(
    'fill' => array(
      'type' => PHPExcel_Style_Fill::FILL_SOLID,
      'color' => array('rgb' => 'DDDDDD')
    )
  );

  $sheet->getStyle("B$first_prod_row:B$last_prod_row")->applyFromArray($fill_gray);
  $sheet->getStyle("${min_col}1:${max_col}1")->applyFromArray($fill_gray);
  for ($col=$min_col; $col<$max_col; $col++,$col++) {
    $sheet->getStyle("$col$first_prod_row:$col$last_prod_row")->applyFromArray($fill_gray);
  }

  $notes_style = array(
    'alignment' => array(
      'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
      'vertical' => PHPExcel_Style_Alignment::VERTICAL_TOP,
    ),
    'borders' => array(
      'outline' => array(
        'style' => PHPExcel_Style_Border::BORDER_THIN,
      )
    )
  );

  $ccol = $min_col;
  $total_row = $last_prod_row + 1;
  $notes_row = $last_prod_row + 2;
  foreach ($client_orders as $order) {
    $left_col = $ccol++;
    $right_col = $ccol++;

    # Note
    $notes_lcell = "$left_col$notes_row";
    $notes_rcell = "$right_col$notes_row";
    $sheet->setCellValue($notes_lcell, $order['note']);
    $sheet->mergeCells("$notes_lcell:$notes_rcell")
          ->getStyle("$notes_lcell:$notes_rcell")->applyFromArray($notes_style);

    # Totale
    $sheet->setCellValue("$left_col$total_row", "Tot.:")
          ->getStyle("$left_col$total_row")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
    $sheet->setCellValue("$right_col$total_row", "=SUM($right_col$first_prod_row:$right_col$last_prod_row)")
          ->getStyle("$right_col$total_row")->getNumberFormat()->setFormatCode("#,##0.00 [$\xE2\x82\xAC-410];;;");
    $sheet->getStyle("$left_col$total_row:$right_col$total_row")->applyFromArray($out_borders);
  }

  # Notes and Total rows style
  $sheet->getStyle("$min_col$total_row:$max_col$total_row")->applyFromArray($fill_gray);
  $sheet->getRowDimension($notes_row)->setRowHeight(80);

  # Copy GAS sections to own sheet
  foreach ($gas_map as $gas_range) {
    # Get values from main sheet
    $prod_values = $sheet->rangeToArray("A1:C$last_prod_row", null, true, FALSE);
    $gas_values = $sheet->rangeToArray("${gas_range['start']}1:${gas_range['end']}$notes_row", null, true, FALSE);

    # Create new sheet for GAS (with data from main sheet)
    $gas_sheet = $excel->createSheet()
                         ->setTitle($gas_range['name'])->fromArray($prod_values)
                         ->fromArray($gas_values, null, 'D1');
    $row = $first_prod_row;
    foreach ($gas_range['tot'] as $prod_id => $tot_qty) {
      $gas_sheet->setCellValue("C$row", $tot_qty);
      $row++;
    }

    # Copy style
    $gas_sheet->getColumnDimension('A')->setAutoSize(TRUE);
    $gas_sheet->getColumnDimension('B')->setWidth(12);
    $gas_sheet->getColumnDimension('C')->setWidth(12);
    for ($row=1; $row<=$notes_row; $row++) {
      $gas_sheet->getRowDimension($row)->setRowHeight($sheet->getRowDimension($row)->getRowHeight());
      for ($col='A'; $col<'D'; $col++) {
        $gas_sheet->duplicateStyle($sheet->getStyle("$col$row"), "$col$row");
      }
      for ($src_col=$gas_range['start'], $dest_col = $min_col; $src_col<=$gas_range['end']; $src_col++, $dest_col++) {
        $src_col_start = $src_col++;
        $src_col_end = $src_col;
        $dest_col_start = $dest_col++;
        $dest_col_end = $dest_col;

        $gas_sheet->getColumnDimension($dest_col_start)->setWidth($sheet->getColumnDimension($src_col_start)->getWidth());
        $gas_sheet->getColumnDimension($dest_col_end)->setWidth($sheet->getColumnDimension($src_col_end)->getWidth());
        $gas_sheet->duplicateStyle($sheet->getStyle("$src_col_start$row"), "$dest_col_start$row");
        $gas_sheet->duplicateStyle($sheet->getStyle("$src_col_end$row"), "$dest_col_end$row");
        $gas_sheet->mergeCells("${dest_col_start}1:${dest_col_end}1");
        $gas_sheet->mergeCells("$dest_col_start$notes_row:$dest_col_end$notes_row");
      }
    }
    $gas_sheet->mergeCells("${min_col}2:${dest_col_end}2");
  }

  if ($ext === 'xlsx') {
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel2007');
  } else {
    header('Content-Type: application/vnd.ms-excel');
    $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
  }

  header('Content-Disposition: attachment; filename="Ordini_'.$week_start.'_'.date('YmdHis').'.'.$ext.'"');
  header('Cache-Control: max-age=0');
  $writer->setPreCalculateFormulas(TRUE)->save('php://output');

  $excel->disconnectWorksheets();
  return 0;
}
