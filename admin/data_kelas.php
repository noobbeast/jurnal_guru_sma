<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

// Ambil daftar guru untuk wali kelas
$sql_guru = "SELECT g.id, u.nama FROM guru g JOIN users u ON g.user_id = u.id ORDER BY u.nama";
$stmt = $conn->prepare($sql_guru);
$stmt->execute();
$guru_list = $stmt->fetchAll();

// Ambil semua kelas + wali kelas
$sql = "SELECT k.id, k.nama_kelas, u.nama as wali_kelas 
        FROM kelas k 
        LEFT JOIN guru g ON k.wali_kelas_id = g.id 
        LEFT JOIN users u ON g.user_id = u.id 
        ORDER BY k.nama_kelas";
$stmt = $conn->prepare($sql);
$stmt->execute();
$kelas_list = $stmt->fetchAll();

$content = '
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Data Kelas</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
                            <i class="fas fa-plus"></i> Tambah Kelas
                        </button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped text-nowrap">
                        <thead>
                            <tr>
                                <th>Nama Kelas</th>
                                <th>Wali Kelas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
';

if (count($kelas_list) > 0) {
    foreach ($kelas_list as $kelas) {
        $content .= '
                            <tr>
                                <td>' . htmlspecialchars($kelas['nama_kelas']) . '</td>
                                <td>' . htmlspecialchars($kelas['wali_kelas'] ?? '-') . '</td>
                                <td>
                                    <button class="btn btn-warning btn-sm" data-toggle="modal" data-target="#modalEdit' . $kelas['id'] . '">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <a href="proses_kelas.php?hapus=' . $kelas['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Yakin hapus kelas ini?\')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>

                            <!-- Modal Edit -->
                            <div class="modal fade" id="modalEdit' . $kelas['id'] . '">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form method="POST" action="proses_kelas.php">
                                            <input type="hidden" name="id" value="' . $kelas['id'] . '">
                                            <div class="modal-header">
                                                <h4 class="modal-title">Edit Kelas</h4>
                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                    <span aria-hidden="true">&times;</span>
                                                </button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="form-group">
                                                    <label>Nama Kelas</label>
                                                    <input type="text" name="nama_kelas" class="form-control" value="' . htmlspecialchars($kelas['nama_kelas']) . '" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Wali Kelas</label>
                                                    <select name="wali_kelas_id" class="form-control">
                                                        <option value="">-- Pilih Wali Kelas --</option>
        ';

        foreach ($guru_list as $guru) {
            $selected = ($guru['id'] == ($kelas['wali_kelas_id'] ?? '')) ? 'selected' : '';
            $content .= '<option value="' . $guru['id'] . '" ' . $selected . '>' . htmlspecialchars($guru['nama']) . '</option>';
        }

        $content .= '
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="modal-footer justify-content-between">
                                                <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                                                <button type="submit" name="edit" class="btn btn-primary">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
        ';
    }
} else {
    $content .= '
                            <tr>
                                <td colspan="3" class="text-center">Belum ada data kelas.</td>
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
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="modalTambah">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="proses_kelas.php">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Kelas Baru</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Kelas</label>
                        <input type="text" name="nama_kelas" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Wali Kelas</label>
                        <select name="wali_kelas_id" class="form-control">
                            <option value="">-- Pilih Wali Kelas --</option>
        ';

foreach ($guru_list as $guru) {
    $content .= '<option value="' . $guru['id'] . '">' . htmlspecialchars($guru['nama']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
';

if (isset($_SESSION['success'])) {
    $content = '
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Berhasil!</strong> ' . $_SESSION['success'] . '
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    ' . $content;
    unset($_SESSION['success']);
}

$title = "Data Kelas";
include 'template.php';
?>