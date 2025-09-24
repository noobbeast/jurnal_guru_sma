<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Akses ditolak');
}

include '../koneksi.php';
require_once '../vendor/tcpdf/tcpdf.php';

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

// Buat PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Admin Sekolah');
$pdf->SetTitle('Rekap Absensi Siswa');
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

// Judul
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 10, 'REKAP ABSENSI SISWA', 0, 1, 'C');
$pdf->Ln(5);

// Filter
if ($filter_kelas) {
    $stmt_kelas = $conn->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
    $stmt_kelas->execute([$filter_kelas]);
    $kelas = $stmt_kelas->fetch();
    $pdf->Cell(0, 6, 'Kelas: ' . $kelas['nama_kelas'], 0, 1);
}
$pdf->Ln(5);

// Tabel
$pdf->SetFont('dejavusans', 'B', 10);
$header = ['NIS', 'Nama', 'Kelas', 'Hadir', 'Sakit', 'Izin', 'Alfa', 'Total'];
$w = [20, 40, 25, 15, 15, 15, 15, 20];

// Header
for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Isi
$pdf->SetFont('dejavusans', '', 9);
foreach ($rekap_list as $siswa) {
    $pdf->Cell($w[0], 6, $siswa['nis'], 1);
    $pdf->Cell($w[1], 6, $siswa['nama'], 1);
    $pdf->Cell($w[2], 6, $siswa['nama_kelas'], 1);
    $pdf->Cell($w[3], 6, $siswa['hadir'], 1, 0, 'C');
    $pdf->Cell($w[4], 6, $siswa['sakit'], 1, 0, 'C');
    $pdf->Cell($w[5], 6, $siswa['izin'], 1, 0, 'C');
    $pdf->Cell($w[6], 6, $siswa['alfa'], 1, 0, 'C');
    $pdf->Cell($w[7], 6, $siswa['total_jurnal'], 1, 0, 'C');
    $pdf->Ln();
}

// Output
$filename = 'Rekap_Absensi_Siswa_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D');
?>