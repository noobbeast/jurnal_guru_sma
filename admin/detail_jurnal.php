<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

if (!isset($_GET['id'])) {
    die("ID jurnal tidak ditemukan.");
}

$jurnal_id = $_GET['id'];

// Ambil info jurnal
$sql = "SELECT j.tanggal, u.nama as nama_guru, k.nama_kelas, m.nama_mapel, j.materi
        FROM jurnal j
        JOIN guru g ON j.guru_id = g.id
        JOIN users u ON g.user_id = u.id
        JOIN kelas k ON j.kelas_id = k.id
        JOIN mata_pelajaran m ON j.mapel_id = m.id
        WHERE j.id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$jurnal_id]);
$jurnal = $stmt->fetch();

if (!$jurnal) {
    die("Jurnal tidak ditemukan.");
}

// Ambil absensi siswa
$sql = "SELECT s.nama, a.status 
        FROM absensi a
        JOIN siswa s ON a.siswa_id = s.id
        WHERE a.jurnal_id = ?
        ORDER BY s.nama ASC";
$stmt = $conn->prepare($sql);
$stmt->execute([$jurnal_id]);
$absensi_list = $stmt->fetchAll();

// Kelompokkan berdasarkan status
$hadir = [];
$sakit = [];
$izin = [];
$alfa = [];

foreach ($absensi_list as $absen) {
    switch ($absen['status']) {
        case 'H': $hadir[] = $absen['nama']; break;
        case 'S': $sakit[] = $absen['nama']; break;
        case 'I': $izin[] = $absen['nama']; break;
        case 'A': $alfa[] = $absen['nama']; break;
    }
}

$content = '
<div class="card">
    <div class="card-header bg-gradient bg-info">
        <h3 class="card-title">Detail Jurnal & Absensi</h3>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <h5>Info Jurnal</h5>
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Tanggal</strong></td>
                        <td>: ' . format_tanggal_indonesia($jurnal['tanggal']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Guru</strong></td>
                        <td>: ' . htmlspecialchars($jurnal['nama_guru']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Kelas</strong></td>
                        <td>: ' . htmlspecialchars($jurnal['nama_kelas']) . '</td>
                    </tr>
                    <tr>
                        <td><strong>Mapel</strong></td>
                        <td>: ' . htmlspecialchars($jurnal['nama_mapel']) . '</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h5>Materi</h5>
                <div class="bg-light p-3 rounded">
                    ' . nl2br(htmlspecialchars($jurnal['materi'])) . '
                </div>
            </div>
        </div>

        <h5 class="mt-4">📊 Rekap Kehadiran Siswa</h5>
        <div class="row">
            <!-- Hadir -->
            <div class="col-md-6 col-lg-3">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3>' . count($hadir) . '</h3>
                        <p>Hadir</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="bg-light p-2 rounded small" style="max-height: 150px; overflow-y: auto;">
                    <ul class="mb-0">
                        ' . (count($hadir) > 0 ? 
                            implode('', array_map(fn($nama) => "<li>" . htmlspecialchars($nama) . "</li>", $hadir)) 
                            : "<li class='text-muted'>Tidak ada</li>") . '
                    </ul>
                </div>
            </div>

            <!-- Sakit -->
            <div class="col-md-6 col-lg-3">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3>' . count($sakit) . '</h3>
                        <p>Sakit</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-procedures"></i>
                    </div>
                </div>
                <div class="bg-light p-2 rounded small" style="max-height: 150px; overflow-y: auto;">
                    <ul class="mb-0">
                        ' . (count($sakit) > 0 ? 
                            implode('', array_map(fn($nama) => "<li>" . htmlspecialchars($nama) . "</li>", $sakit)) 
                            : "<li class='text-muted'>Tidak ada</li>") . '
                    </ul>
                </div>
            </div>

            <!-- Izin -->
            <div class="col-md-6 col-lg-3">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>' . count($izin) . '</h3>
                        <p>Izin</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                </div>
                <div class="bg-light p-2 rounded small" style="max-height: 150px; overflow-y: auto;">
                    <ul class="mb-0">
                        ' . (count($izin) > 0 ? 
                            implode('', array_map(fn($nama) => "<li>" . htmlspecialchars($nama) . "</li>", $izin)) 
                            : "<li class='text-muted'>Tidak ada</li>") . '
                    </ul>
                </div>
            </div>

            <!-- Alfa -->
            <div class="col-md-6 col-lg-3">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3>' . count($alfa) . '</h3>
                        <p>Alfa</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-user-times"></i>
                    </div>
                </div>
                <div class="bg-light p-2 rounded small" style="max-height: 150px; overflow-y: auto;">
                    <ul class="mb-0">
                        ' . (count($alfa) > 0 ? 
                            implode('', array_map(fn($nama) => "<li>" . htmlspecialchars($nama) . "</li>", $alfa)) 
                            : "<li class='text-muted'>Tidak ada</li>") . '
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<a href="rekap_jurnal.php" class="btn btn-secondary mt-3">
    <i class="fas fa-arrow-left"></i> Kembali ke Rekap
</a>
';

// Fungsi format tanggal (copy dari rekap_jurnal.php)
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

$title = "Detail Jurnal & Absensi";
include 'template.php';
?>