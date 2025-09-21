<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Test Berhasil!');

$writer = new Xlsx($spreadsheet);
$writer->save('test.xlsx');

echo "✅ PhpSpreadsheet BERHASIL!";
?>