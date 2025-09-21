<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    exit('Akses ditolak');
}

include '../koneksi.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$guru_id = $_SESSION['guru_id'];

// Ambil data nilai
$sql = "SELECT ns.tanggal, ns.jenis_penilaian, ns.nilai, ns.catatan, 
               s.nama as nama_siswa, k.nama_kelas, m.nama_mapel
        FROM nilai_siswa ns
        JOIN siswa s ON ns.siswa_id = s.id
        JOIN kelas k ON ns.kelas_id = k.id
        JOIN mata_pelajaran m ON ns.mapel_id = m.id
        WHERE ns.guru_id = ?
        ORDER BY ns.tanggal DESC, k.nama_kelas, s.nama";
$stmt = $conn->prepare($sql);
$stmt->execute([$guru_id]);
$nilai_list = $stmt->fetchAll();

// Buat spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header
$sheet->setCellValue('A1', 'LAPORAN NILAI SISWA');
$sheet->mergeCells('A1:G1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Subheader
$sheet->setCellValue('A2', 'Guru: ' . $_SESSION['nama']);
$sheet->setCellValue('A3', 'Tanggal Export: ' . date('d M Y'));
$sheet->setCellValue('A4', ' ');

// Header tabel
$headers = ['Tanggal', 'Jenis Penilaian', 'Kelas', 'Mapel', 'Siswa', 'Nilai', 'Catatan'];
$colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];

// Set header
$row = 6;
foreach ($headers as $index => $header) {
    $sheet->setCellValue($colLetters[$index] . $row, $header);
    $sheet->getStyle($colLetters[$index] . $row)->getFont()->setBold(true);
    $sheet->getColumnDimension($colLetters[$index])->setAutoSize(true);
}

// Isi data
$row = 7;
foreach ($nilai_list as $nilai) {
    $sheet->setCellValue('A' . $row, date('d M Y', strtotime($nilai['tanggal'])));
    $sheet->setCellValue('B' . $row, $nilai['jenis_penilaian']);
    $sheet->setCellValue('C' . $row, $nilai['nama_kelas']);
    $sheet->setCellValue('D' . $row, $nilai['nama_mapel']);
    $sheet->setCellValue('E' . $row, $nilai['nama_siswa']);
    $sheet->setCellValue('F' . $row, $nilai['nilai']);
    $sheet->setCellValue('G' . $row, $nilai['catatan'] ?? '-');
    $row++;
}

// Set alignment
$sheet->getStyle('A6:G' . ($row - 1))->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
$sheet->getStyle('F7:F' . ($row - 1))->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Response ke browser
$filename = 'Nilai_Siswa_' . $_SESSION['nama'] . '_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>