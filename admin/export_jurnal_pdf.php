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
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    exit('Akses ditolak');
}

include '../koneksi.php';
require_once '../vendor/tcpdf/tcpdf.php';

// Ambil filter dari GET
$filter_kelas = $_GET['kelas_id'] ?? '';
$filter_guru = $_GET['guru_id'] ?? '';
$filter_mapel = $_GET['mapel_id'] ?? '';
$filter_bulan = $_GET['bulan'] ?? date('Y-m');

// Query data jurnal
$sql = "SELECT j.id, j.tanggal, u.nama as nama_guru, k.nama_kelas, m.nama_mapel, j.materi
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

// Buat PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document info
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Jurnal Mengajar SMA');
$pdf->SetTitle('Rekap Jurnal Mengajar');
$pdf->SetSubject('Rekap Jurnal + Absensi Siswa');

// Remove default header/footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set margins
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(TRUE, 15);

// Add a page
$pdf->AddPage();

$pdf->AddPage();
$pdf->SetFont('freeserif', '', 10); // ✅ Set default font Unicode

// Judul utama
$pdf->SetFont('freeserif', 'B', 16); // ✅ Ganti ke freeserif
$pdf->Cell(0, 10, 'REKAP JURNAL MENGAJAR DAN ABSENSI SISWA', 0, 1, 'C');
$pdf->Ln(5);

// Subjudul filter
$pdf->SetFont('freeserif', '', 12); // ✅ Ganti ke freeserif
$pdf->Cell(0, 6, 'Periode: ' . ($filter_bulan ? date('F Y', strtotime($filter_bulan)) : 'Semua Waktu'), 0, 1);

if ($filter_kelas) {
    $stmt_kelas = $conn->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
    $stmt_kelas->execute([$filter_kelas]);
    $kelas = $stmt_kelas->fetch();
    $pdf->Cell(0, 6, 'Kelas: ' . $kelas['nama_kelas'], 0, 1);
}

if ($filter_guru) {
    $stmt_guru = $conn->prepare("SELECT u.nama FROM guru g JOIN users u ON g.user_id = u.id WHERE g.id = ?");
    $stmt_guru->execute([$filter_guru]);
    $guru = $stmt_guru->fetch();
    $pdf->Cell(0, 6, 'Guru: ' . $guru['nama'], 0, 1);
}

if ($filter_mapel) {
    $stmt_mapel = $conn->prepare("SELECT nama_mapel FROM mata_pelajaran WHERE id = ?");
    $stmt_mapel->execute([$filter_mapel]);
    $mapel = $stmt_mapel->fetch();
    $pdf->Cell(0, 6, 'Mapel: ' . $mapel['nama_mapel'], 0, 1);
}

$pdf->Ln(10);

// Tabel Rekap Jurnal
$pdf->SetFont('freeserif', 'B', 12);
$pdf->Cell(0, 8,  'REKAP JURNAL MENGAJAR', 0, 1, 'L');
$pdf->Ln(3);

$pdf->SetFont('freeserif', 'B', 10);
$pdf->SetFillColor(230, 230, 230);

$header = ['Tanggal', 'Guru', 'Kelas', 'Mapel', 'Materi'];
$w = [40, 35, 30, 30, 55];

// Header tabel
for ($i = 0; $i < count($header); $i++) {
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', 1);
}
$pdf->Ln();

// Isi tabel
$pdf->SetFont('freeserif', '', 9);
foreach ($jurnal_list as $jurnal) {
    $pdf->Cell($w[0], 6, format_tanggal_indonesia($jurnal['tanggal']), 1);
    $pdf->Cell($w[1], 6, $jurnal['nama_guru'], 1);
    $pdf->Cell($w[2], 6, $jurnal['nama_kelas'], 1);
    $pdf->Cell($w[3], 6, $jurnal['nama_mapel'], 1);
    $pdf->MultiCell($w[4], 6, $jurnal['materi'], 1, 'L');
}
$pdf->Ln(10);

// Detail Kehadiran Siswa per Jurnal
$pdf->SetFont('freeserif', 'B', 12);
$pdf->Cell(0, 8, ' DETAIL KEHADIRAN SISWA PER JURNAL', 0, 1, 'L');
$pdf->Ln(5);

$pdf->SetFont('freeserif', '', 9);
foreach ($jurnal_list as $jurnal) {
    // Ambil absensi siswa untuk jurnal ini
    $stmt_absen = $conn->prepare("
        SELECT s.nama, a.status 
        FROM absensi a
        JOIN siswa s ON a.siswa_id = s.id
        WHERE a.jurnal_id = ?
        ORDER BY FIELD(a.status, 'H', 'S', 'I', 'A'), s.nama
    ");
    $stmt_absen->execute([$jurnal['id']]);
    $absensi = $stmt_absen->fetchAll();

    // Kelompokkan berdasarkan status
    $status_groups = [
        'H' => ['Hadir', []],
        'S' => ['Sakit', []],
        'I' => ['Izin', []],
        'A' => ['Alfa', []]
    ];

    foreach ($absensi as $a) {
        $status_groups[$a['status']][1][] = $a['nama'];
    }

    // Judul jurnal
    $pdf->SetFont('freeserif', 'B', 10);
    $pdf->Cell(0, 6, ' ' . format_tanggal_indonesia($jurnal['tanggal']) . ' | ' . $jurnal['nama_kelas'] . ' | ' . $jurnal['nama_mapel'], 0, 1);
    $pdf->Ln(2);

    $pdf->SetFont('freeserif', '', 9);
    foreach ($status_groups as $kode => $data) {
        [$label, $siswa_list] = $data;
        if (empty($siswa_list)) continue;

        $pdf->SetFont('freeserif', 'B', 9);
        $pdf->Cell(0, 5, '» ' . $label . ' (' . count($siswa_list) . ' siswa):', 0, 1);
        $pdf->SetFont('freeserif', '', 9);

        // Format nama siswa: maks 3 per baris
        $lines = [];
        $current_line = [];
        foreach ($siswa_list as $index => $nama) {
            $current_line[] = $nama;
            if (($index + 1) % 3 == 0 || $index == count($siswa_list) - 1) {
                $lines[] = implode(', ', $current_line);
                $current_line = [];
            }
        }

        foreach ($lines as $line) {
            $pdf->MultiCell(0, 5, $line, 0, 'L');
        }
        $pdf->Ln(2);
    }
    $pdf->Ln(5);
}

// Total keseluruhan
$pdf->SetFont('freeserif', 'B', 11);
$pdf->Cell(0, 8, ' Total Jurnal: ' . count($jurnal_list), 0, 1, 'R');

// Output PDF (download)
$filename = 'Rekap_Jurnal_dan_Absensi_' . date('Ymd') . '.pdf';
$pdf->Output($filename, 'D');
?>