<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'guru') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

$guru_id = $_SESSION['guru_id'];

// Hitung total jurnal
$stmt = $conn->prepare("SELECT COUNT(*) FROM jurnal WHERE guru_id = ?");
$stmt->execute([$guru_id]);
$total_jurnal = $stmt->fetchColumn();

// Hitung jumlah kelas yang diampu
$stmt = $conn->prepare("SELECT COUNT(DISTINCT kelas_id) FROM guru_mapel_kelas WHERE guru_id = ?");
$stmt->execute([$guru_id]);
$total_kelas_diampu = $stmt->fetchColumn();

// Ambil 5 jurnal terakhir
$stmt = $conn->prepare("
    SELECT j.tanggal, k.nama_kelas, m.nama_mapel, j.materi, j.jam_ke
    FROM jurnal j
    JOIN kelas k ON j.kelas_id = k.id
    JOIN mata_pelajaran m ON j.mapel_id = m.id
    WHERE j.guru_id = ?
    ORDER BY j.tanggal DESC LIMIT 5
");
$stmt->execute([$guru_id]);
$jurnal_terakhir = $stmt->fetchAll();

// CSS Modern
$css = '
<style>
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem 0;
    border-radius: 0 0 2rem 2rem;
    margin-bottom: 2rem;
}
.hero-section h1 {
    font-weight: 700;
    font-size: 2.5rem;
}
.stat-card {
    border-radius: 1rem;
    transition: all 0.3s ease;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px rgba(0,0,0,0.1);
}
.stat-card .card-body {
    padding: 1.5rem;
}
.stat-card .display-4 {
    font-weight: 700;
}
.quick-action {
    padding: 1.2rem 2rem;
    font-size: 1.2rem;
    font-weight: 600;
    border-radius: 50rem;
    box-shadow: 0 4px 15px rgba(25, 135, 84, 0.4);
    transition: all 0.3s ease;
}
.quick-action:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(25, 135, 84, 0.6);
}
.recent-journal-card {
    border-left: 4px solid #0d6efd;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}
.recent-journal-card:hover {
    border-left-color: #20c997;
    transform: translateX(5px);
}
</style>
';

$content = $css . '
<!-- Hero Section -->
<div class="hero-section text-center text-white">
    <h1>👋 Halo, ' . htmlspecialchars($_SESSION['nama']) . '!</h1>
    <p class="lead">Selamat datang di dashboard jurnal mengajar Anda</p>
</div>

<!-- Quick Action -->
<div class="text-center mb-4">
    <a href="isi_jurnal.php" class="btn btn-success quick-action">
        <i class="fas fa-plus-circle me-2"></i> Isi Jurnal Baru
    </a>
</div>

<!-- Statistik -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-4">
        <div class="card stat-card border-0 text-white bg-gradient-primary">
            <div class="card-body text-center">
                <div class="display-4">' . $total_jurnal . '</div>
                <p class="mb-0">Total Jurnal</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-4">
        <div class="card stat-card border-0 text-white bg-gradient-success">
            <div class="card-body text-center">
                <div class="display-4">' . $total_kelas_diampu . '</div>
                <p class="mb-0">Kelas Diampu</p>
            </div>
        </div>
    </div>
    <div class="col-md-12 col-lg-4">
        <div class="card stat-card border-0 text-white bg-gradient-purple">
            <div class="card-body text-center">
                <div class="display-4"><i class="fas fa-clipboard-list"></i></div>
                <p class="mb-0">Ayo isi jurnal hari ini!</p>
            </div>
        </div>
    </div>
</div>

<!-- Jurnal Terakhir -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white border-0 pb-0">
        <h4 class="mb-0">📒 Jurnal Terakhir</h4>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam ke-</th>
                        <th>Kelas</th>
                        <th>Mapel</th>
                        <th>Materi</th>
                    </tr>
                </thead>
                <tbody>
';

if (count($jurnal_terakhir) > 0) {
    foreach ($jurnal_terakhir as $jurnal) {
        $content .= '
                    <tr class="recent-journal-card">
                        <td>' . format_tanggal_indonesia($jurnal['tanggal']) . '</td>
                        <td>' . ($jurnal['jam_ke'] ? $jurnal['jam_ke'] : '-') . '</td>
                        <td>' . htmlspecialchars($jurnal['nama_kelas']) . '</td>
                        <td>' . htmlspecialchars($jurnal['nama_mapel']) . '</td>
                        <td>' . htmlspecialchars($jurnal['materi']) . '</td>
                    </tr>
        ';
    }
} else {
    $content .= '
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <div class="display-4">📭</div>
                            <h5>Belum ada jurnal.</h5>
                            <p class="text-muted">Silakan isi jurnal mengajar pertama Anda.</p>
                        </td>
                    </tr>
        ';
}

$content .= '
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Footer -->
<div class="text-center mt-5 mb-3 text-muted">
    <small>&copy; 2025 Jurnal Mengajar SMA. All rights reserved.</small>
</div>
';

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

$title = "Dashboard Guru";
include 'template.php';
?>