<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Akses ditolak');
}

include '../koneksi.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Ambil filter
$filter_kelas = $_GET['kelas_id'] ?? '';

// Query rekap absensi
$sql = "SELECT 
            s.nis,
            s.nama,
            k.nama_kelas,
            COUNT(CASE WHEN a.status = 'H' THEN 1 END) as hadir,
            COUNT(CASE WHEN a.status = 'S' THEN 1 END) as sakit,
            COUNT(CASE WHEN a.status = 'I' THEN 1 END) as izin,
            COUNT(CASE WHEN a.status = 'A' THEN 1 END) as alfa,
            COUNT(a.id) as total_jurnal
        FROM siswa s
        JOIN kelas k ON s.kelas_id = k.id
        LEFT JOIN absensi a ON s.id = a.siswa_id
        WHERE 1=1";

$params = [];

if ($filter_kelas) {
    $sql .= " AND k.id = ?";
    $params[] = $filter_kelas;
}

$sql .= " GROUP BY s.id, s.nis, s.nama, k.nama_kelas
          ORDER BY k.nama_kelas, s.nama";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$rekap_list = $stmt->fetchAll();

// Buat spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Judul
$sheet->setCellValue('A1', 'REKAP ABSENSI SISWA');
$sheet->mergeCells('A1:H1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Filter
$row = 2;
if ($filter_kelas) {
    $stmt_kelas = $conn->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
    $stmt_kelas->execute([$filter_kelas]);
    $kelas = $stmt_kelas->fetch();
    $sheet->setCellValue('A' . $row, 'Kelas: ' . $kelas['nama_kelas']);
    $row++;
}
$row++;

// Header tabel
$headers = ['NIS', 'Nama', 'Kelas', 'Hadir', 'Sakit', 'Izin', 'Alfa', 'Total Jurnal'];
$colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

// Set header
foreach ($headers as $index => $header) {
    $sheet->setCellValue($colLetters[$index] . $row, $header);
    $sheet->getStyle($colLetters[$index] . $row)->getFont()->setBold(true);
    $sheet->getColumnDimension($colLetters[$index])->setAutoSize(true);
    // Warna header
    $sheet->getStyle($colLetters[$index] . $row)->getFill()
         ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
         ->getStartColor()->setARGB('FFD9EAD3');
}
$row++;

// Isi data
foreach ($rekap_list as $siswa) {
    $sheet->setCellValue('A' . $row, $siswa['nis']);
    $sheet->setCellValue('B' . $row, $siswa['nama']);
    $sheet->setCellValue('C' . $row, $siswa['nama_kelas']);
    $sheet->setCellValue('D' . $row, $siswa['hadir']);
    $sheet->setCellValue('E' . $row, $siswa['sakit']);
    $sheet->setCellValue('F' . $row, $siswa['izin']);
    $sheet->setCellValue('G' . $row, $siswa['alfa']);
    $sheet->setCellValue('H' . $row, $siswa['total_jurnal']);
    
    // Warna baris
    if ($row % 2 == 0) {
        $sheet->getStyle('A' . $row . ':H' . $row)->getFill()
             ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
             ->getStartColor()->setARGB('FFF4CCCC');
    }
    $row++;
}

// Set alignment
$sheet->getStyle('D3:H' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Response ke browser
$filename = 'Rekap_Absensi_Siswa_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>