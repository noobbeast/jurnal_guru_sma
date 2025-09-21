<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    exit('Akses ditolak');
}

include '../koneksi.php';
require_once '../vendor/tcpdf/tcpdf.php';

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

// Buat PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($_SESSION['nama']);
$pdf->SetTitle('Laporan Nilai Siswa');
$pdf->SetSubject('Nilai Siswa');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(15, 15, 15);
$pdf->AddPage();

// Gunakan font Unicode
$pdf->SetFont('freeserif', '', 10);

// Judul
$pdf->SetFont('freeserif', 'B', 16);
$pdf->Cell(0, 10, 'LAPORAN NILAI SISWA', 0, 1, 'C');
$pdf->Ln(5);

// Info guru
$pdf->SetFont('freeserif', '', 12);
$pdf->Cell(0, 6, 'Guru: ' . $_SESSION['nama'], 0, 1);
$pdf->Cell(0, 6, 'Tanggal Export: ' . date('d M Y'), 0, 1);
$pdf->Ln(5);

// Tabel
$pdf->SetFont('freeserif', 'B', 10);
$header = ['Tanggal', 'Jenis Penilaian', 'Kelas', 'Mapel', 'Siswa', 'Nilai', 'Catatan'];
$w = [25, 35, 25, 25, 40, 20, 30]; // Lebar kolom

// Header tabel
for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Isi tabel
$pdf->SetFont('freeserif', '', 9);
foreach ($nilai_list as $nilai) {
    $pdf->Cell($w[0], 6, date('d M Y', strtotime($nilai['tanggal'])), 1);
    $pdf->Cell($w[1], 6, $nilai['jenis_penilaian'], 1);
    $pdf->Cell($w[2], 6, $nilai['nama_kelas'], 1);
    $pdf->Cell($w[3], 6, $nilai['nama_mapel'], 1);
    $pdf->Cell($w[4], 6, $nilai['nama_siswa'], 1);
    $pdf->Cell($w[5], 6, number_format($nilai['nilai'], 2), 1, 0, 'C');
    $pdf->MultiCell($w[6], 6, $nilai['catatan'] ?? '-', 1, 'L');
}

// Total
$pdf->Ln(5);
$pdf->SetFont('freeserif', 'B', 11);
$pdf->Cell(0, 8, 'Total Data Nilai: ' . count($nilai_list), 0, 1, 'R');

// Output
$filename = 'Nilai_Siswa_' . $_SESSION['nama'] . '_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D');
?>