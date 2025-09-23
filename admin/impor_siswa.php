<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

$content = '
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">📤 Impor Data Siswa dari Excel</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info-circle"></i> Petunjuk:</h5>
                        <ul>
                            <li>File harus berekstensi <strong>.xlsx</strong></li>
                            <li>Struktur kolom: <strong>NIS | Nama | Kelas</strong></li>
                            <li>Kolom "Kelas" harus berisi <strong>nama kelas yang sudah terdaftar</strong> (contoh: "X MIPA 1")</li>
                        </ul>
                    </div>

                    <form method="POST" action="proses_impor_siswa.php" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="file_excel">Pilih File Excel (.xlsx)</label>
                            <input type="file" name="file_excel" id="file_excel" class="form-control" accept=".xlsx" required>
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload"></i> Upload & Impor
                        </button>
                        <a href="data_siswa.php" class="btn btn-secondary">Batal</a>
                    </form>

                    <hr>

                    <h5>📥 Template Excel</h5>
                    <p>Download template kosong untuk diisi:</p>
                    <a href="template_siswa.xlsx" class="btn btn-outline-primary">
                        <i class="fas fa-download"></i> Download Template
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
';

// HANYA tampilkan notifikasi jika ada — tanpa override
if (isset($_SESSION['success'])) {
    echo $_SESSION['success']; // Langsung tampilkan — karena sudah dalam format HTML
    unset($_SESSION['success']);
}

$title = "Impor Data Siswa";
include 'template.php';
?>