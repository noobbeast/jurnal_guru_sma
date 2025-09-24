<?php
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

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    exit('Akses ditolak');
}

include '../koneksi.php';
require_once '../vendor/tcpdf/tcpdf.php';

$guru_id = $_SESSION['guru_id'];
$filter_kelas = $_GET['kelas_id'] ?? '';

$sql = "SELECT j.tanggal, j.jam_ke, k.nama_kelas, m.nama_mapel, j.materi, j.foto_kegiatan
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

$pdf = new TCPDF('L', PDF_UNIT, 'A4', true, 'UTF-8', false); // Landscape orientation

$pdf->SetCreator('Jurnal Mengajar SMA');
$pdf->SetAuthor($_SESSION['nama']);
$pdf->SetTitle('Rekap Jurnal Mengajar');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);

$pdf->AddPage();
$pdf->SetFont('dejavusans', '', 10);

// Judul
$pdf->SetFont('dejavusans', 'B', 16);
$pdf->Cell(0, 10, 'REKAP JURNAL MENGAJAR', 0, 1, 'C');
$pdf->Ln(5);

// Info guru
$pdf->SetFont('dejavusans', '', 12);
$pdf->Cell(0, 6, 'Guru: ' . $_SESSION['nama'], 0, 1);

if ($filter_kelas) {
    $stmt_kelas = $conn->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
    $stmt_kelas->execute([$filter_kelas]);
    $kelas = $stmt_kelas->fetch();
    $pdf->Cell(0, 6, 'Kelas: ' . $kelas['nama_kelas'], 0, 1);
}

$pdf->Ln(5);

// Tabel dengan lebar kolom yang disesuaikan
$header = ['No', 'Tanggal', 'Jam', 'Kelas', 'Mapel', 'Materi', 'Foto'];
$w = [15, 40, 12, 20, 30, 65, 30]; // Total width ≈ 190mm (sesuai landscape A4)

$pdf->SetFont('dejavusans', 'B', 9);
// Header tabel
foreach ($header as $key => $value) {
    $pdf->Cell($w[$key], 7, $value, 1, 0, 'C');
}
$pdf->Ln();

// Isi tabel
$pdf->SetFont('dejavusans', '', 8);
$no = 1;
foreach ($jurnal_list as $jurnal) {
    // Kolom No
    $pdf->Cell($w[0], 15, $no++, 1, 0, 'C');
    
    // Kolom Tanggal
    $pdf->Cell($w[1], 15, format_tanggal_indonesia($jurnal['tanggal']), 1, 0, 'C');
    
    // Kolom Jam
    $pdf->Cell($w[2], 15, $jurnal['jam_ke'] ?: '-', 1, 0, 'C');
    
    // Kolom Kelas
    $pdf->Cell($w[3], 15, $jurnal['nama_kelas'], 1, 0, 'C');
    
    // Kolom Mapel
    $pdf->Cell($w[4], 15, $jurnal['nama_mapel'], 1, 0, 'C');
    
    // Kolom Materi (dipotong jika terlalu panjang)
    $materi = substr($jurnal['materi'], 0, 80) . (strlen($jurnal['materi']) > 80 ? '...' : '');
    $pdf->Cell($w[5], 15, $materi, 1, 0, 'L');
    
    // Kolom Foto
    $foto_path = '../uploads/' . $jurnal['foto_kegiatan'];
    if ($jurnal['foto_kegiatan'] && file_exists($foto_path)) {
        $pdf->Image($foto_path, $pdf->GetX() + 1, $pdf->GetY() + 1, 18, 13, '', '', '', false, 300, '', false, false, 0, false, false, false);
        $pdf->Cell($w[6], 15, '', 1, 0, 'C');
    } else {
        $pdf->Cell($w[6], 15, 'Tidak ada', 1, 0, 'C');
    }
    
    $pdf->Ln();
}

$pdf->Ln(5);
$pdf->SetFont('dejavusans', 'B', 10);
$pdf->Cell(0, 8, 'Total Jurnal: ' . count($jurnal_list), 0, 1, 'R');

$filename = 'Jurnal_Saya_' . date('Ymd_His') . '.pdf';
$pdf->Output($filename, 'D');
?>