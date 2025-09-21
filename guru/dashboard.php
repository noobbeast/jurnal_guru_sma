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
    SELECT j.tanggal, k.nama_kelas, m.nama_mapel, j.materi 
    FROM jurnal j
    JOIN kelas k ON j.kelas_id = k.id
    JOIN mata_pelajaran m ON j.mapel_id = m.id
    WHERE j.guru_id = ?
    ORDER BY j.tanggal DESC LIMIT 5
");
$stmt->execute([$guru_id]);
$jurnal_terakhir = $stmt->fetchAll();

$content = '
<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>' . $total_jurnal . '</h3>
                <p>Total Jurnal Saya</p>
            </div>
            <div class="icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <a href="daftar_jurnal.php" class="small-box-footer">Lihat Semua <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-4 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>' . $total_kelas_diampu . '</h3>
                <p>Kelas Diampu</p>
            </div>
            <div class="icon">
                <i class="fas fa-chalkboard"></i>
            </div>
            <a href="daftar_jurnal.php" class="small-box-footer">Detail <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-4 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>📝</h3>
                <p>Ayo Isi Jurnal!</p>
            </div>
            <div class="icon">
                <i class="fas fa-pen"></i>
            </div>
            <a href="isi_jurnal.php" class="small-box-footer">Isi Sekarang <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Jurnal Terakhir</h3>
            </div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
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
                        <tr>
                            <td>' . date('d M Y', strtotime($jurnal['tanggal'])) . '</td>
                            <td>' . htmlspecialchars($jurnal['nama_kelas']) . '</td>
                            <td>' . htmlspecialchars($jurnal['nama_mapel']) . '</td>
                            <td>' . htmlspecialchars($jurnal['materi']) . '</td>
                        </tr>
        ';
    }
} else {
    $content .= '
                        <tr>
                            <td colspan="4" class="text-center">Belum ada jurnal.</td>
                        </tr>
        ';
}

$content .= '
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
';

$title = "Dashboard Guru";
include 'template.php';
?>