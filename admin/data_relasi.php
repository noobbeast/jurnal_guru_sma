<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}
include '../koneksi.php';

// Ambil data untuk dropdown
$sql_guru = "SELECT g.id, u.nama FROM guru g JOIN users u ON g.user_id = u.id ORDER BY u.nama";
$stmt = $conn->prepare($sql_guru);
$stmt->execute();
$guru_list = $stmt->fetchAll();

$sql_mapel = "SELECT id, nama_mapel FROM mata_pelajaran ORDER BY nama_mapel";
$stmt = $conn->prepare($sql_mapel);
$stmt->execute();
$mapel_list = $stmt->fetchAll();

$sql_kelas = "SELECT id, nama_kelas FROM kelas ORDER BY nama_kelas";
$stmt = $conn->prepare($sql_kelas);
$stmt->execute();
$kelas_list = $stmt->fetchAll();

// Ambil semua relasi
$sql = "SELECT gmk.id, u.nama as nama_guru, m.nama_mapel, k.nama_kelas
        FROM guru_mapel_kelas gmk
        JOIN guru g ON gmk.guru_id = g.id
        JOIN users u ON g.user_id = u.id
        JOIN mata_pelajaran m ON gmk.mapel_id = m.id
        JOIN kelas k ON gmk.kelas_id = k.id
        ORDER BY u.nama, k.nama_kelas, m.nama_mapel";
$stmt = $conn->prepare($sql);
$stmt->execute();
$relasi_list = $stmt->fetchAll();

$content = '
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Relasi Guru - Mapel - Kelas</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTambah">
                            <i class="fas fa-plus"></i> Tambah Relasi
                        </button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover table-striped text-nowrap">
                        <thead>
                            <tr>
                                <th>Guru</th>
                                <th>Mata Pelajaran</th>
                                <th>Kelas</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
';

if (count($relasi_list) > 0) {
    foreach ($relasi_list as $relasi) {
        $content .= '
                            <tr>
                                <td>' . htmlspecialchars($relasi['nama_guru']) . '</td>
                                <td>' . htmlspecialchars($relasi['nama_mapel']) . '</td>
                                <td>' . htmlspecialchars($relasi['nama_kelas']) . '</td>
                                <td>
                                    <a href="proses_relasi.php?hapus=' . $relasi['id'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Yakin hapus relasi ini?\')">
                                        <i class="fas fa-trash"></i> Hapus
                                    </a>
                                </td>
                            </tr>
        ';
    }
} else {
    $content .= '
                            <tr>
                                <td colspan="4" class="text-center">Belum ada data relasi.</td>
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

<!-- Modal Tambah Relasi -->
<div class="modal fade" id="modalTambah">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="proses_relasi.php">
                <div class="modal-header">
                    <h4 class="modal-title">Tambah Relasi Baru</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Guru</label>
                        <select name="guru_id" class="form-control" required>
                            <option value="">-- Pilih Guru --</option>
        ';

foreach ($guru_list as $guru) {
    $content .= '<option value="' . $guru['id'] . '">' . htmlspecialchars($guru['nama']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Mata Pelajaran</label>
                        <select name="mapel_id" class="form-control" required>
                            <option value="">-- Pilih Mapel --</option>
        ';

foreach ($mapel_list as $mapel) {
    $content .= '<option value="' . $mapel['id'] . '">' . htmlspecialchars($mapel['nama_mapel']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Kelas</label>
                        <select name="kelas_id" class="form-control" required>
                            <option value="">-- Pilih Kelas --</option>
        ';

foreach ($kelas_list as $kelas) {
    $content .= '<option value="' . $kelas['id'] . '">' . htmlspecialchars($kelas['nama_kelas']) . '</option>';
}

$content .= '
                        </select>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" name="tambah" class="btn btn-primary">Simpan Relasi</button>
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

$title = "Relasi Guru-Mapel-Kelas";
include 'template.php';
?>