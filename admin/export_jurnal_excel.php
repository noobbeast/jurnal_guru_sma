<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Akses ditolak');
}

include '../koneksi.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Ambil filter dari GET
$filter_kelas = $_GET['kelas_id'] ?? '';
$filter_guru = $_GET['guru_id'] ?? '';
$filter_mapel = $_GET['mapel_id'] ?? '';
$filter_bulan = $_GET['bulan'] ?? date('Y-m');

// Query data jurnal
$sql = "SELECT j.tanggal, j.jam_ke, u.nama as nama_guru, k.nama_kelas, m.nama_mapel, j.materi
        FROM jurnal j
        JOIN guru g ON j.guru_id = g.id
        JOIN users u ON g.user_id = u.id
        JOIN kelas k ON j.kelas_id = k.id
        JOIN mata_pelajaran m ON j.mapel_id = m.id
        WHERE 1=1";

$params = [];

if ($filter_kelas) {
    $sql .= " AND j.kelas_id = ?";
    $params[] = $filter_kelas;
}
if ($filter_guru) {
    $sql .= " AND j.guru_id = ?";
    $params[] = $filter_guru;
}
if ($filter_mapel) {
    $sql .= " AND j.mapel_id = ?";
    $params[] = $filter_mapel;
}
if ($filter_bulan) {
    $sql .= " AND j.tanggal LIKE ?";
    $params[] = $filter_bulan . '%';
}

$sql .= " ORDER BY j.tanggal DESC, k.nama_kelas, u.nama";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$jurnal_list = $stmt->fetchAll();

// Buat spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Judul
$sheet->setCellValue('A1', 'REKAP JURNAL MENGAJAR');
$sheet->mergeCells('A1:F1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Subjudul filter
$row = 2;
$sheet->setCellValue('A' . $row, 'Periode: ' . ($filter_bulan ? date('F Y', strtotime($filter_bulan)) : 'Semua Waktu'));
$row++;
if ($filter_kelas) {
    $stmt_kelas = $conn->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
    $stmt_kelas->execute([$filter_kelas]);
    $kelas = $stmt_kelas->fetch();
    $sheet->setCellValue('A' . $row, 'Kelas: ' . $kelas['nama_kelas']);
    $row++;
}
if ($filter_guru) {
    $stmt_guru = $conn->prepare("SELECT u.nama FROM guru g JOIN users u ON g.user_id = u.id WHERE g.id = ?");
    $stmt_guru->execute([$filter_guru]);
    $guru = $stmt_guru->fetch();
    $sheet->setCellValue('A' . $row, 'Guru: ' . $guru['nama']);
    $row++;
}
if ($filter_mapel) {
    $stmt_mapel = $conn->prepare("SELECT nama_mapel FROM mata_pelajaran WHERE id = ?");
    $stmt_mapel->execute([$filter_mapel]);
    $mapel = $stmt_mapel->fetch();
    $sheet->setCellValue('A' . $row, 'Mapel: ' . $mapel['nama_mapel']);
    $row++;
}
$row++;

// Header tabel
$headers = ['Tanggal', 'Jam ke-', 'Guru', 'Kelas', 'Mapel', 'Materi'];
$colLetters = ['A', 'B', 'C', 'D', 'E', 'F'];

// Set header
foreach ($headers as $index => $header) {
    $sheet->setCellValue($colLetters[$index] . $row, $header);
    $sheet->getStyle($colLetters[$index] . $row)->getFont()->setBold(true);
    $sheet->getColumnDimension($colLetters[$index])->setAutoSize(true);
}
$row++;

// Isi data
foreach ($jurnal_list as $jurnal) {
    $sheet->setCellValue('A' . $row, format_tanggal_indonesia($jurnal['tanggal']));
    $sheet->setCellValue('B' . $row, $jurnal['jam_ke'] ?? '-');
    $sheet->setCellValue('C' . $row, $jurnal['nama_guru']);
    $sheet->setCellValue('D' . $row, $jurnal['nama_kelas']);
    $sheet->setCellValue('E' . $row, $jurnal['nama_mapel']);
    $sheet->setCellValue('F' . $row, $jurnal['materi']);
    $row++;
}

// Set alignment
$sheet->getStyle('A4:F' . ($row - 1))->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
$sheet->getStyle('A4:F4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAD3'); // Warna header hijau muda

// Response ke browser
$filename = 'Rekap_Jurnal_' . date('Ymd') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;

// Fungsi format tanggal
function format_tanggal_indonesia($tanggal_mysql) {
    $hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    $bulan = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];
    $timestamp = strtotime($tanggal_mysql);
    $nama_hari = $hari[date('w', $timestamp)];
    $tanggal = date('j', $timestamp);
    $nama_bulan = $bulan[date('m', $timestamp)];
    $tahun = date('Y', $timestamp);
    return "$nama_hari, $tanggal $nama_bulan $tahun";
}
?>