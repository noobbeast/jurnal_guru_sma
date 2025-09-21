<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

$stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE role = 'guru'");
$stmt->execute();
$total_guru = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM kelas");
$stmt->execute();
$total_kelas = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM siswa");
$stmt->execute();
$total_siswa = $stmt->fetchColumn();

$stmt = $conn->prepare("SELECT COUNT(*) FROM jurnal");
$stmt->execute();
$total_jurnal = $stmt->fetchColumn();

$content = '
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>' . $total_guru . '</h3>
                <p>Total Guru</p>
            </div>
            <div class="icon">
                <i class="fas fa-chalkboard-teacher"></i>
            </div>
            <a href="data_guru.php" class="small-box-footer">Lihat <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>' . $total_kelas . '</h3>
                <p>Total Kelas</p>
            </div>
            <div class="icon">
                <i class="fas fa-school"></i>
            </div>
            <a href="data_kelas.php" class="small-box-footer">Lihat <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>' . $total_siswa . '</h3>
                <p>Total Siswa</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="data_siswa.php" class="small-box-footer">Lihat <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>' . $total_jurnal . '</h3>
                <p>Total Jurnal</p>
            </div>
            <div class="icon">
                <i class="fas fa-clipboard-list"></i>
            </div>
            <a href="rekap_jurnal.php" class="small-box-footer">Lihat <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Selamat Datang, Admin</h3>
            </div>
            <div class="card-body">
                <p>Aplikasi ini membantu Anda memantau kegiatan mengajar guru dan absensi siswa secara real-time.</p>
            </div>
        </div>
    </div>
</div>
';

$title = "Dashboard Admin";
include 'template.php';
?>