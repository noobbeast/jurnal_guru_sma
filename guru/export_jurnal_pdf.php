<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    exit('Akses ditolak');
}

include '../koneksi.php';
require_once '../vendor/tcpdf/tcpdf.php';

$guru_id = $_SESSION['guru_id'];

// Ambil filter
$filter_kelas = $_GET['kelas_id'] ?? '';

// Query data jurnal
$sql = "SELECT j.tanggal, j.jam_ke, k.nama_kelas, m.nama_mapel, j.materi
        FROM jurnal j
        JOIN kelas k ON j.kelas_id = k.id
        JOIN mata_pelajaran m ON j.mapel_id = m.id
        WHERE j.guru_id = ?";

$params = [$guru_id];

if ($filter_kelas) {
    $sql .= " AND j.kelas_id = ?";
    $params[] = $filter_kelas;
}

$sql .= " ORDER BY j.tanggal DESC, k.nama_kelas, m.nama_mapel";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$jurnal_list = $stmt->fetchAll();

// Buat PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($_SESSION['nama']);
$pdf->SetTitle('Rekap Jurnal Mengajar');
$pdf->SetSubject('Jurnal Mengajar');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

$pdf->AddPage();
$pdf->SetFont('freeserif', '', 10);

// Judul
$pdf->SetFont('freeserif', 'B', 16);
$pdf->Cell(0, 10, 'REKAP JURNAL MENGAJAR', 0, 1, 'C');
$pdf->Ln(5);

// Info guru
$pdf->SetFont('freeserif', '', 12);
$pdf->Cell(0, 6, 'Guru: ' . $_SESSION['nama'], 0, 1);

// Filter
if ($filter_kelas) {
    $stmt_kelas = $conn->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
    $stmt_kelas->execute([$filter_kelas]);
    $kelas = $stmt_kelas->fetch();
    $pdf->Cell(0, 6, 'Kelas: ' . $kelas['nama_kelas'], 0, 1);
}

$pdf->Ln(5);

// Tabel
$pdf->SetFont('freeserif', 'B', 10);
$pdf->SetFillColor(230, 230, 230);

$header = ['Tanggal', 'Jam ke-', 'Kelas', 'Mapel', 'Materi'];
$w = [35, 15, 25, 30, 60]; // Lebar kolom

// Header tabel
for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Isi tabel
$pdf->SetFont('freeserif', '', 9);
foreach ($jurnal_list as $jurnal) {
    $pdf->Cell($w[0], 6, format_tanggal_indonesia($jurnal['tanggal']), 1);
    $pdf->Cell($w[1], 6, $jurnal['jam_ke'] ? $jurnal['jam_ke'] : '-', 1);
    $pdf->Cell($w[2], 6, $jurnal['nama_kelas'], 1);
    $pdf->Cell($w[3], 6, $jurnal['nama_mapel'], 1);
    $pdf->MultiCell($w[4], 6, $jurnal['materi'], 1, 'L');
}
$pdf->Ln(10);

// Total
$pdf->SetFont('freeserif', 'B', 11);
$pdf->Cell(0, 8, 'Total Jurnal: ' . count($jurnal_list), 0, 1, 'R');

// Output
$filename = 'Jurnal_Saya_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D');

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