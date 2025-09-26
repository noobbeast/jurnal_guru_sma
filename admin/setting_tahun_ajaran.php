<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

// Ambil setting saat ini
$stmt = $conn->prepare("SELECT * FROM setting WHERE id = 1");
$stmt->execute();
$setting = $stmt->fetch();

// Proses update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tahun_ajaran = $_POST['tahun_ajaran'];
    $semester = $_POST['semester'];
    
    $stmt = $conn->prepare("UPDATE setting SET tahun_ajaran = ?, semester = ? WHERE id = 1");
    $stmt->execute([$tahun_ajaran, $semester]);
    
    $_SESSION['success'] = "✅ Setting tahun ajaran berhasil diperbarui!";
    header("Location: setting_tahun_ajaran.php");
    exit;
}

$content = '
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">⚙️ Setting Tahun Ajaran & Semester</h3>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Tahun Ajaran</label>
                                    <select name="tahun_ajaran" class="form-control" required>
                                        <option value="">-- Pilih Tahun Ajaran --</option>
        ';

// Generate tahun ajaran
$tahun_sekarang = date('Y');
for ($i = -2; $i <= 5; $i++) {
    $tahun1 = $tahun_sekarang + $i;
    $tahun2 = $tahun1 + 1;
    $tahun_ajaran = "$tahun1/$tahun2";
    $selected = ($tahun_ajaran == $setting['tahun_ajaran']) ? 'selected' : '';
    $content .= "<option value=\"$tahun_ajaran\" $selected>$tahun_ajaran</option>";
}

$content .= '
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Semester</label>
                                    <select name="semester" class="form-control" required>
                                        <option value="Ganjil" ' . ($setting['semester'] == 'Ganjil' ? 'selected' : '') . '>Ganjil (Juli - Desember)</option>
                                        <option value="Genap" ' . ($setting['semester'] == 'Genap' ? 'selected' : '') . '>Genap (Januari - Juni)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Setting
                        </button>
                    </form>
                    
                    <div class="alert alert-info mt-4">
                        <h5><i class="fas fa-info-circle"></i> Informasi:</h5>
                        <ul>
                            <li>Setting ini akan digunakan secara otomatis saat guru mengisi jurnal</li>
                            <li>Tahun ajaran aktif saat ini: <strong>' . htmlspecialchars($setting['tahun_ajaran']) . '</strong></li>
                            <li>Semester aktif saat ini: <strong>' . htmlspecialchars($setting['semester']) . '</strong></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
';

$title = "Setting Tahun Ajaran";
include 'template.php';
?>